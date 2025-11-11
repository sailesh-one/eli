export default {
  data() {
    const currentYear = new Date().getFullYear();
    const startYear = 2023;
    const years = [];
    for (let y = startYear; y <= currentYear + 1; y++) {
      years.push(y);
    }
    const now = new Date();
    return {
      kra_targets: [],
      editedTargets: {}, // store keyed by kra.id or branch
      search_kra_year: now.getFullYear(),
      search_kra_month: now.getMonth() + 1,
      saving: false,
      months: [],
      years,
    };
  },

  mounted() {
    this.getKraTargets();
  },

  methods: {
    async request(action, data = {}) {
      try {
        const res = await $http(
          "POST",
          `${g.$base_url_api}/admin/kra-panel`,
          { action, ...data },
          {}
        );
        return res;
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async getKraTargets() {
      const res = await this.request("list", {
        kra_year: this.search_kra_year,
        kra_month: this.search_kra_month,
      });

      if (res.status == 200 && res.body.status == "ok") {
        this.kra_targets = res.body.data[0];
        this.months = res.body.data[1];
      }
    },

    handleInput(dealerId, branchId, kra, field, value) {
      const key = kra?.id > 0 ? kra.id : `new-${branchId}`;
      if (!this.editedTargets[key]) {
        this.editedTargets[key] = {
          field,
          value,
          dealer: dealerId,
          branch: branchId,
          year: this.search_kra_year,
          month: this.search_kra_month,
        };
        if (kra?.id > 0) {
          this.editedTargets[key].id = kra.id;
        }
      } else {
        // Update only the changed field/value
        this.editedTargets[key].field = field;
        this.editedTargets[key].value = value;
      }
    },

    sanitizeAndHandle(dealerId, branchId, kra, field, event) {
      let val = event.target.value.replace(/[^0-9]/g, ""); // only digits
      event.target.value = val;
      this.handleInput(dealerId, branchId, kra, field, val);
      // if(val && val > 0){
        this.autoSaveTargets();
      // }
    },

    async autoSaveTargets() {
      const edits = Object.values(this.editedTargets);
      if (edits.length === 0) return;

      this.saving = true;
      try {
        const payload = edits[0]; // one record at a time
        const res = await this.request("save", { ...payload });

        if (res.status == 200 && res.body.status == "ok") {
          this.editedTargets = {};
          console.log("Auto-saved successfully!");
        }

      else if(res.body.status == 'fail'){
        this.msg = res.body.msg;
        this.errors = res.body.errors;
      }
      } finally {
        this.saving = false;
      }
    },

    saveTargets() {
      const toastEl = document.getElementById("saveToast");
      const toast = new bootstrap.Toast(toastEl);
      toast.show();
    },
  },

template: `
<div class="container-fluid py-2 bg-light min-vh-100 kra-targets" id="grid-container">
  <h3 class="mb-4">KRA Targets Panel</h3>

  <!-- Filters -->
  <div class="row mb-2">
    <div class="col-md-3">
      <label class="form-label fw-bold">Select Year</label>
      <select v-model="search_kra_year" class="form-select" @change="getKraTargets">
        <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
      </select>
    </div>
    <div class="col-md-2">
        <label class="form-label fw-bold">Select Month</label>
        <select v-model="search_kra_month" class="form-select" @change="getKraTargets">
        <option v-for="(label, key) in months" :key="key" :value="key">{{ label }}</option>
        </select>
    </div>

    <div class="col d-flex justify-content-end">
        <div class="toast-container p-3">
        <div id="saveToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
            <div class="toast-body">
                Added successfully !!
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        </div>
    </div>

  </div>
  
<div class="table-responsive shadow-sm rounded-3 bg-white">
  <table class="table table-hover table-bordered mb-0 text-center align-middle">
    <thead class="table-dark">
      <tr>
        <th style="width: 15%">Dealer</th>
        <th style="width: 15%">Branch</th>
        <th style="width: 15%">City</th>
        <th style="width: 10%">Evaluation</th>
        <th style="width: 10%">Trade-in</th>
        <th style="width: 10%">Purchase</th>
        <th style="width: 10%">Sales</th>
        <th style="width: 10%">Overall Sales</th>
      </tr>
    </thead>
    <tbody>
      <template v-for="(dealer, dgIdx) in kra_targets" :key="dgIdx">
        <template v-if="dealer.branches && dealer.branches.length">
          <tr v-for="(branch, brIdx) in dealer.branches" :key="branch.branch_id">
            <td v-if="brIdx === 0" :rowspan="dealer.branches.length" class="fw-semibold align-middle gray-color">
              {{ dealer.dealer_group_name }}
            </td>

            <td class = "gray-color fw-semibold">{{ branch.branch_name }}</td>
            <td class = "gray-color fw-semibold">{{ branch.branch_city }}</td>

            <td>
              <input type="number" class="form-control" min="0"
                :value="branch.kra_targets[0]?.evaluation || ''"
                @blur="sanitizeAndHandle(dealer.dealer_group_id, branch.branch_id, branch.kra_targets[0], 'evaluation', $event)" />
            </td>

            <td>
              <input type="number" class="form-control" min="0"
                :value="branch.kra_targets[0]?.['trade_in'] || ''"
                @blur="sanitizeAndHandle(dealer.dealer_group_id, branch.branch_id, branch.kra_targets[0], 'trade_in', $event)" />
            </td>

            <td>
              <input type="number" class="form-control" min="0"
                :value="branch.kra_targets[0]?.purchase || ''"
                @blur="sanitizeAndHandle(dealer.dealer_group_id, branch.branch_id, branch.kra_targets[0], 'purchase', $event)" />
            </td>

            <td>
              <input type="number" class="form-control" min="0"
                :value="branch.kra_targets[0]?.sales || ''"
                @blur="sanitizeAndHandle(dealer.dealer_group_id, branch.branch_id, branch.kra_targets[0], 'sales', $event)" />
            </td>

            <td>
              <input type="number" class="form-control" min="0"
                :value="branch.kra_targets[0]?.overall_sales || ''"
                @blur="sanitizeAndHandle(dealer.dealer_group_id, branch.branch_id, branch.kra_targets[0], 'overall_sales', $event)" />
            </td>
          </tr>
        </template>
      </template>
    </tbody>
  </table>
</div>
  <div class="text-end mt-3">
    <button class="btn btn-dark" @click="saveTargets">Save Targets</button>
  </div>
</div>

`

};