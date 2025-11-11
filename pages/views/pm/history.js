export const History = {
  name: 'History',
  props: {
    title: { type: String, default: 'Status History' },
    store: { type: Object, required: true },
    row: { type: Object, default: () => ({}) },
    // Props for Grid attach mode
    visible: { type: Boolean, default: false },
    attachKey: { type: String, default: '' }
  },
  emits: ['close', 'select'],
  
  data() {
    return {
      listData: [],
      offcanvasInstance: null,
      isAttachMode: false,
      isLoading: false
    };
  },
  
  computed: {
    columns() {
      return this.store?.getHistoryConfig || [];
    },
    list() {
      // Priority: 1. Loaded data, 2. Row history array, 3. Store details history
      if (this.listData.length) return this.listData;
      if (this.row?.history && Array.isArray(this.row.history)) return this.row.history;
      return this.store?.getDetails?.history || [];
    },
    offcanvasTitle() {
      return this.row?.formatted_id
        ? `${this.title} (${this.row.formatted_id})`
        : this.title;
    }
  },

  mounted() {
    this.$nextTick(() => {
      const el = document.getElementById('HistoryOffcanvas');
      if (el && typeof bootstrap !== 'undefined') {
        this.offcanvasInstance = new bootstrap.Offcanvas(el, { backdrop: true });
        
        // Listen for hide event to reset and emit close
        el.addEventListener('hidden.bs.offcanvas', () => {
          this.resetData();
          if (this.isAttachMode) {
            this.$emit('close');
          }
        });
      }
    });
  },

  beforeUnmount() {
    if (this.offcanvasInstance) {
      this.offcanvasInstance.dispose();
    }
  },

  watch: {
    // Watch visible prop for Grid attach mode
    visible(newVal) {
      this.isAttachMode = true;
      if (newVal) {
        this.loadHistory();
        this.$nextTick(() => {
          if (this.offcanvasInstance) {
            this.offcanvasInstance.show();
          }
        });
      } else {
        this.close();
      }
    }
  },

  methods: {
    // Reset data when closing
    resetData() {
      this.listData = [];
      this.isLoading = false;
    },

    async loadHistory() {
      // If row has ID, call store method to fetch history
      if (this.row?.id && typeof this.store.getHistory === 'function') {
        this.isLoading = true;
        try {
          const result = await this.store.getHistory(this.row.id);
          if (Array.isArray(result)) {
            this.listData = result;
          } else {
            this.listData = [];
          }
        } catch (err) {
          console.error('Error fetching history:', err);
          this.listData = [];
        } finally {
          this.isLoading = false;
        }
      } else {
        // If no ID, use row.history array or store details
        this.listData = [];
        this.isLoading = false;
      }
    },

    formatCellValue(row, col) {
      let val = Array.isArray(col.key)
        ? col.key.map(k => row[k]).filter(Boolean).join(col.type === 'concat' ? ', ' : ' ')
        : row[col.key];

      if (!val || (col.type === 'concat' && val === '0')) return '';
      if (col.type === 'date') return $formatTime(val);
      
      // Handle currency formatting
      if (col.type === 'numeric_format' && typeof window.$formattedCurrency === 'function') {
        const formatter = window.$formattedCurrency();
        return formatter(val);
      }
      
      return val;
    },

    // Public method for Detail page
    async open(rowData) {
      this.isAttachMode = false;
      this.resetData(); // Reset before opening
      
      if (rowData) {
        this.row = rowData;
      }
      
      await this.loadHistory();
      
      this.$nextTick(() => {
        if (this.offcanvasInstance) {
          this.offcanvasInstance.show();
        } else {
          const el = document.getElementById('HistoryOffcanvas');
          if (el && typeof bootstrap !== 'undefined') {
            this.offcanvasInstance = new bootstrap.Offcanvas(el, { backdrop: true });
            this.offcanvasInstance.show();
          }
        }
      });
    },

    close() {
      if (this.offcanvasInstance) {
        this.offcanvasInstance.hide();
      }
      // resetData will be called by the 'hidden.bs.offcanvas' event listener
    },

    closeCanvas() {
      this.close();
    }
  },

  template: /*html*/`
    <div 
      class="offcanvas offcanvas-end custom-offcanvas-lg"
      tabindex="-1"
      id="HistoryOffcanvas"
      aria-labelledby="HistoryOffcanvasLabel"
      style="width: 1000px; max-width: 90vw;"
    >
      <div class="offcanvas-header border-bottom">
        <h6 class="offcanvas-title fw-bold text-dark" id="HistoryOffcanvasLabel">
          {{ offcanvasTitle }}
        </h6>
        <button type="button" class="btn-close" @click="closeCanvas" aria-label="Close"></button>
      </div>

      <div class="offcanvas-body p-2">
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center py-5">
          <div class="spinner-border text-secondary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>

        <!-- Data Table -->
        <div v-else-if="list.length">
          <div class="table-responsive overflow-auto" style="max-height: calc(100vh - 130px);">
            <table class="table table-sm table-hover table-striped table-bordered align-middle mb-0 rounded-3 overflow-hidden">
              <thead class="bg-light text-dark small bg-midgrey1">
                <tr>
                  <th v-for="group in columns" :key="group.title" class="fw-semibold text-nowrap px-2 py-1">
                    {{ group.title }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in list" :key="row.id">
                  <td v-for="group in columns" :key="group.title" class="px-2 py-1 small align-top">
                    <div v-for="col in group.data" :key="col.label || col.key" class="mb-1">
                      <template v-if="['text','concat','date','numeric_format'].includes(col.type)">
                        <span v-if="col.label">{{ col.label }}: </span> 
                        {{ formatCellValue(row, col) }}
                      </template>
                      <template v-else-if="col.type === 'badge'">
                        <span class="badge rounded-pill shadow-sm bg-secondary">
                          {{ formatCellValue(row, col) }}
                        </span>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-5">
          <i class="bi bi-inbox fs-1 text-muted opacity-50"></i>
          <p class="text-muted small mt-2 mb-0">No records found.</p>
        </div>
      </div>
    </div>
  `
};