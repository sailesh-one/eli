export const Add = {
  name: "Add",
  props: { 
    store: { type: Object, required: true },
    isReadOnly: { type: Boolean, required: false, default: false },
  },
  components: { },
  data() {
    return {
      componentMap: {},
      componentsLoaded: false,
    };
  },

async created() {
  const [{ default: vaahan }] = await $importComponent(['/pages/views/pm/vaahan.js']);
  this.componentMap['vaahan'] =  Vue.markRaw(vaahan);
  this.componentsLoaded = true;
},

computed: {
    isProcessing: vm => vm.store.isProcessing,
    currentSlug() {
      const slug = (typeof $routeGetParam === 'function') ? $routeGetParam('slug3') : null;
      return slug || null;
    },

    myConfig() {
      const cfg = this.store?.getConfigName;
      if (typeof cfg === 'function') return cfg() || 'detailAddConfig';
      return cfg || 'detailAddConfig';
    },

    config() {
      const slug = this.currentSlug;
      if (slug) {
        const formattedSlug = slug
          .split('-')
          .map(word => word.charAt(0).toUpperCase() + word.slice(1))
          .join('');
        const key = `get${formattedSlug}AddConfig`;
        return this.store?.[key] || this.store?.getDetailAddConfig || {};
      }
      return this.store?.getDetailAddConfig || {};
    },

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

      if (['text','alphanumeric','numeric','number','calender','date', 'phone', 'email', 'pin_code_search'].includes(type)) {
        events.input = e => {
          const val = this.sanitizeInput(field, e.target.value);
          field.value = val;
          e.target.value = val; // UPDATE: Force the input to show sanitized value
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
      // For numeric types, only allow numbers (and decimal if allowed)
      if (['numeric','number', 'phone', 'pin_code_search'].includes(type)) {
        if (field.allowDecimal) {
          const parts = value.split('.');
          if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
          }
          return value.replace(/[^0-9.]/g, '');
        }
        return value.replace(/[^0-9]/g, '');
      }
      
      if (type === 'alphanumeric') {
        value = value.replace(/[^a-zA-Z0-9@!*\-_\+.,\s]/g, '');
      }
      if (['text','alphanumeric', 'email'].includes(type) && field.isCaps) {
        value = value.toUpperCase();
      }
      
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
    },

    handleCurrencyInput(field, event) {
      const isNumericFormat = (field.inputType) === 'numeric_format';
      if (isNumericFormat) {
        const rawValue = event.target.value.replace(/[^0-9]/g, '');
        
        // Check maxLength on raw numeric value (without commas)
        if (field.maxLength && rawValue.length > field.maxLength) {
          // Revert to previous value
          event.target.value = field.value ? $formattedNumber()(parseFloat(field.value)) : '';
          return; // Don't update if exceeds maxLength
        }
        
        field.value = rawValue;
        if (rawValue) {
          const num = parseFloat(rawValue);
          event.target.value = $formattedNumber()(num);
        } else {
          event.target.value = '';
        }
        this.store?.handleFieldEvent?.("onchange", this.myConfig, field.fieldKey || field.key, field, event);
      }
    },

    formatCurrency(value) {
      if (!value) return '';
      const num = parseFloat(value);
      if (isNaN(num)) return value;
      return $formattedNumber()(num);
    }
  },


  template: /*html*/ `
<div class="container p-0 m-0 ps-2">
  <form @submit.prevent="handleSubmit" autocomplete="off" spellcheck="false">
    <div v-for="formBlock in config" :key="formBlock.fieldLabel">
      <div v-if="formBlock.formType === 'expandable_form'">
        <div v-for="section in formBlock.sections" :key="section.sectionId" class="card mb-3 bg-white shadow-sm">
          <div class="card-header popup-head text-white py-2 d-flex justify-content-between align-items-center border-bottom-0"
               role="button"
               data-bs-toggle="collapse"
               :data-bs-target="'#' + section.sectionId">
            <h6 class="mb-0 fw-semibold">{{ section.sectionTitle }}</h6>
            <i class="bi text-dark fs-5" :class="section.isExpandedByDefault ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
          </div>

          <div :id="section.sectionId" class="collapse" :class="{ show: section.isExpandedByDefault }">
            <div class="card-body py-2 pb-3">
              <div class="row g-3">
                <template v-for="field in getFieldsWithConditionals(section)" :key="field.fieldKey">

                  <!-- Field column -->
                  <div class="col-md-3" :class="{ 'd-none': field.show === false || field.isHidden === true }">
                    <label :for="field.fieldKey" class="form-label small fw-semibold">{{ field.fieldLabel }} <span v-if="field.isRequired" class="text-danger small">*</span></label>
                    <span v-if="field.tooltip" class="ms-1 text-muted"  data-bs-toggle="tooltip" data-bs-placement="top" :title="field.tooltip" style="cursor: help;">
                      <i class="bi bi-info-circle"></i>
                    </span>

                    <div :class="{ 'input-group': field.addons?.some(a => a.inputType === 'component') }">
                      <!-- Text / Numeric / Alphanumeric -->
                      <input v-if="['alphanumeric','numeric','number','email','phone','text','pin_code_search','numeric_format'].includes(field.inputType || field.type)"
                            type="text"
                            :id="field.fieldKey"
                            class="form-control form-control-sm shadow-none py-0 px-2"
                            :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                            :placeholder="field.fieldHolder || field.placeholder || ''"
                            :value="(field.inputType || field.type) === 'numeric_format' && field.value ? formatCurrency(field.value) : field.value"
                            :maxlength="(field.inputType || field.type) === 'numeric_format' ? null : field.maxLength"
                            :readonly="field.isReadOnly"
                            :disabled="isReadOnly"
                            :required="field.isRequired || field.required"
                            @input="(field.inputType || field.type) === 'numeric_format' ? handleCurrencyInput(field, $event) : getFieldEvents(myConfig, field.fieldKey || field.key, field).input?.($event)"
                      />


                    <div v-if="(field.inputType || field.type) === 'numeric_format' && field.value" class="mt-0 d-flex align-items-center justify-content-start">
                      <div class="bg-light border px-1 py-1 d-inline-flex align-items-center" style="font-size: 0.72rem;">
                        <i class="bi bi-currency-rupee me-1 text-muted" style="font-size: 0.8rem;"></i>
                        <span class="fw-medium">{{ $formatNumberText(field.value) }}</span>
                      </div>
                    </div>

    

                      <!-- Radio Group -->
                      <div v-else-if="['radio','radio_group'].includes(field.inputType || field.type)" class="form-check-group">
                        <div v-for="opt in getFieldOptions(field)" :key="opt.value" class="form-check">
                          <input class="form-check-input" type="radio"
                                :id="field.fieldKey + '_' + opt.value"
                                :name="field.fieldKey"
                                :value="opt.value"
                                v-model="field.value"
                                :readonly="isReadOnly || field?.isReadOnly"
                                :disabled="isReadOnly || field?.isDisabled"
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
                        :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
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
                            :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
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
                        :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
                        :accept="getFileAccept(field.validation?.validationPattern || [])"
                        @change="handleFileChange($event, field)" />


                  <!-- Component-type addons only (inside input-group) -->
                      <template v-if="Array.isArray(field.addons) && field.addons.length && componentsLoaded">
                        <template v-for="addon in field.addons" :key="addon?.fieldKey || Math.random()">
                          <component
                            v-if="addon.inputType === 'component' && getDynamicComponent(addon.fieldKey)"
                            :is="getDynamicComponent(addon.fieldKey)"
                            :store="store"
                            :config="myConfig"
                            :field="field"
                          />
                        </template>
                      </template>
                    </div>

                    <!-- Action-type addons as links (below input) -->
                    <template v-if="Array.isArray(field.addons) && field.addons.length && componentsLoaded">
                      <template v-for="addon in field.addons" :key="'link-' + (addon?.fieldKey || Math.random())">
                        <a
                          v-if="addon.inputType === 'action'"
                          href="javascript:void(0)"
                          class="text-dark text-decoration-none d-inline-flex align-items-center mt-1"
                          style="font-size: 0.75rem;"
                          :class="{ 'text-muted pe-none': addon.isDisabled }"
                          :title="addon.tooltip"
                          @click="!addon.isDisabled && handleAddonAction(myConfig, addon.inputChange, addon)"
                        >
                          <i :class="'bi bi-' + (addon.inputIcon || 'car-front') + ' me-1'"></i>
                          {{ addon.tooltip || addon.fieldLabel }}
                        </a>
                      </template>
                    </template>


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
      <button type="submit" v-if="!isReadOnly" class="btn btn-dark btn-sm px-4" :disabled="isProcessing" @click.prevent="handleSubmit">
        <i class="bi bi-save me-1"></i>
        <span v-if="isProcessing">Saving...</span>
        <span v-else>Submit</span>
      </button>
    </div>

  </form>
</div>
`
};