export default {
  data() {
    return {
      features: [],
      featureSearch: "",
      isEditMode: false,
      selectedFeatureId: null,
      confirmAction: null, 
      confirmFeature: null,
      isSubmitting: false,

      featureForm: {
        id: null,
        flag_name: "",
        description: "",
        flag_type: 1,
        value: ""
      },
      formErrors: {}
    };
  },

  mounted() {
    this.getFeatureFlags();
  },

  computed: {
    filteredFeatures() {
      const q = this.featureSearch.toLowerCase();
      return this.features.filter(
        (f) =>
          f.flag_name.toLowerCase().includes(q) ||
          (f.description && f.description.toLowerCase().includes(q))
      );
    }
  },

  methods: {
    async request(action, data = {}) {
      try {
        const res = await $http(
          "POST",
          `${g.$base_url_api}/admin/feature-flags`,
          { action, ...data },
          {}
        );
        return res;
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async getFeatureFlags() {
      const res = await this.request("list");
      if (res.status === 200 && Array.isArray(res.body.data.flags)) {
        this.features = res.body.data.flags || [];
      } else {
        this.features = [];
        console.error("Failed to load feature flags:", res.body.message);
      }
    },

    addFeature() {
      this.isEditMode = false;
      this.featureForm = {
        id: null,
        flag_name: "",
        description: "",
        flag_type: 1,
        value: "n"
      };
      this.formErrors = {};
      $("#showFeatureModal").modal('show');
    },

    editFeature(feature) {
      this.isEditMode = true;
      this.featureForm = { ...feature };
      this.formErrors = {};
      $("#showFeatureModal").modal('show');
    },

    closeFeatureFormModal() {
      $("#showFeatureModal").modal('hide');
    },

    validateFeatureForm() {
      this.formErrors = {};

      if (!this.featureForm.flag_name) {
        this.formErrors.flag_name = "Feature name is required.";
      } else if (/\s/.test(this.featureForm.flag_name)) {
        this.formErrors.flag_name = "Feature name cannot contain spaces.";
      }

      if (!this.featureForm.description) {
        this.formErrors.description = "Description is required.";
      }

      if (!this.featureForm.flag_type) {
        this.formErrors.flag_type = "Flag type is required.";
      }

      return Object.keys(this.formErrors).length == 0;
    },

    async submitFeatureForm() {
      if (!this.validateFeatureForm()) return;

      const payload = {
        ...this.featureForm,
        value:
          this.featureForm.flag_type == 1
            ? this.featureForm.value === "y"
              ? "y"
              : "n"
            : this.featureForm.value
      };
      if (!this.isEditMode) { delete payload.id; }
      const action = this.isEditMode ? "update" : "add";
      const res = await this.request(action, payload);

      if (res.status === 200) {
        this.closeFeatureFormModal();
        this.getFeatureFlags();
      } else {
        this.formErrors = { ...res.body.errors };
        this.formErrors.general = res.body?.msg || "Something went wrong.";
      }
    },

    // ===== Confirmation entry points =====
    deleteFeature(feature) {
      this.confirmAction = "delete";
      this.confirmFeature = feature;
      $("#confirmModal").modal("show");
    },

    confirmToggle(feature) {
      this.confirmAction = "toggle";
      this.confirmFeature = feature;
      $("#confirmModal").modal("show");
    },

    confirmIncrement(feature) {
      this.confirmAction = "increment";
      this.confirmFeature = feature;
      $("#confirmModal").modal("show");
    },

    // ===== Confirm execution =====
    async handleConfirmAction() {
      if (!this.confirmFeature) return;

      this.isSubmitting = true;

      if (this.confirmAction === "delete") {
        const res = await this.request("delete", { id: this.confirmFeature.id });
        if (res.status === 200) {
          this.getFeatureFlags();
        } else {
          alert(res.body?.message || "Failed to delete feature");
        }
      }

      if (this.confirmAction === "toggle") {
        const f = this.confirmFeature;
        f.value = f.value === "y" ? "n" : "y";
        await this.request("update", f);
      }

      if (this.confirmAction === "increment") {
        const f = this.confirmFeature;
        let newValue = Number(f.value) || 0;
        newValue++;
        f.value = newValue;
        await this.request("update", { ...f, value: String(newValue) });
      }

      this.isSubmitting = false;
      $("#confirmModal").modal("hide");
      this.confirmFeature = null;
      this.getFeatureFlags();
    }
  },

  template: `
<div class="container-fluid py-5 bg-light min-vh-100 dealers-modules" id="grid-container">
  <div class="row justify-content-center" id="grid-panel">
    <div class="col-lg-11 col-xl-10">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h4 text-uppercase fw-bold text-muted">Feature Flags </h2>
        <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a New Module" id="grid-button" @click="addFeature">
          <i class="bi bi-plus-lg me-1"></i>  Add Feature</button>
      </div>

        <!-- Search -->
      <div class="row mb-3">
        <div class="col-12 col-md-6 col-sm-10 col-lg-6 search-input">
          <div class="input-group rounded-1 overflow-hidden shadow-sm" id="grid-search-group">
            <span class="input-group-text bg-white border-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input v-model="featureSearch" type="text" class="form-control border-0 py-2" placeholder="Search Features..." id="grid-search-input">
          </div>
        </div>
      </div>

      <!-- Feature List -->
      <div class="row justify-content-center g-4">
        <div class="col-md-12">
          <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <li 
                  v-for="feature in filteredFeatures" 
                  :key="feature.id" 
                  class="list-group-item d-flex justify-content-between align-items-center"
                >

                  <!-- Left: Info -->
                  <div class="flex-grow-1">
                    <div class="fw-bold">{{ feature.flag_name }}</div>
                    <small class="text-muted d-block">{{ feature.description }}</small>
                  </div>

                  <!-- Center: Actions -->
                  <div class="d-flex align-items-center">

                    <!-- Text Value -->
                    <div v-if="feature.flag_type == 2 || feature.flag_type == 3" class="ms-3">
                      <input 
                        type="text" 
                        class="form-control form-control-sm d-inline-block text-center px-1 py-2" 
                        :value="feature.value" 
                        readonly
                        style="width:auto; min-width:60px;"
                      >
                    </div>

                    <!-- Increment -->
                    <div v-if="feature.flag_type == 2" class="d-flex align-items-center ms-3">
                      <button 
                        class="btn btn-sm btn-outline-secondary rounded-1"
                        @click="confirmIncrement(feature)"
                      >
                        <i class="bi bi-plus-lg"></i>
                      </button>
                    </div>

                    <!-- Text Edit -->
                    <div v-if="feature.flag_type == 3" class="ms-3">
                      <button 
                        class="btn btn-sm btn-outline-secondary rounded-1"
                        @click="editFeature(feature)"
                      >
                        <i class="bi bi-pencil"></i>
                      </button>
                    </div>

                    <!-- Boolean -->
                  <div v-if="feature.flag_type == 1" class="form-check form-switch ms-0 d-flex align-items-center">
                    <input
                      class="form-check-input my-1"
                      type="checkbox"
                      :class="feature.value == 'y' ? 'bg-success border-success' : 'border-danger'"
                      :checked="feature.value == 'y'"
                      @click.prevent="confirmToggle(feature)" 
                    >
                    <span 
                      class="ms-0 fw-bold"
                      :class="feature.value == 'y' ? 'text-success' : 'text-danger'"
                    >
                      {{ feature.value == 'y' ? 'On' : 'Off' }}
                    </span>
                  </div>
                  </div>

                  <!-- Right: Delete -->
                  <div class="ms-3">
                    <button 
                      class="btn btn-sm btn-outline-secondary rounded-1"
                      @click="deleteFeature(feature)"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>

                </li>

                <!-- Empty State -->
                <li 
                  v-if="filteredFeatures.length == 0" 
                  class="list-group-item text-center text-muted"
                >
                  No features found
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Feature Form Modal (add/edit) -->
      <div class="modal fade" id ="showFeatureModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
          <div class="modal-content bg-white rounded-3 shadow border-0">

            <!-- Header -->
            <div class="modal-header popup-head">
              <h5 class="modal-title fw-bold">
                {{ isEditMode ? 'Edit Feature' : 'Add Feature' }}
              </h5>
              <button 
                type="button" 
                class="btn-close" 
                @click="closeFeatureFormModal"
              ></button>
            </div>

            <!-- Body -->
            <div class="modal-body">

              <!-- General error -->
              <div v-if="formErrors.general" class="alert alert-danger">
                {{ formErrors.general }}
              </div>

              <!-- Feature Name -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Feature Name</label>
                <input 
                  v-model="featureForm.flag_name" 
                  class="form-control shadow-sm" 
                  placeholder="Enter feature name" 
                />
                <small v-if="formErrors.flag_name" class="text-danger">
                  {{ formErrors.flag_name }}
                </small>
              </div>

              <!-- Description -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea 
                  v-model="featureForm.description" 
                  class="form-control shadow-sm" 
                  placeholder="Enter description"
                ></textarea>
                <small v-if="formErrors.description" class="text-danger">
                  {{ formErrors.description }}
                </small>
              </div>

              <!-- Flag Type -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Flag Type</label>
                <select 
                  v-model.number="featureForm.flag_type" 
                  class="form-select shadow-sm"
                >
                  <option :value="1">Boolean</option>
                  <option :value="2">Increment</option>
                  <option :value="3">Text</option>
                </select>
                <small v-if="formErrors.flag_type" class="text-danger">
                  {{ formErrors.flag_type }}
                </small>
              </div>

              <!-- Value (Text) -->
              <div class="mb-3" v-if="featureForm.flag_type == 3">
                <label class="form-label fw-semibold">Value</label>
                <input 
                  v-model="featureForm.value" 
                  class="form-control shadow-sm" 
                  placeholder="Enter value" 
                />
                <small v-if="formErrors.value" class="text-danger">
                  {{ formErrors.value }}
                </small>
              </div>

              <!-- Value (Increment / Number) -->
              <div class="mb-3" v-if="featureForm.flag_type == 2">
                <label class="form-label fw-semibold">Value</label>
                <input 
                  v-model="featureForm.value" 
                  type="number" 
                  min="0" 
                  class="form-control shadow-sm" 
                  placeholder="Enter numeric value" 
                />
                <small v-if="formErrors.value" class="text-danger">
                  {{ formErrors.value }}
                </small>
              </div>

              <!-- Value (Boolean Switch) -->
              <div class="mb-3" v-if="featureForm.flag_type === 1">
                <label class="form-label fw-semibold">Active</label><br>
                <div class="form-check form-switch d-flex align-items-center">
                  <input 
                    class="form-check-input my-1" 
                    type="checkbox" 
                    role="switch" 
                    :class="featureForm.value === 'y' ? 'bg-success border-success' : 'border-danger'" 
                    :checked="featureForm.value === 'y'" 
                    @change="featureForm.value = featureForm.value === 'y' ? 'n' : 'y'"
                  >
                  <span 
                    class="ms-0 fw-bold" 
                    :class="featureForm.value === 'y' ? 'text-success' : 'text-danger'"
                  >
                    {{ featureForm.value === 'y' ? 'On' : 'Off' }}
                  </span>
                </div>
              </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer border-0">
              <button 
                class="btn btn-outline-secondary rounded-1 shadow-sm" 
                @click="closeFeatureFormModal"
              >
                Cancel
              </button>
              <button 
                class="btn btn-dark rounded-1 shadow-sm btn-hover-gradient" 
                @click="submitFeatureForm"
              >
                Save
              </button>
            </div>

          </div>
        </div>
      </div>

      
      
      <!-- Confirmation Modal -->
      <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content rounded-3 border-0 shadow-lg">
            <div class="modal-header popup-head">
              <h5 class="modal-title fw-bold text-danger"> Confirm Action </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
              <span v-if="confirmAction === 'delete'">
                Are you sure you want to <strong>delete</strong> the feature "<b>{{ confirmFeature?.flag_name }}</b>"?
              </span>
              <span v-if="confirmAction === 'toggle'">
                Are you sure you want to <strong>toggle</strong> the feature "<b>{{ confirmFeature?.flag_name }}</b>"?
              </span>
              <span v-if="confirmAction === 'increment'">
                Are you sure you want to <strong>increment</strong> the value of "<b>{{ confirmFeature?.flag_name }}</b>"?
              </span>
            </div>
            <div class="modal-footer border-0 pt-2">
              <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-danger rounded-1" @click="handleConfirmAction" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                Yes
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
`
};
