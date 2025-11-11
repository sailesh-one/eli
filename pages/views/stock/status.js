export const Status = {
    name: 'status',
    props: { store: { type: Object, required: true } },

    data() {
        return {
            lead_id: null,
            allowedStatuses: [],
            originalStatus: null,
            errors: {
                sub_action: '',
                id: '',
                status: '',
                sub_status: '',
                followup_datetime: '',
                price_customer: '',
                price_quote: '',
                price_expenses: '',
                price_selling: '',
                price_indicative : '',
                evaluation_type : '',
                evaluation_place : '',
                remarks: '',
                lead_classification: '',
                relationship_proof_doc: '',
                price_agreement_doc: '',
                is_exchange: '',
            },
            // Allowed file types (only PDF and Images)
            allowedFileTypes: ["application/pdf","image/jpeg", "image/jpg", "image/png"],
            acceptedFileTypes: ".pdf,.jpg,.jpeg,.png",
        };
    },

    computed: {
        // from store
        displayData: vm => vm.store.getDetails || {},
        statusConfig: vm => vm.store.getStatusConfig || {},
        statusOptions() {
            const currentStatus = this.originalStatus || this.displayData.status;
            const currentConfig = this.statusConfig[currentStatus] || {};
            const allowed = new Set([String(currentStatus), ...(currentConfig.eligible_statuses || []).map(String)]);

            return Object.entries(this.statusConfig).map(([key, value]) => ({
                value: key,
                label: value.label,
                disabled: !allowed.has(String(key))
            }));
        },
    },

    watch: {
        displayData: {
            immediate: true,
            handler(newVal) {
                $log("displayData changed:", newVal);
                if (!newVal || !this.statusConfig[newVal.status]) return;

                this.originalStatus = newVal.status;

                const currentStatusConfig = this.statusConfig[newVal.status];

                currentStatusConfig.fields.forEach(field => {
                    if (newVal[field.name] !== undefined) {
                        field.value = newVal[field.name];
                        $log(`Updated field "${field.name}" to value:`, newVal[field.name]);
                        if (field.name === "status") {
                            this.handleStatusChange(field.value);
                        }
                    }
                });

                $log("statusConfig updated with displayData:", this.statusConfig);
            }
        }
    },

    methods: {
        async saveStatusForm() {
            this.lead_id = this.displayData.id;
            const currentStatus = this.displayData.status;
            let isValid = true;

            const formData = new FormData();

            this.statusConfig[currentStatus].fields.forEach(field => {
                let val = field.value;
                const rules = field.validation || {};
                let error = "";

                // File validation
                if (field.type === "file") {
                    if (rules.required && !(val instanceof File)) {
                        error = rules.msg || `${field.label} is required`;
                    }
                } else {
                    if (rules.required && !field.value) {
                        error = rules.msg || `${field.label} is required`;
                    }
                }

                // Handle datetime-local → split into date + time
                if (!error && field.name === "followup_datetime" && field.value) {
                    const dt = new Date(field.value);
                    if (!isNaN(dt)) {
                        formData.append("followup_date", dt.toISOString().slice(0, 10)); // YYYY-MM-DD
                        formData.append("followup_time", dt.toTimeString().split(" ")[0]); // HH:mm:ss
                    }
                }

                if (error) {
                    this.errors[field.name] = error;
                    isValid = false;
                } else {
                    delete this.errors[field.name];
                }

                // Append only if not datetime-local (already handled)
                if (field.type === "file" && val instanceof File) {
                    formData.append(field.name, val);
                } else if (field.name !== "followup_datetime") {
                    formData.append(field.name, val ?? "");
                }
            });

            formData.append("status", currentStatus);
            formData.append("id", this.lead_id);
            formData.append("action", "update");
            formData.append("sub_action", "updatestatus");

            if (!isValid) {
                $log("Validation failed. Errors:", this.errors);
                return;
            }

            $log("Payload (FormData) to save:", formData);
            await this.updateLeadStatus(formData);
        },

        async updateLeadStatus(formData) {
            try {
                const res = await $http('POST', `${g.$base_url_api}/purchase-master`, formData, {
                    headers: { "Content-Type": "multipart/form-data" }
                });

                if (res?.body?.status == "ok") {
                    $toast('success', res.body.msg || 'Status updated successfully');
                    this.originalStatus = this.displayData.status;
                    // this.store.displayData = { ...this.store.displayData, ...Object.fromEntries(formData) };
                    this.errors = {};
                }
            } catch (e) {
                $toast('danger', e.body.msg || 'Failed to update status');
                this.errors = e.body.errors || {};
            }
        },

        shouldShowField(field) {
            return field.validation?.visible !== false;
        },

        handleStatusChange(status) {
            $log("status changed:", status);
            if (!status || !this.statusConfig[status]) return;

            const allowedValues = this.statusConfig[status].eligible_statuses || [];
            this.allowedStatuses = allowedValues.map(Number);
            $log("allowedStatuses:", this.allowedStatuses);
        },

        isAllowed(value) {
            if (value === this.displayData.status) return true;
            return this.allowedStatuses.includes(Number(value));
        },

        handleFileUpload(event, field) {
            const file = event.target.files[0];
            if (!file) return;

            if (!this.allowedFileTypes.includes(file.type)) {
                $toast('danger', "Invalid file type. Only PDF and images are allowed.");
                event.target.value = "";
                field.value = null;
                return;
            }

            field.value = file;
            this.errors[field.name] = '';
        },
    },

    template: /*html*/`
        <div class="container my-4">
            <div class="p-3 mb-4 bg-secondary text-white rounded">
                <div class="d-flex justify-content-between flex-wrap gap-4 ">
                    <div><strong>Id:</strong> {{ displayData.formatted_id }}</div>
                    <div><strong>Current Status:</strong> {{ displayData.status_name }}</div>
                    <div><strong>Followup Date:</strong> {{ displayData.followup_date }}</div>
                </div>
            </div>

            <form class="g-3 needs-validation" @submit.prevent="saveStatusForm">
                <div class="row">
                    <!-- Current Status dropdown -->
                    <div class="col-6 mb-2">
                        <label class="col-form-label"><strong>Current Status</strong></label><br>
                        <select 
                            class="form-select form-select-sm rounded-pill shadow-none py-1 px-2" 
                            v-model="displayData.status"
                            @change="handleStatusChange(displayData.status)"
                        >
                            <option 
                                v-for="option in statusOptions" 
                                :key="option.value" 
                                :value="option.value" 
                                :disabled="option.disabled"
                                :style="{ fontWeight: option.disabled ? 'normal' : 'bold' }"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Dynamic fields -->
                    <div class="col-6 mb-2" v-for="(field, index) in statusConfig[displayData.status]?.fields" :key="index">
                        <label class="col-form-label" v-show="shouldShowField(field)">
                          <strong>{{ field.label }}</strong>
                        </label><br>

                        <!-- DateTime picker -->
                        <input v-if="field.type === 'datetime-local'" type="datetime-local"
                            class="form-control form-control-sm rounded-pill shadow-none py-1 px-2"
                            v-model="field.value"
                            :required="field.validation?.required"
                            v-show="shouldShowField(field)"/>

                        <!-- Generic select fields -->
                        <select v-else-if="field.type === 'select'" 
                            class="form-select form-select-sm rounded-pill shadow-none py-1 px-2"
                            v-model="field.value" 
                            :required="field.validation?.required"
                            v-show="shouldShowField(field)">
                            <option value="">Select</option>
                            <option v-for="(opt, oi) in field.options" 
                                    :key="oi" 
                                    :value="opt.value || opt"
                                    :disabled="field.name === 'status' && !isAllowed(opt.value || opt)">
                                {{ opt.label || opt }}
                            </option>
                        </select>

                        <!-- Radio -->
                        <div v-else-if="field.type === 'radio'" class="d-flex gap-3">
                            <div v-for="(opt, oi) in field.options" :key="oi" class="form-check">
                                <input 
                                    class="form-check-input"
                                    type="radio"
                                    :id="field.name + '_' + oi"
                                    :name="field.name"
                                    :value="opt.value || opt"
                                    v-model="field.value"
                                    :required="field.validation?.required"
                                />
                                <label class="form-check-label" :for="field.name + '_' + oi">
                                    {{ opt.label }}
                                </label>
                            </div>
                        </div>

                        <!-- File -->
                        <input v-else-if="field.type === 'file'"
                            type="file"
                            :accept="acceptedFileTypes"
                            class="form-control form-control-sm rounded-pill shadow-none py-1 px-2"
                            :id="field.name"
                            :name="field.name"
                            @change="handleFileUpload($event, field)"
                        />

                        <!-- Money -->
                        <div class="input-group" v-else-if="field.name.includes('price')" v-show="shouldShowField(field)">
                            <span class="input-group-text form-control-sm rounded-pill shadow-none py-1 px-2"><i class="bi bi-currency-rupee"></i></span>
                            <input :type="field.type" 
                                class="form-control form-control-sm rounded-pill shadow-none py-1 px-2"
                                v-model="field.value" 
                                :placeholder="field.placeholder || ''"
                                :required="field.validation?.required"/>
                        </div>

                        <!-- Fallback -->
                        <input v-else :type="field.type" 
                            class="form-control form-control-sm rounded-pill shadow-none py-1 px-2"
                            v-model="field.value" 
                            :placeholder="field.placeholder || ''"  
                            :required="field.validation?.required"
                            v-show="shouldShowField(field)"/>

                        <!-- Validation error -->
                        <span v-if="errors[field.name]" class="text-danger">{{ errors[field.name] }}</span>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-center">
                    <button class="btn btn-success btn-sm px-4 rounded-pill" type="submit">
                        Save
                    </button>
                </div>
            </form>
        </div>
    `
};
