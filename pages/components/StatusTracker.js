export const StatusTracker = {
    name: 'StatusTracker',
    props: {
        currentStatus: {
            type: [Number, String],
            required: true
        },
        isCertifiable: {
            type: [String, Boolean],
            default: 'n'
        }
    },
    
    computed: {
        statusFlow() {
            const isJLRVehicle = this.isCertifiable === 'y' || this.isCertifiable === true;
            
            if (isJLRVehicle) {
                // JLR Flow: Refurbishment → Certification → Need Approval → Ready for Sale (Final)
                return [
                    {
                        id: 1,
                        title: 'Refurbishment',
                        subtitle: 'Details & Quality Check',
                        icon: 'bi bi-tools'
                    },
                    {
                        id: 2,
                        title: 'Certification',
                        subtitle: 'Age & Mileage Criteria',
                        icon: 'bi bi-clipboard-check'
                    },
                    {
                        id: 3,
                        title: 'Need Approval',
                        subtitle: 'Awaiting Final Approval',
                        icon: 'bi bi-shield-check'
                    },
                    {
                        id: 4,
                        title: 'Ready for Sale',
                        subtitle: 'Available in Inventory',
                        icon: 'bi bi-check-circle'
                    }
                ];
            } else {
                // Non-JLR Flow: Refurbishment → Ready for Sale (Final)
                return [
                    {
                        id: 1,
                        title: 'Refurbishment',
                        subtitle: 'Details & Quality Check',
                        icon: 'bi bi-tools'
                    },
                    {
                        id: 4,
                        title: 'Ready for Sale',
                        subtitle: 'Available in Inventory',
                        icon: 'bi bi-check-circle'
                    }
                ];
            }
        },
        
        currentStatusNum() {
            return parseInt(this.currentStatus) || 1;
        }
    },
    
    methods: {
        getStatusClass(step) {
            const currentStatus = this.currentStatusNum;
            
            if (step.id <= currentStatus) {
                return 'completed';
            } else {
                return 'pending';
            }
        },
        
        getConnectorClass(index) {
            const currentStatus = this.currentStatusNum;
            const currentStep = this.statusFlow[index];
            const nextStep = this.statusFlow[index + 1];
            
            if (!nextStep) return '';
            
            if (nextStep.id <= currentStatus) {
                return 'completed';
            } else {
                return 'pending';
            }
        }
    },
    
    template: `
        <div class="mb-4">
            <div class="card">
                <div class="card-header" style="background: #4A4F54 !important; border: none; padding: 0.5rem 0.75rem; color: white !important;">
                    <h6 class="mb-0 d-flex align-items-center small" style="color: white !important;">
                        <span>STATUS PROGRESS</span>
                        <span class="badge ms-auto small" style="background: #989991; color: white; border-radius: 4px; padding: 4px 8px; font-size: 10px;">
                            {{ isCertifiable === 'y' ? 'JLR VEHICLE' : 'NON-JLR VEHICLE' }}
                        </span>
                    </h6>
                </div>
                <div class="card-body" style="padding: 0.75rem; background: white;">
                    <div style="position: relative; width: 100%;">
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; position: relative; width: 100%;">
                            <div 
                                v-for="(step, index) in statusFlow" 
                                :key="step.id"
                                style="display: flex; align-items: center; flex: 1; position: relative;"
                                :style="{ 'flex': index === statusFlow.length - 1 ? '0 0 auto' : '1' }"
                            >
                                <!-- Status Step -->
                                <div style="display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; z-index: 2; min-width: 80px;">
                                    <div 
                                        style="width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; margin-bottom: 6px; border: 2px solid; transition: all 0.3s ease;"
                                        :style="{
                                            'background-color': getStatusClass(step) === 'completed' ? '#4A4F54' : '#f8f9fa',
                                            'border-color': getStatusClass(step) === 'completed' ? '#4A4F54' : '#CCCCCC',
                                            'color': getStatusClass(step) === 'completed' ? 'white' : '#6c757d',
                                            'box-shadow': getStatusClass(step) === 'completed' ? '0 4px 12px rgba(74, 79, 84, 0.3)' : 'none'
                                        }"
                                    >
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <div style="text-align: center;">
                                        <div 
                                            style="font-weight: 600; font-size: 12px; margin-bottom: 1px; line-height: 1.1;"
                                            :style="{
                                                'color': getStatusClass(step) === 'completed' ? '#4A4F54' : '#6c757d',
                                                'font-weight': getStatusClass(step) === 'completed' ? '700' : '600'
                                            }"
                                        >{{ step.title }}</div>
                                        <div 
                                            style="font-size: 10px; opacity: 0.75; line-height: 1.1;"
                                            :style="{
                                                'color': getStatusClass(step) === 'completed' ? '#4A4F54' : '#adb5bd'
                                            }"
                                        >{{ step.subtitle }}</div>
                                    </div>
                                </div>
                                
                                <!-- Connector Line -->
                                <div 
                                    v-if="index < statusFlow.length - 1"
                                    style="flex: 1; height: 2px; margin: 0 6px; position: relative; top: -26px; z-index: 1; border-radius: 2px; transition: all 0.3s ease;"
                                    :style="{
                                        'background': getConnectorClass(index) === 'completed' ? 'linear-gradient(to right, #4A4F54, #989991)' : '#CCCCCC',
                                        'box-shadow': getConnectorClass(index) === 'completed' ? '0 2px 8px rgba(74, 79, 84, 0.2)' : 'none'
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `
};