

export const Status = {
  name: "Status",
  props: { 
    store: { type: Object, required: true },
    isReadOnly: { type: Boolean, required: false, default: false },
  },
  components: { },

  data() {
    return {
      componentMap: {}, 
      componentsLoaded: false,
      myConfig: 'statusConfig'
    };
  },

  async created() {
    this.componentsLoaded = true;
  },

  computed: {
    isProcessing: vm => vm.store.isProcessing,
    detail: vm => vm.store.detail || {},
    config: vm => vm.store.getStatusConfig || [],
    configOverview: vm => vm.store.getStatusOverviewConfig || [],
    hasSections() { return this.config?.length > 0; },
    // Check if form has unsaved changes
    // isFormDirty: vm => vm.store.isStatusFormDirty,
    getErrors: vm => field => {
      const errorValue = vm.store.errors?.statusConfig?.[field];
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
          if ((parentField.inputType || parentField.type) === 'checkbox_group' && Array.isArray(parentField.value)) {
            parentField.value = parentField.value.join(',');
          }
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

    openDoc(name, url) {
      globalThis.$docViewer.openDoc(name, url);
    },
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
  let files = event?.target?.files || null;

  if (!files || files.length === 0) {
    // File removed or reset
    field.value = '';
  } else if (files.length === 1) {
    // Single file selected
      field.value = files?.length === 1 ? files[0] : [...files] || null;
  }

  // Trigger store event
},



    async handleSubmit() {
      if (!this.store) return;
      const result = await this.store.saveStatus();
      if (!result.success) return; // Stop execution if validation fails
    },
    formatCellValue(val, type='') {
      if (!type) return val;
      if (type === 'date') return $formatTime(val);
      return val;
    },
    hasVisibleFields(section) {
      if (!section || !section.fields || !Array.isArray(section.fields)) {
        return false;
      }
      const fields = this.getFieldsWithConditionals(section);
      return fields.some(field => field.show !== false && field.isHidden !== true);
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
    
    handleCurrencyInput(field, event) {
      const isNumericFormat = (field.inputType) === 'numeric_format';
      if (isNumericFormat) {
        const rawValue = event.target.value.replace(/[^0-9]/g, '');
        if (field.maxLength && rawValue.length > field.maxLength) {
          event.target.value = field.value ? $formattedNumber()(parseFloat(field.value)) : '';
          return;
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

  <!-- Skeleton Loader -->
  <div v-if="isProcessing" class="p-3">
	<div class="card mb-3">
		<div class="card-header bg-light py-2">
		<span class="skeleton w-25 d-block">&nbsp;</span>
		</div>
		<div class="card-body py-2">
		<div class="row g-3">
			<div v-for="n in 6" :key="'skeleton-field-' + n" class="col-md-4">
			<div class="skeleton w-50 mb-1">&nbsp;</div>
			<div class="skeleton w-75">&nbsp;</div>
			</div>
		</div>
		</div>
	</div>

	<div class="card mb-3">
		<div class="card-header bg-light py-2">
		<span class="skeleton w-25 d-block">&nbsp;</span>
		</div>
		<div class="card-body py-2">
		<div class="row g-2">
			<div v-for="n in 6" :key="'skeleton-img-' + n" class="col-6 col-sm-4 col-md-3 col-lg-2">
			<div class="skeleton rounded w-100" style="height:100px;">&nbsp;</div>
			</div>
		</div>
		</div>
	</div>
</div>


  <!-- Content -->
  <div v-else>
<!-- Dynamic Overview Section -->
<div 
  v-if="detail && Object.keys(detail).length && configOverview && configOverview?.length"
  class="card border-0 shadow-sm rounded-3 mb-3"
>
  <div class="card-header popup-head text-dark py-3 px-3 rounded-top">
    <h6 class="mb-0 fw-semibold small text-uppercase">
      <i class="bi bi-info-circle me-1"></i> Overview
    </h6>
  </div>

  <div class="card-body py-2">
    <div class="row g-3">
      <div 
        v-for="(colBlock, colIndex) in configOverview" 
        :key="'overview-block-' + colIndex"
        class="col-12 col-md-6 col-lg-4"
      >
        <div class="border rounded-3 p-2 h-100 bg-white">
          <h6 class="fw-semibold text-dark border-bottom pb-1 mb-2 d-flex align-items-center gap-1">
            <i class="bi bi-layout-text-window me-1"></i>
            {{ colBlock.title }}
          </h6>

          <div class="d-flex flex-wrap gap-1">
            <div 
              v-for="(col, dataIndex) in colBlock.data" 
              :key="'overview-data-' + colIndex + '-' + dataIndex"
              class="flex-grow-1 small text-muted"
              style="min-width: 45%;"
            >
              <div class="fw-normal text-dark mb-1">{{ col.label || colBlock.title }}</div>
              <div class="text-body fw-semibold">
                <span :class="col.class ? col.class : ''">{{ formatCellValue(detail[col.key?.[0]], col.type) || '—' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


    <!-- End Mini Overview -->

    <div  v-if="detail && Object.keys(detail).length">
      <form @submit.prevent="handleSubmit" autocomplete="off" spellcheck="false">
        <div v-for="formBlock in config" :key="formBlock.fieldLabel">
          <div v-if="formBlock.formType === 'expandable_form'">
            <div v-for="section in formBlock.sections" :key="section.sectionId" v-show="hasVisibleFields(section)" class="card mb-3 bg-white shadow-sm">

              <!-- Section Header -->
              <div class="card-header popup-head text-dark py-2 px-2 d-flex justify-content-between align-items-center border-bottom"
                  role="button"
                  data-bs-toggle="collapse"
                  :data-bs-target="'#' + section.sectionId">
                <h6 class="mb-0 fw-semibold">{{ section.sectionTitle }}</h6>
                <i class="bi text-dark fs-5" :class="section.isExpandedByDefault ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
              </div>

              <!-- Section Body -->
              <div :id="section.sectionId" class="collapse" :class="{ show: section.isExpandedByDefault }">
                <div class="card-body py-2 pb-3">
                  <div class="row g-3">

                    <div v-for="field in getFieldsWithConditionals(section)" :key="field.fieldKey" 
                      class="col-md-3" 
                      :class="{ 'd-none': field.show === false || field.isHidden === true }">
                      
                      <label :for="field.fieldKey" class="form-label small fw-semibold">{{ field.fieldLabel }} <span v-if="field.isRequired" class="text-danger small">*</span></label>
                      <span v-if="field.tooltip" class="ms-1 text-muted" :title="field.tooltip" data-bs-toggle="tooltip" data-bs-placement="top" style="cursor: help;">
                        <i class="bi bi-info-circle"></i>
                      </span>
                      <div :class="{ 'input-group': field.addons?.length }">

                        <!-- Text / Numeric -->
                     <input v-if="['alphanumeric','numeric','number','text','pin_code_search','numeric_format'].includes(field.inputType || field.type)"
                            type="text"
                            :id="field.fieldKey"
                            class="form-control form-control-sm shadow-none py-0 px-2"
                            :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                            :placeholder="field.fieldHolder || field.placeholder || ''"
                            :value="(field.inputType || field.type) === 'numeric_format' && field.value ? formatCurrency(field.value) : field.value"
                            :maxlength="(field.inputType || field.type) === 'numeric_format' ? null : field.maxLength"
                            :readonly="isReadOnly || field?.isReadOnly"
                            :disabled="isReadOnly || field?.isDisabled"
                            :required="field.isRequired || field.required"
                            @input="(field.inputType || field.type) === 'numeric_format' ? handleCurrencyInput(field, $event) : getFieldEvents(myConfig, field.fieldKey || field.key, field).input?.($event)"
                      />
                    <div v-if="(field.inputType || field.type) === 'numeric_format' && field.value" class="mt-0 d-flex align-items-center justify-content-start">
                      <div class="bg-light border px-1 py-1 d-inline-flex align-items-center" style="font-size: 0.72rem;">
                        <i class="bi bi-currency-rupee me-1 text-muted" style="font-size: 0.8rem;"></i>
                        <span class="fw-medium">{{ $formatNumberText(field.value) }}</span>
                      </div>
                    </div>
                        <!-- Date -->
                    <input
                        v-else-if="['calender','date','calender_time'].includes(field.inputType || field.type)"
                        :type="['calender_time'].includes(field.inputType || field.type) ? 'datetime-local' : 'date'"
                        :id="field.fieldKey"
                        class="form-control form-control-sm shadow-none py-1 px-2"
                        :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                        v-model="field.value"
                        :readonly="isReadOnly || field?.isReadOnly"
                        :disabled="isReadOnly || field?.isDisabled"
                        v-on="getFieldEvents(myConfig, field.fieldKey || field.key, field)"
                        @click="(e) => { e.target.showPicker && e.target.showPicker(); }"
                        v-bind="getDateAttributes(field, field?.calenderType)"
                    />
                      <!-- Dropdown -->  
                      <div v-else-if="['dropdownIds','dynamic_dropdown','select','searchbleDropdownIds'].includes(field.inputType || field.type)">
                        <SelectSearch
                          v-model="field.value"
                          :id="field.fieldKey"
                          :options="getFieldOptions(field)"
                          :placeholder="field.fieldLabel"
                          :searchable="field?.isSearch"
                          :showGrouping="field?.isGroup"
                          option-label="label"
                          option-value="value"
                          :disabled-options="field.disabledOptions?.map(v => v.toString())"
                          :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
                          :multiple ="field?.multiple"
                          :class="{ 'is-invalid': getErrors(field.fieldKey || field.key).length }"
                          @change="(val) => getFieldEvents(myConfig, field.fieldKey || field.key, field).change?.({ target: { value: val } })"
                        />
                      </div>

                        <!-- Checkbox Group -->
                    <div v-else-if="['checkbox_group'].includes(field.inputType || field.type)" class="form-check-group">
                      <div v-for="opt in getFieldOptions(field)" :key="opt.value" class="form-check">
                        <input
                          class="form-check-input"
                          type="checkbox"
                          :id="field.fieldKey + '_' + opt.value"
                          :value="opt.value.toString()"
                          :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
                          :checked="(field.value || '').split(',').includes(opt.value.toString())"
                          @change="toggleCheckboxValue(field, opt.value.toString())"
                        />
                        <label class="form-check-label small" :for="field.fieldKey + '_' + opt.value">
                          {{ opt.label }}
                        </label>
                      </div>
                    </div>

                        <!-- Radio Group -->
                        <div v-else-if="['radio','radio_group'].includes(field.inputType || field.type)" class="form-check-group">
                          <div class="row">
                            <div v-for="opt in getFieldOptions(field)" :key="opt.value" class="col-6">
                              <div class="form-check">
                                <input
                                  class="form-check-input"
                                  type="radio"
                                  :id="field.fieldKey + '_' + opt.value"
                                  :name="field.fieldKey"
                                  :value="opt.value"
                                  v-model="field.value"
                                  :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
                                  @change="store?.handleFieldEvent?.('onchange', myConfig, field.fieldKey, field, $event)"
                                />
                                <label class="form-check-label small" :for="field.fieldKey + '_' + opt.value" :class="{ 'text-muted': field.isReadOnly || field.isDisabled }">
                                  {{ opt.label }}
                                </label>
                              </div>
                            </div>
                          </div>
                        </div>

                 <!-- File -->
                    <div v-else-if="field.inputType === 'file'">
                      <!-- Show existing file link if it's a valid URL -->
                      <div
                        v-if="field.value && typeof field.value === 'string'"
                        class="d-flex align-items-center gap-2 p-2 border rounded bg-light"
                      >
                        <button
                          type="button"
                          class="btn btn-link p-0 text-truncate"
                          @click="openDoc(field.fieldLabel || field.key || 'Document', field.value)"
                        >
                          {{ field.fieldLabel || field.key }}
                        </button>

                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger rounded-circle p-1"
                          title="Delete File"
                          @click="handleFileChange(null, field)"
                        >
                          <i class="bi bi-x"></i>
                        </button>
                      </div>

                      <!-- File input (visible when no file or after delete) -->
                      <div
                        v-else
                        class="input-group"
                      >
                        <input
                          :id="field.fieldKey"
                          type="file"
                          class="form-control form-control-sm shadow-none py-1 px-2"
                          :class="{ 'is-invalid': getErrors(field.fieldKey).length }"
                          :multiple="field.multiple || false"
                          :accept="getFileAccept(field.validation?.mimeType || [])"
                          :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
                          @change="handleFileChange($event, field)"
                        />
                        <span class="input-group-text bg-white">
                          <i class="bi bi-upload"></i>
                        </span>
                      </div>
                    </div>

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
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Submit Button (hidden when purchased) -->
        <div class="text-end" v-if="hasSections">
          <button type="submit" v-if="!isReadOnly" class="btn btn-dark btn-sm px-4" :disabled="isProcessing">
          <!--<button type="submit" class="btn btn-dark btn-sm px-4" :disabled="isProcessing || !isFormDirty">-->
            <i class="bi bi-save me-1"></i>
            <span v-if="isProcessing">Saving...</span>
            <span v-else>Submit</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
`
};
