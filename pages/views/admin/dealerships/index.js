export const Dealerships = {
    data() {
        return {
            dealershipsList: [],
            search: { search_data: '' },
            delete_id: null,
            delete_action: null,
            perPage: 10,
            currentPage: 1,
            totalCount: 0,
            start_count: 0,
            end_count: 0,
            msg: '',

            formData: { id: null, name: '', short_name: '', website_url: '' },
            formDataErrors: {},
            isEditMode: false,
            exportLoading: false,
        };
    },
    props: {
        branch_id: { type: [String, Number, null], default: null },
        dealer_id: { type: [String, Number, null], default: null },
        fieldGroups: { type: Array, default: () => [] }
    },
    mounted() {
        this.getdealerships();
    },
    methods: {
        goTo(page) {
            $routeTo(`dealerships/${page}`);
        },
        async getdealerships() {
            const res = await this.request('getdealerships', {
                perPage: this.perPage,
                page: this.currentPage,
                search: this.search
            });
            if (res.body.status === 'ok' && res.body.data.dealers?.length > 0) {
                this.dealershipsList = res.body.data.dealers || [];
                this.totalCount = parseInt(res.body.data.total) || 0;
                this.start_count = parseInt(res.body.data.start_count) || 0;
                this.end_count = parseInt(res.body.data.end_count) || 0;
            } else {
                this.dealershipsList = [];
                this.totalCount = 0;
            }
        },

        openDealerForm() {
            this.isEditMode = false;
            this.msg = '';
            this.formDataErrors = {};
            this.formData = { id: null, name: '', short_name: '', website_url: '' };
            $('#formModal').modal('show');
        },
        openEditDealer(dealer) {
            this.isEditMode = true;
            this.msg = '';
            this.formDataErrors = {};
            this.formData = {
                id: dealer.id,
                name: dealer.name,
                short_name: dealer.short_name,
                website_url: dealer.website_url || ''
            };
            $('#formModal').modal('show');
        },
        async saveDealer() {
            this.formDataErrors = {}
            if (!this.formData.name) {
                this.formDataErrors.name = "Name is required";
            } else if (!/^[a-zA-Z\s]+$/.test(this.formData.name)) {
                this.formDataErrors.name = "Name must contain only letters and spaces";
            }
            if (!this.formData.short_name) {
                this.formDataErrors.short_name = "Short Name is required";
            } else if (!/^[a-zA-Z0-9_-]+$/.test(this.formData.short_name)) {
                this.formDataErrors.short_name = "Short Name must be alphanumeric (letters, numbers, _ or - allowed)";
            }
            if (this.formData.website_url) {
                const urlPattern = /^[a-zA-Z0-9_-]+$/;
                if (!urlPattern.test(this.formData.website_url)) {
                    this.formDataErrors.website_url = "Website URL is not valid";
                }
            }
            if (Object.keys(this.formDataErrors).length > 0) {
                return;
            }

            const action = this.isEditMode ? 'update' : 'add';
            const payload = {
                sub_action: 'dealer_group',
                name: this.formData.name,
                short_name: this.formData.short_name,
                website_url: this.formData.website_url
            };
            if (this.isEditMode) {
                payload.id = this.formData.id;
            }

            try {
                const res = await this.request(action, payload);
                if (res.status === 200 && res.body.status === 'ok') {
                    this.msg = res.body.msg;
                    $('#formModal').modal('hide');
                    this.getdealerships();
                } else {
                    console.error("Failed to save dealership:", res.body);
                    alert("Failed to save dealership");
                }
            } catch (err) {
                console.error("Error saving dealership:", err);
                alert("Error while saving dealership");
            }
        },

        async openToggleModal(id, status) {
            this.delete_id = id;
            this.delete_action = status === "y" ? "deactivate" : "activate";
            $('#deleteConfirmModal').modal('show');
        },
        closeFormModal() {
            $('#deleteConfirmModal').modal('hide');
        },
        async handleDeleteConfirmed() {
            try {
                const active = this.delete_action === 'deactivate' ? 'n' : 'y';
                const res = await this.request('delete', { dealership_id: this.delete_id, active: active });
                if (res.status === 200 && res.body.status === 'ok') {
                    this.getdealerships();
                    this.closeFormModal();
                } else {
                    console.error("Failed to update dealership:", res.body);
                }
            } catch (err) {
                console.error("Error updating dealership:", err);
            }
        },
        async request(action, data = {}) {
            try {
                const res = await $http('POST', `${g.$base_url_api}/admin/dealerships`, { action, ...data }, {});
                return res;
            } catch (e) {
                return { status: e.status, body: e.body };
            }
        },

        async exportData() {
            this.exportLoading = true;
            try {
                const res = await this.request('exportdata', { search: this.search });
                const fileUrl = res?.body?.data?.file_url;

                if (res.status === 200 && fileUrl) {
                    $downloadFile(fileUrl);
                } else {
                    console.error("Export failed:", res);
                    alert('Failed to export dealerships');
                }
            } catch (err) {
                console.error("Export error:", err);
                alert('Error while exporting dealerships');
            } finally {
                this.exportLoading = false;
            }
        },
        onSearch() {
            this.currentPage = 1;
            this.getdealerships();
        },
        changePerPage(e) {
            this.perPage = parseInt(e.target.value, 10);
            this.currentPage = 1;
            this.getdealerships();
        },
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.getdealerships();
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.getdealerships();
            }
        },
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalCount / this.perPage) || 1;
        }
    },
    template: `
    <div style="margin: 0px auto; padding: 5px;">
       <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center px-1 py-2 border-bottom">
           <h6 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1"><i class="bi bi-table text-secondary"></i> Dealerships</h6>
           <div class="d-flex gap-2">
            <button @click="openDealerForm()" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm btn-dark" >
            <i class="bi bi-plus-circle"></i> Add Dealership
                </button>

            <button 
                    @click="exportData" 
                    :disabled="exportLoading"
                     class="btn btn-sm d-flex align-items-center gap-1 shadow-sm btn-outline-dark">
                    <i class="bi bi-file-earmark-spreadsheet"></i> <span v-if="!exportLoading">Export</span>
                    <span v-else style="display: flex; align-items: center; justify-content: center;">
                        <span class="spinner-border spinner-border-sm me-2" style="width: 1rem; height: 1rem;" role="status"></span>
                        Exporting...
                    </span>
                </button>

              
           </div>
       </div>

        <!-- Search Row -->
        <div class="p-2 pb-3 mb-1 bg-light rounded-1 border shadow-sm search-filter">
            <div class="g-1 align-items-end d-flex col-md-4">
            <span class="input-group">
                 <input v-model="search.search_data" class="form-control" @keyup.enter="onSearch" placeholder="Search dealerships..." />
            </span>
            <button @click="onSearch" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm btn-dark ms-3"> Search </button>
            </div>
        </div>

        <!-- Controls -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 small">
            <label style="display: flex; align-items: center; gap: 6px;">
                <span>Per&nbsp;page:</span>
                <select v-model="perPage" @change="changePerPage" class="form-select form-select-sm border-0 shadow-sm">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>

            <div>
                Displaying <b>{{ start_count }}</b> to <b>{{ end_count }}</b> of <b>{{ totalCount }}</b>
            </div>

            <ul class="pagination pagination-sm mb-0 gap-2">
               <li class="page-item"> <button 
                    @click="prevPage" 
                    :disabled="currentPage === 1" class="page-link border-0 shadow-sm">
                    <i class="bi bi-chevron-left"></i>
                </button></li>
                <li class="page-item"> <button 
                    @click="nextPage" 
                    :disabled="currentPage === totalPages" class="page-link border-0 shadow-sm">
                  
                    <i class="bi bi-chevron-right"></i>
                </button></li>
            
            </div>

        </div>

        <!-- Table -->
        <div class="rounded-1 m-2" style="overflow-x: auto;">
            <!-- Dealerships List -->
            <div v-for="dealer in dealershipsList" :key="dealer.id" class="mb-4 border rounded-1 shadow-sm bg-white">
                <!-- Dealer Group Header -->
                <div class="d-flex justify-content-between align-items-center py-2 px-3 bg-lightest border-bottom rounded-top">
                    <div>
                        <h2 class="mb-1 fw-semibold text-dark">
                            {{ dealer.name }}
                            <span v-if="dealer.short_name" class="small text-muted">({{ dealer.short_name }})</span>
                        </h2>
                        <div class="small text-muted">
                            Website URL: {{ dealer.website_url || '-' }}
                        </div>
                    </div>
                    <div class="d-flex">
                        <button 
                            @click="openEditDealer(dealer)" 
                            class="btn btn-sm btn-outline-dark me-2 fs-6 py-0 px-2"
                            title="Edit Dealership"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button 
                            @click="goTo('add/' + dealer.id)" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm btn-dark">
                            <i class="bi bi-plus-lg"></i> Add Branch
                        </button>
                    </div>
                </div>

                <!-- Branches Table -->
                <div class="px-0 pt-3">
                    <template v-if="dealer.branches && dealer.branches.length > 0">
                        <h6 class="mb-2 fw-semibold text-secondary ps-3">Branches</h6>
                        <table class="table table-sm align-middle dealerships-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-muted">Branch ID</th>
                                    <th class="text-muted">Name</th>
                                    <th class="text-muted">City</th>
                                    <th class="text-muted">State</th>
                                    <th class="text-muted">Address</th>
                                    <th class="text-muted">Pin Code</th>
                                    <th class="text-muted">Contact</th>
                                    <th class="text-muted">Outlet Code</th>
                                    <th class="text-muted text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="branch in dealer.branches" :key="branch.id" class="branch-row">
                                    <td>{{ branch.id }}</td>
                                    <td class="fw-medium">
                                        <div>{{ branch.name }}</div>
                                        <div v-if="branch.main_branch === 'y'" class="badge bg-success mt-1">
                                            Main
                                        </div>
                                    </td>
                                    <td>{{ branch.city_name }}</td>
                                    <td>{{ branch.state_name }}</td>
                                    <td class="text-truncate" style="max-width:180px;">{{ branch.address }}</td>
                                    <td>{{ branch.pin_code }}</td>
                                    <td>
                                        <div v-if="branch.contact_email"><small><b>Email:</b> {{ branch.contact_email }}</small></div>
                                        <div v-if="branch.contact_mobile"><small><b>Phone:</b> {{ branch.contact_mobile }}</small></div>
                                    </td>
                                    <td>{{ branch.outlet_code }}</td>
                                    <td class="text-center btns">
                                        <button @click="goTo('view/' + branch.id)" class="btn btn-sm btn-outline-dark me-2 fs-6 p-0 my-2" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button @click="goTo('edit/' + branch.id)" class="btn btn-sm btn-outline-dark me-2 fs-6 p-0 my-2" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </template>
                    <template v-else>
                        <div class="text-center text-muted fst-italic">No branches available</div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Form Modal -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <div class="modal-header popup-head">
                <h5 class="modal-title">{{ isEditMode ? 'Edit Dealership' : 'Add Dealership' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div v-if="msg" class="alert alert-success" role="alert"> {{ msg }} </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" v-model="formData.name" id="name" class="form-control">
                    <div class="text-danger" v-if="formDataErrors.name">{{ formDataErrors.name }}</div>
                </div>
                <div class="mb-3">
                    <label for="short_name" class="form-label">Short Name <span class="text-danger">*</span></label>
                    <input type="text" v-model="formData.short_name" id="short_name" class="form-control">
                    <div class="text-danger" v-if="formDataErrors.short_name">{{ formDataErrors.short_name }}</div>
                </div>
                <div class="mb-3">
                    <label for="url" class="form-label">Website URL</label>
                    <input type="text" v-model="formData.website_url" id="url" class="form-control">
                    <div class="text-danger" v-if="formDataErrors.website_url">{{ formDataErrors.website_url }}</div>
                </div>
            <div class="modal-footer border-0 p-0">
                <button type="button" @click="saveDealer" class="btn btn-dark">
                    {{ isEditMode ? 'Update' : 'Save' }}
                </button>
             </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold text-danger">
                Confirm {{ delete_action === 'deactivate' ? 'Deactivate' : 'Activate' }}
            </h5>
            <button type="button" class="btn-close" @click="closeFormModal"></button>
            </div>
            <div class="modal-body pt-2">
            Are you sure you want to {{ delete_action === 'deactivate' ? 'deactivate' : 'activate' }} this dealership?
            </div>
            <div class="modal-footer border-0 pt-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill" @click="closeFormModal">Cancel</button>
            <button type="button" class="btn btn-danger rounded-pill" @click="handleDeleteConfirmed">
                {{ delete_action === 'deactivate' ? 'Deactivate' : 'Activate' }}
            </button>
            </div>
        </div>
        </div>
    </div>
    `
};
