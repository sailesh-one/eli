export const users = {
    data() {
        return {
            dealersList: [],
            roles:[],
            search: {
                search_data : '',
                role : ''
            },
            perPage: 10,
            currentPage: 1,
            totalCount: 0,
            start_count:0,
            end_count:0,
            showModal: false,
            modalDealer: {
                id: '',
                name: '',
                email: '',
                mobile: '',
                role_id:'',
                active: '1',
            },
            isEdit: false
        };
    },
    mounted() {
        this.getdealers();
        this.getroles();
        // getdealerships();
    },
    methods: {
        async getdealers() {
            const res = await this.request('getdealers', {
                perPage: this.perPage,
                page: this.currentPage,
                search: this.search
            });
            if (res.body.status === 'ok' && res.body.data?.length > 0) {
                this.dealersList = res.body.data[0].leads || [];
                this.totalCount = parseInt(res.body.data[0].total) || 0;
                this.start_count = parseInt(res.body.data[0].start_count) || 0;
                this.end_count = parseInt(res.body.data[0].end_count) || 0;


            } else {
                this.dealersList = [];
                this.totalCount = 0;
            }
        },
        async getroles(){
             const res = await this.request('getroles', {
                    is_dealer: 1
              });
            if (res.body.status === 'ok' && res.body.data?.length > 0) {
                this.roles = res.body.data[0].roles || [];
                console.log(this.roles);
            } else {
                this.roles = [];
            }
        },
        async getdealerships(){
            const res = await this.request('getdealerships', {
            });
            if (res.body.status === 'ok' && res.body.data?.length > 0) {
                console.log("dealerships");
                console.log(res.body);
                this.dealerships = res.body.data[0].roles || [];
                console.log(this.roles);
            } else {
                this.roles = [];
            }
        },
        async request(action, data = {}) {
            try {
                const res = await $http('POST', `${g.$base_url_api}/admin/dealer-management`, { action, ...data }, {});
                return res;
            } catch (e) {
                return { status: e.status, body: e.body };
            }
        },
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
            this.modalDealer = { ...dealer };
            this.isEdit = true;
            this.showModal = true;
        },
        addDealer() {
            this.modalDealer = { id: '', name: '', email: '', mobile: '', role_id: '', active: '1' };
            this.isEdit = false;
            this.showModal = true;
        },
        closeModal() {
            this.showModal = false;
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
    <div style="margin: 30px auto; padding: 32px;">
        <!-- Search Row -->
        <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 18px;">
            <input v-model="search.search_data" @keyup.enter="onSearch" placeholder="Search dealers..." style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" />
            <select v-model="search.role" id="searchRole" class="form-select">
                <option value="">-- Select Role --</option>
                <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                {{ role.role_name }}
                </option>
            </select>
            <button @click="onSearch" style="padding: 8px 16px; background: #0e0f0fff; color: #fff; border: none; border-radius: 4px; font-weight: bold;">Search</button>
        </div>

        <!-- Controls Row -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            
            <button @click="addDealer" 
                    style="padding: 8px 18px; background: #000; color: #fff; border: none; border-radius: 4px; font-weight: bold;">
                Add User
            </button>

            <label style="display: flex; align-items: center;">
                Per page:
                <select v-model="perPage" @change="changePerPage" 
                        style="margin-left: 6px; padding: 4px 8px; border-radius: 4px;">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </label>

            <div>
                Displaying {{ start_count }} to {{ end_count }} of {{ totalCount }}
            </div>

            <div style="display: flex; gap: 10px;">
                <button @click="prevPage" :disabled="currentPage === 1" style="padding: 6px 12px;">
                    Prev
                </button>
                <button @click="nextPage" :disabled="currentPage === totalPages" style="padding: 6px 12px;">
                    Next
                </button>
            </div>

        </div>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                <thead style="background: #f5f5f5;">
                    <tr>
                        <th style="padding: 10px; border: 1px solid #eee;">ID</th>
                        <th style="padding: 10px; border: 1px solid #eee;">Name</th>
                        <th style="padding: 10px; border: 1px solid #eee;">Email</th>
                        <th style="padding: 10px; border: 1px solid #eee;">Mobile</th>
                        <th style="padding: 10px; border: 1px solid #eee;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="dealer in dealersList" :key="dealer.id">
                        <td style="padding: 8px; border: 1px solid #eee;">{{ dealer.id }}</td>
                        <td style="padding: 8px; border: 1px solid #eee;">{{ dealer.name }}</td>
                        <td style="padding: 8px; border: 1px solid #eee;">{{ dealer.email }}</td>
                        <td style="padding: 8px; border: 1px solid #eee;">{{ dealer.mobile }}</td>
                        <td style="padding: 8px; border: 1px solid #eee;">
                            <button @click="editDealer(dealer)" style="padding: 4px 12px; background: #ffa726; color: #fff; border: none; border-radius: 4px; font-size: 13px; cursor: pointer;">Edit</button>
                        </td>
                    </tr>
                    <tr v-if="dealersList.length === 0">
                        <td colspan="5" style="text-align: center; padding: 16px;">No data found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        
        <!-- Modal -->
        <div v-if="showModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center;">
            <div style="background: white; padding: 24px; border-radius: 8px; width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <h3 style="margin-bottom: 16px;">{{ isEdit ? 'Edit Dealer' : 'Add New Dealer' }}</h3>
                <div style="margin-bottom: 12px;">
                    <label>Name:</label>
                    <input v-model="modalDealer.name" style="width: 100%; padding: 8px; border: 1px solid #ccc;" />
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Email:</label>
                    <input v-model="modalDealer.email" style="width: 100%; padding: 8px; border: 1px solid #ccc;" />
                </div>
                <div style="margin-bottom: 12px;">
                    <label>Mobile:</label>
                    <input v-model="modalDealer.mobile" style="width: 100%; padding: 8px; border: 1px solid #ccc;" />
                </div>
                <div class="mb-3">
                    <label for="addRole">Select Role:</label>
                    <select v-model="modalDealer.role_id" id="addRole" class="form-select" required>
                    <option value="">-- Select Role --</option>
                    <option v-for="role in roles" :key="role.role_id" :value="role.role_id">
                        {{ role.role_name }}
                    </option>
                    </select>
                </div>
                 <div style="margin-bottom: 12px;">
                    <label>Active:</label><br>
                    <label><input type="radio" value="1" v-model="modalDealer.active" /> Yes</label>
                    <label style="margin-left: 12px;"><input type="radio" value="0" v-model="modalDealer.active" /> No</label>
                </div>
                <div style="text-align: right;">
                    <button @click="closeModal" style="margin-right: 8px; padding: 6px 12px;">Cancel</button>
                    <button style="padding: 6px 12px; background: #0e0f0f; color: #fff;">Save</button>
                </div>
            </div>
        </div>
    </div>
    `
};
