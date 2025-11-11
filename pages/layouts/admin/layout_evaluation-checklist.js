export default {
   name : 'Evaluation-checklist',
   data(){
      return{
         sectionsList : [],
         templates:[],
         template:{
            template_name:'',
            template_description:'',
            status:''
         },
        template_validation:[],
         itemsList : [],
         formMode : 'Add',
         form_data: {
            section_name : '',
            active : ''
         },
         formErrors : '',
         mpi : 1,
         section_id : '',
         section_name : '',
         active : '',
         isSubmitting : false,
         field : {
          type : ''
         },
         fieldList : ["checkbox","radio","dropdown","textfield"],
         items : [
            { type: "", options: [ {id: Date.now() ,value : ''}] }
         ],
         checklist_id : '',
         items_data : {
          name : '',
          field_type : '',
          options : [],
          active : '',
          text_value : ''
         },
         itemformErrors : {
          name : '',
          field_type: '',
          options:''
         },
         item_id : '',
         item_name :'',
         temp_id : '',
         temp_name : '',
         temp_status : '',
      }
   },
   mounted(){
      this.getSectionsList();
      this.getItemNames();
   },
   computed:{
    activeTemplates() {
        return Object.values(this.templates).filter(t => t.status === 1);
    }
   },
   methods:{

       async getSectionsList()
       { 

         const data = {
              action : 'getsections',
              mpi : this.mpi,
          
          };
    
         try
         {
            const response = await $http('POST',`${g.$base_url_api}/admin/evaluation-checklist`,{...data},{});

            if(response.body.status === "ok" && Array.isArray(response.body.data['sections-list']))
            {
                this.sectionsList = response.body.data['sections-list'];
                this.templates = response.body.data['templates'];
            }
          }
          catch(error)
          {
            this.sectionsList = [];
          }
       },
        async getItemNames()
       { 
         try
         {
            const data = {
              action : 'getitems'
            };
            const response = await $http('POST',`${g.$base_url_api}/admin/evaluation-checklist`,{...data},{});
            if(response.body.status === "ok")
            {
              this.itemsList = response.body.data['items-list'];
            }
            else
            {
                this.itemsList = [];
            }
          }
          catch(error)
          {
            return { status: error.status, body: error.body };
          }
       },
       openModal()
       {
          $("#commonModal").modal("show");
       },
       closeModal()
       {
          this.formErrors = '';
          $("#commonModal").modal("hide");
       },
       addModal()
       {
          this.openModal();
          this.form_data = {
            section_name: '',
            active : 'y'
          };
          this.formMode = "Add";
       },
       editModal(formData)
       {
          this.openModal();
          this.form_data.section_name = formData.section_name;
          this.form_data.active = formData.active;
          this.section_id = formData.id;
          this.formMode = "Edit";
       },
       addSubitem(id)
       {
          this.checklist_id = id;
          this.openSubitemsModal();
          this.items_data = {
            name: '',
            field_type : 'radio',
            options : [],
            text_value : ''
          };
          this.items = [{
              type : '',
              options : []
           }];
          this.formMode = "Add";
       },
     editSubitem(itemData,id) {
        this.checklist_id = id;
        this.item_id = itemData.id;
        this.formMode = "Edit";
        this.openSubitemsModal();
        let optionsArr = [];

       if(itemData.field_type != "text")
       {
           if (Array.isArray(itemData.options)) 
           {
              optionsArr = itemData.options.map(opt => ({ value: opt }));
           } 
           else if (typeof itemData.options === 'string') 
           {
             optionsArr = itemData.options
             .split(',')
             .map(opt => ({ value: opt.trim() }));
           }
       }
       else
       {
         optionsArr = itemData.options;
       }

        this.items_data = {
          name: itemData.name,
          field_type: itemData.field_type,
          active : itemData.active,
          options: optionsArr,
          text_value : optionsArr
        };

        this.items = [
          {
            type: itemData.field_type,
            options: optionsArr.length ? optionsArr : [{ value: "" }]
          }
        ];
      },

       deleteSection(data)
       {
          this.section_id = data.id;
          this.section_name = data.section_name;
          this.active = data.active;
          this.formMode = 'Delete';
          this.opendeleteModal();
       },
       deleteTemplate(data)
       {
          this.temp_id = data.id;
          this.temp_name = data.template_name;
          this.temp_status = data.status;
          this.template = {...data};
          this.opentempdeleteModal();
       },
       deleteItem(data,id)
       {
            this.checklist_id = id;
            this.item_id = data.id;
            this.formMode = "Delete";
            let optionsArr = [];

           if(data.field_type != 'text')
           {
              if (Array.isArray(data.options)) 
              {
                optionsArr = data.options.map(opt => ({ value: opt }));
              } 
              else if(typeof data.options === 'string') 
              {
                 optionsArr = data.options
                .split(',')
                .map(opt => ({ value: opt.trim() }));
              }
           }
           else
           {
             optionsArr = data.options;
           }

            this.items_data = {
              name: data.name,
              field_type: data.field_type,
              active : data.active,
              options: optionsArr,
              text_value : optionsArr
            };

            this.items = [
              {
                type: data.field_type,
                options: optionsArr.length ? optionsArr : [{ value: "" }]
              }
            ];
            this.item_name = data.name;
            this.opensubitemdeleteModal();
       },
       async submitSectionData()
       {
          this.formErrors = '';
          let sub_action = '';
          this.isSubmitting = true; 

          
          sub_action = this.formMode == "Add" ? "addsection" : "editsection";
         
          const payload = {
            action : 'savesection',
            sub_action : sub_action,
            section_id : this.section_id,
            section_name : this.form_data.section_name,
            evaluation_type : this.mpi,
            active : this.form_data.active
          };
          
          try
          {
              const res = await $http("POST",`${g.$base_url_api}/admin/evaluation-checklist`,{...payload},{});
              
              if(res.body.status === "ok")
              {
                if(sub_action =="addsection")
                {
                  $toast('success', res.body.message || 'Section added');
                  //refresh
                  this.getSectionsList(); 
                  this.getItemNames();
                }
                else
                {
                  $toast('success', res.body.message || 'Section updated');
                  //refresh
                  this.getSectionsList(); 
                  this.getItemNames();
                }
               this.formErrors = '';
               this.closeModal();
              }
          }
          catch(error)
          {
              if(sub_action == "addsection")
              {
                $toast('danger',error.message || 'Failed to add section');
              }
              else
              {
                $toast('danger',error.message || 'Failed to update section details');
              }
              this.formErrors = error.body.msg;
          }
          finally{
             this.isSubmitting = false;
          }
       },
       async handleDeleteSection()
       {
         let sub_action = '';
            sub_action = this.formMode == "Delete" ? "editsection" : '';
            this.isSubmitting = true;
            const newAction = this.active === 'y' ? 'n' : 'y';
            
            const payload = {
            action : 'savesection',
            sub_action : sub_action,
            section_id : this.section_id,
            section_name : this.section_name,
            evaluation_type : this.mpi,
            active : newAction
          };
        
          try
          {
              const response =  await $http("POST",`${g.$base_url_api}/admin/evaluation-checklist`,{...payload},{});
              
              if(response.body.status == "ok")
              {
                  if(newAction == 'y')
                  {
                    $toast('success', response.body.message || 'Checklist activated');
                  }
                  else
                  {
                    $toast('success', response.body.message || 'Checklist deactivated');
                  }
                  this.closedeleteModal();
                  //refresh
                  this.getSectionsList(); 
                  this.getItemNames();
              }
          }
          catch(error)
          {
              
            if(newAction == 'y')
            {
              $toast('danger', error.message || 'Failed to activate checklist');
            }
            else
            {
              $toast('danger', error.message || 'Failed to deactivate checklist');
            }
          }
          finally{
               this.isSubmitting = false;
          }
       },
       opendeleteModal()
       {
          $("#deleteConfirmModal").modal("show");
       },
       closedeleteModal()
       {
          $("#deleteConfirmModal").modal("hide");
          $("#templateModal").modal("hide");
       },
       opensubitemdeleteModal()
       {
          $("#deleteitemConfirmModal").modal("show");
       },
       closesubitemdeleteModal()
       {
          $("#deleteitemConfirmModal").modal("hide");
       },
       opentempdeleteModal()
       {
          $("#deletetempConfirmModal").modal("show");
       },
       closetempdeleteModal()
       {
          $("#deletetempConfirmModal").modal("hide");
       },
       openSubitemsModal()
       {
          $("#commonitemsModal").modal("show");
       },
        async handleDeleteSubitem()
        { 
            let sub_action = '';
            sub_action = this.formMode == "Delete" ? "editSubitem" : '';
            this.isSubmitting = true;
            const newAction = this.items_data.active === 'y' ? 'n' : 'y';
          
            const payload = {
              "action" : 'savesubitemdata',
              "subitem_action" : sub_action,
              "name" : this.items_data.name,
              "field_type" : this.items_data.field_type,
              "options" : this.items[0].options,
              "checklist_id" : this.checklist_id,
              "item_id" : this.item_id, 
              "active" : newAction,
              "mpi" : this.mpi,
          };
        
          try
          {   
              const response =  await $http("POST",`${g.$base_url_api}/admin/evaluation-checklist`,{...payload},{});

              if(response.body.status == "ok")
              {
                  if(newAction == 'y')
                  {
                    $toast('success', response.body.message || 'Sub item activated');
                  }
                  else
                  {
                    $toast('success', response.body.message || 'Sub item deactivated');
                  }
                  this.closesubitemdeleteModal();
                  //refresh
                  this.getSectionsList(); 
                  this.getItemNames();
              }
          }
          catch(error)
          {
              if(newAction == 'y')
              {
                $toast('danger', error.body.msg || 'Failed to activate subitem');
              }
              else
              {
                $toast('danger', error.body.msg || 'Failed to deactivate subitem');
              }
          }
          finally{
               this.isSubmitting = false;
          }
       },
       closeSubitemsModal()
       {
          this.itemformErrors = '';
          this. items = [
            { type: "", options: [] }, 
         ],
          this.items_data={
             field_type : '',
             name : ''
          };
          $("#commonitemsModal").modal("hide");
       },
       addOption(item) 
       { 
         if(item.options.length > 4)
         {
           return false;
         }
         item.options.push({ id: Date.now(),value: "" })
       },
       removeOption(item, index) 
       {
         if(item.options.length == 1)
         {
           return false;
         }
         item.options.splice(index, 1)
       },
       async submitSubitemData()
       {
            if(this.items_data.field_type === "text") 
            {
              this.items.options = this.items_data.text_value;   
            } 
            else 
            {
              if(!this.items.options || this.items.options.length === 0) 
              {
                this.items.options = [{ value: "" }]
              }
            }          
          
          this.itemformErrors = [];
          const subitem_action = (this.formMode == 'Add') ? 'addSubitem' : 'editSubitem';
          // Validation

          if(this.items_data.name == "" || this.items_data.field_type == "" || this.items_data.options == "")
          {
            this.itemformErrors.name = this.items_data.name ? '' : 'Sub item name is required.';
            this.itemformErrors.field_type = this.items_data.field_type ? '' : 'Field type is required.';
            this.itemformErrors.options = this.items_data.options ? '' : (subitem_action == 'addSubitem')? 'Please add options.':'Option value is required.';
          }
 
          const payload = {
             "action" : 'savesubitemdata',
             "subitem_action" : subitem_action,
             "name" : this.items_data.name,
             "field_type" : this.items_data.field_type,
             "options" : this.items_data.field_type === "text" ? this.items.options : this.items[0].options,
             "checklist_id" : this.checklist_id,
             "item_id" : this.item_id,
             "active" : this.items_data.active,
              "mpi" : this.mpi,
          };
          
          try
          {
              const response = await $http("POST",`${g.$base_url_api}/admin/evaluation-checklist`,{...payload},{});          
              
              if(response.body.status === "ok")
              {
                if(subitem_action == "addSubitem")
                {
                    $toast('success', response.body.message || 'Item added');
                    //refresh
                    this.getSectionsList(); 
                    this.getItemNames();
                }
                else
                {
                    $toast('success', response.body.message || 'Item updated');
                    //refresh
                    this.getSectionsList(); 
                    this.getItemNames();
                }
                this.itemformErrors = '';
                this.closeSubitemsModal();
              }
          }
          catch(error)
          {
             
            if(subitem_action == "addSubitem")
            {
              $toast('danger', error.body.msg|| 'Failed to add item');
            }
            else
            {
              $toast('danger', error.body.msg || 'Failed to update item details');
            }
          }
       },
       onDragStart(event, dragIndex) 
       {
          event.dataTransfer.effectAllowed = 'move';
          event.dataTransfer.setData('dragIndex', dragIndex);
       },
      onDrop(event, dropIndex) 
      {
          const dragIndex = parseInt(event.dataTransfer.getData('dragIndex'));
          if (isNaN(dragIndex) || dragIndex === dropIndex) return;

          // Move the dragged section
          const moved = this.sectionsList.splice(dragIndex, 1)[0];
          this.sectionsList.splice(dropIndex, 0, moved);

          // Update sort_order for each section
          this.sectionsList.forEach((section, idx) => {
            section.sort_order = idx + 1;
          });

          // Persist new order to backend
          this.updateSectionOrder();
      },
      async updateSectionOrder() {
       
        const order = this.sectionsList.map(section => ({
          id: section.id,
          sort_order: section.sort_order
        }));

        const payload = {
             action: 'update_section_order',
             order: order
        }

        try
        {
          const response = await $http('POST', `${g.$base_url_api}/admin/evaluation-checklist`, { ...payload }, {});
          if(response.body.status == "ok")
          {
            $toast('success', 'Section order updated'); 
          }
        } 
        catch(error)
        {
           $toast('danger', 'Failed to update section order');
        }
      },
        templateModal(tid){              
            $("#templateModal").modal("show");
            this.template = {}
            if(tid>0){
                this.template = {...this.templates[tid]}
                console.log(this.template)
            }
        },
        async handleDeleteTemplate()
        {
            const tid = this.template.id;
            if(!tid)
            {
              return;
            }
            const newStatus = this.template.status ? 0 : 1;

              const newData = {
                    ...this.template,
                    status: newStatus
                };
            try
            {
                const data = {
                    action : 'saveTemplate',                    
                    data : newData
                };            
                const response = await $http('POST',`${g.$base_url_api}/admin/evaluation-checklist`,{...data},{});                         
                if( response.status == 200 ){
                    let body = response.body;
                    if( body.status == "ok" ){
                        this.templates = body.data;
                        $toast('success', body.msg || 'Template details are updated successfully');                  
                        this.closetempdeleteModal();
                        // refresh 
                        this.getSectionsList();
                    }                    
                }
            }
            catch(e){                          
                this.template_validation = error.body.errors;               
                $toast('danger', e.msg || 'Template details not updated'); 
            } 
        },
        async saveTemplate()
        {
            try
            {
                const data = {
                    action: 'saveTemplate',                    
                    data:this.template
                };            
                const response = await $http('POST',`${g.$base_url_api}/admin/evaluation-checklist`,{...data},{});                         
                if( response.status == 200 ){
                    let body = response.body;
                    if( body.status == "ok" ){
                        this.templates = body.data;  
                        $("#templateModal").modal("hide"); 
                        this.closetempdeleteModal();
                        $toast('success', body.msg || 'Template details are updated successfully');  
                        this.getSectionsList();                
                    }                    
                }
            }
            catch(e){                          
                this.template_validation = error.body.errors;               
                $toast('danger', e.msg || 'Template details not updated'); 
            } 
        }
   },
    template:/*html*/`
      <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center px-1 py-2 border-bottom">
        <h6 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1"><i class="bi bi-palette text-secondary"></i> Evaluation Checklist</h6>
      </div>
    <div class="container-fluid py-3 bg-light min-vh-100 evaluation" id="grid-container">
    <div class="row justify-content-center" id="grid-panel">
        <div class="col-lg-12 col-xl-12">
        <div class="row g-4"> 
             <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 ">
                    <div class="card-body">                        
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">  
                            <h5 class="text-dark modal-title">Templates</h5>                      
                            <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add Template"  v-on:click="templateModal(0)"><i class="bi bi-plus-lg me-1"></i> Add Template </button>
                        </div>                       
                        <ul class="list-group">
                            <li  class="list-group-item d-flex flex-column flex-sm-row align-items-center" v-for="temp,index in templates" :key="temp.id">
                                <div class="flex-grow-1">                                    
                                    <div class="fw-bold">{{temp.template_name}}</div>
                                    <small class="text-muted">{{temp.template_description}}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm rounded-1 px-2 py-1" class="btn-outline-secondary" title="Edit template" v-on:click="templateModal(temp.id)" ><i class="bi bi-pencil fs-6"></i></button>
                                    <!--Activate/Deactivate template-->
                                     <button
                                          v-if="temp.status"
                                          class="btn btn-sm btn-outline-success rounded-1 me-3 py-0 px-1"
                                          @click.stop="deleteTemplate(temp)"
                                      >
                                          <i class="bi bi-toggle-on fs-5"></i>
                                      </button>
                                      <button
                                          v-else
                                          class="btn btn-sm btn-outline-danger rounded-1 me-3 py-0 px-1"
                                          @click.stop="deleteTemplate(temp)"
                                      >
                                          <i class="bi bi-toggle-off fs-5"></i>
                                      </button>
                                </div>

                            </li>
                           
                        </ul>
                    </div>
                </div>
            </div>                
              
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-body">

                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark modal-title">Evaluation Checklist</h5>
                        <select class="form-select form-select-sm w-25" v-model="mpi" @change="getSectionsList">
                            <option :value="temp.id" v-for="(temp,index) in activeTemplates" :key="temp.id"  >{{temp.template_name}}</option>                            
                        </select>
                        <button class="btn btn-dark rounded-1 px-4" @click="addModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Checklist
                        </button>
                    </div>

            <!-- Accordion start -->
            <div class="accordion" id="sectionsAccordion">
                <div class="text-danger text-center accordion-item mb-3 border-0 shadow-sm rounded h6 mt-5 p-4" v-if="mpi == 0">
                    No items found.
                </div>

                <div v-else v-for="(section, index) in sectionsList" :key="section.id" class="accordion-item mb-3 shadow-sm rounded">
                    <!-- Accordion header + drag icon row -->
                <div class="d-flex align-items-center justify-content-between">
                        <!-- Accordion Header -->
                        <h2 class="accordion-header flex-grow-1" :id="'heading' + section.id">
                            <button
                            class="accordion-button py-3 fw-medium text-dark rounded w-100"
                            :class="{ collapsed: index !== 0 }"
                            type="button"
                            data-bs-toggle="collapse"
                            :data-bs-target="'#collapse' + section.id"
                            :aria-expanded="index === 0 ? 'true' : 'false'"
                            :aria-controls="'collapse' + section.id"
                            >
                            <span class="d-flex align-items-center w-100 justify-content-between">
                        <span>
                        {{ section.section_name }}
                        <span class="badge rounded-pill bg-dark ms-1">
                            {{ section.items_count }} <i class="bi bi-arrow-down-short"></i>
                        </span>
                       
                        </span>
                        <!--  <span
                            :class="{
                            'badge text-bg-success': section.active == 'y',
                            'badge text-bg-danger': section.active == 'n'
                            }"
                            class="ms-5"
                        >
                            {{ section.active == 'y' ? 'Active' : 'Inactive' }}
                        </span> -->

                        <div class="mt-auto d-flex gap-2 justify-content-center">
                        <button
                            class="btn btn-sm btn-outline-secondary rounded-1 px-2 py-0"
                            @click.stop="editModal(section)"
                        >
                            <i class="bi bi-pencil fs-6"></i>
                        </button>

                        <button
                            v-if="section.active == 'y'"
                            class="btn btn-sm btn-outline-success rounded-1 me-3 py-0 px-1"
                            @click.stop="deleteSection(section)"
                        >
                            <i class="bi bi-toggle-on fs-5"></i>
                        </button>
                        <button
                            v-else
                            class="btn btn-sm btn-outline-danger rounded-1 me-3 py-0 px-1"
                            @click.stop="deleteSection(section)"
                        >
                            <i class="bi bi-toggle-off fs-5"></i>
                        </button>
                        </div>
                    </span>
                    </button>
                    </h2>

                    <i class="bi bi-grip-vertical h5 rounded-1 p-1 me-3 ms-2 mt-0 mb-0" style="cursor: grab;" draggable="true" @dragstart="onDragStart($event, index)" @dragover.prevent @drop="onDrop($event, index)" @dragenter.prevent title="Drag to re-order"></i>
                    </div>

                    <div :id="'collapse' + section.id" class="accordion-collapse collapse" :class="{ show: index === 0 }" :aria-labelledby="'heading' + section.id" data-bs-parent="#sectionsAccordion">
                        <div class="accordion-body bg-white rounded shadow-sm p-3">
                            <button class="btn btn-sm btn-dark ms-3 rounded-pill float-end" @click="addSubitem(section.id)">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                            <br />

                            <ul class="list-group list-group-flush">
                                <li v-for="(item, idx) in (itemsList.find(i => i.id == section.id)?.items || [])" :key="idx" class="list-group-item d-flex flex-column rounded mb-2 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center w-100">
                                        <span>{{ item.name }}</span>
                                        <select v-model="field.type" class="form-select selectField" disabled>
                                            <option value="">{{ item.field_type }}</option>
                                        </select>

                                        <div class="d-flex gap-2">
                                            <button class="btn btn-outline-warning btn-sm rounded-pill fw-semibold" @click="editSubitem(item, section.id)">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <button v-if="item.active == 'y'" class="btn btn-sm btn-outline-success rounded-pill me-3" @click="deleteItem(item, section.id)">
                                                <i class="bi bi-toggle-on"></i>
                                            </button>
                                            <button v-else class="btn btn-sm btn-outline-danger rounded-pill me-3" @click="deleteItem(item, section.id)">
                                                <i class="bi bi-toggle-off"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Options Rendering -->
                                    <ul v-if="item.options && item.options.length" class="mt-2 listItemStyle">
                                        <li v-for="(option, optIdx) in item.options" :key="optIdx" class="listStyle mb-1" v-if="item.field_type == 'radio' || item.field_type == 'checkbox'">
                                            <input :type="item.field_type" class="me-2" name="field_type" :value="item.field_type" disabled />
                                            <input type="text" class="border-0 bg-transparent text-capitalize" :value="option" disabled />
                                        </li>

                                        <li class="listStyle mb-1" v-else-if="item.field_type == 'text'">
                                            <input class="form-control form-control-sm border-top-0 border-start-0 border-end-0 w-25" :type="item.field_type" :value="item.options" disabled />
                                        </li>

                                        <li class="listStyle mb-1" v-else>
                                            <select class="form-select form-select-sm w-25" disabled>
                                                <option v-for="(i, index) in item.options" :key="index">{{ i }}</option>
                                            </select>
                                        </li>
                                    </ul>
                                </li>

                                <li v-if="!(itemsList.find(i => i.id == section.id)?.items || []).length" class="text-muted fst-italic">
                                    No items added
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                </div></div></div></div>
            </div>
        </div>
    </div>
    <!-- Accordion end -->
    
    <!-- TemplateModal-->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title text-primary" id="templateModalLabel">Template</h5>
                    <button type="button" class="btn-close" v-on:click="closedeleteModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Template Name</label>
                        <input type="text" class="form-control form-control-sm shadow-sm" v-model="template.template_name"  />
                        <div  class="text-danger small" v-html="template_validation['template_name']"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Template Description</label>
                        <input type="text" class="form-control form-control-sm shadow-sm" v-model="template.template_description"  />
                        <div  class="text-danger small" v-html="template_validation['template_description']"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Active:</label>
                        <div class="form-check">
                            <input class="form-check-input" value="1" type="radio" v-model="template.status" id="template_active">
                            <label class="form-check-label"  for="radioDefault1">Yes</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" value="0" type="radio" v-model="template.status" id="template_inactive">
                            <label class="form-check-label" for="radioDefault2">No</label>
                        </div>
                        <div  class="text-danger small" v-html="template_validation['status']"></div>
                    </div>                                        
                </div>
				<div class="modal-footer border-0 pt-2">
					<button type="button" class="btn btn-secondary rounded-pill" v-on:click="closedeleteModal()">Cancel</button>
					<button type="button" class="btn btn-dark rounded-pill" v-on:click="saveTemplate()" >Save</button>
				</div>
			</div>
        </div>
	</div>
  <!-- Add/Edit Section Modal -->
  <div class="modal fade" id="commonModal" tabindex="-1" aria-labelledby="commonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded shadow-lg">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title text-primary" id="commonModalLabel">{{formMode}} Checklist</h5>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Section Name</label>
            <input type="text" class="form-control form-control-sm shadow-sm" v-model="form_data.section_name" @input="formErrors = ''"/>
            <div v-if="formErrors" class="text-danger small">{{ formErrors }}</div>
          </div>
          <div class="mb-3">
           <div v-if="formMode == 'Edit'">
              <label class="form-label fw-semibold">Active:</label><br/>
              <input type="radio" value='y' class="mt-1" v-model="form_data.active" />Yes &nbsp;&nbsp;
              <input type="radio" value='n' class="mt-1" v-model="form_data.active" />No
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-secondary rounded-pill" @click="closeModal">Cancel</button>
          <button type="button" class="btn btn-dark rounded-pill" @click="submitSectionData">Save</button>
        </div>
      </div>
    </div>
  </div>

    <!-- Delete Section Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded shadow-lg border-0">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title text-danger fw-bold">Confirm {{active === 'y' ? 'Deactivate' : 'Activate'}}</h5>
            <button type="button" class="btn-close" @click="closedeleteModal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to <strong>{{active === 'y' ? 'deactivate' : 'activate'}}</strong> the <b>Section</b> "<b>{{section_name}}</b>"?
          </div>
          <div class="modal-footer border-0 pt-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill" @click="closedeleteModal">Cancel</button>
            <button type="button" class="btn btn-danger rounded-pill" @click="handleDeleteSection" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{active === 'y' ? 'Deactivate' : 'Activate'}}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
   <!-- Add/Edit items Modal -->
  <div class="modal fade" id="commonitemsModal" tabindex="-1" aria-labelledby="commonitemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded shadow-lg">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title text-primary" id="commonitemsModalLabel">{{formMode}} Item</h5>
          <button type="button" class="btn-close" @click="closeSubitemsModal"></button>
        </div>
        <div class="modal-body">

          <!-- Item name -->
          <label class="mt-2 fw-semibold">Item name:</label>
          <input type="text" class="form-control form-control-sm mt-2 shadow-sm" v-model="items_data.name" @input="itemformErrors.name = ''"/>
          <div v-if="itemformErrors.name != ''" class="text-danger small">{{ itemformErrors.name }}</div>
          
          <div v-for="(item, idx) in items" :key="idx" class="mt-3">

             <!-- Filed type -->
            <label class="mt-2 fw-semibold">Field Type:</label>
            <select v-model="items_data.field_type" class="form-select form-select-sm mt-2 shadow-sm">
              <option value="" disabled>--Select field type--</option>
              <option value="text">Text</option>
              <option value="radio">Radio</option>
              <option value="checkbox">Checkbox</option>
              <option value="dropdown">Dropdown</option>
            </select>
            <div v-if="itemformErrors.field_type != ''" class="text-danger small">{{ itemformErrors.field_type }}</div>
        
             <!-- Text field -->
            <div v-if="items_data.field_type === 'text'" class="mt-2">
              <input type="text" v-model="items_data.text_value" class="form-control form-control-sm border-top-0 border-start-0 border-end-0 mt-2 w-75 inputfieldStyle" placeholder="Short answer" />
            </div>

            <div v-else class="mt-2">
              <div v-for="(option, oidx) in items[0].options" :key="oidx" class="d-flex align-items-center mt-2">
                   
               <!-- option icon -->
                 <input :type="items_data.field_type" v-model="items_data.field_type" v-if="(items_data.field_type != 'dropdown')" disabled/> 
              
               <!-- options -->
               <input type="text" class="form-control form-control-sm border-top-0 border-start-0 border-end-0 w-25 inputfieldStyle"
               v-model="option.value" :placeholder="'Option ' + (oidx + 1)" :id="'option-' + oidx" v-if="items_data.field_type != '' || items_data.field_type == 'dropdown'" />
               <div v-if="itemformErrors.options != ''" class="text-danger small">{{ itemformErrors.options }}</div>


                <!--Remove option button-->
                <button class="btn btn-sm btn-danger ms-3 mt-2 rounded-pill" @click="removeOption(item, oidx)" v-if="items_data.field_type != ''">
                  <i class="bi bi-x"></i>
                </button>
              </div>

               <!-- Active -->
               <div v-if="formMode == 'Edit'">
                  <label class="mt-2 mb-2 fw-semibold">Active:</label><br/>
                  <input type="radio" value='y' class="mt-1" v-model="items_data.active"/>Yes &nbsp;&nbsp;
                  <input type="radio" value='n' class="mt-1" v-model="items_data.active" />No
               </div>

              <!--Add option button-->
                <button class="btn btn-sm btn-success ms-3 rounded-pill float-end" @click="addOption(item)" v-if="items_data.field_type != ''">
                 <i class="bi bi-plus-lg"></i>
                </button>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-2">
          <button type="button" class="btn btn-secondary rounded-pill" @click="closeSubitemsModal">Cancel</button>
          <button type="button" class="btn btn-dark rounded-pill" @click="submitSubitemData()">Save</button>
        </div>
      </div>
    </div>
  </div>

   <!-- Delete Sub item Modal -->
    <div class="modal fade" id="deleteitemConfirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded shadow-lg border-0">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title text-danger fw-bold">Confirm {{items_data.active === 'y' ? 'Deactivate' : 'Activate'}}</h5>
            <button type="button" class="btn-close" @click="closesubitemdeleteModal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to <strong>{{items_data.active === 'y' ? 'deactivate' : 'activate'}}</strong> the <b>Item</b> "<b>{{item_name}}</b>"?
          </div>
          <div class="modal-footer border-0 pt-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill" @click="closesubitemdeleteModal">Cancel</button>
            <button type="button" class="btn btn-danger rounded-pill" @click="handleDeleteSubitem" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{items_data.active === 'y' ? 'Deactivate' : 'Activate'}}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Template Modal-->
    <div class="modal fade" id="deletetempConfirmModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded shadow-lg border-0">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title text-danger fw-bold">Confirm {{temp_status  ? 'Deactivate' : 'Activate'}}</h5>
            <button type="button" class="btn-close" @click="closetempdeleteModal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to <strong>{{temp_status ? 'deactivate' : 'activate'}}</strong> the <b>Template</b> "<b>{{temp_name}}</b>"?
          </div>
          <div class="modal-footer border-0 pt-2">
            <button type="button" class="btn btn-outline-secondary rounded-pill" @click="closetempdeleteModal">Cancel</button>
            <button type="button" class="btn btn-danger rounded-pill" @click="handleDeleteTemplate" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{temp_status ? 'Deactivate' : 'Activate'}}
            </button>
          </div>
        </div>
      </div>
    </div> 

   `,
};




