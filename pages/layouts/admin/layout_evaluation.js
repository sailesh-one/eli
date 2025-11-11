export default {
    name:'Evaluation',
    data(){
        return{
            section_id:0,
            item_id:1,
            template_id:1,
            templates:[],
            template:{
                template_name:'',
                template_description:'',
                status:''
            },
            template_validation:[],
            checklist:{},
            groups:[],
            delete_groups:[],
            delete_items:[],
            schema:[
                {
                    fieldKey:"new",
                    fieldLabel:"Demo Section",
                    sort_order:1,                    
                    fields:[
                        {                            
                            fieldKey:"new",
                            fieldLabel:"Demo Item",
                            remarks:"",
                            isRequired:"no",
                            refurb_cost:"",
                            editing:false,
                            fieldOptionIds:[
                                {
                                    fieldLabel:"Yes",
                                    inputType:'checkbox',
                                    fieldSubOptions:[
                                        {
                                            "fieldLabel":"Sub-Option 1",
                                            "isSelected":false
                                        }
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ],
        }
    },
    created(){     
        //this.addGroup();
    },
    mounted(){
        this.getchecklist();
    },
    methods:{
        async getchecklist()
        { 
            const data = {
                action : 'getchecklistItems',     
            };    
            try
            {
                const response = await $http('POST',`${g.$base_url_api}/admin/evaluation-checklist`,{...data},{});
                if(response.body.status === "ok")
                {
                    this.templates = response.body.data['templates'];
                    this.checklist = response.body.data['checklist'];
                    this.groups = this.checklist[this.template_id]['fields'];
                }
            }
            catch(error)
            {
                this.groups = [];
            }
        },
        newItemFromTemplate(template){
            return {
                fieldKey: template.fieldKey,
                fieldLabel: template.fieldLabel,
                remarks: template.remarks || "",
                refurb_cost: template.refurb_cost || "",
                isRequired: template.isRequired || "no",
                inputType:"radio",
                isUpdate:false,
                editing:false,
                fieldOptionIds: (template.fieldOptionIds || []).map(opt => ({
                    fieldLabel: opt.fieldLabel,
                    inputType: opt.inputType || 'checkbox',
                    editing:false,
                    fieldSubOptions: (opt.fieldSubOptions || []).map(s => ({...s, isSelected:false, editing:false}))
                }))
            };
        },
        addGroup(){
            //this.section_id = Math.max(...this.groups.map(s=>Number(s.section_id) ));           
            //this.section_id++;            
            const base = this.schema[0];
            const name = base.fieldLabel;
            this.groups.push({
                fieldKey: base.fieldKey,
                fieldLabel: name,
                sort_order: base.sort_order,
                editing:false,
                isUpdate:false,
                fields: base.fields.map(it => this.newItemFromTemplate(it))
            });
        },
        removeGroup(idx){
            this.delete_groups.push(this.groups[idx]['fieldKey']);
            this.groups.splice(idx,1);
        },
        toggleSubOption(groupIdx,itemIdx,optIdx,subIdx){
            const option = this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx];
            const sub = option.fieldSubOptions[subIdx];
            if(option.type === 'radio'){
                option.fieldSubOptions.forEach((s,i)=>{ s.isSelected = (i===subIdx); });
            }else{
                sub.selected = !sub.selected;
            }
        },
        clearOptionGroup(groupIdx,itemIdx,optIdx){
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].fieldSubOptions.forEach(s=> s.selected=false);
        },
        enableGroupEdit(gIndex){
            this.groups[gIndex].editing = true;
        },
        disableGroupEdit(gIndex){
            this.groups[gIndex].isUpdate = true;
            this.groups[gIndex].editing = false;
        },
        enableOptionEdit(groupIdx,itemIdx,optIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].editing = true;
        },
        disableOptionEdit(groupIdx,itemIdx,optIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].editing = false;
        },
        enableSubEdit(groupIdx,itemIdx,optIdx,subIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].fieldSubOptions[subIdx].editing = true;
        },
        disableSubEdit(groupIdx,itemIdx,optIdx,subIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].fieldSubOptions[subIdx].editing = false;
        },
        addOption(groupIdx,itemIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds.push({
                fieldLabel: 'New Option',
                inputType: 'checkbox',
                editing: false,
                fieldSubOptions: []
            });
        },
        removeOption(groupIdx,itemIdx,optIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds.splice(optIdx,1);
        },
        addSubOption(groupIdx,itemIdx,optIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            const id = 'n'+Date.now();
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].fieldSubOptions.push({  fieldLabel:'New Sub-option', isSelected:false, editing:false });
        },
        removeSubOption(groupIdx,itemIdx,optIdx,subIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx].fieldSubOptions.splice(subIdx,1);
        },
        addItem(groupIdx){
            this.item_id++;
            const template = this.schema[0].fields[0]; // Use first template item as base
            this.groups[groupIdx].fields.push({
                fieldKey: template.fieldKey,
                fieldLabel: template.fieldLabel,
                remarks: template.remarks || "",
                refurb_cost: "",
                editing: false,
                fieldOptionIds: (template.fieldOptionIds || []).map(opt => ({
                    fieldLabel: opt.fieldLabel,
                    inputType: opt.inputType || 'checkbox',
                    editing: false,
                    fieldSubOptions: (opt.fieldSubOptions || []).map(s => ({...s, isSelected: false, editing: false}))
                }))
            });
        },
        removeItem(groupIdx, itemIdx){
            if(this.groups[groupIdx]['fields'][itemIdx]['fieldKey'] != "new")
            {
                this.delete_items.push(this.groups[groupIdx]['fields'][itemIdx]['fieldKey']);
            }
            this.groups[groupIdx].fields.splice(itemIdx, 1);
        },
        enableItemEdit(groupIdx, itemIdx){
            console.log(groupIdx, itemIdx);
            this.groups[groupIdx].fields[itemIdx].editing = true;
        },
        disableItemEdit(groupIdx, itemIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
            this.groups[groupIdx].fields[itemIdx].editing = false;
        },
        changeOptionType(groupIdx,itemIdx,optIdx){
            const option = this.groups[groupIdx].fields[itemIdx].fieldOptionIds[optIdx];
            if(option.type === 'radio'){
                // keep only first selected
                let found = false;
                option.fieldSubOptions.forEach(s=>{
                    if(s.isSelected && !found){ found = true; }
                    else { s.isSelected = false; }
                });
            }
        },
        
        async submit(){
            const payload = this.groups;
            try{
                console.log('Evaluation submit payload', payload);
                const data = {
                    action: 'savechecklist',
                    template_id: this.template_id,
                    data: payload,
                    delete_groups:this.delete_groups,
                    delete_items:this.delete_items,
                }                                    ;
                const response = await $http('POST',`${g.$base_url_api}/admin/evaluation-checklist`,{...data},{});
                if( response.status == 200 ){
                    let body = response.body;
                    if( body.status == "ok" ){
                        $toast('success', body.msg || 'Evaluation checklist is updated successfully');  
                        this.getchecklist();                
                    }
                }
            }catch(e){
                
            }
        },
        setTemplate(){           
            if( this.checklist[this.template_id] ){
                this.groups = this.checklist[this.template_id]['fields'];
            }else{
                this.groups = [];
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
        closedeleteModal()
        {
            //$("#deleteConfirmModal").modal("hide");
            $("#templateModal").modal("hide");
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
                        this.getchecklist();                
                    }                    
                }
            }
            catch(e){                          
                this.template_validation = error.body.errors;               
                $toast('danger', e.msg || 'Template details not updated'); 
            } 
        },
        requiredUpdate(groupIdx, itemIdx){
            this.groups[groupIdx].fields[itemIdx].isUpdate = true;
        }
    },
    template:/*html*/`
    <div class="container-fluid py-3 bg-light min-vh-100">
        <div class="row justify-content-center">
            <div class="col-lg-12 col-xl-12">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 rounded-2 ">
                            <div class="card-body">
                                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">  
                                    <h5 class="text-dark modal-title">Templates</h5>                      
                                    <button class="btn btn-dark btn-sm rounded-1 shadow-sm" title="Add Template" v-on:click="templateModal(0)" ><i class="bi bi-plus-lg me-1"></i> Add Template </button>
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
                    <div class="card shadow-sm border-0 rounded-2 ">
                    <div class="card-body evaluation"> 
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h4 text-uppercase fw-bold text-muted mb-0">Evaluation CheckList</h2>
                            <select class="form-select form-select-sm w-25" v-model="template_id" @change="setTemplate()">
                                <option>Select Template</option>
                                <template v-for="(temp,index) in templates">
                                <option :value="temp.id" :key="temp.id" v-if="temp.status" >{{temp.template_name}}</option> 
                                </template>                           
                            </select>
                            <div class="d-flex">
                                <button class="btn btn-sm btn-outline-dark me-2 py-1 d-flex align-items-center" @click="addGroup()"><i class="bi bi-plus fs-6"></i> Add Checklist</button>
                                <button class="btn btn-sm btn-dark py-1 d-flex align-items-center" @click="submit()"><i class="bi bi-check2-circle fs-6"></i> Submit</button>
                            </div>
                        </div>

                        <div v-if="groups.length===0" class="alert alert-info">No checklist added yet.</div>
                        <div class="accordion" id="eligibilityAccordion">
                        <div class="accordion-item mb-3 shadow-sm rounded " v-for="(group,gIndex) in groups" :key="group.filedKey">
                            <h2 class="accordion-header" :id="'heading-'+group.fieldKey">
                                <button class="accordion-button modal-title popup-head px-3 py-3 collapsed" type="button" data-bs-toggle="collapse" :data-bs-target="'#collapse-'+group.fieldKey" aria-expanded="false" :aria-controls="'collapse-'+group.fieldKey">
                                    {{ group.fieldLabel }}
                                </button>
                            </h2>
                            <div :id="'collapse-'+group.fieldKey" class="accordion-collapse collapse" :aria-labelledby="'heading-'+group.fieldKey" data-bs-parent="#eligibilityAccordion">
                                <div class="accordion-body bg-white">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong v-if="!group.editing">{{ group.fieldLabel }}</strong>
                                            <input v-else class="form-control form-control-sm py-2" style="max-width:420px" v-model="group.fieldLabel" @keyup.enter="disableGroupEdit(gIndex)" @blur="disableGroupEdit(gIndex)" />
                                            <button class="btn btn-sm btn-outline-none px-1 py-0" v-if="!group.editing" @click="enableGroupEdit(gIndex)"><i class="bi bi-pencil fs-6"></i></button>
                                            <button class="btn btn-sm btn-outline-success" v-else @click="disableGroupEdit(gIndex)"><i class="bi bi-check2"></i></button>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" @click="removeGroup(gIndex)"><i class="bi bi-trash"></i> Remove</button>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Items</h5>
                                        <button class="btn btn-sm btn-outline-primary" @click="addItem(gIndex)"><i class="bi bi-plus"></i> Add Item</button>
                                    </div>
                                
                                    <div v-for="(item,iIndex) in group.fields" :key="iIndex" class="mb-4 border rounded p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                <h6 v-if="!item.editing" class="mb-0">{{ item.fieldLabel }}</h6>
                                                <input v-else class="form-control form-control-sm" style="max-width: 300px;" v-model="item.fieldLabel" @keyup.enter="disableItemEdit(gIndex,iIndex)" @blur="disableItemEdit(gIndex,iIndex)" />
                                                <button class="btn btn-sm btn-outline-primary" v-if="!item.editing" @click="enableItemEdit(gIndex,iIndex)"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-success" v-else @click="disableItemEdit(gIndex,iIndex)"><i class="bi bi-check2"></i></button>
                                            </div>
                                            <div class="d-flex gap-2 align-items-center">
                                                <button class="btn btn-sm btn-outline-danger" @click="removeItem(gIndex,iIndex)"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12 mb-2">
                                                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-between align-items-center mb-3">                                                
                                                    Input Type: 
                                                    <select class="form-select form-select-sm" style="width:auto" v-model="item.inputType">                                                            
                                                            <option value="checkbox">checkbox</option>
                                                            <option value="radio">radio</option>
                                                    </select>
                                                    Item Required: 
                                                    <select class="form-select form-select-sm" style="width:auto" v-model="item.isRequired" v-on:change="requiredUpdate(gIndex,iIndex)">
                                                            <option value="yes">Yes</option>
                                                            <option value="no">No</option>
                                                    </select>
                                                    <button class="btn btn-sm btn-outline-primary" @click="addOption(gIndex,iIndex)"><i class="bi bi-plus"></i> Add Option</button>
                                                </div>
                                            </div>
                                            <div class="col-md-6" v-for="(opt,oIndex) in item.fieldOptionIds" :key="oIndex">
                                                <div class="border rounded p-2 h-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                            <span v-if="!opt.editing" class="fw-semibold">{{ opt.fieldLabel }}</span>
                                                            <input v-else class="form-control form-control-sm" style="max-width:240px" v-model="opt.fieldLabel" @keyup.enter="disableOptionEdit(gIndex,iIndex,oIndex)" @blur="disableOptionEdit(gIndex,iIndex,oIndex)" />
                                                            <button class="btn btn-sm btn-outline-primary" v-if="!opt.editing" @click="enableOptionEdit(gIndex,iIndex,oIndex)"><i class="bi bi-pencil"></i></button>
                                                            <button class="btn btn-sm btn-outline-success" v-else @click="disableOptionEdit(gIndex,iIndex,oIndex)"><i class="bi bi-check2"></i></button>
                                                            Sub Option Type: <select class="form-select form-select-sm" style="width:auto" v-model="opt.inputType" @change="changeOptionType(gIndex,iIndex,oIndex)">
                                                                <option value="checkbox">checkbox</option>
                                                                <option value="radio">radio</option>
                                                            </select>
                                                        </div>
                                                        <div class="d-flex gap-1">
                                                            
                                                            <button class="btn btn-sm btn-outline-danger" @click="removeOption(gIndex,iIndex,oIndex)"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column gap-1">
                                                        <div class="d-flex align-items-center justify-content-between" v-for="(sub,sIndex) in opt.fieldSubOptions" :key="sub.fieldKey">
                                                            <label class="form-check d-flex align-items-center gap-2 mb-0 flex-grow-1">
                                                                <input class="form-check-input" :type="opt.inputType==='radio' ? 'radio' : 'checkbox'" :name="group.fieldKey+'-'+iIndex+'-'+oIndex" >
                                                                <span v-if="!sub.editing" class="form-check-label">{{ sub.fieldLabel }}</span>
                                                                <input v-else class="form-control form-control-sm" v-model="sub.fieldLabel" @keyup.enter="disableSubEdit(gIndex,iIndex,oIndex,sIndex)" @blur="disableSubEdit(gIndex,iIndex,oIndex,sIndex)" />
                                                            </label>
                                                            <div class="d-flex gap-1 ms-2">
                                                                <button class="btn btn-sm btn-outline-primary" v-if="!sub.editing" @click="enableSubEdit(gIndex,iIndex,oIndex,sIndex)"><i class="bi bi-pencil"></i></button>
                                                                <button class="btn btn-sm btn-outline-success" v-else @click="disableSubEdit(gIndex,iIndex,oIndex,sIndex)"><i class="bi bi-check2"></i></button>
                                                                <button class="btn btn-sm btn-outline-danger" @click="removeSubOption(gIndex,iIndex,oIndex,sIndex)"><i class="bi bi-trash"></i></button>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <button class="btn btn-sm btn-outline-primary" @click="addSubOption(gIndex,iIndex,oIndex)"><i class="bi bi-plus"></i> Add Sub-option</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TemplateModal-->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded shadow-lg">
                <div class="modal-header popup-head">
                    <h5 class="modal-title text-dark" id="templateModalLabel">Template</h5>
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
					<button type="button" class="btn btn-outline-secondary rounded-1" v-on:click="closedeleteModal()">Cancel</button>
					<button type="button" class="btn btn-dark rounded-1" v-on:click="saveTemplate()" >Save</button>
				</div>
			</div>
        </div>
	</div>
    `
}


