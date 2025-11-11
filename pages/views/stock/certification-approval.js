export const CertificationApproval = {
  name: 'CertificationApproval',
  props: { 
    store: { type: Object, required: true },
    isReadOnly: { type: Boolean, required: false, default: false },
  },

  data: () => ({
    defaultImage: '/assets/images/default-image.jpg',
    formModel: {},              
    errors: {},
    isProcessing: false,
  }),

  async created() {
    this.store?.init?.();
  },

  watch: {
    // re-init form model when data becomes available
    isLoaded(val) {
      if (val) this.initFormModel();
    },
    // propagate any changes in formModel back into section.field.value
    formModel: {
      handler(newVal) {
        const form = this.formConfig;
        if (!form || !form.sections) return;
        form.sections.forEach(section => {
          (section.fields || []).forEach(f => {
            const key = f.fieldKey || f.key;
            if (key && newVal.hasOwnProperty(key)) {
              f.value = newVal[key];
            }
          });
        });
      },
      deep: true,
    },
  },

  methods: {
    get(obj, path) {
      return path?.split('.').reduce((o, p) => o?.[p], obj);
    },

    setFormModelKey(key, value) {
      this.formModel = { ...this.formModel, [key]: value };
    },

    initFormModel() {
      const form = this.formConfig;
      if (!form || !form.sections) return;

      const data = this.displayData || {};
      form.sections.forEach(section => {
        (section.fields || []).forEach(f => {
          const key = f.fieldKey || f.key;
          const candidates = [key, f.key, 'certification_remarks', 'approval_note', 'remarks', 'note'];
          let value = undefined;
          for (const k of candidates) {
            if (k && Object.prototype.hasOwnProperty.call(data, k) && data[k] !== null && data[k] !== undefined) {
              value = data[k];
              break;
            }
          }
          if (value === undefined) value = (f.value ?? '');
          if (f.inputType && f.inputType.toString().toLowerCase().includes('dropdown') && value !== null && value !== undefined) {
            value = String(value);
          }
          this.setFormModelKey(key, value);
          f.value = value;
        });
      });
    },

    sanitizeInput(field, value) {
      const type = field.inputType || field.type;
      if (['numeric','number','phone','pin_code_search'].includes(type))
        return field.allowDecimal ? value.replace(/[^0-9.]/g,'') : value.replace(/[^0-9]/g,'');
      if (type === 'alphanumeric') value = value.replace(/[^a-zA-Z0-9@!*\-_\+.,\s]/g, '');
      if (['text','alphanumeric','email'].includes(type) && field.isCaps) value = value.toUpperCase();
      return value;
    },

    async handleFormSubmit(){
      this.errors = {};

      if (!this.formModel.certification_status) {
        this.errors.certification_status = 'Certification status is required';
      }
      if (!this.formModel.certification_remarks) {
        this.errors.certification_remarks = 'Note is required';
      }

      if (Object.keys(this.errors).length > 0) return;

      this.isProcessing = true;
      try {
        const payload = {
          action: 'update',
          sub_action: 'certificationapproval',
          certification_status: this.formModel.certification_status || '',
          certification_remarks: this.formModel.certification_remarks || '',
          id: this.displayData?.id || null,
        };

        const res = await $http("POST", `${g.$base_url_api}/my-stock`, payload);
        if (res?.body?.status && res.body.data) {
          $toast('success', res.body.msg);
          //  Refresh details and re-populate
          if (typeof this.store.getDetails === 'function') {
            await this.store.getDetails();
          }
          // this.initFormModel();
        }
      } catch (error) {
        console.error('Submit error:', error);
      } finally {
        this.isProcessing = false;
      }
    },

    checklist_answer(que) {
      const data = this.displayData || {};
      const answerMap = this.parseJson(data.certification_checklist) || {};
      if (que in answerMap) {
        return (answerMap[que] == 'y') ? 'Yes' : ((answerMap[que] == 'n') ? 'No' : '');
      } else return '-';
    },

    display(field) {
      const data = this.displayData || {};
      const val = data[field.key];
      if (field.key?.includes(',')) {
        const parts = field.key.split(',').map(k => data[k]?.trim()).filter(Boolean);
        return parts.join(' ') || '-';
      }
      if (this.isEmpty(val)) return '-';
      if (field.type === 'date' && typeof window.$formatTime === 'function')
        return window.$formatTime(val) || '-';
      if (field.type === 'numeric_format' && typeof window.$formattedCurrency === 'function') {
        const formatter = window.$formattedCurrency();
        return formatter(val);
      }
      const parsed = this.parseJson(val);
      if (parsed) return Array.isArray(parsed)
        ? `${parsed.length} items`
        : `${Object.keys(parsed).length} items`;
      return val;
    },

    isEmpty(v) {
      return [null, undefined, '', '0000-00-00', '0000-00-00 00:00:00'].includes(v);
    },

    parseJson(v) {
      if (typeof v !== 'string' || !/^[{\[]/.test(v)) return null;
      try { return JSON.parse(v); } catch { return null; }
    },

    getFieldOptions(field) {
      if (typeof this.store?.getOptionsForField === 'function') {
        const options = this.store.getOptionsForField(field.fieldKey || field.key, field);
        if (options?.length) return options;
      }
      return field.fieldOptionIds || field.fieldOptions || [];
    },
  },

  mounted() {
    this.store?.getDetails;
    this.initFormModel();
  },

  computed: {
    getDetails() { return this.store?.getDetails || {}; },
    meta() { return this.config.meta || {}; },
    formConfig() { return this.config?.form || this.config?.approvalConfig || null; },
    currentSlug() {
      const slug = (typeof $routeGetParam === 'function') ? $routeGetParam('slug3') : null;
      return slug || null;
    },
    config() {
      const slug = this.currentSlug;
      const key = slug ? `${slug}Config` : 'approvalConfig';
      return this.store?.[key] || this.store?.approvalConfig || {};
    },
    displayData() {
      return this.get(this.store, this.config.meta?.dataPath || 'detail') || {};
    },
    groupedFields() {
      const fields = this.config.fields || {};
      return Object.values(fields).reduce((acc, f) => {
        if (!['view', 'date', 'numeric_format'].includes(f.type)) return acc;
        const cat = f.category || 'Others';
        (acc[cat] ||= []).push(f);
        return acc;
      }, {});
    },
    isLoaded() {
      const checkPath = this.config.meta?.loadedCheckPath || this.config.meta?.dataPath;
      const data = this.get(this.store, checkPath);
      return data && (data.id || Object.keys(data).length);
    },
  },

  template: /*html*/`
  <div class="overview p-2">
    <ImageViewer ref="viewer" />

    <template v-if="isLoaded">
      <!-- Overview -->
      <div class="card mb-3">
        <div class="card-header py-2 popup-head">
          <h6 class="mb-0 fw-semibold small">{{ meta.title || 'Overview' }}</h6>
        </div>
        <div class="card-body py-2">
          <div v-for="(fields, group) in groupedFields" :key="group" class="mb-3">
            <h6 class="border-bottom pb-1 mb-2 small fw-semibold">{{ group }}</h6>
            <div class="row g-3">
              <div v-for="f in fields" :key="f.key" class="col-md-3">
                <div class="small text-muted">{{ f.label }}</div>
                <div class="fw-semibold text-body">{{ display(f) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Checklist -->
        <div class="card-body py-2">
          <h6 class="border-bottom pb-1 mb-2 small fw-semibold">Certification Checklist</h6>
          <div v-for="(question, index) in config.checklist" :key="index" class="row mb-3">
            <div class="col-md-10">
              <div class="small text-muted">{{ question.label }}</div>
              <div class="fw-semibold text-body">{{ checklist_answer(question.key) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div v-if="formConfig && formConfig.sections" class="card mb-3">
        <div class="card-header py-2 popup-head">
          <h6 class="mb-0 fw-semibold small">{{ formConfig.fieldLabel || 'Form' }}</h6>
        </div>
        <div class="card-body py-2">
          <form @submit.prevent="handleFormSubmit">
            <template v-for="section in formConfig.sections" :key="section.sectionId">
              <h6 class="border-bottom pb-1 mb-2 small fw-semibold">{{ section.sectionTitle }}</h6>
              <div class="row g-2">
                <template v-for="field in section.fields" :key="field.fieldKey">
                  <div class="col-md-6" v-if="field.inputType === 'dropdownIds'">
                    <label class="form-label small">
                      {{ field.fieldLabel }} <span v-if="field.isRequired" class="text-danger">*</span>
                    </label>
                    <SelectSearch
                      v-model="formModel[field.fieldKey]"
                      :options="getFieldOptions(field)"
                      :placeholder="field.fieldHolder || field.placeholder || ''"
                      option-label="label"
                      option-value="value"
                      :disabled="isReadOnly || field?.isReadOnly || field?.isDisabled"
                      :required="field.isRequired"
                      :searchable="field.isSearch"
                    />
                    <div v-if="errors[field.fieldKey]" class="invalid-feedback d-block">
                      {{ errors[field.fieldKey] }}
                    </div>
                  </div>

                  <div class="col-md-6" v-else>
                    <label class="form-label small">
                      {{ field.fieldLabel }} <span v-if="field.isRequired" class="text-danger">*</span>
                    </label>
                    <input
                      v-if="['alphanumeric','numeric','number','email','phone','text','pin_code_search','numeric_format'].includes(field.inputType || field.type)"
                      type="text"
                      v-model="formModel[field.fieldKey]"
                      class="form-control form-control-sm shadow-none py-0 px-2"
                      :placeholder="field.fieldHolder || ''"
                      :readonly="field.isReadOnly || field.readonly"
                      :disabled="isReadOnly"
                      :maxlength="field.maxLength || null"
                      @input="formModel[field.fieldKey] = sanitizeInput(field, $event.target.value)"
                    />
                    <div v-if="errors[field.fieldKey]" class="invalid-feedback d-block">
                      {{ errors[field.fieldKey] }}
                    </div>
                  </div>
                </template>
              </div>
            </template>

            <div class="d-flex gap-2 mt-3" v-if="(!getDetails.certification_status && !getDetails.certification_remarks)">
              <button
                v-if="!isReadOnly"
                class="btn btn-dark px-4"
                @click="handleFormSubmit"
                :disabled="isProcessing"
              >
                <span
                  v-if="isProcessing"
                  class="spinner-border spinner-border-sm me-2"
                  role="status"
                  aria-hidden="true"
                ></span>
                <span v-if="isProcessing">SAVING...</span>
                <span v-else>SUBMIT</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </template>

    <div v-else class="text-center p-4 text-muted">No data available</div>
  </div>`
};
