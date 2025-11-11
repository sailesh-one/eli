const { defineStore } = Pinia;



export const useStoreSalesMaster = defineStore('sm', {
  // ---------------- STATE ----------------
  state: () => ({
    isProcessing: false,
    _initialized: false,
    endpoint: null,
    errors: { gridConfig: {}, detailAddConfig: {}, statusConfig: {} },
    componentsConfig: {},
    menuConfig: { title: 'Sales Master', subtilte: '' },
    sidebarConfig: { showSidebar: false, sidebarItems: [] },
    gridConfig: { searchConfig: {}, list: [], pagination: {}, routeSlugs: {} },
    detailMenuConfig: { default:{}, detail:{} },
    detail: {},
    detailAddConfig: {},
    overviewConfig: {},
    imagesConfig: {},
    evaluationConfig: {},
    historyConfig: {},
    statusConfig: {},
    vehicleConfig: {},
    exactMatchConfig: {},

    commonMasterLists: {
      modelsWithVariants: {},
      branchesWithExecutives: [],
    },
    masterLists: {},
    savedPagination: null,
    
    // Cache default configs
    _defaultConfigs: null,
    _currentMode: null,
  }),

    // ---------------- GETTERS ----------------
    getters: {
        routePath: (s) => (s.endpoint ? `/${s.endpoint}` : ''),
        getMenuConfig: (s) => s.menuConfig,
        getSidebarConfig: (s) => s.sidebarConfig,
        getGridConfig: (s) => s.gridConfig,
        getDetailMenuConfig: (s) => s.detailMenuConfig.detail || {},
        getDetails: (s) => s.detail || {},
        getDetailAddConfig: (s) => s.detailAddConfig?.fields || [],
        getOverviewConfig: (s) => s.overviewConfig || {},
        getImagesConfig: (s) => s.imagesConfig || {},
        getEvaluationConfig: (s) => s.evaluationConfig || {},
        getHistoryConfig: (s) => s.historyConfig || {},
        getVehicleConfig: (s) => s.vehicleConfig || {},
        getStatusConfig: (s) => s.statusConfig?.fields || {},
        getStatusOverviewConfig: (s) => s.statusConfig?.columns || [],
        getExecutives: (s) => s.masterLists.executive || {},
        getExecutivesByBranchId: (s) => (branchId) => {
          const branch = s.commonMasterLists.branchesWithExecutives?.find(
            (b) => String(b.branch_id) === String(branchId)
          );
          if (!branch || !Array.isArray(branch.executives)) return [];
          return branch.executives.map((e) => ({
            value: String(e.id),
            label: e.name,
          }));
        },
        getOptionsForField: (s) => (fieldName, field) => s.masterLists?.[fieldName]?.length ? s.masterLists[fieldName] : field?.options || [],
        getExactMatchConfig: (s) => s.exactMatchConfig || {},

    },
  // ---------------- ACTIONS ----------------
  actions: {
    // ---------------- INIT & CONFIG ----------------
    async init() {
      if (this._initialized) return;
      this._initialized = true;
      this.endpoint = this.endpoint || $routeGetMeta?.('path') || 'sales-master';
      await this.fetchConfig();
      await this.loadInitialMasterData();
      this._watchRoute();
    },

    async fetchConfig() {
      this.isProcessing = true;
      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getconfig'
        });
        const cfg = res?.body?.data?.config || {};
        
        Object.assign(this, {
          sidebarConfig: cfg.sidebar || this.sidebarConfig,
          gridConfig: cfg.grid || this.gridConfig,
          detailMenuConfig: {
            default: cfg.menu || this.detailMenuConfig.default,
          },
          overviewConfig: cfg.overview || this.overviewConfig,
          detailAddConfig: cfg.addConfig || this.detailAddConfig,
          historyConfig: cfg.history || this.historyConfig,
          vehicleConfig: cfg.exact_vehicles || this.vehicleConfig,
          statusConfig: cfg.statusConfig || this.statusConfig,
        });


        if (!this._defaultConfigs) {
          this._defaultConfigs = JSON.parse(JSON.stringify({
            gridConfig: this.gridConfig,
            detailAddConfig: this.detailAddConfig,
            overviewConfig: this.overviewConfig,
            historyConfig: this.historyConfig,
            statusConfig: this.statusConfig,
            vehicleConfig: this.vehicleConfig,
            detailMenuConfig: this.detailMenuConfig,
          }));
        }

      } catch (err) {
        console.error('Error fetching config:', err);
      } finally {
        this.isProcessing = false;
      }
    },

   // ------------------------------------------------------------
    // ROUTE HANDLING
    // ------------------------------------------------------------
    _watchRoute() {
      router.afterEach((to) => {
        if (to.path.includes(this.endpoint)) this.updateFromRoute(to);
      });

      const current = router.currentRoute.value;
      if (current.path.includes(this.endpoint)) this.updateFromRoute(current);
    },

    async updateFromRoute() {
      const [slug1 = '', slug2 = '', slug3 = ''] = ['slug1', 'slug2', 'slug3'].map(
        (k) => $routeGetParam(k)
      );
      this.gridConfig.routeSlugs = { status: slug1, sub_status: slug2 };
      if (slug1 === 'detail') {
        if (slug2 && !slug3) {
          if (/^[A-Za-z0-9+/-]+=*$/.test(slug2)) {
            await this._prepareConfig('detail', slug2, slug3);
          } else {
            return $routeTo(`/${this.endpoint}`);
          }
        } else {
          await this._prepareConfig('detail', slug2, slug3);
        }
      } else {
        await this._prepareConfig('grid');
      }
    },


   
   // ------------------------------------------------------------
    // CONFIG PREPARATION
    // ------------------------------------------------------------
    async _prepareConfig(type, slug2 = '', slug3 = '') {
      const isGrid = type === 'grid';
      const isDetail = type === 'detail';
      const modeChanged = this._currentMode !== type;

      const previousMode = this._currentMode;
      this._currentMode = type;

      // Only reset sidebar and restore configs when MODE changes (grid ↔ detail)
      if (modeChanged) {
        // Clear sidebar only on mode change
        this.sidebarConfig = { showSidebar: false, sidebarItems: [] };
        
        // Restore default configs when switching modes
        if (this._defaultConfigs) {
          if (isGrid) {
            this.gridConfig = JSON.parse(JSON.stringify(this._defaultConfigs.gridConfig));
          } else if (isDetail) {
            this.detailAddConfig = JSON.parse(JSON.stringify(this._defaultConfigs.detailAddConfig));
            this.overviewConfig = JSON.parse(JSON.stringify(this._defaultConfigs.overviewConfig));
            this.historyConfig = JSON.parse(JSON.stringify(this._defaultConfigs.historyConfig));
            this.statusConfig = JSON.parse(JSON.stringify(this._defaultConfigs.statusConfig));
            this.vehicleConfig = JSON.parse(JSON.stringify(this._defaultConfigs.vehicleConfig));
            this.detailMenuConfig = JSON.parse(JSON.stringify(this._defaultConfigs.detailMenuConfig));
          }
        }
     }

      const resetConfigs = () => {
        this._resetDetailAddConfig('detailAddConfig');
        this._resetDetailAddConfig('statusConfig');
        Object.keys(this.errors).forEach((key) => (this.errors[key] = {}));
      };

      // ---------------- GRID ----------------
      if (isGrid) {
        this.detail = {};
        resetConfigs();
        this._updateMenuConfig('grid');

        await this.getList();

        if (this._currentMode === 'grid') {
          this._updateMenuConfig('grid');
        }
      }

      // ---------------- DETAIL ----------------
      if (isDetail) {
        const needsFetch = !this.detail?.id || this.detail.id !== slug2;

        if (needsFetch) {
          this.detail = {};
          resetConfigs();
          this.detailMenuConfig.detail = {};
          await this.getDetail(slug2);
        }

        if (this._currentMode === 'detail') {
          this._prepareSidebar({ mode: 'detail', data: slug2 });
          this._updateMenuConfig('detail', slug3);
        }
      }
    },


     // ------------------------------------------------------------
    // MENU CONFIG UPDATES
    // ------------------------------------------------------------
    _updateMenuConfig(mode = 'grid', data = null) {
      if (mode === 'grid' && this._currentMode === 'detail') return;

      if (mode === 'grid') {
        const statusSlug = this.gridConfig.routeSlugs?.status || '';
        const subStatusSlug = this.gridConfig.routeSlugs?.sub_status || '';

        const getLabel = (slug) => {
          if (!slug || slug === 'all') return 'All';
          const parent = this.sidebarConfig?.sidebarItems?.find(
            (i) => i.key === slug || i.slug === slug
          );
          if (parent?.label) return parent.label;

          for (const i of this.sidebarConfig?.sidebarItems || []) {
            const child = i.sub?.find((c) => c.key === slug || c.slug === slug);
            if (child?.label) return child.label;
          }
          return '';
        };

        const statusLabel = getLabel(statusSlug);
        this.menuConfig.subtitle = getLabel(subStatusSlug) || statusLabel;
      }

      if (mode === 'detail') {
        const tab = this.detail?.id ? data || 'overview' : 'add';
        const displayId = this.detail?.formatted_id || '';
        this.menuConfig.subtitle = `${tab[0].toUpperCase() + tab.slice(1)}${
          displayId ? ` ${displayId}` : ''
        }`;
      }
    },


  // ------------------------------------------------------------
    // SIDEBAR CONFIG BUILDER
    // ------------------------------------------------------------
    _prepareSidebar({ mode = 'grid', data = null } = {}) {
      if (mode === 'grid' && this._currentMode === 'detail') return;
      if (mode === 'detail' && this._currentMode === 'grid') return;

      const sidebar = { showSidebar: false, sidebarItems: [] };

      // GRID SIDEBAR
      if (mode === 'grid') {
        if (!data || Object.keys(data).length === 0) return sidebar;

        const buildItems = (items) =>
          Object.entries(items).map(([key, item]) => {
            const hasSub = item.sub && Object.keys(item.sub).length > 0;
            const baseRoute = `/${this.endpoint}/${key}`;

            return {
              key,
              label: item.label || '-',
              count: item.count || 0,
              // Always set href for navigation, even if parent has sub-buckets
              href: key !== 'all' ? baseRoute : `/${this.endpoint}`,
              isActive: item.is_active === 'y',
              sub: hasSub
                ? Object.entries(item.sub).map(([subKey, subItem]) => ({
                    key: subKey,
                    label: subItem.label || '-',
                    count: subItem.count || 0,
                    href: `/${this.endpoint}/${subKey}`,
                    isActive: subItem.is_active === 'y',
                    sub: [],
                  }))
                : [],
            };
          });

        sidebar.showSidebar = true;
        sidebar.sidebarItems = buildItems(data);
      }

      // DETAIL SIDEBAR
      if (mode === 'detail') {
        const recordId = this.detail?.id || data;
        const baseRoute = recordId
          ? `/${this.endpoint}/detail/${recordId}`
          : null;

        let menu = recordId
          ? this.detailMenuConfig?.detail || {}
          : (this.detailMenuConfig.detail =
              this.detailMenuConfig?.default || {});

        const sections = Object.values(menu)
          .filter((sec) => !sec.isHidden)
          .map((sec) => ({
            key: sec.fieldKey,
            label: sec.fieldLabel,
            href: baseRoute ? `${baseRoute}/${sec.fieldKey}` : null,
            disabled: !sec.isEnabled,
          }));

        sidebar.showSidebar = true;
        sidebar.sidebarItems = sections;
      }

      this.sidebarConfig = sidebar;
    },



    
    // ---------------- MASTER DATA ----------------
    async loadInitialMasterData() {
      try {
        this.getYears(['mfg_year']);
        this.getMonths(['mfg_month']);

        // ---------------- MASTER CONFIG ----------------
        const masters = {
          getMakes: ['make', 'rs_make'],
          getColors: ['color']
        };

        // ---------------- COMMON MASTERS ----------------
        const commonMasters = {
          getExecutivesByBranch: ['branchesWithExecutives']
        };

        // Combine both for one API call
        const collections = [
          ...Object.keys(masters),
          ...Object.keys(commonMasters)
        ].join(",");

        const res = await $http("POST", `${g.$base_url_api}/master-data`, {
          action: "getCollections",
          collections
        });

        if (res?.body?.status && res.body.data) {
          // ---------- NORMAL MASTERS ----------
          for (const [apiKey, fieldNames] of Object.entries(masters)) {
            const list = res.body.data[apiKey]?.list || [];
            if (Array.isArray(list)) {
              const opts = list.map((x) => ({
                value: String(x.value ?? x.id),
                label: x.label ?? x.name ?? String(x.value ?? x.id),
                group: x.group ?? null,
              }));

              // Assign options to all mapped fields
              fieldNames.forEach((fieldName) => {
                this.updateFieldOptions(fieldName, opts);
              });
            }
          }

          // ---------- COMMON MASTERS ----------
          for (const [apiKey, fieldNames] of Object.entries(commonMasters)) {
            const list = res.body.data[apiKey]?.list || [];
            fieldNames.forEach((fieldName) => {
              this.commonMasterLists[fieldName] = list;
            });
          }
          // Process executives by branch (after storage)
          this.processBranchesWithExecutives('branchesWithExecutives', ['branch']);
        }
        
        // Fetch sources with type parameter (auto-detects module on backend)
        await this.fetchMaster('getSources', 'source', { type: this.endpoint });
      } catch (err) {
        // Error loading master data
      }
    },

    processBranchesWithExecutives(key, fieldNames = []) {
      const branchData = this.commonMasterLists[key];
      if (!Array.isArray(branchData)) return;

      // Prepare branch dropdown options
      const branchOpts = branchData.map((b) => ({
        value: String(b.branch_id),
        label: b.branch_name,
      }));

      fieldNames.forEach((fieldName) => {
        this.updateFieldOptions(fieldName, branchOpts);
      });
    },

    dynamic_executies(branchId) {
    // get executives for this branch
    const execs = this.getExecutivesByBranchId(branchId);

    // log for debug
    // console.table(execs);

    // update dropdown options for executive field
    this.updateFieldOptions("executive", execs);
    this.updateFieldOptions("sold_by", execs);
  },

    async fetchMaster(action, listKey, params = {}) {
      try {
        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action,
          ...params
        });
        if (res?.body?.status && res.body.data) {
          const list = res.body.data.list || [];
          if (Array.isArray(list)) {
            // Try to get placeholder from loaded config first
            let label = `Select ${listKey.charAt(0).toUpperCase() + listKey.slice(1)}`;
           // debugger;
            // Check detailAddConfig for the field's placeholder
            if (this.detailAddConfig?.fields) {
              const allFields = this.getAllFields(this.detailAddConfig);
              const field = allFields.find(f => f.fieldKey === listKey);
              if (field?.fieldHolder) {
                label = field.fieldHolder;
                // $log(`[fetchMaster] Using fieldHolder from config for ${listKey}:`, label);
              }
            }
            
            const opts = [{
              value: '',
              label
            }, ...list.map(item => ({
              value: String(item.value ?? item.id),
              label: item.label ?? item.name ?? String(item.value ?? item.id),
            }))];
            this.updateFieldOptions(listKey, opts);
          }
        }
      } catch (e) {
        console.error(`Error fetching ${listKey} master data:`, e);
      }
    },

    // ---------------- HELPERS ----------------
    getYears(startYear = 2000) {
      const current = new Date().getFullYear();
      const years = [{
        value: '',
        label: 'Select Year'
      }, ...Array.from({
        length: current - startYear + 1
      }, (_, i) => {
        const y = current - i;
        return {
          value: String(y),
          label: String(y)
        };
      })];
      this.updateFieldOptions('mfg_year', years);
    },

    getMonths() {
      const months = [{
        value: '',
        label: 'Select Month'
      }];
      for (let i = 0; i < 12; i++) {
        const date = new Date(0, i);
        months.push({
          value: String(i + 1).padStart(2, '0'),
          label: date.toLocaleString('en', {
            month: 'long'
          })
        });
      }
      this.updateFieldOptions('mfg_month', months);
    },


    // ---------------- GRID ----------------
    resetGrid() {
      Object.assign(this.gridConfig, {
        list: [],
        pagination: {}
      });
    },

    resetGridFilters() {
      Object.values(this.gridConfig.search || {}).forEach(f => (f.val = ''));
    },

    _resetDetailAddConfig(configKey, resetValues = {}) {
      const config = this[configKey];
      if (!config) return;

      const allFields = this.getAllFields(config);

      allFields.forEach(field => {
        // If a resetValues override exists, use that
        const value = resetValues[field.fieldKey] ?? '';

        this.updateFieldValue(configKey, field.fieldKey, value);
      });
    },

    getAllFields(config, currentValues = {}) {
      const result = [];
      if (!config) return result;

      const rootFields = Array.isArray(config) ? config : Array.isArray(config.fields) ? config.fields : (config.sections || []).flatMap(s => s.fields || []);

      const collect = (fields) => {
        fields.forEach(field => {
          // Add the field itself
          if (field.hasOwnProperty('value') && field.formType !== 'expandable_form' && !field.sections) {
            result.push(field);
          }

          // Recursively collect from nested sections if present
          if (Array.isArray(field.sections)) {
            field.sections.forEach(sec => {
              if (Array.isArray(sec.fields)) {
                collect(sec.fields);
              }
            });
          }

          if (field.conditionalFields) {
            const parentFieldValue = field.value !== undefined && field.value !== null && field.value !== ''
                                     ? field.value
                                     : currentValues[field.fieldKey];

            if (parentFieldValue !== undefined && parentFieldValue !== null) {
              const conditional = field.conditionalFields[parentFieldValue];
              if (Array.isArray(conditional)) {
                collect(conditional); // Recursively collect conditional fields
              }
            }
          }
        });
      };

      collect(rootFields);
      return result;
    },

    // --- Validate Inputs ---
  validateInputs(config) {
      const allFields = this.getAllFields(config);

      const errors = {};
      let valid = true;

      allFields.forEach(field => {
        const {
          fieldKey,
          fieldLabel,
          value,
          isRequired,
          isHidden,
          maxLength,
          inputType,
          validation
        } = field;

        // Skip validation for hidden fields
        if (isHidden === true || field.show === false) {
          return;
        }

        let val = typeof value === 'string' ? value.trim() : value;

        if (['date', 'datetime-local'].includes(inputType)) {
          if (val === '0000-00-00' || val === '0000-00-00 00:00:00') {
            val = '';
            field.value = '';
          }
        }

        // --- Required check ---
        if (isRequired) {
          // For numeric fields, also reject "0" as invalid (matching backend behavior)
          if (['numeric', 'number'].includes(inputType)) {
            if (val === null || val === undefined || val === '' || val === '0') {
              valid = false;
              errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
              return;
            }
          } 
          // For dropdown fields, also reject "0" as invalid (placeholder/empty value)
          else if (['dropdown', 'dropdownIds', 'dynamic_dropdown'].includes(inputType)) {
            if (val === null || val === undefined || val === '' || val === '0') {
              valid = false;
              errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
              return;
            }
          } 
          else {
            // For other fields, standard empty check
            if (val === null || val === undefined || val === '') {
              valid = false;
              errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
              return;
            }
          }
        }
        // Skip empty non-required
        if (!val && !isRequired) {
          return;
        }

        // --- Handle file inputs separately ---
        if (inputType === 'file' && val) {
          const files = Array.isArray(val) ? val : [val]; // support single/multiple
          for (const file of files) {
            // 1. Check file size (<= 5 MB)
            const maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxFileSize) {
              valid = false;
              errors[fieldKey] = `File size must not exceed 5 MB`;
              return;
            }

            // 2. Check allowed extensions if mimeType exists
            if (validation?.mimeType?.length) {
              const allowed = validation.mimeType.map(t => t.toLowerCase());
              const ext = file.name.split('.').pop().toLowerCase();

              let isValidExt = false;
              if (allowed.includes('images') && ['jpg','jpeg','png','gif'].includes(ext)) {
                isValidExt = true;
              }
              if (allowed.includes('pdf') && ext === 'pdf') {
                isValidExt = true;
              }

              if (!isValidExt) {
                valid = false;
                errors[fieldKey] = validation.errorMessageInvalid || `Invalid file format for ${fieldLabel || fieldKey}`;
                return;
              }
            }
          }
          return; // ✅ file validated, skip further checks
        }

        // --- Max length ---
        if (maxLength && typeof val === 'string' && val.length > maxLength) {
          valid = false;
          errors[fieldKey] = `Maximum length is ${maxLength} characters`;
          return;
        }

        // --- Regex validation ---
        if (validation?.validationPattern && typeof validation.validationPattern === 'string' && typeof val === 'string') {
          const regexPattern = validation.validationPattern.replace(/^\/|\/$/g, '');
          const regex = new RegExp(regexPattern);
          if (!regex.test(val)) {
            valid = false;
            errors[fieldKey] = validation.errorMessageInvalid || `Invalid ${fieldLabel || fieldKey}`;
            return;
          }
        }

        // --- Numeric check ---
        if (['numeric', 'number'].includes(inputType) && val !== '' && isNaN(Number(val))) {
          valid = false;
          errors[fieldKey] = validation?.errorMessageInvalid || `${fieldLabel || fieldKey} must be a number`;
        }
      });

      return {
        valid,
        errors
      };
    },

    getFormPayload(config) {
      const formData = new FormData();
      const fieldsToExtract = this.getAllFields(config);

      fieldsToExtract.forEach(f => {
        let val = f.value;

        if (f.inputType === "file") {
          if (f.multiple && Array.isArray(val)) {
            val.forEach((file, idx) => {
              if (file instanceof File) {
                // New file selected
                formData.append(`${f.fieldKey}[${idx}]`, file);
              } else if (typeof file === 'string' && file.trim() !== '') {
                // Existing file URL — preserve it
                formData.append(`${f.fieldKey}[${idx}]`, file);
              }
            });
          } else {
            if (val instanceof File) {
              formData.append(f.fieldKey, val);
            } else if (typeof val === 'string' && val.trim() !== '') {
              // Existing file URL
              formData.append(`${f.fieldKey}`, val);
            }
          }
          return;
        }

        // Handle non-file fields
        if (typeof val === 'string') val = val.trim();
        formData.append(f.fieldKey, val ?? '');
      });

      return formData;
    },

    updateFieldOptions(key, options) {
      this.masterLists[key] = Array.isArray(options) ? options : [];
    },

    mapDetailToConfig(configKey, detail) {
      // Pass the detail object to getAllFields to correctly identify conditional fields
      const config = configKey.split('.').reduce((obj, key) => obj && obj[key], this);
      
      // Safety check: ensure config exists and has structure
      if (!config || (!config.fields && !config.sections)) {
        console.warn(`Config not found or empty for key: ${configKey}`);
        return;
      }
      
      const allFields = this.getAllFields(config,detail);
      console.table(allFields); 
      allFields.forEach(field => {
        if (detail[field.fieldKey] !== undefined) {
          // Ensure field has validation object
          if (!field.validation) field.validation = {};
          // Assign to `value` (what your form uses)
          field.defaultInputValue = detail[field.fieldKey];
          field.value = detail[field.fieldKey];
          this.applyConditionalLogic(configKey, field.fieldKey, field);
        }
      });
    },

    updateFieldValue(configKey, fieldName, value) {
      const config = configKey.split('.').reduce((obj, key) => obj && obj[key], this);
      if (!config) return;
      const allFields = this.getAllFields(config);
      
      const targetField = allFields.find(f => f.fieldKey === fieldName);
      if (targetField) {
        targetField.value = value;
      }
    },



 applyConditionalLogic(configPath, fieldKey, triggerField) {
        const config = configPath.split(".").reduce((acc, key) => acc && acc[key], this);
        if (!config) return;

        if (!triggerField.conditionalApply) return;

        const allFields = this.getAllFields(config);

        Object.entries(triggerField.conditionalApply).forEach(([applyType, rules]) => {
            // Special handling for isOptionsDisabled
            if (applyType === 'isOptionsDisabled') {
                const rulesByField = {};
                rules.forEach(rule => {
                    if (!rulesByField[rule.fieldKey]) rulesByField[rule.fieldKey] = [];
                    rulesByField[rule.fieldKey].push(rule);
                });

                Object.entries(rulesByField).forEach(([targetFieldKey, fieldRules]) => {
                    const targetField = allFields.find(f => f.fieldKey === targetFieldKey);
                    if (!targetField) return;

                    targetField.disabledOptions = [];
                    const currentValue = String(triggerField.defaultInputValue ?? "");

                    fieldRules.forEach(rule => {
                        const conditionMet = (rule.equal && rule.equal.includes(currentValue)) ||
                                            (rule.not_equal && !rule.not_equal.includes(currentValue));

                        if (conditionMet && Array.isArray(rule.options)) {
                            const newDisabled = rule.options.map(opt => String(opt));
                            targetField.disabledOptions = Array.from(
                                new Set([...targetField.disabledOptions, ...newDisabled])
                            );
                        }

                    });
                });
                return;
            }

            // Handle other apply types normally
            rules.forEach(rule => {
                const targetField = allFields.find(f => f.fieldKey === rule.fieldKey);
                if (!targetField) return;

                const currentValue = String(triggerField.value ?? "");
                const conditionMet = (rule.equal && rule.equal.includes(currentValue)) ||
                                    (rule.not_equal && !rule.not_equal.includes(currentValue));

                switch (applyType) {
                    case 'isHidden':
                        targetField.isHidden = conditionMet;
                        break;

                    case 'isRequired':
                        targetField.isRequired = conditionMet;
                        break;

                    case 'isReadOnly':
                        targetField.isReadOnly = conditionMet;
                        break;

                    case 'issetValue':
                        if (conditionMet && rule.value !== undefined) {
                            this.updateFieldValue(configPath, rule.fieldKey, rule.value);
                        }
                        break;

                    case 'setFieldLabel':
                        if (conditionMet && rule.fieldLabel !== undefined) {
                            targetField.fieldLabel = rule.fieldLabel;
                        }
                        break;

                    case 'isOptionsShowGroup':
                      if (!targetField._originalVisibleGroups) {
                          targetField._originalVisibleGroups = targetField.visibleGroups ? [...targetField.visibleGroups] : [];
                      }

                      // Always start from empty
                      targetField.visibleGroups = [];

                      rules.forEach(rule => {
                          const conditionMet = (rule.equal && rule.equal.includes(currentValue)) ||
                                              (rule.not_equal && !rule.not_equal.includes(currentValue));

                          if (conditionMet && Array.isArray(rule.optionsGroup)) {
                              // Merge matching groups without duplicates
                              targetField.visibleGroups = Array.from(
                                  new Set([...targetField.visibleGroups, ...rule.optionsGroup])
                              );
                          }
                      });

                      // Optional: if no rules match, reset to original
                      if (targetField.visibleGroups.length === 0) {
                          targetField.visibleGroups = [...(targetField._originalVisibleGroups || [])];
                          targetField.value = targetField.defaultInputValue || "";
                      }
                      break;

                }
            });
        });
    },

async handleFieldEvent(eventType, configKey, fieldName, field, e) {
          const newVal = field?.value ?? "";

          const config = (configKey || "").split(".")[0] || null;
          if (config && this.errors?.[config]?.[fieldName]) { delete this.errors[config][fieldName]; }

          // Step 1: Apply current field's conditionalApply (modifies dependent fields)
          if (field.conditionalApply) {
              this.applyConditionalLogic(configKey, fieldName, field);
          }

          // Step 2: Handle inputChange functions (like dynamic_substatus)
          if (field.inputChange) {
            // clear dependent fields if any
            if (Array.isArray(field.clearFields)) {
              field.clearFields.forEach(cf => {
                this.updateFieldValue(configKey, cf, "");
              });
            }
            // handle multiple functions in inputChange array
            if (Array.isArray(field.inputChange)) {
              for (const fn of field.inputChange) {
                if (typeof this[fn] === "function") {
                  await this[fn](newVal, field, configKey, e);
                }
              }
            } else if (typeof field.inputChange === "string" && typeof this[field.inputChange] === "function") {
              // backward compatibility if inputChange is a string
              await this[field.inputChange](newVal, field, configKey, e);
            }
          }

          // Step 3: Handle fields that depend on this field via inputMethod + filterBy
          const configObj = configKey.split(".").reduce((acc, key) => acc && acc[key], this);
          if (configObj) {
            const allFields = this.getAllFields(configObj);
            const dependentFields = allFields.filter(f => f.filterBy === fieldName && f.inputMethod);

            for (const depField of dependentFields) {
              if (typeof this[depField.inputMethod] === "function") {
                await this[depField.inputMethod](newVal, depField, configKey);
              }
            }
          }

          // Step 4: After parent has applied all changes, process cascading conditionalApply
          if (field.conditionalApply) {
              this.processCascadingConditionals(configKey, field);
          }
        },

    processCascadingConditionals(configPath, triggerField) {
        const config = configPath.split(".").reduce((acc, key) => acc && acc[key], this);
        if (!config || !triggerField.conditionalApply) return;

        const allFields = this.getAllFields(config);
        const processedFields = new Set(); // Track processed fields to avoid infinite loops

        // Collect all fields that were modified by the trigger field's conditionalApply
        const affectedFieldKeys = new Set();
        Object.values(triggerField.conditionalApply).forEach(rules => {
            rules.forEach(rule => {
                if (rule.fieldKey) affectedFieldKeys.add(rule.fieldKey);
            });
        });

        // Recursive function to process a field's conditionalApply
        const processField = (fieldKey) => {
            if (processedFields.has(fieldKey)) return;
            processedFields.add(fieldKey);

            const field = allFields.find(f => f.fieldKey === fieldKey);
            
            // Only process if field exists and has conditionalApply
            if (!field || !field.conditionalApply) return;

            // Apply this field's conditionalApply based on its CURRENT value
            // (which may have been modified by the parent field)
            this.applyConditionalLogic(configPath, fieldKey, field);

            // Collect fields affected by THIS field's conditionalApply
            const nextAffectedFields = new Set();
            Object.values(field.conditionalApply).forEach(rules => {
                rules.forEach(rule => {
                    if (rule.fieldKey && !processedFields.has(rule.fieldKey)) {
                        nextAffectedFields.add(rule.fieldKey);
                    }
                });
            });

            // Recursively process the next level of affected fields
            nextAffectedFields.forEach(nextFieldKey => processField(nextFieldKey));
        };

        // Start processing from all directly affected fields
        affectedFieldKeys.forEach(fieldKey => processField(fieldKey));
    },

    async dynamic_models(make, field='', configKey='') {
      // Reset model & variant fields
      this.updateFieldOptions('model', [{
        value: '',
        label: 'Select Model'
      }]);
      this.updateFieldOptions('variant', [{
        value: '',
        label: 'Select Variant'
      }]);



      this.isProcessing = true;
      try {

        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action: 'getmodelsbyMake',
          make: make
        });

        if (res?.body?.status === 'ok' && res.body?.data?.list?.length) {

          // store models + variants in masterLists
          this.masterLists.modelsWithVariants = res.body.data.list;
          console.table(this.masterLists.modelsWithVariants);
          // update model dropdown
          const modelOptions = this.masterLists.modelsWithVariants.map(m => ({
            value: m.value,
            label: m.label
          }));
          this.updateFieldOptions('model', modelOptions);
          this.updateFieldValue(configKey, 'model', '');


        }
      } catch (err) {
        console.error('Error fetching models:', err);
      } finally {
        this.isProcessing = false;
      }
    },

    // dynamic_variants(modelValue) {
    //   this.updateFieldOptions('variant', [{
    //     value: '',
    //     label: 'Select Variant'
    //   }]);
    //   if (!modelValue) return;

    //   console.log('Selected model:', modelValue);
    //   console.log('All models with variants:', this.masterLists.modelsWithVariants);

    //   const selectedModel = this.masterLists.modelsWithVariants.find(
    //     (m) => m.value == modelValue // allow type coercion
    //   );

    //   console.log('Selected model:', selectedModel);

    //   if (selectedModel?.variants?.length) {
    //     this.updateFieldOptions('variant', selectedModel.variants);
    //   }
    // },



    async dynamic_subsources(source) {
      this.updateFieldOptions('source_sub', []);
      if (source) await this.fetchMaster('getSubSources', 'source_sub', {
        source
      });
    },



    async dynamic_substatus(status) {
      // Only fetch if sub_status field is not hidden
      this.updateFieldOptions('sub_status', []);
      
      if (status) {
        await this.fetchMaster('getSubStatus', 'sub_status', {
          type:'sm',
          status
        });
      }
    },

    // Fetch booking vehicles when status = Booked (5)
    async dynamic_booking_vehicles(statusValue, field, configKey) {
      const [slug1 = '', slug2 = '', slug3 = ''] = ['slug1', 'slug2', 'slug3'].map(k => $routeGetParam(k));
      const bookedField = this.getAllFields(this[configKey]).find(f => f.fieldKey === 'booked_vehicle');

      // If this field was locked from detail, do not clear or overwrite it.
      if (bookedField?.isFixedFromDetail) {
        // Keep the locked option/value in place regardless of status toggles
        console.log('booked_vehicle is fixed from detail; skipping dynamic load/clear');
        return;
      }

      if (statusValue != '3' && statusValue != '4') {
        // Clear booking dropdown if status is not Booked
        this.updateFieldValue(configKey, 'booked_vehicle', '');
        if (bookedField) {
          bookedField.defaultInputValue = [];
          bookedField.value = '';
          bookedField.fieldOptionIds = [];
        }
        return;
      }

      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getreadyforsalevehicleslist',
          id: slug2
        });

        if (res?.status === 200 && res?.body?.data?.list) {
          const list = res.body.data.list;
          const bookedField = this.getAllFields(this[configKey]).find(f => f.fieldKey === 'booked_vehicle');
          
          if (bookedField) {
            bookedField.defaultInputValue = list;
            bookedField.fieldOptionIds = list;
            console.log('Loaded', list.length, 'booking vehicles');
          }
        } else {
          $toast('danger', res?.body?.msg || 'Failed to load booking vehicles');
        }
      } catch (e) {
        console.error('Error fetching booking vehicles:', e);
        $toast('danger', 'Failed to load booking vehicles');
      }
    },

    async dynamic_testdrive_vehicles(subStatusValue, field, configKey = 'statusConfig') {
      // Populate the test_drive_vehicle used in the FOLLOW UP section
      try {
        const cfg = this[configKey];
        if (!cfg) return;

        const allFields = this.getAllFields(cfg);
        const statusField = allFields.find(f => f.fieldKey === 'status');
        const testDriveField = allFields.find(f => f.fieldKey === 'test_drive_vehicle' || f.fieldKey === 'final_test_drive_vehicle');

        if (!testDriveField) return;

        const statusVal = String(statusField?.value ?? statusField?.defaultInputValue ?? this.detail?.status ?? '');
        const subVal = String(subStatusValue ?? field?.value ?? this.detail?.sub_status ?? '');

        const shouldLoad = (statusVal === '2' && subVal === '9'); // Follow up + Test Drive Scheduled

        if (!shouldLoad) {
          testDriveField.isHidden = true;
          testDriveField.isRequired = false;
          if (!testDriveField.isFixedFromDetail) {
            testDriveField.value = '';
            testDriveField.defaultInputValue = [];
            testDriveField.fieldOptionIds = [];
          }
          return;
        }

        if (testDriveField?.isFixedFromDetail) {
          testDriveField.isHidden = false;
          return;
        }

        testDriveField.isHidden = false;
        testDriveField.isRequired = true;

        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getreadyforsalevehicleslist'
        });

        if (res?.status === 200 && Array.isArray(res?.body?.data?.list)) {
          const list = res.body.data.list;
          const detailTD = this.detail?.test_drive_vehicle_details ?? null;
          const merged = [...list];

          if (detailTD) {
            const idx = merged.findIndex(v => String(v.id ?? v.value) === String(detailTD.id ?? detailTD.value));
            if (idx === -1) merged.unshift(detailTD);
            else if (idx > 0) {
              const [m] = merged.splice(idx, 1);
              merged.unshift(m);
            }
          }

          testDriveField.defaultInputValue = merged;
          testDriveField.fieldOptionIds = merged;

          if (detailTD) {
            testDriveField.value = String(detailTD.id ?? detailTD.value);
            testDriveField.defaultValue = testDriveField.value;
            testDriveField.isFixedFromDetail = true;
            testDriveField.lockedVehicleDetails = detailTD;
          }
        } else {
          testDriveField.defaultInputValue = [];
          testDriveField.fieldOptionIds = [];
        }
      } catch (err) {
        console.error('dynamic_testdrive_vehicles error:', err);
      }
    },

    // Populate ONLY the test_drive_vehicle_list used in the TEST DRIVE STATUS section
    async dynamic_testdrive_vehicles_list(triggerValue, field, configKey = 'statusConfig') {
      try {
        const cfg = this[configKey];
        if (!cfg) return;

        const allFields = this.getAllFields(cfg);
        const targetField = allFields.find(f => f.fieldKey === 'test_drive_vehicle_list' || f.fieldKey === 'final_test_drive_vehicle');
        if (!targetField) return;

        const callerKey = field?.fieldKey ?? '';

        // Decide when to load: date provided OR test drive completed toggled to Yes
        let shouldLoad = false;
        if (callerKey === 'test_drive_date' || callerKey === 'testdrive_date') {
          shouldLoad = !!(triggerValue && String(triggerValue).trim());
        } else if (callerKey === 'test_drive_completed' || callerKey === 'is_testdrive') {
          shouldLoad = String(triggerValue) === '1' || triggerValue === 1 || triggerValue === true;
        } else {
          // fallback: only load if we have a non-empty trigger value
          shouldLoad = !!(triggerValue || field?.value);
        }

        if (!shouldLoad) {
          targetField.isHidden = true;
          targetField.isRequired = false;
          if (!targetField.isFixedFromDetail) {
            targetField.value = '';
            targetField.defaultInputValue = [];
            targetField.fieldOptionIds = [];
          }
          return;
        }

        if (targetField?.isFixedFromDetail) {
          targetField.isHidden = false;
          return;
        }

        targetField.isHidden = false;
        targetField.isRequired = true;

        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getreadyforsalevehicleslist'
        });

        if (res?.status === 200 && Array.isArray(res?.body?.data?.list)) {
          const list = res.body.data.list;
          const detailTD = this.detail?.test_drive_vehicle_details ?? null;
          const merged = [...list];

          if (detailTD) {
            const idx = merged.findIndex(v => String(v.id ?? v.value) === String(detailTD.id ?? detailTD.value));
            if (idx === -1) merged.unshift(detailTD);
            else if (idx > 0) {
              const [m] = merged.splice(idx, 1);
              merged.unshift(m);
            }
          }

          targetField.defaultInputValue = merged;
          targetField.fieldOptionIds = merged;

          if (detailTD) {
            targetField.value = String(detailTD.id ?? detailTD.value);
            targetField.defaultValue = targetField.value;
            targetField.isFixedFromDetail = true;
            targetField.lockedVehicleDetails = detailTD;
          }
        } else {
          targetField.defaultInputValue = [];
          targetField.fieldOptionIds = [];
        }
      } catch (err) {
        console.error('dynamic_testdrive_vehicles_list error:', err);
      }
    },


    async dynamic_testdrivelist() {
      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getreadyforsalevehicleslist'
        });
        if (res?.body?.status && res.body.data) {
          const list = res.body.data?.list || [];
          const listKey = 'test_vehicles_list'
          if (Array.isArray(list)) {
            const label = `Select ${listKey.charAt(0).toUpperCase() + listKey.slice(1)}`;
            const opts = [...list.map(item => ({
              value: String(item.value ?? item.id),
              label: item.label ?? item.name ?? String(item.value ?? item.id),
            }))];
            this.updateFieldOptions(listKey, opts);
          }
        }
      } catch (e) {
        console.error(e)
      }
    },

   async saveTestdrive(formrow) {
      if (!this.detail?.id) {
        return { success: false, errors: {} };
      }

      this.isProcessing = true;

      try {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('sub_action', 'savetestdrivevehicle');
        formData.append('id', this.detail.id);

        Object.entries(formrow || {}).forEach(([key, val]) => {
          if (val instanceof File) {
            // Binary file
            formData.append(key, val, val.name);
          } else if (val !== null && val !== undefined) {
            // Text / numeric fields
            formData.append(key, val);
          } else {
            formData.append(key, '');
          }
        });

        const res = await $http(
          'POST',
          `${g.$base_url_api}/${this.endpoint}`,
          formData,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        const body = res?.data || res?.body;
        if (res?.status === 200 && body?.status === 'ok') {
          $toast('success', body?.msg || 'Updated successfully');
          await this.getDetail(this.detail.id);
          return true;
        } else {
          $toast('danger', body?.msg || 'Failed to update');
          return false;
        }
      } catch (err) {
        console.error('saveTestdrive error:', err);
        $toast('danger', err?.message || 'Failed to update');
        return false;
      } finally {
        this.isProcessing = false;
      }
    },




    async dynamic_location(pin_code, field, configKey = 'detailAddConfig') {
      if (!pin_code || !/^\d{6}$/.test(pin_code)) return;

      // Reset dependent fields
      if (Array.isArray(field?.clearFields)) {
        field.clearFields.forEach(f => {
          this.updateFieldOptions(f, []);
          this.updateFieldValue(configKey, f, '');
        });
      }

      // Initialize cache
      if (!this.commonMasterLists.locationByPincode) {
        this.commonMasterLists.locationByPincode = {};
      }

      const cachedData = this.commonMasterLists.locationByPincode[pin_code];

      const getFieldData = (apiData, fieldName) => {
        // Try exact match or fallback: replace '_' with '' and lowercase
        const keyBase = fieldName.replace(/^rc_/, '').toLowerCase(); // rc_state -> state
        if (apiData[keyBase]?.[keyBase] && apiData[keyBase]?.[`${keyBase}_name`]) {
          return {
            value: apiData[keyBase][keyBase],
            label: apiData[keyBase][`${keyBase}_name`]
          };
        }
        return null;
      };

      // Populate fields from cache
      if (cachedData && Array.isArray(field?.clearFields)) {
        field.clearFields.forEach(f => {
          const opts = getFieldData(cachedData, f);
          if (opts) {
            this.updateFieldOptions(f, [opts]);
            this.updateFieldValue(configKey, f, String(opts.value));
          }
        });
        return cachedData;
      }

      // Fetch from API
      try {
        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action: 'getStateCityByPincode',
          pin_code,
        });

        const data = res?.body?.data;
        if (!data) return null;

        // Cache it
        this.commonMasterLists.locationByPincode[pin_code] = data;

        // Populate fields dynamically
        if (Array.isArray(field?.clearFields)) {
          field.clearFields.forEach(f => {
            const opts = getFieldData(data, f);
            if (opts) {
              this.updateFieldOptions(f, [opts]);
              this.updateFieldValue(configKey, f, String(opts.value));
            }
          });
        }

        return data;
      } catch (err) {
        $toast('danger', err?.message || 'Failed to fetch location data');
        return null;
      } finally {
        this.isProcessing = false;
      }
    },

  setPagination(payload) {
    this.savedPagination = {
      current_page: payload.current_page || 1,
      perPage: payload.perPage || this.gridConfig.pagination?.perPage || 10
    };
  },


    // --- Fetch List ---
  async getList(payload = {}, remember = false) {
    this.errors.gridConfig = {};

    const searchConfigFields = this.gridConfig?.searchConfig;
    const validationResult = this.validateInputs(searchConfigFields);
    if (!validationResult.valid) {
      this.errors.gridConfig = validationResult.errors;
      return;
    }

    this.isProcessing = true;
    if (!remember) {
      this.gridConfig.list = [];
    }

    try {
      const formData = this.getFormPayload(searchConfigFields);

      // Decide pagination
      const paginationPayload = remember && this.savedPagination
        ? this.savedPagination
        : {
            current_page: payload.current_page || 1,
            perPage: payload.perPage || this.gridConfig.pagination?.perPage || 10
          };

      this.setPagination(paginationPayload);

      // append extras
      formData.append("action", "getlist");
      Object.entries(this.gridConfig.routeSlugs || {}).forEach(([k, v]) =>
        formData.append(k, v)
      );
      formData.append("current_page", paginationPayload.current_page);
      formData.append("perPage", paginationPayload.perPage);

      const res = await $http("POST", `${g.$base_url_api}/${this.endpoint}`, formData);
      const data = res?.body?.data;

      if (res?.body?.status === "fail" && res.body.errors) {
        this.errors.gridConfig = res.body.errors;
        $toast("danger", res.body.msg || "Validation failed.");
        return;
      }

      if (data) {
        this.gridConfig.pagination = {
          ...data.pagination,
          perPage: paginationPayload.perPage
        };

        if (remember) {
          this.gridConfig.list = [];
        }

        this.gridConfig.list = data.list || [];
        this._prepareSidebar({
          mode: "grid",
          data: data?.menu || {}
        });
      }
    } catch (err) {
      const msg = err?.body?.message || err?.message || "";
      if (msg.toLowerCase().includes("abort")) {
        console.warn("List request aborted:", msg);
        if (window.$toast) $toast("danger", "Request timed out, please retry.");
      } else {
        console.error("Error fetching list:", err);
        if (window.$toast) $toast("danger", "Failed to fetch list.");
      }
    } finally {
      this.isProcessing = false;
    }
  },


    
    async dynamic_stock(id,make, model, year, budget, type = 'counts') {
      this.isProcessing = true;
      try{
        const body = { action: 'getexactmatch', type, id, make, model, year, budget };
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, body);
        const data = res?.body?.data;
        $log('Exact match response:', data);
        if (res?.body?.status === 'ok') {
          return data;
        }
      }
      catch(e){
        console.error(`Error fetching stock data:`, e);
      }
      finally{
        this.isProcessing = false;
      }
    },

    async addEmatchesItem(id, list, type = '', type_id = '') {
      this.isProcessing = true;
      try {
        const body = { action: 'update', sub_action: 'saveematches', id, list: JSON.stringify(list) };
        if (type != '' && type_id != '') {
          body.type = type;
          body.type_id = type_id;
        }
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, body);
        const msg = res?.body?.msg;
        $log('addEmatchItems Response', msg);
        if (res?.body?.status === 'ok') {
          $toast('success', msg || (body.action === 'addematches' ? 'Added successfully' : 'Updated successfully'));
          if (id) await this.getDetail(id);
        }
      } catch (e) {
        console.error(`Error in adding Ematch details:`, e);
      } finally {
        this.isProcessing = false;
      }
    },

    async deleteShortlistItem(id, row_id){
      this.isProcessing = true;
      try{
        const body = {action:'update',sub_action: 'deleteshortlistitem',row_id };
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, body);
        // const msg = res?.body?.msg;
        $log('deleteEmatchItems Response', res?.body.msg);
        if (res?.body?.status === 'ok') {
          $toast('success', res.body.msg || 'Deleted successfully');
          if (id) await this.getDetail(id);
        }
      }
      catch(e){
        console.error(`Error in deleting Ematch details:`, e);
      }
      finally{
        this.isProcessing = false;
      }
    },
   
       async deleteIntrestedItem(id, row_id) {
        this.isProcessing = true;
        try {
          const body = { action: 'update', sub_action: 'deleteintresteditem',row_id };
          const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, body);
          $log('deleteEmatchItems Response', res?.body.msg);
          if (res?.body?.status === 'ok') {
            $toast('success', res.body.msg || 'Deleted successfully');
            if (id) await this.getDetail(id);
          }
        } catch (e) {
          console.error(`Error in deleting Ematch details:`, e);
        } finally {
          this.isProcessing = false;
        }
      },

 // ---------------- Add/Edit Detail ----------------
    async saveDetail() {
    // console.log('[STORE] saveDetail called');
    this.errors.detailAddConfig = {};
    // console.log('[STORE] Cleared errors, now empty:', this.errors.detailAddConfig);
    const detailConfig = this.detailAddConfig;
    // console.log('[STORE] detailConfig sections count:', detailConfig?.fields?.length);
    const validationResult = this.validateInputs(detailConfig);
    // console.log('[STORE] Validation result:', validationResult);

    if (!validationResult.valid) {
      this.errors.detailAddConfig = validationResult.errors;
      // console.log('[STORE] Validation FAILED, errors set:', this.errors.detailAddConfig);
      return { success: false, errors: validationResult.errors };
    }

    // console.log('[STORE] Validation PASSED, proceeding with save');
    this.isProcessing = true;
    try {
      const formData = this.getFormPayload(detailConfig);
      const isUpdate = !!this.detail?.id;

      formData.append("action", isUpdate ? "update" : "addlead");
      if (isUpdate) {
        formData.append("id", this.detail.id);
        formData.append("sub_action", this.detail?.sub_action || "updatelead");
      }

      const res = await $http("POST", `${g.$base_url_api}/${this.endpoint}`, formData);

      if (res?.status === 200) {
        const id = res.body?.data?.id || this.detail?.id;
        $toast("success", res.body.msg || (isUpdate ? "Updated successfully" : "Added successfully"));
        if (!isUpdate && id) $routeTo(`/${this.endpoint}/detail/${id}`);
        this.getDetail(id);
        return { success: true, data: res.body.data };
      }
      throw new Error(res?.body?.message || "Failed to save detail");
    } catch (err) {
      $toast("danger", err.message || "Failed to save detail");
      return { success: false, error: err };
    } finally {
      this.isProcessing = false;
    }
  },
// export functionality not in use
      async exportData() {
      this.errors.gridConfig = {};

      const fields = this.gridConfig?.searchConfig?.fields || [];
      const validationResult = this.validateInputs(fields);

      if (!validationResult.valid) {
        this.errors.gridConfig = validationResult.errors;
        return;
      }
      try {
        const filters = this.getFormPayload(fields);
        const body = {
          action: 'exportdata',
          ...filters,
          ...this.gridConfig.routeSlugs,
        };
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, body);
        const fileUrl = res?.body?.data?.file_url;
        if (res.status === 200 && fileUrl) {
          $downloadFile(fileUrl);
        } else {
          console.error("Export failed:", res);
          alert('Failed to export data');
        }
      } catch (err) {
        console.error('Error exporting data:', err);
        alert('Failed to export data');
      }
    },



    // ---------------- Add/Edit Detail ----------------

    async loadDependentDropdowns(configKey = 'detailAddConfig') {
        // Use the requested config (detailAddConfig / statusConfig / etc.)
        const configObj = this[configKey];
        if (!configObj) return;

        const allFields = this.getAllFields(configObj);
        const fieldsWithHandlers = allFields.filter(field =>
            field.inputChange && (this.detail?.[field.fieldKey] !== undefined && this.detail[field.fieldKey] !== '')
        );

        // Process in simple order - fields without dependencies first
        for (const field of fieldsWithHandlers) {
            const value = this.detail[field.fieldKey];
            field.value = value;

            try {
                // Use the provided configKey here so dynamic loaders update the right config
                await this.handleFieldEvent('change', configKey, field.fieldKey, field, null);

                // If this field clears other fields, handle those dependent fields
                if (field.clearFields && Array.isArray(field.clearFields)) {
                    for (const dependentKey of field.clearFields) {
                        if (this.detail[dependentKey]) {
                            const dependentField = allFields.find(f => f.fieldKey === dependentKey);
                            if (dependentField && dependentField.inputChange) {
                                // Small delay for parent dropdown to populate
                                await new Promise(resolve => setTimeout(resolve, 100));
                                dependentField.value = this.detail[dependentKey];
                                await this.handleFieldEvent('change', configKey, dependentKey, dependentField, null);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error(`Error loading ${field.fieldKey}:`, error);
            }
        }
    },
        


    _checkConfig() {
      const menu = this.detailMenuConfig?.detail || {};
      if (!menu || Object.keys(menu).length === 0) return;

      this.componentsConfig = Object.entries(menu).reduce((acc, [key, item]) => {
        if (item.isReadOnly === true) {
          acc[key] = {
            isReadOnly: true,
          };
        }
        return acc;
      }, {});
    },
   

    

    async getDetail(encryptedId, preserveFormState = false) {
      if (!encryptedId) return;
      this.isProcessing = true;
      try {
        const r = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getlead',
          id: encryptedId
        });

        if (r?.status === 200 && r?.body?.data) {
          const responseData = r.body.data;
          $log('RES',responseData);
          
          //  Extract nested properties from API response
          // Main detail object with all lead fields
          this.detail = {
            ...responseData.detail,           // All lead fields (id, dealer, branch, etc.)
            documents: responseData.documents || {},
            shortlisted_vehicles: responseData.shortlisted_vehicles || [],
            interested_vehicles: responseData.interested_vehicles || {},
            testdrive_vehicles: responseData.testdrive_vehicles || [],
            history: responseData.history || [],
          };

          $log('DET', this.detail);
                    
          await this.loadDependentDropdowns('detailAddConfig');
          await this.loadDependentDropdowns('statusConfig');

          // Set detail values into configs only if not preserving form state
          // Pass this.detail directly (not this.detail['detail']) since we already merged all fields
          if (this.detailAddConfig && this.detailAddConfig.fields) {
            this.mapDetailToConfig('detailAddConfig', this.detail);
          }
          if (this.statusConfig && this.statusConfig.fields) {
            this.mapDetailToConfig('statusConfig', this.detail);
          }
          this.detailMenuConfig.detail = responseData?.menu || {};
          this._checkConfig();
        } else {
          $toast('danger', r?.body?.msg || 'Failed to load lead detail');
          this.detail = {};
        }
      } catch (e) {
        console.error('Error loading lead detail:', e);
        $toast('danger', e?.body?.msg || 'Failed to load lead detail');
        this.detail = {};
      } finally {
        this.isProcessing = false;
      }
    },

    async saveStatus() {
          this.errors.statusConfig = {};

      if (!this.detail?.id) {
        return { success: false, errors: {} };
      }

      const statusConfig = this.statusConfig;
      const validationResult = this.validateInputs(statusConfig);

      if (!validationResult.valid) {
        this.errors.statusConfig = validationResult.errors;
        return { success: false, errors: validationResult.errors };
      }

      this.isProcessing = true;
      try {
        const formData = this.getFormPayload(statusConfig);
        formData.append("action", "update");
       
        $log('apple FormData in sm', formData);
        if (this.detail?.id) {
          formData.append("id", this.detail.id);
          formData.append("sub_action", "updatestatus");
        }

        const res = await $http("POST", `${g.$base_url_api}/${this.endpoint}`, formData);
        const body = res?.body || {};
        if (res?.status === 200 && body.status === "ok") {
          $toast("success", body.msg || "Status updated successfully");
          this.getDetail(this.detail.id);
          return { success: true, msg: body.msg || "Status updated successfully" };
        } else {
          $toast("danger", body.msg || "Failed to update status");
          this.errors.statusConfig = body.errors || {};
          return {
            success: false,
            errors: body.errors || {},
            msg: body.msg || "Failed to update status"
          };
        }
      } catch (err) {
        console.error("saveStatus error:", err);

        // ✅ handle API error with response body
        if (err) {
          try {
            const body = err?.body || {};
            $toast("danger", body.msg || "Failed to update status");
            this.errors.statusConfig = body.errors || {};
            return {
              success: false,
              errors: body.errors || {},
              msg: body.msg || "Failed to update status"
            };
          } catch (parseErr) {
            console.error("Error parsing response body:", parseErr);
          }
        }

        // fallback if no response
        $toast("danger", err?.message || "Failed to update statuszz");
        return { success: false, errors: {}, msg: err?.message || "Failed to update status" };
      } finally {
        this.isProcessing = false;
      }
    },

    async assignExecutive(payload = {}) {
      const {
        id,
        branch,
        executive
      } = payload;
      if (!id) return false;

      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'update',
          sub_action: 'updateExecutive',
          id,
          branch: branch || '0',
          executive: executive || '0'
        });

        if (res?.status === 200 && res?.body?.status === 'ok') {
          $toast('success', res.body.msg || 'Executive assigned successfully');
          this.getList({}, true);
          return true;
        }

        $toast('danger', res?.body?.msg || 'Failed to assign executive');
        return false;
      } catch (err) {
        console.error('assignExecutive error:', err);
        $toast('danger', err?.message || 'Failed to assign executive');
        return false;
      } finally {
        this.isProcessing = false;
      }
    },

    async getHistory(encryptedId) {
      let historyDetails = [];
      if (!encryptedId) return [];
      try {
        const r = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'gethistory',
          id: encryptedId
        });
        if (r?.status === 200 && r?.body?.data) {
          const responseData = r.body.data;
          historyDetails = responseData?.history || [];
        } else {
          $toast('danger', r?.body?.msg || 'Failed to load history');
        }
      } catch (e) {
          console.error('Error loading history detail:', e);
          $toast('danger', r?.body?.msg || 'Failed to load history');
      } finally {
          return historyDetails;
      }
    },





  },


});


const store = useStoreSalesMaster();
store.init();