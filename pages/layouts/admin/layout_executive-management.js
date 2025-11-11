export default {
  data() {
    return {
      dealersList: [],
      roles: [],
      dealerships: [],
      branches: [], 
      search: {
        search_data: '',
        role: '',
        dealership: ''
      },
      perPage: 10,
      currentPage: 1,
      totalCount: 0,
      start_count: 0,
      end_count: 0,
      showModal: false,
      modalDealer: {
        id: '',
        name: '',
        email: '',
        mobile: '',
        role_id: '',
        dealership_id: '',
        branch_ids: [],
        active: 'y',
      },
      formErrors : {
             name : "",
             email : "",
             mobile : "",
             role_id : "",
             dealership_id : "",
             branch_ids : "",
             active : "y"
      },
      isEdit: false,
      toggleDealer: null,

    };
  },
  mounted() {
    this.getdealers();
  },
  methods: {
    async request(action, data = {}) {
      try {
        const res = await $http(
          'POST',
          `${g.$base_url_api}/admin/executive-management`,
          { action, ...data },
          {}
        );
        return res;
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async getdealers() {
      const res = await this.request('get', {
        perPage: this.perPage,
        page: this.currentPage,
        search: this.search
      });
      console.log('getdealers response:', res);
      if (res.body.status === 'ok'){
        let data = res.body.data || {};

        this.dealersList = data?.leads?.leads || [];
        console.log('Loaded dealersList:', this.dealersList)
        this.totalCount  = parseInt(data?.leads?.total) || 0;
        this.start_count = parseInt(data?.leads?.start_count) || 0;
        this.end_count   = parseInt(data?.leads?.end_count) || 0;

        this.roles       = data?.roles?.roles || [];
        this.dealerships = data?.dealerships?.dealers || [];
      } else {
        this.dealersList = [];
        this.roles = [];
        this.dealerships = [];
        this.totalCount = 0;
      }
    },

    async loadBranches(dealershipId) {
      if (!dealershipId) {
        this.branches = [];
        this.modalDealer.branch_ids = [];
        return;
      }

      const res = await $http("POST", `${g.$base_url_api}/master-data`, {
         action: "getBranches",
         dealership_id: dealershipId,
      }, {});
      if (res.body.status === 'ok') {
        this.branches = res.body.data.branches || [];
        console.log('Loaded branches:', this.branches)
      } else {
        this.branches = [];
      }
    },

    // Search + Pagination
    onSearch() {
      this.currentPage = 1;
      this.getdealers();
    },
    changePerPage(e) {
      this.perPage = parseInt(e.target.value, 10);
      this.currentPage = 1;
      this.getdealers();
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
        this.getdealers();
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.getdealers();
      }
    },
    editDealer(dealer) {
      console.log('Editing dealer:', dealer);
      const branchIds = dealer.branches ? dealer.branches.map(b => b.id) : [];
      this.modalDealer = { ...dealer,  branch_ids: branchIds };
      this.isEdit = true;
      this.showModal = true;
      this.formErrors = [];
      this.loadBranches(this.modalDealer.dealership_id);
    },
    addDealer() {
      this.formErrors = [];
      this.modalDealer = { id: '', name: '', email: '', mobile: '', role_id: '', dealership_id: '',  branch_ids: [], active: 'y' };
      this.branches = [];
      this.isEdit = false;
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
    },

    async saveDealer() {
      const action = this.isEdit ? 'edit' : 'add';
      const payload = {
        id: this.modalDealer.id,
        name: this.modalDealer.name,
        email: this.modalDealer.email,
        mobile: this.modalDealer.mobile,
        role_id: this.modalDealer.role_id,
        dealership_id: this.modalDealer.dealership_id,
        active: this.modalDealer.active,
        branch_ids: this.modalDealer.branch_ids
      };

      const res = await this.request(action, payload);

      if (res.body.status === 'ok') {
        this.getdealers(); 
        this.closeModal();
        this.formErrors = [];
      } 
      else
      {
        this.formErrors = res.body.errors;
      }
    },

    openToggleModal(dealer) {
      this.toggleDealer = dealer;
      $('#toggleConfirmModal').modal('show');
    },

    async confirmToggle() {
      if (!this.toggleDealer) return;
      const payload = {
        id: this.toggleDealer.id,
        name: this.toggleDealer.name,
        email: this.toggleDealer.email,
        mobile: this.toggleDealer.mobile,
        role_id: this.toggleDealer.role_id,
        dealership_id: this.toggleDealer.dealership_id,
        branch_ids: this.toggleDealer.branches ? this.toggleDealer.branches.map(b => b.id) : [],
        active: this.toggleDealer.active === 'y' ? 'n' : 'y',
      };

      const res = await this.request('edit', payload);

      if (res.body.status === 'ok') {
        this.getdealers();
        this.closeModal();
      } else {
        this.formErrors = res.body.errors;
      }

      $('#toggleConfirmModal').modal('hide');
      this.toggleDealer = null;
    },
    cancelToggle() {
      $('#toggleConfirmModal').modal('hide');
      this.toggleDealer = null;
    },
    resetFields()
    {
       this.search.search_data = "";
       this.search.role = "";
       this.search.dealership = "";
       this.getdealers();
    }
  },
  computed: {
    totalPages() {
      return Math.ceil(this.totalCount / this.perPage) || 1;
    },
    startCount() {
      return (this.currentPage - 1) * this.perPage + 1;
    },
    endCount() {
      return Math.min(this.currentPage * this.perPage, this.totalCount);
    }
  },
  
  template: `
 <div class="container-fluid py-5 bg-light min-vh-100" id="grid-container">
  <div class="row justify-content-center" id="grid-panel">
    <div class="col-lg-10">

      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h4 text-uppercase fw-bold text-muted">Manage Executives</h2>
        <button @click="addDealer" class="btn btn-dark btn-sm rounded-pill shadow-sm">
          <i class="bi bi-person-add me-1"></i> Add Executive
        </button>
      </div>

      <!-- Search Row -->
      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <div class="input-group rounded-pill shadow-sm overflow-hidden">
            <span class="input-group-text bg-white border-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input v-model="search.search_data"
                   @keyup.enter="onSearch"
                   class="form-control border-0 py-2"
                   placeholder="Search users...">
          </div>
        </div>
        <div class="col-md-2">
          <select v-model="search.role" id="searchRole" class="form-select shadow-sm rounded-pill">
            <option value="">-- Select Role --</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.role_name }}
            </option>
          </select>
        </div>
        <div class="col-md-2">
          <select v-model="search.dealership" id="searchDealership" class="form-select shadow-sm rounded-pill">
            <option value="">-- Select Dealership --</option>
            <option v-for="dealer in dealerships" :key="dealer.id" :value="dealer.id">
              {{ dealer.name }}
            </option>
          </select>
        </div>
        <div class="col-md-2">
          <button @click="onSearch" class="btn btn-dark w-100 fw-bold rounded-pill shadow-sm">
            Search
          </button>
        </div>
        <div class="col-md-2">
          <button class="btn btn-dark rounded-pill text-light w-50 fw-bold" @click="resetFields">Reset</button>
        </div>
      </div>

   <!-- Toolbar -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
        <div class="d-flex align-items-center gap-2">
          <label class="form-label mb-0 text-muted">Per page:</label>
          <select v-model="perPage" @change="changePerPage" class="form-select form-select-sm shadow-sm rounded-pill w-auto">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
          </select>
        </div>

        <div class="text-muted small">
          Showing <span class="fw-semibold">{{ start_count }}</span> - <span class="fw-semibold">{{ end_count }}</span> of <span class="fw-semibold">{{ totalCount }}</span>
        </div>

        <div class="btn-group shadow-sm">
          <button @click="prevPage" :disabled="currentPage === 1" class="btn btn-outline-dark btn-sm rounded-start-pill">
            <i class="bi bi-chevron-left"></i> Prev
          </button>
          <button @click="nextPage" :disabled="currentPage === totalPages" class="btn btn-outline-dark btn-sm rounded-end-pill">
            Next <i class="bi bi-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Data Table -->
      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted text-uppercase small">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Mobile</th>
                  <th>Dealership</th>
                  <th>Branches</th>
                  <th>Role</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="dealer in dealersList" :key="dealer.id">
                  <td>{{ dealer.id }}</td>
                  <td class="fw-semibold">{{ dealer.name }}</td>
                  <td>{{ dealer.email }}</td>
                  <td>{{ dealer.mobile }}</td>
                  <td>{{ dealer.dealership_name }}</td>
                  <td>
                    <div v-if="dealer.branches.length > 0">
                      <div v-for="branch in dealer.branches" :key="branch.id">
                        {{ branch.branch_name }}
                      </div>
                    </div>
                  </td>
                  <td><span class="badge bg-secondary rounded-pill">{{ dealer.role_name }}</span></td>
                  <td class="text-center">
                    <div class="d-flex gap-2 justify-content-center">
                      <!-- Edit -->
                      <button @click="editDealer(dealer)"
                              class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center"
                              style="width: 32px; height: 32px;">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <!-- Toggle -->
                     <button class="btn btn-sm btn-link p-0"
                              :class="dealer.active === 'y' ? 'text-success' : 'text-danger'"
                              @click="openToggleModal(dealer)"
                              :title="dealer.active === 'y' ? 'Deactivate Dealer' : 'Activate Dealer'">
                        <i :class="dealer.active === 'y' ? 'bi bi-toggle-on fs-4' : 'bi bi-toggle-off fs-4'"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="dealersList.length === 0">
                  <td colspan="7" class="text-center py-4 text-muted">No data found</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>


      <!-- Modal -->
      <div v-if="showModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center;">
        <div style="background: white; padding: 24px; border-radius: 8px; width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
          <h3 style="margin-bottom: 16px;">{{ isEdit ? 'Edit User' : 'Add New User' }}</h3>
          <div style="margin-bottom: 12px;">
            <label>Name:</label>
            <input v-model="modalDealer.name" style="width: 100%; padding: 8px; border: 1px solid #ccc;" @input="formErrors.name = ''" />
            <div v-if="formErrors.name != ''" class="text-danger">{{formErrors.name}}</div>
          </div>
          <div style="margin-bottom: 12px;">
            <label>Email:</label>
            <input v-model="modalDealer.email" style="width: 100%; padding: 8px; border: 1px solid #ccc;" @input="formErrors.email = ''" />
            <div v-if="formErrors.email != ''" class="text-danger">{{formErrors.email}}</div>
            </div>
          <div style="margin-bottom: 12px;">
            <label>Mobile:</label>
            <input v-model="modalDealer.mobile" style="width: 100%; padding: 8px; border: 1px solid #ccc;" @input="formErrors.mobile = ''" />
            <div v-if="formErrors.mobile != ''" class="text-danger">{{formErrors.mobile}}</div>
          </div>
          <div class="mb-3">
            <label for="addRole">Select Role:</label>
            <select v-model="modalDealer.role_id" id="addRole" class="form-select" @change="formErrors.role_id = ''" required>
              <option value="">-- Select Role --</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">
                {{ role.role_name }}
              </option>
            </select>
            <div v-if="formErrors.role_id != ''" class="text-danger">{{formErrors.role_id}}</div>
          </div>
          <div class="mb-3">
            <label for="addRole">Select Dealership:</label>
            <select v-model="modalDealer.dealership_id" id="addDealership" @change="formErrors.dealership_id = ''; loadBranches(modalDealer.dealership_id)"
                     class="form-select" required>
              <option value="">-- Select Dealership --</option>
              <option v-for="dealership in dealerships" :key="dealership.id" :value="dealership.id">
                {{ dealership.name }}
              </option>
            </select>
            <div v-if="formErrors.dealership_id != ''" class="text-danger">{{formErrors.dealership_id}}</div>
          </div>

          <div class="mb-3">
            <label for="addBranches">Select Branches:</label>
            <select v-model="modalDealer.branch_ids" 
                    id="addBranches" 
                    class="form-select" 
                    multiple
                    @change="formErrors.branch_ids = ''">
              <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
            <div v-if="formErrors.branch_ids" class="text-danger">{{ formErrors.branch_ids }}</div>
          </div>

          <div style="margin-bottom: 12px;">
            <label>Active:</label><br>
            <label><input type="radio" value="y" v-model="modalDealer.active" /> Yes</label>
            <label style="margin-left: 12px;"><input type="radio" value="n" v-model="modalDealer.active" /> No</label>
          </div>
          <div style="text-align: right;">
            <button @click="closeModal" style="margin-right: 8px; padding: 6px 12px;">Cancel</button>
            <button @click= "saveDealer" style="padding: 6px 12px; background: #0e0f0f; color: #fff;">Save</button>
          </div>
        </div>
      </div>

      <div class="modal fade" id="toggleConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
              <h5 class="modal-title fw-bold" :class="toggleDealer?.active === 'y' ? 'text-danger' : 'text-success'">
                Confirm {{ toggleDealer?.active === 'y' ? 'Deactivate' : 'Activate' }}
              </h5>
              <button type="button" class="btn-close" @click="cancelToggle"></button>
            </div>
            <div class="modal-body pt-2">
              Are you sure you want to {{ toggleDealer?.active === 'y' ? 'deactivate' : 'activate' }} this dealer?
            </div>
            <div class="modal-footer border-0 pt-2">
              <button type="button" class="btn btn-outline-secondary rounded-pill" @click="cancelToggle">Cancel</button>
              <button type="button" class="btn rounded-pill" :class="toggleDealer?.active === 'y' ? 'btn-danger' : 'btn-success'" @click="confirmToggle">
                {{ toggleDealer?.active === 'y' ? 'Deactivate' : 'Activate' }}
              </button>
            </div>
          </div>
        </div>
      </div>
  `
};
