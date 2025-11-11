export default
{
   data()
   {
     return{
          search_filters : {
            search_data : "",
            role : ""
          },
          roles : [],
          dealersList : [],
          branches : [],
          perPage : 10,
          start_count : 0,
          end_count : 0,
          total_count : 0,
          currentPage : 1,
          selectedCount : 10,
          login_user_id : null,
          modalDealer : {
             name : "",
             email : "",
             mobile : "",
             role_id : "",
             active : "y",
             branch_ids : [] 
          },
          showModal : false,
          toggleDealer : null,
          formErrors : {
             name : "",
             email : "",
             mobile : "",
             role_id : "",
             active : "y"
          },
          userId : null,
          dealership_id : null,
     }
   },
   mounted()
   {
      this.getDealerData();
   },
   computed: {
    totalPages() 
    {
      return Math.ceil(this.total_count / this.perPage) || 1;
    },
    startCount() 
    {
      return (this.currentPage - 1) * this.perPage + 1;
    },
    endCount() 
    {
      return Math.min(this.currentPage * this.perPage, this.total_count);
    }
  },
   methods:
   {
      async getDealerData()
      {
         const data = {
            action : "get",
            page : this.currentPage,
            perPage : this.perPage,
            search_filters : this.search_filters
         };
         try
         {
            const response = await $http("POST",`${g.$base_url_api}/executive-management`,{...data},{});
            if(response?.body?.status == "ok")
            {
              let data = (response.body.data) ?? {};

              this.userId = data.login_user_id;
              this.dealership_id = data.leads.dealership_id;
              this.branches = data.branches ?? [];

              if(data.roles.roles.length > 0)
              {
                this.roles = data.roles.roles;   
              }
              else
              {
                this.roles = []; 
              }
                

              if(data.leads.leads.length > 0)
              {
                this.dealersList = data.leads.leads;
                this.total_count = data['leads'].total ?? 0;
                this.start_count = data['leads'].start_count ?? 0;
                this.end_count = data['leads'].end_count ?? 0;
                this.login_user_id = data['leads']['id'] ?? null;
              }
              else
              {
                this.dealersList = [];
                this.total_count = 0;
              }
            }
            else
            {
              this.dealersList = [];
              this.roles = [];
              this.total_count = 0;
            }
         }
         catch(error)
         {
           alert(error.body?.errors ?? "An error occurred while fetching data.");
         }
      },
      changePerPage(perPage)
      {
          this.perPage = perPage;
          this.currentPage = 1;
          this.getDealerData();
      },
      prevPage()
      {
        if(this.currentPage > 1)
        {
          this.currentPage--;
          this.getDealerData();
        }
      },
      nextPage()
      {
         if(this.currentPage < this.totalPages)
         {
           this.currentPage++;
           this.getDealerData();
         }
      },
      searchData()
      {
        this.currentPage = 1;
        this.getDealerData();
      },
      addDealer()
      {
        this.formErrors = [];
        this.showModal = true;
        this.modalDealer = {
          name: "",
          email: "",
          mobile: "",
          role_id: "",
          active: "y",
          branch_ids: [] 
        };
       this.isEdit = false;
      },
      editDealer(dealer) {
        this.formErrors = [];
        this.showModal = true;

        let branch_ids = [];
        try {
          branch_ids = dealer.branch_id ? JSON.parse(dealer.branch_id) : [];
        } catch(e) {
          branch_ids = [];
        }

        this.modalDealer = {
          ...dealer,
          branch_ids: branch_ids
        };
        
        this.isEdit = true;
      },
      closeModal()
      {
        this.formErrors = [];
        this.showModal = false;
      },
      async saveDealer() {
      const action = this.isEdit ? 'edit' : 'add';
      const data = {
        action : action,
        id: this.modalDealer.id,
        name: this.modalDealer.name,
        email: this.modalDealer.email,
        mobile: this.modalDealer.mobile,
        role_id: this.modalDealer.role_id,
        dealership_id : this.dealership_id,
        active: this.modalDealer.active,
        branch_ids : this.modalDealer.branch_ids
      };
      
      try
      {
        const res = await $http("POST", `${g.$base_url_api}/executive-management`, { ...data }, {});
        if(res.body.status === 'ok') 
        {
          this.getDealerData(); 
          this.closeModal();
          this.formErrors = [];
        }
      }
     catch(error)
     {
        this.formErrors = error.body.errors;
     }
    },
    openToggleModal(dealer) 
    {
      this.toggleDealer = dealer;
      $('#toggleConfirmModal').modal('show');
    },

    async confirmToggle()
    {
      if (!this.toggleDealer) return;
      const data = 
      {
        action : 'edit',
        id: this.toggleDealer.id,
        name: this.toggleDealer.name,
        email: this.toggleDealer.email,
        mobile: this.toggleDealer.mobile,
        role_id: this.toggleDealer.role_id,
        dealership_id : this.dealership_id,
        branch_ids : this.toggleDealer.branch_id ? JSON.parse(this.toggleDealer.branch_id) : [],
        active: this.toggleDealer.active === 'y' ? 'n' : 'y',
      };
      
      try
      {
        const res = await $http("POST", `${g.$base_url_api}/executive-management`, { ...data }, {});
        if (res.body.status === 'ok') 
        {
          this.getDealerData();
          this.closeModal();
        }
      }
      catch(error)
      {
        this.formErrors = error.body.errors;
      }

      $('#toggleConfirmModal').modal('hide');
      this.toggleDealer = null;
    },
    cancelToggle()
    {
      $('#toggleConfirmModal').modal('hide');
      this.toggleDealer = null;
    },
    resetFields()
    {
       this.search_filters.search_data = "";
       this.search_filters.role = "";
       this.getDealerData();
    }
   },
   template:/*html*/`
     <div class="container-fluid py-5 bg-light min-vh-100" id="grid-container">
       <div class="row justify-content-center" id="grid-panel">
         <div class="col-lg-10">
            <!--Header-->
            <div class="d-flex justify-content-between align-items-center">
               <h4 class="fw-bold text-muted text-uppercase">Executive management</h4>
               <button class="btn btn-dark btn-sm rounded-pill shadow-sm" @click="addDealer">
                <i class="bi bi-person-add me-1"></i> Add Executive
               </button>       
            </div>
            <!--Search filters-->
              <div class="row g-2 mb-3 mt-2">
                <div class="col-md-4">
                  <div class="input-group rounded-pill shadow-sm overflow-hidden">
                    <span class="input-group-text bg-white border-0">
                      <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-0 py-2" v-model="search_filters.search_data" placeholder="Search users..."/>
                  </div>
                </div>  
                <div class="col-md-2">
                  <select class="form-select shadow-sm rounded-pill" v-model="search_filters.role">
                    <option value="" disabled>-- Select Role --</option>
                    <option v-for="(role,index) in roles" :key="role.id" :value="role.id">{{role.role_name}}</option>
                  </select>
                </div>
              <div class="col-md-2">
                <button class="btn btn-dark rounded-pill text-light w-75 fw-bold ms-5" @click="searchData">Search</button>
              </div>
              <div class="col-md-2">
                <button class="btn btn-dark rounded-pill text-light w-50 fw-bold" @click="resetFields">Reset</button>
              </div>
         </div>
         <!-- Pagination -->
         <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
             <div class="d-flex align-items-center gap-2">
                 <label class="form-label text-muted mt-2">Per page:</label>
                 <select class="form-select rounded-pill ms-1 shadow-sm w-auto" @change="changePerPage(selectedCount)" v-model="selectedCount">
                   <option value="5">5</option>
                   <option value="10">10</option>
                   <option value="25">25</option>
                   <option value="50">50</option>
                 </select>
              </div>
             
             <div class="text-muted">
               <span>Showing <b>{{start_count}}</b> - <b>{{end_count}}</b> of <b>{{total_count}}</b></span>
             </div>

             <div class="btn-group shadow-sm">
               <button class="btn btn-outline-dark btn-sm rounded-start-pill" @click="prevPage" :disabled="currentPage == 1"> 
                 <i class="bi bi-chevron-left"></i> Prev
               </button>
               <button class="btn btn-outline-dark btn-sm rounded-end-pill" @click="nextPage" :disabled="currentPage == totalPages">
                Next <i class="bi bi-chevron-right"></i>
               </button>
             </div>
         </div>
        <!-- Executives table -->
        <div class="card shadow-sm border-0 rounded-4">
           <div class="card-body p-0">
               <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase table-light small text-muted">
                      <tr>
                        <th>ID</th>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>MOBILE</th>
                        <th>Branch</th>
                        <th>ROLE</th>
                        <th class="text-center">ACTIONS</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="dealersList.length === 0">
                        <td colspan="7" class="text-center text-danger">No executives found.</td>
                      </tr>
                      <tr v-else v-for="(dealer,idx) in dealersList" :key="dealer.id" :class="{ 'opacity-50 pointer-events-none': userId == dealer.id }"  :title="userId == dealer.id ? 'You can not select your own record' : ''">
                        <td class="align-middle">{{dealer.id}}</td>
                        <td class="align-middle"><strong>{{dealer.name}}</strong></td>
                        <td class="align-middle">{{dealer.email}}</td>
                        <td class="align-middle">{{dealer.mobile}}</td>
                        <td class="align-middle">
                          <div  v-for="id in (dealer.branch_id ? JSON.parse(dealer.branch_id) : [])"  :key="id" >
                            {{ branches.find(b => b.branch_id == id)?.branch_name }} - 
                            {{ branches.find(b => b.branch_id == id)?.branch_city }}
                          </div>
                        </td>
                        <td ><span class="rounded-pill mt-3 bg-secondary badge text-light">{{dealer.role_name}}</span></td>
                        <td class="text-center">
                          <div class="d-flex gap-2 justify-content-center">
                            <!-- Edit -->
                            <button class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px;" :disabled="(userId == dealer.id)" @click="editDealer(dealer)">
                              <i class="bi bi-pencil"></i>
                            </button>
                            <!-- Activate/Deactivate -->
                            <button class="btn btn-sm btn-link p-0"
                                      :class="dealer.active === 'y' ? 'text-success' : 'text-danger'"
                                      @click="openToggleModal(dealer)"
                                      :title="dealer.active === 'y' ? 'Deactivate Dealer' : 'Activate Dealer'" :disabled="(userId == dealer.id)">
                                <i :class="dealer.active === 'y' ? 'bi bi-toggle-on fs-4' : 'bi bi-toggle-off fs-4'"></i>
                              </button>
                           </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
               </div>
           </div>
        </div>
        
      <!--Add/Edit Executive modal-->
      <div v-if="showModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center;">
        <div style="background: white; padding: 24px; border-radius: 8px; width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
          <h3 style="margin-bottom: 16px;">{{ isEdit ? 'Edit Executive' : 'Add New Executive' }}</h3>
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
            <label>Select Role:</label>
            <select v-model="modalDealer.role_id" class="form-select" @change="formErrors.role_id = ''">
              <option value="" disabled>-- Select Role --</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">
                {{ role.role_name }}
              </option>
            </select>
            <div v-if="formErrors.role_id != ''" class="text-danger">{{formErrors.role_id}}</div>
          </div>
          <div class="mb-3">
            <label for="branches">Select Branches:</label>
            <select class="form-select" v-model="modalDealer.branch_ids" multiple>
              <option v-for="branch in branches" :key="branch.branch_id" :value="branch.branch_id">
                {{ branch.branch_name }} ({{ branch.branch_city }})
              </option>
            </select>
            <div v-if="formErrors.branch_ids" class="text-danger">{{formErrors.branch_ids}}</div>
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
      
      <!-- Activate/Deactivate executive modal -->
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
              Are you sure you want to {{ toggleDealer?.active === 'y' ? 'deactivate' : 'activate' }} this executive?
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

    </div>
   </div>
  </div>
   `,
}