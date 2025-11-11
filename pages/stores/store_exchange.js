const { defineStore } = Pinia;

export const useStoreExchange = defineStore('exchange', {
  // ---------------- STATE ----------------
  state: () => ({
    isProcessing: false,
    _initialized: false,
    endpoint: null,
    errors: { gridConfig: {}, detailAddConfig: {}, statusConfig: {} },
    masterLists: {
      modelsWithVariants: [],
      make: [],
      model: [],
      variant: [],
      source: [],
      source_sub: [],
      mfg_year: [],
      mfg_month: [],
      state: [],
      city: [],
      color: [],
    },
    menuConfig: { title: 'Exchange', subtitle: '' },
    sidebarConfig: { showSidebar: false, sidebarItems: [] },
    gridConfig: { searchConfig: {}, list: [], pagination: {}, routeSlugs: {} },
    detail: {},
    detailAddConfig: {},
    overviewConfig: {},
    historyConfig: {},
    statusConfig: {},
    savedPagination: null,
  }),

    // ---------------- GETTERS ----------------
    getters: {
        routePath: (s) => (s.endpoint ? `/${s.endpoint}` : ''),
        getMenuConfig: (s) => s.menuConfig,
        getSidebarConfig: (s) => s.sidebarConfig,
        getGridConfig: (s) => s.gridConfig,
        getDetails: (s) => s.detail || {},
        getDetailAddConfig: (s) => s.detailAddConfig?.fields || [],
        getOverviewConfig: (s) => s.overviewConfig || {},
        getHistoryConfig: (s) => s.historyConfig || {},
        getStatusConfig: (s) => s.statusConfig?.fields || {},
        getOptionsForField: (s) => (fieldName, field) => s.masterLists?.[fieldName]?.length ? s.masterLists[fieldName] : field?.options || []
    },
  // ---------------- ACTIONS ----------------
  actions: {

    // ---------------- INIT & CONFIG ----------------
    async init() {
      if (this._initialized) return;
      this._initialized = true;
      this.endpoint = this.endpoint || $routeGetMeta?.('path') || 'exchange';
      await this.fetchConfig();
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
          overviewConfig: cfg.overview || this.overviewConfig,
          detailAddConfig: cfg.addConfig || this.detailAddConfig,
          historyConfig: cfg.history || this.historyConfig,
          statusConfig: cfg.statusConfig || this.statusConfig,
        });
      } catch (err) {
        console.error('Error fetching config:', err);
      } finally {
        this.isProcessing = false;
      }
    },

    // ---------------- ROUTE HANDLING ----------------
    _watchRoute() {
      router.afterEach((to) => {
        if (to.path.includes(this.endpoint)) this.updateFromRoute(to);
      });
      const current = router.currentRoute.value;
      if (current.path.includes(this.endpoint)) this.updateFromRoute(current);
    },



    async updateFromRoute() {
      const [slug1 = '', slug2 = '', slug3 = ''] = ['slug1', 'slug2', 'slug3'].map(k => $routeGetParam(k));
      this.gridConfig.routeSlugs = {status: slug1, sub_status: slug2};
      if (slug1 === 'detail') {
        if (slug2 && !slug3) {
          if (/^[A-Za-z0-9+/]+=*$/.test(slug2)) {
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



    // ----------------- Helper -----------------
    _updateMenuConfig(mode = 'grid', data = null) {
      if (mode === 'grid') {
        const statusSlug = this.gridConfig.routeSlugs?.status || '';
        const subStatusSlug = this.gridConfig.routeSlugs?.sub_status || '';
        const getLabel = (slug) => {
          if (!slug || slug === 'all') return 'All';
          const parent = this.sidebarConfig?.sidebarItems?.find(
            i => i.key === slug || i.slug === slug
          );
          if (parent?.label) return parent.label;
                    for (const i of this.sidebarConfig?.sidebarItems || []) {
                        const child = i.sub?.find(c => c.key === slug || c.slug === slug);
                        if (child?.label) return child.label;
                    }
                    return '';
                };
                const statusLabel = getLabel(statusSlug);
                const subStatusLabel = getLabel(subStatusSlug);
                this.menuConfig.subtitle = subStatusLabel ? `${statusLabel}` : statusLabel;
            }

            if (mode === 'detail') {
                let tab;
                if (this.detail?.id) {
                    tab = data || 'overview';
                } else {
                    tab = 'add';
                }

                // Use formatted ID for display in subtitle
                const displayId = this.detail?.formatted_id || '';
                this.menuConfig.subtitle = `${tab[0].toUpperCase() + tab.slice(1)}${displayId ? ` ${displayId}` : ''}`;
            }
        },


    // ---------------- CONFIG PREPARATION ----------------
    async _prepareConfig(type, slug2 = '', slug3 = '') {
      if (this._currentMode !== type) {
        this.sidebarConfig = {
          showSidebar: false,
          sidebarItems: []
        };
        this._currentMode = type;
      }
      this._updateMenuConfig('grid');

      if (type === 'grid') {
        this.detail = {};
        this._resetDetailAddConfig('detailAddConfig');
        this._resetDetailAddConfig('statusConfig');
        await this.getList();
        this._updateMenuConfig('grid');
      } else if (type === 'detail') {
        // IMPORTANT: Only fetch detail if ID changes or is not yet loaded
        if (this.detail?.id !== slug2 || !this.detail?.id) {
          this.detail = {};
          this._resetDetailAddConfig('detailAddConfig'); // Reset before loading new detail
          this._resetDetailAddConfig('statusConfig');
          await this.getDetail(slug2); // This will also handle sidebar and initial conditionals
        }
        // Always prepare sidebar and update menu config based on current state and slug3
        this._prepareSidebar({mode: 'detail', data: slug2});
        this._updateMenuConfig('detail', slug3);
      }
    },

    _prepareSidebar({mode = 'grid', data = null} = {}) {
      const sidebarConfig = {
        showSidebar: false,
        sidebarItems: []
      };

      if (mode === 'grid') {
        if (!data || Object.keys(data).length === 0) return sidebarConfig;

        const buildItems = (menuObj) =>
          Object.entries(menuObj).map(([key, value]) => {
            const mainSlug = value.sub ? Object.keys(value.sub)[0] : key;
            return {
              key,
              label: value.label || 'Untitled',
              count: value.count || 0,
              href: mainSlug === 'all' || !mainSlug ? `/${this.endpoint}` : `/${this.endpoint}/${mainSlug}`,
              sub: value.sub ? buildItems(value.sub) : [],
            };
          });

        sidebarConfig.showSidebar = true;
        sidebarConfig.sidebarItems = buildItems(data);
      }

            if (mode === 'detail') {
                // Use encrypted id for routing
                const id = this.detail?.id || data;
                const base = id ? `/${this.endpoint}/detail/${id}` : null;
                const items = ['overview', 'status'];

        sidebarConfig.showSidebar = true;
        sidebarConfig.sidebarItems = items.map((k) => ({
          key: k,
          label: k[0].toUpperCase() + k.slice(1),
          href: base ? `${base}/${k}` : null,
          disabled: !base,
        }));
      }
      this.sidebarConfig = sidebarConfig;
    },

    processBranchesWithExecutives(branchData) {
      if (!Array.isArray(branchData)) return;

      this.masterLists.branchesWithExecutives = branchData;

      // branch options
      const branchOpts = branchData.map((b) => ({
        value: String(b.branch_id),
        label: b.branch_name,
      }));
      this.updateFieldOptions("branch", branchOpts);
    },

    dynamic_executies(branchId) {
      // get executives for this branch
      const execs = this.getExecutivesByBranchId(branchId);
      // log for debug
      console.table(execs);
      // update dropdown options for executive field
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
        console.error(`Error fetching ${listKey} master data:`, e);
      }
    },

    // ---------------- HELPERS ----------------
    getYears(startYear = 2000) {
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
      this.updateFieldOptions('mfg_year', years);
    },

    getMonths() {
      const months = [];
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
      console.table(config);

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

        // --- Required check ---
        if (isRequired && (val === null || val === undefined || val === '')) {
          valid = false;
          errors[fieldKey] = validation?.errorMessageRequired || `${fieldLabel || fieldKey} is required`;
          return;
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

            // 2. Check allowed extensions if validationPattern exists
            if (validation?.validationPattern?.length) {
              const allowed = validation.validationPattern.map(t => t.toLowerCase());
              const ext = file.name.split('.').pop().toLowerCase();

              let isValidExt = false;
              if (allowed.includes('images') && ['jpg','jpeg','png','gif'].includes(ext)) {
                isValidExt = true;
              }
              if (allowed.includes('pdf') && ext === 'pdf') {
                isValidExt = true;
              }
              if (allowed.includes('doc') && ['doc','docx'].includes(ext)) {
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
              formData.append(`${f.fieldKey}[${idx}]`, file);
            });
          } else if (val instanceof File) {
            formData.append(f.fieldKey, val);
          }
          return; // skip to next field
        }
        // Handle normal fields
        if (typeof val === 'string') val = val.trim();
        formData.append(f.fieldKey, val || '');
      });

      return formData;
    },


    updateFieldOptions(key, options) {
      this.masterLists[key] = Array.isArray(options) ? options : [];
    },


    // Map detail data into config fields (handles nested sections + conditionals)
    mapDetailToConfig(configKey, detail) {
      // Pass the detail object to getAllFields to correctly identify conditional fields

      const config = configKey.split('.').reduce((obj, key) => obj && obj[key], this);

      const allFields = this.getAllFields(config,detail);

      console.table(allFields); // You should now see conditional fields here

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
        // Handle clearFields separately
        if (applyType === 'clearFields' && Array.isArray(rules)) {
            rules.forEach(fKey => {
                this.updateFieldValue(configPath, fKey, "");
            });
            return;
        }
        
        // Special handling for isOptionsDisabled - collect all rules first
        if (applyType === 'isOptionsDisabled') {
            // Group rules by target fieldKey
            const rulesByField = {};
            rules.forEach(rule => {
                if (!rulesByField[rule.fieldKey]) {
                    rulesByField[rule.fieldKey] = [];
                }
                rulesByField[rule.fieldKey].push(rule);
            });
            
            // Process each target field
            Object.entries(rulesByField).forEach(([targetFieldKey, fieldRules]) => {
                const targetField = allFields.find(f => f.fieldKey === targetFieldKey);
                if (!targetField) return;
                
                // Reset disabled options for this field
                targetField.disabledOptions = [];
                
                const currentValue = String(triggerField.defaultInputValue ?? "");
                
                // Apply all matching rules
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
                    
                case 'setValue':
                    if (conditionMet && rule.value !== undefined) {
                        this.updateFieldValue(configPath, rule.fieldKey, rule.value);
                    }
                    break;
            }
        });
    });
},


    async handleFieldEvent(eventType, configKey, fieldName, field, e) {
      const newVal = field?.value ?? "";
      if (field.conditionalApply) {
          this.applyConditionalLogic(configKey, fieldName, field);
      }
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
  this.errors.detailAddConfig = {};
  const detailConfig = this.detailAddConfig;
  const validationResult = this.validateInputs(detailConfig);

  if (!validationResult.valid) {
    this.errors.detailAddConfig = validationResult.errors;
    return { success: false, errors: validationResult.errors };
  }

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
        

    async getDetail(encryptedId) {
      if (!encryptedId) return;
      this.isProcessing = true;
      try {
        const r = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
          action: 'getdetail',
          id: encryptedId
        });

        if (r?.status === 200 && r?.body?.data) {
          // Store detail with encrypted ID as main ID
          this.detail = r.body.data?.detail || {};
            await this.loadDependentDropdowns('detailAddConfig');
            await this.loadDependentDropdowns('statusConfig');

          // Set detail values into configs
          if (this.detailAddConfig) {
            this.mapDetailToConfig('detailAddConfig', this.detail);
          }
          if (this.statusConfig) {
            this.mapDetailToConfig('statusConfig', this.detail);
          }

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
    async updateNewCarData(payload = {}){
      this.errors.detailAddConfig = {};
        const {id,new_chassis,benefit_flag,bonus_price} = payload;
        if (!id) return false;
        try {
              const modBenFlag=benefit_flag=='Yes'? 1:2;
              const modBonusPrice=modBenFlag==1?bonus_price:0;
              const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
                  action: 'updateNewCarData',
                  id: id || 0,
                  new_chassis: new_chassis || '',
                  benefit_flag : modBenFlag || '',
                  bonus_price: modBonusPrice || ''
              });
        
            if (res?.status === 200) {
              $toast('success', res.body.message || 'New Car VIN & Bounus price added successfully');
              return { success:true, msg:res.body.msg || "New Car VIN & Bounus price added successfully" };
            }
          } catch (err) {
            if(err.body.status=='fail'){
              $toast("danger", err.body.msg || "Failed to add new car detail");
              return { success: false, val_errors: err.body.errors };
            }
            
          } finally {
            this.isProcessing = false;
          }
      
    }
  },
});
const store = useStoreExchange();
store.init();