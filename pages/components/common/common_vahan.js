export const CommonVahan = {
  name: 'CommonVahan',
  components: {},
  props: { store: { type: Object, required: true } },
  data() {
    return {
      defaultValue: 'Not Available',
      loadingTimeout: null, 
    }
  },

  async created() {
    if (this.registrationNumber && this.isCurrentlyOnVahanTab) {
      await this.loadVahanData();
    }
  },
  
  async activated() {
    if (this.registrationNumber && this.isCurrentlyOnVahanTab) {
      await this.loadVahanData();
    }
  },
  
  watch: {
    '$route.params.slug3': {
      immediate: true, // Set to true to handle initial route
      handler: async function(newTab, oldTab) {
        if (newTab === 'vahan' && this.registrationNumber) {
          await this.loadVahanData();
        }
      }
    },
    
    // Watch for when registration number becomes available (detail loads after component mount)
    registrationNumber: {
      immediate: false,
      handler: async function(newRegNum, oldRegNum) {
        // If we're on vahan tab and registration number just became available, call API
        if (newRegNum && this.isCurrentlyOnVahanTab && newRegNum !== oldRegNum) {
          await this.loadVahanData();
        }
      }
    },
    
    // Watch for when detail becomes available (handles reload scenario)
    'store.detail': {
      immediate: false,
      handler: async function(newDetail, oldDetail) {
        // If detail just loaded and we're on vahan tab, call API
        if (newDetail && newDetail.reg_num && this.isCurrentlyOnVahanTab && !oldDetail) {
          await this.loadVahanData();
        }
      }
    }
  },

  // Clean up timeout when component is destroyed
  beforeUnmount() {
    if (this.loadingTimeout) {
      clearTimeout(this.loadingTimeout);
    }
  },

  computed: {
    vahanConfig() {
      return this.store?.getVahanConfig || {};
    },
    
    vahanData() {
      return this.store?.vahanData || {};
    },
    
    vahanLoading() {
      return this.store?.vahanLoading || false;
    },
    
    vahanError() {
      return this.store?.vahanError || null;
    },

    registrationNumber() {
      // Try multiple possible field names for registration number
      const detail = this.store.detail || {};
      
      // Try different field names that might contain the registration number
      const regNum = detail.reg_num || detail.registrationNumber || detail.registration_no || detail.regNum || '';
      
      return regNum;
    },
    
    groupedFields() {
      return Object.values(this.vahanConfig).reduce((acc, field) => {
        if (!field || typeof field !== 'object') return acc;
        if (field.type === 'button') return acc;
        const cat = field.category || 'Others';
        (acc[cat] ||= []).push(field);
        return acc;
      }, {});
    },
    
    hasFields() {
      return Object.values(this.groupedFields).some(f => f.length);
    },
    
    hasVahanData() {
      return this.vahanData && Object.keys(this.vahanData).length > 0;
    },
    
    isLoaded() {
      return !!this.store.detail?.id;
    },
    
    isCurrentlyOnVahanTab() {
      return this.$route?.params?.slug3 === 'vahan';
    }
  },

  methods: {
    // ---- Auto-loading Vahan data ----
    async loadVahanData() {
      const regNum = this.registrationNumber;
      
      // Debounce API calls to prevent multiple simultaneous calls
      if (this.loadingTimeout) {
        clearTimeout(this.loadingTimeout);
      }
      
      this.loadingTimeout = setTimeout(async () => {
        if (!regNum) return;
        
        // Prevent duplicate calls if already loading
        if (this.vahanLoading) return;
        
        try {
          // Both PM and My Stock should have fetchVahanDetailsForTab method
          if (typeof this.store.fetchVahanDetailsForTab === 'function') {
            await this.store.fetchVahanDetailsForTab();
          } else {
            // Fallback if the method doesn't exist
            if (typeof this.store.getVahanDetails === 'function') {
              await this.store.getVahanDetails('vahanConfig', { value: regNum });
            }
          }
        } catch (error) {
          // Silent error handling
        }
      }, 100); // 100ms debounce delay
    },

    // ---- Utilities ----
    isEmpty(value) {
      return value === null || value === undefined || value === '' || value === '0000-00-00 00:00:00' || value === '0000-00-00';
    },

    looksLikeDate(value) {
      if (/^\d+$/.test(value)) return false; // avoid pure numbers
      const date = new Date(value);
      return !isNaN(date.getTime());
    },

    // ---- Display helpers ----
    getDisplayValue(field) {
      const data = this.vahanData || {};
      
      const value = data[field.key];
      if (this.isEmpty(value)) return this.defaultValue;

      // Check if field has mapping and use it to convert value
      if (field.mapping && typeof field.mapping === 'object') {
        const mappedValue = field.mapping[value];
        if (mappedValue !== undefined && mappedValue !== null) {
          return mappedValue;
        }
      }

      // Handle date formatting
      if (field.format === 'date' || this.looksLikeDate(value)) {
        return this.formatDate(value);
      }

      // Handle suffix (like CC, Kg)
      if (field.suffix && value) {
        return value + field.suffix;
      }

      return value;
    },

    formatDate(dateStr) {
      if (!dateStr || dateStr === 'NA' || dateStr === 'Not Available' || dateStr === null || dateStr === '') {
        return 'Not Available';
      }
      
      try {
        // Handle various date formats
        let date;
        
        if (dateStr.includes('/')) {
          // Handle DD/MM/YYYY format
          const [day, month, year] = dateStr.split('/');
          date = new Date(year, month - 1, day);
        } else if (dateStr.includes('-')) {
          // Handle YYYY-MM-DD format
          date = new Date(dateStr);
        } else {
          date = new Date(dateStr);
        }
        
        if (isNaN(date.getTime())) {
          return dateStr; // Return original if parsing fails
        }
        
        return date.toLocaleDateString('en-GB', {
          day: '2-digit',
          month: '2-digit', 
          year: 'numeric'
        });
      } catch (e) {
        return dateStr;
      }
    },
    
    getCategoryIcon(category) {
      const iconMap = {
        'Vehicle Information': 'bi bi-car-front',
        'Technical Specifications': 'bi bi-gear', 
        'Owner Information': 'bi bi-person',
        'Insurance Information': 'bi bi-shield-check',
        'Permits and Compliance': 'bi bi-clipboard-check'
      };
      return iconMap[category] || 'bi bi-info-circle';
    }
  },

  template: /*html*/`
    <div class="container-fluid p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
          <i class="bi bi-car-front text-primary"></i>
          Vahan Details
          <small class="text-muted" v-if="registrationNumber">({{ registrationNumber }})</small>
        </h5>
      </div>

      <!-- Loader -->
      <div v-if="!isLoaded" class="p-3">
        <div class="card mb-3">
          <div class="card-header bg-light py-2">
            <span class="skeleton w-25 d-block">&nbsp;</span>
          </div>
          <div class="card-body py-2">
            <div class="row g-3">
              <div v-for="n in 6" :key="'skeleton-field-' + n" class="col-md-4">
                <div class="skeleton w-50 mb-1">&nbsp;</div>
                <div class="skeleton w-75">&nbsp;</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-else-if="vahanLoading" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Loading...</span>
        </div>
        <h6 class="text-muted">Fetching Vahan details...</h6>
      </div>

      <!-- Error State -->
      <div v-else-if="vahanError" class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
          <strong>Unable to fetch Vahan details:</strong> {{ vahanError }}
        </div>
      </div>

      <!-- No Registration Number -->
      <div v-else-if="!registrationNumber" class="alert alert-info d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <div>No registration number available for this vehicle.</div>
      </div>

      <!-- No fields -->
      <div v-else-if="!hasFields" class="text-center p-4 text-muted">
        No Vahan configuration found.
      </div>

      <!-- Vahan Data Display -->
      <div v-else-if="hasVahanData" class="row g-4">
        <!-- Dynamic Categories -->
        <div 
          v-for="(fields, category) in groupedFields" 
          :key="category" 
          :class="category === 'Permits and Compliance' ? 'col-12' : 'col-lg-6'"
        >
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-secondary text-white d-flex align-items-center gap-2">
              <i :class="getCategoryIcon(category)"></i>
              <h6 class="mb-0">{{ category }}</h6>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div 
                  v-for="field in fields" 
                  :key="field.key" 
                  :class="category === 'Permits and Compliance' ? 'col-lg-4 col-md-6' : 'col-12'"
                >
                  <div 
                    v-if="getDisplayValue(field) && getDisplayValue(field) !== defaultValue" 
                    class="d-flex justify-content-between align-items-start border-bottom pb-2"
                  >
                    <small class="text-muted fw-medium">{{ field.label }}:</small>
                    <small 
                      class="text-end fw-semibold" 
                      :style="category === 'Permits and Compliance' ? '' : 'max-width: 60%;'"
                    >
                      {{ getDisplayValue(field) }}
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- No Data State (after failed fetch) -->
      <div v-else class="alert alert-secondary d-flex align-items-center" role="alert">
        <i class="bi bi-database-x me-2"></i>
        <div>
          No Vahan details available for this registration number.
        </div>
      </div>
    </div>
  `
};