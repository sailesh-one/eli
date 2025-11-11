const [{ useUserStore }] = await $importComponent([
  '/pages/stores/userStore.js'
]);

export default {
  data() {
    return {
      userStore: null,
      mmvTree: [],
      loading: false,
      showAddMakeModal: false,
      showAddModelModal: false,
      showAddVariantModal: false,
      selectedMakeForModel: null,
      selectedModelForVariant: null,
      editingItem: null,
      editType: '', // 'make', 'model', 'variant'
      
      // Form data
      makeForm: {
        name: '',
        description: ''
      },
      modelForm: {
        make_id: 0,
        name: '',
        description: '',
        fuel_type: '',
        body_type: ''
      },
      variantForm: {
        model_id: 0,
        name: '',
        description: '',
        engine_capacity: '',
        transmission: '',
        fuel_type: '',
        price_range: ''
      },
      
      // Form validation
      formErrors: {},
      
      // Filter options
      showInactive: false,
      searchQuery: '',
      
      // Constants for dropdowns
      fuelTypes: ['Petrol', 'Diesel', 'Electric', 'Hybrid', 'CNG', 'LPG'],
      bodyTypes: ['Hatchback', 'Sedan', 'SUV', 'Coupe', 'Convertible', 'Wagon', 'Pickup'],
      transmissionTypes: ['Manual', 'Automatic', 'CVT', 'AMT']
    };
  },
  
  async created() {
    this.userStore = useUserStore();
    await this.loadMMVTree();
  },
  
  computed: {
    filteredMMVTree() {
      if (!this.searchQuery) return this.mmvTree;
      
      const query = this.searchQuery.toLowerCase();
      return this.mmvTree.filter(make => {
        const makeMatch = make.name.toLowerCase().includes(query);
        const modelMatch = make.models && make.models.some(model => 
          model.name.toLowerCase().includes(query) ||
          (model.variants && model.variants.some(variant => 
            variant.name.toLowerCase().includes(query)
          ))
        );
        return makeMatch || modelMatch;
      });
    }
  },
  
  watch: {
    showInactive() {
      this.loadMMVTree();
    }
  },
  
  methods: {
    async loadMMVTree() {
      this.loading = true;
      try {
        const response = await $http('POST', '/mmv/', { 
          action: 'get_mmv_tree',
          include_inactive: this.showInactive,
          admin_view: true  // Add flag to tell backend it's admin view
        }, {}, { auth: true });
        
        if (response?.status === 200) {
          this.mmvTree = response.body.data || [];
        } else {
          console.error('MMV API Error:', response);
          $toast('error', 'Failed to load MMV data');
        }
      } catch (error) {
        console.error('Error loading MMV tree:', error);
        $toast('error', 'Failed to load MMV data');
      } finally {
        this.loading = false;
      }
    },
    
    // ===== MAKE OPERATIONS =====
    openAddMakeModal() {
      this.resetMakeForm();
      this.editingItem = null;
      this.editType = '';
      this.showAddMakeModal = true;
    },
    
    openEditMakeModal(make) {
      this.makeForm = {
        name: make.name,
        description: make.description || ''
      };
      this.editingItem = make;
      this.editType = 'make';
      this.showAddMakeModal = true;
    },
    
    async saveMake() {
      if (!this.validateMakeForm()) return;
      
      try {
        const action = this.editingItem ? 'update_make' : 'add_make';
        const data = { ...this.makeForm };
        
        if (this.editingItem) {
          data.id = this.editingItem.id;
        }
        
        const response = await $http('POST', '/mmv/', { 
          action, 
          ...data 
        }, {}, { auth: true });
        
        if (response?.status === 200 && response.body.status === 'success') {
          $toast('success', this.editingItem ? 'Make updated successfully' : 'Make added successfully');
          this.showAddMakeModal = false;
          await this.loadMMVTree();
        } else {
          $toast('error', response.body.message || 'Failed to save make');
        }
      } catch (error) {
        console.error('Error saving make:', error);
        $toast('error', 'Failed to save make');
      }
    },
    
    async toggleMakeStatus(make) {
      try {
        const newStatus = make.manual_active === 'y' ? 'n' : 'y';
        const response = await $http('POST', '/mmv/', {
          action: 'update_make',
          id: make.id,
          name: make.name,
          description: make.description || '',
          active: newStatus
        }, {}, { auth: true });
        
        if (response?.status === 200 && response.body.status === 'success') {
          $toast('success', `Make ${newStatus === 'y' ? 'activated' : 'deactivated'} successfully`);
          await this.loadMMVTree();
        } else {
          $toast('error', response.body.message || 'Failed to update make status');
        }
      } catch (error) {
        console.error('Error toggling make status:', error);
        $toast('error', 'Failed to update make status');
      }
    },
    
    // ===== MODEL OPERATIONS =====
    openAddModelModal(make) {
      this.resetModelForm();
      this.selectedMakeForModel = make;
      this.modelForm.make_id = make.id;
      this.editingItem = null;
      this.editType = '';
      this.showAddModelModal = true;
    },
    
    openEditModelModal(model, make) {
      this.modelForm = {
        make_id: model.make_id,
        name: model.name,
        description: model.description || '',
        fuel_type: model.fuel_type || '',
        body_type: model.body_type || ''
      };
      this.selectedMakeForModel = make;
      this.editingItem = model;
      this.editType = 'model';
      this.showAddModelModal = true;
    },
    
    async saveModel() {
      if (!this.validateModelForm()) return;
      
      try {
        const action = this.editingItem ? 'update_model' : 'add_model';
        const data = { ...this.modelForm };
        
        if (this.editingItem) {
          data.id = this.editingItem.id;
        }
        
        const response = await $http('POST', '/mmv/', { 
          action, 
          ...data 
        }, {}, { auth: true });
        
        if (response?.status === 200 && response.body.status === 'success') {
          $toast('success', this.editingItem ? 'Model updated successfully' : 'Model added successfully');
          this.showAddModelModal = false;
          await this.loadMMVTree();
        } else {
          $toast('error', response.body.message || 'Failed to save model');
        }
      } catch (error) {
        console.error('Error saving model:', error);
        $toast('error', 'Failed to save model');
      }
    },
    
    async toggleModelStatus(model) {
      try {
        const newStatus = model.manual_active === 'y' ? 'n' : 'y';
        const response = await $http('POST', '/mmv/', {
          action: 'update_model',
          id: model.id,
          name: model.name,
          description: model.description || '',
          fuel_type: model.fuel_type || '',
          body_type: model.body_type || '',
          active: newStatus
        }, {}, { auth: true });
        
        if (response?.status === 200 && response.body.status === 'success') {
          $toast('success', `Model ${newStatus === 'y' ? 'activated' : 'deactivated'} successfully`);
          await this.loadMMVTree();
        } else {
          $toast('error', response.body.message || 'Failed to update model status');
        }
      } catch (error) {
        console.error('Error toggling model status:', error);
        $toast('error', 'Failed to update model status');
      }
    },
    
    // ===== VARIANT OPERATIONS =====
    openAddVariantModal(model, make) {
      this.resetVariantForm();
      this.selectedModelForVariant = model;
      this.selectedMakeForModel = make;
      this.variantForm.model_id = model.id;
      this.editingItem = null;
      this.editType = '';
      this.showAddVariantModal = true;
    },
    
    openEditVariantModal(variant, model, make) {
      this.variantForm = {
        model_id: variant.model_id,
        name: variant.name,
        description: variant.description || '',
        engine_capacity: variant.engine_capacity || '',
        transmission: variant.transmission || '',
        fuel_type: variant.fuel_type || '',
        price_range: variant.price_range || ''
      };
      this.selectedModelForVariant = model;
      this.selectedMakeForModel = make;
      this.editingItem = variant;
      this.editType = 'variant';
      this.showAddVariantModal = true;
    },
    
    async saveVariant() {
      if (!this.validateVariantForm()) return;
      
      try {
        const action = this.editingItem ? 'update_variant' : 'add_variant';
        const data = { ...this.variantForm };
        
        if (this.editingItem) {
          data.id = this.editingItem.id;
        }
        
        const response = await $http('POST', '/mmv/', { 
          action, 
          ...data 
        }, {}, { auth: true });
        
        if (response?.status === 200 && response.body.status === 'success') {
          $toast('success', this.editingItem ? 'Variant updated successfully' : 'Variant added successfully');
          this.showAddVariantModal = false;
          await this.loadMMVTree();
        } else {
          $toast('error', response.body.message || 'Failed to save variant');
        }
      } catch (error) {
        console.error('Error saving variant:', error);
        $toast('error', 'Failed to save variant');
      }
    },
    
    async toggleVariantStatus(variant) {
      try {
        const newStatus = variant.manual_active === 'y' ? 'n' : 'y';
        const response = await $http('POST', '/mmv/', {
          action: 'update_variant',
          id: variant.id,
          name: variant.name,
          description: variant.description || '',
          engine_capacity: variant.engine_capacity || '',
          transmission: variant.transmission || '',
          fuel_type: variant.fuel_type || '',
          price_range: variant.price_range || '',
          active: newStatus
        }, {}, { auth: true });
        
        if (response?.status === 200 && response.body.status === 'success') {
          $toast('success', `Variant ${newStatus === 'y' ? 'activated' : 'deactivated'} successfully`);
          await this.loadMMVTree();
        } else {
          $toast('error', response.body.message || 'Failed to update variant status');
        }
      } catch (error) {
        console.error('Error toggling variant status:', error);
        $toast('error', 'Failed to update variant status');
      }
    },
    
    // ===== FORM VALIDATION =====
    validateMakeForm() {
      this.formErrors = {};
      
      if (!this.makeForm.name.trim()) {
        this.formErrors.make_name = 'Make name is required';
      }
      
      return Object.keys(this.formErrors).length === 0;
    },
    
    validateModelForm() {
      this.formErrors = {};
      
      if (!this.modelForm.name.trim()) {
        this.formErrors.model_name = 'Model name is required';
      }
      
      if (!this.modelForm.make_id) {
        this.formErrors.make_id = 'Make selection is required';
      }
      
      return Object.keys(this.formErrors).length === 0;
    },
    
    validateVariantForm() {
      this.formErrors = {};
      
      if (!this.variantForm.name.trim()) {
        this.formErrors.variant_name = 'Variant name is required';
      }
      
      if (!this.variantForm.model_id) {
        this.formErrors.model_id = 'Model selection is required';
      }
      
      return Object.keys(this.formErrors).length === 0;
    },
    
    // ===== FORM RESET =====
    resetMakeForm() {
      this.makeForm = {
        name: '',
        description: ''
      };
      this.formErrors = {};
    },
    
    resetModelForm() {
      this.modelForm = {
        make_id: 0,
        name: '',
        description: '',
        fuel_type: '',
        body_type: ''
      };
      this.formErrors = {};
    },
    
    resetVariantForm() {
      this.variantForm = {
        model_id: 0,
        name: '',
        description: '',
        engine_capacity: '',
        transmission: '',
        fuel_type: '',
        price_range: ''
      };
      this.formErrors = {};
    },
    
    // ===== UTILITY METHODS =====
    getStatusBadgeClass(item) {
      return item.manual_active === 'y' ? 'bg-success' : 'bg-danger';
    },
    
    getStatusText(item) {
      return item.manual_active === 'y' ? 'Active' : 'Inactive';
    },
    
    closeAllModals() {
      this.showAddMakeModal = false;
      this.showAddModelModal = false;
      this.showAddVariantModal = false;
      this.editingItem = null;
      this.editType = '';
      this.selectedMakeForModel = null;
      this.selectedModelForVariant = null;
    }
  },

  template: /*html*/ `
    <div class="mmv-layout">
      <div class="container-fluid mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h2 class="mb-1">MMV Management</h2>
                <p class="text-muted">Manage Makes, Models, and Variants</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-primary" @click="openAddMakeModal">
                  <i class="bi bi-plus-circle me-2"></i>Add Make
                </button>
                <button class="btn btn-outline-secondary" @click="loadMMVTree">
                  <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Filters -->
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search makes, models, or variants..."
                v-model="searchQuery"
              >
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="showInactive"
                v-model="showInactive"
                @change="loadMMVTree"
              >
              <label class="form-check-label" for="showInactive">
                Show inactive items
              </label>
            </div>
          </div>
        </div>
        
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading MMV data...</p>
        </div>
        
        <!-- MMV Tree -->
        <div v-else class="mmv-tree">
          <div v-if="filteredMMVTree.length === 0" class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h4 class="mt-3 text-muted">No MMV data found</h4>
            <p class="text-muted">Start by adding a make to create your first MMV entry.</p>
          </div>
          
          <!-- Makes -->
          <div v-for="make in filteredMMVTree" :key="make.id" class="card mb-3">
            <div class="card-header bg-primary text-white">
              <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                  <i class="bi bi-car-front-fill me-2"></i>
                  <span class="fw-bold">{{ make.name }}</span>
                  <span class="badge ms-2" :class="getStatusBadgeClass(make)">
                    {{ getStatusText(make) }}
                  </span>
                  <small class="ms-2 opacity-75">
                    ({{ make.models_count || 0 }} models, {{ make.variants_count || 0 }} variants)
                  </small>
                </div>
                <div class="btn-group btn-group-sm">
                  <button 
                    class="btn btn-outline-light"
                    @click="toggleMakeStatus(make)"
                    :title="make.manual_active === 'y' ? 'Deactivate' : 'Activate'"
                  >
                    <i :class="make.manual_active === 'y' ? 'bi bi-toggle-on' : 'bi bi-toggle-off'"></i>
                  </button>
                  <button class="btn btn-outline-light" @click="openEditMakeModal(make)">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-outline-light" @click="openAddModelModal(make)">
                    <i class="bi bi-plus"></i> Model
                  </button>
                </div>
              </div>
              <div v-if="make.description" class="mt-2">
                <small class="opacity-75">{{ make.description }}</small>
              </div>
            </div>
            
            <!-- Models -->
            <div v-if="make.models && make.models.length > 0" class="card-body p-0">
              <div v-for="model in make.models" :key="model.id" class="border-bottom">
                <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                  <div class="d-flex align-items-center">
                    <i class="bi bi-diagram-2 me-2 text-secondary"></i>
                    <span class="fw-semibold">{{ model.name }}</span>
                    <span class="badge ms-2" :class="getStatusBadgeClass(model)">
                      {{ getStatusText(model) }}
                    </span>
                    <small class="ms-2 text-muted">
                      ({{ model.variants_count || 0 }} variants)
                    </small>
                    <div v-if="model.fuel_type || model.body_type" class="ms-2">
                      <span v-if="model.fuel_type" class="badge bg-secondary me-1">{{ model.fuel_type }}</span>
                      <span v-if="model.body_type" class="badge bg-secondary">{{ model.body_type }}</span>
                    </div>
                  </div>
                  <div class="btn-group btn-group-sm">
                    <button 
                      class="btn btn-outline-secondary"
                      @click="toggleModelStatus(model)"
                      :title="model.manual_active === 'y' ? 'Deactivate' : 'Activate'"
                    >
                      <i :class="model.manual_active === 'y' ? 'bi bi-toggle-on' : 'bi bi-toggle-off'"></i>
                    </button>
                    <button class="btn btn-outline-secondary" @click="openEditModelModal(model, make)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-secondary" @click="openAddVariantModal(model, make)">
                      <i class="bi bi-plus"></i> Variant
                    </button>
                  </div>
                </div>
                
                <!-- Variants -->
                <div v-if="model.variants && model.variants.length > 0" class="ps-4">
                  <div v-for="variant in model.variants" :key="variant.id" class="d-flex justify-content-between align-items-center p-2 border-bottom border-light">
                    <div class="d-flex align-items-center">
                      <i class="bi bi-gear me-2 text-muted"></i>
                      <span>{{ variant.name }}</span>
                      <span class="badge ms-2" :class="getStatusBadgeClass(variant)">
                        {{ getStatusText(variant) }}
                      </span>
                      <div v-if="variant.engine_capacity || variant.transmission" class="ms-2">
                        <span v-if="variant.engine_capacity" class="badge bg-light text-dark me-1">{{ variant.engine_capacity }}</span>
                        <span v-if="variant.transmission" class="badge bg-light text-dark">{{ variant.transmission }}</span>
                      </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                      <button 
                        class="btn btn-outline-secondary"
                        @click="toggleVariantStatus(variant)"
                        :title="variant.manual_active === 'y' ? 'Deactivate' : 'Activate'"
                      >
                        <i :class="variant.manual_active === 'y' ? 'bi bi-toggle-on' : 'bi bi-toggle-off'"></i>
                      </button>
                      <button class="btn btn-outline-secondary" @click="openEditVariantModal(variant, model, make)">
                        <i class="bi bi-pencil"></i>
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- No Variants Message -->
                <div v-else class="ps-4 py-2 text-muted fst-italic">
                  <i class="bi bi-info-circle me-1"></i>
                  No variants added yet. Add variants to activate this model.
                </div>
              </div>
            </div>
            
            <!-- No Models Message -->
            <div v-else class="card-body text-muted text-center">
              <i class="bi bi-info-circle me-1"></i>
              No models added yet. Add models to activate this make.
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add/Edit Make Modal -->
      <div class="modal fade show" tabindex="-1" v-if="showAddMakeModal" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ editingItem ? 'Edit Make' : 'Add New Make' }}</h5>
              <button type="button" class="btn-close" @click="closeAllModals"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveMake">
                <div class="mb-3">
                  <label class="form-label">Make Name *</label>
                  <input 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.make_name }"
                    v-model="makeForm.name"
                    placeholder="e.g., BMW, Mercedes, Audi"
                  >
                  <div v-if="formErrors.make_name" class="invalid-feedback">{{ formErrors.make_name }}</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea 
                    class="form-control" 
                    rows="3"
                    v-model="makeForm.description"
                    placeholder="Optional description"
                  ></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeAllModals">Cancel</button>
              <button type="button" class="btn btn-primary" @click="saveMake">
                {{ editingItem ? 'Update Make' : 'Add Make' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add/Edit Model Modal -->
      <div class="modal fade show" tabindex="-1" v-if="showAddModelModal" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ editingItem ? 'Edit Model' : 'Add New Model' }}
                <span v-if="selectedMakeForModel" class="text-muted"> - {{ selectedMakeForModel.name }}</span>
              </h5>
              <button type="button" class="btn-close" @click="closeAllModals"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveModel">
                <div class="mb-3">
                  <label class="form-label">Model Name *</label>
                  <input 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.model_name }"
                    v-model="modelForm.name"
                    placeholder="e.g., X5, E-Class, A4"
                  >
                  <div v-if="formErrors.model_name" class="invalid-feedback">{{ formErrors.model_name }}</div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Fuel Type</label>
                    <select class="form-select" v-model="modelForm.fuel_type">
                      <option value="">Select fuel type</option>
                      <option v-for="type in fuelTypes" :key="type" :value="type">{{ type }}</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Body Type</label>
                    <select class="form-select" v-model="modelForm.body_type">
                      <option value="">Select body type</option>
                      <option v-for="type in bodyTypes" :key="type" :value="type">{{ type }}</option>
                    </select>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea 
                    class="form-control" 
                    rows="3"
                    v-model="modelForm.description"
                    placeholder="Optional description"
                  ></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeAllModals">Cancel</button>
              <button type="button" class="btn btn-primary" @click="saveModel">
                {{ editingItem ? 'Update Model' : 'Add Model' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add/Edit Variant Modal -->
      <div class="modal fade show" tabindex="-1" v-if="showAddVariantModal" style="display: block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                {{ editingItem ? 'Edit Variant' : 'Add New Variant' }}
                <span v-if="selectedMakeForModel && selectedModelForVariant" class="text-muted">
                  - {{ selectedMakeForModel.name }} {{ selectedModelForVariant.name }}
                </span>
              </h5>
              <button type="button" class="btn-close" @click="closeAllModals"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="saveVariant">
                <div class="mb-3">
                  <label class="form-label">Variant Name *</label>
                  <input 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.variant_name }"
                    v-model="variantForm.name"
                    placeholder="e.g., xDrive30d, E 200, 2.0 TFSI"
                  >
                  <div v-if="formErrors.variant_name" class="invalid-feedback">{{ formErrors.variant_name }}</div>
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Engine Capacity</label>
                    <input 
                      type="text" 
                      class="form-control"
                      v-model="variantForm.engine_capacity"
                      placeholder="e.g., 2.0L, 3.0L, 1.5L"
                    >
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Transmission</label>
                    <select class="form-select" v-model="variantForm.transmission">
                      <option value="">Select transmission</option>
                      <option v-for="type in transmissionTypes" :key="type" :value="type">{{ type }}</option>
                    </select>
                  </div>
                </div>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Fuel Type</label>
                    <select class="form-select" v-model="variantForm.fuel_type">
                      <option value="">Select fuel type</option>
                      <option v-for="type in fuelTypes" :key="type" :value="type">{{ type }}</option>
                    </select>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Price Range</label>
                    <input 
                      type="text" 
                      class="form-control"
                      v-model="variantForm.price_range"
                      placeholder="e.g., ₹25-30 Lakhs"
                    >
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea 
                    class="form-control" 
                    rows="3"
                    v-model="variantForm.description"
                    placeholder="Optional description"
                  ></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeAllModals">Cancel</button>
              <button type="button" class="btn btn-primary" @click="saveVariant">
                {{ editingItem ? 'Update Variant' : 'Add Variant' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  `,
};
