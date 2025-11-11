// Admin-Interiors component (JS module style)
// Replace your existing component with this. It uses the same template structure you provided earlier.

const template = /*html*/`
<div class="container-fluid py-3 bg-light min-vh-100">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h2 class="h4 text-uppercase fw-bold text-dark mb-3 mb-md-0">Manage Interiors</h2>
        <button type="button" class="btn btn-dark rounded-1 shadow-sm px-4 py-2" @click="addUser">
            <i class="bi bi-person-plus me-1"></i>  Add Color
        </button>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold text-muted">Per page:</span>
            <select class="form-select form-select-sm" v-model="perPage" @change="changePerPage" style="width: auto;">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        <div class="text-muted">
            Displaying {{from_count}} to {{to_count}} of {{totalCount}}
        </div>

        <div class="d-flex gap-2">
            <button type="button" @click="prevPage" :disabled="currentPage == 1" class="btn btn-outline-dark btn-sm">Prev</button>
            <button type="button" @click="nextPage" :disabled="currentPage == totalPages" class="btn btn-outline-dark btn-sm">Next</button>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content shadow-sm border-0 rounded-3">
                <div class="modal-header popup-head text-light">
                    <h5 class="modal-title" id="userModalLabel">{{isEditModal ? 'Edit Interior Color' : 'Add Interior Color'}}</h5>
                    <button type="button" class="btn-close btn-close-dark" @click="closeUserModal"></button>
                </div>

                <div class="modal-body">
                    <span v-if="validationErrors['other_error']" class="text-danger">{{validationErrors['other_error']}}</span>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Make</label>
                        <select class="form-select" v-model="user_data.make">
                            <option value="">--Select Make--</option>
                            <option v-for="make in makesList" :key="make.id" :value="String(make.id)">{{ make.make }}</option>
                        </select>
                        <span v-if="validationErrors['make']" class="text-danger">{{validationErrors['make']}}</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Interior Color</label>
                        <input class="form-control" type="text" v-model="user_data.interior_color" placeholder="Enter Interior color"/>
                        <span v-if="validationErrors['interior_color']" class="text-danger">{{validationErrors['interior_color']}}</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Base Color</label>
                        <input class="form-control" type="text" v-model="user_data.base_color" placeholder="Enter base color"/>
                        <span v-if="validationErrors['base_color']" class="text-danger">{{validationErrors['base_color']}}</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold me-3">Active:</label>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="active" value="y" v-model="user_data.active" id="active" />
                          <label class="form-check-label" for="active">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="active" value="n" v-model="user_data.active" id="inactive"/>
                          <label class="form-check-label" for="inactive">No</label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-1 px-4" @click="closeUserModal">Cancel</button>
                    <button type="button" class="btn btn-dark rounded-1 px-4" @click="saveInteriorColor">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive shadow-sm rounded-3 bg-white">
        <table class="table table-hover table-bordered mb-0 text-center align-middle">
            <thead class="bg-light text-dark small bg-midgrey1">
                <tr>
                    <th>ID</th>
                    <th>Make</th>
                    <th>Interior Color</th>
                    <th>Base Color</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row,index) in tableList" :key="index">
                    <td>{{row.id}}</td>
                    <td>{{row.make}}</td>
                    <td>{{row.interior_color}}</td>
                    <td>{{row.base_color}}</td>
                    <td>
                        <span class="badge rounded-pill" :class="row.active == 'y' ? 'bg-success' : 'bg-danger'">{{row.active == 'y' ? 'Active' : 'Inactive'}}</span>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-1 me-2 px-2 py-1" @click="editUser(row)"><i class="bi bi-pencil fs-6"></i></button>
                    </td>
                </tr>
                <tr v-if="tableList.length === 0">
                    <td colspan="6" class="text-muted py-4">No users found</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
`;

export default {
    name: 'Admin-Users',
    template,
    data() {
        return {
            table_headers : ["id","Make","Interior Color","Base color","active"],
            tableList : [],
            makesList : [],
            user_data:{
                id : '',           
                make : '',      
                interior_color : '',
                base_color : '',
                active : 'y'
            },
            currentPage:1,
            perPage :10,
            from_count :0,
            to_count : 0,
            totalCount:0,
            validationErrors: {},
            isEditModal:false,
        }
    },
    mounted() {
        this.getMakes();
        this.getInteriorColors();
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalCount / this.perPage) || 1;
        }
    },
    methods: {
        async getMakes() {
            try {
                const data = { action: 'getmakes' };
                const response = await $http('POST', `${g.$base_url_api}/admin/colors`, { ...data }, {});
                if (response?.body?.status === "ok") {
                    this.makesList = response.body.data.makes || [];
                } else {
                    this.makesList = [];
                }
            } catch (err) {
                console.error('getMakes error', err);
                this.makesList = [];
            }
        },

        async getInteriorColors() {
            try {
                const data = { action: 'getinteriorcolors', perPage: this.perPage, page: this.currentPage };
                const response = await $http('POST', `${g.$base_url_api}/admin/colors`, { ...data }, {});
                if (response?.body?.status === "ok") {
                    const d = response.body.data;
                    this.tableList = d.interior_colors || [];
                    this.from_count = d.start_count || 0;
                    this.to_count = d.end_count || 0;
                    this.totalCount = d.total || 0;
                } else if (response?.body?.status === "empty") {
                    this.tableList = [];
                    this.totalCount = 0;
                } else {
                    this.tableList = [];
                    this.totalCount = 0;
                }
            } catch (err) {
                console.error('getInteriorColors error', err);
                this.tableList = [];
                this.totalCount = 0;
            }
        },

        addUser() {
            this.isEditModal = false;
            this.user_data = { id: '', make: '', interior_color: '', base_color: '', active: 'y' };
            this.validationErrors = {};
            this.openUserModal();
        },

        editUser(row) {
            this.isEditModal = true;
            this.user_data = {
                id: row.id,
                make: String(row.make_id ?? (row.make === 'Other' ? 0 : '')), // row.make_id preferred, fallback
                interior_color: row.interior_color,
                base_color: row.base_color,
                active: row.active
            };
            this.validationErrors = {};
            this.openUserModal();
        },

        async saveInteriorColor() {
            this.validationErrors = {};
            if (this.user_data.make === '' && this.user_data.make !== '0') {
                this.validationErrors['make'] = 'Make is required';
            }
            if (!this.user_data.interior_color) {
                this.validationErrors['interior_color'] = 'Interior color is required';
            }
            if (!this.user_data.base_color) {
                this.validationErrors['base_color'] = 'Base color is required';
            }
            if (this.user_data.make === '0' && this.user_data.interior_color !== this.user_data.base_color) {
                this.validationErrors['other_error'] = 'Base color and interior color be the same for Other makes';
            }

            if (Object.keys(this.validationErrors).length > 0) {
                return;
            }

            const isUpdate = this.isEditModal && this.user_data.id;
            const action = isUpdate ? 'updateinteriorcolor' : 'saveinteriorcolor';
            const payload = {
                action,
                make: this.user_data.make,
                interior_color: this.user_data.interior_color,
                base_color: this.user_data.base_color,
                active: this.user_data.active
            };
            if (isUpdate) payload.id = this.user_data.id;

            try {
                const response = await $http('POST', `${g.$base_url_api}/admin/colors`, { ...payload }, {});
                if (response?.body?.status === 'ok') {
                    this.getInteriorColors();
                    this.closeUserModal();
                } else if (response?.body?.status === 'fail' || response?.body?.status === 'fail') {
                    this.validationErrors['other_error'] = response.body.msg || 'Save failed';
                } else if (response?.body?.status === 'fail' && response.body?.data?.errors) {
                    this.validationErrors = response.body.data.errors;
                } else if (response?.body?.status === 'fail') {
                    this.validationErrors['other_error'] = response.body.msg|| 'Save failed';
                }
            } catch (err) {
                console.error('saveInteriorColor error', err);
                this.validationErrors['other_error'] = err.body.msg;
            }
        },

        openUserModal() {
            $("#userModal").modal('show');
        },
        closeUserModal() {
            $("#userModal").modal('hide');
        },

        changePerPage(e) {
            this.perPage = parseInt(e.target.value, 10);
            this.currentPage = 1;
            this.getInteriorColors();
        },
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.getInteriorColors();
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.getInteriorColors();
            }
        }
    }
}
