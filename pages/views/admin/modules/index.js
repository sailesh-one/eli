const [{ModuleRoles}] = await $importComponent([
  '/pages/components/common/common_module_roles.js'
]);


export const Modules = {
  data() {
    return {
      modules_data: [],
      roles: [],
      expandedModuleId: null,
      searchQuery: '',
      formData: this.getDefaultFormData(),
      isSubmoduleForm: false,
      formMode: 'Add',
      formError: '',
      errors: {},
      showModal: false,
      showDeleteConfirm: false,
      deleteTarget: this.getDefaultDeleteTarget(),
      currentModuleName: '',
      isSubmitting: false,
      selectedModule: null,
    };
  },
  components: { ModuleRoles },
  props: {
    is_dealer: { type: Number, default: 0 },
  },

  mounted() {
    this.getModules();
  },

  methods: {
    // defaults
    getDefaultFormData() {
      return {
        category_name: '',
        name: '',
        url: '',
        module_id: null,
        submodule_id: null,
        active: 'y',
        is_visible: 0,
        icon: '',
        editIndex: null,
      };
    },
    getDefaultDeleteTarget() {
      return {
        type: '',
        category_name: '',
        name: '',
        url: '',
        module_id: null,
        submodule_id: null,
        icon: '',
        active: 'y'
      };
    },

    async request(action, data = {}) {
      try {
        const basePath = this.is_dealer ? 'dealer-modules' : 'admin-modules';
        return await $http('POST', `${g.$base_url_api}/admin/${basePath}`, { action, ...data }, {});
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    // api
    async getModules() {
      const res = await this.request('getmodules', {});
      if (res.body.status === 'ok' && res.body?.data?.modules) {
        this.modules_data = res.body.data.modules;
      }
    },

    // module ui
    toggleModule(moduleId) {
      this.expandedModuleId = this.expandedModuleId === moduleId ? null : moduleId;
    },
    addModule() {
      this.formData = this.getDefaultFormData();
      this.isSubmoduleForm = false;
      this.formMode = 'Add';
      this.showModal = true;
      this.$nextTick(() => $('#moduleFormModal').modal('show'));
    },
    editModule(index) {
      const mod = this.filteredModules[index];
      this.formData = {
        category_name: mod.category_name || '',
        name: mod.module_name,
        url: mod.module_url,
        module_id: mod.module_id,
        active: mod.active || 'y',
        is_visible: String(mod.is_visible),
        icon: mod.icon || '',
        editIndex: index,
      };
      this.isSubmoduleForm = false;
      this.formMode = 'Edit';
      this.showModal = true;
      this.$nextTick(() => $('#moduleFormModal').modal('show'));
    },

    // submodule ui
    addSubmodule(moduleId) {
      const module = this.modules_data.find(m => m.module_id === moduleId);
      this.currentModuleName = module?.module_name || '';
      this.formData = { ...this.getDefaultFormData(), module_id: moduleId };
      this.isSubmoduleForm = true;
      this.formMode = 'Add';
      this.showModal = true;
      this.$nextTick(() => $('#moduleFormModal').modal('show'));
    },
    editSubmodule(sub, moduleId, index) {
      this.formData = {
        ...this.getDefaultFormData(),
        name: sub.submodule_name,
        url: sub.submodule_url,
        module_id: moduleId,
        submodule_id: sub.submodule_id,
        active: sub.active || 'y',
        editIndex: index,
      };
      this.isSubmoduleForm = true;
      this.formMode = 'Edit';
      this.showModal = true;
      this.$nextTick(() => $('#moduleFormModal').modal('show'));
    },

    // save/update
    async submitForm() {
      this.formError = '';
      this.errors = {};
      this.isSubmitting = true; // ✅ start loader

      const f = this.formData;
      const action = this.isSubmoduleForm
        ? (this.formMode === 'Add' ? 'addsubmodule' : 'updatesubmodule')
        : (this.formMode === 'Add' ? 'addmodule' : 'updatemodule');

      const payload = {
        category_name: f.category_name,
        name: f.name,
        url: f.url,
        active: f.active,
        is_visible: f.is_visible,
        icon: f.icon,
        form_action: action,
      };

      if (!this.isSubmoduleForm) {
        if (this.formMode !== 'Add') {
          payload.module_id = f.module_id;
        }
      } else {
        payload.module_id = f.module_id;
        if (this.formMode !== 'Add') {
          payload.submodule_id = f.submodule_id;
        }
      }

      try {
        const res = await this.request('savemodules', payload);
        if (res.body.status === 'ok') {
          this.closeFormModal();
          await this.getModules(); // refresh
        } else {
          this.formError = res.body.msg || 'An error occurred.';
          this.errors = res.body.errors || {};
        }
      } catch {
        this.formError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },


    closeFormModal() {
      this.formError = '';
      this.errors = {};
      this.formData = this.getDefaultFormData();
      this.deleteTarget = this.getDefaultDeleteTarget();
      this.showModal = false;
      this.showDeleteConfirm = false;
      $('#moduleFormModal').modal('hide');
      $('#deleteConfirmModal').modal('hide');
    },

    // delete/toggle
    confirmDeleteModule(module) {
      this.deleteTarget = {
        type: 'module',
        category_name: module.category_name,
        name: module.module_name,
        url: module.module_url,
        module_id: module.module_id,
        submodule_id: null,
        active: module.active,
      };
      this.showDeleteConfirm = true;
      this.$nextTick(() => $('#deleteConfirmModal').modal('show'));
    },
    confirmDeleteSubmodule(sub, moduleId) {
      this.deleteTarget = {
        type: 'submodule',
        name: sub.submodule_name,
        url: sub.submodule_url,
        module_id: moduleId,
        submodule_id: sub.submodule_id,
        active: sub.active,
      };
      this.showDeleteConfirm = true;
      this.$nextTick(() => $('#deleteConfirmModal').modal('show'));
    },
    async handleDeleteConfirmed() {
      this.isSubmitting = true;
      const { type, category_name, name, url, module_id, submodule_id, active } = this.deleteTarget;
      const action = type === 'module' ? 'updatemodule' : 'updatesubmodule';
      const newActive = active === 'y' ? 'n' : 'y';

      const payload = { 
        category_name, 
        name, 
        url, 
        module_id, 
        submodule_id: type === 'module' ? '' : submodule_id,
        active: newActive, 
        form_action: action 
      };

      try {
        const res = await this.request('savemodules', payload);
        if (res.body.status === 'ok') {
          await this.getModules();
          this.closeFormModal();
        } else {
          this.formError = res.body.msg || 'Failed to update status.';
        }
      } catch {
        this.formError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },
    openModuleRoles(get, type, id, name) {
      this.$refs.ModuleRolesOffcanvas.open({ get, type, id, name });
    },
    cardIcon(icon) {
      if (icon) {
        return `bi bi-${icon}`;
      }
      return "bi bi-grid";
    },
  },

  computed: {
    filteredModules() {
      const q = this.searchQuery.toLowerCase();
      return this.modules_data.filter(
        m => m.module_name.toLowerCase().includes(q) || m.module_url.toLowerCase().includes(q)
      );
    },
  },

  template: `
<div class="container-fluid py-5 bg-light min-vh-100 dealers-modules"  id="grid-container">
  <div class="row justify-content-center"  id="grid-panel">
    <div class="col-lg-10">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="text-uppercase fw-bold text-muted">
          {{ is_dealer == 1 ? 'Dealer' : 'Admin' }} Modules
        </h2>
        <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a New Module" id="grid-button" @click="addModule">
          <i class="bi bi-plus-lg me-1"></i> Add Module </button>
      </div>

        <!-- Search -->
      <div class="row mb-3">
        <div class="col-12 col-md-6 col-sm-10 col-lg-6 search-input">
          <div class="input-group rounded-1 overflow-hidden shadow-sm" id="grid-search-group">
            <span class="input-group-text bg-white border-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input v-model="searchQuery" type="text" class="form-control border-0 py-3" placeholder="Search module name or slug..." id="grid-search-input">
          </div>
        </div>
      </div>


      <!-- Data Cards -->
      <div class="row g-3" v-for="(module, index) in filteredModules" :key="module.module_id">
        <div class="col-12">
          <div class="card shadow-sm border-0 rounded-1 overflow-hidden mb-3">
            <div class="card-body d-flex flex-column flex-md-row gap-3">
              <!-- Module Info -->
              <span class="badge bg-light text-muted position-absolute top-0 start-1 m-2 p-1 z-1">#{{ module.module_id }}</span>
              <div class="p-3 bg-white border rounded-1 text-center position-relative d-flex flex-column shadow-sm" :class="module.active == 'y' ? 'card-inner-active' : 'card-inner-inactive'" style="min-width: 220px;">
              <span class="badge rounded-1 text-bg-secondary fw-medium mb-2 align-self-center">{{ module.category_name }}</span>
                <h6 class="fw-bold mb-1 d-flex align-items-center justify-content-center gap-2">
                   <i :class="[cardIcon(module.icon), 'text-dark']"></i>
                  {{ module.module_name }}
                </h6>
                <small class="text-muted mb-2">/{{ module.module_url }}</small>
                <span :class="{'badge text-bg-success': module.active=='y','badge text-bg-danger': module.active=='n'}" class="mb-3 position-absolute activebtn">
                  {{ module.active == 'y' ? 'Active' : 'Inactive' }}
                </span>
                <div class="mt-auto d-flex gap-2 justify-content-center">
                  <button class="btn btn-sm btn-outline-secondary rounded-1 py-1 px-3" :title="'View Roles for ' + module.module_name" @click="openModuleRoles('roles', 'module', module.module_id, module.module_name)">
                    <i class="bi bi-person-badge fs-5"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-secondary rounded-1 py-1 px-3" :title="'Edit Module- ' + module.module_name" @click="editModule(index)">
                    <i class="bi bi-pencil fs-5"></i>
                  </button>
                  <button v-if="module.active == 'y'" class="btn btn-sm btn-outline-success rounded-1 py-1 px-3" @click="confirmDeleteModule(module)">
                    <i class="bi bi-toggle-on fs-5"></i>
                  </button>
                  <button v-else class="btn btn-sm btn-outline-danger rounded-1 py-1 px-3" @click="confirmDeleteModule(module)">
                    <i class="bi bi-toggle-off fs-5"></i>
                  </button>
                </div>
              </div>
              <!-- Submodules -->
              <div class="flex-grow-1 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0 text-muted">Submodules</h6>
                  <button class="btn btn-outline-secondary d-flex align-items-center rounded-circle py-1 px-2 fs-5 add-plus" :title="'Add Sub module to ' + module.module_name" @click="addSubmodule(module.module_id)">
                    <i class="bi bi-plus-lg"></i>
                  </button>
                </div>
                <div class="d-flex flex-wrap gap-2 p-2 border rounded-1 bg-white">
                  <div v-for="(sub, subIndex) in module.submodules" :key="sub.submodule_id" class="rounded-1 px-3 py-2 d-flex flex-column text-nowrap shadow-sm" :class="sub.active == 'y' ? 'card-inner-active' : 'card-inner-inactive'" style="min-width: 160px; max-width: 240px;">
                    <!-- Submodule Name + Actions -->
                    <div class="d-flex justify-content-between align-items-center w-100">
                      <span class="fw-semibold">{{ sub.submodule_name }}</span>
                      <div class="btn-group btn-group-sm ms-2">
                        <button class="btn btn-defualt p-1 border-0" :title="'Edit SubModule- ' + sub.submodule_name" @click.stop="editSubmodule(sub, module.module_id, subIndex)">
                          <i class="bi bi-pencil fs-6"></i>
                        </button>
                        <button v-if="sub.active=='y'" class="btn btn-defualt p-1 border-0" @click.stop="confirmDeleteSubmodule(sub, module.module_id)">
                          <i class="bi bi-toggle-on fs-5"></i>
                        </button>
                        <button v-else class="btn btn-defualt p-1 border-0" @click.stop="confirmDeleteSubmodule(sub, module.module_id)">
                          <i class="bi bi-toggle-off fs-5"></i>
                        </button>
                      </div>
                    </div>
                    <!-- Submodule URL -->
                    <small class="text-muted">/{{ sub.submodule_url }}</small>
                  </div>
                  <span v-if="!module.submodules || !module.submodules.length" class="text-muted fst-italic p-2"> No submodules found </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Add/Edit Modal -->
  <div class="modal fade" id="moduleFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-1 shadow-lg">
        <!-- Modal Header -->
        <div class="modal-header popup-head">
          <h5 class="modal-title">
            {{ formMode }} {{ isSubmoduleForm ? 'Submodule' : 'Module' }}
            <span v-if="isSubmoduleForm && currentModuleName" class="text-muted small ms-2"> for <strong>{{ currentModuleName }}</strong>
            </span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" @click="closeFormModal"></button>
        </div>
        <!-- Modal Body -->
        <div class="modal-body">
          <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
          <div v-if="!isSubmoduleForm" class="mb-3">
            <label class="form-label">Category Name</label>
            <input v-model="formData.category_name" class="form-control" placeholder="Enter Category">
            <small v-if="errors.category_name" class="text-danger">{{ errors.category_name }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ isSubmoduleForm ? 'Submodule' : 'Module' }} Name</label>
            <input v-model="formData.name" class="form-control" placeholder="Enter name">
            <small v-if="errors.name" class="text-danger">{{ errors.name }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ isSubmoduleForm ? 'Action' : 'URL / Slug' }}</label>
            <input v-model="formData.url" class="form-control" placeholder="Enter slug or URL">
            <small v-if="errors.url" class="text-danger">{{ errors.url }}</small>
          </div>
          <div v-if="!isSubmoduleForm" class="mb-3">
            <label class="form-label">Is Visible at Home</label>
            <br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" v-model="formData.is_visible" value="1" id="visibleYes">
              <label class="form-check-label" for="visibleYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" v-model="formData.is_visible" value="0" id="visibleNo">
              <label class="form-check-label" for="visibleNo">No</label>
            </div>
          </div>
          <div v-if="!isSubmoduleForm" class="mb-3">
            <label class="form-label">Icon (Icon Name)</label>
            <input v-model="formData.icon" class="form-control" placeholder="e.g. house, grid, gear">
            <small class="text-muted">Enter the icon name without prefix. Example: <code>grid</code> → <i class="bi bi-grid"></i></small>
            <small v-if="errors.icon" class="text-danger">{{ errors.icon }}</small>
          </div>
          <div v-if="formMode == 'Edit'" class="mb-3">
            <label class="form-label">Active</label>
            <br>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" v-model="formData.active" value="y" id="activeYes">
              <label class="form-check-label" for="activeYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" v-model="formData.active" value="n" id="activeNo">
              <label class="form-check-label" for="activeNo">No</label>
            </div>
          </div>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer">
          <button class="btn btn-outline-secondary rounded-1" data-bs-dismiss="modal" @click="closeFormModal" :disabled="isSubmitting">
            Cancel
          </button>
          <button class="btn btn-dark rounded-1" @click="submitForm" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal: Delete Confirm -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-1 border-0 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title fw-bold text-danger"> Confirm {{ deleteTarget.active === 'y' ? 'Deactivate' : 'Activate' }}
          </h5>
          <button type="button" class="btn-close" @click="closeFormModal"></button>
        </div>
        <div class="modal-body py-4"> Are you sure you want to <span class="fw-semibold">{{ deleteTarget.active === 'y' ? 'deactivate' : 'activate' }}</span> the <span class="fw-semibold text-capitalize">{{ deleteTarget.type }}</span> " <strong>{{ deleteTarget.name }}</strong>"? </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-outline-secondary rounded-1" @click="closeFormModal">Cancel</button>
          <button type="button" class="btn btn-danger rounded-1" @click="handleDeleteConfirmed" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ deleteTarget.active === 'y' ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<ModuleRoles ref="ModuleRolesOffcanvas" :module="selectedModule" />
` 
};