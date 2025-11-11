export const Overview = {
  name: 'Overview',
  props: { 
    store: { type: Object, required: true },
  },

  data: () => ({
    defaultImage: '/assets/images/default-image.jpg',
  }),

  async created() {
    this.store?.init?.();
  },

  methods: {
    get(obj, path) {
      return path?.split('.').reduce((o, p) => o?.[p], obj);
    },

    isEmpty(v) {
      return [null, undefined, '', '0000-00-00', '0000-00-00 00:00:00'].includes(v);
    },

    parseJson(v) {
      if (typeof v !== 'string' || !/^[{\[]/.test(v)) return null;
      try { return JSON.parse(v); } catch { return null; }
    },

    imageUrl(url) {
      if (!url) return this.defaultImage;
      const base = window.g?.$base_url || window.location.origin;
      return url.startsWith('/') ? base + url : url;
    },

    display(field) {
      const data = this.displayData || {};
      const val = data[field.key];

      // Multiple fields combined
      if (field.key?.includes(',')) {
        const parts = field.key.split(',').map(k => data[k]?.trim()).filter(Boolean);
        return parts.join(' ') || '-';
      }

      if (this.isEmpty(val)) return '-';

      // Date formatting
      if (field.type === 'date' && typeof window.$formatTime === 'function')
        return window.$formatTime(val) || '-';

      if (field.type === 'numeric_format' && typeof window.$formattedCurrency === 'function') {
        const formatter = window.$formattedCurrency();
        return formatter(val);
      }

      // JSON data
      const parsed = this.parseJson(val);
      if (parsed) return Array.isArray(parsed)
        ? `${parsed.length} items`
        : `${Object.keys(parsed).length} items`;

      return val;
    },

    openImageViewer(name, urls, index) {
      this.$refs.viewer?.openImages?.(name, urls, index);
    },

    openDocument(name, url) {
      globalThis.$docViewer?.openDoc?.(name, url);
    },

    onImageError(e) {
      e.target.src = this.defaultImage;
    },
  },

  computed: {

    currentSlug() {
      const slug = (typeof $routeGetParam === 'function') ? $routeGetParam('slug3') : null;
      return slug || null;
    },

    config() {
      const slug = this.currentSlug;
      const key = slug ? `${slug}Config` : 'overviewConfig';
      return this.store?.[key] || this.store?.overviewConfig || {};
    },

    displayData() {
      return this.get(this.store, this.config.meta?.dataPath || 'detail') || {};
    },

    groupedFields() {
      const fields = this.config.fields || {};
      return Object.values(fields).reduce((acc, f) => {
        if (!['view', 'date', 'numeric_format'].includes(f.type)) return acc;
        const cat = f.category || 'Others';
        (acc[cat] ||= []).push(f);
        return acc;
      }, {});
    },

    isLoaded() {
      const checkPath = this.config.meta?.loadedCheckPath || this.config.meta?.dataPath;
      const data = this.get(this.store, checkPath);
      return data && (data.id || Object.keys(data).length);
    },

    images() {
      const val = this.displayData?.images;
      return typeof val === 'string' ? this.parseJson(val) || {} : val || {};
    },

    documents() {
      const val = this.displayData?.documents;
      if (!val || typeof val !== 'object') return [];
      return Object.entries(val).map(([k, v]) => ({ key: k, ...v }));
    },

    meta() {
      return this.config.meta || {};
    },
  },

  template: /*html*/`
  <div class="overview p-2">
    <ImageViewer ref="viewer" />

    <template v-if="isLoaded">
      <!-- Fields -->
      <div class="card mb-3">
        <div class="card-header py-2 popup-head">
          <h6 class="mb-0 fw-semibold small">{{ meta.title || 'Overview' }}</h6>
        </div>
        <div class="card-body py-2">
          <div v-for="(fields, group) in groupedFields" :key="group" class="mb-3">
            <h6 class="border-bottom pb-1 mb-2 small fw-semibold">{{ group }}</h6>
            <div class="row g-3">
              <div v-for="f in fields" :key="f.key" class="col-md-3">
                <div class="small text-muted">{{ f.label }}</div>
                <div class="fw-semibold text-body">{{ display(f) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Images -->
      <div v-if="meta.showImages !== false && Object.keys(images).length" class="card mb-3">
        <div class="card-header py-2 popup-head">
          <h6 class="mb-0 fw-semibold small">Images</h6>
        </div>
        <div class="card-body py-2">
          <div class="row g-2">
            <div v-for="(img, key, i) in images" :key="key" class="col-6 col-sm-4 col-md-3 col-lg-2 text-center">
              <button 
                class="btn p-0 border-0 bg-transparent w-100 btn-imgupload"
                @click="openImageViewer('Images', Object.values(images).map(i => imageUrl(i.imgPath || i.url)), i)">
                <img 
                  :src="imageUrl(img?.imgPath || img?.url)" 
                  @error="onImageError" 
                  class="img-fluid img-thumbnail" />
                <div class="mt-1 text-muted">{{ img?.imgName || key }}</div>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents -->
      <div v-if="meta.showDocuments !== false && documents.length" class="card mb-3">
        <div class="card-header py-2 popup-head">
          <h6 class="mb-0 fw-semibold small">Documents</h6>
        </div>
        <div class="card-body py-2">
          <ul class="list-unstyled m-0">
            <li v-for="d in documents" :key="d.key" class="mb-2">
              <button class="btn btn-link p-0 btn-imgupload text-black" @click="openDocument(d.name || d.key, d.file || d.url)">
                {{ d.name || d.key }}
              </button>
            </li>
          </ul>
        </div>
      </div>
    </template>
    <div v-else class="text-center p-4 text-muted">No data available</div>
  </div>`
};
