export default {
  name: "Vaahan",
  props: {
    store: { type: Object, required: true },
    config: { type: String, required: true },
    field: { type: Object, required: true },
  },
  data() {
    return {
      showConfirm: false,
      fetchedData: null
    };
  },
  methods: {
    async fetchVahanData() {
      if (!this.store || typeof this.store.getVahanDetails !== "function") return;
      const data = await this.store.getVahanDetails(this.config, this.field, null, null, { previewOnly: true });
      if (data) {
        this.fetchedData = data;
        this.showConfirm = true;
      }
    },
    confirmApply() {
      if (!this.fetchedData) return;

      // Push values into fields now
      Object.entries(this.fetchedData).forEach(([k, v]) => {
        this.store.updateFieldValue(this.config, k, v);
      });

      this.$toast?.("success", "Vehicle details applied to form");
      this.showConfirm = false;
    },
    cancelApply() {
      this.showConfirm = false;
      this.fetchedData = null;
    }
  },
  template: `
    <span>
      <button
        class="btn btn-sm rounded-pill btn-outline-secondary ms-1"
        title="Fetch Vahan Data"
        type="button"
        @click="fetchVahanData"
      >
        <i class="bi bi-car-front"></i>
      </button>

      <!-- Preview Modal -->
      <div class="modal fade show" tabindex="-1" v-if="showConfirm" style="display:block; background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content rounded-3 shadow">
            <div class="modal-header">
              <h6 class="modal-title">Vaahan Details Preview</h6>
              <button type="button" class="btn-close" @click="cancelApply"></button>
            </div>
            <div class="modal-body">
              <table class="table table-sm table-bordered">
                <tbody>
                  <tr v-for="(val, key) in fetchedData" :key="key">
                    <th class="text-nowrap">{{ key }}</th>
                    <td>{{ val }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer">
              <button class="btn btn-sm btn-dark" @click="confirmApply">Apply</button>
              <button class="btn btn-sm btn-outline-secondary" @click="cancelApply">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </span>
  `
};
