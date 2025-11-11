export const Add = {
  props: ["branch_id", "dealer_id"],

  data() {
    return {
      isLoading: false,
      isEdit: false,
      branchData: null,
      error: null,
      fieldGroups: [],
      pinCodeData: [],
    };
  },

  async mounted() {
    this.isLoading = true;
    try {
      await this.getFields();
      if (this.branch_id) {
        this.isEdit = true;
        await this.getBranchData();
        const pinField = this.fieldGroups ?.flatMap(group => group.fields) ?.find(f => f.id === "pin_code");
        if (pinField && pinField.value) {
          await this.handlePincodeChange(pinField.value);
        }
      }
    } finally {
      this.isLoading = false;
    }
  },

  methods: {
    async getBranchData() {
      try {
        const res = await this.request("get_view", {
          branch_id: this.branch_id,
        });

        if (res.status === 200 && res.body.status === "ok" && res.body.data) {
          this.branchData = res.body.data['branches'][0] || {};
          this.fieldGroups.forEach(group => {
            group.fields.forEach(field => {
              if (this.branchData[field.id] !== undefined) {
                if (field.type === "check_box") {
                    const val = this.branchData[field.id] || "";
                    field.value = val ? val.split(",") : [];
                } else {
                    field.value = this.branchData[field.id];
                }
              } else {
                field.value = field.type === "check_box" ? [] : "";
              }

            });
          });
        } else {
          this.error = "No data found";
        }
      } catch (err) {
        console.error(err);
        this.error = "Failed to load dealer data.";
      }
    },

async getFields() {
  try {
    const res = await this.request("get_fields", {});
    if (res.status === 200 && res.body.status === "ok") {
      const fields = Array.isArray(res.body.data)
        ? res.body.data
        : Object.values(res.body.data);

      const grouped = {};

      fields.forEach(field => {
        if (!grouped[field.heading]) {
          grouped[field.heading] = [];
        }

        grouped[field.heading].push({
          ...field,
          value: field.type === "check_box" ? [] : "",
          error: ""
        });
      });

      this.fieldGroups = Object.keys(grouped).map(heading => ({
        heading,
        fields: grouped[heading],
      }));
    }
  } catch (err) {
    console.error("Failed to load field groups:", err);
  }
},

    async request(action, data = {}) {
      try {
        return await $http(
          "POST",
          `${g.$base_url_api}/admin/dealerships`,
          { action, ...data },
          {}
        );
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async saveDealer() {
      const mergedFields = this.fieldGroups.flatMap(section => section.fields);
      let hasError = false;

      this.fieldGroups.forEach(group => {
        group.fields.forEach(field => {
          field.error = "";
        });
      });

      mergedFields.forEach(field => {
        const value = (field.value || "").toString().trim();

        if (field.required && (value === "" || value === "0")) {
          field.error = `${field.label} is required`;
          hasError = true;
          return;
        }

        if (value && field.validation) {
          console.log(value);
          console.log(field.validation);
          try {
            const regexBody = field.validation.replace(/^\/|\/$/g, "");
            const regex = new RegExp(regexBody);

            if (!regex.test(value)) {
              field.error = `Invalid ${field.label}`;
              hasError = true;
            }
          } catch (e) {
            console.error("Invalid regex in field.validation:", field.validation, e);
          }
        }
      });
      if (hasError) return;

      const formData = {};
      mergedFields.forEach(field => {
          if (field.type === "check_box") {
              // Save as comma-separated string
              formData[field.id] = (field.value || []).join(",");
          } else {
              formData[field.id] = field.value;
          }
      });

      const payload = {
        form: { ...formData },
        ...(this.isEdit
          ? { branch_id: this.branch_id }
          : { dealer_id: this.dealer_id })
      };

      try {
        const res = await this.request(
          this.isEdit ? "update" : "add",
          payload
        );

        if (res.status === 400 && res.body.status === "fail") {
          const errors = res.body.errors;
          this.fieldGroups.forEach(group => {
            group.fields.forEach(field => {
              field.error = errors[field.id] || "";
            });
          });
          return;
        }

        if (res.status === 200 && res.body.status === "ok") {
          this.$router.back();
        }
      } catch (err) {
        console.error("Save failed", err);
      }
    },

    async handlePincodeChange(value) {
      const pin_code = (value || "").trim();
      const stateField = this.fieldGroups?.flatMap(g => g.fields)?.find(f => f.id === "state");
      const cityField  = this.fieldGroups?.flatMap(g => g.fields)?.find(f => f.id === "city");

      if (!/^\d{6}$/.test(pin_code)) {
      if (stateField) {
        stateField.options = ["Select"];
        stateField.value = "0";
      }
      if (cityField) {
        cityField.options = ["Select"];
        cityField.value = "0";
      }
        return;
      }

      try {
        const res = await $http("POST", `${g.$base_url_api}/master-data`, {
          action: "getStateCityByPincode",
          pin_code,
        });

        if (res?.body?.status && res.body.data) {
          const { state: stateData, city: cityData } = res.body.data;

          if (stateField && stateData.state && stateData.state_name) {
            stateField.options = {
              [stateData.state]: stateData.state_name,
            };
            stateField.value = stateData.state;
          }

          if (cityField && cityData.city && cityData.city_name) {
            cityField.options = {
              [cityData.city]: cityData.city_name,
            };
            cityField.value = cityData.city;
          }

        }
      } catch (err) {
        console.error("Failed to fetch pincode data", err);
      }
    }


  },

  template: `
    <div class="container mt-4">
      <h3>{{ isEdit ? 'Edit Branch' : 'Add Branch' }}</h3>

      <!-- Loader -->
      <div v-if="isLoading" class="py-4 text-center">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">Loading dealer data...</p>
      </div>

      <!-- Form -->
      <div v-else>
        <form @submit.prevent="saveDealer" class="mt-3">
          <div class="section-card p-0">
            <div v-for="(group, gIndex) in fieldGroups" :key="gIndex" class="mb-4">
            <div class="modal-title popup-head px-3 py-3">
              <h2 class="m-0">{{ group.heading }}</h2>
            </div>
              <div class="row g-3 p-3">
                <template v-for="(field, fIndex) in group.fields" :key="field.id">
                    <div class="col-md-6">
                        <label class="form-label">
                        {{ field.label }}
                        <span v-if="field.required" class="text-danger">*</span>
                        </label>

                        <input
                          v-if="field.type == 'text'"
                          :type="field.type"
                          v-model="field.value"
                          class="form-control"
                          :class="{ 'field-error': field.error }"
                          :placeholder="'Enter ' + field.label.toLowerCase()"
                          v-bind="{ maxlength: field.maxlength || null }"
                          @input="field.id === 'pin_code' ? handlePincodeChange(field.value) : null"
                        />

                        <textarea
                          v-else-if = "field.type === 'textarea'"
                          v-model="field.value"
                          class="form-control"
                          :class="{ 'field-error': field.error }"
                          :rows="field.rows || 3"
                          :placeholder="'Enter ' + field.label.toLowerCase()"
                        ></textarea>

                        <!-- Select -->
                        <select v-else-if="field.type === 'select'" 
                          :class="{ 'field-error': field.error }"
                          v-model="field.value" class="form-select">
                          <option 
                            v-for="(label, value) in field.options" 
                            :key="value" 
                            :value="value"
                          >
                            {{ label }}
                          </option>
                        </select>

<div v-else-if="field.type === 'check_box'" class="d-flex flex-wrap gap-2">
  <div v-for="(label, value) in field.options" :key="value" class="form-check">
    <input
      class="form-check-input"
      type="checkbox"
      :id="field.id + '_' + value"
      :value="value"
      v-model="field.value"
    />
    <label class="form-check-label" :for="field.id + '_' + value">
      {{ label }}
    </label>
  </div>
</div>



                        <!-- Error message -->
                        <div v-if="field.error" class="text-danger small mt-1">
                          {{ field.error }}
                        </div>

                    </div>
                    </template>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="text-end my-4">
            <button type="button" class="btn btn-outline-secondary rounded-1 me-2" @click="$router.back()">
              Cancel
            </button>
            <button type="submit" class="btn btn-dark rounded-1">
              {{ isEdit ? 'Update Branch' : 'Add Branch' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirmation Modal -->
        <!-- <div class="modal fade" id="confirmMainBranchModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog ">
            <div class="modal-content rounded-3 border-0 shadow-lg">
              <div class="modal-header popup-head">
                <h5 class="modal-title">Set as Main Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body text-center">
                <p class="mb-3">
                  Already having main branch: <strong>{{ currentMainBranch }}</strong>
                </p>
                <p>Do you want to make <strong>{{ selectedBranch }}</strong> the new main branch?</p>
              </div>
              <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark rounded-1" @click="confirmMakeMainBranch">Yes</button>
              </div>
            </div>
          </div>
        </div> -->
  `,
};
