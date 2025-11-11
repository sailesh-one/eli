export const Overview = {
  name: 'common-overview',
  components: {},
  props: { 
    store: { type: Object, required: true },
    config: { type: Object, required: true }
  },

  data: () => ({
    defaultImage: '/assets/images/default-image.jpg',
  }),

  async created() {
    this.store?.init?.();
  },

  methods: {
    // Get nested property from object
    getNestedProperty(obj, path) {
      if (!path) return obj;
      return path.split('.').reduce((acc, part) => acc?.[part], obj);
    },

    // Check if value is empty
    isEmpty(value) {
      return value === null || value === undefined || value === '' || 
             value === '0000-00-00 00:00:00' || value === '0000-00-00';
    },

    // Parse JSON strings
    parseMaybeJson(value) {
      if (typeof value !== 'string' || (!value.startsWith('{') && !value.startsWith('['))) return null;
      try {
        return JSON.parse(value);
      } catch (e) {
        return null;
      }
    },

    // Normalize URL with base path
    normalizeUrl(url) {
      if (!url) return this.defaultImage;
      if (url.startsWith('/')) {
        const baseUrl = window.g?.$base_url || window.location.origin;
        return baseUrl + url;
      }
      return url;
    },

    // Format date to DD/MM/YYYY
    formatDate(dateStr) {
      if (!dateStr || dateStr === 'NA' || dateStr === 'Not Available') {
        return 'Not Available';
      }
      
      try {
        let date;
        if (dateStr.includes('/')) {
          const [day, month, year] = dateStr.split('/');
          date = new Date(year, month - 1, day);
        } else {
          date = new Date(dateStr);
        }
        
        if (isNaN(date.getTime())) return dateStr;
        
        return date.toLocaleDateString('en-GB', {
          day: '2-digit',
          month: '2-digit', 
          year: 'numeric'
        });
      } catch (e) {
        return dateStr;
      }
    },

    // Get display value for a field based on config
    getDisplayValue(field) {
      const data = this.displayData || {};
      
      // Handle multi-key fields (comma-separated keys like 'title,first_name,last_name')
      if (typeof field.key === 'string' && field.key.includes(',')) {
        const keys = field.key.split(',').map(k => k.trim());
        const values = keys.map(k => data[k]).filter(Boolean);
        return values.length > 0 ? values.join(' ') : '-';
      }
      
      // Single key
      const value = data[field.key];
      
      if (this.isEmpty(value)) return '-';

      // Handle date formatting if field type is 'date'
      if (field.type === 'date' && typeof window.$formatTime === 'function') {
        const formatted = window.$formatTime(value);
        return formatted || '-';
      }

      // Handle JSON parsing
      const parsed = this.parseMaybeJson(value);
      if (parsed) {
        return Array.isArray(parsed) ? `${parsed.length} items` : `${Object.keys(parsed).length} items`;
      }

      return value;
    },

    // Open image viewer
    openImages(name, urls, index) {
      this.$refs.imageViewer?.openImages?.(name, urls, index);
    },

    // Open document viewer
    openDoc(name, url) {
      globalThis.$docViewer.openDoc(name, url);
    },

    // Handle image load error
    handleImageError(e) {
      e.target.src = this.defaultImage;
    },

    // Handle button actions
    handleDynamicAction(btn) {
      const { key } = btn;
      if (key && typeof this.store?.[key] === 'function') {
        this.store[key](this.displayData?.id, this.displayData, key);
      }
    }
  },

  computed: {
    // Get data from store based on config
    displayData() {
      const dataPath = this.config.meta?.dataPath || 'detail';
      return this.getNestedProperty(this.store, dataPath) || {};
    },

    // Group fields by category
    groupedFields() {
      const groups = {};
      const fields = this.config.fields || {};
      
      Object.entries(fields).forEach(([key, field]) => {
        // Skip media fields (images, documents) - only show view and date fields
        if (field.type !== 'view' && field.type !== 'date') return;
        
        const category = field.category || 'Others';
        
        if (!groups[category]) {
          groups[category] = [];
        }
        
        groups[category].push(field);
      });
      
      return groups;
    },

    // Check if data is loaded
    isLoaded() {
      const loadedCheckPath = this.config.meta?.loadedCheckPath || this.config.meta?.dataPath;
      if (loadedCheckPath) {
        const data = this.getNestedProperty(this.store, loadedCheckPath);
        return data && (data.id || Object.keys(data).length > 0);
      }
      return !!this.displayData?.id;
    },

    // Get images from data
    images() {
      const imagesData = this.displayData?.images;
      
      if (typeof imagesData === 'string') {
        const parsed = this.parseMaybeJson(imagesData);
        return parsed || {};
      }
      
      return imagesData || {};
    },

    // Get documents from data
    documents() {
      const docsData = this.displayData?.documents;
      
      if (!docsData || typeof docsData !== 'object') {
        return [];
      }
      
      return Object.entries(docsData).map(([k, v]) => ({ 
        ...v, 
        key: k 
      }));
    },

    // Config flags
    showImages() {
      return this.config.meta?.showImages !== false;
    },

    showDocuments() {
      return this.config.meta?.showDocuments !== false;
    },

    showButtons() {
      return this.config.meta?.showButtons !== false;
    },

    pageTitle() {
      return this.config.meta?.title || 'Overview';
    }
  },

  template: /*html*/`
  <div class="container p-0 m-0 ps-2 overview">
    <ImageViewer ref="imageViewer" />

    <!-- Content -->
    <div v-if="isLoaded">
      <!-- Fields Card -->
      <div class="card mb-3">
        <div class="card-header popup-head py-2">
          <h6 class="mb-0 fw-semibold small">{{ pageTitle }}</h6>
        </div>
        <div class="card-body py-2">
          <div v-for="(fields, category) in groupedFields" :key="category" class="mb-3">
            <div class="border-bottom py-2 mb-2">
              <h6 class="mb-0 fw-semibold small">{{ category }}</h6>
            </div>
            <div class="row g-3">
              <div v-for="field in fields" :key="field.key" class="col-md-3">
                <div class="small text-muted">{{ field.label }}</div>
                <div class="fw-semibold text-body">{{ getDisplayValue(field) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Images -->
      <div class="card mb-3" v-if="showImages && Object.keys(images).length">
        <div class="card-header popup-head py-2">
          <h6 class="mb-0 fw-semibold small">Images</h6>
        </div>
        <div class="card-body py-2">
          <div class="row g-2 p-2">
            <div v-for="(img, key, idx) in images" :key="key" class="col-6 col-sm-4 col-md-3 col-lg-2">
              <button 
                class="btn p-0 border-0 bg-transparent w-100"
                @click="openImages('Car Images', Object.values(images).map(i => normalizeUrl(i.imgPath || i.url)), idx)">
                <img 
                  :src="normalizeUrl(img?.imgPath || img?.url)" 
                  :alt="key + ' image'" 
                  @error="handleImageError" 
                  class="img-fluid img-thumbnail" />
                <div class="text-muted small mt-1">{{ img?.imgName || key.replace(/[_-]/g, ' ') }}</div>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents -->
      <div class="card mb-3" v-if="showDocuments && documents.length">
        <div class="card-header popup-head py-2">
          <h6 class="mb-0 fw-semibold small">Documents</h6>
        </div>
        <div class="card-body py-2 m-2">
          <ul class="list-unstyled">
            <li v-for="(doc, i) in documents" :key="doc.key || i" class="mb-2">
              <a 
                @click="openDoc(doc?.name || doc.key || 'Document', doc?.file || doc?.url || '#')" 
                class="btn btn-link p-0">
                {{ doc?.name || doc.key }}
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <!-- No Data -->
    <div v-else class="text-center p-4 text-muted">
    </div>
  </div>`
};