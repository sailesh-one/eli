export const Roles = {
  name: 'Roles',

  data() {
    return {
      roleSearch: '',
      moduleSearch: '',
      roles: [],
      modules: [],
      rolepermissions: [],

      selectedRoleId: null,
      selectedModules: [],
      selectedSubmodules: [],

      showRoleModal: false,
      isEditMode: false,
      roleForm: this.getDefaultRoleForm(),
      roleFormError: '',
      errors: {},
      isSubmitting: false,

      showDeleteConfirm: false,
      deleteTarget: this.getDefaultDeleteTarget(),
      deleteError: '',

      // New state for permission confirmation
      showPermissionConfirm: false,
      permissionToggleTarget: this.getDefaultPermissionTarget(),
      permissionToggleError: '',
    };
  },


  props: {
    role_type: {
      type: Number,
      default: 0,
    },
  },

  mounted() {
    this.getModules();
  },

  computed: {
    filteredRoles() {
      return this.roles.filter(role =>
        role.role_name?.toLowerCase().includes(this.roleSearch.toLowerCase())
      );
    },

    filteredModules() {
      return this.modules.filter(mod =>
        mod.module_name?.toLowerCase().includes(this.moduleSearch.toLowerCase())
      );
    },
  },

  methods: {
    // defaults
    getDefaultRoleForm() {
      return {
        role_id: '',
        role_name: '',
        description: '',
        active: 'y',
        role_main: 'n'
      };
    },
    getDefaultDeleteTarget() {
      return {
        role_id: null,
        role_name: '',
        active: 'y',
        role_main: 'n'
      };
    },
    getDefaultPermissionTarget() {
      return {
        role_id: null,
        module_id: null,
        submodule_id: null,
        name: '',
        isActive: false
      };
    },

    async request(action, data = {}) {
      try {
        const basePath = this.role_type ? 'dealer-roles' : 'admin-roles';
        return await $http('POST', `${g.$base_url_api}/admin/${basePath}`, { action, ...data }, {});
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async getModules() {
      const res = await this.request('list', {});
      if (res.body.status === 'ok') {
        this.modules = res.body.data['modules'];
        this.rolepermissions = res.body.data['permissions'] || [];
        this.roles = res.body.data['roles'] || [];
      }
    },
    
    // role ui
    addRole() {
      this.isEditMode = false;
      this.roleForm = this.getDefaultRoleForm();
      this.roleFormError = '';
      this.errors = {};
      this.showRoleModal = true;
      this.$nextTick(() => $('#roleFormModal').modal('show'));
    },
    editRole(role) {
      this.isEditMode = true;
      this.roleForm = { ...role };
      this.roleFormError = '';
      this.errors = {};
      this.showRoleModal = true;
      this.$nextTick(() => $('#roleFormModal').modal('show'));
    },

    // save/update role
    async submitRoleForm() {
      this.roleFormError = '';
      this.errors = {};
      this.isSubmitting = true;

      const action = this.isEditMode ? 'edit' : 'add';
      const payload = { ...this.roleForm, form_action: action };

      try {
        const res = await this.request(action, payload);
        if (res.body.status === 'ok') {
          this.closeRoleFormModal();
          await this.getModules();
        } else {
          this.roleFormError = res.body.msg || 'An error occurred.';
          this.errors = res.body.errors || {};
        }
      } catch {
        this.roleFormError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },

    closeRoleFormModal() {
      this.roleFormError = '';
      this.errors = {};
      this.roleForm = this.getDefaultRoleForm();
      this.showRoleModal = false;
      $('#roleFormModal').modal('hide');
    },

    closeDeleteConfirmModal() {
      this.deleteTarget = this.getDefaultDeleteTarget();
      this.showDeleteConfirm = false;
      this.deleteError = '';
      $('#deleteConfirmModal').modal('hide');
    },
    
    closePermissionConfirmModal() {
        this.permissionToggleTarget = this.getDefaultPermissionTarget();
        this.showPermissionConfirm = false;
        this.permissionToggleError = '';
        $('#permissionConfirmModal').modal('hide');
    },

    // delete/toggle role
    confirmDeleteRole(role) {
      this.deleteError = '';
      this.deleteTarget = {
        role_id: role.id,
        role_name: role.role_name,
        active: role.active,
        role_main: role.role_main
      };
      this.showDeleteConfirm = true;
      this.$nextTick(() => $('#deleteConfirmModal').modal('show'));
    },
    async handleDeleteConfirmed() {
      this.isSubmitting = true;
      this.deleteError = '';
      const { role_id, active } = this.deleteTarget;
      const newActive = active === 'y' ? 'n' : 'y';
      
      const role = this.roles.find(r => r.id === role_id);
      const payload = { ...role, active: newActive, form_action: 'updaterole' };
      
      try {
        const res = await this.request('edit', payload);
        if (res.body.status === 'ok') {
          await this.getModules();
          this.closeDeleteConfirmModal();
        } else {
          this.deleteError = res.body.msg || 'Failed to update status.';
        }
      } catch {
        this.deleteError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },

    selectRole(roleId) {
      this.selectedRoleId = roleId;
      const currentRolePermissions = this.rolepermissions.filter(
        p => p.role_id === roleId
      );
      this.selectedModules = [
        ...new Set(currentRolePermissions.map(p => p.module_id))
      ];
      this.selectedSubmodules = [
        ...new Set(currentRolePermissions.map(p => p.submodule_id))
      ];
    },
    
    // New methods for permission toggling with confirmation
    confirmPermissionToggle(target) {
        this.permissionToggleError = '';
        this.permissionToggleTarget = target;
        this.showPermissionConfirm = true;
        this.$nextTick(() => $('#permissionConfirmModal').modal('show'));
    },
    async handlePermissionToggleConfirmed() {
        this.isSubmitting = true;
        this.permissionToggleError = '';
        
        const { role_id, module_id, submodule_id, isActive } = this.permissionToggleTarget;
        const isChecked = !isActive; // The action is the opposite of the current state

        try {
            const res = await this.updateRolePermission({ role_id, module_id, submodule_id, isChecked });
            
            if (res.body.status === 'ok') {
                // Manually update state upon success
                if (submodule_id) {
                    const idx = this.selectedSubmodules.indexOf(submodule_id);
                    if (isChecked && idx === -1) this.selectedSubmodules.push(submodule_id);
                    else if (!isChecked && idx > -1) this.selectedSubmodules.splice(idx, 1);
                } else {
                    const idx = this.selectedModules.indexOf(module_id);
                    if (isChecked && idx === -1) this.selectedModules.push(module_id);
                    else if (!isChecked && idx > -1) this.selectedModules.splice(idx, 1);
                }
                this.closePermissionConfirmModal();
            } else {
                this.permissionToggleError = res.body.msg || 'Failed to update permission.';
            }
        } catch {
            this.permissionToggleError = 'Request failed. Please try again.';
        } finally {
            this.isSubmitting = false;
        }
    },
    async updateRolePermission({ role_id, module_id = null, submodule_id = null, isChecked }) {
      const payload = { role_id };
      if (module_id !== null) payload.module_id = module_id;
      if (submodule_id !== null) payload.submodule_id = submodule_id;
      
      const actionType = isChecked ? 'addrolepermission' : 'removerolepermission';
      try {
        const res = await this.request(actionType, payload);
        if (res.body.status !== 'ok') {
          console.error('Failed to update role permission');
           alert(`Failed to ${isChecked ? 'add' : 'remove'} permission.`);
        }
        await this.getModules(); 
        return res; // Return the response for the handler
      } catch (error) {
        console.error('Error updating role permission:', error);
        return error;
      }
    }
  },

  template: `
<div class="container-fluid py-5 bg-light min-vh-100 dealers-modules" id="grid-container">
  <div class="row justify-content-center" id="grid-panel">
    <div class="col-lg-11 col-xl-10">

      <!-- Header -->
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">
        <h2 class="h4 text-uppercase fw-bold text-muted mb-3 mb-sm-0"> 
          {{ role_type == 1 ? 'Dealer' : 'Admin' }} Roles Management
        </h2>
        <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a New Role" @click="addRole">
          <i class="bi bi-plus-lg me-1"></i> Add Role
        </button>
      </div>

      <!-- Search -->
      <div class="row mb-3">
        <div class="col-12 col-md-8 col-lg-6 search-input">
          <div class="input-group rounded-1 overflow-hidden shadow-sm">
            <span class="input-group-text bg-white border-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input v-model="roleSearch" type="text" class="form-control border-0 py-2" placeholder="Search roles by name...">
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Roles Panel -->
        <div class="col-lg-5">
          <div class="card shadow-sm border-0 rounded-1 h-100">
            <div class="card-body">
               <h2 class="mb-3 text-muted">System Roles</h2>
              <ul class="list-group list-group-flush">
                <li v-for="role in filteredRoles" :key="role.id"
                    class="list-group-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between rounded-1 mb-2 p-2 gap-3 shadow-sm"
                    :class="{ 'bg-dark text-white': selectedRoleId === role.id, 'bg-light': selectedRoleId !== role.id }"
                    style="cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease;"
                    @click="selectRole(role.id)">
                  
                  <div class="flex-grow-1">
                    <div class="fw-semibold">{{ role.role_name }} <span v-if="role.role_main == 'y'" class="badge text-bg-success mt-10 text-12"> Main </span></div>
                    <small :class="selectedRoleId === role.id ? 'text-white-50' : 'text-muted'">{{ role.description }}</small>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                     <button class="btn btn-sm rounded-1 fs-6 py-1 px-2" :class="selectedRoleId === role.id ? 'btn-outline-light' : 'btn-outline-secondary'" title="Edit Role" @click.stop="editRole(role)">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button v-if="role.active == 'y'" class="btn btn-sm btn-outline-success rounded-1 me-2 px-1 py-0" @click.stop="confirmDeleteRole(role)" title="Deactivate Role" :disabled='role.description=="super-admin"'>
                      <i class="bi bi-toggle-on fs-5"></i>
                    </button>
                    <button v-else class="btn btn-sm btn-outline-danger rounded-1 me-2 px-1 py-0" @click.stop="confirmDeleteRole(role)" title="Activate Role">
                      <i class="bi bi-toggle-off fs-5"></i>
                    </button>
                  </div>
                </li>
                 <li v-if="!filteredRoles.length" class="list-group-item border-0">
                    <span class="text-muted fst-italic p-2"> No roles found. </span>
                 </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Modules Panel -->
        <div class="col-lg-7">
          <div v-if="selectedRoleId" class="card shadow-sm border-0 rounded-1 h-100">
            <div class="card-body">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
                  <h2 class="mb-0 text-muted">Assign Permissions</h2>
                   <div class="input-group rounded-1 overflow-hidden shadow-sm w-50 w-sm-auto">
                     <span class="input-group-text bg-white border-1 border-light py-1"> <i class="bi bi-search text-muted small"></i> </span>
                     <input v-model="moduleSearch" placeholder="Search modules..." class="form-control form-control-sm border-1 border-light" />
                  </div>
              </div>

              <div class="p-2 border rounded-1 bg-white" style="max-height: 500px; overflow-y: auto;">
                <div v-for="module in filteredModules" :key="module.module_id" class="mb-2 p-2 rounded-1" :class="selectedModules.includes(module.module_id) ? 'bg-light' : ''" style="transition: background-color 0.2s ease;">
                  <div class="d-flex align-items-center">
                     <button v-if="selectedModules.includes(module.module_id)" class="btn btn-sm btn-outline-success border-0 rounded-1 fs-4 py-1 px-2 lh-1 me-0" @click="confirmPermissionToggle({ role_id: selectedRoleId, module_id: module.module_id, name: module.module_name, isActive: true })">
                         <i class="bi bi-toggle-on lh-1"></i>
                     </button>
                     <button v-else class="btn btn-sm btn-outline-danger rounded-1 border-0 fs-4 py-1 px-2 lh-1 me-0" @click="confirmPermissionToggle({ role_id: selectedRoleId, module_id: module.module_id, name: module.module_name, isActive: false })">
                         <i class="bi bi-toggle-off lh-1"></i>
                     </button>
                    <label class="fw-semibold" style="cursor: default;">{{ module.module_name }}</label>
                  </div>

                  <div v-if="selectedModules.includes(module.module_id) && module.submodules.length" class="ms-4 mt-2 border-start ps-3">
                    <div v-for="sub in module.submodules" :key="sub.submodule_id" class="d-flex align-items-center mb-2">
                        <button v-if="selectedSubmodules.includes(sub.submodule_id)" class="btn btn-sm btn-outline-success rounded-1 border-0 fs-4 py-1 px-2 lh-1 me-0" @click="confirmPermissionToggle({ role_id: selectedRoleId, module_id: module.module_id, submodule_id: sub.submodule_id, name: sub.submodule_name, isActive: true })">
                            <i class="bi bi-toggle-on lh-1"></i>
                        </button>
                        <button v-else class="btn btn-sm btn-outline-danger rounded-1 border-0 fs-4 py-1 px-2 lh-1 me-0" @click="confirmPermissionToggle({ role_id: selectedRoleId, module_id: module.module_id, submodule_id: sub.submodule_id, name: sub.submodule_name, isActive: false })">
                            <i class="bi bi-toggle-off lh-1"></i>
                        </button>
                        <label style="cursor: default;">{{ sub.submodule_name }}</label>
                    </div>
                  </div>
                </div>
                 <div v-if="!filteredModules.length" class="p-2">
                    <span class="text-muted fst-italic"> No modules match your search. </span>
                 </div>
              </div>
            </div>
          </div>
          <div v-else class="d-flex align-items-center justify-content-center bg-light h-100 rounded-1 border">
            <div class="text-center text-muted">
                <i class="bi bi-hand-index-thumb fs-1"></i>
                <p class="mt-2">Select a role to view and assign permissions.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Role Modal -->
  <div class="modal fade" id="roleFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-1 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title">{{ isEditMode ? 'Edit Role' : 'Add Role' }}</h5>
          <button type="button" class="btn-close" @click="closeRoleFormModal"></button>
        </div>
        <div class="modal-body">
          <div v-if="roleFormError" class="alert alert-danger small">{{ roleFormError }}</div>
          <div class="mb-3">
            <label class="form-label">Role Name</label>
            <input v-model="roleForm.role_name" class="form-control" placeholder="Enter role name">
            <small v-if="errors.role_name" class="text-danger">{{ errors.role_name }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea v-model="roleForm.description" class="form-control" placeholder="Enter description" :readonly="role_type == 0 && roleForm.role_main == 'y'"></textarea>
             <small v-if="errors.description" class="text-danger">{{ errors.description }}</small>
          </div>
          <div v-if="isEditMode" class="mb-3">
            <label class="form-label">Active</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="roleForm.active" value="y" class="form-check-input" id="activeYes" :disabled="role_type == 0 && roleForm.role_main == 'y'"> 
              <label class="form-check-label" for="activeYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="roleForm.active" value="n" class="form-check-input" id="activeNo" :disabled="role_type == 0 && roleForm.role_main == 'y'"> 
              <label class="form-check-label" for="activeNo">No</label>
            </div>
          </div>
          <label class="form-label">Main Role</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="roleForm.role_main" value="y" class="form-check-input" id="roleMainYes"> 
              <label class="form-check-label" for="roleMainYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="roleForm.role_main" value="n" class="form-check-input" id="roleMainNo" > 
              <label class="form-check-label" for="roleMainNo">No</label>
            </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary rounded-1" @click="closeRoleFormModal" :disabled="isSubmitting">Cancel</button>
          <button class="btn btn-dark rounded-1" @click="submitRoleForm" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Role Confirm Modal -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-1 border-0 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title fw-bold text-danger">Confirm {{ deleteTarget.active === 'y' ? 'Deactivation' : 'Activation' }}</h5>
          <button type="button" class="btn-close" @click="closeDeleteConfirmModal"></button>
        </div>
        <div class="modal-body py-4">
          Are you sure you want to <span class="fw-semibold">{{ deleteTarget.active === 'y' ? 'deactivate' : 'activate' }}</span> the role "<strong>{{ deleteTarget.role_name }}</strong>"?
          <div v-if="deleteError" class="alert alert-danger small mt-3 mb-0">{{ deleteError }}</div>
        </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-outline-secondary rounded-1" @click="closeDeleteConfirmModal">Cancel</button>
          <button type="button" class="btn btn-danger rounded-1" @click="handleDeleteConfirmed" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ deleteTarget.active === 'y' ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Permission Toggle Confirm Modal -->
  <div class="modal fade" id="permissionConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-1 border-0 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title fw-bold text-danger">Confirm {{ permissionToggleTarget.isActive ? 'Deactivation' : 'Activation' }}</h5>
          <button type="button" class="btn-close" @click="closePermissionConfirmModal"></button>
        </div>
        <div class="modal-body py-4">
          Are you sure you want to <span class="fw-semibold">{{ permissionToggleTarget.isActive ? 'deactivate' : 'activate' }}</span> the permission for "<strong>{{ permissionToggleTarget.name }}</strong>"?
          <div v-if="permissionToggleError" class="alert alert-danger small mt-3 mb-0">{{ permissionToggleError }}</div>
        </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-outline-secondary rounded-1" @click="closePermissionConfirmModal">Cancel</button>
          <button type="button" class="btn btn-danger rounded-1" @click="handlePermissionToggleConfirmed" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ permissionToggleTarget.isActive ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  
</div>
`
};