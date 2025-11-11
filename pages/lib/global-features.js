export function useMixin(router) {
    
  const r = router.currentRoute;

  const $log = (...a) => console.log('[LOG]', ...a);

  // --- Route Helpers ---
  const $routeGetMeta = (k = null) => k ? r.value?.meta?.[k] : r.value?.meta || {};
  const $routeAuthRole = () => {
    const path = r.value?.path || '';
    return path.startsWith('/admin') ? 'admin' : 'dealer';
  };
  const $routeAdminPrefix = () => {
    const path = r.value?.path || '';
    return path.startsWith('/admin') ? '/admin' : '';
  };
  const $routeGetName = () => r.value?.name || null;
  const $routeGetParam = (k = null) => k ? r.value?.params?.[k] : r.value?.params || {};
  const $routeGetQuery = (k = null) => k ? r.value?.query?.[k] : r.value?.query || {};
  const $routeGetPath = () => r.value?.path || '';
  const $routeIs = name => r.value?.name === name;

  const $routeSetParam = (updates = {}) => {
    const current = { ...r.value.query };
    Object.entries(updates).forEach(([key, val]) => {
      if (val === null || val === undefined) {
        delete current[key];
      } else {
        current[key] = val;
      }
    });

    router.replace({
      path: r.value.path,
      query: current
    });
  };


  const $routeTo = (route, type = '', delay = 0) => {
    setTimeout(() => {
      let finalRoute = $routeAuthRole() + route;
      if (typeof route === 'string') {
        finalRoute = route.replace(/\/{2,}/g, '/'); // remove duplicate slashes
      }

      if (type === 'refresh') {
        // ✅ Only one full reload instead of router + reload
        window.location.href = finalRoute;
      } else {
        router.push(finalRoute);
      }
    }, delay * 1000);
  };


  const $routeParams = (setParams = {}) => {
    const url = new URL(window.location.href);
    const queryParams = {};
    url.searchParams.forEach((v, k) => queryParams[k] = v);

    if (Object.keys(setParams).length > 0) {
      for (const [key, val] of Object.entries(setParams)) {
        if (val === null || val === undefined) {
          url.searchParams.delete(key);
        } else {
          url.searchParams.set(key, val);
        }
      }
      window.history.replaceState(null, '', url.toString());
    }

    return queryParams;
  };


  // --- Validation & Input ---
  const $isEmpty = str => !str || /^\s*$/.test(str);
  const $sanitizeHTML = input => String(input).replace(/<\/?[^>]+(>|$)/g, '').trim();
  const $numOnly = e => { if (!/\d/.test(e.key)) e.preventDefault(); };
  const $alphaNumOnly = e => { if (!/^[a-zA-Z0-9]$/.test(e.key)) e.preventDefault(); };
  const $isValidEmail = str => /^(?=.{5,100}$)([A-Za-z0-9._-]+@[A-Za-z0-9._-]+\.[A-Za-z]{2,5})$/.test(String(str).trim());
  const $capitalize = str => str.replace(/\b\w/g, ch => ch.toUpperCase());
  const $limitLength = (e, maxLength) => {
    if (e.target.value.length >= maxLength && !['Backspace', 'Delete'].includes(e.key)) {
      e.preventDefault();
    }
  };

// --- Time Formatting ---
  const $formatTime = (timestamp) => {
    if (!timestamp) return '';
    if (timestamp === '0000-00-00 00:00:00' || timestamp === '0000-00-00') {
      return '';
    }

    let ts = typeof timestamp === 'string'
      ? timestamp.replace(' ', 'T')
      : timestamp;

    const date = new Date(ts);
    if (isNaN(date)) return '';

    // Check if only date (YYYY-MM-DD format)
    const isOnlyDate = /^\d{4}-\d{2}-\d{2}$/.test(timestamp);

    if (isOnlyDate) {
      return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        timeZone: 'Asia/Kolkata'
      })
        .format(date)
        .replace(/ /g, '-'); // 11-Sep-2025
    }

    return new Intl.DateTimeFormat('en-GB', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
      timeZone: 'Asia/Kolkata'
    })
      .format(date)
      .replace(',', '')      
      .replace(' ', '-')     
      .replace(' ', '-');     
  };


  const $formattedCurrency = () => { return (number) => { return new Intl.NumberFormat('en-IN', {style: 'currency', currency: 'INR', minimumFractionDigits: 0}).format(number); }; };
  const $formattedNumber = () => { return (number) => { return new Intl.NumberFormat('en-IN').format(number); }; };


  const $formatNumberText = (value) => {
      if (value === null || value === undefined || value === '') return '';
      let rawStr;
      try {
        rawStr = typeof value === 'string' ? value : String(value);
      } catch (e) {
        return '';
      }

      const cleaned = rawStr.replace(/[^0-9.\-]+/g, '').trim();
      if (cleaned === '' || cleaned === '.' || cleaned === '-' || cleaned === '-.' ) return '';

      let num = Number(cleaned);
      if (!isFinite(num)) {
        try {
          if (/^-?\d+$/.test(cleaned)) {
            const bi = BigInt(cleaned);
            return `${String(bi)} Rupees Only`;
          }
        } catch (e) {}
        return '';
      }

      if (isNaN(num)) return '';

      const isNegative = num < 0;
      num = Math.abs(num);

      // Separate integer and paise (2 decimal places). Handle rounding carry.
      let integerPart = Math.floor(num);
      let decimalPart = Math.round((num - integerPart) * 100);
      if (decimalPart === 100) {
        integerPart += 1;
        decimalPart = 0;
      }

      if (integerPart === 0 && decimalPart === 0) {
        return 'Zero Rupees Only';
      }

      // Word maps
      const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
      const teens = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
      const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

      // Convert a number < 1000 to words (no trailing punctuation)
      const convertChunkToWords = (n) => {
        n = Math.floor(n);
        if (n === 0) return '';
        let words = '';

        if (n >= 100) {
          const h = Math.floor(n / 100);
          words += units[h] + ' Hundred';
          n = n % 100;
          if (n) words += ' ';
        }

        if (n >= 20) {
          const t = Math.floor(n / 10);
          words += tens[t];
          n = n % 10;
          if (n) words += ' ' + units[n];
        } else if (n >= 10) {
          words += teens[n - 10];
        } else if (n > 0) {
          words += units[n];
        }

        return words;
      };

      const parts = [];

      // Indian numbering: Crore (1e7), Lakh (1e5), Thousand (1e3), remaining (<1000)
      const crore = Math.floor(integerPart / 10000000);
      if (crore > 0) {
        parts.push(convertChunkToWords(crore) + ' Crore');
        integerPart = integerPart % 10000000;
      }

      const lakh = Math.floor(integerPart / 100000);
      if (lakh > 0) {
        parts.push(convertChunkToWords(lakh) + ' Lakh');
        integerPart = integerPart % 100000;
      }

      const thousand = Math.floor(integerPart / 1000);
      if (thousand > 0) {
        parts.push(convertChunkToWords(thousand) + ' Thousand');
        integerPart = integerPart % 1000;
      }

      if (integerPart > 0) {
        parts.push(convertChunkToWords(integerPart));
      }

      let result = parts.join(' ').trim();
      if (result) result += ' Rupees';

      if (decimalPart > 0) {
        const paiseText = convertChunkToWords(decimalPart);
        if (paiseText) {
          result += (result ? ' and ' : '') + paiseText + ' Paise';
        }
      }

      if (!result) return '';

      if (isNegative) result = 'Minus ' + result;

      return result + ' Only';
    };

  // --- Toast Alerts ---
  const $toast = (type = 'info', msg = '', time = 3000) => {
  const container = document.getElementById('alert-container') || (() => {
    const c = document.createElement('div');
    c.id = 'alert-container';
    document.body.appendChild(c);
    return c;
  })();

  const alert = document.createElement('div');
  alert.className = `alert alert-${type} alert-dismissible fade show my-toast`;
  alert.role = 'alert';
  alert.innerHTML = `
    <span>${msg}</span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  container.appendChild(alert);

  setTimeout(() => {
    alert.classList.remove('show');
    setTimeout(() => alert.remove(), 150);
  }, time);
};


  const $downloadFile = (fileUrl, fileName = '') => {
    if (!fileUrl) {
      console.error("No file URL provided for download");
    }
    const link = document.createElement('a');
    link.href = fileUrl;
    if (fileName) {
      link.download = fileName;  // if empty browser use fileName from server
    }
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }


  const $setFieldAttributes = (field) => {
    const v = field.validation || {};
    const attrs = {};
    if (v.disabled) attrs.disabled = true;
    if (v.required) attrs.required = true;
    if (v.maxlength && field.type === 'text') attrs.maxlength = v.maxlength;
    if (v.readonly) {
      if (field.type === 'text' || field.type === 'area' || field.type === 'date') {
        attrs.readonly = true;
        attrs.class = 'bg-body-secondary text-muted';
      }
      if (field.type === 'select') {
        attrs.disabled = true;
        attrs['data-readonly'] = true;
        attrs.class = 'bg-body-secondary text-muted';
      }
    }
    return attrs;
  }


  const $setDateAttributes = (field, calendarType)  => {
      const today = new Date();
      const formatDate = (d) => {
          if (['calender_time'].includes(field.inputType || field.type)) {
              return d.toISOString().slice(0,16); 
          }
          return d.toISOString().split('T')[0];
      };

      let attrs = {};
      switch(calendarType) {
          case 'past':
              attrs.max = formatDate(today);
              break;
          case 'future':
              today.setDate(today.getDate() + 1);
              attrs.min = formatDate(today);
              break;
          case 'upto_current':
              attrs.max = formatDate(today);
              break;
          case 'from_current':
              attrs.min = formatDate(today);
              break;
          case 'range':
              if (field.minDate) attrs.min = field.minDate;
              if (field.maxDate) attrs.max = field.maxDate;
              break;
          // default: no restriction
      }
      return attrs;
   }

  const $setFileAccept = (patterns = []) => {
    if (!Array.isArray(patterns) || !patterns.length) return '';

    const map = {
      images: 'image/*',
      pdf: '.pdf',
      doc: '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      excel: '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      text: '.txt,text/plain',
    };

    return patterns
      .map(t => {
        t = String(t).trim().toLowerCase();
        if (map[t]) return map[t];
        // Auto-handle direct MIME types or extensions
        if (t.startsWith('.') || t.includes('/')) return t;
        return '.' + t.replace(/^\./, '');
      })
      .join(',');
  };


  return {
    // Core
    $log,
    // Route helpers
    $routeGetMeta,
    $routeAdminPrefix,
    $routeGetName,
    $routeGetQuery,
    $routeGetParam,
    $routeGetPath,
    $routeAuthRole,
    $routeIs,
    $routeTo,
    $routeParams,
    $routeSetParam,

    // Validation
    $isEmpty,
    $sanitizeHTML,
    $numOnly,
    $alphaNumOnly,
    $isValidEmail,
    $capitalize,
    $limitLength,

    // Time
    $formatTime,
    $formattedCurrency,
    $formattedNumber, 
    $formatNumberText,

    // Alerts
    $toast,

    $setFieldAttributes,
    $setDateAttributes,
    $setFileAccept,
    $downloadFile
  };
}
