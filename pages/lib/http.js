export function useHttp() {
  // --- Constants ---
  const DEFAULT_TIMEOUT = 30000, DEFAULT_RETRIES = 1, DEFAULT_AUTH = true, DEVICE_TYPE = 'web', USE_ENCRYPT = true;

  // --- State ---
  let $isProcessing = false, isRefreshingToken = false, tokenRefreshPromise = null;
  const jobQueue = [];

 // --- Generate or Get Device ID ---
const getDeviceId = () => {
  const DEVICE_ID_KEY = 'jlr_x-device-id';
  let id = localStorage.getItem(DEVICE_ID_KEY);
  if (!id) {
    const ua = navigator.userAgent;
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    // Generate 5 parts of 5 characters each
    const parts = [];
    for (let i = 0; i < 5; i++) {
      let part = '';
      for (let j = 0; j < 5; j++) {
        const byte = crypto.getRandomValues(new Uint8Array(1))[0];
        part += chars[byte % chars.length];
      }
      parts.push(part);
    }

    const rawId = parts.join('-');
    // Signature from rawId + UA
    const combined = rawId + ua;
    let hash = 0;
    for (let i = 0; i < combined.length; i++) {
      hash = ((hash << 5) - hash) + combined.charCodeAt(i);
      hash |= 0;
    }

    const signature = Math.abs(hash).toString(36);
    id = `${rawId}.${signature}`;
    localStorage.setItem(DEVICE_ID_KEY, id);
  }
  return id;
};
const deviceId = getDeviceId();

    // --- AES-GCM Crypto Helpers ---
    const getCryptoKey = async () => {
      const deviceId = getDeviceId(); // e.g., abcde-fghij-klmno-pqrst-uvwxy.hash
      const base = deviceId.split('.')[0].replace(/-/g, ''); // Strip hyphens → 25 chars

      // Normalize to 16 bytes (AES-128): repeat or slice
      const keyStr = (base + base).slice(0, 16);
      const rawBytes = new TextEncoder().encode(keyStr);
      return crypto.subtle.importKey(
        'raw',
        rawBytes,
        { name: 'AES-GCM' },
        false,
        ['encrypt', 'decrypt']
      );
    };

    // --- Encrypt Data ---
    const encryptData = async (data) => {
      if (!USE_ENCRYPT) return data; // no encryption
      const iv = crypto.getRandomValues(new Uint8Array(12));
      const key = await getCryptoKey();
      const encoded = new TextEncoder().encode(JSON.stringify(data));
      const encrypted = await crypto.subtle.encrypt({ name: "AES-GCM", iv }, key, encoded);
      return {
        iv: Array.from(iv),
        data: Array.from(new Uint8Array(encrypted))
      };
    };

    // --- Decrypt Data ---
    const decryptData = async ({ iv, data }) => {
      if (!USE_ENCRYPT) return data; // no decryption
      const key = await getCryptoKey();
      const decrypted = await crypto.subtle.decrypt(
        { name: "AES-GCM", iv: new Uint8Array(iv) },
        key,
        new Uint8Array(data)
      );
      return JSON.parse(new TextDecoder().decode(decrypted));
    };

    // --- Secure Storage ---
    const $secureStorage = async (action, key, value = null) => {
      key = `jlr_${key}`;

      $log('*storage--------' + action);
      $log(key);

      if (["set", "remove"].includes(action)) {
        const oldValue = localStorage.getItem(key);
        let newValue = null;

        if (action === "set") {
          const payload = USE_ENCRYPT ? await encryptData({ value }) : { value };
          newValue = JSON.stringify(payload);
          localStorage.setItem(key, newValue);
        } else {
          localStorage.removeItem(key);
        }

        window.dispatchEvent(new StorageEvent("storage", {
          key,
          oldValue,
          newValue,
          storageArea: localStorage,
          url: location.href,
        }));
        return;
      }

      if (action === "get") {
        try {
          const raw = localStorage.getItem(key);
          if (!raw) return null;

          const parsed = JSON.parse(raw);
          const result = USE_ENCRYPT ? await decryptData(parsed) : parsed;

          $log(result.value ?? null);
          $log('----------------------------------------------------');
          return result.value ?? null;
        } catch {
          localStorage.removeItem(key);
          return null;
        }
      }

      $log(`Unknown secureStorage action: ${action}`);
    };



  // --- Token Refresh ---
  const getAccessToken = async () => {
    if (isRefreshingToken) return tokenRefreshPromise;
    isRefreshingToken = true;
    tokenRefreshPromise = (async () => {
      const { refresh_token: refreshToken, access_token: accessToken } = await $secureStorage('get', 'auth') || {};
      if (!refreshToken || !accessToken) return null;
      try {
        const formData = new FormData();
        formData.append('action', 'getaccesstoken');
        formData.append('refresh_token', refreshToken);
        const res = await fetch(`${window.g?.$base_url_api || ''}/auth`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${accessToken}`,
            'X-Device-ID': deviceId,
            'X-Device-Type': DEVICE_TYPE
          },
          credentials: "include", 
          body: formData,
        });
        if (!res.ok) throw new Error("Token refresh failed");
        const data = (await res.json()).data;
          $secureStorage('set', 'auth', { refresh_token: data.refresh_token, access_token: data.access_token });
          $secureStorage('set', 'logged', data.auth_user);
        return data.access_token;
      } catch {
          $log('catch------getaccesshttpcall&removeauth');


          $secureStorage('remove', 'auth');
          $secureStorage('remove', 'logged');
        return null;
      } finally {
        isRefreshingToken = false;
        tokenRefreshPromise = null;
      }
    })();
    return tokenRefreshPromise;
  };

  // --- HTTP Core ---
  const fetchWithTimeout = (url, opts, timeout = DEFAULT_TIMEOUT) => {
    const ctrl = new AbortController();
    const id = setTimeout(() => ctrl.abort(), timeout);
    opts.signal = ctrl.signal;
    return fetch(url, opts).finally(() => clearTimeout(id));
  };

  const processJobs = async () => {
    if ($isProcessing || !jobQueue.length) return;
    $isProcessing = true;
    while (jobQueue.length) {
      const { method, url, data, headers, options, resolve, reject } = jobQueue.shift();
      let attempt = 0, retriedAfterRefresh = false, currentToken = null;
      const retries = options.retries ?? DEFAULT_RETRIES;
      const timeout = options.timeout ?? DEFAULT_TIMEOUT;
      const auth = options.auth ?? DEFAULT_AUTH;

      while (attempt <= retries) {
        attempt++;
        try {
          let reqUrl = url;
          const opts = { method, headers: { ...headers } };

          // Auth
          if (auth) {
            if (!currentToken) {
              const { access_token } = await $secureStorage('get', 'auth') || {};
              const loggedIn = await $secureStorage('get', 'logged');
              currentToken = access_token || (loggedIn && await getAccessToken());
              if (!currentToken) return reject({ status: 401, body: { message: 'Unauthorized' } });
            }
            opts.headers['Authorization'] = `Bearer ${currentToken}`;
          }
          opts.headers['X-Device-ID'] = deviceId;
          opts.headers['X-Device-Type'] = DEVICE_TYPE;
          opts.credentials = 'include';
          // Data
          if (method === 'GET' && data && typeof data === 'object') {
            reqUrl += (reqUrl.includes('?') ? '&' : '?') + new URLSearchParams(data);
          } else if (data instanceof FormData) {
            opts.body = data;
          } else if (data && typeof data === 'object') {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(data);
          }

          const res = await fetchWithTimeout(reqUrl, opts, timeout);
          const isJson = (res.headers.get('content-type') || '').includes('application/json');
          const parsed = isJson ? await res.json() : await res.text();
          const result = { status: res.status, body: parsed };

          if (res.ok) { resolve(result); break; }
          if (res.status === 401 && auth && !retriedAfterRefresh) {
            retriedAfterRefresh = true;
            currentToken = await getAccessToken();
            if (!currentToken) { reject({ status: 401, body: { message: 'Unauthorized' } }); break; }
            attempt--; continue;
          }
          reject(result); break;
        } catch (e) {
          if (attempt > retries) reject({ status: 0, body: { message: e.message } });
        }
      }
    }
    $isProcessing = false;
    if (jobQueue.length) processJobs();
  };

  // --- API ---
  const $http = (method, url, data = {}, headers = {}, options = {}) =>
    new Promise((resolve, reject) => {
      if (!url) return reject(new Error("Missing URL"));
      const fullUrl = url.startsWith('http') ? url : `${window.g?.$base_url_api || ''}${url}`;
      jobQueue.push({ method: method.toUpperCase(), url: fullUrl, data, headers, options, resolve, reject });
      processJobs();
  });

  const $importComponent = async (paths = []) => {
    try {
      const version = window.g?.$ver || Date.now().toString();
      return await Promise.all(paths.map(p => import(`${p}?v=${version}`)));
    } catch (e) {
      console.error('Dynamic import failed:', e);
      return [];
    }
  };

  const $isLoggedIn = async () => (await $secureStorage('get', 'logged') || null);

  return { $http, $importComponent, $secureStorage, $isLoggedIn, $isProcessing };
}