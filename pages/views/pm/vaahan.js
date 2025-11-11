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
      fetchedData: null,
      loading: false
    };
  },
  methods: {
    async fetchVahanData() {
      if (!this.store || typeof this.store.getVahanDetails !== "function") return;

      this.loading = true;
      try {
        const data = await this.store.getVahanDetails(
          this.config,
          this.field,
          null,
          null
        );

        if (data) {
          this.fetchedData = data;
          this.showConfirm = true;
        }
      } catch (err) {
        console.error("Error fetching Vahan data:", err);
        this.$toast?.("error", "Failed to fetch vehicle details");
      } finally {
        this.loading = false;
      }
    },
    confirmApply() {
      if (!this.fetchedData) return;

      // Call store method to apply Vahan data to form
      this.store.applyVahanDataToForm();

      this.showConfirm = false;
    },
    cancelApply() {
      this.showConfirm = false;
      this.fetchedData = null;
    },
    formatKey(key) {
      // Remove special characters, add spaces before capital letters, and capitalize first letters
      return key
        .replace(/[^a-zA-Z0-9]/g, ' ')        // replace non-alphanumerics with space
        .replace(/([a-z])([A-Z])/g, '$1 $2') // split camelCase
        .replace(/\s+/g, ' ')                 // collapse multiple spaces
        .trim()
        .replace(/\b\w/g, c => c.toUpperCase()); // capitalize each word
    }
  },
  template: `
    <span>
      <button
        class="btn btn-sm rounded-1 btn-outline-secondary ms-1 py-1 fs-5"
        title="Fetch Vahan Data"
        type="button"
        @click="fetchVahanData"
        :disabled="loading"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm" role="status"></span>
        <i v-else class="bi bi-car-front"></i>
      </button>

      <div class="modal fade show" tabindex="-1" v-if="showConfirm" style="display:block; background:rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content rounded-3 shadow">
            <div class="modal-header popup-head">
              <h2 class="modal-title">Vaahan Details Preview</h2>
              <button type="button" class="btn-close" @click="cancelApply"></button>
            </div>
            <div class="modal-body pb-0">
              <table class="table table-sm table-bordered">
                <tbody>
                  <tr v-for="(val, key) in fetchedData" :key="key">
                    <td class="text-nowrap fw-semibold">{{ formatKey(key) }}</td>
                    <td>{{ val }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="modal-footer border-0">
              <button class="btn btn-sm btn-dark" @click="confirmApply">Apply</button>
              <button class="btn btn-sm btn-outline-secondary" @click="cancelApply">Cancel</button>
            </div>
          </div>
        </div>
      </div>
    </span>
  `
};
