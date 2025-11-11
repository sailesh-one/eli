const template = /*html*/`
<div class="container-fluid pt-2 bg-light min-vh-100">
  <!-- Header & Add HSN Button -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <h2 class="h4 text-uppercase fw-bold text-dark mb-3 mb-md-0">Manage HSN Codes</h2>
    <button type="button" class="btn btn-dark rounded-1 shadow-sm px-4 py-2" @click="addHSN">
      <i class="bi bi-person-plus me-1"></i>  Add HSN
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
<div class="modal fade" id="hsnModal" tabindex="-1" aria-labelledby="hsnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content shadow-sm border-0 rounded-3">
        <div class="modal-header popup-head text-light">
          <h5 class="modal-title text-dark" id="hsnModalLabel">{{isEdit ? 'Edit HSN' : 'Add HSN'}}</h5>
          <button type="button" class="btn-close btn-close-dark" @click="closeHsnModal"></button>
        </div>
        <div class="modal-body">
              <div v-if="globalError" class="text-danger text-center">{{ globalError}}</div>
              <div class="mb-3">
                <label class="form-label fw-semibold">HSN Code</label>
                <input class="form-control" type="text" v-model="hsn_data.hsn_code" placeholder="Enter HSN number"/>
                <div v-if="formErrors.hsn_code" class="text-danger">{{ formErrors.hsn_code }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea class="form-control" v-model="hsn_data.description" placeholder="Enter HSN Description"></textarea>
                <div v-if="formErrors.description" class="text-danger">{{ formErrors.description }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">CGST(%)</label>
                <input class="form-control" type="text" v-model="hsn_data.cgst" placeholder="Enter CGST"/>
                <div v-if="formErrors.cgst" class="text-danger">{{ formErrors.cgst }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">SGST(%)</label>
                <input class="form-control" type="text" v-model="hsn_data.sgst" placeholder="Enter SGST"/>
                <div v-if="formErrors.sgst" class="text-danger">{{ formErrors.sgst }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">IGST(%)</label>
                <input class="form-control" type="text" v-model="hsn_data.igst" placeholder="Enter IGST"/>
                <div v-if="formErrors.igst" class="text-danger">{{ formErrors.igst }}</div>
              </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary rounded-1 px-4" @click="closeHsnModal">Cancel</button>
          <button type="button" class="btn btn-dark rounded-1 px-4" @click="saveHSN">Save</button>
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
          <td>{{row.hsn_code}}</td>
          <td>{{row.cgst}}</td>
          <td>{{row.sgst}}</td>
          <td>{{row.igst}}</td>
          <td width="40%">{{row.description}}</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-1 me-2 px-2 py-1" @click="editHSN(row)"><i class="bi bi-pencil fs-6"></i></button>
            <!-- Active/Inactive Toggle Buttons -->
            <button type="button" v-if="row.active ==1" class="btn btn-sm btn-outline-success rounded-1 me-2 px-2 py-0" @click="openActStatus('Deactivate',row)"><i class="bi bi-toggle-on fs-5"></i></button>
            <button type="button" v-else class="btn btn-sm btn-outline-danger rounded-1 me-2 px-2 py-0" @click="openActStatus('Activate',row)"><i class="bi bi-toggle-off fs-5"></i></button>
          </td>
        </tr>
        <tr v-if="tableList.length === 0">
          <td colspan="7" class="text-muted py-4">No users found</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
<div class="modal fade" id="openConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2 border-0 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title fw-bold text-danger">Confirm {{ hsn_data.active == 1 ? 'Deactivate' : 'Activate' }}</h5>
          <button type="button" class="btn-close" @click="closeActiveConfirmModal"></button>
        </div>
        <div class="modal-body pt-2">
          Are you sure you want to <span class="fw-semibold">{{ hsn_data.active == 1 ? 'Deactivate' : 'Activate' }}</span> the HSN Code "<strong>{{ hsn_data.hsn_code }}</strong>"?
          <div v-if="activationError" class="alert alert-danger small mt-3 mb-0">{{ hsnError }}</div>
        </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-outline-secondary rounded-1" @click="closeActiveConfirmModal">Cancel</button>
          <button type="button" class="btn btn-danger rounded-1" @click="handleActivationConfirmed" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ hsn_data.active == 1 ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>
    </div>
</div>
`;
export default {
    name: 'Admin-HSN-Codes',
    template,
    data(){
        return{
           table_headers : ["id","HSN Code","CGST (%)","SGST (%)","IGST (%)","Description"],
           tableList : [],
           hsn_data:{
               hsn_code : '',
               description : '',
               cgst : '',
               sgst : '',
               igst : '',
               id:'',
               active: 1
           },
           formErrors : {
             hsn_code : "",
             description : "",
             cgst : "",
             sgst : "",
             igst : ""
          },
           currentPage:1,
           perPage :10,
           from_count :0,
           to_count : 0,
           totalCount:0,
           rolesList : [],
           isEdit:false,
           globalError:'',
           hsnError:''
           
        }
    },
    mounted()
    {
      this.getAllHsnCodes();
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
        async getAllHsnCodes()
         {
            try{
               const data = {
                  action : 'getAllHsnCodes',
                  perPage : this.perPage,
                  page : this.currentPage
               };
               const response = await $http('POST',`${g.$base_url_api}/admin/hsn-codes`,{...data},{});
               if(response.body.status === "ok")
               {
                  this.tableList = response.body.data.hsn_list;
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
        async saveHSN()
        {
            try{

               const action = this.isEdit ? 'editHSN' : 'saveHSN';
               const data = {
                  action : action,
                  hsn_code : this.hsn_data.hsn_code,
                  description : this.hsn_data.description,
                  cgst : this.hsn_data.cgst,
                  sgst : this.hsn_data.sgst,
                  igst : this.hsn_data.igst,
                  id: this.hsn_data.id ?? '',
                  active:this.hsn_data.active
               };
               const response = await $http('POST',`${g.$base_url_api}/admin/hsn-codes`,{...data},{});
               if(response.body.status === "ok")
               {
                  if(this.isEdit){
                    this.globalError='HSN code updated successfully';
                    this.getAllHsnCodes();
                  }else{
                    this.globalError='HSN code added successfully';
                  }
                  setTimeout(() =>
                  this.closeHsnModal(),700
                  )
               }else if(response.body.status=='fail' && Object.keys(response.body.errors).length != 0){
                  this.formErrors = response.body.errors;
               }
               else 
                {
                  this.formErrors =[];
                  this.globalError=response.body.msg;
                }
            }
            catch(error){
               alert(error);
            }
        },
        openHsnModal()
        {
          $("#hsnModal").modal('show');
        },
        addHSN()
        {
           this.openHsnModal();
           this.isEdit = false;
           this.hsn_data = {
              hsn_code : '',
              description : '',
              cgst : '',
              sgst : '',
              igst:'',
              id:'',
              active : 1
          };
          this.formErrors =[];
        },
        editHSN(record)
        {
            this.isEdit = true;
            this.hsn_data = { ...record };
            this.openHsnModal();
        },
        openActStatus(ty,record)
        {
            this.isEdit = true;
            this.hsn_data = { ...record };
            this.$nextTick(() => $('#openConfirmModal').modal('show'));
        },
        closeHsnModal()
        {
          this.formErrors =[];
          this.globalError='';
          $("#hsnModal").modal('hide');
        },
        closeActiveConfirmModal() {
          $('#openConfirmModal').modal('hide');
        },
        formatedHeader(header)
        {
            return $capitalize(header).replace(/_/g, " ");
        },
        changePerPage(e) {
            this.perPage = parseInt(e.target.value, 10);
            this.currentPage = 1;
            this.getAllHsnCodes();
        },
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.getAllHsnCodes();
            }
        },
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.getAllHsnCodes();
            }
        },
        async handleActivationConfirmed() {
          this.closeActiveConfirmModal();
          this.hsn_data.active=this.hsn_data.active==1?0:1;
          this.saveHSN();
        }
    }
}
