
export const Add = {
  name: "Add",
  props: { 
    store: { type: Object, required: true },
  },
  components: { },
  data() {
    return {
      componentMap: {},      // dynamically loaded components
      componentsLoaded: false,
      myConfig: 'detailAddConfig'
    };
  },

async created() {
  const [{ default: vaahan }] = await $importComponent(['/pages/views/pm/vaahan.js']);
  this.componentMap['vaahan'] =  Vue.markRaw(vaahan);
  this.componentsLoaded = true;
},

computed: {
    isProcessing: vm => vm.store.isProcessing,
    config: vm => vm.store.getDetailAddConfig || [],
    hasSections() { return this.config?.length > 0; },
    getErrors: vm => field => {
      const errorValue = vm.store.errors?.detailAddConfig?.[field];
      // Convert string error to array for consistent display
      return errorValue ? (Array.isArray(errorValue) ? errorValue : [errorValue]) : [];
    },
    getFieldOptions: vm => field => {
      if (typeof vm.store?.getOptionsForField === 'function') {
        const options = vm.store.getOptionsForField(field.fieldKey || field.key, field);
        if (options?.length) return options;
      }
      return field.fieldOptionIds || field.fieldOptions || [];
    },
    getFieldsWithConditionals() {
      return section => {
        const result = [];
        section.fields.forEach(parentField => {
          result.push(parentField);

          if (parentField.conditionalFields && parentField.value != null) {
            const conditional = parentField.conditionalFields[String(parentField.value)];
            if (conditional?.length) {
              conditional.forEach(cf => {
                if (!result.find(f => f.fieldKey === cf.fieldKey)) result.push(cf);
              });
            }
          }
        });
        return result;
      };
    }
  },

  methods: {
    getDynamicComponent(fieldKey) {
      return this.componentMap[fieldKey] || null;
    },
    getFieldEvents(configKey, fieldKey, field) {
      if (!field) return {};
      const events = {};
      const type = field.inputType || field.type;

      if (['text','alphanumeric','numeric','number','calender','date', 'pin_code_search'].includes(type)) {
        events.input = e => {
          const val = this.sanitizeInput(field, e.target.value);
          field.value = val;
          this.store?.handleFieldEvent?.("onchange", configKey, fieldKey, field, e);
        };
      } else if (['dropdownIds','dynamic_dropdown','select', 'searchbleDropdownIds'].includes(type)) {
        events.change = e => {
          field.value = e.target.value.toString();
          this.store?.handleFieldEvent?.("onchange", configKey, fieldKey, field, e);
        };
      }

      return events;
    },
    sanitizeInput(field, value) {
      if (!value) return '';
      const type = field.inputType || field.type;
      if (['numeric','number', 'pin_code_search'].includes(type))
        return field.allowDecimal ? value.replace(/[^0-9.]/g,'') : value.replace(/[^0-9]/g,'');
      if (type === 'alphanumeric') value = value.replace(/[^a-zA-Z0-9@!*\-_\+.,\s]/g, '');
      if (['text','alphanumeric'].includes(type) && field.isCaps) value = value.toUpperCase();
      return value;
    },
    getDateAttributes(field, calendarType) {
      return $setDateAttributes(field, calendarType);     
    },
    getFileAccept(patterns) {
      return $setFileAccept(patterns);     
    },
    handleFileChange(event, field) {
      const files = event.target.files;
      field.val = files?.length === 1 ? files[0] : [...files] || null;
    },
    handleAddonAction(configKey, action, field) {
      if (action && typeof this.store?.[action] === 'function') {
        try {
          this.store[action](configKey, field);
        } catch (err) {
          console.error(`Error executing action "${action}":`, err);
        }
      } else {
        console.warn(`Action "${action}" is not a valid function in store.`);
      }
    },
    async handleSubmit() {
      // console.log('=== handleSubmit called ===');
      if (!this.store) return;
      const result = await this.store.saveDetail();
      // console.log('saveDetail returned:', result);
      // console.log('Errors after save:', this.store.errors.detailAddConfig);
      if (!result.success) return; // Stop execution if validation fails
    },
    
    toggleCheckboxValue(field, value) {
      const separator = '|'; 
      let current = (field.value || '').split(separator).filter(v => v); // ensure array without empties
      if (current.includes(value)) {
        current = current.filter(v => v !== value);
      } else {
        current.push(value);
      }
      field.value = current.join(separator); // store using configured separator
      this.store?.handleFieldEvent?.('onchange', this.myConfig, field.fieldKey, field, { target: { value: field.value } });
    }
  },


  template: /*html*/ `
<div class="container p-0 m-0 ps-2">
  <form @submit.prevent="handleSubmit" autocomplete="off" spellcheck="false">
    <div v-for="formBlock in config" :key="formBlock.fieldLabel">
      <div v-if="formBlock.formType === 'expandable_form'">
        <div v-for="section in formBlock.sections" :key="section.sectionId" class="card mb-3 border-light bg-white shadow-sm">
          <div class="card-header bg-prime text-white py-2 shadow-sm d-flex justify-content-between align-items-center border-bottom-0"
               role="button"
               data-bs-toggle="collapse"
               :data-bs-target="'#' + section.sectionId">
            <h6 class="mb-0 fw-semibold">{{ section.sectionTitle }}</h6>
            <i class="bi text-dark" :class="section.isExpandedByDefault ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </div>

          <div :id="section.sectionId" class="collapse" :class="{ show: section.isExpandedByDefault }">
            <div class="card-body py-2 pb-3">
              <div class="row g-3">
                <template v-for="field in getFieldsWithConditionals(section)" :key="field.fieldKey">

                  <!-- Field column -->
                  <div class="col-md-3" :class="{ 'd-none': field.show === false || field.isHidden === true }">
                    <label :for="field.fieldKey" class="form-label small fw-semibold">{{ field.fieldLabel }} <span v-if="field.isRequired" class="text-danger small">*</span></label>
                    <span v-if="field.tooltip" class="ms-1 text-muted" :title="field.tooltip" data-bs-toggle="tooltip" data-bs-placement="top" style="cursor: help;">
                      <i class="bi bi-info-circle"></i>
                    </span>

                    <div :class="{ 'input-group': field.addons?.length }">
                      <!-- Text / Numeric / Alphanumeric -->
                      <input v-if="['alphanumeric','numeric','number','text','pin_code_search'].includes(field.inputType || field.type)"
                        type="text"
                        :id="field.fieldKey"
                        class="form-control form-control-sm shadow-none py-0 px-2"
                        :class="{ 'is-invalid': getErrors(field.fieldKey).length }"
                        :placeholder="field.fieldHolder || field.placeholder || ''"
                        v-model="field.value"
                        :maxlength="field.maxLength"
                        :readonly="field.isReadOnly || field.readonly"
                        :required="field.isRequired || field.required"
                        v-on="getFieldEvents(myConfig, field.fieldKey || field.key, field)"
                      />
                      <!-- Radio Group -->
                      <div v-else-if="['radio','radio_group'].includes(field.inputType || field.type)" class="form-check-group">
                        <div v-for="opt in getFieldOptions(field)" :key="opt.value" class="form-check">
                          <input class="form-check-input" type="radio"
                                :id="field.fieldKey + '_' + opt.value"
                                :name="field.fieldKey"
                                :value="opt.value"
                                v-model="field.value"
                                :disabled="field?.isReadOnly || field?.isDisabled"
                                @change="store?.handleFieldEvent?.('onchange', myConfig, field.fieldKey, field, $event)" />
                          <label class="form-check-label small" :for="field.fieldKey + '_' + opt.value">
                            {{ opt.label }}
                          </label>
                        </div>
                      </div>

                      <!-- Dropdown / Select -->
                      <SelectSearch
                        v-else-if="['dropdownIds','dynamic_dropdown','select','searchbleDropdownIds'].includes(field.inputType || field.type)"
                        v-model="field.value"
                        :id="field.fieldKey"
                        :options="getFieldOptions(field)"
                        :placeholder="field.fieldLabel"
                        :searchable="field?.isSearch"
                        :disabled="field?.isReadOnly || field?.isDisabled"
                        :showGrouping="field?.isGroup"
                        option-label="label"
                        :required="field?.isRequired"
                        option-value="value"
                        :visibleGroups="field?.visibleGroups || []"
                        :disabled-options="field.disabledOptions?.map(v => v.toString())"
                        @change="(val) => getFieldEvents(myConfig, field.fieldKey || field.key, field).change?.({ target: { value: val } })"
                      />
                      <!-- Date / Calendar -->
                      <input v-else-if="['calender','date','calender_time'].includes(field.inputType || field.type)"
                        :type="['calender_time'].includes(field.inputType || field.type) ? 'datetime-local' : 'date'"
                        :id="field.fieldKey"
                        class="form-control form-control-sm shadow-none py-1 px-2"
                        :class="{ 'is-invalid': getErrors(field.fieldKey).length }"
                        v-model="field.value"
                        :readonly="field.isReadOnly || field.readonly"
                        :required="field.isRequired || field.required"
                        v-bind="getDateAttributes(field, field?.calenderType)"
                      />

                     <div v-else-if="['checkbox_group'].includes(field.inputType || field.type)" class="d-flex flex-wrap gap-2">
                        <div v-for="opt in getFieldOptions(field)" :key="opt.value" class="form-check me-2">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            :id="field.fieldKey + '_' + opt.value"
                            :value="opt.value.toString()"
                            :checked="(field.value || '').split('|').includes(opt.value.toString())"
                            :disabled="field?.isReadOnly || field?.isDisabled"
                            @change="toggleCheckboxValue(field, opt.value.toString())"
                          />
                          <label class="form-check-label small" :for="field.fieldKey + '_' + opt.value">
                            {{ opt.label }}
                          </label>
                        </div>
                      </div>

                      <!-- File -->
                      <input v-else-if="field.inputType === 'file'"
                        :id="field.fieldKey"
                        type="file"
                        class="form-control form-control-sm shadow-none py-1 px-2"
                        :class="{ 'is-invalid': getErrors(field.fieldKey).length }"
                        :multiple="field.multiple || false"
                        :accept="getFileAccept(field.validation?.validationPattern || [])"
                        @change="handleFileChange($event, field)" />

                      <!-- Addons -->
                      <template v-if="Array.isArray(field.addons) && field.addons.length && componentsLoaded">
                        <template v-for="addon in field.addons" :key="addon?.fieldKey || Math.random()">
      
                          <!-- Component-type addon -->
                          <component
                            v-if="addon.inputType === 'component' && getDynamicComponent(addon.fieldKey)"
                            :is="getDynamicComponent(addon.fieldKey)"
                            :store="store"
                            :config="myConfig"
                            :field="field"
                          />

                          <!-- Action-type addon -->
                          <button
                            v-else-if="addon.inputType === 'action'"
                            type="button"
                            class="btn btn-sm rounded-1 btn-outline-secondary ms-1 py-1 fs-5"
                            :disabled="addon.isDisabled"
                            :title="addon.tooltip"
                            @click="handleAddonAction(myConfig, addon.inputChange, addon)"
                          >
                            <i :class="'bi bi-' + (addon.inputIcon || 'car-front')"></i>
                            {{ addon.fieldLabel }}
                          </button>

                        </template>
                      </template>
                    </div>

                    <!-- Errors -->
                    <div v-if="getErrors(field.fieldKey).length" class="invalid-feedback d-block" style="font-size: 0.7rem;">
                      <span v-for="(err, idx) in getErrors(field.fieldKey)" :key="idx">{{ err }}</span>
                    </div>
                  </div>
                  <div v-if="field.isBr" class="row"></div>                  
                </template>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Submit (hidden when purchased) -->
    <div class="text-end" v-if="hasSections">
      <button type="submit" class="btn btn-dark btn-sm px-4" :disabled="isProcessing" @click.prevent="handleSubmit">
        <i class="bi bi-save me-1"></i>
        <span v-if="isProcessing">Saving...</span>
        <span v-else>Submit</span>
      </button>
    </div>

  </form>
</div>
`
};
