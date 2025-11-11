export const statusUpdates = {
    name: 'statusUpdates',
    data() {
        return {
            store: null,
            // initialData: {},
            statusForm: [
                {
                    label: "Status",
                    value: "status",
                    type: "select",
                    options: [],
                    required: true,
                },
                {
                    label: "Lead Classification",
                    value: "lead_classification",
                    type: "select",
                    options: ["Hot Plus", "Hot", "Warm", "Cool"],
                    required: true,
                },
                {
                    label: "Followup Date",
                    value: "followup_date",
                    type: "date",
                    required: true,
                },
                {
                    label: "Time",
                    value: "followup_time",
                    type: "select",
                    required: true,
                },
                {
                    label: "Remarks",
                    value: "remarks",
                    type: "text",
                    required: true,
                },
                {
                    label: "Executive Quote",
                    value: "executive_quote",
                    type: "text",
                    is_money: true,
                    required: true,
                },
                {
                    label: "Customer Expectations",
                    value: "customer_expectations",
                    type: "text",
                    is_money: true,
                    required: true,
                },
                {
                    label: "Other Expenses",
                    value: "oth_exp",
                    type: "text",
                    is_money: true,
                    required: true,
                },
                {
                    label: "Expected Selling Price",
                    value: "exp_sel_price",
                    type: "text",
                    is_money: true,
                },
            ],

            formData: {
                status: "",
                lead_classification: "",
                followup_date: "",
                followup_time: "",
            }
        };
    },

    async created() {
		const routePath = $routeGetMeta('path'); // e.g., 'purchase-master'
		try {
		let [storeModule] = await $importComponent([`/pages/stores/store_${routePath}.js`]);

		if (storeModule.default) storeModule = storeModule.default;

		const toPascalCase = str =>
			str.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('');

		const storeFunctionName = `useStore${toPascalCase(routePath)}`;

        if (typeof storeModule[storeFunctionName] === 'function') {
            this.store = storeModule[storeFunctionName]();
            this.store.init(); // Initialize store after creating it
            $log("in created", this.store);
        } else {
            console.error(`Store function "${storeFunctionName}" not found in module`, storeModule);
        }
        } catch (err) {
        console.error('Error loading store:', err);
        }
    },

    mounted() {

    },

    methods: {
        openOffcanvas(){
            const offcanvasEl = document.getElementById('offcanvasRight');

            // 2. Create an instance of Bootstrap Offcanvas
            const offcanvas = new bootstrap.Offcanvas(offcanvasEl, {
                backdrop: true,
                keyboard: false
            });

            offcanvas.show();
        },
        async saveStatusForm() {
           const form = document.querySelector('.needs-validation');
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                return;
            }
            console.log("Form Data:", this.formData);
            alert("Form saved successfully!");
            await this.store.updateLeadStatus(this.formData);
        }, 
    },
    computed: {
        timeOptions() {
            const times = [];
            const start = 0;   // 0 = 12 AM
            const end = 24;    // 23 = 11 PM
            const step = 15;   // 15-minute intervals

            for (let hour = start; hour < end; hour++) {
                for (let minute = 0; minute < 60; minute += step) {
                const h = hour % 12 || 12;  // convert 0 -> 12
                const m = minute.toString().padStart(2, "0"); // "0" -> "00"
                const ampm = hour < 12 ? "AM" : "PM";
                times.push(`${h}:${m} ${ampm}`);
                }
            }

            return times;
        },
        // full config from Pinia
        config() {
            $log("config in status_updates", this.store?.detailConfig);
            return this.store?.detailConfig || {};
        },
        displayData() {
            $log("displayData in status_updates", this.config);
            return this.store?.detailConfig?.data || {};
        },
    },
    template: /*html*/`

        <div class="offcanvas offcanvas-end offcanvas-fullscreen" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasRightLabel">Lead Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body" id = "offcanvasBody">
                <div class="accordion accordion-flush" id="accordionFlushExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                            Current Status
                        </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                
                                <form class="row g-3 needs-validation" novalidate @submit.prevent="saveStatusForm">
                                    <div class="col-12 mb-2" v-for="(field, index) in statusForm" :key="index">
                                        <label class="col-form-label">{{ field.label }}</label><br>

                                        <!-- Time dropdown -->
                                        <select v-if="field.type === 'select' && field.value === 'followup_time'" class="form-select" v-model="formData[field.value]" :required="field.required">
                                            <option value="">Select Time</option>
                                            <option v-for="(t, ti) in timeOptions" :key="ti" :value="t">
                                                {{ t }}
                                            </option>
                                        </select>

                                        <select v-else-if="field.type === 'select'" class="form-select" v-model="formData[field.value]" :required="field.required">
                                            <option v-if="field.label === 'Status' && displayData?.status_name" :value='displayData.status_name'>{{ displayData.status_name }}</option>
                                            <option v-else value="">Select</option>
                                            <option v-for="(opt, oi) in field.options" :key="oi" :value="opt">
                                                {{ opt }}
                                            </option>
                                        </select>

                                        <!-- Date picker -->
                                        <input v-else-if="field.type === 'date'" type="date" class="form-control" v-model="formData[field.value]" :required="field.required"/>

                                        <!-- Default fallback for other types -->
                                        <div class="input-group" v-else-if = "field.is_money">
                                            <span class="input-group-text"><i class="bi bi-currency-rupee"></i></span>
                                            <input :type="field.type" class="form-control" v-model="formData[field.value]" :required="field.required"/>
                                        </div>

                                        <input v-else :type="field.type" class="form-control" v-model="formData[field.value]" :required="field.required"/>

                                    </div>

                                    <div class="col-12 mt-3">
                                        <button class="btn btn-success w-100" @click="saveStatusForm">Save</button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                            Status History
                        </button>
                        </h2>
                        <div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">Status History<br>Status History<br>Status History</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `

};

// **** Usage ************
// const [{ statusUpdates }] = await $importComponent([
//   '/pages/views/pm/status_updates.js'
// ]);
// components: { statusUpdates }
// <statusUpdates ref="statusupdates" />
// <button class="btn btn-primary" type="button" @click = "openOffcanvass()">Toggle right offcanvas</button>
// In parent methods, e.g., when clicking the "openOffcanvass" cell: