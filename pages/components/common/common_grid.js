const [{ Sidebar } ] = await $importComponent([
  '/pages/components/common/common_sidebar.js',
]);

export const Grid = {
  name: 'Grid',
  components: { Sidebar },
  props: {
    store: { type: Object, required: true },
    attachComponents: { type: Object, default: () => ({}) },
  },
  data: () => ({
    userStore: null,
    perPage: 10,
    perPageOptions: [10, 25, 50, 100],
    fallbackIcon: 'bi-info',
    attachModalVisible: {},
    attachData: {},
    loadingKey: null,
    revealedCells: new Set(),
    myConfig: 'gridConfig.searchConfig'
  }),
  async created() {
    const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);
    this.userStore = storeModule.useUserStore(); 
    this.perPageOptions = this.config?.rowsPerPageOptions || this.perPageOptions;
    // Initialize perPage from store if available
    if (this.pagination?.perPage) {
      this.perPage = this.pagination.perPage;
    }
  },
  watch: {
    perPage() { this.fetchGridData(1); },
    // Sync local perPage when store pagination changes (e.g., bucket switching)
    'pagination.perPage'(newVal) {
      if (newVal && newVal !== this.perPage) {
        this.perPage = newVal;
      }
    }
  },
  computed: {
    isProcessing: vm => vm.store?.isProcessing || false,
    endpoint: vm => vm.store?.endpoint || '',
    adminPrefix: vm => vm.store?.adminPrefix || '',
    getErrors: vm => field => vm.store?.errors?.gridConfig?.[field] || [],
    menuConfig: vm => vm.store?.getMenuConfig || {},
    sidebarConfig: vm => vm.store?.getSidebarConfig || {},
    config: vm => vm.store?.getGridConfig || {},
    list: vm => vm.config?.list || [],
    pagination: vm => vm.config?.pagination || {},
    columns: vm => vm.config?.columns || [],
    filtersConfig: vm => vm.config?.searchConfig || {},
    filterFields() {
      const config = this.filtersConfig;
      if (!config) return [];
      let allFields = [];
      if (Array.isArray(config.fields)) allFields = config.fields;
      else if (Array.isArray(config.sections)) {
        config.sections.forEach(s => Array.isArray(s.fields) && allFields.push(...s.fields));
      }
      return allFields;
    },
    displayCurrentPage: vm => vm.pagination?.pages ? vm.pagination.current_page : 0,
    displayStartCount: vm => vm.pagination?.total ? vm.pagination.start_count : 0,
    displayEndCount: vm => vm.pagination?.total ? vm.pagination.end_count : 0,
    hasActiveFilters() {
      return this.filterFields.some(f => f.value);
    },
    getFieldOptions: vm => (field) => {
      // Check store masterLists first
      if (typeof vm.store?.getOptionsForField === 'function') {
        const options = vm.store.getOptionsForField(field.fieldKey || field.key, field);
        if (options?.length) return options;
      }
      return field.fieldOptionIds || field.fieldOptions || [];
    }
  },
  
  methods: {
    fetchGridData(page = 1) {
      this.store?.getList?.({ current_page: page, perPage: this.perPage });
    },
    resetFilters() {
      this.filterFields.forEach(f => f.value = f.defaultInputValue || '');
      this.store?.resetGridFilters?.();
      this.fetchGridData(1);
    },
    getFieldValue(field) { return field?.value ?? field?.val ?? ''; },
    setFieldValue(field, value) { if (field) field.value = value; },
    formatCellValue(row, col) {
      if (!col) return '';

      // Handle 'attach' type when no key is defined or empty
      if (col.type === 'attach' && (!col.key || col.key.length === 0)) {
        return col.label || '';
      }

      let val = Array.isArray(col.key)
        ? col.key.map(k => row?.[k]).filter(Boolean).join(col.type === 'concat' ? ', ' : ' ')
        : row?.[col.key];

      if (!val || (col.type === 'concat' && val === '0')) return '';

      if (col.type === 'date') return $formatTime(val);
      
      // Handle currency formatting
      if (col.type === 'numeric_format' && typeof window.$formattedCurrency === 'function') {
        const formatter = window.$formattedCurrency();
        return formatter(val);
      }

      return val;
    },
    getColumnStyle(col) {
      // Check if class property exists
      if (!col.class) return '';
      
      // Style mapping - converts semantic style types to Bootstrap CSS classes
      const styleMap = {
        // Font weights
        'bold': 'fw-bold',
        'semibold': 'fw-semibold',
        'normal': 'fw-normal',
        'light': 'fw-light',
        'lighter': 'fw-lighter',
        
        // Font sizes
        'small': 'small',
        'large': 'fs-5',
        'xlarge': 'fs-4',
        
        // Text alignment
        'center': 'text-center',
        'left': 'text-start',
        'right': 'text-end',
        
      };
      
      // Support multiple styles separated by comma or space
      const styles = col.class.includes(',') 
        ? col.class.split(',').map(s => s.trim())
        : col.class.split(' ').map(s => s.trim());
      
      // Map each style, if no mapping exists return original value (for direct Bootstrap classes)
      return styles
        .map(style => styleMap[style] || style)
        .join(' ');
    },
    getDateAttributes(field, calendarType) {
      return $setDateAttributes(field, calendarType);     
   },
    getFileAccept(patterns) {
      return $setFileAccept(patterns);     
    },

    toggleCheckboxValue(field, value) {
      let current = (field.value || '').split(',').filter(v => v); // ensure array without empties
      if (current.includes(value)) {
        current = current.filter(v => v !== value);
      } else {
        current.push(value);
      }
      field.value = current.join(','); // store as comma-separated string
      this.store?.handleFieldEvent?.('onchange', this.myConfig, field.fieldKey, field, { target: { value: field.value } });
    },
    async runAction(meta, row = {}) {
      if (!meta) return;
      if (typeof meta.onClick === 'function') return meta.onClick(row);

      this.loadingKey = meta.key || null;
      try {
        if (meta.type === 'route' && meta.action) {
          const route = meta.action.replace(/:([a-zA-Z0-9_]+)/g, (_, k) => row[k] || '');
          return $routeTo(`${this.adminPrefix}/${this.endpoint}/${route}`);
        }

        if (meta.type === 'get' && meta.action) {
            if (typeof this.store[meta.action] === 'function') {
              return await this.store[meta.action](row);
            } else {
              console.warn(`Store action "${meta.action}" not found`);
            }
        }

        if (meta.href) return $routeTo(meta.href);
        console.warn('No valid action in meta:', meta);
      } finally {
        this.loadingKey = null;
      }
    },

    getLink(meta, row = {}) {
      if (!meta) return '#';
      if (meta.type === 'route' && meta.action) return `${this.adminPrefix}/${this.endpoint}/${meta.action.replace(/:([a-zA-Z0-9_]+)/g, (_, k) => row[k] || '')}`;
      return meta.href || '#';
    },

    changePage(p) {
      if (p >= 1 && p <= (this.pagination?.pages || 1) && p !== this.pagination?.current_page) this.fetchGridData(p);
    },

    openGallery(row) {
      const imgs = row?.images
        ? Array.isArray(row.images)
          ? row.images.map(img => img.url)
          : Object.values(row.images).map(img => img.url)
        : [];
      if (imgs.length) this.$refs.imageViewer?.openImages('IMAGES', imgs);
    },

    getFieldEvents(configKey, fieldKey, field) {
      if (!field) return {};
      const events = {};
      const type = field.inputType || field.type;

      if (['text','alphanumeric','numeric','number','calender','date', 'phone', 'email', 'pin_code_search'].includes(type)) {
        events.input = e => {
          let val = this.sanitizeInput(field, e.target.value);
          field.value = val;
          this.store?.handleFieldEvent?.("onchange", configKey, fieldKey, field, e);
        };
      } else if (['dropdownIds','dynamic_dropdown','select'].includes(type)) {
        events.change = e => {
          field.value = e.target.value;
          this.store?.handleFieldEvent?.("onchange", configKey, fieldKey, field, e);
        };
      }
      return events;
    },

    openAttach(key, row = {}) {
      // console.log(key);
      // console.log(JSON.stringify(row));
      if (!key) return;
      this.attachModalVisible[key] = true;
      this.attachData[key] = row;
      console.log(this.attachModalVisible[key]);
      console.log(this.attachData[key]);
    },

    onAttachSelect(key, selected) {
      if (!key) return;
      console.log('Selected from', key, selected);
      this.attachModalVisible[key] = false;
    },

    sanitizeInput(field, value) {
        if (!value) return '';
        const type = field.inputType || field.type;

        if (['numeric','number', 'phone', 'pin_code_search'].includes(type)){
            return field.allowDecimal ? value.replace(/[^0-9.]/g,'') : value.replace(/[^0-9]/g,'');
        }

        if (type === 'alphanumeric') {
            // Allow letters, numbers, spaces, and common email/special chars
            value = value.replace(/[^a-zA-Z0-9@!*\-_\+.,\s]/g, '');
        }

        if (['text','alphanumeric'].includes(type) && field.isCaps) {
            value = value.toUpperCase();
        }

        return value;
    },
    
    maskValue(value) {
      if (!value) return '';

      const str = String(value);
      const atIndex = str.indexOf('@');

      // Case 1: Email-like value
      if (atIndex !== -1) {
        const first = str.slice(0, 2);
        const last = str.slice(-2);
        const masked = '*'.repeat(Math.max(1, str.length - 4));
        return first + masked + last;
      }

      // Case 2: Generic string (no '@')
      if (str.length <= 4) return '*'.repeat(str.length);

      const first = str.slice(0, 2);
      const last = str.slice(-2);
      const masked = '*'.repeat(str.length - 4);

      return first + masked + last;
    },
    toggleReveal(rowId, key) {
      const cellKey = `${rowId}-${key}`;
      if (this.revealedCells.has(cellKey)) {
        this.revealedCells.delete(cellKey);
      } else {
        this.revealedCells.add(cellKey);
      }
    },

  },


  template: /*html*/ `
<div class="container-fluid p-1 bg-light min-vh-100" id="grid-container">
  <ImageViewer ref="imageViewer" />
  <div class="card shadow border-0 rounded-0 overflow-hidden">
    <!-- Header -->
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center px-1 py-1 border-bottom">
      <h6 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1">
        <i class="bi bi-table text-secondary"></i> {{ menuConfig?.title }}{{ menuConfig?.subtitle ? ' - ' + menuConfig?.subtitle : '' }}
      </h6>
      <div class="d-flex gap-2">
        <template v-for="item in config.header" :key="item.label">
          <button v-if="item?.validation?.show"
            class="btn btn-sm d-flex align-items-center gap-1 shadow-sm"
            :class="item.class"
            :disabled="item?.validation?.disabled || (item.btn_type === 'export' && loadingKey === item.key)"
            @click="runAction(item.conditional?.onclick?.meta)">
            <span v-if="loadingKey === item.conditional?.onclick?.meta?.key" class="spinner-border spinner-border-sm"></span>
            <i v-else class="bi" :class="item.icon ? 'bi-' + item.icon : fallbackIcon"></i>
            <span class="d-none d-sm-inline">{{ item.label }}</span>
          </button>
        </template>
      </div>
    </div>

    <div class="d-flex">
      <!-- Sidebar -->
      <Sidebar v-if="sidebarConfig.showSidebar" :store="store" />
      <div :class="['flex-grow-1 p-0', { 'w-100': !sidebarConfig.showSidebar }]">
        <div class="card-body p-1" style="max-height:1000px;overflow-y:auto;-ms-overflow-style:none;scrollbar-width:none;">

        <!-- Adaptive Filters (works with both flat and sectioned configs) -->
        <div v-if="filterFields.length" class="p-2 pb-3 mb-1 bg-light rounded-1 border shadow-sm search-filter ">
          <div class="row g-1 align-items-end">
            <div v-for="(field, index) in filterFields" :key="field.fieldKey || field.key || index" 
                 class="col-lg-2 col-md-3 col-sm-6"
                 v-show="!field.isHidden">
              <label class="form-label form-text fw-semibold text-muted mb-0">
                {{ field.fieldLabel || field.label || field.fieldKey }}
              </label>
              <div class="input-group input-group-sm">
               <!--  <span v-if="['calender','date','dropdownIds','dynamic_dropdown','select'].includes(field.inputType || field.type)" 
                      class="input-group-text bg-white border-end-0 rounded-start-pill py-0 px-1">
                  <i :class="(field.inputType || field.type) === 'calender' || (field.inputType || field.type) === 'date' ? 'bi bi-calendar-date text-muted' : 'bi bi-filter text-muted'"></i>
                </span> -->
                
                <!-- Text/Alphanumeric/Numeric inputs -->


                 <input v-if="['alphanumeric','numeric','number','text','email','phone','pin_code_search','numeric_format'].includes(field.inputType || field.type)"
                              type="text"
                              :id="field.fieldKey"
                              class="form-control form-control-sm shadow-none py-0 px-2"
                              :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                              :placeholder="field.fieldHolder || field.placeholder || ''"
                              v-model="field.value"
                              :maxlength="field.maxLength"
                              :readonly="field.isReadOnly || field.readonly"
                              :required="field.isRequired || field.required"
                              v-on="getFieldEvents(myConfig, field.fieldKey || field.key, field)"
                        />
                       
                <!-- Date inputs -->
                 <input
                        v-else-if="['calender','date','calender_time'].includes(field.inputType || field.type)"
                        :type="['calender_time'].includes(field.inputType || field.type) ? 'datetime-local' : 'date'"
                        :id="field.fieldKey"
                        class="form-control form-control-sm shadow-none py-1 px-2"
                        :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                        v-model="field.value"
                        :readonly="field.isReadOnly || field.readonly"
                        :required="field.isRequired || field.required"
                        v-on="getFieldEvents(myConfig, field.fieldKey || field.key, field)"
                        @click="(e) => { e.target.showPicker && e.target.showPicker(); }"
                        v-bind="getDateAttributes(field, field?.calenderType)"
                    />


                  <SelectSearch
                        v-else-if="['dropdownIds','dynamic_dropdown','select','searchbleDropdownIds'].includes(field.inputType || field.type)"
                        v-model="field.value"
                        class="w-100"
                        :id="field.fieldKey"
                        :options="getFieldOptions(field)"
                        :placeholder="field.fieldHolder || field.placeholder || field.fieldLabel"
                        :searchable="field?.isSearch"
                        :showGrouping="field?.isGroup"
                        option-label="label"
                        option-value="value"
                        :disabled-options="field.disabledOptions?.map(v => v.toString())"
                        @change="(val) => getFieldEvents(myConfig, field.fieldKey || field.key, field).change?.({ target: { value: val } })"
                      />

                        <div v-else-if="['checkbox_group'].includes(field.inputType || field.type)" class="d-flex flex-wrap gap-2">
                        <div v-for="opt in getFieldOptions(field)" :key="opt.value" class="form-check me-2">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            :id="field.fieldKey + '_' + opt.value"
                            :value="opt.value.toString()"
                            :checked="(field.value || '').split(',').includes(opt.value.toString())"
                            @change="toggleCheckboxValue(field, opt.value.toString())"
                          />
                          <label class="form-check-label small" :for="field.fieldKey + '_' + opt.value">
                            {{ opt.label }}
                          </label>
                        </div>
                      </div>

                  <div v-if="getErrors(field.fieldKey).length" class="invalid-feedback d-block" style="font-size: 0.7rem;">
                    <span v-for="(err, idx) in getErrors(field.fieldKey)" :key="idx">{{ err }}</span>
                  </div>

              </div>
            </div>
            
            <div class="col-auto d-flex align-items-end gap-1">
              <button class="btn btn-xs btn-dark px-2 py-0 shadow-sm" @click="fetchGridData(1)">
                <i class="bi bi-search me-1"></i>Search
              </button>
              <button v-if="hasActiveFilters" class="btn btn-xs btn-outline-secondary px-2 py-0 shadow-sm" @click="resetFilters">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination + Info -->
        <nav v-if="filterFields.length" class="d-flex flex-wrap justify-content-between align-items-center my-3 ">
          <div class="d-flex align-items-center gap-2">
            <label for="perPageSelect" class="text-muted">Rows:</label>
            <select id="perPageSelect" v-model="perPage" class="form-select form-select-sm border-0 shadow-sm" style="width:65px;">
              <option v-for="opt in perPageOptions" :value="opt" :key="opt">{{ opt }}</option>
            </select>
            <span class="text-muted">Showing <b>{{ displayStartCount }}</b>-<b>{{ displayEndCount }}</b> of <b>{{ pagination.total }}</b></span>
          </div>
          <ul class="pagination pagination-sm mb-0 gap-2">
            <li class="page-item" :class="{ disabled: pagination.current_page === 1 || pagination.total === 0 }">
              <a class="page-link border-0 shadow-sm" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                <i class="bi bi-chevron-left"></i>
              </a>
            </li>
            <li class="page-item disabled">
              <span class="page-link rounded-1 border-0 bg-white text-dark fw-semibold">
                {{ pagination.current_page }} / {{ pagination.pages }}
              </span>
            </li>
            <li class="page-item" :class="{ disabled: pagination.current_page >= pagination.pages || pagination.total === 0 }">
              <a class="page-link border-0 shadow-sm" href="#" @click.prevent="changePage(pagination.current_page + 1)">
                <i class="bi bi-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>

        <!-- Table -->
        <div class="table-responsive overflow-auto">
          <table class="table table-sm table-hover table-striped table-bordered align-middle mb-0 rounded-3 overflow-hidden">
            <thead class="bg-light text-dark bg-midgrey1">
              <tr><th v-for="group in columns" :key="group.title" class="fw-semibold text-nowrap px-2 py-1">{{ group.title }}</th></tr>
            </thead>
            <tbody>
              <tr v-if="isProcessing" v-for="n in perPage" :key="'loading-'+n">
                <td v-for="group in columns" :key="'loading-col-'+group.title">
                  <span class="skeleton w-100 d-block">&nbsp;</span>
                </td>
              </tr>
              <tr v-else-if="list.length===0">
                <td :colspan="columns.length" class="text-center text-muted py-4">
                  <i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>No Records Found
                </td>
              </tr>
              <tr v-else v-for="row in list" :key="row.id">
                <td v-for="group in columns" :key="group.title" class="px-2 py-1 small align-top">
                  <div v-for="col in group.data" :key="col.label||col.key" class="mb-1">
                   <template v-if="['text','concat','date','numeric_format'].includes(col.type)">
                      <span :class="getColumnStyle(col)">
                        <span v-if="col.label">{{ col.label ? col.label + ': ' : '' }}</span>

                        <!-- If masking enabled -->
                        <template v-if="col.isMasked === 'y'">
                          <span>
                            {{
                              revealedCells.has(row.id + '-' + (col.key?.[0] || col.key))
                                ? formatCellValue(row, col)
                                : maskValue(formatCellValue(row, col))
                            }}
                          </span>
                          <i
                            class="bi ms-1"
                            :class="revealedCells.has(row.id + '-' + (col.key?.[0] || col.key)) ? 'bi-eye-slash' : 'bi-eye'"
                            role="button"
                            style="cursor:pointer"
                            :title="revealedCells.has(row.id + '-' + (col.key?.[0] || col.key)) ? 'Click to hide' : 'Click to reveal'"
                            @click="toggleReveal(row.id, col.key?.[0] || col.key)"
                          ></i>
                        </template>

                        <!-- Normal display -->
                        <template v-else>
                          {{ formatCellValue(row, col) }}
                        </template>
                      </span>
                    </template>
                    <template v-else-if="col.type==='image'">
                      <div class="d-flex gap-1 flex-wrap">
                        <div v-if="row.images && Object.keys(row.images).length" class="position-relative m-auto">
                          <img :src="Object.values(row.images)[0].url" class="img-thumbnail rounded-1 shadow-sm"
                               style="object-fit:cover;cursor:pointer;"
                               @click="openGallery(row)" />
                          <span class="badge bg-dark text-white position-absolute top-0 start-100 translate-middle rounded-circle">
                            {{ Object.keys(row.images).length }}
                          </span>
                        </div>
                        <div v-else class="d-flex align-items-center justify-content-center bg-light border rounded img-ratio">
                          <i class="bi bi-car-front text-muted opacity-50" style="font-size:2rem;"></i>
                        </div>
                      </div>
                    </template>
                    <template v-else-if="col.type==='badge'">
                      <span class="badge bg-secondary badge-cus">{{ formatCellValue(row, col) }}</span>
                    </template>
                    <template v-else-if="col.type==='button'">
                      <button class="btn btn-sm  align-items-center" :class="col.class" @click="runAction(col.meta,row)">
                        <i class="bi" :class="col.icon ? 'bi-'+col.icon : fallbackIcon"></i><span>{{ col.label }}</span>
                      </button>
                    </template>
                    <template v-else-if="col.type==='link'">
                      <a class="btn btn-sm d-flex align-items-center justify-content-center gap-1 text-truncate" :class="col.class" :href="getLink(col.meta,row)" target="_blank" @click.prevent="runAction(col.meta,row)">
                        <i class="bi" :class="col.icon ? 'bi-'+col.icon : fallbackIcon"></i> <span> {{ col.label }}</span>
                      </a>
                    </template>
                    <template v-else-if="col.type === 'attach'">
                      <span
                        class="btn btn-sm btn-outline-secondary px-2 py-1"
                        role="button"
                        style="cursor: pointer;"
                        @click="openAttach(col.attachKey, row)"
                        :title="col.tooltip"
                        data-bs-toggle="tooltip"
                      >
                        <i class="bi fs-6" :class="[col.icon ? 'bi-' + col.icon : fallbackIcon, formatCellValue(row, col) ? 'me-1' : '']"></i>
                        {{ formatCellValue(row, col) }}
                      </span>
                    </template>
                    <template v-else><span class="text-muted">—</span></template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Attach components -->
        <template v-for="(Comp, key) in attachComponents" :key="key">
          <component
            :is="Comp"
            :visible="attachModalVisible[key]"
            :store="store"
            :attachKey="key"
            :row="attachData[key]"
            @close="attachModalVisible[key] = false"
            @select="onAttachSelect(key,$event)" />
        </template>

        </div>
      </div>
    </div>
  </div>
</div>
`
};