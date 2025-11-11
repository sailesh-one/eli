const { defineStore } = Pinia;
export const useStorePurchaseMaster = defineStore('pm', {
  // ============================================================
  // STATE
  // ============================================================
  state: () => ({
    isProcessing: false,
    _initialized: false,
    endpoint: null,

    errors: {
      gridConfig: {},
      detailAddConfig: {},
      statusConfig: {},
    },
    componentsConfig: {},
    commonMasterLists: {
      modelsWithVariants: {},
      branchesWithExecutives: [],
    },
    masterLists: {},
    menuConfig: {
      title: 'Purchase Master',
      subtitle: '',
    },
    detailMenuConfig: {
      default: {},
      detail: {},
    },
    sidebarConfig: {
      showSidebar: false,
      sidebarItems: [],
    },
    gridConfig: {
      searchConfig: {},
      list: [],
      pagination: {},
      routeSlugs: {},
    },
    detail: {},
    detailAddConfig: {},
    overviewConfig: {},
    imagesConfig: {},
    evaluationConfig: {},
    historyConfig: {},
    statusConfig: {},
    vahanConfig: {},
    vahanData: {},
    vahanFieldMapping: null,
    vahanLoading: false,
    vahanError: null,
    savedPagination: null,
    // Cache
    _defaultConfigs: null,
    _currentMode: null,
  }),

  // ============================================================
  // GETTERS
  // ============================================================
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
    getStatusConfig: (s) => s.statusConfig?.fields || {},
    getStatusOverviewConfig: (s) => s.statusConfig?.columns || [],
    getVahanConfig: (s) => s.vahanConfig || {},
    getExecutives: (s) => s.masterLists.executive || {},

    getOptionsForField: (s) => (fieldName, field) =>
      s.masterLists?.[fieldName]?.length
        ? s.masterLists[fieldName]
        : field?.options || [],

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
  },

  // ============================================================
  // ACTIONS
  // ============================================================
  actions: {
    // ------------------------------------------------------------
    // INIT & CONFIG
    // ------------------------------------------------------------
    async init() {
      if (this._initialized) return;
      this._initialized = true;
      this.endpoint = this.endpoint || $routeGetMeta?.('path') || 'purchase-master';

      await this.fetchConfig();
      await this.loadInitialMasterData();
      this._watchRoute();
    },

    async fetchConfig() {
      this.isProcessing = true;

      try {
        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getconfig',
        });

        const cfg = res?.body?.data?.config || {};

        Object.assign(this, {
          sidebarConfig: cfg.sidebar || this.sidebarConfig,
          gridConfig: cfg.grid || this.gridConfig,
          detailMenuConfig: { default: cfg.menu || this.detailMenuConfig.default },
          overviewConfig: cfg.overview || this.overviewConfig,
          detailAddConfig: cfg.addConfig || this.detailAddConfig,
          historyConfig: cfg.history || this.historyConfig,
          statusConfig: cfg.statusConfig || this.statusConfig,
          imagesConfig: cfg.images || this.imagesConfig,
          vahanConfig: cfg.vahanConfig || this.vahanConfig,
          evaluationConfig: cfg.evaluation || this.evaluationConfig,
        });

        // Cache defaults once
        if (!this._defaultConfigs) {
          this._defaultConfigs = JSON.parse(
            JSON.stringify({
              gridConfig: this.gridConfig,
              detailAddConfig: this.detailAddConfig,
              overviewConfig: this.overviewConfig,
              imagesConfig: this.imagesConfig,
              evaluationConfig: this.evaluationConfig,
              historyConfig: this.historyConfig,
              statusConfig: this.statusConfig,
              vahanConfig: this.vahanConfig,
              detailMenuConfig: this.detailMenuConfig,
            })
          );
        }
      } catch (err) {
        console.error('Error fetching config:', err);
      } finally {
        this.isProcessing = false;
      }
    },

    async loadInitialMasterData() {
      try {
        this.getYears(['mfg_year']);
        this.getMonths(['mfg_month']);

        // Master Config
        const masters = { getMakes: ['make', 'rs_make'] };

        // Common Masters
        const commonMasters = { getExecutivesByBranch: ['branchesWithExecutives'] };

        const collections = [
          ...Object.keys(masters),
          ...Object.keys(commonMasters),
        ].join(',');

        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action: 'getCollections',
          collections,
        });

        if (res?.body?.status && res.body.data) {
          // Normal Masters
          for (const [apiKey, fieldNames] of Object.entries(masters)) {
            const list = res.body.data[apiKey]?.list || [];
            if (Array.isArray(list)) {
              const opts = list.map((x) => ({
                value: String(x.value ?? x.id),
                label: x.label ?? x.name ?? String(x.value ?? x.id),
                group: x.group ?? null,
              }));

              fieldNames.forEach((fieldName) => {
                this.updateFieldOptions(fieldName, opts);
              });
            }
          }

          // Common Masters
          for (const [apiKey, fieldNames] of Object.entries(commonMasters)) {
            const list = res.body.data[apiKey]?.list || [];
            fieldNames.forEach((fieldName) => {
              this.commonMasterLists[fieldName] = list;
            });
          }

          // Process executives
          this.processBranchesWithExecutives('branchesWithExecutives', ['branch']);
        }

        await this.fetchMaster('getSources', 'source', { type: this.endpoint });
      } catch (err) {
        console.error('Error loading master data:', err);
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
        
        // Restore default configs only on mode change
        if (this._defaultConfigs) {
          const restore = (key) => JSON.parse(JSON.stringify(this._defaultConfigs[key]));
          Object.assign(this, {
            gridConfig: restore('gridConfig'),
            detailAddConfig: restore('detailAddConfig'),
            overviewConfig: restore('overviewConfig'),
            imagesConfig: restore('imagesConfig'),
            evaluationConfig: restore('evaluationConfig'),
            historyConfig: restore('historyConfig'),
            statusConfig: restore('statusConfig'),
            vahanConfig: restore('vahanConfig'),
            detailMenuConfig: restore('detailMenuConfig'),
          });
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
      const execs = this.getExecutivesByBranchId(branchId);
      this.updateFieldOptions("executive", execs);
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

    // Skip hidden fields
    if (isHidden === true || field.show === false) return;

    // Trim only string values
    let val = typeof value === 'string' ? value.trim() : value;

    // --- Handle special date fields (prevent invalid HTML datetime formats) ---
    if (['date', 'datetime-local'].includes(inputType)) {
      if (val === '0000-00-00' || val === '0000-00-00 00:00:00') {
        val = '';
        field.value = '';
      }
    }

    // --- Required check ---
    if (isRequired) {
      const isEmpty = val === null || val === undefined || val === '' || val === '0';

      if (['numeric', 'number', 'dropdown', 'dropdownIds', 'dynamic_dropdown'].includes(inputType)) {
        if (isEmpty) {
          valid = false;
          errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
          return;
        }
      } else {
        if (isEmpty) {
          valid = false;
          errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
          return;
        }
      }
    }

    // Skip non-required empty
    if (!val && !isRequired) return;

    // --- File validation ---
    if (inputType === 'file' && val) {
      const files = Array.isArray(val) ? val : [val];

      for (const file of files) {
        // Skip if not a File object (e.g. URL string)
        if (!file || typeof file !== 'object' || !file.name) continue;

        // 1. Check file size (<= 5MB)
        const maxFileSize = 5 * 1024 * 1024;
        if (file.size && file.size > maxFileSize) {
          valid = false;
          errors[fieldKey] = `File size must not exceed 5 MB`;
          return;
        }

        // 2. Check file extension
        if (validation?.mimeType?.length) {
          const allowed = validation.mimeType.map(t => t.toLowerCase());
          const ext = (file.name.split('.').pop() || '').toLowerCase();

          let isValidExt = false;
          if (allowed.includes('images') && ['jpg', 'jpeg', 'png'].includes(ext)) isValidExt = true;
          if (allowed.includes('pdf') && ext === 'pdf') isValidExt = true;

          if (!isValidExt) {
            valid = false;
            errors[fieldKey] = validation.errorMessageInvalid || `Invalid file format for ${fieldLabel || fieldKey}`;
            return;
          }
        }
      }
      return; // ✅ file validated
    }

    // --- Max length ---
    if (maxLength && typeof val === 'string' && val.length > maxLength) {
      valid = false;
      errors[fieldKey] = `Maximum length is ${maxLength} characters`;
      return;
    }

    // --- Regex validation ---
    if (validation?.validationPattern && typeof validation.validationPattern === 'string' && typeof val === 'string') {
      const pattern = validation.validationPattern.replace(/^\/|\/$/g, '');
      const regex = new RegExp(pattern);
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

  return { valid, errors };
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
      if (!key) return;
      this.masterLists[key] = Array.isArray(options) ? options : [];
    },

    // Map detail data into config fields (handles nested sections + conditionals)
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



    dynamic_variants(modelValue, field = '', configKey = '', makeId = null) {
      const variantFieldKey = field?.clearFields?.[0];
      if (!variantFieldKey) return;

      if (!modelValue) {
        this.updateFieldOptions(variantFieldKey, []);
        return;
      }

      // Resolve makeId if invalid
      if (!makeId || typeof makeId === 'object') {
        const config = configKey.split('.').reduce((obj, key) => obj?.[key], this);
        const makeFieldKey = field?.fieldKey?.replace(/_?model$/i, match => 
          match.startsWith('_') ? '_make' : 'make'
        ) || 'make';
        
        const makeField = this.getAllFields(config).find(f => f.fieldKey === makeFieldKey);
        const makeValue = makeField?.value?.value ?? makeField?.value;
        makeId = makeValue || this.detail?.[makeFieldKey];
      }

      // Find and populate variants
      const variants = (this.commonMasterLists.modelsWithVariants?.[makeId] || [])
        .find(m => m.value == modelValue)?.variants || [];

      this.updateFieldOptions(variantFieldKey, variants);
      if (variants.length) this.updateFieldValue(configKey, variantFieldKey, '');
    },
    
    async dynamic_subsources(source) {
      this.updateFieldOptions('source_sub', []);
      if (source) await this.fetchMaster('getSubSources', 'source_sub', {
        source
      });
    },

    async dynamic_substatus(status, field, configKey) {
      this.updateFieldOptions('sub_status', []);
      if (status) await this.fetchMaster('getSubStatus', 'sub_status', {
        type:'pm',
        status
      });
    },

    // async dynamic_colors(makeId, configKey) {
    //   if (!makeId) return;

    //   // Prevent duplicate calls - debounce using the last call timestamp
    //   const callKey = `${makeId}_${configKey}`;
    //   const now = Date.now();
      
    //   if (!this._colorCallTimestamps) {
    //     this._colorCallTimestamps = {};
    //   }
      
    //   // If called within last 100ms with same params, skip
    //   if (this._colorCallTimestamps[callKey] && (now - this._colorCallTimestamps[callKey]) < 100) {
    //     return;
    //   }
      
    //   this._colorCallTimestamps[callKey] = now;

    //   try {
    //     // Use the optimized single API call for both colors
    //     const response = await $http('POST', `${g.$base_url_api}/master-data`, {
    //       action: 'getColorsByMake',
    //       make_id: makeId
    //     });

    //     // Check response format
    //     if (response?.body?.status === 'ok' && response.body.data) {
    //       const { exterior_colors, interior_colors } = response.body.data;

    //       // Update exterior colors (stored in 'color' field)
    //       if (exterior_colors && Array.isArray(exterior_colors)) {
    //         const exteriorOptions = exterior_colors.map(c => ({
    //           value: String(c.value),
    //           label: c.label
    //         }));
        
    //         this.updateFieldOptions('color', exteriorOptions);
    //       } else {
    //         console.warn(' No exterior colors in response');
    //       }

    //       // Update interior colors
    //       if (interior_colors && Array.isArray(interior_colors)) {
    //         const interiorOptions = interior_colors.map(c => ({
    //           value: String(c.value),
    //           label: c.label
    //         }));
          
    //         this.updateFieldOptions('interior_color', interiorOptions);
    //       } else {
    //         console.warn(' No interior colors in response');
    //       }
    //     } else {
    //       console.warn(' Invalid response format:', response?.body);
    //     }
    //   } catch (err) {
    //     console.error(' Error fetching colors for make:', err);
    //   }
    // },



       // ---- VAHAN API METHODS ----
    async getVahanDetails(configKey, field, event = null, map = null, options = {}) {
      const regNum = field?.value?.trim();
      if (!regNum) {
        $toast('warning', 'Please enter registration number first');
        return null;
      }

      const targetConfig = this[configKey];
      if (!targetConfig || typeof targetConfig !== 'object') return null;

      try {
        this.isProcessing = true;
        this.vahanLoading = true;
        this.vahanError = null;

        // Car button should ONLY save to vahan_api_log, not to sellleads table
        // The sellleads table will be updated on form submit
        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action: 'getVahanDetails',
          reg_num: regNum
        });

        const vahanData = res?.body?.data;
        if (!vahanData) {
          const error = res.body?.msg || 'Vehicle details not found';
          this.vahanError = error;
          $toast('danger', error);
          return null;
        }

        // Store fetched data but DO NOT auto-populate form
        this.vahanData = { ...vahanData };
        this.vahanError = null;
        
        // Store field mapping context for later use when applying data
        this.vahanFieldMapping = { configKey, map };

        // Show confirmation to user to apply details manually
        $toast('info', 'Vahan data fetched. Click "Apply" to fill details.');

        return this.vahanData;
      } catch (err) {
        const error = err?.body?.msg || err.message || "Failed to fetch vehicle details";
        this.vahanError = error;
        $toast("danger", "Failed to fetch Vahan details");
        // Don't overwrite existing data on error
        return null;
      } finally {
        this.isProcessing = false;
        this.vahanLoading = false;
      }
    },

    /**
     * Apply Vahan data to form fields (called when user clicks Apply in popup)
     */
    applyVahanDataToForm() {
      if (!this.vahanData || Object.keys(this.vahanData).length === 0) {
        $toast('warning', 'No Vahan data available to apply');
        return;
      }

      const { configKey, map } = this.vahanFieldMapping || {};  
      if (!configKey) {
        console.error('configKey is missing!');
        return;
      }

      // Filter small fields for form population
      const smallFields = {
        first_name: this.vahanData.first_name || this.vahanData.ownerName || '',
        last_name: this.vahanData.last_name || '',
        reg_date: this.vahanData.reg_date || this.vahanData.registrationDate || '',
        chassis: this.vahanData.chassis || this.vahanData.chassisNumber || '',
        insurance_exp_date: this.vahanData.insurance_exp_date || this.vahanData.insuranceUpto || '',
        owners: this.vahanData.owners || this.vahanData.ownerSerialNumber || '',
        mfg_month: this.vahanData.mfg_month || '',
        mfg_year: this.vahanData.mfg_year || '',
        fuel: this.vahanData.fuel || this.vahanData.fuelDescription || '',
        financier: this.vahanData.financier || '',
        rc_address: this.vahanData.rc_address || this.vahanData.presentAddress || ''
      };

      // Populate form fields
      for (const [k, v] of Object.entries(smallFields)) {
        if (v !== null && v !== undefined && v !== '') {
          const targetKey = map?.[k] || k;
          this.updateFieldValue(configKey, targetKey, v);
        }
      }

      $toast('success', 'Vehicle details applied to form');
    },

    /**
     * Load Vahan details for Vahan tab - reads from database, no API call
     */
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
          this.vahanError = 'No Vahan data available. Please click the car icon in Lead Details to fetch vehicle information.';
          this.vahanData = {};
        }
      } catch (err) {
        this.vahanError = 'Failed to load Vahan details';
        this.vahanData = {};
      } finally {
        this.vahanLoading = false;
      }
    },


    // async dynamic_colors(){
    //    alert('hello colors');
    // },


    async dynamic_models(make, field = '', configKey = '') {
      // Reset dependent fields safely
      if (Array.isArray(field?.clearFields)) {
        field.clearFields.forEach(async (fieldName) => {
          this.updateFieldOptions(fieldName, []);
          this.updateFieldValue(configKey, fieldName, '');
        });
      }

      // Don't make API call if make is empty, 0, or "0"
      if (!make || make === 0 || make === '0') return;

      // Fetch colors for this make in parallel
      //this.dynamic_colors(make, configKey);

      // Ensure modelsWithVariants is an object keyed by makeId
      if (typeof this.commonMasterLists.modelsWithVariants !== 'object' || this.commonMasterLists.modelsWithVariants === null) {
        this.commonMasterLists.modelsWithVariants = {};
      }

      const cachedModelsForMake = this.commonMasterLists?.modelsWithVariants[make] || [];

      if (cachedModelsForMake.length) {
        // Models already cached
        const modelField = field?.clearFields?.[0];
        const variantField = field?.clearFields?.[1];

        const modelOptions = cachedModelsForMake.map(m => ({ value: m.value, label: m.label }));
        this.updateFieldOptions(modelField, modelOptions);
        this.updateFieldValue(configKey, modelField, '');

        // Clear variants when make changes - wait for user to select model
        if (variantField) {
          this.updateFieldOptions(variantField, []);
          this.updateFieldValue(configKey, variantField, '');
        }
        return; // skip API call
      }

      // ---------- FETCH FROM API ----------
      try {

        const res = await $http('POST', `${g.$base_url_api}/master-data`, {
          action: 'getmodelsbyMake',
          make
        });

        if (res?.body?.status === 'ok' && Array.isArray(res.body.data?.list) && res.body.data.list.length) {
          const newModels = res.body.data.list.map(m => ({ ...m, makeId: make }));

          // Store models keyed by makeId
          this.commonMasterLists.modelsWithVariants[make] = newModels;

          const modelField = field?.clearFields?.[0];
          const variantField = field?.clearFields?.[1];

          const modelOptions = newModels.map(m => ({ value: m.value, label: m.label }));
          this.updateFieldOptions(modelField, modelOptions);
          this.updateFieldValue(configKey, modelField, '');

          // Clear variants when make changes - wait for user to select model
          if (variantField) {
            this.updateFieldOptions(variantField, []);
            this.updateFieldValue(configKey, variantField, '');
          }
        }
      } catch (err) {
        // Error fetching models
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


    async dynamic_copy(configKey, field) {
        if (!field?.conditionalApply?.copyValue?.length) {
          return;
        }

        // Get config object
        const config = configKey.split('.').reduce((acc, key) => acc && acc[key], this);
        if (!config) {
          return;
        }

        const allFields = this.getAllFields(config);
        const changedFields = [];

        field.conditionalApply.copyValue.forEach(rule => {
          const targetField = allFields.find(f => f.fieldKey === rule.fieldKey);
          const sourceField = allFields.find(f => f.fieldKey === rule.copyFieldKey);

          if (!targetField || !sourceField) return;

          const valueToCopy = sourceField.value ?? "";

          // Update value only if different
          if (targetField.value !== valueToCopy) {
            this.updateFieldValue(configKey, targetField.fieldKey, valueToCopy);
            changedFields.push(targetField);
          }
        });

        //  Trigger conditional logic for all updated fields
        for (const updatedField of changedFields) {
          if (typeof this.applyConditionalLogic === 'function') {
            this.handleFieldEvent('onchange', configKey, updatedField.fieldKey, updatedField, updatedField);
          }
        }

        //  Feedback message
        if (changedFields.length) {
          $toast('success', 'Copied successfully!');
        } else {
          $toast('info', 'No new values to copy.');
        }
  },



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

      // Log FormData contents for debugging
      console.log('=== FormData Contents (saveDetail) ===');
      for (let [key, value] of formData.entries()) {
        console.log(`${key}:`, value);
      }
      console.log('======================================');

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
      const formData = this.getFormPayload(fields);
      formData.append("action", "exportdata");
      Object.entries(this.gridConfig.routeSlugs || {}).forEach(([k, v]) =>
        formData.append(k, v)
      );

      const res = await $http("POST", `${g.$base_url_api}/${this.endpoint}`, formData);
      const fileUrl = res?.body?.data?.file_url;

      if (res.status === 200 && fileUrl) {
        $downloadFile(fileUrl);
      } else {
        console.error("Export failed:", res);
        alert("Failed to export data");
      }
    } catch (err) {
      console.error("Error exporting data:", err);
      alert("Failed to export data");
    }
  },


    // ---------------- Add/Edit Detail ----------------

    async loadDependentDropdowns(configKey) {

        const config = configKey.split(".").reduce((acc, key) => acc && acc[key], this);

        const allFields = this.getAllFields(config);
        const fieldsWithHandlers = allFields.filter(field => 
            field.inputChange && this.detail[field.fieldKey] && 
            this.detail[field.fieldKey] !== 0 && this.detail[field.fieldKey] !== '0'
        );

        // Process in simple order - fields without dependencies first
        for (const field of fieldsWithHandlers) {
            const value = this.detail[field.fieldKey];
            field.value = value;
            
            try {
            await this.handleFieldEvent('change', configKey, field.fieldKey, field, null);
            
            // If this field clears other fields, handle those dependent fields
            if (field.clearFields && Array.isArray(field.clearFields)) {
                for (const dependentKey of field.clearFields) {
                if (this.detail[dependentKey]) {
                    const dependentField = allFields.find(f => f.fieldKey === dependentKey);
                    if (dependentField && dependentField.inputChange) {
                    // Small delay for parent dropdown to populate
                    await new Promise(resolve => setTimeout(resolve, 50));
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
          // Main detail object with all lead fields
          this.detail = {
            ...responseData.detail,  
            documents: responseData.documents || {},
            dent_map: responseData.dent_map || [],
            images: responseData.images || {},
            history: responseData.history || [],
            evaluation: responseData.evaluation_templates || {},
            vahanInfo: responseData.vahanInfo || {}
          };
                    
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
   
    // Refresh only evaluation data without resetting form state
    async refreshEvaluationData() {
      if (!this.detail?.id) return;
      try {
        const r = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getlead',
          id: this.detail.id
        });
        
        if (r?.status === 200 && r?.body?.data) {
          const freshData = r.body.data;
          
          // Update only evaluation, images, and history without touching form configs
          // NEW STRUCTURE: data is nested, extract from proper locations
          if (freshData.evaluation_templates) {
            this.detail.evaluation_templates = freshData.evaluation_templates;
          }
          if (freshData.images) {
            this.detail.images = freshData.images;
          }
          if (freshData.history) {
            this.detail.history = freshData.history;
          }
          if (freshData.dent_map) {
            this.detail.dent_map = freshData.dent_map;
          }
          
          // Update evaluation_done in detail and sync to statusConfig field
          if (freshData.detail?.evaluation_done !== undefined) {
            this.detail.evaluation_done = freshData.detail.evaluation_done;
            
            // Sync evaluation_done field in statusConfig using mapDetailToConfig logic
            if (this.statusConfig) {
              const allFields = this.getAllFields(this.statusConfig);
              const evaluationDoneField = allFields.find(f => f.fieldKey === 'evaluation_done');
              
              if (evaluationDoneField) {
                let fieldValue = freshData.detail.evaluation_done;
                
                // Update field value and trigger reactivity
                evaluationDoneField.defaultInputValue = fieldValue;
                evaluationDoneField.value = fieldValue;
                
                // Re-apply conditional logic to update any dependent fields
                this.applyConditionalLogic('statusConfig', 'evaluation_done', evaluationDoneField);
              }
            }
          }
        }
      } catch (err) {
        console.error('Failed to refresh evaluation data:', err);
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

    async deleteImage(key, key_id) {
      this.isProcessing = true;
      const id = this.detail?.id;

      try {
        if (!id) throw new Error("Lead ID is required for image upload.");
        if (!key_id) throw new Error("Image tag is required.");

        const formData = new FormData();
        formData.append("action", "update");
        formData.append("sub_action", "deleteimage");
        formData.append("id", id);
        formData.append("image_id", key_id);

        const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, formData);

        if (res?.status === 200 && res?.body?.status === 'ok') {
          $toast('success', res.body?.msg || `Image for ${key} deleted successfully!`);
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
        this.getDetail(id);
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
        if (this.detail?.id) {
          formData.append("id", this.detail.id);
          formData.append("sub_action", "updatestatus");
        }

        const res = await $http("POST", `${g.$base_url_api}/${this.endpoint}`, formData);
        const body = res?.body || {};

        // Log FormData contents for debugging
        console.log('=== FormData Contents ===');
        for (let [key, value] of formData.entries()) {
          console.log(`${key}:`, value);
        }
        console.log('========================');

        // debugger;

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


  async saveEvaluation({ template_id, checklist }) {
    if (!this.detail?.id) return {
        success: false,
        errors: {}
      };

    this.isProcessing = true;

    try {
      const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
        action: 'addevaluation',
        id: this.detail.id,
        template_id,
        checklist
      });

      if (res?.status === 200 && res?.body?.status === 'ok') {
        $toast('success', res.body.msg || 'Checklist updated successfully');
        // Only refresh evaluation data, don't reset form state
        await this.refreshEvaluationData();
        return true;
      }

      $toast('danger', res?.body?.msg || 'Failed to update checklist');
      return false;
    } catch (err) {
      console.error('saveChecklist error:', err);
      $toast('danger', err?.message || 'Failed to update checklist');
      return false;
    } finally {
      this.isProcessing = false;
    }
  }



  },


});
const store = useStorePurchaseMaster();
store.init();