const template = /*html*/`
<div class="container-fluid py-3 bg-light min-vh-100">

  <!-- Header & Add User Button -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <h2 class="h4 text-uppercase fw-bold text-dark mb-3 mb-md-0">Manage Admin Users</h2>
    <button type="button" class="btn btn-dark rounded-1 shadow-sm" @click="addUser">
      <i class="bi bi-person-plus me-1"></i>  Add User
    </button>
  </div>

  <!-- Pagination & Per Page Controls -->
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

  <!-- User Modal -->
  <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content shadow-sm border-0 rounded-3">
        <div class="modal-header popup-head">
          <h5 class="modal-title" id="userModalLabel">{{isEditModal ? 'Edit User' : 'Add User'}}</h5>
          <button type="button" class="btn-close" @click="closeUserModal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Name</label>
            <input class="form-control" type="text" v-model="user_data.name" placeholder="Enter user name"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input class="form-control" type="email" v-model="user_data.email" placeholder="Enter email"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Mobile</label>
            <input class="form-control" type="text" v-model="user_data.mobile" placeholder="Enter mobile number"/>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Select Role</label>
            <select class="form-select" v-model="user_data.role_name">
              <option value="">--Select Role--</option>
              <option v-for="role in rolesList" :key="role.role_name">{{role.role_name}}</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold me-3">Active:</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="active" value="y" v-model="user_data.active" id="active" checked>
              <label class="form-check-label" for="active">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="active" value="n" v-model="user_data.active" id="inactive">
              <label class="form-check-label" for="inactive">No</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary rounded-1" @click="closeUserModal">Cancel</button>
          <button type="button" class="btn btn-dark rounded-1" @click="saveUser">Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Table -->
  <div class="table-responsive shadow-sm rounded-3 bg-white">
    <table class="table table-hover table-bordered mb-0 text-center align-middle">
      <thead class="table-dark">
        <tr>
          <th v-for="(header,index) in table_headers" :key="index">{{formatedHeader(header)}}</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(row,index) in tableList" :key="index">
          <td>{{row.id}}</td>
          <td>{{row.name}}</td>
          <td>{{row.email}}</td>
          <td>{{row.mobile}}</td>
          <td>{{row.role_name}}</td>
          <td>
            <span class="badge rounded-pill" :class="row.active == 'y' ? 'bg-success' : 'bg-danger'">{{row.active == 'y' ? 'Active' : 'Inactive'}}</span>
          </td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-1 me-2 px-2 py-1" @click="editUser(row)"><i class="bi bi-pencil fs-6"></i></button>
            <!-- Active/Inactive Toggle Buttons -->
            <button type="button" v-if="row.active == 'y'" class="btn btn-sm btn-outline-success rounded-1 me-2 px-2 py-0" @click="deleteUser(row.id)"><i class="bi bi-toggle-on"></i></button>
            <button type="button" v-else class="btn btn-sm btn-outline-danger rounded-1 me-2 px-2 py-0" @click="deleteUser(row.id)"><i class="bi bi-toggle-off"></i></button>
          </td>
        </tr>
        <tr v-if="tableList.length === 0">
          <td colspan="7" class="text-muted py-4">No users found</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
`;



export default {
    name: 'Admin-Users',
    template,
    data(){
        return{
           table_headers : ["id","user name","email","mobile","role_name","active"],
           tableList : [],
           user_data:{
               name : '',
               email : '',
               mobile : '',
               active : '',
               role_name : ''
           },
           currentPage:1,
           perPage :10,
           from_count :0,
           to_count : 0,
           totalCount:0,
           rolesList : [],
           isEditModal:false,
           
        }
    },
    mounted()
    {
        this.getUsers();
        this.getRoles();
    },
    computed: {
        totalPages() {
            return Math.ceil(this.totalCount / this.perPage) || 1;
        },
        fromCount() {
            return (this.currentPage - 1) * this.perPage + 1;
        },
        toCount() {
            return Math.min(this.currentPage * this.perPage, this.totalCount);
        }
    },
    methods:{
        async getUsers()
         {
            try{
               const data = {
                  action : 'getusers',
                  perPage : this.perPage,
                  page : this.currentPage
               };
               const response = await $http('POST',`${g.$base_url_api}/admin/admin-users`,{...data},{});
               if(response.body.status === "ok")
               {
                  this.tableList = response.body.data.users_list;
                  this.from_count = response.body.data.start_count;
                  this.to_count = response.body.data.end_count;
                  this.totalCount = response.body.data.total;
               }
               else
               {
                  this.tableList = [];  
                  this.totalCount = 0;
               }
            }
            catch(error){
               alert(error);
            }
         },
         async getRoles()
         {
            try{
               const data = {
                  action : 'getroles',
               };
               const response = await $http('POST',`${g.$base_url_api}/admin/admin-users`,{...data},{});
               if(response.body.status === "ok")
               {
                  this.rolesList = response.body.data.roles_list;
               }
               else
               {
                  this.rolesList = [];  
               }
            }
            catch(error){
               alert(error);
            }
         },
        async saveUser()
        {
            try{

               this.isEditModal = false;
               const data = {
                  action : 'saveuser',
                  name : this.user_data.name,
                  email : this.user_data.email,
                  mobile : this.user_data.mobile,
                  role_name : this.user_data.role_name,
                  active : this.user_data.active
               };
               const response = await $http('POST',`${g.$base_url_api}/admin/admin-users`,{...data},{});
               if(response.body.status === "ok")
               {
                 alert("Ok");
               }
               else if(response.body.status == "fail")
               {
                 alert("fail");
               }
            }
            catch(error){
               alert(error);
            }
        },
        openUserModal()
        {
          $("#userModal").modal('show');
        },
        addUser()
        {
           this.openUserModal();
           this.isEditModal = false;
           this.user_data = {
              name : '',
              email : '',
              mobile : '',
              role_name : '',
              active : 'y'
          };
        },
        editUser(user)
        {
         console.log(user);
            this.isEditModal = true;
            this.user_data = { ...user };
            this.openUserModal();
        },
        deleteUser(userId)
        {
            this.isEditModal = true;
            const res = this.user_data.filter(id => id == userId);
            if(res != -1)
            {
               if(confirm("Are you sure do you want to delete this user?"))
               { 
                  
               }
            }
        },
        closeUserModal()
        {
          $("#userModal").modal('hide');
        },
        formatedHeader(header)
        {
            return $capitalize(header).replace(/_/g, " ");
        },
        changePerPage(e) {
            this.perPage = parseInt(e.target.value, 10);
            this.currentPage = 1;
            this.getUsers();
        },
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.getUsers();
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.getUsers();
            }
        },
    }
}
