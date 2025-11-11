export const AddExchVinBonus = {
  name: 'AddExchVinBonus',
  props: {
    visible: { type: Boolean, default: false },
    title: { type: String, default: 'Assign New Car VIN & Bonus' },
    store: { type: Object, required: true },
    row: { type: Object, default: () => ({}) }
  },
  emits: ['close', 'select'],
  data: () => ({
    id: '',
    new_chassis: '',
    benefit_flag:'',
    bonus_price: null,
    show_flag:false,
    isSubmitting: false,
    formErrors:{
      "new_chassis":"",
      "benefit_flag":"",
      "bonus_price":""
    }
  }),
  watch: {
    visible(val) {
      this.formErrors={};
      this.id=this.row.id;
      this.new_chassis=this.row.new_chassis;
      this.benefit_flag=this.row.benefit_flag;
      this.bonus_price=this.row.bonus_price;
      if(this.benefit_flag=='Yes'){
        this.show_flag=true;
      }else{
         this.show_flag=false;
      }
    },
  },
  computed: {
    modalTitle() {
      return this.row?.formatted_id
        ? `${this.title}`
        : this.title;
    },
  },
  methods: {
    async updateNewCarData() {
      this.formErrors={};
       if (!this.store) return;
       try {
          const res = await this.store.updateNewCarData({
            id:this.id,
            new_chassis: this.new_chassis,
            benefit_flag: this.benefit_flag,
            bonus_price: this.bonus_price
          });
        if(!res.success){
          this.formErrors={...res.val_errors};
          return;
        }
        if (res.success) {
          this.closeModal();
          const result=await this.store.getList();
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    change_benfit(){
      if(this.benefit_flag=='Yes'){
        this.show_flag=true;
      }else{
        this.show_flag='';
        this.bonus_active=false;
      }
    },
    closeModal() {
      this.formErrors={};
      this.show_flag=false;
      this.new_chassis='',
      this.benefit_flag='',
      this.bonus_price='';
      this.$emit('close');
    },
  },
  template: /*html*/`
  <div v-if="visible" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4);">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content shadow-sm border-0 rounded-3">
        <div class="modal-header popup-head  text-light">
          <h5 class="modal-title text-dark" id="hsnModalLabel">{{ modalTitle }}</h5>
           <button type="button" class="btn-close" @click="closeModal" :disabled="isSubmitting"></button>
        </div>
        <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label fw-semibold">Enter New Car VIN </label>
                  <input class="form-control" type="text" v-model="new_chassis" placeholder="Enter VIN number"/>
                  <div v-if="formErrors.new_chassis" class="text-danger">{{ formErrors.new_chassis }}</div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">Offer Exchange Benefit</label><br>
                <div class="form-check form-check-inline">
                  <input type="radio" v-model="benefit_flag" value="Yes" class="form-check-input" id="offExchYes" v-on:change="change_benfit"> 
                  <label class="form-check-label" for="offExchYes">Yes</label>
                </div>
                <div class="form-check form-check-inline">
                  <input type="radio" v-model="benefit_flag" value="No" class="form-check-input" id="offExchNo" v-on:change="change_benfit"> 
                  <label class="form-check-label" for="offExchNo">No</label>
                </div>
                <div v-if="formErrors.benefit_flag" class="text-danger">{{ formErrors.benefit_flag }}</div>
              </div>
              <div class="mb-3" v-if="show_flag">
                <label class="form-label fw-semibold">Enter Bonus Amount </label>
                <input class="form-control" type="text" v-model="bonus_price" placeholder="Enter Bonus"/>
                <div v-if="formErrors.bonus_price" class="text-danger">{{ formErrors.bonus_price }}</div>
              </div>
        </div>
        <div class="modal-footer">
           <button class="btn btn-sm btn-outline-secondary" @click="closeModal" :disabled="isSubmitting">Cancel</button>
            <button class="btn btn-sm btn-dark" 
                    
                    @click="updateNewCarData()">
              <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-1"></span>
              Update
            </button>
        </div>
    </div>
  </div>
</div>`
};
