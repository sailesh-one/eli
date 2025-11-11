const { defineStore } = Pinia;

export const useStoreLocations = defineStore('locations', {
    state: () => ({
        isProcessing: false,
        _initialized: false,
        endpoint: null,
        masterLists: {},
        commonMasterLists: {
            statewithCities: {},
        },
        errors: { gridConfig: {} },
        sidebarConfig: { showSidebar: false, sidebarItems: [] },
        menuConfig: { title: 'Locations', subtitle: '' },
        gridConfig: { searchConfig: {}, list: [], pagination: {}, routeSlugs: {} },
        savedPagination: null,
    }),
    getters: {
        routePath: (s) => (s.endpoint ? `/${s.endpoint}` : ''),
        getMenuConfig: (s) => s.menuConfig,
        getSidebarConfig: (s) => s.sidebarConfig,
        getGridConfig: (s) => s.gridConfig,
        getOptionsForField: (s) => (fieldName, field) => s.masterLists?.[fieldName]?.length ? s.masterLists[fieldName] : field?.options || [],
    },
    actions: {
        async init() {
            $log('Zero');
            if (this._initialized) return;
            this._initialized = true;
            this.endpoint = this.endpoint || $routeGetMeta?.('path') || 'locations';
            $log('Initializing store for endpoint:', this.endpoint);
            await this.fetchConfig();
            await this.loadInitialMasterData();
           await this.getList();
        },

        async fetchConfig() {
            $log('one');
            this.isProcessing = true;
            try {
                const res = await $http('POST', `${g.$base_url_api}/admin/${this.endpoint}`, {
                action: 'getconfig'
                });
                const cfg = res?.body?.data?.config || {};

                Object.assign(this, {
                    sidebarConfig: cfg.sidebar || this.sidebarConfig,
                    gridConfig: cfg.grid || this.gridConfig
                });
                $log('Grid config loaded:', this.gridConfig);
            } catch (err) {
                console.error('Error fetching config:', err);
            } finally {
                this.isProcessing = false;
            }
        },

        async loadInitialMasterData() {
            try {

                // ---------------- MASTER CONFIG ----------------
                const masters = {
                    getMasterStates: ['cw_state'],
                };

                // Combine both for one API call
                const collections = [
                    ...Object.keys(masters),
                ].join(",");

                $log('Collections to fetch:', collections);

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


                }
                $log('Initial master data loaded:', this.masterLists.states);
            } catch (err) {
                // Error loading master data
            }
        },

        resetGrid() {
            Object.assign(this.gridConfig, {
                list: [],
                pagination: {}
            });
        },

        resetGridFilters() {
            Object.values(this.gridConfig.search || {}).forEach(f => (f.val = ''));
        },

        async dynamic_cities(state, field = '', configKey = '') {
            // Reset dependent fields safely
            // if (Array.isArray(field?.clearFields)) {
            //     field.clearFields.forEach(async (fieldName) => {
            //     this.updateFieldOptions(fieldName, []);
            //     this.updateFieldValue(configKey, fieldName, '');
            //     });
            // }

            // Don't make API call if state is empty, 0, or "0"
            if (!state || state === 0 || state === '0') return;

            // ---------- FETCH FROM API ----------
            try {

                const res = await $http('POST', `${g.$base_url_api}/master-data`, {
                    action: 'getcitiesbystate',
                    cw_state: state,
                });

                if (res?.body?.status === 'ok' && Array.isArray(res.body.data?.list) && res.body.data.list.length) {
                    const newCities = res.body.data.list.map(m => ({ ...m, cw_state: state }));

                    // Store models keyed by state
                    this.commonMasterLists.statewithCities[state] = newCities;

                    const modelField = field?.clearFields?.[0];

                    const cityOptions = newCities.map(m => ({ value: m.value, label: m.label }));
                    this.updateFieldOptions(modelField, cityOptions);
                    // this.updateFieldValue(configKey, modelField, '');

                }
            } catch (err) {
                // Error fetching models
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

        getFormPayload(config) {
            const formData = new FormData();
            const fieldsToExtract = this.getAllFields(config);

            fieldsToExtract.forEach(f => {
                let val = f.value;

                // Handle non-file fields
                if (typeof val === 'string') val = val.trim();
                
                // Send current value or empty string (backend handles empty → NULL conversion)
                const valueToSend = (val === undefined || val === null || val === '') ? '' : val;
                formData.append(f.fieldKey, valueToSend);
            });

            return formData;
        },

        async getList(payload = {}, remember = false) {
            this.errors.gridConfig = {};

            const searchConfigFields = this.gridConfig?.searchConfig;
            // const validationResult = this.validateInputs(searchConfigFields);
            // if (!validationResult.valid) {
            //     this.errors.gridConfig = validationResult.errors;
            //     return;
            // }

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
                $log('formData for getList:', formData);

                const res = await $http("POST", `${g.$base_url_api}/admin/${this.endpoint}`, formData);
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
                }
            } catch (err) {
                // const msg = err?.body?.message || err?.message || "";
                console.error('Error fetching list:', err);
            } finally {
                this.isProcessing = false;
            }
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
                });
            };
            collect(rootFields);
            return result;
        },

        updateFieldOptions(key, options) {
            if (!key) return;
            this.masterLists[key] = Array.isArray(options) ? options : [];
            $log(`Field options updated for ${key}`, this.masterLists[key]);
        },

        async handleFieldEvent(eventType, configKey, fieldName, field, e) {
          const newVal = field?.value ?? "";

          const config = (configKey || "").split(".")[0] || null;
          if (config && this.errors?.[config]?.[fieldName]) { delete this.errors[config][fieldName]; }

          // Handle fields that depend on this field via inputMethod + filterBy
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

          if (field.inputChange) {
            // clear dependent fields if any
            // if (Array.isArray(field.clearFields)) {
            //   field.clearFields.forEach(cf => {
            //     this.updateFieldValue(configKey, cf, "");
            //   });
            // }
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

        
    },
});

const store = useStoreLocations();
store.init();