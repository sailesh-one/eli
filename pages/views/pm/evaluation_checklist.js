export const EvaluationChecklist = {
    props: {
        store: { type: Object, required: true },
        isReadOnly: { type: Boolean, required: false, default: false },
    },
    data() {
        return {
            template_id: 0,
            lead_id: this.$route.params.slug2 || 0,
            items_selected: {},
            openSections: {},
            section_files:{},
            checklist:{},
            isProcessing:false,            
            validations:{},
            rfcost:{},
                           
        }
    },
    computed: {                
        templates(){                                                 
            return this.store?.getDetails?.evaluation?.templates || {}
        },
        mapped_templates(){
            return this.store?.getDetails?.evaluation?.mapped_templates || {}
        },
        sectionWise_refurbcost()
        {
            //console.log(this.templates)
            
            //this.template_id = Object.values(this.templates)?.[0]?.['id'];
            //console.log(this.checklist[this.template_id])
            /*this.checklist[this.template_id]['fields'].forEach((section) => {
                const sectionId = section.fieldKey;                
                section.fields.forEach((item) => {
                    const questionId = item.fieldKey;
                    console.log(sectionId,questionId)
                }); 
            });*/
            const totals = {}
            
            for (const [sectionKey, sectionValues] of Object.entries(this.rfcost)) {
                totals[sectionKey] = Object.values(sectionValues)
                .map(Number)
                .reduce((a, b) => a + b, 0)
            }
           return totals || 0
        },
        overall_refurbcost()
        {
            const sectionTotals = this.sectionWise_refurbcost
            return Object.values(sectionTotals).reduce((a, b) => a + b, 0)
        }               
    },
    watch: {
        mapped_templates:{
            handler(val) {              
                if (val && Array.isArray(val) && val.length) 
                {                  
                    const mt = parseInt(val[0]);                    
                    if (mt && this.template_id !== mt) {
                        this.template_id = mt;
                    }
                }else{
                    this.template_id = Object.values(this.templates)?.[0]?.['id'];
                }
            }
        },
        checklist:{
            handler(val ){
                this.template_id = Object.keys(this.checklist)?.[0];
                //console.log(this.checklist)
                //console.log(this.template_id)
                this.checklist[this.template_id]['fields'].forEach((section) => {
                    const sectionId = section.fieldKey;                
                    section.fields.forEach((item) => {
                        const questionId = item['fieldKey'];
                        if (!this.rfcost[sectionId]) {
                            this.rfcost[sectionId] = {};   
                        }
                        if (!this.rfcost[sectionId][questionId]) 
                        {
                            this.rfcost[sectionId][questionId] = [];
                        } 
                    
                        this.rfcost[sectionId][questionId] = item.imgPart.imgRefCost;
                    });
                });
            }
        } 

    },
    mounted() { 
        this.fetchChecklist();           
    },
    methods: {
        async fetchChecklist()
        {
            this.isProcessing =true;
            const formData = new FormData();
            formData.append("action", "getleadevaluation");                            
            formData.append("id", this.lead_id);
            const res = await $http('POST', `${g.$base_url_api}/`+this.store.endpoint, formData);
            if (res?.status === 200 && res?.body?.status === 'ok') {
                this.checklist = res.body.data.checklist;
                console.log("TemplateID", this.templates)
                this.isProcessing = false;
            }
        },     
        async saveChecklist() {
            this.isProcessing =true;
            this.items_selected = {};
            this.validations = {};
            if (!this.checklist[this.template_id]) return;
            this.checklist[this.template_id]['fields'].forEach((section) => {
                const sectionId = section.fieldKey;                
                section.fields.forEach((item) => { 
                    let img_data = item.imgPart.imgData;                               
                    let img_subdata = item.imgPart.imgSubData;                               
                    let img_remark = item.imgPart.imgRemark;                               
                    let img_refcost = item.imgPart.imgRefCost; 

                    if (img_data != '' || img_subdata != '' || img_remark != '' || img_refcost != '') 
                    {
                        if (!this.items_selected[sectionId]) 
                        {
                            this.items_selected[sectionId] = [];
                        }     
                        this.items_selected[sectionId].push(item.imgPart);
                    }

                   
                    /*item['fields'].forEach((index,val)=>{
                        if( val.isRequired && img_data == '' )
                        {
                            this.validations["section"] = sectionId;
                            let err = val['fieldKey'];                        
                            this.validations[err] = true;
                        }
                    })*/
                    
                    
                    if( item['fields'][0].isRequired && img_data == '')
                    {                                              
                        this.validations["section"] = sectionId;
                        let err = item['fields'][0]['fieldKey'];                        
                        this.validations[err] = "This option is requied.";                       
                    }                    
                    if( img_data != '' && img_subdata == '' && 'conditionalFields' in item['fields'][0] && item['fields'][0].conditionalFields[img_data][0].isRequired)
                    {                        
                        this.validations["section"] = sectionId;
                        let err = item['fields'][0]['fieldKey']+item['fields'][0].conditionalFields[img_data][0]['fieldKey'];                 
                        this.validations[err] = "This Suboption is requied.";  
                    }
                    if(item['fields'][0].isRequired && img_remark == '')
                    {
                       this.validations["section"] = sectionId;
                       let err = item['fields'][0]['fieldKey'] + item['fields'][2]['fieldKey'];                        
                       this.validations[err] = "Remarks is required.";  
                    }
                });                
            });
             
            if( Object.keys(this.validations).length >0){
                this.isProcessing = false; 
                return false;       
            }

            try{              
                if(Object.keys(this.items_selected).length <=0){
                    $toast('danger', 'The selection checklist are empty');
                    return;
                }
                const formData = new FormData();
                if(this.store.endpoint == 'my-stock')
                {
                  formData.append("action", "update");                
                  formData.append("sub_action", "addevaluation");                
                }
                else
                {
                  formData.append("action", "addevaluation");                   
                }
                formData.append("template_id", this.template_id);
                formData.append("id", this.lead_id);
                
                for (const sectionId in this.section_files) {
                    for (const questionId in this.section_files[sectionId]) 
                    {
                        const files = this.section_files[sectionId][questionId];
                        files.forEach((file, index) => {                      
                            formData.append(`file_${sectionId}_${questionId}`, file);
                        });
                    }
                }                
                formData.append("checklist", JSON.stringify( this.items_selected) );
                const res = await $http('POST', `${g.$base_url_api}/`+this.store.endpoint, formData);                
                if (res?.status === 200 && res?.body?.status === 'ok') {                   
                    this.store.getDetail(this.lead_id);
                    //this.fetchChecklist();
                    $toast('success', res.body.msg || 'Checklist updated successfully');
                }                
                this.isProcessing =false;
            }
            catch(err)
            {
                console.log(err)
                if (err?.status === 400 && err?.body?.status === 'fail') {
                    this.isProcessing =false;
                    $toast('danger', err.body.msg || 'Checklist updation is failed');
                }
                
            }
        },
        selectTemplate(id) {
            console.log(id)
            this.template_id = id;
            //this.openSections = {}; 
        },
        toggleSection(sectionId) {
            this.openSections[sectionId] = !this.openSections[sectionId];
        },
        setOption(field,gIndx,iIndx,value) { 
            if( field == 'imgData' ){
                this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart'][field] = value;
                console.log(this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart'][field]);
            }                            
        },
        setSubOption(field,gIndx,iIndx,value,checked)
        {            
            let arr = this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart'][field]? this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart'][field].split("|"): [];            
            if (checked && !arr.includes(value)) {
                arr.push(String(value));                
            } else if (!checked) {
                arr = arr.filter(v => v !== String(value));               
            }
            this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart'][field] = arr.join("|");           
        },   
        itemFileUpload(event,field,gIndx,iIndx)
        {
            let sectionId = this.checklist[this.template_id]['fields'][gIndx]['fieldKey'];
            let questionId = this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['fieldKey'];
            console.log(sectionId,questionId)
            if (!this.section_files[sectionId]) {
                this.section_files[sectionId] = {};   // create the section if missing
            }
            if (!this.section_files[sectionId][questionId]) 
            {
                this.section_files[sectionId][questionId] = [];
            } 
            this.section_files[sectionId][questionId].push(event.target.files[0]);
            console.log(this.section_files)
            //this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart'][field] = event.target.files[0];
        },
        openDoc(name, url) {
            globalThis.$docViewer.openDoc(name, url);
        },
        totalRFCost(event,gIndx,iIndx)
        { 
            let sectionId = this.checklist[this.template_id]['fields'][gIndx]['fieldKey'];
            let questionId = this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['fieldKey'];

            this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart']['imgRefCost'] = event.target.value.replace(/[^0-9]/g, '');

            if (!this.rfcost[sectionId]) {
                this.rfcost[sectionId] = {};   
            }
            if (!this.rfcost[sectionId][questionId]) 
            {
                this.rfcost[sectionId][questionId] = [];
            } 
            this.rfcost[sectionId][questionId] = this.checklist[this.template_id]['fields'][gIndx]['fields'][iIndx]['imgPart']['imgRefCost'];
        },
        NumberFormat(num) {
            return Number(num).toLocaleString("en-IN");
        }                     
    },
    template:/*html */`
    <div class="container-fluid mt-5 overflow-auto">
        <div v-if="isProcessing" class="p-3">
            <div class="card mb-3">
                <div class="card-header bg-light py-2">
                <span class="skeleton w-25 d-block">&nbsp;</span>
                </div>
                <div class="card-body py-2">
                <div class="row g-3">
                    <div v-for="n in 6" :key="'skeleton-field-' + n" class="col-md-4">
                    <div class="skeleton w-50 mb-1">&nbsp;</div>
                    <div class="skeleton w-75">&nbsp;</div>
                    </div>
                </div>
                </div>
            </div>           
        </div>
        <div v-else>
        <div class="mb-4">
            <label class="form-label fw-semibold mb-2">Evaluation Templates</label>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button
                    v-for="temp in templates"
                    :key="temp.id"
                    type="button"
                    class="btn"
                    :class="Number(template_id) === Number(temp.id) ? 'btn-dark border border-dark' : 'btn-outline-dark'"
                    style="min-width: 180px; font-weight: 500;"
                    @click="selectTemplate(temp.id)"
                >
                    {{ temp.template_name }}
                </button>
                <h2 class="mb-0">Total Refurb Cost: ₹{{NumberFormat(overall_refurbcost)}}</h2>
            </div>
        </div>
        <div class="col-md-10" v-if="template_id in checklist">
            <div class="accordion mb-3 border-0 shadow-sm rounded" v-for="(section,cindex) in checklist[template_id]['fields']" :key="section.fieldKey">
                <div class="accordion-item">
                    <h2 class="accordion-header" :id="'heading' + section.fieldKey" :class="{ 'border border-danger': validations?.['section'] == section.fieldKey }">
                        <button
                            class="accordion-button"
                            :class="{'collapsed': !openSections[section.fieldKey]}"
                            type="button"
                            @click="toggleSection(section.fieldKey)"
                            :style="openSections[section.fieldKey] ? 'background:#e0e0e0;color:#222;' : 'background:none;color:inherit;'"
                        >
                            {{section.fieldLabel}} (Cost: ₹<span v-if="sectionWise_refurbcost?.[section.fieldKey]">{{NumberFormat(sectionWise_refurbcost[section.fieldKey])}}</span> <span v-else>0</span> )  
                        </button>
                    </h2>
                    <div
                        v-show="openSections[section.fieldKey]"
                        class="accordion-collapse"
                        :id="'collapse'+ section.fieldKey"
                        :aria-labelledby="'heading' + section.fieldKey"
                    >
                        <div class="accordion-body bg-white p-0">
                            <div class="container-fluid">
                            <div class="row popup-head align-items-center">                        
                                <div class="col-sm-4"><p class="fw-semibold mb-0 py-2">Check List</p></div>
                                <div class="col-sm-2"><p class="fw-semibold mb-0 py-2">Options</p></div>
                                <div class="col-sm-2" ><p class="fw-semibold mb-0 py-2">Sub Options</p></div>
                                <div class="col-sm-1"><p class="fw-semibold mb-0 py-2">Refurb Cost</p></div>
                                <div class="col-sm-1"><p class="fw-semibold mb-0 py-2">Remarks</p></div>
                                <div class="col-sm-2"><p class="fw-semibold mb-0 py-2">Document</p></div>
                            </div>
                        </div>
                         <div class="container-fluid">
                            <div class="row py-3" v-if="section?.fields" v-for="(item,it_index) in section.fields" :key="item.fieldKey" :class="[it_index % 2 === 0 ? 'bg-light' : 'bg-white']">                                   
                                <div class="col-sm-4"><p class="text-md-start mb-0">{{item.fieldLabel}} <span v-if="item['fields'][0].isRequired" class="text-danger">*</span></p></div>                        
                                <div class="col-sm-2">
                                    <div class="form-check form-check-inline" v-for="(opt,index) in item['fields'][0].fieldOptionIds" :key="item.fieldKey + index">
                                        <span v-if="item.inputType == 'checkbox'">
                                            <!--<input class="form-check-input" :id="opt+it_index" :type="item.inputType" v-model="checklist[template_id]['fields'][cindex]['fields'][it_index]['checkoptions']"  :value="opt.label"><label class="form-check-label"> {{opt.fieldLabel}}</label>-->
                                        </span>                                       
                                        <span v-else class="d-flex">                                   
                                           
                                                <input class="form-check-input"
                                                :id="opt.label+index+it_index"
                                                type="radio"
                                                :name="'rg-'+template_id+'-'+section.fieldKey+'-'+item.fieldKey"
                                                v-model="item['imgPart']['imgData']"
                                                :value="opt.label" v-on:change="setOption('imgData',cindex,it_index,opt.label)">
                                             <label class="form-check-label">{{opt.label}}</label>
                                        </span>                                    
                                    </div>
                                    <div class="invalid-feedback d-block" style="font-size: 0.7rem;" v-if="validations[item['fields'][0]['fieldKey']]" >This option is required</div>
                                </div>
                                <div class="col-sm-2">                       
                                    <div v-if="'conditionalFields' in item['fields'][0]" class="ms-0">
                                        <div class="form-check form-check-sm" v-for="(sub, sidx) in item['fields'][0].conditionalFields[item['imgPart']['imgData']]" :key="sidx">
                                            <span v-for="cfield in sub.fieldOptionIds" class="d-flex  align-items-center">
                                            <label class="form-check-label mt-0" ><input class="form-check-input" :type="sub.inputType" :value="cfield.value" :checked="item['imgPart']['imgSubData'].split('|').includes(String(cfield.value))" v-on:change="setSubOption('imgSubData',cindex,it_index,cfield.value,$event.target.checked)">
                                            {{ cfield.label }}</label>
                                            </span>
                                            <div class="invalid-feedback d-block" style="font-size: 0.7rem;" v-if="validations[item['fields'][0]['fieldKey']+sub.fieldKey]" >The suboption is required</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-1">
                                    <input class="form-control form-control-sm p-1"  
                                        type="text" 
                                        v-model="item['imgPart']['imgRefCost']" 
                                        maxlength="9" pattern="^[0-9]+$"
                                        @input="totalRFCost($event,cindex,it_index)"
                                       
                                     />
                                </div>
                                <div class="col-sm-1">
                                    <input class="form-control form-control-sm p-1"  type="text" v-model="item['imgPart']['imgRemark']" maxlength="250" pattern="^[a-zA-Z0-9.,@\\-\/ ]+$">
                                    <div class="invalid-feedback d-block" style="font-size: 0.7rem;" v-if="validations[item['fields'][0]['fieldKey']+item['fields'][2]['fieldKey']]" >Remarks is required</div>
                                </div>
                                <div class="col-sm-2">
                                    <input type="file" class="form-control form-control-sm p-1" v-on:change="itemFileUpload($event,'imgFile',cindex,it_index)" accept="image/*,.pdf"  />
                                    <p v-if="item['imgPart']['imgPath'] != ''"><a class="link-opacity-75"  v-on:click="openDoc('image',item['imgPart']['imgPath'])">View Doc</a></p>
                                </div>
                            </div>
                          </div>    
                        </div>    
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">            
                <button v-if="!isReadOnly" type="button" class="btn" style="background:#111; color:#fff; min-width:120px;" v-on:click="saveChecklist" :disabled="isProcessing" >
                    <span v-if="isProcessing">Saving...</span>
                    <span v-else>Update</span>
                </button>
            </div>
        </div>
        </div>
    </div>
    `
}
// Alias for stock module dynamic load key 'Refurb'
export const Refurb = EvaluationChecklist;
export default EvaluationChecklist;