export default {
  data() {
    return {
      sources_data: [],
      searchQuery: '',
      formData: { source_id: null, subsource_id: null, name: '',pm_flag:'',sm_flag:'', active: 1,is_selected:1},
      isSubsourceForm: false,
      formMode: 'Add',
      formError: '',
      deleteTarget: { type: '', source_id: null, subsource_id: null, name: '',pm_flag:'',sm_flag:'',  active: 1,is_selected:1 },
      isSubmitting: false,
      isSelSubmitting:false
    };
  },

  mounted() {
    this.getSources();
  },

  computed: {
    filteredSources() {
      const q = this.searchQuery.trim().toLowerCase();
      if (!q) {
        return this.sources_data;
      }
      return this.sources_data
        .map(source => {
          const sourceMatches = source.name.toLowerCase().includes(q);
          const filteredSubsources = (source.subsources || []).filter(sub =>
            sub.name.toLowerCase().includes(q)
          );
          if (sourceMatches || filteredSubsources.length) {
            return {
              ...source,
              subsources: filteredSubsources
            };
          }
          return null;
        })
        .filter(Boolean);
    }
  },

  methods: {
    async request(action, data = {}) {
      const cleanedData = { action, ...data };
      Object.keys(cleanedData).forEach(
        key => (cleanedData[key] === null || cleanedData[key] === undefined) && delete cleanedData[key]
      );
      try {
        return await $http('POST', `${g.$base_url_api}/admin/sources`, cleanedData, {});
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async getSources() {
      const res = await this.request('list');
      this.sources_data = (res.body.status === 'ok' && Array.isArray(res.body.data?.list))
        ? res.body.data.list
        : [];
    },

    getActionName(isSub, isAdd) {
      return `${isAdd ? 'add' : 'update'}_${isSub ? 'subsource' : 'source'}`;
    },

    buildPayload(form, isSub) {
      return {
        id: isSub ? form.subsource_id : form.source_id,
        source_id: isSub ? form.source_id : form.source_id,
        name: form.name,
        pm_flag:form.pm_flag,
        sm_flag:form.sm_flag,
        active: form.active,
        is_selected:form.is_selected
      };
    },

    openForm(isSub, sourceId = null, item = null) {
      this.isSubsourceForm = isSub;
      this.formMode = item ? 'Edit' : 'Add';
      this.formError = '';
      this.isSubmitting = false;
    
      this.formData = {
        source_id: isSub ? sourceId : item?.source_id ?? sourceId,
        subsource_id: isSub ? item?.subsource_id ?? null : null,
        name: item?.name ?? '',
        pm_flag:item?.pm_flag ?? '',
        sm_flag:item?.sm_flag ?? '',
        active: item?.active !== undefined ? Number(item.active) : 1,
        is_selected:item?.is_selected ?? 0
      };
    
      $('#sourceFormModal').modal('show');
    },

    async submitForm() {
      this.formError ='';
      if (!this.formData.name) {
        this.formError = 'Name is required.';
        return;
      }
      if ((this.formData.pm_flag==false && this.formData.sm_flag==false) || (this.formData.pm_flag==undefined && this.formData.sm_flag==undefined)) {
        this.formError = 'Please select activate In.';
        return;
      }
      this.isSubmitting = true;
      const action = this.getActionName(this.isSubsourceForm, this.formMode === 'Add');
      const payload = this.buildPayload(this.formData, this.isSubsourceForm);
      const res = await this.request(action, payload);
      this.isSubmitting = false;

      if (res.body.status === 'ok') {
        $toast('success', res.body.msg || 'Successful');
        await this.getSources();
        this.closeModals();
      } else {
        $toast('danger', res.body.msg || 'Failed.');
        this.formError = res.body.message || 'An error occurred.';
      }
    },

    closeModals() {
      this.formData = { source_id: null, subsource_id: null, name: '',pm_flag:'',sm_flag:'', active: 1,is_selected:1};
      this.formError = '';
      this.isSubmitting = false;
      $('#sourceFormModal, #deleteConfirmModal').modal('hide');
    },

    confirmDelete(type, item, parentId = null) {
      this.deleteTarget = {
        type,
        source_id: type === 'source' ? item.source_id : parentId,
        subsource_id: type === 'subsource' ? item.subsource_id : null,
        name: item.name,
        pm_flag:item.pm_flag,
        sm_flag:item.sm_flag,
        active: item.active,
        is_selected:item.is_selected
      };
      this.isSubmitting = false;
      $('#deleteConfirmModal').modal('show');
    },

    async toggleStatus(type, item, active, parentId = null) {
      const isSub = type === 'subsource';
      const payload = {
        id: isSub ? item.subsource_id : item.source_id,
        source_id: isSub ? parentId : item.source_id,
        name: item.name,
        pm_flag:item.pm_flag,
        sm_flag:item.sm_flag,
        active,
        is_selected:item.is_selected
      };
      const res = await this.request(this.getActionName(isSub, false), payload);
      if (res.body.status === 'ok'){ $toast('success', res.body.msg || 'Updated'); await this.getSources(); }
    },

    async handleDeleteConfirmed() {
      this.isSubmitting = true;
      const { type, source_id, subsource_id, name,pm_flag,sm_flag, active } = this.deleteTarget;
      const newStatus = active == 1 ? 0 : 1;
      await this.toggleStatus(type, { source_id, subsource_id, name,pm_flag,sm_flag }, newStatus, source_id);
      this.isSubmitting = false;
      this.closeModals();
    }
  },

  template: `
<div class="container-fluid py-5 bg-light min-vh-100 dealers-modules" id="grid-container">
    <div class="row justify-content-center" id="grid-panel">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h2 class="h4 text-uppercase fw-bold text-muted" id="grid-header">Manage Sources & Subsources</h2>
                <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a new source" id="grid-button" @click="openForm(false)">
                    <i class="bi bi-plus-lg me-1"></i> Add Source
                </button>
            </div>
            <!-- Search -->
            <div class="row mb-3 search-input">
                <div class="col-12 col-md-6 col-sm-10 col-lg-6">
                    <div class="input-group rounded-1 overflow-hidden shadow-sm" id="grid-search-group">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input v-model="searchQuery" type="text" class="form-control border-0 py-3" placeholder="Search sources or subsources..." id="grid-search-input">
                    </div>
                </div>
            </div>
            <!-- Data Cards -->
            <div class="row g-3" v-for="source in filteredSources" :key="source.source_id">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3 overflow-hidden mb-3">
                        <div class="card-body d-flex flex-column flex-md-row gap-3">
                            <span class="badge bg-light text-muted position-absolute top-0 start-1 m-2 p-1 z-1">#{{ source.source_id }}</span>
                            <!-- Source Info -->
                            <div class="p-3 rounded-3 text-center d-flex flex-column position-relative" :class="source.active == 1 ? 'card-inner-active' : 'card-inner-inactive'" style="min-width: 220px;">
                                <h6 class="fw-semibold mb-1 text-truncate">{{ source.name }}</h6>
                                <span :class="{'badge text-bg-success': source.active==1,'badge text-bg-danger': source.active==0}" class="mb-3 position-absolute activebtn">
                                    {{ source.active == 1 ? 'Active' : 'Inactive' }}
                                </span>
                                <div class="mt-auto d-flex gap-2 justify-content-center">
                                    <button class="btn btn-sm btn-outline-secondary rounded-1 py-1 px-3" :title="'Edit source ' + source.name" @click="openForm(false, source.source_id, source)">
                                        <i class="bi bi-pencil fs-5"></i>
                                    </button>
                                    <button v-if="source.active == 1" type="button" class="btn btn-sm btn-outline-success rounded-1 py-1 px-3" @click="confirmDelete('source', source)">
                                        <i class="bi bi-toggle-on fs-5"></i>
                                    </button>
                                    <button v-else type="button" class="btn btn-sm btn-outline-danger rounded-1 py-1 px-3" @click="toggleStatus('source', source, 1)">
                                        <i class="bi bi-toggle-off fs-5"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Subsources -->
                            <div class="flex-grow-1 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-muted fw-semibold">Subsources</h6>
                                    <button class="btn btn-sm btn-light border border-grey rounded-1 px-1 py-0" type="button" :title="'Add a new sub source in ' + source.name" @click="openForm(true, source.source_id)">
                                        <i class="bi bi-plus-lg fs-5"></i>
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap gap-2 p-2 border rounded-1 bg-white">
                                    <template v-if="source.subsources && source.subsources.length">
                                        <div v-for="sub in source.subsources" :key="sub.subsource_id" class="border rounded-1 px-2 py-2 d-flex flex-column text-nowrap" :class="sub.active == 1 ? 'card-inner-active' : 'card-inner-inactive'" style="min-width: 160px; max-width: 240px;">
                                            <div class="d-flex justify-content-between align-items-center w-100">
                                                <span class="fw-semibold">{{ sub.name }}</span>
                                                <div class="btn-group btn-group-sm ms-2">
                                                    <button type="button" class="btn btn-defualt border-0" :title="'Edit sub source ' + sub.name" @click.stop="openForm(true, source.source_id, sub)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button v-if="sub.active == 1" type="button" class="btn btn-defualt text-success border-0" @click.stop="confirmDelete('subsource', sub, source.source_id)">
                                                        <i class="bi bi-toggle-on"></i>
                                                    </button>
                                                    <button v-else type="button" class="btn btn-defualt text-danger border-0" @click.stop="toggleStatus('subsource', sub, 1, source.source_id)">
                                                        <i class="bi bi-toggle-off"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div v-else class="text-muted fst-italic small px-2">
                                        No subsources.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Add/Edit Modal -->
    <div class="modal fade" id="sourceFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header popup-head">
                    <h5 class="modal-title">
                        {{ formMode }} {{ isSubsourceForm ? 'Subsource' : 'Source' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
                        <div class="mb-3">
                            <label class="form-label">{{ isSubsourceForm ? 'Subsource Name' : 'Source Name' }}</label>
                            <input v-model="formData.name" class="form-control" placeholder="Enter name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Activate In</label>
                            <br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" true-value="1" false-value="0" v-model="formData.pm_flag">
                                <label class="form-check-label" for="inlineCheckbox1">PM Module</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox2" true-value="1" false-value="0" v-model="formData.sm_flag">
                                <label class="form-check-label" for="inlineCheckbox2">SM Module</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Is Selcted</label>
                            <br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" v-model="formData.is_selected" :value="1">
                                <label class="form-check-label" for="activeYes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" v-model="formData.is_selected" :value="0">
                                <label class="form-check-label" for="activeNo">No</label>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-0">
                            <button class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-dark rounded-1" @click="submitForm" :disabled="isSubmitting">
                                <span><span v-if="isSubmitting" class="spinner-border spinner-border-sm"></span> {{ formMode }}</span>
                            </button>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-2 border-0 shadow-lg">
                <div class="modal-header popup-head">
                    <h5 class="modal-title fw-bold" :class="deleteTarget.active == 1 ? 'text-danger' : 'text-success'">
            {{ deleteTarget.active == 1 ? 'Confirm Deactivate' : 'Confirm Activate' }}
          </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    Are you sure you want to <span class="fw-semibold">
            {{ deleteTarget.active == 1 ? 'deactivate' : 'activate' }}
          </span> the <span class="fw-semibold text-capitalize">{{ deleteTarget.type }}</span> "
                    <strong>{{ deleteTarget.name }}</strong>"?
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn rounded-1" :class="deleteTarget.active == 1 ? 'btn-danger' : 'btn-success'" @click="handleDeleteConfirmed" :disabled="isSubmitting">
                        <span><span v-if="isSubmitting" class="spinner-border spinner-border-sm"></span> {{ deleteTarget.active == 1 ? 'Deactivate' : 'Activate' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>`
};