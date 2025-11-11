const { defineStore } = Pinia;

export const useStoreMyStock = defineStore('stock', {
  // ---------------- STATE ----------------
  state: () => ({
    isProcessing: false,
    isProcessingHistory: false,
    _initialized: false,
    _loadingDetailId: null, // Track which detail is currently being loaded to prevent duplicates
    _loadingDependentDropdowns: false, // Prevent concurrent dropdown loading
    endpoint: null,
    errors: { gridConfig: {}, detailAddConfig: {}, certificationConfig: {} },
    componentsConfig: {},
    masterLists: {},
    commonMasterLists: {
      modelsWithVariants: {},
      branchesWithExecutives: [],
    },
    menuConfig: { title: 'My Stock', subtitle: '' },
    sidebarConfig: { showSidebar: false, sidebarItems: [] },
    gridConfig: { searchConfig: {}, list: [], pagination: {}, routeSlugs: {} },
    detail: {},
    detailAddConfig: {},
    detailMenuConfig: { default:{}, detail:{} },
    overviewConfig: {},
    imagesConfig: {},
    historyConfig: {},
    statusConfig: {},
    evaluationConfig: {},
    certificationConfig: {},
    approvalConfig: {},
    vahanConfig: {},
    vahanFieldMapping: null,
    vahanData: {},
    vahanLoading: false,
    vahanError: null,

    savedPagination: null,
    _defaultConfigs: null,
    _currentMode: null,
  }),

    // ---------------- GETTERS ----------------
    getters: {
        routePath: (s) => (s.endpoint ? `/${s.endpoint}` : ''),
        getMenuConfig: (s) => s.menuConfig,
        getSidebarConfig: (s) => s.sidebarConfig,
        getGridConfig: (s) => s.gridConfig,
        getDetails: (s) => s.detail || {},
        getDetailAddConfig: (s) => s.detailAddConfig?.fields || [],
        getDetailMenuConfig: (s) => s.detailMenuConfig.detail || {},
        getOverviewConfig: (s) => s.overviewConfig || {},
        getApprovalConfig: (s) => s.approvalConfig || {},
        getImagesConfig: (s) => s.imagesConfig || {},
        getHistoryConfig: (s) => s.historyConfig || {},
        getStatusConfig: (s) => s.statusConfig || {},
        getEvaluationConfig: (s) => s.evaluationConfig || {},
        getCertificationConfig: (s) => s.certificationConfig || {},
        getVahanConfig: (s) => s.vahanConfig || {},
        getExecutives: (s) => s.masterLists.executive || {},
        getOptionsForField: (s) => (fieldName, field) => s.masterLists?.[fieldName]?.length ? s.masterLists[fieldName] : field?.options || [],
        getExecutivesByBranchId: (s) => (branchId) => {
          const branch = s.commonMasterLists.branchesWithExecutives?.find(
            (b) => String(b.branch_id) === String(branchId)
          );
          if (!branch || !Array.isArray(branch.executives)) return [];
          return branch.executives.map((e) => ({
            value: String(e.id),
            label: e.name,
          }));
        }
    },
  // ---------------- ACTIONS ----------------
  actions: {
    // ---------------- INIT & CONFIG ----------------
    async init() {
      if (this._initialized) return;
      this._initialized = true;
      this.endpoint = this.endpoint || $routeGetMeta?.('path') || 'my-stock';
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
        console.log(cfg)
        //debugger;
        Object.assign(this, {
          sidebarConfig: cfg.sidebar || this.sidebarConfig,
          gridConfig: cfg.grid || this.gridConfig,
          overviewConfig: cfg.overview || this.overviewConfig,
          approvalConfig: cfg.approvalConfig || this.approvalConfig,
          detailAddConfig: cfg.addConfig || this.detailAddConfig,
          detailMenuConfig: {
            default: cfg.menu || this.detailMenuConfig.default,
          },
          historyConfig: cfg.history || this.historyConfig,
          statusConfig: cfg.status || this.statusConfig,
          evaluationConfig: cfg.evaluation || this.evaluationConfig,
          certificationConfig: cfg.certificationConfig || this.certificationConfig,
          imagesConfig: cfg.images || this.imagesConfig,
          vahanConfig: cfg.vahanConfig || this.vahanConfig,
        });


        if (!this._defaultConfigs) {
          this._defaultConfigs = JSON.parse(JSON.stringify({
            gridConfig: this.gridConfig,
            detailAddConfig: this.detailAddConfig,
            overviewConfig: this.overviewConfig,
            approvalConfig: this.approvalConfig,
            imagesConfig: this.imagesConfig,
            evaluationConfig: this.evaluationConfig,
            certificationConfig: this.certificationConfig, 
            historyConfig: this.historyConfig,
            statusConfig: this.statusConfig,
            vahanConfig: this.vahanConfig,
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
            this.imagesConfig = JSON.parse(JSON.stringify(this._defaultConfigs.imagesConfig));
            this.evaluationConfig = JSON.parse(JSON.stringify(this._defaultConfigs.evaluationConfig));
            this.historyConfig = JSON.parse(JSON.stringify(this._defaultConfigs.historyConfig));
            this.statusConfig = JSON.parse(JSON.stringify(this._defaultConfigs.statusConfig));
            this.vahanConfig = JSON.parse(JSON.stringify(this._defaultConfigs.vahanConfig));
            this.detailMenuConfig = JSON.parse(JSON.stringify(this._defaultConfigs.detailMenuConfig));
            this.certificationConfig = JSON.parse(JSON.stringify(this._defaultConfigs.certificationConfig));
            this.approvalConfig = JSON.parse(JSON.stringify(this._defaultConfigs.approvalConfig));
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
              href: hasSub
                ? null
                : key !== 'all'
                ? baseRoute
                : `/${this.endpoint}`,
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


    // ---------------- EXECUTIVE HANDLERS ----------------
   
   
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
        //alert(branchId)
      const execs = this.getExecutivesByBranchId(branchId);
      this.updateFieldOptions("executive", execs);
      this.updateFieldOptions("certified_by", execs);
    },
    
    // ---------------- MASTER DATA ----------------
    async loadInitialMasterData() {
      try {
        this.getYears(['mfg_year']);
        this.getMonths(['mfg_month']);

        // ---------------- MASTER CONFIG ----------------
        const masters = {
          getMakes: ['make', 'rs_make'],
          getSources: ['source'],
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
      } catch (err) {
        // Error loading master data
      }
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
            const label = `Select ${listKey.charAt(0).toUpperCase() + listKey.slice(1)}`;
            const opts = [...list.map(item => ({
              value: String(item.value ?? item.id),
              label: item.label ?? item.name ?? String(item.value ?? item.id),
            }))];
            this.updateFieldOptions(listKey, opts);
          }
        }
      } catch (e) {
        // Error fetching master data
      }
    },

    // ---------------- HELPERS ----------------
    // ---------------- HELPERS ----------------
    getYears(fieldNames=[]) {
      const startYear = 2000;
      const current = new Date().getFullYear();
      const years = [...Array.from({
        length: current - startYear + 1
      }, (_, i) => {
        const y = current - i;
        return {
          value: String(y),
          label: String(y)
        };
      })];
       fieldNames.forEach((fieldName) => {
          this.updateFieldOptions(fieldName, years);
       });
    },

    getMonths(fieldNames=[]) {
      const months = [];
      for (let i = 0; i < 12; i++) {
        const date = new Date(0, i);
        months.push({
          value: String(i + 1), // No padding - store as "1", "2", "3"... "12"
          label: date.toLocaleString('en', {
            month: 'long'
          })
        });
      }
      fieldNames.forEach((fieldName) => {
          this.updateFieldOptions(fieldName, months);
      });
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


    // --- Validate Inputs ---
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
   
   
   
    validateInputs(config) { // Now accepts the full config object
      console.table(config);

      // Get all relevant fields using the updated getAllFields function
      const allFields = this.getAllFields(config);
      console.table(allFields);

      const errors = {};
      let valid = true;

      allFields.forEach(field => {
        const {
          fieldKey,
          fieldLabel,
          value,
          isRequired,
          maxLength,
          inputType,
          validation
        } = field;
        let val = typeof value === 'string' ? value.trim() : value;

        // Check for required fields
        if (isRequired && (val === null || val === undefined || val === '')) {
          valid = false;
          errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
          return; // Stop checking this field if it's required and empty
        }

        // If the field is not required and has no value, skip further validation for it
        if (!val && !isRequired) {
          return;
        }

        // Check maximum length for string values
        if (maxLength && typeof val === 'string' && val.length > maxLength) {
          valid = false;
          errors[fieldKey] = `Maximum length is ${maxLength} characters`;
          return;
        }

        // Validate against a regex pattern
        if (validation?.validationPattern && typeof val === 'string') { // Ensure val is a string for regex.test
          // Remove leading/trailing slashes from the regex string if present
          const regexPattern = validation.validationPattern.replace(/^\/|\/$/g, '');
          const regex = new RegExp(regexPattern);
          if (!regex.test(val)) {
            valid = false;
            errors[fieldKey] = validation.errorMessageInvalid || `Invalid ${fieldLabel || fieldKey}`;
            return;
          }
        }

        // Validate numeric inputs
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
    
    getFormPayload(config) {
  const formData = new FormData();
  const fieldsToExtract = this.getAllFields(config);

  fieldsToExtract.forEach(f => {
    let val = f.value;

    // Handle file fields
    if (f.inputType === "file") {
      if (f.multiple && Array.isArray(val)) {
        val.forEach((file, idx) => {
          if (file instanceof File) {
            formData.append(`${f.fieldKey}[${idx}]`, file);
          } else if (typeof file === 'string' && file.trim() !== '') {
            formData.append(`${f.fieldKey}[${idx}]`, file);
          }
        });
      } else {
        if (val instanceof File) {
          formData.append(f.fieldKey, val);
        } else if (typeof val === 'string' && val.trim() !== '') {
          formData.append(f.fieldKey, val);
        } else {
          // Empty file field - send empty string (backend converts to NULL)
          formData.append(f.fieldKey, '');
        }
      }
      return;
    }

    // Handle non-file fields
    if (typeof val === 'string') val = val.trim();
    
    // Send current value or empty string (backend handles empty → NULL conversion)
    const valueToSend = (val === undefined || val === null || val === '') ? '' : val;
    formData.append(f.fieldKey, valueToSend);
  });

  return formData;
},

    updateFieldOptions(key, options) {
      this.masterLists[key] = Array.isArray(options) ? options : [{
        value: '',
        label: `Select ${key}`
      }];
    },


    // Map detail data into config fields (handles nested sections + conditionals)
    mapDetailToConfig(configKey, detail) {
      // debugger;
      // Pass the detail object to getAllFields to correctly identify conditional fields
      const config = configKey.split('.').reduce((obj, key) => obj && obj[key], this);

      const allFields = this.getAllFields(config, detail);

      console.table(allFields); // You should now see conditional fields here

      allFields.forEach(field => {
        if (detail[field.fieldKey] !== undefined) {
          // Ensure field has validation object
          if (!field.validation) field.validation = {};
          // Assign to `value` (what your form uses)
          field.value = detail[field.fieldKey];
          this.applyConditionalLogic(configKey, field.fieldKey, field);
        }
      });
    },


    updateFieldValue(configKey, fieldName, value) {
      // Update field value

      const config = this[configKey];
      if (!config) return;

      // Get all fields including nested and conditional ones
      const allFields = this.getAllFields(config);

      // Find the target field by key and update its value
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

      // Don't make API call if make is empty, 0, or "0"
      if (!make || make === 0 || make === '0') {
        return;
      }

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

     dynamic_variants(modelValue) {
      this.updateFieldOptions('variant', [{
        value: '',
        label: 'Select Variant'
      }]);
      if (!modelValue) return;

      // safety check
      if (!this.masterLists?.modelsWithVariants) {
        return;
      }

      const selectedModel = this.masterLists?.modelsWithVariants.find(
        (m) => m.value == modelValue // allow type coercion
      );

      if (selectedModel?.variants?.length) {
        this.updateFieldOptions('variant', selectedModel.variants);
      }
    },


   
    async dynamic_subsources(source) {
      this.updateFieldOptions('source_sub', [{
        value: '',
        label: 'Select Sub Source'
      }]);
      if (source) await this.fetchMaster('getSubSources', 'source_sub', {
        source
      });
    },


    async dynamic_location(pin_code, field, configKey = 'detailAddConfig') {
      if (!pin_code || !/^\d{6}$/.test(pin_code)) return;

      // Reset state & city fields in masterLists and config
      ['state', 'city'].forEach(f => {
        this.updateFieldOptions(f, [{
          value: '',
          label: `Select ${f.charAt(0).toUpperCase() + f.slice(1)}`
        }]);
        this.updateFieldValue(configKey, f, ''); // reset form field value
      });

      try {
        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action: 'getStateCityByPincode',
          pin_code,
        });

        const data = res?.body?.data;
        if (!data) return;

        // --- Update State ---
        if (data.state?.state && data.state?.state_name) {
          const stateOpts = [{
            value: data.state.state,
            label: data.state.state_name
          }];
          this.updateFieldOptions('state', stateOpts);
          this.updateFieldValue(configKey, 'state', String(data.state.state));
        }

        if (data.city?.city && data.city?.city_name) {
          const cityOpts = [{
            value: data.city.city,
            label: data.city.city_name
          }];
          this.updateFieldOptions('city', cityOpts);
          this.updateFieldValue(configKey, 'city', String(data.city.city));
        }

        return data;
      } catch (err) {
        console.error('dynamic_location failed:', err);
        $toast('danger', err?.message || 'Failed to fetch location data');
        return null;
      }
    },


    // --- Fetch List ---

     setPagination(payload) {
    this.savedPagination = {
      current_page: payload.current_page || 1,
      perPage: payload.perPage || this.gridConfig.pagination?.perPage || 10
    };
  },
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




 // ---------------- Add/Edit Detail ----------------

    async saveDetail() {
      this.errors.detailAddConfig = {};
      // Pass the entire detailAddConfig to validateInputs
      const detailConfig = this.detailAddConfig;
      const validationResult = this.validateInputs(detailConfig); // Pass detailAddConfig

      if (!validationResult.valid) {
        this.errors.detailAddConfig = validationResult.errors;
        return {
          success: false,
          errors: validationResult.errors
        };
      }
      this.isProcessing = true;
      try {
        // Use the entire detailAddConfig to generate form payload
        const formData = this.getFormPayload(detailConfig); // Pass detailAddConfig
        formData.append("action", "update");
        if (this.detail?.id) {
          formData.append("id", this.detail.id);
          formData.append("sub_action", "updatelead");
        }
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, formData);
        const body = res?.body || {};
         if (res?.status === 200 && body.status === "ok") {
          $toast("success", body.msg || "Stock details updated successfully");
          this.getDetail(this.detail.id);
          return { success: true, msg: body.msg || "Stock details updated successfully" };
        } else {
          $toast("danger", body.msg || "Failed to update the stock");
          this.errors.statusConfig = body.errors || {};
          return {
            success: false,
            errors: body.errors || {},
            msg: body.msg || "Failed to update the stock"
          };
        }
      } catch (err) {
        if (err) {
          try {
            const body = err?.body || {};
            $toast("danger", body.msg || "Failed to update the stock");
            this.errors.statusConfig = body.errors || {};
            return {
              success: false,
              errors: body.errors || {},
              msg: body.msg || "Failed to update stock"
            };
          } catch (parseErr) {
            console.error("Error parsing response body:", parseErr);
          }
        }

        // fallback if no response
        $toast("danger", err?.message || "Failed to update stock");
        return { success: false, errors: {}, msg: err?.message || "Failed to update stock" };
      } finally {
        this.isProcessing = false;
      }
    },
    
    async saveCertification()
    {
      this.errors.certificationConfig = {};
      // Pass the entire detailAddConfig to validateInputs
      const detailCertificationConfig = this.certificationConfig;
      console.log(detailCertificationConfig)
      const validationResult = this.validateInputs(detailCertificationConfig); // Pass detailAddConfig

      if (!validationResult.valid) {
        this.errors.certificationConfig = validationResult.errors;
        return {
          success: false,
          errors: validationResult.errors
        };
      }
      this.isProcessing = true;
      try {
        // Use the entire detailAddConfig to generate form payload
        const formData = this.getFormPayload(detailCertificationConfig); // Pass detailAddConfig
        
        formData.append("action", "update");
        if (this.detail?.id) {
          formData.append("id", this.detail.id);
          formData.append("sub_action", "savecertification");
        }
       
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, formData);
        const body = res?.body || {};

        if (res?.status === 200 && body.status === "ok") {
          $toast("success", body.msg || "Certification details are updated successfully");
          this.getDetail(this.detail.id);
          return { success: true, msg: body.msg || "Certification details are updated successfully" };
        } else {
          $toast("danger", body.msg || "Failed to update certification details");
          this.errors.statusConfig = body.errors || {};
          return {
            success: false,
            errors: body.errors || {},
            msg: body.msg || "Failed to update certification details"
          };
        }
      } catch (err) {
        console.error("saveStatus error:", err);

        // ✅ handle API error with response body
        if (err) {
          try {
            const body = err?.body || {};
            $toast("danger", body.msg || "Failed to update certification details");
            this.errors.statusConfig = body.errors || {};
            return {
              success: false,
              errors: body.errors || {},
              msg: body.msg || "Failed to update certification details"
            };
          } catch (parseErr) {
            console.error("Error parsing response body:", parseErr);
          }
        }

        // fallback if no response
        $toast("danger", err?.message || "Failed to update statuszz");
        return { success: false, errors: {}, msg: err?.message || "Failed to update certification details" };
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
          $toast('error', 'Failed to export data');
        }
      } catch (err) {
        console.error('Error exporting data:', err);
        $toast('error', 'Failed to export data');
      }
    },


    // ---------------- Add/Edit Detail ----------------

    async loadDependentDropdowns() {
        // Prevent multiple concurrent executions
        if (this._loadingDependentDropdowns) {
            return;
        }
        
        this._loadingDependentDropdowns = true;
        try {
            const allFields = this.getAllFields(this.detailAddConfig);
            const fieldsWithHandlers = allFields.filter(field => 
                field.inputChange && this.detail[field.fieldKey] && 
                this.detail[field.fieldKey] !== 0 && this.detail[field.fieldKey] !== '0'
            );

        // Process in simple order - fields without dependencies first
        for (const field of fieldsWithHandlers) {
            const value = this.detail[field.fieldKey];
            field.value = value;
            
            try {
            await this.handleFieldEvent('change', 'detailAddConfig', field.fieldKey, field, null);
            
            // If this field clears other fields, handle those dependent fields
            if (field.clearFields && Array.isArray(field.clearFields)) {
                for (const dependentKey of field.clearFields) {
                if (this.detail[dependentKey]) {
                    const dependentField = allFields.find(f => f.fieldKey === dependentKey);
                    if (dependentField && dependentField.inputChange) {
                    // Small delay for parent dropdown to populate
                    await new Promise(resolve => setTimeout(resolve, 100));
                    dependentField.value = this.detail[dependentKey];
                    await this.handleFieldEvent('change', 'detailAddConfig', dependentKey, dependentField, null);
                    }
                }
                }
            }
            } catch (error) {
            console.error(`Error loading ${field.fieldKey}:`, error);
            }
        }
        } finally {
            this._loadingDependentDropdowns = false;
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
   

    async getDetail(encryptedId) {
      if (!encryptedId) return;
      
      //Prevent duplicate concurrent calls for the same detail
      if (this._loadingDetailId === encryptedId) {
        return;
      }
      
      //If detail is already loaded and matches, skip the API call
      if (this.detail?.id === encryptedId && Object.keys(this.detail).length > 1) {
        return;
      }
      
      this._loadingDetailId = encryptedId;
      this.isProcessing = true;
      try {
        const r = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getlead',
          id: encryptedId
        });

        if (r?.status === 200 && r?.body?.data) {
          // Store detail with encrypted ID as main ID
          const responseData = r.body.data || {};
          
          this.detail = {
            ...responseData.detail,           // All lead fields (id, dealer, branch, etc.)
            documents: responseData.documents || {},
            images: responseData.images || {},
            history: responseData.history || [],
            evaluation: responseData.evaluation_templates || {},
            vahanInfo: responseData.vahanInfo || {},
          };

          await this.loadDependentDropdowns();

          // 🔹 Set detail values into configs
          if (this.detailAddConfig) {
            this.mapDetailToConfig('detailAddConfig', this.detail);
          }
          if (this.statusConfig) {
            this.mapDetailToConfig('statusConfig', this.detail);
          }
          
           if (this.certificationConfig) {
            this.mapDetailToConfig('certificationConfig', this.detail);
          }

          // if (this.approvalConfig) {
          //   this.mapDetailToConfig('approvalConfig', this.detail);
          // }
        

          this.detailMenuConfig.detail = responseData?.menu || {};
          // History config is now unified (no separate JLR/non-JLR configs needed)
          // The historyConfig from getConfig is already set correctly
          
          // Refresh sidebar with updated detail
          this._prepareSidebar({mode: 'detail', data: encryptedId});
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
        this._loadingDetailId = null; // Reset loading flag
      }
    },

    // ---------------- HISTORY ----------------
    async fetchHistory(limit = 10, offset = 0) {
      try {
        const encId = this.detail?.id;
        if (!encId) return;
        this.isProcessingHistory = true;
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'gethistory',
          inventory_id: encId,
          limit,
          offset
        });

        if (res?.status === 200 && res?.body?.status === 'ok') {
          const data = res.body?.data || {};
          // API returns the correct grouped columns based on is_jlr_vehicle
          this.historyConfig = Array.isArray(data.historyConfig) ? data.historyConfig : [];
          // Update both the historyConfig and the detail.history for UI consistency
          if (!this.detail) this.detail = {};
          this.detail.history = Array.isArray(data.history) ? data.history : [];
        } else {
          // no-op on unexpected response
        }
      } catch (e) {
        // swallow history fetch errors to avoid noisy console in production
      } finally {
        this.isProcessingHistory = false;
      }
    },

    // ---------------- REFURBISHMENT (Evaluation) ----------------
    async saveRefurbishment({ template_id, checklist }) {
      if (!this.detail?.id) return false;
      this.isProcessing = true;
      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'update',
          sub_action: 'addevaluation',
          id: this.detail.id,
          template_id,
          checklist
        });
        if (res?.status === 200 && res?.body?.status === 'ok') {
          if (res.body?.data?.evaluation) {
            this.detail.evaluation = res.body.data.evaluation;
          } else {
            // reload full detail to be safe
            await this.getDetail(this.detail.id);
          }
          $toast('success', res.body?.msg || 'Refurbishment details saved');
          return true;
        }
        $toast('danger', res?.body?.msg || 'Failed to save refurbishment details');
        return false;
      } catch (e) {
        console.error('saveRefurbishment error:', e);
        $toast('danger', e?.message || 'Failed to save refurbishment details');
        return false;
      } finally {
        this.isProcessing = false;
      }
    },
  // Update inventory status for refurbishment workflow
  async updateInventoryStatus(targetStatus) {
    if (!this.detail?.id) {
      console.error('No detail ID available for status update');
      return { success: false, msg: 'No vehicle selected' };
    }

    this.isProcessing = true;
    
    try {
      const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
        action: 'updatestatus',
        id: this.detail.id,
        status: targetStatus
      });

      if (res?.status === 200 && res?.body?.status === 'ok') {
        $toast('success', res.body.msg || 'Status updated successfully');
        
        // Update local detail status
        if (this.detail) {
          this.detail.status = targetStatus;
          // Refresh sidebar to update disabled states
          this._prepareSidebar({mode: 'detail', data: this.detail.id});
        }
        
        // Redirect based on target status
        if (targetStatus === 2) {
          // For certifiable vehicles, navigate to certification tab
          if (this.detail?.is_certifiable === 'y') {
            setTimeout(() => {
              $routeTo('certification');
            }, 1500);
          } else {
            // For non-certifiable vehicles, update status to 4 (Ready for Sale) directly
            setTimeout(() => {
              this.updateInventoryStatus(4);
            }, 1000);
          }
        } else if (targetStatus === 4) {
          // Vehicle is now ready for sale - stay on approval tab (final tab)
          setTimeout(() => {
            // Vehicle status updated, but no navigation needed as approval is final tab
            console.log('Vehicle status updated to Ready for Sale');
          }, 1500);
        }
        
        return { success: true, msg: res.body.msg || 'Status updated successfully' };
      }

      const errorMsg = res?.body?.msg || 'Failed to update status';
      $toast('danger', errorMsg);
      return { success: false, msg: errorMsg };
      
    } catch (err) {
      console.error('updateInventoryStatus error:', err);
      const errorMsg = err?.message || 'Failed to update status';
      $toast('danger', errorMsg);
      return { success: false, msg: errorMsg };
    } finally {
      this.isProcessing = false;
    }
  },


       
    // ---------------- IMAGE UPLOAD ----------------
    async uploadImage(tag, imageFile) {
      this.isProcessing = true;
      const id = this.detail?.id;

      try {
        if (!id) throw new Error("Lead ID is required for image upload.");
        if (!imageFile) throw new Error("Image file is required.");
        if (!tag) throw new Error("Image tag is required.");

        const formData = new FormData();
        formData.append("action", "update");
        formData.append("sub_action", "uploadimages");
        formData.append("id", id);
        formData.append("image", imageFile);
        formData.append("tag", tag);

        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, formData);

        if (res?.status === 200 && res?.body?.status === 'ok') {
          $toast('success', res.body?.msg || `Image for ${tag} uploaded successfully!`);
          await this.getDetail(id);
          return {
            success: true,
            data: res.body.data
          };
        }
        throw new Error(res.body?.msg || `Failed to update image for ${tag}`);
      } catch (e) {
        console.error('uploadImage error:', e);
        $toast('danger', e.message || 'Failed to update image');
        return {
          success: false,
          error: e.message
        };
      } finally {
        this.isProcessing = false;
      }
    },

    async deleteImage(key, imageId) {
      this.isProcessing = true;
      const id = this.detail?.id;

      try {
        if (!id) throw new Error("Inventory ID is required for image deletion.");
        if (!imageId) throw new Error("Image ID is required for deletion.");

        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'update',
          sub_action: 'deleteimage',
          id: id,
          image_id: imageId
        });

        if (res?.status === 200 && res?.body?.status === 'ok') {
          $toast('success', res.body?.msg || 'Image deleted successfully!');
          await this.getDetail(id);
          return {
            success: true,
            message: res.body?.msg || 'Image deleted successfully!'
          };
        }
        throw new Error(res.body?.msg || 'Failed to delete image');
      } catch (e) {
        console.error('deleteImage error:', e);
        $toast('danger', e.message || 'Failed to delete image');
        return {
          success: false,
          error: e.message
        };
      } finally {
        this.isProcessing = false;
      }
      if (!this.detail?.id) return {
        success: false,
        errors: {}
      };

      const payload = {};
      // Collect only enabled fields
      Object.entries(this.statusConfig).forEach(([key, field]) => {
        if (!field.readonly && field.validation?.show !== false) {
          payload[key] = field.val ?? '';
        }
      });
      payload.id = this.detail.id;

      this.isProcessing = true;
      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'update',
          sub_action: 'updatestatus',
          ...payload
        });

        if (res?.status === 200 && res?.body?.status === 'ok') {
          $toast('success', res.body.msg || 'Status updated successfully');
          return {
            success: true,
            msg: res.body.msg || 'Status updated successfully'
          };
        } else {
          $toast('danger', res?.body?.msg || 'Failed to update status');
          return {
            success: false,
            errors: res?.body?.errors || {},
            msg: res?.body?.msg || 'Failed to update status'
          };
        }
      } catch (err) {
        console.error('saveStatusForm error:', err);
        $toast('danger', err?.message || 'Failed to update status');
        return {
          success: false,
          errors: {},
          msg: err?.message || 'Failed to update status'
        };
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

    // ---------------- VAHAN DETAILS TAB ----------------
    // Load Vahan details for Vahan tab - reads from database

    async fetchVahanDetailsForTab() {
      // Use vahanInfo from detail object (populated by getLead API)
      this.vahanLoading = true;
      this.vahanError = null;

      try {
        // Check if vahanInfo exists in detail (comes from getLead response)
        if (this.detail?.vahanInfo && Object.keys(this.detail.vahanInfo).length > 0) {
          this.vahanData = { ...this.detail.vahanInfo };
          this.vahanError = null;
        } else {
          // No Vahan data available
          this.vahanError = 'No Vahan data available. This data is automatically transferred from Purchase Master when the vehicle is purchased.';
          this.vahanData = {};
        }
      } catch (err) {
        console.error("Error loading Vahan data:", err);
        this.vahanError = 'Failed to load Vahan details';
        this.vahanData = {};
      } finally {
        this.vahanLoading = false;
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


const store = useStoreMyStock();
store.init();