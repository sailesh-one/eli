const [{ ImageViewer }] = await $importComponent([
  '/pages/lib/image-viewer.js'
]);
export const Vehicles = {
  name: 'Vehicles',
  props: { 
    store: { type: Object, required: true },
    isReadOnly: { type: Boolean, required: false, default: false },
  },
  components: { ImageViewer },
  data() {
    return {
      matches: [
        { id: 1, make: '', model: '', year: '', budget: '', stock: null,  modelOptions: [], selectedMatch: null }
      ],
      leadMatches: [],
      selected_table: "",
      selected_response: [],
      currentRow: null,
      isProcessing: false,
      deleteTarget: null,
      validationError: "",
      existingStockCache: [], 
      activeTab: 'interested',
    };
  },
  computed: {
    makeOptions() {
      return this.store?.getOptionsForField('make') || [];
    },
    modelOptions() {
      return this.store?.getOptionsForField('model') || [];
    },
    vehicleConfig() {
      return this.store?.getVehicleConfig || [];
    },
    detailConfig() {
      const d = this.store?.getDetails;
      if (Array.isArray(d)) return d[0] || {};
      return d || {};
    },

    shortlistEmatches() {
      const details = this.store?.getDetails;
      let shortlistData = [];

      if (Array.isArray(details?.shortlisted_vehicles)) {
        shortlistData = details.shortlisted_vehicles;
      } else if (Array.isArray(details) && Array.isArray(details[0]?.shortlisted_vehicles)) {
        shortlistData = details[0].shortlisted_vehicles;
      } else if (details?.shortlisted_vehicles && typeof details.shortlisted_vehicles === "object") {
        shortlistData = [details.shortlisted_vehicles];
      }

      // Just return the grouped data as-is (label + list)
      return shortlistData.map(group => ({
        label: group.label || "Untitled Group",
        type: group.type || "",
        list: Array.isArray(group.list) ? group.list : []
      }));
    },

    groupedShortlist() {
      return this.shortlistEmatches || [];
    },

    intrestedEmatches() {
      const details = this.store?.getDetails;
      if (Array.isArray(details?.interested_vehicles)) {
        return details.interested_vehicles;
      }
      return [];
    },
  },
  watch: {
    intrestedEmatches: {
      immediate: true,
      async handler() {
        await this.resetAddForm();
      }
    },

    shortlistEmatches: {
      immediate: true,
      async handler(ematches) {
        if (ematches && ematches.length) {
          this.existingStockCache = [];
          const fetchedMakes = new Set();
          for (let i = 0; i < ematches.length; i++) {
            const e = ematches[i];
            let makeId = null;
            let modelId = null;
            if (e.make && !isNaN(Number(e.make))) {
              makeId = Number(e.make);
            } else {
              const makeLabel = (e.make_name || e.make || '').toString().trim().toLowerCase();
              const opt = this.makeOptions.find(o => String(o.label).toLowerCase() === makeLabel);
              if (opt) makeId = Number(opt.value);
            }
            // Try to map model name to id from getOptionsForField('model')
            const modelOpts = (this.store?.getOptionsForField('model') || []);
            if (e.model && !isNaN(Number(e.model))) {
              modelId = Number(e.model);
            } else {
              const modelLabel = (e.model_name || e.model || '').toString().trim().toLowerCase();
              const mopt = modelOpts.find(o => String(o.label).toLowerCase() === modelLabel);
              if (mopt) modelId = Number(mopt.value);
            }
            // Create temporary match object to cache stock data
            const tempRow = {
              id: i + 1,
              // set numeric id where available, otherwise keep original string (for matching by name later)
              make: makeId !== null ? makeId : (e.make || e.make_name || ''),
              make_name: e.make_name || e.make || '',
              model: modelId !== null ? modelId : (e.model || e.model_name || ''),
              model_name: e.model_name || e.model || '',
              year: e.year || e.mfg_year || '',
              stock: null,
              modelOptions: modelOpts.slice()
            };
            this.existingStockCache.push(tempRow);
          }
        }
       
      }
    }
  },
  methods: {
    openImg(image) {
      if (!image) return;
      this.$refs.imgViewer.openImages('Front Image', [image]);
    },
    async resetAddForm() {
      this.matches = await this.convertintrestedEmatchesToMatches();
      this.validationError = "";
    },
    addMatch() {
      if (this.matches.length >= 10) {
        this.validationError = "Maximum of 10 rows allowed!";
        return;
      }
      const newId = this.matches.length
        ? this.matches[this.matches.length - 1].id + 1
        : 1;
      this.matches.push({ id: newId, make: '', model: '', mfg_year: '', budget_range: '', stock: null, modelOptions: [], selectedMatch: null });
    },

    modelbyMake(selectedMake) {
      return this.store.dynamic_models(selectedMake);
    },
    async stockByModel(selectedMake, selectedModel, selectedYear, row) {
      try {
        const make = Number(selectedMake);
        const model = selectedModel ? Number(selectedModel) : 0;
        const year = selectedYear ? Number(selectedYear) : 0;
        const budget = row.budget_range ? row.budget_range : 0;
        const id = (this.detailConfig?.id);
        if (isNaN(make)) {
          row.match_counts = null;
          return;
        }
        const stockData = await this.store.dynamic_stock(id, make, model, year, budget );
        row.match_counts = stockData['counts'];
      } catch (err) {
        console.error('Error fetching stock:', err);
      }
    },
    async openDetailedMatches(row, type) {
      if (!row?.match_counts) {
        this.leadMatches = [];
        return;
      }
      const stock_data = await this.store.dynamic_stock(this.detailConfig?.id, row.make, row.model, row.mfg_year, row.budget_range, type);
      this.leadMatches = stock_data?.[type] || [];
      this.selected_table = type;
      this.currentRow = row;
      const modalEl = document.getElementById('detailedMatches');
      new bootstrap.Modal(modalEl).show();
    },

    async addExactMatchSel(id) {
      const leadId = this.detailConfig?.id;
      const numId = id;
      const row = this.currentRow;
      if (!row) return;

      const make = row.make || '';
      const model = row.model || '';
      const year = row.mfg_year || '';
      const budget = row.budget_range || '';
      const type = this.selected_table || '';
      const type_id = numId;

      try {
        const payload = [
          {
            make: String(make),
            model: String(model),
            mfg_year: String(year),
            budget: String(budget),
          },
        ];

        // ✅ Reuse the same common method
        await this.store.addEmatchesItem(leadId, payload, type, type_id);

        await this.getDetail(leadId);
      } catch (err) {
        console.error('Error fetching selected item:', err);
      }
    },

    async convertintrestedEmatchesToMatches() {
      let ematches = this.intrestedEmatches;

      if (ematches && !Array.isArray(ematches)) {
        ematches = [ematches];
      }

      if (!ematches || ematches.length === 0) {
        return [
          { id: 1, make: '', model: '', mfg_year: '', budget_range: '', match_counts: [], modelOptions: [], selectedMatch: null }
        ];
      }

      const matches = [];
      const modelCache = {};

      for (let index = 0; index < ematches.length; index++) {
        const ematch = ematches[index];

        const makeId = ematch.make_id || '';
        const modelId = ematch.model_id || '';
        const mfg_year = String(ematch.mfg_year || ematch.year || '');
        const budget_range = String(ematch.budget_range || ematch.budget || '');
        const row_id = ematch.row_id || '';
        const match_counts = ematch.match_counts || [];

        const numericMake = Number(makeId);
        let modelOptions = [];

        if (numericMake > 0) {
          if (!modelCache[numericMake]) {
            try {
              const maybePromise = this.modelbyMake(numericMake);
              if (maybePromise && typeof maybePromise.then === 'function') {
                await maybePromise;
                modelCache[numericMake] = (this.store.getOptionsForField('model') || []).slice();
              } else {
                modelCache[numericMake] = (this.store?.getOptionsForField('model') || []).slice();
              }
            } catch (err) {
              modelCache[numericMake] = [];
            }
          }
          modelOptions = modelCache[numericMake];
        }

        const row = {
          id: index + 1,
          make: makeId,
          model: modelId,
          mfg_year,    
          budget_range,
          match_counts,
          modelOptions,
          row_id,
          selectedMatch: null,
        };

        matches.push(row);
      }

      return matches;
    },

    removeMatch(index, row) {
      this.deleteTarget = { index, row, type: 'interested' }; 
      const modalEl = document.getElementById('confirmDeleteModal');
      new bootstrap.Modal(modalEl).show();
    },
    
    deleteExactMatch(ematch) {
      this.deleteTarget = { row: ematch, type: 'shortlisted' };
      const modalEl = document.getElementById('confirmDeleteModal');
      new bootstrap.Modal(modalEl).show();
    },
    async confirmDelete() {
      if (this.isProcessing) return;
      if (!this.deleteTarget) return;

      const { index, row, type } = this.deleteTarget;
      const id = (this.detailConfig?.id || 0);
      const modalEl = document.getElementById('confirmDeleteModal');
      const modalInstance =
        bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

      this.isProcessing = true;

      try {
        if (type === 'interested') {
          if (!row.row_id) {
            this.matches.splice(index, 1);
          } else {
            await this.store.deleteIntrestedItem(id, row.row_id);
          }
        } else if (type === 'shortlisted') {
          await this.store.deleteShortlistItem(id, row.row_id);
        }

        await this.$nextTick();
        modalInstance.hide();
        this.deleteTarget = null;
      } catch (err) {
        console.error("Error during deletion:", err);
      } finally {
        this.isProcessing = false;
      }
    },

    async saveMatches() {
      // prevent re-entry
      if (this.isProcessing) return;
      this.isProcessing = true;
      this.validationError = ""; 
      if (!this.detailConfig) {
        $log("No detailConfig found");
        this.isProcessing = false;
        return;
      }
      const leadId = (this.detailConfig?.id);
      for (let row of this.matches) {
        if (!row.make) {
          this.validationError = "Make is required to save an ematch.";
          this.isProcessing = false;
          return;
        }
      }
      const payload = this.matches.map(m => {
        const rowData = {
          make: String(m.make || ""),
          model: String(m.model || ""),
          mfg_year: String(m.mfg_year || ""),
          budget: String(m.budget_range || ""),
        };
        $log("Built row data:", rowData);
        return rowData;
      });
      try {
        if (payload.length) {
          const result = await Promise.resolve(this.store.addEmatchesItem(leadId, payload));
          $log("Save result:", result);
          this.validationError = "";
          this.resetAddForm();
        } else {
          this.validationError = "No valid matches to save.";
        }
      } catch (err) {
        console.error('Error saving ematches:', err);
        this.validationError = "Save failed. Please try again.";
      } finally {
        this.isProcessing = false;
      }
    },
    getFieldOptions(field, row) {
    if (field.fieldKey === 'make') {
      return row.makeOptions?.length ? row.makeOptions : (this.store.getOptionsForField('make') || []);
    }
      if (field.fieldKey === 'model') {
      return row.modelOptions?.length ? row.modelOptions : (this.store.getOptionsForField('model') || []);
    }
    // Standard options for others
    return field.fieldOptions || field.fieldOptionIds || [];
  },
  async handleFieldChange(field, row) {
    const fnName = field.inputChange;
    const value = row[field.fieldKey];

    // If there's an input change handler in config and it exists in the store
    if (fnName && typeof this.store[fnName] === 'function') {
      await this.store[fnName](value);
      // Example: dynamically load models
      if (field.fieldKey === 'make') {
        row.modelOptions = (this.store.getOptionsForField('model') || []).slice();
      }
    }
    this.stockByModel(row.make, row.model, row.mfg_year, row);
  },



  },
  template: /*html*/ `
    <div class="p-2">
      <div class="d-flex align-items-center">
        <ul class="nav nav-pills w-100  d-flex  mb-0" role="tablist">
          <li class="nav-item text-center">
            <button
              class="btn rounded-0 px-5"
              :class="{ active: activeTab === 'interested' }"
              type="button"
              @click="activeTab = 'interested'"
              :style="activeTab === 'interested' ? 'background-color: black; color: white;' : 'background-color: #f1f1f1; color: black;'"

            >
              Interested Cars
            </button>
          </li>

          <li class="nav-item text-center">
            <button
              class="btn rounded-0 px-5"
              :class="{ active: activeTab === 'shortlisted' }"
              type="button"
              @click="activeTab = 'shortlisted'"
              :style="activeTab === 'shortlisted' ? 'background-color: black; color: white;' : 'background-color: #f1f1f1; color: black;'"
            >
              Shortlisted Cars
            </button>
          </li>
        </ul>

      </div>

      <!-- Interested Cars Tab -->
      <div class="container-fluid py-3 bg-light min-vh-100" v-show="activeTab === 'interested'">
        <div class="table-responsive shadow-sm rounded-3 bg-white">
        <table class="table table-bordered">
          <thead class="bg-light text-dark small bg-midgrey1">
            <tr>
              <th align="center">
                <button class="btn p-0 border-0 bg-transparent text-white" @click="addMatch">
                  <i class="bi bi-plus-square fs-4 text-white"></i>
                </button>
              </th>
              <th class="fw-semibold" valign="middle">Make</th>
              <th class="fw-semibold" valign="middle">Model</th>
              <th class="fw-semibold" valign="middle">MFG Year</th>
              <th class="fw-semibold" valign="middle">Budget</th>
              <th class="fw-semibold" valign="middle">Matching Cars</th>
              <th class="fw-semibold" valign="middle">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in matches" :key="row.id">
              <td>{{ row.id }}</td>

              <!-- Dynamic Fields Loop -->
              <td
                v-for="(field, fIdx) in vehicleConfig.fields.filter(f =>
                  ['make', 'model', 'mfg_year', 'budget_range'].includes(f.fieldKey)
                )"
                :key="fIdx"
              >
                <select
                  class="form-select"
                  v-model="row[field.fieldKey]"
                  :disabled="field.fieldKey === 'model' && !row.make"
                  @change="handleFieldChange(field, row)"
                >
                  <option value="">Select {{ field.fieldLabel }}</option>
                  <option
                    v-for="opt in getFieldOptions(field, row)"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label || opt.value }}
                  </option>
                </select>
              </td>

              <!-- Matching Cars -->
              <td>
                <div v-if="row.match_counts && row.match_counts.length" class="d-flex flex-column">
                  <template v-for="(mc, idx) in row.match_counts" :key="idx">
                    <div class="text-nowrap">
                      <span class="me-1 fw-semibold">{{ mc.label }}:</span>
                      <a
                        v-if="mc.count > 0"
                        href="javascript:void(0)"
                        class="text-primary fw-semibold text-decoration-none"
                        @click="openDetailedMatches(row, mc.type)"
                      >
                        {{ mc.count }}
                      </a>
                      <span v-else class="text-muted">{{ mc.count }}</span>
                    </div>
                  </template>
                </div>
                <div v-else class="text-muted small">
                  Select criteria to view matches
                </div>
              </td>

              <!-- Delete -->
              <td align="center">
                <button @click="removeMatch(index, row)" class="btn btn-sm btn-outline-secondary px-2 py-1">
                  <i class="bi bi-trash fs-6"></i>
                </button>
              </td>
            </tr>
          </tbody>

        </table>
      </div>
        <!-- Save Button -->
        <div class="text-end mt-3">
          <p v-if="validationError" class="text-secondary mb-2">{{ validationError }}</p>
          <button
            class="btn btn-dark px-4"
            @click="saveMatches"
            :disabled="isProcessing"
          >
            <span
              v-if="isProcessing"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            <span v-if="isProcessing">SAVING...</span>
            <span v-else>SAVE</span>
          </button>
        </div>
      </div>


      <!-- Shortlisted Cars Tab -->
<div v-show="activeTab === 'shortlisted'" class="container-fluid py-3 bg-light min-vh-100">

  <div v-if="groupedShortlist && groupedShortlist.length" class="shortlist-wrapper">
  <ImageViewer ref="imgViewer" />
    <div
      v-for="(group, idx) in groupedShortlist"
      :key="idx"
      class="card mb-3 border-0 bg-white shadow-sm rounded-3"
    >
      <!-- Header -->
      <div
        class="card-header popup-head d-flex justify-content-between align-items-center"
        role="button"
        data-bs-toggle="collapse"
        :data-bs-target="'#collapse-' + idx"
      >
        <h6 class="mb-0 fw-semibold">
          {{ group.label }} <span class="text-dark small">({{ group.list.length }})</span>
        </h6>
        <i class="bi bi-chevron-down fs-5"></i>
      </div>

      <div
        :id="'collapse-' + idx"
        class="collapse show"
      >
        <div class="card-body py-3 pb-3">
          <div v-if="group.list.length" class="table-responsive shadow-sm rounded-3 bg-white">
            <table class="table table-bordered align-middle text-center">
              <thead class="bg-light text-dark small bg-midgrey1">
                <tr>
                  <th>#</th>
                  <th class="fw-semibold">Branch</th>
                  <th class="fw-semibold">MMV</th>
                  <th class="fw-semibold">Year</th>
                  <th class="fw-semibold">Mileage</th>
                  <th class="fw-semibold">Color</th>
                  <th class="fw-semibold">Fuel</th>
                  <th class="fw-semibold">Listing Price</th>
                  <th class="fw-semibold">Status</th>
                  <th class="fw-semibold">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(item, i) in group.list" :key="i">
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <!-- Show image thumbnail if available -->
                        <div v-if="item.image" class="position-relative">
                          <img
                            :src="item.image"
                            class="img-thumbnail rounded-1 shadow-sm"
                            style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                            @click="openImg(item.image)"
                          />
                        </div>

                        <!-- Fallback placeholder when no image -->
                        <div
                          v-else
                          class="d-flex align-items-center justify-content-center bg-light border rounded"
                          style="width: 60px; height: 60px;"
                        >
                          <i class="bi bi-car-front text-muted opacity-50" style="font-size: 1.5rem;"></i>
                        </div>
                    </td>
                  <td>
                    {{ item.dealer_name }}<br>
                    <small class="text-muted">{{ item.branch_name || '-' }}</small>
                  </td>
                  <td>
                      <div>{{ item.label }}</div>
                    {{ item.make_name }} {{ item.model_name }} {{ item.variant_name }}
                  </td>
                  <td>{{ item.mfg_year }}</td>
                  <td>{{ item.mileage }}</td>
                  <td>{{ item.color_name }}</td>
                  <td>{{ item.fuel_name }}</td>
                  <td>{{ item.listing_price ? '₹ ' + item.listing_price.toLocaleString() : '-' }}</td>
                  <td>{{item.status_name}}</td>
                  <td>
                    <button
                      class="btn btn-outline-danger btn-sm px-2 py-1"
                      title="Remove"
                      @click="deleteExactMatch(item)"
                    >
                      <i class="bi bi-trash fs-6"></i>
                    </button>
                  </td>
                </tr>

                <tr v-if="!group.list || !group.list.length">
                  <td colspan="8" class="text-muted">No records found</td>
                </tr>
              </tbody>
            </table>

          </div>

          <div v-else class="text-center text-muted py-3">
            No shortlisted vehicles in this category.
          </div>
        </div>
      </div>
    </div>
  </div>

  <div v-else class="text-center text-muted mt-3">
    No shortlisted vehicles found.
  </div>
</div>

      
      <!-- Matches Modal -->
      <div class="modal fade" id="detailedMatches" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
          <div class="modal-content border-0 rounded shadow-sm">
            <div class="modal-header popup-head">
              <h2 class="modal-title">Exact Lead Matches</h2>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" style="max-height: 400px; overflow-y: auto;">
              <div class="table-responsive shadow-sm rounded-3 bg-white">
              <table class="table table-bordered align-middle">
                <thead class="bg-light text-dark small bg-midgrey1">
                  <tr>
                    <th class="fw-semibold">Id</th>
                    <th class="fw-semibold">Vehicle Details</th>
                    <th class="fw-semibold" v-if="currentRow">Select</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(lead, index) in leadMatches" :key="lead.id">
                    <td>{{ lead.formatted_inv_id || lead.formatted_id }}</td>
                    <td>
                      <ul class="list-unstyled">
                        <li><strong>{{ lead.dealer_name }}</strong></li>
                        <li><strong>{{ lead.make_name }} {{ lead.model_name }} {{ lead.variant_name }}</strong></li>
                        <li>Current Status: {{lead.status_name}}</li>
                        <li>Mfg Year: {{ lead.mfg_year }}</li>
                        <li>Location: {{ lead.state_name }} - {{ lead.city_name }}</li>
                        <li>Date: {{ lead.created || lead.added_on }}</li>
                        <li v-if="selected_table === 'inventory'">Listing Price: {{ lead.price_selling || lead.listing_price }}</li>
                        <li v-else>Listing Price: {{ lead.price_selling || lead.listing_price }}</li>
                      </ul>
                    </td>
                    <td v-if="currentRow">
                      <button
                        @click="addExactMatchSel(lead.inv_id || lead.id)"
                        class="btn btn-dark px-3"
                        data-bs-dismiss="modal"
                      >
                        SELECT
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm Delete Modal -->
      <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content border-0 shadow-sm rounded-3">
            <div class="modal-header  popup-head">
              <h6 class="modal-title text-dark">Confirm Deletion</h6>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p v-if="deleteTarget?.type === 'interested'" class="mb-0">
                Confirm the deletion of this interested vehicle?
              </p>
              <p v-else-if="deleteTarget?.type === 'shortlisted'" class="mb-0">
                Confirm the deletion of shortlisted car?
              </p>
            </div>
            <div class="modal-footer border-0">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-danger" @click="confirmDelete">Delete</button>
            </div>
          </div>
        </div>
      </div>


    </div>
  `
};
