export const View = {
  props: ["branch_id"],

  data() {
    return {
      branches: null,
      loading: true,
      error: null,
      activeIndex: 0,
      fieldGroups: [],

      currentMainBranch: null,
      selectedBranch: null,
    };
  },

  async mounted() {
    await this.getBranchData();
    await this.getFieldGroups();
  },

  computed: {
    groupedFields() {
      const fieldsArray = Array.isArray(this.fieldGroups)
      ? this.fieldGroups
      : Object.values(this.fieldGroups);

      return fieldsArray.reduce((acc, field) => {
        if (!acc[field.heading]) {
          acc[field.heading] = [];
        }
        acc[field.heading].push(field);
        return acc;
      }, {});
    },
  },

  methods: {
    goTo(page) {
      $routeTo(`/admin/dealerships/${page}/${this.branch_id}`);
    },

    async getBranchData() {
      try {
        const res = await this.request("get_view", {
          branch_id: this.branch_id,
        });
        if (res.status === 200 && res.body.status === "ok") {
          this.branches = res.body.data["branches"][0] || [];
          console.log('branches', this.branches);
        } else {
          this.error = "No data found";
        }
      } catch (err) {
        this.error = "Failed to load dealer data.";
      } finally {
        this.loading = false;
      }
    },

    async getFieldGroups() {
      try {
        const res = await this.request("get_fields", {});
        if (res.status === 200 && res.body.status === "ok") {
          this.fieldGroups = res.body.data || [];
        }
      } catch (err) {
        console.error("Failed to load field groups:", err);
        this.error = "Failed to load field groups.";
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

    async confirmMakeMainBranch() {
      const oldMain = this.branches.find((b) => b.main_branch === "y");

      if (oldMain && oldMain.id !== this.branchForm.id) {
        const updatedOld = { ...oldMain, main_branch: "n" };
        await this.request("update", updatedOld);
      }

      this.branchForm.main_branch = "y";
      $("#confirmMainBranchModal").modal("hide");
      await this.saveBranchForm();

      await this.getDealershipData();
    },

  },

  template: `
    <div class="container mt-4 viewdetails">
     <h3>View Details</h3>
      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">Loading dealer details...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="alert alert-danger">{{ error }}</div>

      <!-- Accordion Dealer Details -->
      <div v-else>
        <div class="accordion" id="dealerAccordion">
          <div 
            v-for="(fields, heading, sIndex) in groupedFields" 
            :key="heading" 
            class="accordion-item"
          >
            <h2 class="accordion-header" :id="'heading' + sIndex">
              <button 
                class="accordion-button modal-title popup-head px-3 py-3" 
                :class="{ collapsed: activeIndex !== sIndex, 'active-header': activeIndex === sIndex }"
                type="button" 
                @click="activeIndex = (activeIndex === sIndex ? null : sIndex)"
              >
                {{ heading }}
              </button>
            </h2>

            <div 
              :id="'collapse' + sIndex" 
              class="accordion-collapse collapse" 
              :class="{ show: activeIndex === sIndex }" 
              :aria-labelledby="'heading' + sIndex"
            >
              <div class="accordion-body bg-white">
                <div class="row">
                  <div 
                    v-for="(field, fIndex) in fields" 
                    :key="fIndex" 
                    class="col-md-6"
                  >
                    <div class="field-card">
                      <div class="field-label">{{ field.label }}</div>
                      <div class="field-value">
                        : {{ branches?.[field.view_id ?? field.id] ?? '' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Back & Edit -->
        <div class="d-flex justify-content-end gap-2 mt-3">
          <button type="button" class="btn btn-outline-secondary" @click="$router.back()"> Cancel </button>
          <button class="btn btn-dark" @click="goTo('edit')"> Edit Details </button>
        </div>


      



        <!-- Confirmation Modal -->
        <div class="modal fade" id="confirmMainBranchModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog ">
            <div class="modal-content rounded-4 border-0 shadow-lg">
              <div class="modal-header border-0 pb-0">
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
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill" @click="confirmMakeMainBranch">Yes</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Branch Not Allowed Modal -->
        <div class="modal fade" id="mainNotAllowedModal" tabindex="-1">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
              <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body pt-3 text-center">
                <p class="mb-2">
                  <strong>Main Branch cannot be deleted.</strong>
                </p>
                <p class="mb-0">
                  Please assign another branch as the main branch before deleting this one.
                </p>
              </div>
              <div class="modal-footer border-0 pt-2">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  `,
};
