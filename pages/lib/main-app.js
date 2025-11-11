document.addEventListener('DOMContentLoaded', async () => {
  const initApp = async () => {
    const script = document.currentScript || document.querySelector('script[data-init="main-app"]');
    const g = Object.freeze({
      $ver: script?.getAttribute('data-ver') || '',
      $base_url: script?.getAttribute('data-base-url') || '',
      $base_url_api: script?.getAttribute('data-base-url-api') || '',
      $base_service_url: script?.getAttribute('data-base-service-url') || '',
      $env_server: script?.getAttribute('data-env-stage') || '',
    });

    Object.defineProperty(globalThis, 'g', {
      value: g,
      writable: false,
      configurable: false
    });

    const [
      http,
      mixin,
      { useUserStore },
      { router, $routeAdd, $routeHistory },
      { AuthComponent },
      { SelectSearch },
      { ImageViewer },
      { DocViewer }
    ] = await Promise.all([
      '/pages/lib/http.js',
      '/pages/lib/global-features.js',
      '/pages/stores/userStore.js',
      '/pages/lib/router.js',
      '/pages/components/auth.js',
      '/pages/lib/select.js',
      '/pages/lib/image-viewer.js',
      '/pages/lib/doc-viewer.js',
    ].map(path => import(`${path}?v=${g.$ver}`)));

    const { createApp } = Vue;
    const pinia = Pinia.createPinia();

    const app = createApp({ template: `<app-layout />` });
    app.use(pinia);

    const globalUtils = {
      ...http.useHttp(),
      ...mixin.useMixin(router),
      router,
      $routeAdd,
      $routeHistory,
    };

    Object.assign(globalThis, globalUtils, { useUserStore });
    Object.assign(app.config.globalProperties, globalUtils);

    try {
      await useUserStore().getUser();
    } catch (err) {
      console.warn('[User Init Failed]', err);
    }

    app.use(router);
    app.component('auth-component', AuthComponent);
    app.component('SelectSearch', SelectSearch);
    app.component('ImageViewer', ImageViewer);
    app.component('DocViewer', DocViewer);

    let HeaderComponent = {
      template: ''
    };

    const routes = router.getRoutes?.() || [];
    const shouldLoadHeader = routes.some(route => route.meta?.requiresAuth);

    if (shouldLoadHeader) {
      try {
        const { Header } = await import(`/pages/components/common/common_header.js?v=${g.$ver}`);
        if (Header) HeaderComponent = Header;
      } catch (err) {
        console.warn('[Header Load Failed]', err);
      }
    }
    app.component('header-component', HeaderComponent);
    app.component('app-layout', {
      template: `
    <div>
      <auth-component />
      <header-component v-if="showHeader" />
      <router-view :key="$route.fullPath" />
      <DocViewer ref="docViewer" />
    </div>
      `,
      computed: {
        showHeader() {
          return this.$route?.meta?.requiresAuth === true;
        }
      },
      mounted() {
        globalThis.$docViewer = this.$refs.docViewer;
      }
    });
    app.mount('#app');
  };

  try {
    await initApp();
  } catch (err) {
    console.error('[App Load Error]', err);
    const el = document.getElementById('app');
    if (el) {
      el.innerHTML = `
        <div style="padding:20px;border-radius:8px;background:#f8d7da;border:1px solid #f5c6cb;color:#721c24">
          <h2>Oops! App Failed to Load.</h2>
          <p>Try refreshing. If it persists, contact support.</p>
          <strong>Error:</strong>
          <pre><code>${err.message || 'Unknown error'}</code></pre>
          ${err.stack ? `
            <details>
              <summary>Stack Trace</summary>
              <pre><code>${err.stack}</code></pre>
            </details>` : ''}
        </div>
      `;
    }
  }
});
