export const MMVS = {
  name: 'MMVS',
  data() {
    return {
      makeSearch: '',
      modelSearch: '',
      variantSearch:'',
      mmvType:'',
      makes: [],
      models: [],
      variants:[],
      

      selectedMakeId: null,
      selectedModelId:null,
      selectedVariantId:null,
   

      showMakeModal: false,
      isEditMode: false,
      makeForm: this.getDefaultMakeForm(),
      makeFormError: '',
      modelForm:this.getDefaultModelForm(),
      modelFormError: '',
      variantForm:this.getDefaultVariantForm(),
      variantFormError: '',
      errors: {},
      isSubmitting: false,

      showActivationConfirm: false,
      activationTarget: this.getDefaultDeleteTarget(),
      activationError: '',

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
    this.getAllMakes();
  },
  computed: {
    filteredMakes() {
      return this.makes.filter(m =>
        m.make?.toLowerCase().includes(this.makeSearch.toLowerCase())
      );
    },
    filteredModels() {
      return this.models.filter(m =>
        m.model?.toLowerCase().includes(this.modelSearch.toLowerCase())
      );
    },
    filteredVariants() {
      return this.variants.filter(v =>
        v.variant?.toLowerCase().includes(this.variantSearch.toLowerCase())
      );
    },
  },
  methods: {
    // defaults
    getDefaultMakeForm() {
      return {
        id: '',
        make: '',
        active: 'y',
        is_popular: 'n',
        is_brand_group:'n'
      };
    },
    getDefaultModelForm() {
      return {
        model_id: '',
        model: '',
        make_id: this.selectedMakeId ? this.selectedMakeId:'',
        active:'y'
      };
    },
    getDefaultVariantForm(){
      return {
        variant_id: '',
        variant: '',
        model_id: this.selectedModelId?this.selectedModelId:'',
        make_id:this.selectedMakeId?this.selectedMakeId:'',
        active:'y'
      };
    },
    getDefaultDeleteTarget() {
      return {
        id: null,
        make: '',
        active: 'y',
        is_popular: 'n',
        is_brand_group:'n'
      };
    },
    getDefaultPermissionTarget() {
      return {
        id: null,
        make: null,
        submodule_id: null,
        name: '',
        isActive: false
      };
    },
    selectMake(make_id) {
        this.selectedMakeId = make_id;
        if(this.selectedMakeId){
            this.getModelsByMakeId(this.selectedMakeId);
        }
    },
    selectModel(model_id){
        this.selectedModelId = model_id;
        if(this.selectedMakeId){
            this.getVariantsByModelId(this.selectedModelId);
        }
    },
    async request(action, data = {}) {
      try {
        const basePath = this.role_type ? 'mmv' : 'mmv';
        return await $http('POST', `${g.$base_url_api}/admin/${basePath}`, { action, ...data }, {});
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async getAllMakes() {
      const res = await this.request('makeList', {});
      if (res.body.status === 'ok') {
        this.makes = res.body.data['makes_list'];
        this.models=[];
        this.variants=[];
      }
    },
    async getModelsByMakeId(make_id) {
      const res = await this.request('modelList', {make_id:make_id});
      if (res.body.status === 'ok') {
        this.models = res.body.data['models_list'];
        this.variants=[];
      }else{
        this.models=[];
        this.variants=[];
      }
    },
    async getVariantsByModelId(model_id) {
      const res = await this.request('variantList', {model_id:model_id});
      if (res.body.status === 'ok') {
        this.variants = res.body.data['variant_list'];
      }else{
        this.variants=[];
      }
    },
    
    // Make ui
    addMake() {
      this.isEditMode = false;
      this.makeForm = this.getDefaultMakeForm();
      this.makeFormError = '';
      this.errors = {};
      this.showMakeModal = true;
      this.$nextTick(() => $('#makeFormModal').modal('show'));
    },
    editMake(make) {
      this.isEditMode = true;
      this.makeForm = { ...make };
      this.makeFormError = '';
      this.errors = {};
      this.showMakeModal = true;
      this.$nextTick(() => $('#makeFormModal').modal('show'));
    },
    addModel(){
      this.isEditMode = false;
      this.modelForm = this.getDefaultModelForm();
      this.modelFormError = '';
      this.errors = {};
      this.showMakeModal = true;
      this.$nextTick(() => $('#modelFormModal').modal('show'));
    },
    editModel(model) {
      this.isEditMode = true;
      this.modelForm = { ...model };
      this.modelFormError = '';
      this.errors = {};
      this.showModelModal = true;
      this.$nextTick(() => $('#modelFormModal').modal('show'));
    },
    addVariant(){
      this.isEditMode = false;
      this.variantForm = this.getDefaultVariantForm();
      this.variantFormError = '';
      this.errors = {};
      this.showMakeModal = true;
      this.$nextTick(() => $('#variantFormModal').modal('show'));
    },
    editVariant(variant){
      this.isEditMode = true;
      this.variantForm = {...variant};
      this.variantFormError = '';
      this.errors = {};
      this.showMakeModal = true;
      this.$nextTick(() => $('#variantFormModal').modal('show'));
    },
    // save/update make
    async submitMakeForm() {
      this.makeFormError = '';
      this.errors = {};
      this.isSubmitting = true;

      const action = this.isEditMode ? 'editMake' : 'addMake';
      const form_data=this.makeForm;
      const payload = { form_data, form_action: action };

      try {
        const res = await this.request(action, payload);
        if (res.body.status === 'ok') {
          this.closeMakeFormModal();
          await this.getAllMakes();
        } else {
          this.makeFormError = res.body.msg || 'An error occurred.';
          this.errors = res.body.errors || {};
        }
      } catch {
        this.makeFormError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },
    async submitModelForm() {
      this.modelFormError = '';
      this.errors = {};
      this.isSubmitting = true;

      const action = this.isEditMode ? 'editModel' : 'addModel';
      const form_data=this.modelForm;
      const payload = { form_data, form_action: action };

      try {
        const res = await this.request(action, payload);
        if (res.body.status === 'ok') {
          this.closeModelFormModal();
          await this.getModelsByMakeId(this.selectedMakeId);
        } else {
          this.modelFormError = res.body.msg || 'An error occurred.';
          this.errors = res.body.errors || {};
        }
      } catch {
        this.modelFormError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },
    async submitVariantForm() {
      this.variantFormError = '';
      this.errors = {};
      this.isSubmitting = true;

      const action = this.isEditMode ? 'editVariant' : 'addVariant';
      const form_data=this.variantForm;
      const payload = { form_data, form_action: action };

      try {
        const res = await this.request(action, payload);
        if (res.body.status === 'ok') {
          this.closeVariantFormModal();
          await this.getVariantsByModelId(this.selectedModelId);
        } else {
          this.variantFormError = res.body.msg || 'An error occurred.';
          this.errors = res.body.errors || {};
        }
      } catch {
        this.variantFormError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    },
    closeMakeFormModal() {
      this.makeFormError = '';
      this.errors = {};
      this.makeForm = this.getDefaultMakeForm();
      this.showMakeModal = false;
      $('#makeFormModal').modal('hide');
    },
    closeModelFormModal() {
      this.modelFormError = '';
      this.errors = {};
      this.modelForm = this.getDefaultModelForm();
      this.showMakeModal = false;
      $('#modelFormModal').modal('hide');
    },
    closeVariantFormModal() {
      this.variantFormError = '';
      this.errors = {};
      this.variantForm = this.getDefaultVariantForm();
      this.showMakeModal = false;
      $('#variantFormModal').modal('hide');
    },

    closeActiveConfirmModal() {
      this.activationTarget = this.getDefaultDeleteTarget();
      this.showActivationConfirm = false;
      this.activationError = '';
      $('#openConfirmModal').modal('hide');
    },
    // delete/toggle role
    confirmActivation(m,type) {
      this.activationError = '';
      this.mmvType=type;
      if(type==='make'){
          this.activationTarget = {
            id: m.id,
            make: m.make,
            active: m.active,
            is_popular: m.is_popular,
            is_brand_group:m.is_brand_group,
            mmvType:type
          };
      }else if(type==='model'){
          this.activationTarget = {
              model_id: m.model_id,
              model: m.model,
              make_id: m.make_id,
              active: m.active,
              mmvType:type
          };
      }else if(type==='variant'){
         this.activationTarget = {
              variant_id: m.variant_id,
              variant: m.variant,
              make_id: m.make_id,
              model_id: m.model_id,
              active: m.active,
              mmvType:type
          };
      }
      
      this.showActivationConfirm = true;
      this.$nextTick(() => $('#openConfirmModal').modal('show'));
    },
    async handleActivationConfirmed() {
      this.isSubmitting = true;
      this.activationError = '';
      this.activationTarget.active=this.activationTarget.active=='y'?'n':'y';
      const form_data= this.activationTarget;
      let action='';
      if(this.activationTarget.mmvType==='make'){
          action='editMake';
      }
      else if(this.activationTarget.mmvType==='model'){
          action='editModel';
      }
      else if(this.activationTarget.mmvType==='variant'){
          action='editVariant';
      }
      const payload = {form_data, form_action: action };
      
      try {
         const res = await this.request(action, payload);
        if (res.body.status === 'ok') {
            if(this.activationTarget.mmvType==='make'){
                await this.getAllMakes();
            }
            else if(this.activationTarget.mmvType==='model'){
                await this.getModelsByMakeId(this.selectedMakeId);
            }
            else if(this.activationTarget.mmvType==='variant'){
              await this.getVariantsByModelId(this.selectedModelId);
            }
          
          this.closeActiveConfirmModal();
        } else {
          this.activationError = res.body.msg || 'Failed to update status.';
        }
      } catch {
        this.activationError = 'Request failed. Please try again.';
      } finally {
        this.isSubmitting = false;
      }
    }
    
  },
  template: `
<div>
<div class="container-fluid py-2 bg-light min-vh-100" id="grid-container">
  <div class="row justify-content-center mmv-management" id="grid-panel">
    <!-- Header -->
    <div class="card-header bg-white border-bottom px-2">

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">
        <h2 class="h4 text-uppercase fw-bold text-muted mb-3 mb-sm-0"> 
          MMV Management
        </h2>
        <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a New Make" @click="addMake">
          <i class="bi bi-plus-lg me-1"></i> Add Make
        </button>
      </div>
    </div>
    <div class="col-lg-12 col-xl-12">

      

      <!-- Search -->
      <div class="row py-2 pb-3 mb-1 bg-light border search-filter">
        <div class="col-12 col-md-6 col-lg-4">
          <div class="input-group rounded-1 overflow-hidden shadow-sm">
            <span class="input-group-text bg-white border-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input v-model="makeSearch" type="text" class="form-control form-control-lg border-0 py-3" placeholder="Search Make by name...">
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Makes Panel -->
        <div class="col-lg-4">
          <div class="card shadow-sm border-0 rounded-2 h-100">
            <div class="card-body">
               <h6 class="mb-3 text-muted">Makes</h6>
              <ul class="list-group list-group-flush">
                <li v-for="m in filteredMakes" :key="m.id"
                    class="list-group-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between rounded-3 mb-2 p-3 gap-3 shadow-sm"
                    :class="{ 'bg-dark text-white': selectedMakeId === m.id, 'bg-light': selectedMakeId !== m.id }"
                    style="cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease;"
                    @click="selectMake(m.id)">
                  
                  <div class="flex-grow-1">
                    
                    <div class="fw-semibold">{{ m.make }} <span v-if="m.is_popular == 'y'" class="badge text-bg-success fw-semibold mt-10"> Main </span></div>
                    <small :class="selectedMakeId === m.id ? 'text-white-50' : 'text-muted'">{{  m.make }}</small>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                     <button class="btn btn-sm rounded-1 px-2 py-1" :class="selectedMakeId === m.id ? 'btn-outline-light' : 'btn-outline-secondary'" title="Edit Make" @click.stop="editMake(m)">
                        <i class="bi bi-pencil fs-6"></i>
                    </button>
                    <button v-if="m.active == 'y'" class="btn btn-sm btn-outline-success rounded-1 px-1 py-0" @click.stop="confirmActivation(m,'make')" title="Deactivate Make" :disabled='m.make=="super-admin"'>
                      <i class="bi bi-toggle-on fs-5"></i>
                    </button>
                    <button v-else class="btn btn-sm btn-outline-danger rounded-1 px-1 py-0" @click.stop="confirmActivation(m,'make')" title="Activate Make">
                      <i class="bi bi-toggle-off fs-5"></i>
                    </button>
                  </div>
                </li>
                 <li v-if="!filteredMakes.length" class="list-group-item border-0">
                    <span class="text-muted fst-italic p-2"> No Makes found. </span>
                 </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Model Panel -->
        <div class="col-lg-4">
          <div v-if="selectedMakeId" class="card shadow-sm border-0 rounded-2 h-100">
            <div class="card-body">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-1">
                  <!-- <h6 class="mb-0 text-muted">Models</h6> -->
                   <div class="input-group rounded-1 overflow-hidden shadow-sm w-50 w-sm-auto border border-secondary">
                     <span class="input-group-text bg-white border-0 py-1"> <i class="bi bi-search text-muted small"></i> </span>
                     <input v-model="modelSearch" placeholder="Search Models..." class="form-control form-control-sm border-0" />
                  </div>
                  <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a New Model" @click="addModel">
                    <i class="bi bi-plus-lg me-1"></i> Add Model
                  </button>
              </div>
              <div class="p-2 border rounded-3 bg-white" style="max-height: 500px; overflow-y: auto;">
                <div v-for="md in filteredModels" :key="md.model_id" class="mb-0 pb-1 rounded-1" style="transition: background-color 0.2s ease;">
                  <div class="list-group-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between rounded-1 mb-0 p-2 gap-1 border-1 border-grey bg-light" :class="{ 'bg-dark text-white': selectedModelId === md.model_id, 'bg-light': selectedModelId !== md.model_id }">
                    <div class="fw-bold"  @click="selectModel(md.model_id)" 
                    style="cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease;"> {{ md.model }} </div>
                    <div class="d-flex align-items-center gap-2">
                         <button class="btn btn-sm rounded-1 px-2 py-1" :class="selectedModelId === md.model_id ? 'btn-outline-light' : 'btn-outline-secondary'" title="Edit Model" @click.stop="editModel(md)">
                            <i class="bi bi-pencil fs-6"></i>
                        </button>
                        <button v-if="md.active == 'y'" class="btn btn-sm btn-outline-success rounded-1 px-1 py-0" @click.stop="confirmActivation(md,'model')" title="Deactivate Model" :disabled='md.model=="super-admin"'>
                          <i class="bi bi-toggle-on fs-5"></i>
                        </button>
                        <button v-else class="btn btn-sm btn-outline-danger rounded-1 px-1 py-0" @click.stop="confirmActivation(md,'model')" title="Activate Model">
                          <i class="bi bi-toggle-off fs-5"></i>
                        </button>
                      </div>
                   <!--  <label class="fw-bold" style="cursor: pointer; transition: background-color 0.2s ease, color 0.2s ease;"  @click="selectModel(md.model_id)">{{ md.model }}</label> -->
                  </div>
                </div>
                 <div v-if="!filteredModels.length" class="p-2">
                    <span class="text-muted fst-italic"> No Models match your search. </span>
                 </div>
              </div>
            </div>
          </div>
          <div v-else class="d-flex align-items-center justify-content-center bg-light h-100 rounded-2 border">
            <div class="text-center text-muted">
                <i class="bi bi-hand-index-thumb fs-1"></i>
                <p class="mt-2">Select a Make to view</p>
            </div>
          </div>
        </div>
        <!-- Variant Panel -->
        <div class="col-lg-4">
          <div v-if="selectedModelId" class="card shadow-sm border-0 rounded-2 h-100">
            <div class="card-body">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
                  <!--<h6 class="mb-0 text-muted">Variants</h6> -->
                   <div class="input-group rounded-1 overflow-hidden shadow-sm w-50 w-sm-auto border border-secondary">
                     <span class="input-group-text bg-white border-0 py-1"> <i class="bi bi-search text-muted small"></i> </span>
                     <input v-model="variantSearch" placeholder="Search variants..." class="form-control form-control-sm border-0" />
                  </div>
                  <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add a New Model" @click="addVariant">
                    <i class="bi bi-plus-lg me-1"></i> Add Variant
                  </button>
              </div>

              <div class="p-2 border rounded-2 bg-white" style="max-height: 500px; overflow-y: auto;">
                <div v-for="v in filteredVariants" :key="v.variant_id" class="mb-0 pb-1 rounded-2">
                  <div class="list-group-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between rounded-1 mb-0 p-2 gap-1 border-1 border-grey bg-light">
                    
                    <label class="fw-bold" style="cursor: default;">{{ v.variant }}</label>
                  <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm rounded-1 px-2 py-1" :class="selectedVariantId === v.variant_id ? 'btn-outline-light' : 'btn-outline-secondary'" title="Edit Variant" @click.stop="editVariant(v)">
                            <i class="bi bi-pencil fs-6"></i>
                        </button>
                     <button v-if="v.active == 'y'" class="btn btn-sm btn-outline-success rounded-1 px-1 py-0" @click.stop="confirmActivation(v,'variant')" title="Deactivate Variant" :disabled='v.variant=="super-admin"'>
                          <i class="bi bi-toggle-on fs-5"></i>
                        </button>
                        <button v-else class="btn btn-sm btn-outline-danger rounded-1 px-1 py-0" @click.stop="confirmActivation(v,'variant')" title="Activate Variant">
                          <i class="bi bi-toggle-off fs-5"></i>
                        </button>
                   </div>
                  </div>
     
                  
                </div>
                 <div v-if="!filteredVariants.length" class="p-2">
                    <span class="text-muted fst-italic"> No Variants match your search. </span>
                 </div>
              </div>
            </div>
          </div>
          <div v-else class="d-flex align-items-center justify-content-center bg-light h-100 rounded-2 border">
            <div class="text-center text-muted">
                <i class="bi bi-hand-index-thumb fs-1"></i>
                <p class="mt-2">Select a Model to view</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

  <!-- Add/Edit Make Modal -->
  <div class="modal fade" id="makeFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title">{{ isEditMode ? 'Edit Make' : 'Add Make' }}</h5>
          <button type="button" class="btn-close" @click="closeMakeFormModal"></button>
        </div>
        <div class="modal-body">
          <div v-if="makeFormError" class="alert alert-danger small">{{ makeFormError }}</div>
          <div class="mb-3">
            <label class="form-label">Make Name</label>
            <input v-model="makeForm.make" class="form-control" placeholder="Enter Make name">
            <small v-if="errors.make" class="text-danger">{{ errors.make }}</small>
          </div>
          <div v-if="isEditMode" class="mb-3">
            <label class="form-label">Active</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="makeForm.active" value="y" class="form-check-input" id="activeYes"> 
              <label class="form-check-label" for="activeYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="makeForm.active" value="n" class="form-check-input" id="activeNo" :disabled="role_type == 0 && makeForm.active == 'y'"> 
              <label class="form-check-label" for="activeNo">No</label>
            </div>
          </div>
          <div  class="mt-2">
          <label class="form-label">is Popular</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="makeForm.is_popular" value="y" class="form-check-input"> 
              <label class="form-check-label" for="roleMainYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="makeForm.is_popular" value="n" class="form-check-input"> 
              <label class="form-check-label" for="roleMainNo">No</label>
            </div>
           </div>
           <div  class="mt-2">
            <label class="form-label">is Brand Group</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="makeForm.is_brand_group" value="y" class="form-check-input"> 
              <label class="form-check-label" for="roleMainYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="makeForm.is_brand_group" value="n" class="form-check-input"> 
              <label class="form-check-label" for="roleMainNo">No</label>
            </div>
            </div>
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-outline-secondary rounded-1" @click="closeMakeFormModal" :disabled="isSubmitting">Cancel</button>
          <button class="btn btn-dark rounded-1" @click="submitMakeForm" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Model -->
  <div class="modal fade" id="modelFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title">{{ isEditMode ? 'Edit Model' : 'Add Model' }}</h5>
          <button type="button" class="btn-close" @click="closeModelFormModal"></button>
        </div>
        <div class="modal-body">
          <div v-if="modelFormError" class="alert alert-danger small">{{ modelFormError }}</div>
          <div class="mb-3">
            <label class="form-label">Model Name</label>
            <input v-model="modelForm.model" class="form-control" placeholder="Enter Model name">
            <small v-if="errors.model" class="text-danger">{{ errors.model }}</small>
          </div>
          <div  class="mb-3">
            <label class="form-label">Active</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="modelForm.active" value="y" class="form-check-input" id="activeYes"> 
              <label class="form-check-label" for="activeYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="modelForm.active" value="n" class="form-check-input" id="activeNo" :disabled="role_type == 0 && modelForm.active == 'y'"> 
              <label class="form-check-label" for="activeNo">No</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-outline-secondary rounded-1" @click="closeModelFormModal" :disabled="isSubmitting">Cancel</button>
          <button class="btn btn-dark rounded-1" @click="submitModelForm" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>
   <!-- Add/Edit Variant -->
  <div class="modal fade" id="variantFormModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title">{{ isEditMode ? 'Edit Variant' : 'Add Variant' }}</h5>
          <button type="button" class="btn-close" @click="closeVariantFormModal"></button>
        </div>
        <div class="modal-body">
          <div v-if="variantFormError" class="alert alert-danger small">{{ variantFormError }}</div>
          <div class="mb-3">
            <label class="form-label">Variant Name</label>
            <input v-model="variantForm.variant" class="form-control" placeholder="Enter Variant name">
            <small v-if="errors.variant" class="text-danger">{{ errors.variant }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label">Active</label><br>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="variantForm.active" value="y" class="form-check-input" id="activeYes"> 
              <label class="form-check-label" for="activeYes">Yes</label>
            </div>
            <div class="form-check form-check-inline">
              <input type="radio" v-model="variantForm.active" value="n" class="form-check-input" id="activeNo" :disabled="role_type == 0 && variantForm.active == 'y'"> 
              <label class="form-check-label" for="activeNo">No</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button class="btn btn-outline-secondary rounded-1" @click="closeVariantFormModal" :disabled="isSubmitting">Cancel</button>
          <button class="btn btn-dark rounded-1" @click="submitVariantForm" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Activate / Deactivate Confirm Modal -->
  <div class="modal fade" id="openConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-2 border-0 shadow-lg">
        <div class="modal-header popup-head">
          <h5 class="modal-title fw-bold text-danger">Confirm {{ activationTarget.active === 'y' ? 'Deactivation' : 'Activation' }}</h5>
          <button type="button" class="btn-close" @click="closeActiveConfirmModal"></button>
        </div>
        <div class="modal-body py-4">
          Are you sure you want to <span class="fw-semibold">{{ activationTarget.active === 'y' ? 'deactivate' : 'activate' }}</span> the "<strong>{{ activationTarget.mmvType }}</strong>"?
          <div v-if="activationError" class="alert alert-danger small mt-3 mb-0">{{ activationError }}</div>
        </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-outline-secondary rounded-1" @click="closeActiveConfirmModal">Cancel</button>
          <button type="button" class="btn btn-danger rounded-1" @click="handleActivationConfirmed" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ activationTarget.active === 'y' ? 'Deactivate' : 'Activate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
`
};