export const Certification = {
  name: "Certification",
  props: { 
    store: { type: Object, required: true },
    isReadOnly: { type: Boolean, required: false, default: false },
  },
  components: { },
  data() {
    return {
      componentMap: {},      // dynamically loaded components
      componentsLoaded: false,
      myConfig: 'certificationConfig',
    };
  },

async created() {
  const [{ default: vaahan }] = await $importComponent(['/pages/views/pm/vaahan.js']);
  this.componentMap['vaahan'] =  Vue.markRaw(vaahan);
  this.componentsLoaded = true;
},
mounted(){
    if(this.store.getDetails.certification_checklist && Object.keys(this.store.getDetails.certification_checklist).length > 0  ){
    //const answerMap = Object.assign({}, ...JSON.parse(this.store.getDetails.certification_checklist));
    const answerMap = JSON.parse(this.store.getDetails.certification_checklist);
   
    if( this.store.getCertificationConfig?.fields[0]?.sections ){
          this.store.getCertificationConfig['fields'][0]['sections'].forEach(section => {
              if(section.sectionId == "certification-checklist"){
                section['fields'].forEach( question=>{
                  if (answerMap[question.fieldKey] !== undefined) {
                    question.value = answerMap[question.fieldKey]
                  }
                } )
              }
            /*if (answerMap[field.fieldKey]) {
              field.value = answerMap[field.fieldKey];
            }*/
          });
    }
    
  }
},
computed: {
    getDetails: vm => vm.store.getDetails || {},
    config: vm => vm.store.getCertificationConfig || [],
   
    isProcessing: vm => vm.store.isProcessing,
   
    hasSections() { return this.config?.length > 0; },
    getErrors: vm => field => {
      const errorValue = vm.store.errors?.certificationConfig?.[field];
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
    },
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
      if (['numeric','number', 'phone', 'pin_code_search'].includes(type))
        return field.allowDecimal ? value.replace(/[^0-9.]/g,'') : value.replace(/[^0-9]/g,'');
      if (type === 'alphanumeric') value = value.replace(/[^a-zA-Z0-9@!*\-_\+.,\s]/g, '');
      if (['text','alphanumeric', 'email'].includes(type) && field.isCaps) value = value.toUpperCase();
      return value;
    },
    getDateAttributes(field, calendarType) {
      return $setDateAttributes(field, calendarType);     
    },
    getFileAccept(patterns) {
      return $setFileAccept(patterns);     
    },
    handleFileChange(event, field) {
      console.log(field)
      const files = event.target.files;
      field.value = files?.length === 1 ? files[0] : [...files] || null;
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
      const result = await this.store.saveCertification();
      // console.log('saveDetail returned:', result);
      // console.log('Errors after save:', this.store.errors.certificationConfig);
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
    },
    formatCellValue(val, type='') {
      if (!type) return val;
      if (type === 'date') return $formatTime(val);
      return val;
    },
  },


  template: /*html*/ `
<div class="container p-0 m-0 ps-2">
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header popup-head text-dark py-3 px-3 rounded-top">
            <h6 class="mb-0 fw-semibold small text-uppercase"><i class="bi bi-info-circle me-1"></i> Overview</h6>
        </div>
            <div class="card-body py-2">
                <div class="row g-3">
                    <div v-for="(colBlock, colIndex) in config.columns" :key="'overview-block-' + colIndex" class="col-12 col-md-6 col-lg-4">
                        <div class="border rounded-3 p-2 h-100 bg-white">
                            <h6 class="fw-semibold text-dark border-bottom pb-1 mb-2 d-flex align-items-center gap-1">
                            <i class="bi bi-layout-text-window me-1"></i>{{ colBlock.title }}</h6>
                            <div class="d-flex flex-wrap gap-1">
                                <div v-for="(col, dataIndex) in colBlock.data" :key="'overview-data-' + colIndex + '-' + dataIndex" 
                                    class="flex-grow-1 small text-muted"
                                    style="min-width: 45%;">
                                    <div class="fw-normal text-dark mb-1">{{ col.label || colBlock.title }}</div>
                                    <div class="text-body fw-semibold">
                                        <span :class="col.class ? col.class : ''">{{ formatCellValue(getDetails[col.key?.[0]], col.type) || '—' }}</span>
                                    </div>    
                                </div>
                                <div v-if="colBlock?.badge">
                                    <div class="badge bg-secondary text-white fw-semibold px-3 py-2 rounded d-inline-flex align-items-center fs-6">
                                        <i :class="getDetails[colBlock?.badge?.key] ? 'bi bi-check-circle' : 'bi bi-x-circle'" class="me-1"></i>
                                        {{ getDetails[colBlock?.badge?.key] ? colBlock?.badge?.yes : colBlock?.badge?.no }}
                                    </div>
                                </div>                        
                            </div>                    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    
  <form @submit.prevent="handleSubmit" autocomplete="off" spellcheck="false">
    <div v-for="formBlock in config.fields" :key="formBlock.fieldLabel">
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
                    <span v-if="field.tooltip" class="ms-1 text-muted"  data-bs-toggle="tooltip" data-bs-placement="top" :title="field.tooltip" style="cursor: help;">
                      <i class="bi bi-info-circle"></i>
                    </span>

                    <div :class="{ 'input-group': field.addons?.length }">
                      <!-- Text / Numeric / Alphanumeric -->
                      <input v-if="['alphanumeric','numeric','number','email','phone','text','pin_code_search','numeric_format'].includes(field.inputType || field.type)"
                            type="text"
                            :id="field.fieldKey"
                            class="form-control form-control-sm shadow-none py-0 px-2"
                            :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                            :placeholder="field.fieldHolder || field.placeholder || ''"
                            :value="(field.inputType || field.type) === 'numeric_format' && field.value ? formatCurrency(field.value) : field.value"
                            :maxlength="(field.inputType || field.type) === 'numeric_format' ? null : field.maxLength"
                            :readonly="field.isReadOnly || field.readonly"
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
                     <div v-else-if="['dropdownIds','dynamic_dropdown','select','searchbleDropdownIds'].includes(field.inputType || field.type)">
                        <SelectSearch
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
                      </div>
                      <!-- Date / Calendar -->
                      <input
                        v-else-if="['calender','date','calender_time'].includes(field.inputType || field.type)"
                        :type="['calender_time'].includes(field.inputType || field.type) ? 'datetime-local' : 'date'"
                        :id="field.fieldKey"
                        class="form-control form-control-sm shadow-none py-1 px-2"
                        :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                        v-model="field.value"
                        :readonly="field.isReadOnly || field.readonly"
                        :disabled="isReadOnly || field?.isDisabled"
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
    <div class="text-end" v-if="getDetails.certification_status != 1">
      <button v-if="!isReadOnly" type="submit" class="btn btn-dark btn-sm px-4" :disabled="isProcessing" @click.prevent="handleSubmit">
        <i class="bi bi-save me-1"></i>
        <span v-if="isProcessing">Saving...</span>
        <span v-else>Submit</span>
      </button>
    </div>
  </form>
</div>
`
};
