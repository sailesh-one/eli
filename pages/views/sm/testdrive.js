export const Testdrive = {
  name: 'Testdrive',
  props: { 
    store: { type: Object, required: true },
    isReadOnly: { type: Boolean, required: false, default: false },
  },

  data() {
    return {
      options: {
        places: [
          { value: '1', label: 'Showroom' },
          { value: '2', label: 'Home Visit' },
          { value: '3', label: 'Other' },
          { value: '4', label: 'Event' },
        ],
        statuses: [
          { value: '1', label: 'Scheduled' },
          { value: '2', label: 'Completed' },
          { value: '3', label: 'Cancelled' },
          { value: '4', label: 'No Show' },
        ],
      },
      newDrive: null,
      myConfig: 'testdriveConfig',
    };
  },

  computed: {
    getFieldOptions: vm => field => {
      if (typeof vm.store?.getOptionsForField === 'function') {
        const options = vm.store.getOptionsForField(field.fieldKey || field.key, field);
        if (options?.length) return options;
      }
      return [];
    },
    testDrives: vm => vm.store.detail?.testdrive_vehicles || [],
  },

  methods: {
    addRow() {
      this.newDrive = {
        row_id: '',
        scheduled_date: '',
        vehicle: '',
        test_drive_place: '',
        test_drive_status: '1',
        form_doc: '',
        completed_date: '',
        form_file: null,
        formUploaded: false,
        editing: true,
        _backup: null,
      };
      this.store.dynamic_testdrivelist();
    },

    editRow(item) {
      // backup copy to restore if cancelled
      item._backup = JSON.parse(JSON.stringify(item));
      item.editing = true;
    },

    cancelEdit(item) {
      if (this.newDrive && item.row_id === this.newDrive.row_id) {
        this.newDrive = null;
      } else {
        // restore from backup if available
        if (item._backup) {
          Object.assign(item, item._backup);
          delete item._backup;
        }
        item.editing = false;
      }
    },

    deleteRow(row_id) {
      if (this.newDrive && row_id === this.newDrive.row_id) {
        this.newDrive = null;
        return;
      }
      this.store.detail.testdrive_vehicles = this.store.detail.testdrive_vehicles.filter(
        d => d.row_id !== row_id
      );
    },

    getFileName(path) {
      if (!path) return '';
      return typeof path === 'string' ? path.split('/').pop() : path.name;
    },

    handleFileUpload(event, item) {
      const file = event?.target?.files?.[0];
      if (file) {
        item.form_file = file;          // holds actual File
        item.form_doc = '';             // hide old link immediately
        item.formUploaded = true;
      } else {
        item.form_file = null;
        item.form_doc = '';
        item.formUploaded = false;
      }
    },

    deleteFile(item) {
      item.form_doc = '';
      item.form_file = null;
      item.formUploaded = false;
    },

    openDoc(name, url) {
      if (url) globalThis.$docViewer?.openDoc?.(name, url);
    },

    async saveRow(item) {
      try {
        item.isSaving = true;

        const formrow = {
          row_id: item.row_id || '',
          test_drive_place: item.test_drive_place || '',
          test_drive_status: item.test_drive_status || '',
          scheduled_date: item.scheduled_date || '',
          completed_date: item.completed_date || '',
          test_drive_vehicle: item.vehicle || '',
          form_doc: item.form_file instanceof File ? item.form_file : '',
        };

        if (item.form_file instanceof File) {
          formrow.form_doc = item.form_file;
        }

        const success = await this.store.saveTestdrive(formrow);

        if (success) {
          item.editing = false;
          item._backup = null;
          this.newDrive = null;
          this.store.dynamic_testdrivelist();
        }
      } catch (err) {
        console.error('Error in saveRow:', err);
        $toast('danger', err?.message || 'Failed to update');
      } finally {
        item.isSaving = false;
      }
    },
  },

  template: /*html*/ `
    <div class="container-fluid py-4 bg-light">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Test Drive Vehicles</h2>
        <button v-if="!isReadOnly" type="button" class="btn btn-dark px-4 rounded-2 shadow-sm" @click="addRow">
          <i class="bi bi-plus-lg me-2"></i>Add New Drive
        </button>
      </div>

      <!-- Cards Container -->
      <div class="row g-3">
        <div 
          v-for="item in [...testDrives, newDrive].filter(Boolean)" 
          :key="item.row_id"
          class="col-12"
        >
          <div class="card border-0 shadow-sm rounded-3 overflow-hidden" 
               :class="{ 'border-3 border-warning': newDrive && item.row_id === newDrive.row_id }">
            
            <div class="card-body p-3">
              <div class="row g-2">
                
                <!-- Scheduled -->
                <div class="col-lg-2 col-md-6">
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.65rem;">Scheduled</div>
                  <div v-if="item.editing">
                    <input type="datetime-local" class="form-control form-control-sm rounded-2" v-model="item.scheduled_date" />
                  </div>
                  <div v-else class="d-flex align-items-center">
                    <i class="bi bi-calendar3 text-dark me-1"></i>
                    <span class="small">{{ $formatTime(item.scheduled_date) || '—' }}</span>
                  </div>
                </div>

                <!-- Vehicle -->
                <div class="col-lg-3 col-md-6">
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.65rem;">Vehicle</div>
                  <div v-if="item.editing && newDrive && item.row_id === newDrive.row_id">
                    <SelectSearch
                      v-model="item.vehicle"
                      :options="getFieldOptions({ key: 'test_vehicles_list' })"
                      :searchable="true"
                      placeholder="Select Vehicle"
                      option-label="label"
                      option-value="value"
                    />
                  </div>
                  <div v-else class="d-flex align-items-center">
                    <div class="bg-dark text-white rounded d-flex align-items-center justify-content-center px-2 py-1 me-2">
                      <i class="bi bi-car-front fs-6"></i>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark small">{{ item.make_name || item.vehicle || '—' }}</div>
                      <div class="text-muted small">{{ item.model_name || '' }}, {{ item.variant_name || '' }}</div>
                    </div>
                  </div>
                </div>

                <!-- Place -->
                <div class="col-lg-2 col-md-6">
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.65rem;">Place</div>
                  <div v-if="item.editing">
                    <SelectSearch
                      v-model="item.test_drive_place"
                      :options="options.places"
                      placeholder="Select Place"
                      option-label="label"
                      option-value="value"
                    />
                  </div>
                  <div v-else>
                    <i class="bi bi-geo-alt text-dark me-1"></i>
                    <span class="small">{{ item.test_drive_place_name || '—' }}</span>
                  </div>
                </div>

                <!-- Status -->
                <div class="col-lg-1 col-md-6">
                  <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.65rem;">Status</div>
                  <div v-if="item.editing">
                    <SelectSearch
                      v-model="item.test_drive_status"
                      :options="options.statuses"
                      placeholder="Select Status"
                      option-label="label"
                      option-value="value"
                    />
                  </div>
                  <div v-else>
                    <span class="badge bg-dark rounded-pill px-2 small">{{ item.test_drive_status_name || '—' }}</span>
                  </div>
                </div>

                <!-- Form -->
              <div class="col-lg-2 col-md-6">
                <div class="text-muted small text-uppercase fw-semibold" style="font-size:0.65rem;">Form</div>

                <!-- EDIT MODE -->
                <div v-if="item.editing">
                  <!-- If form exists and no new file selected -->
                  <div v-if="item.form_doc && typeof item.form_doc === 'string' && !item.form_file" class="d-flex align-items-center justify-content-between gap-2 p-2 border rounded bg-light">
                    <button type="button" class="btn btn-link p-0 small text-truncate" @click="openDoc('Test Drive Form', item.form_doc)">
                      <i class="bi bi-link-45deg me-1"></i>Open Form
                    </button>
                    <button type="button" class="btn btn-defualt btn-sm p-0" title="Delete File" @click="deleteFile(item)">
                      <i class="bi bi-x" style="line-height: 14px; font-size: 20px !important;"></i>
                    </button>
                  </div>

                  <!-- If no file exists OR file deleted -->
                  <div v-else class="input-group">
                    <input
                      type="file"
                      class="form-control form-control-sm shadow-none py-1 px-2"
                      accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                      @change="handleFileUpload($event, item)"
                    />
                    <span class="input-group-text bg-white"><i class="bi bi-upload"></i></span>
                  </div>
                </div>

                <!-- VIEW MODE -->
                <div v-else>
                  <template v-if="item.form_doc && typeof item.form_doc === 'string'">
                    <button type="button" class="btn btn-link text-decoration-underline p-0 small"
                            @click="openDoc('Test Drive Form', item.form_doc)">
                      <i class="bi bi-link-45deg me-1"></i>Open Form
                    </button>
                  </template>
                  <template v-else>
                    <i class="bi bi-file-earmark text-muted fs-6"></i>
                  </template>
                </div>
              </div>

                <!-- Actions -->
                <div class="col-lg-2 col-md-12 d-flex align-items-center justify-content-end">
                  <div v-if="!item.editing" class="d-flex gap-1">
                    <button v-if="!isReadOnly" class="btn btn-outline-dark px-2 py-1" style="width:32px;height:32px;" @click="editRow(item)">
                      <i class="bi bi-pencil"></i>
                    </button>
                  </div>

                  <div v-else class="d-flex gap-1 flex-nowrap">
                    <button type="button" class="btn btn-sm btn-dark rounded-2 px-2" :disabled="item.isSaving" @click="saveRow(item)">
                      <span v-if="item.isSaving" class="spinner-border spinner-border-sm me-1"></span>
                      <i v-else class="bi bi-check-lg me-1"></i>{{ item.isSaving ? 'Saving' : 'Save' }}
                    </button>
                    <button v-if="newDrive && item.row_id === newDrive.row_id" type="button"
                            class="btn btn-outline-danger px-2 py-1 ms-1"  @click="deleteRow(item.row_id)">
                      <i class="bi bi-trash fs-6"></i>
                    </button>
                    <button v-else type="button"
                            class="btn btn-sm btn-outline-danger px-2 py-1" @click="cancelEdit(item)">
                      <i class="bi bi-x-lg fs-6"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Completed Date -->
              <div v-if="item.editing || item.completed_date" class="row mt-2 pt-2">
                <div class="col-lg-3 col-md-6">
                  <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:0.65rem;">Completed</div>
                  <div v-if="item.editing">
                    <input type="datetime-local" class="form-control form-control-sm rounded-2" v-model="item.completed_date" />
                  </div>
                  <div v-else class="d-flex align-items-center">
                    <i class="bi bi-check-circle text-success me-1"></i>
                    <span class="small">{{ $formatTime(item.completed_date) || '—' }}</span>
                  </div>
                </div>
              </div>
              
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="testDrives.length === 0 && !newDrive" class="col-12 text-center py-5">
          <i class="bi bi-car-front display-1 text-muted opacity-25"></i>
          <p class="text-muted mt-3">No test drives scheduled yet</p>
          <button v-if="!isReadOnly" type="button" class="btn btn-dark px-4" @click="addRow">
            <i class="bi bi-plus-lg me-2"></i>Schedule First Drive
          </button>
        </div>
      </div>
    </div>
  `,
};

