<?php
class moduleConfig
{
    public $dealer_id;
    public $login_user_id;
    public $connection;   
    public $module; 
    public array $commonConfig = [];  

    public function __construct() {
        global $connection;               
        $this->connection = $connection;
        $this->commonConfig = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
        // $this->module = $module;
    }

    public function buildOptions(?array $configArray, string $placeholder)
    {
        $options = [];        
        if (!empty($configArray) && is_array($configArray)) {
            foreach ($configArray as $id => $label) {
                $options[] = ['value' => (string)$id, 'label' => $label];
            }
        }
        return $options;
    }
       
    public function getConfig($module, $type = "config", $data=null){
        global $role_main; 
        $role_main = $role_main ?? $GLOBALS['dealership']['role_main'] ?? null;
      
        if( $type == "config") {
                if($module == "pm") { return $this->pm(); }
                if($module == "my-stock") { return $this->stock(); }
                if($module == "sm") { return $this->sm();  }
                if($module == "exchange") { return $this->exchange();  }
                if($module == "invoice") { return $this->invoice();}
                if($module == "dent-map") { return $this->dentMap();}
                if($module == "locations") { return $this->locations();}
                if($module == "dashboard-new") { return $this->dashboardNew();}
        }
        if( $type == "menu") {
            $menu = [];
            if($module == "pm") { $menu = $this->pmMenu($data); }
            if($module == "sm") { $menu = $this->smMenu($data); }
            if($module == "invoice") { $menu = $this->invoiceMenu($data); }
            if($module == "my-stock") { $menu = $this->stockMenu($data); }
            $menu = $this->processMenuItems($menu);
            return $menu;
        }
    }

    private function getDeviceMenu($allowedDevices = [])
    {
        $deviceType = strtolower(trim($_REQUEST['device_type'] ?? ''));
        if (empty($allowedDevices)) {
            return !empty($deviceType);
        }
        if (empty($deviceType)) {
            return false;
        }
        $allowedDevices = array_map('strtolower', (array)$allowedDevices);
        return in_array($deviceType, $allowedDevices);
    }

    private function processMenuItems($menu)
    {
        $processed = [];
        foreach ($menu as $key => $item) {
            if (!$this->getDeviceMenu($item['for'] ?? [])) {
                continue;
            }
            $access = $item['accessApply'] ?? [];
            $processed[$key] = [
                'fieldKey'   => $item['fieldKey']   ?? $key,
                'fieldLabel' => $item['fieldLabel'] ?? '',
                'component'  => $item['component']  ?? '',
                'isEnabled'  => $this->conditionalApplyAccess($access['isEnabled']  ?? [], true),
                'isReadOnly' => $this->conditionalApplyAccess($access['isReadOnly'] ?? [], false),
                'isHidden'   => $this->conditionalApplyAccess($access['isHidden']   ?? [], false),
            ];
        }
        return $processed;
    }

    private function conditionalApplyAccess(array $conditions, $default)
    {
        if (empty($conditions)) {
            return $default;
        }

        foreach ($conditions as $condition) {
            $type = $condition['type'] ?? null;
            $req  = $condition['request'] ?? [];

            if ($type === 'validate') {
                $value     = $req['value'] ?? null;
                $notEqual  = $req['not_equal'] ?? [];
                $equal     = $req['equal'] ?? [];

                if (!empty($equal)) {
                    return in_array($value, $equal);
                }
                if (!empty($notEqual)) {
                    return !in_array($value, $notEqual);
                }
                return $default;
            }
            if ($type === 'user') {
                $role_main = strtolower(trim($GLOBALS['dealership']['role_main'] ?? 'n'));
                $validate  = $req['validate'] ?? [];

                if (!is_array($validate) && !empty($validate)) {
                    $validate = [$validate];
                }

                $validate = array_map(fn($v) => $v === 'role_main' ? $role_main : strtolower(trim($v)), $validate);

                if (!empty($validate)) {
                    return $role_main !== 'y'; 
                }

                return $default;
            }
        }
        return $default;
    }

        private function pmMenu($data = null)
        {
            if (empty($data) || !is_array($data)) {
                return [
                    "lead-detail" => [
                        "fieldLabel" => "LEAD DETAIL",
                        "component"  => "views/pm/add",
                        "accessApply" => [
                        ],
                    ],
                ];
            }

            $menuItems = [

                "status_history" => [
                    "fieldLabel" => "STATUS HISTORY",
                    "component" => "",
                    "for" => ['ios', 'android'],
                    "accessApply" => [ 
                        'isEnabled' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'not_equal' => ['4', '5'] ] ],
                        ],
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "overview"    => [
                    "fieldLabel" => "OVERVIEW",
                    "component" => "views/pm/overview",
                ],
                "lead-detail" => [
                    "fieldLabel" => "LEAD DETAIL",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "images"      => [
                    "fieldLabel" => "IMAGES",
                    "component" => "views/pm/images",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "dent-map"    => [
                    "fieldLabel" => "DENT MAP",
                    "for" => ['web'],
                    "component" => "views/pm/dent-map",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "evaluation"  => [
                    "fieldLabel" => "EVALUATION",
                    "component" => "views/pm/evaluation_checklist",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "status" => [
                    "fieldLabel" => "STATUS",
                    "for" => ['web'],
                    "component" => "views/pm/status",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "vahan" => [
                    "fieldLabel" => "VAHAN",
                    "component" => "views/pm/overview",
                    "accessApply" => [
                        'isHidden' => [
                            ['type' => 'user', 'request' => ['type' => 'role', 'validate' => ['role_main']] ],
                        ],
                    ],
                ],
            ];
            return $menuItems;
        }

        private function smMenu($data = null)
        {
            if (empty($data) || !is_array($data)) {
                return [
                    "lead-detail" => [
                        "fieldLabel" => "LEAD DETAIL",
                        "component"  => "views/pm/add",
                        "accessApply" => [
                        ],
                    ],
                ];
            }

            $menuItems = [
                 "status_history" => [
                    "fieldLabel" => "STATUS HISTORY",
                    "component" => "",
                    "for" => ['ios', 'android'],
                    "accessApply" => [ 
                    ],
                ],

                "overview"    => [
                    "fieldLabel" => "OVERVIEW",
                    "component" => "views/pm/overview",
                ],
                "edit-detail" => [
                    "fieldLabel" => "LEAD DETAIL",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "vehicles"      => [
                    "fieldLabel" => "VEHICLES",
                    "component" => "views/sm/vehicles",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "testdrive"      => [
                    "fieldLabel" => "TEST DRIVE",
                    "component" => "views/sm/testdrive",
                    "accessApply" => [ 
                       'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
                "status" => [
                    "fieldLabel" => "STATUS",
                    "for" => ['web'],
                    "component" => "views/pm/status",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['4', '5'] ] ],
                        ],
                    ],
                ],
               
            ];
            return $menuItems;
        }



        private function stockMenu($data = null)
        {
            if (empty($data) || !is_array($data)) {
                  return [
                    "stock-detail" => [
                        "fieldLabel" => "Stock DETAIL",
                        "component"  => "views/pm/add",
                        "accessApply" => [
                        ],
                    ],
                ];
            }
          
            $menuItems = [
                "status_history" => [
                    "fieldLabel" => "STATUS HISTORY",
                    "component" => "",
                    "for" => ['ios', 'android'],
                    "accessApply" => [ 
                    ],
                ],
                "overview"    => [
                    "fieldLabel" => "OVERVIEW",
                    "component" => "views/pm/overview",
                ],
                "stock-detail" => [
                    "fieldLabel" => "STOCK DETAIL",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['6'] ] ],
                        ],
                    ],
                ],
                "images"      => [
                    "fieldLabel" => "IMAGES",
                    "component" => "views/pm/images",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['6'] ] ],
                        ],
                    ],
                ],
                "refurb"      => [
                    "fieldLabel" => "REFURBISHMENT DETAILS",
                    "component" => "views/pm/evaluation_checklist",
                    "accessApply" => [ 
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['6'] ] ],
                        ],
                    ],
                ],
                "certification"      => [
                    "fieldLabel" => "CERTIFICATION",
                    "component" => "views/stock/certification",
                    "accessApply" => [ 
                         'isHidden' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['is_certifiable'], 'equal' => ['n'] ] ],
                        ],
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['3','4','6'] ] ],
                        ],
                    ],                    
                ],
                "approval"      => [
                    "fieldLabel" => "CERTIFICATION APPROVAL",
                    "component" => "views/stock/certification-approval",
                    "accessApply" => [ 
                        'isHidden' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['is_certifiable'], 'equal' => ['n'] ] ],
                        ],
                        'isReadOnly' => [
                            ['type' => 'validate', 'request' => [ 'type'=>'fieldKey', 'value'=>$data['detail']['status'], 'equal' => ['2','4','6'] ] ],
                        ],
                    ],
                ],
                // "status" => [
                //     "fieldLabel" => "STATUS",
                //     "for" => ['web'],
                //     "component" => "views/pm/status",
                //     "accessApply" => [ 
                //     ],
                // ],
                "vahan"       => [
                    "fieldLabel" => "VAHAN",
                    "component" => "views/pm/overview",
                    "accessApply" => [
                        'isHidden' => [
                            ['type' => 'user', 'request' => ['type' => 'role', 'validate' => ['role_main']] ],
                        ],
                    ],
                ],
            ];
    
            return $menuItems;
        }

        private function invoiceMenu($data = null)
        {

            $menuItems = [
                "overview"    => [
                    "fieldLabel" => "OVERVIEW",
                    "component" => "views/pm/overview",
                ],
                "customer-details"      => [
                    "fieldLabel" => "CUSTOMER DETAILS",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isDisabled' => [
                        ],
                    ],
                ],

                "invoice-details"      => [
                    "fieldLabel" => "INVOICE DETAILS",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isDisabled' => [
                        ],
                    ],
                ],

                "payment-details"      => [
                    "fieldLabel" => "PAYMENT DETAILS",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isDisabled' => [
                        ],
                    ],
                ],


                "documents"      => [
                    "fieldLabel" => "DOCUMENTS",
                    "component" => "views/pm/add",
                    "accessApply" => [ 
                        'isDisabled' => [
                        ],
                    ],
                ],


                "preview"      => [
                    "fieldLabel" => "PREVIEW",
                    "component" => "views/invoice/preview",
                    "accessApply" => [ 
                        'isDisabled' => [
                        ],
                    ],
                ]
            ];
            return $menuItems;
        }

public function pmFilters(){

return [
    "filters" => [
        "makes" => [
            [
                "isSelected" => true,
                "models" => [
                    [
                        "isSelected" => true,
                        "title" => "Alto 800"
                    ]
                ],
                "title" => "Maruti Suzuki"
            ],
            [
                "isSelected" => true,
                "models" => [
                    [
                        "isSelected" => true,
                        "title" => "Amaze"
                    ]
                ],
                "title" => "Honda"
            ]
        ],
        "owners" => [
            [
                "count" => 2,
                "isSelected" => true,
                "title" => "1 Owner"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "2 Owner"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "3+ Owner"
            ]
        ],
        "category" => [
            [
                "isSelected" => true,
                "title" => "2w"
            ]
        ],
        "kmsDriven" => [
            [
                "count" => 2,
                "isSelected" => true,
                "title" => "0 to 50,000 KMs"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "50,000 to 75,000 KMs"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "75,000 to 1 Lakh KMs"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "Above 1 Lakh KMs"
            ]
        ],
        "age" => [
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "0-3 Years"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "4-7 Years"
            ],
            [
                "count" => 0,
                "isSelected" => false,
                "title" => "8-10 Years"
            ],
            [
                "count" => 2,
                "isSelected" => true,
                "title" => "10+ Years"
            ]
        ]
    ]
];

} 



    private function pm()
    {
        GLOBAL $config;
        $data =  [     
            'menu' => $this->getConfig('pm', 'menu'),
            'sidebar' => (object)[
                'showSidebar' => true, 'sidebarItems' => []
            ],
            'detail' => (object)[
                'showSidebar' => true, 'sidebarItems' => []
            ],
            'grid' => (object)[
                'title' => "Purchase Master",
                'pagination' => (object)[
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'start_count' => 0,
                    'end_count' => 0, 
                    'perPageOptions' => [10, 25, 50, 100]
                ],
                'list' => (array)[],
                'header' => (array)[
                    [
                        'type'=>'button',
                        'label' => "Add Purchase Lead",
                        'icon' => "plus-circle",
                        'class' => "btn-dark",
                        'validation' => ['show' => true, 'disabled' => false],
                        'conditional' => [
                            'onclick' =>[
                                'meta' => ['key' => 'add_lead', 'type'=>'route', 'action' => "detail"],
                            ],
                        ]
                    ],
            
                    //   [
                    //     'type'=>'button',
                    //     'label' => "Export",
                    //     'icon' => "file-earmark-spreadsheet",
                    //     'validation' => ['show' => true, 'disabled' => false],
                    //     'class' => "btn-outline-dark",
                    //     'conditional' => [
                    //         'onclick' =>[
                    //             'meta' => ['key' => 'export', 'type'=>'get', 'action' => "exportData"],
                    //         ],
                    //     ]
                    // ]
                ],
                'searchConfig' => (object)[
                        'fields' => [
                            [
                                'fieldKey' => 'search_id',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Lead ID',
                                'fieldHolder' => 'Enter Lead ID',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 10,
                                'validation' => [
                                    'validationPattern' => get_field_regex('alphanumeric'),
                                    'errorMessageRequired' => 'Lead ID is required',
                                    'errorMessageInvalid' => 'Enter Valid Lead ID',
                                ],
                            ],
                            [
                                'fieldKey' => 'seller_name',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Name',
                                'fieldHolder' => 'Enter Customer Name',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 50,
                                'validation' => [
                                    'validationPattern' => get_field_regex('alphanumeric'),
                                    'errorMessageRequired' => 'Name is required',
                                    'errorMessageInvalid' => 'Enter Valid Name',
                                ],
                            ],
                            [
                                'fieldKey' => 'mobile',
                                'inputType' => 'phone',
                                'fieldLabel' => 'Mobile',
                                'fieldHolder' => 'Enter Mobile Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 10,
                                'validation' => [
                                    'validationPattern' => get_field_regex('mobile'),
                                    'errorMessageRequired' => 'Mobile is required',
                                    'errorMessageInvalid' => 'Enter Valid Mobile',
                                ],
                            ],
                            [
                                'fieldKey' => 'email',
                                'inputType' => 'email',
                                'fieldLabel' => 'Email',
                                'fieldHolder' => 'Enter Email Address',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 100,
                                'validation' => [
                                    'validationPattern' => get_field_regex('email'),
                                    'errorMessageRequired' => 'Email is required',
                                    'errorMessageInvalid' => 'Enter Valid email',
                                ],
                            ],
                            [
                                'fieldKey' => 'reg_num',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Registration Number',
                                'fieldHolder' => 'Enter Registration Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 20,
                                'isCaps'=>true,
                                'validation' => [
                                    'validationPattern' => get_field_regex('reg_num'),
                                    'errorMessageRequired' => 'RegNo is required',
                                    'errorMessageInvalid' => 'Enter Valid RegNo',
                                ],
                            ],
                            [
                                'fieldKey' => 'chassis',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'VIN (Chassis Number)',
                                'fieldHolder' => 'Enter VIN or Chassis Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 30,
                                'isCaps'=>true,
                                'validation' => [
                                    'validationPattern' => get_field_regex('chassis'),
                                    'errorMessageRequired' => 'Chassis is required',
                                    'errorMessageInvalid' => 'Enter Valid Chassis',
                                ],
                            ],
                            [
                                'fieldKey' => 'make',
                                'inputType' => 'dynamic_dropdown',
                                "inputChange" => "dynamic_models",
                                'fieldLabel' => 'Make',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                "value" => "",
                                "isSearch" => true,
                                "isGroup" => true,
                                "fieldOptions" => $this->buildOptions($this->commonConfig['makes'] ?? [], 'Makes'),
                                "clearFields" => ["model"],
                                'validation' => [
                                    "validationPattern" => get_field_regex('id'),
                                    'errorMessageRequired' => 'Make is required',
                                    'errorMessageInvalid' => 'Select Valid make',
                                ],
                            ],
                            [
                                'fieldKey' => 'model',
                                'inputType' => 'dynamic_dropdown',
                                "inputChange" => "",
                                'fieldLabel' => 'Model',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => "",
                                "dependsOn" => "make",
                                "fieldOptions" => $this->buildOptions($this->commonConfig['models'] ?? [], 'Models'),
                                'validation' => [
                                    "validationPattern" => get_field_regex('id'),
                                    'errorMessageRequired' => 'Model is required',
                                    'errorMessageInvalid' => 'Select Valid model',
                                ],
                            ],
                            [
                                "fieldKey" => "lead_classification",
                                "inputType" => "dropdownIds",
                                "fieldLabel" => "Lead Classification",
                                "isRequired" => false,
                                "isReadOnly" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['pm_classify'], 'Lead Classification'),
                                "validation" => [
                                    "validationPattern" => get_field_regex('alpha'),
                                    "errorMessageRequired" => "Lead classification is required",
                                    "errorMessageInvalid" => "Select a valid lead classification",
                                ],
                            ],
                            [
                                "fieldKey" => "branch",
                                "inputType" => "dynamic_dropdown",
                                "inputChange" => "dynamic_executies",
                                "fieldLabel" => "Branch",
                                "isRequired" => false,
                                "isReadOnly" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                                "clearFields" => ["executive"],
                                "role_main" => "y",
                                "validation" => [
                                    "validationPattern" => get_field_regex('id'),
                                    "errorMessageRequired" => "Branch is required",
                                    "errorMessageInvalid" => "Please select a valid Branch",
                                ],
                            ],
                            [
                                "fieldKey" => "executive",
                                "inputType" => "dynamic_dropdown",
                                "fieldLabel" => "Executive",
                                "isRequired" => false,
                                "isReadOnly" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['executive'] ?? [], 'Executive'),
                                "dependsOn" => "branch",
                                "role_main" => "y",
                                
                                        // 'isHidden' => [
                                        //     ['type' => 'user', 'request' => ['type' => 'role', 'validate' => ['role_main']] ],
                                        // ],
                                
                                "validation" => [
                                    "validationPattern" => get_field_regex('id'),
                                    "errorMessageRequired" => "Executive is required",
                                    "errorMessageInvalid" => "Please select a valid model from the list",
                                ],
                            ],
                            [
                                "fieldKey" => "evaluated",
                                "inputType" => "checkbox_group",
                                "fieldLabel" => "Evaluated",
                                "isReadOnly" => false,
                                "isHidden" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => [['value' => 'y', 'label' => 'Yes']],
                                "validation" => [
                                    "validationPattern" => "",
                                    "errorMessageRequired" => "Evaluated is required",
                                    "errorMessageInvalid" => "Select a valid Evaluated option",
                                ],
                            ],
                        ],
                    ],


               'columns' => [
                    // Column 1: Images (moved to first position)
                    [
                        'title'=> 'Images',
                        'data'=> [
                            ['key' => ['id'], 'ref' => ['images'], 'label' => '', 'type' => 'image' ],
                        ]
                    ],
                    // Column 2: Lead Details (renamed from #, added branch field)
                    [
                        'title'=> 'Lead Details',
                        'data'=> [
                            ['key' => ['formatted_id'], 'label' => 'Lead ID', 'type' => 'text' ],
                            ['key' => ['created'], 'label' => 'Created', 'type' => 'date' ],
                            ['key' => ['source_name', 'source_sub_name'], 'label' => 'Source', 'type' => 'concat' ],
                            ['key' => ['branch_name'], 'label' => 'Branch', 'type' => 'text', 'role_main' => 'y' ],
                            ['key' => ['executive_name'], 'icon'=>'person-fill-gear', 'attachKey'=>'executive', 'label' => 'Executive', 'type' => 'attach', 'role_main' => 'y' ],
                        ]
                    ],
                    // Column 3: Customer Details (name & mobile semi-bold without label prefix)
                    [
                        'title'=> 'Customer Details',
                        'data'=> [
                            ['key' => ['title', 'first_name', 'last_name'], 'label' => '', 'type' => 'text', 'class' => 'semibold' ],
                            ['key' => ['mobile'], 'label' => '', 'type' => 'text', 'class' => 'semibold', 'isMasked'=>'y' ],
                            ['key' => ['email'], 'label' => '', 'type' => 'text', 'class' => 'semibold', 'isMasked'=>'y' ],
                            ['key' => ['city_name', 'state_name', 'pin_code'], 'label' => 'Location', 'type' => 'concat' ],
                        ]
                    ],
                    // Column 4: Vehicle Details (MMV semi-bold without label, Chassis renamed to VIN)
                    [
                        'title'=> 'Vehicle Details',
                        'data'=> [
                            ['key' => ['make_name', 'model_name', 'variant_name'], 'label' => '', 'type' => 'concat', 'class' => 'semibold' ],
                            ['key' => ['chassis'], 'label' => 'VIN', 'type' => 'text' ],
                            ['key' => ['reg_num'], 'label' => 'Reg No', 'type' => 'text' ],
                            ['key' => ['reg_date'], 'label' => 'Reg Date', 'type' => 'date' ],
                        ]
                    ],
                    // Column 5: Pricing (NEW COLUMN with three price fields)
                    [
                        'title'=> 'Pricing',
                        'data'=> [
                            ['key' => ['price_indicative'], 'label' => 'Indicative Price', 'type' => 'numeric_format' ],
                            ['key' => ['price_quote'], 'label' => 'Retailer Price', 'type' => 'numeric_format' ],
                            ['key' => ['price_customer'], 'label' => 'Customer Expected', 'type' => 'numeric_format' ],
                        ]
                    ],
                    // Column 6: Status (unchanged)
                    [
                        'title'=> 'Status',
                        'data'=> [
                            ['key' => [], 'icon'=>'clock-history', 'attachKey'=>'history', 'label' => '', 'type' => 'attach', 'tooltip' => 'Status History' ],
                            ['key' => ['status_name'], 'label' => '', 'type' => 'badge' ],
                            ['key' => ['followup_date'], 'label' => 'Next Followup', 'type' => 'date' ],
                        ]
                    ],
                    // Column 7: Actions (unchanged)
                    [
                        'title'=> 'Actions',
                        'data'=> [
                            [
                                'label' => 'View',
                                'type' => 'link',
                                'class' => "btn-secondary",
                                'icon' => "eye",
                                'meta' => ['type'=>'route', 'action'=>'detail/:id']
                            ],
                            [
                                'label' => 'Update Status',
                                'type' => 'link',
                                'class' => "btn-outline-secondary",
                                'icon' => "sort-up",
                                'meta' => ['type'=>'route', 'action'=>'detail/:id/status']
                            ],
                        ]
                    ]
                ],
            ],
            'overview'=>(object)[
                // Config properties
              'meta' => [
                'title' => 'Lead Overview',
                'dataPath' => 'detail',
                'showImages' => true,
                'showDocuments' => true,
                'showButtons' => true,
                'loadedCheckPath' => 'detail',
              ],

               // 3 sections structure using categories
               'fields' => [
                    // Lead Details
                    'id' => [ 'category' => 'Lead Details', 'type' => 'view', 'key' => 'formatted_id', 'label' => 'Lead ID', 'val' => '' ],
                    'status' => [ 'category' => 'Lead Details', 'label' => 'Status', 'key' => 'status_name', 'type' => 'view',  'val' => '', ],
                    'sub_status' => [ 'category' => 'Lead Details', 'label' => 'Sub Status', 'key' => 'sub_status_name', 'type' => 'view',  'val' => '', ],
                    'dealer' => [ 'category' => 'Lead Details', 'label' => 'Dealer', 'key' => 'dealer_name', 'type' => 'view',  'val' => '', ],
                    'executive' => [ 'category' => 'Lead Details', 'label' => 'Executive', 'key' => 'executive_name', 'type' => 'view',  'val' => '', ],
                    'source' => [ 'category' => 'Lead Details', 'label' => 'Source', 'key' => 'source_name', 'type' => 'view',  'val' => '', ],
                    'source_sub' => [ 'category' => 'Lead Details', 'label' => 'Sub Source', 'key' => 'source_sub_name', 'type' => 'view',  'val' => '', ],
                    'followup_date' => [ 'category' => 'Lead Details', 'label' => 'Follow-up Date', 'key' => 'followup_date', 'type' => 'date',  'val' => '' ],
                    'evaluation_date' => [ 'category' => 'Lead Details', 'label' => 'Evaluated Date', 'key' => 'evaluation_date', 'type' => 'date',  'val' => '' ],
                    'created' => [ 'category' => 'Lead Details', 'label' => 'Created Date', 'key' => 'created', 'type' => 'date',  'val' => '' ],
                    'updated' => [ 'category' => 'Lead Details', 'label' => 'Updated Date', 'key' => 'updated', 'type' => 'date',  'val' => '' ],

                    // Vehicle Details
                    'source_other' => [ 'category' => 'Vehicle Details', 'label' => 'Vehicle Source', 'key' => 'source_other_name', 'type' => 'view', 'val' => '' ],
                    'car_type' => [ 'category' => 'Vehicle Details', 'label' => 'Car Type', 'key' => 'car_type_name', 'type' => 'view', 'val' => '' ],
                    'reg_type' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Type', 'key' => 'reg_type_name', 'type' => 'view', 'val' => '' ],
                    'reg_num' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Number', 'key' => 'reg_num', 'type' => 'view',  'val' => '', ],
                    'reg_date' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Date', 'key' => 'reg_date', 'type' => 'date',  'val' => '' ],
                    'mfg_year' => [ 'category' => 'Vehicle Details', 'label' => 'Manufacture Year', 'key' => 'mfg_year', 'type' => 'view',  'val' => '', ],
                    'mfg_month' => [ 'category' => 'Vehicle Details', 'label' => 'Manufacture Month', 'key' => 'mfg_month_name', 'type' => 'view',  'val' => '', ],
                    'make' => [ 'category' => 'Vehicle Details', 'label' => 'Make', 'key' => 'make_name', 'type' => 'view',  'val' => '', ],
                    'model' => [ 'category' => 'Vehicle Details', 'label' => 'Model', 'key' => 'model_name', 'type' => 'view',  'val' => '', ],
                    'variant' => [ 'category' => 'Vehicle Details', 'label' => 'Variant', 'key' => 'variant_name', 'type' => 'view',  'val' => '', ],
                    'chassis' => [ 'category' => 'Vehicle Details', 'label' => 'Chassis Number', 'key' => 'chassis', 'type' => 'view',  'val' => '', ],
                    'transmission' => [ 'category' => 'Vehicle Details', 'label' => 'Transmission', 'key' => 'transmission_name', 'type' => 'view', 'val' => '' ],
                    'mileage' => [ 'category' => 'Vehicle Details', 'label' => 'Mileage', 'key' => 'mileage', 'type' => 'view',  'val' => '', ],
                    'fuel' => [ 'category' => 'Vehicle Details', 'label' => 'Fuel Type', 'key' => 'fuel_name', 'type' => 'view', 'val' => '' ],
                    'fuel_end' => [ 'category' => 'Vehicle Details', 'label' => 'Fuel Endorsement (as per RC)', 'key' => 'fuel_end_name', 'type' => 'view', 'val' => '' ],
                    'color' => [ 'category' => 'Vehicle Details', 'label' => 'Color', 'key' => 'color_name', 'type' => 'view', 'val' => '' ],
                    'base_color' => [ 'category' => 'Vehicle Details', 'label' => 'Base Color', 'key' => 'base_color_name', 'type' => 'view', 'val' => '' ],
                    'interior_color' => [ 'category' => 'Vehicle Details', 'label' => 'Interior Color', 'key' => 'interior_color_name', 'type' => 'view', 'val' => '' ],
                    'owners' => [ 'category' => 'Vehicle Details', 'label' => 'No. of Owners', 'key' => 'owners_name', 'type' => 'view', 'val' => '' ],
                    'hypothecation' => [ 'category' => 'Vehicle Details', 'label' => 'Hypothecation', 'key' => 'hypothecation_name', 'type' => 'view', 'val' => '' ],
                    'bank_name' => [ 'category' => 'Vehicle Details', 'label' => 'Bank Name', 'key' => 'bank_name', 'type' => 'view', 'val' => '' ],
                    'loan_paid_off' => [ 'category' => 'Vehicle Details', 'label' => 'Loan Paid Off', 'key' => 'loan_paid_off', 'type' => 'view', 'val' => '' ],
                    'loan_amount' => [ 'category' => 'Vehicle Details', 'label' => 'Outstanding Loan Amount', 'key' => 'loan_amount', 'type' => 'view', 'val' => '' ],
                    'insurance_type' => [ 'category' => 'Vehicle Details', 'label' => 'Insurance Type', 'key' => 'insurance_type_name', 'type' => 'view', 'val' => '' ],
                    'insurance_exp_date' => [ 'category' => 'Vehicle Details', 'label' => 'Insurance Expiry Date', 'key' => 'insurance_exp_date', 'type' => 'date',  'val' => '' ],
                    'rc_pin_code' => [ 'category' => 'Vehicle Details', 'label' => 'RC Area & PIN Code', 'key' => 'rc_pin_code', 'type' => 'view', 'val' => '' ],
                    'rc_state' => [ 'category' => 'Vehicle Details', 'label' => 'RC State', 'key' => 'rc_state_name', 'type' => 'view', 'val' => '' ],
                    'rc_city' => [ 'category' => 'Vehicle Details', 'label' => 'RC City', 'key' => 'rc_city_name', 'type' => 'view', 'val' => '' ],
                    'rc_address' => [ 'category' => 'Vehicle Details', 'label' => 'RC Address', 'key' => 'rc_address', 'type' => 'view', 'val' => '' ],
                   

                    // Customer Details
                    'full_name' => [ 'category' => 'Customer Details', 'label' => 'Customer', 'key' => 'title,first_name,last_name', 'type' => 'view',  'val' => '', ],
                    'mobile' => [ 'category' => 'Customer Details', 'label' => 'Mobile', 'key' => 'mobile', 'type' => 'view',  'val' => '', ],
                    'email' => [ 'category' => 'Customer Details', 'label' => 'Email', 'key' => 'email', 'type' => 'view',  'val' => '', ],
                    'contact_name' => [ 'category' => 'Customer Details', 'label' => 'Contact Person', 'key' => 'contact_name', 'type' => 'view',  'val' => '', ],
                    'pin_code' => [ 'category' => 'Customer Details', 'label' => 'Pin Code', 'key' => 'pin_code', 'type' => 'view',  'val' => '', ],
                    'state' => [ 'category' => 'Customer Details', 'label' => 'State', 'key' => 'state_name', 'type' => 'view',  'val' => '', ],
                    'city' => [ 'category' => 'Customer Details', 'label' => 'City', 'key' => 'city_name', 'type' => 'view',  'val' => '', ],
                    'address' => [ 'category' => 'Customer Details', 'label' => 'Address', 'key' => 'address', 'type' => 'view',  'val' => '', ],
                    'customer_notes' => [ 'category' => 'Customer Details', 'label' => 'Notes', 'key' => 'customer_notes', 'type' => 'view',  'val' => '', ],
                    'reason_for_selling' => [ 'category' => 'Customer Details', 'label' => 'Reason for Selling', 'key' => 'reason_for_selling_name', 'type' => 'view', 'val' => '' ],
                    'rs_subsection' => [ 'category' =>'Customer Details', 'label' => 'Reason Subsection', 'key' => 'rs_subsection_name', 'type' => 'view', 'val' => '' ],
                    'rs_make' => [ 'category' => 'Customer Details', 'label' => 'Make (Interested)', 'key' => 'rs_make_name', 'type' => 'view', 'val' => '' ],
                    'rs_model' => [ 'category' => 'Customer Details', 'label' => 'Model (Interested)', 'key' => 'rs_model_name', 'type' => 'view', 'val' => '' ],
                    'rs_variant' => [ 'category' => 'Customer Details', 'label' => 'Variant (Interested)', 'key' => 'rs_variant_name', 'type' => 'view', 'val' => '' ],
                    'rs_reason' => [ 'category' => 'Customer Details', 'label' => 'Other Reason', 'key' => 'rs_reason', 'type' => 'view', 'val' => '' ],
                    'buying_horizon' => [ 'category' => 'Customer Details', 'label' => 'Buying Horizon', 'key' => 'buying_horizon_name', 'type' => 'view', 'val' => '' ],
                    'budget' => [ 'category' => 'Customer Details', 'label' => 'Budget', 'key' => 'budget_name', 'type' => 'view', 'val' => '' ],

                    // Media (used by dedicated sections, not displayed as fields)
                    'images' => [ 'category' => 'Images', 'label' => 'Images', 'key' => 'images', 'type' => 'media',  'val' => '', ],
                    'documents' => [ 'category' => 'Documents', 'label' => 'Documents', 'key' => 'documents', 'type' => 'media',  'val' => '', ],
               ],
            ],


            'images'=> (object)[

              'front' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Front straight shot",
                    "fieldKey"=> "front",
                    "isRequired"=> true,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "front",
                        "imgName"=> "Front straight shot",
                        "imgMand"=> "y",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/front.png"
                    ]
                ],

                'rhs' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "RHS shot",
                    "fieldKey"=> "rhs",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rhs",
                        "imgName"=> "RHS shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rhs.png"
                    ]
                ],

               'rhs-ang' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "RHS angular shot",
                    "fieldKey"=> "rhs-ang",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rhs-ang",
                        "imgName"=> "RHS angular shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rhs-ang.png"
                    ]
                ],

                'rear' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Rear straight shot",
                    "fieldKey"=> "rear",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rear",
                        "imgName"=> "Rear straight shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rear.png"
                    ]
                ],

                 'lhs' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "LHS shot",
                    "fieldKey"=> "lhs",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "lhs",
                        "imgName"=> "LHS shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/lhs.png"
                    ]
                ],

               'lhs-ang' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "LHS angular shot",
                    "fieldKey"=> "lhs-ang",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "lhs-ang",
                        "imgName"=> "LHS angular shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/lhs-ang.png"
                    ]
                ],

               'windshield-int' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Interior windshield",
                    "fieldKey"=> "windshield-int",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "windshield-int",
                        "imgName"=> "Interior windshield",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/windshield.png"
                    ]
                ],

               'rear-door' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Rear Door open Shot",
                    "fieldKey"=> "rear-door",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rear-door",
                        "imgName"=> "Rear Door open Shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rear-door.png"
                    ]
                ],

               'frhs-door' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "FRHS door open shot",
                    "fieldKey"=> "frhs-door",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "frhs-door",
                        "imgName"=> "FRHS door open shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/frhs-door.png"
                    ]
                ],

               'speedometer' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Tachometer/Speedometer",
                    "fieldKey"=> "speedometer",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "speedometer",
                        "imgName"=> "Tachometer/Speedometer",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/speedometer.png"
                    ]
                ],

               'roof' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Sun/moon/Roof-top",
                    "fieldKey"=> "roof",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "roof",
                        "imgName"=> "Sun/moon/Roof-top",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/roof.png"
                    ]
                ],

                'dashboard' =>  [
                      "inputType"=> "car_img",
                      "fieldLabel"=> "Infotainment console",
                      "fieldKey"=> "dashboard",
                      "isRequired"=> false,
                      'src' => "",
                      'file' => null,
                      'isUploading' => false, 
                      'queueStatus' => 'idle',
                      "imgPart"=> (object)[
                            "imgId"=> "",
                            "imgSno"=> "dashboard",
                            "imgName"=> "Infotainment console",
                            "imgMand"=> "n",
                            "imgLogo"=> "",
                            "imgOrientation"=> "L",
                            "imgAction"=> "add",
                            "ImgEdit"=> "No",
                            "imgLat"=> "",
                            "imgLong"=> "",
                            "imgTime"=> "",
                            "imgFile"=> "",
                            "imgFlag"=> "0",
                            "imgPath"=> "",
                            "imgData"=> "",
                            "imgSubData"=> "",
                            "imgAngle"=> "",
                            "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/dashboard.png"
                      ]
                 ],

               'steering' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Steering control buttons",
                    "fieldKey"=> "steering",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "steering",
                        "imgName"=> "Steering control buttons",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/steering.png"
                    ]
                ],

               'start-btn' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Smart start button",
                    "fieldKey"=> "start-btn",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "start-btn",
                        "imgName"=> "Smart start button",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/start-btn.png"
                    ]
                ],


                'img-1' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 1",
                    "fieldKey"=> "img-1",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-1",
                        "imgName"=> "Other Image 1",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-2' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 2",
                    "fieldKey"=> "img-2",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-2",
                        "imgName"=> "Other Image 2",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                
                'img-3' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 3",
                    "fieldKey"=> "img-3",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-3",
                        "imgName"=> "Other Image 3",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-4' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 4",
                    "fieldKey"=> "img-4",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-4",
                        "imgName"=> "Other Image 4",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-5' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 5",
                    "fieldKey"=> "img-5",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-5",
                        "imgName"=> "Other Image 5",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-6' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 6",
                    "fieldKey"=> "img-6",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-6",
                        "imgName"=> "Other Image 6",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-7' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 7",
                    "fieldKey"=> "img-7",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-7",
                        "imgName"=> "Other Image 7",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-8' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 8",
                    "fieldKey"=> "img-8",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-8",
                        "imgName"=> "Other Image 8",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],
                 'img-9' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 9",
                    "fieldKey"=> "img-9",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-9",
                        "imgName"=> "Other Image 9",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],
                 'img-10' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 10",
                    "fieldKey"=> "img-10",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-10",
                        "imgName"=> "Other Image 10",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],
                 'img-11' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 11",
                    "fieldKey"=> "img-11",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-11",
                        "imgName"=> "Other Image 11",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'pedals' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Pedals Photo",
                    "fieldKey"=> "pedals",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "pedals",
                        "imgName"=> "Pedals Photo",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/pedals.png"
                    ]
                ],

               'key' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Car Key Photo",
                    "fieldKey"=> "key",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "key",
                        "imgName"=> "Car Key Photo",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/key.png"
                    ]
                ],

               'rc' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "RC Book / Chassis No.Plate",
                    "fieldKey"=> "rc",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rc",
                        "imgName"=> "RC Book / Chassis No.Plate",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rc.png"
                    ]
                ],

               'insurance' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "insurance copy",
                    "fieldKey"=> "insurance",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "insurance",
                        "imgName"=> "insurance copy",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/insurance-copy.png"
                    ]
                ],

               'chassis' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Chassis Print Embossing",
                    "fieldKey"=> "chassis",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "chassis",
                        "imgName"=> "Chassis Print Embossing",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/chassis.png"
                    ]
                ],
            ],

           'history' => [
                [
                    'title'=> 'Updated Details',
                    'data'=> [
                        ['key' => ['updated_date'], 'label' => 'Updated', 'type' => 'date' ],
                        ['key' => ['updated_by'], 'label' => 'Updated By', 'type' => 'text' ],
                    ]
                ],
                [
                    'title'=> 'Status Details',
                    'data'=> [
                        ['key' => ['status_name'], 'label' => 'Show Status', 'type' => 'text' ],
                        ['key' => ['sub_status_name'], 'label' => 'Sub Status', 'type' => 'text' ],
                        ['key' => ['followup_date'], 'label' => 'Followup Date', 'type' => 'date' ],
                        ['key' => ['remarks'], 'label' => 'Remarks', 'type' => 'text' ],
                    ]
                ],
                [
                    'title'=> 'Pricing',
                    'data'=> [
                        ['key' => ['price_customer'], 'label' => 'Customer', 'type' => 'numeric_format' ],
                        ['key' => ['price_quote'], 'label' => 'Quote', 'type' => 'numeric_format' ],
                        ['key' => ['price_expenses'], 'label' => 'Expenses', 'type' => 'numeric_format' ],
                        ['key' => ['price_indicative'], 'label' => 'Indicative', 'type' => 'numeric_format' ],
                        ['key' => ['price_selling'], 'label' => 'Selling', 'type' => 'numeric_format' ],
                    ]
                ], 

            ],
        
            'addConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "Purchase Lead",
                        "formType" => "expandable_form",
                        "sections" => [

                            [
                                "sectionId" => "lead-details",
                                "sectionTitle" => "Lead Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "source",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_subsources",
                                        "fieldLabel" => "Source",
                                        "isRequired" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "clearFields" => ["source_sub"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Source is Required",
                                            "errorMessageInvalid" => "Please select a valid source",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "source_sub",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "",
                                        "fieldLabel" => "Sub Source",
                                        "isRequired" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "dependsOn" => "source",
                                        "clearFields" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Sub Source is required",
                                            "errorMessageInvalid" => "Please select a valid Sub Source",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "branch",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_executies",
                                        "fieldLabel" => "Branch",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                                        "clearFields" => ["executive"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Branch is required",
                                            "errorMessageInvalid" => "Please select a valid Branch",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "executive",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_variants",
                                        "fieldLabel" => "Executive",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['executive'] ?? [], 'Executive'),
                                        "dependsOn" => "branch",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Executive is required",
                                            "errorMessageInvalid" => "Please select a valid model from the list",
                                        ],
                                    ],
                                ]
                            ], 
                            [
                                "sectionId" => "vehicle_details",
                                "sectionTitle" => "Vehicle Details",
                                "isExpandedByDefault" => true,
                                "fields" => [

                                    [
                                        "fieldKey" => "source_other",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Vehicle Source",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['source_other'], 'Vehicle Source'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Vehicle Source is required",
                                            "errorMessageInvalid" => "Select a valid Vehicle Source",
                                        ],
                                        "conditionalApply" => [
                                            'issetValue' => [
                                                ['fieldKey'=>'car_type', 'equal' => ['1','3', '5','7','8'], 'value' => '3' ],
                                                ['fieldKey'=>'car_type', 'equal' => ['2'], 'value' => '2' ],
                                                ['fieldKey'=>'car_type', 'equal' => ['4', '6'], 'value' => '1' ],
                                                ['fieldKey'=>'car_type', 'equal' => ['', '0'], 'value' => '' ],
                                            ]
                                        ],
                                    ],

                                    [
                                        "fieldKey" => "car_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Car Type",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['car_type'], 'Car Type'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Car Type is required",
                                            "errorMessageInvalid" => "Select Valid Car Type.",
                                        ],
                                    ],


                                      [
                                        "fieldKey" => "park_and_sell",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Park and Sell",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Park and Sell'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "Park and Sell is required",
                                            "errorMessageInvalid" => "Select Valid Park and Sell.",
                                        ],
                                    ],

                                    [
                                        "fieldKey" => "reg_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Registration Type",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "isBr" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['reg_type'], 'Registration Type'),
                                        "clearFields" => ["contact_name"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Registration Type is required",
                                            "errorMessageInvalid" => "Select a valid Registration Type",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'reg_num', 'equal' => ['', '1'] ],
                                                ['fieldKey'=>'reg_date', 'equal' => ['', '1'] ],
                                                ['fieldKey'=>'contact_name', 'not_equal' => ['3'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'reg_num', 'not_equal' => ['', '1'] ],
                                                ['fieldKey'=>'reg_date', 'not_equal' => ['', '1'] ],
                                                ['fieldKey'=>'contact_name', 'equal' => ['', '3'] ],
                                            ],
                                            'setFieldLabel' => [
                                                ['fieldKey'=>'first_name', 'equal' => ['3'], 'fieldLabel'=>'Contact Person First Name'],
                                                ['fieldKey'=>'last_name', 'equal' => ['3'], 'fieldLabel'=>'Contact Person Last Name'],
                                                ['fieldKey'=>'first_name', 'not_equal' => ['3'], 'fieldLabel'=>'First Name'],
                                                ['fieldKey'=>'last_name', 'not_equal' => ['3'], 'fieldLabel'=>'Last Name'],
                                            ],
                                            'issetValue' => [
                                                ['fieldKey'=>'title', 'equal' => ['3'], 'value' => 'M/s' ],
                                            ],

                                        ],
                                    ],
                                    [
                                        "fieldKey" => "reg_num",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Registration Number",
                                        "fieldHolder" => "Enter Registration Number",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 30,
                                        "isCaps" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('reg_num'),
                                            "errorMessageRequired" => "Registration Number is required",
                                            "errorMessageInvalid" => "Enter Valid Registration Number",
                                        ],
                                        'addons' => [
                                            [
                                                "fieldKey" => "vaahan",
                                                'inputType' => 'component',
                                                "inputChange" => "dynamic_vaahan",
                                                'isDisabled' => false,
                                                'tooltip' => 'Fetch from Vaahan',
                                            ]
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "reg_date",
                                        "inputType" => "calender",
                                        "calenderType" => "upto_current",
                                        "fieldLabel" => "Registration Date",
                                        "isRequired" =>false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Registration Date is required",
                                            "errorMessageInvalid" => "Enter valid Registration Date",
                                        ],
                                    ],
                                  
                                    [
                                        "fieldKey" => "mfg_year",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Manufacturing Year",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['years'], 'Years'),
                                        "dependsOn" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Manufacturing Year is required",
                                            "errorMessageInvalid" => "Select a valid manufacturing year",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mfg_month",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Manufacturing Month",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['months'], 'Months'),
                                        "dependsOn" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Manufacturing Year is required",
                                            "errorMessageInvalid" => "Select a valid manufacturing year",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "make",
                                        "inputType" => "dynamic_dropdown",
                                        // "inputChange" => ["dynamic_models", "dynamic_colors"],
                                        "inputChange" => ["dynamic_models"],
                                        "fieldLabel" => "Make",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isSearch" => true,
                                        "isGroup" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['makes'] ?? [], 'Makes'),
                                        "clearFields" => ["model","variant","color","interior_color"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Make is required",
                                            "errorMessageInvalid" => "Please select a valid make",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "model",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_variants",
                                        "fieldLabel" => "Model",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['models'] ?? [], 'Models'),
                                        "dependsOn" => "make",
                                        "clearFields" => ["variant"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Model is required",
                                            "errorMessageInvalid" => "Please select a valid model from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "variant",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "",
                                        "fieldLabel" => "Variant",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['variants'] ?? [], 'Variants'),
                                        "dependsOn" => "model",
                                        "clearFields" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Variant is required",
                                            "errorMessageInvalid" => "Please select a valid variant from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "color",
                                        "inputType" => "dropdownIds",
                                        "inputChange" => "",
                                        "fieldLabel" => "Exterior Color",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "make",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['colors'] ?? [], 'Colors'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Exterior Color is required",
                                            "errorMessageInvalid" => "Select Valid Exterior Color.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "interior_color",
                                        "inputType" => "dropdownIds",
                                        "inputChange" => "",
                                        "fieldLabel" => "Interior Color / Upholstery",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "make",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['colors'] ?? [], 'Colors'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Interior Color is required",
                                            "errorMessageInvalid" => "Select Valid Interior Color.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "body_type",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "",
                                        "fieldLabel" => "Body Type",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['body_types'] ?? [], 'Body Types'),
                                        "dependsOn" => "model",
                                        "clearFields" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Body Type is required",
                                            "errorMessageInvalid" => "Please select a valid Body Type from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "chassis",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "VIN / Chassis Number",
                                        "fieldHolder" => "Enter Vehicle Chassis",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 30,
                                        "isCaps" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('chassis'),
                                            "errorMessageRequired" => "Chassis is required",
                                            "errorMessageInvalid" => "Enter Valid Chassis",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "transmission",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Transmission",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['transmission'], 'Transmission'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Transmission is required",
                                            "errorMessageInvalid" => "Select Valid Transmission.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mileage",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Mileage",
                                        "fieldHolder" => "Enter Mileage in KMs",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Mileage is required",
                                            "errorMessageInvalid" => "Enter Valid Mileage",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "fuel",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Fuel",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['fuel'], 'Fuel'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Fuel is required",
                                            "errorMessageInvalid" => "Select Valid Fuel.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "fuel_end",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Fuel Endorsement (as per RC)",
                                        "tooltip" => "Does the RC book show the same fuel type?",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Fuel Endorsement'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "Fuel Endorsement is required",
                                            "errorMessageInvalid" => "Select Valid Fuel Endorsement.",
                                        ],
                                    ],
                                    // [
                                    //     "fieldKey" => "color",
                                    //     "inputType" => "dropdownIds",
                                    //     "fieldLabel" => "Color",
                                    //     "isRequired" => true,
                                    //     "isReadOnly" => false,
                                    //     "defaultInputValue" => "",
                                    //     "value" => "",
                                    //     "fieldOptionIds" => $this->buildOptions($this->commonConfig['colors'], 'Color'),
                                    //     "validation" => [
                                    //         "validationPattern" => get_field_regex('id'),
                                    //         "errorMessageRequired" => "Color is required",
                                    //         "errorMessageInvalid" => "Select Valid Color.",
                                    //     ],
                                    // ],
                                    [
                                        "fieldKey" => "owners",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Number of Owners",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['owners'], 'Owners'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Owners is required",
                                            "errorMessageInvalid" => "Select Valid Owners.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "hypothecation",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Hypothecation",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['hypothecation'], 'Hypothecation'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "Hypothecation is required",
                                            "errorMessageInvalid" => "Select Valid Hypothecation.",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'bank_name', 'equal' => ['', 'n'] ],
                                                ['fieldKey'=>'loan_paid_off', 'equal' => ['', 'n'] ],
                                                ['fieldKey'=>'loan_amount', 'equal' => ['', 'n'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'bank_name', 'equal' => ['y'] ],
                                                ['fieldKey'=>'loan_paid_off', 'equal' => ['y'] ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "bank_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Financier Name",
                                        "fieldHolder" => "Enter Financier Name",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHiddden" => true,
                                        "defaultInputValue" => "",
                                        "maxLength" => 50,
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Financier Name is required",
                                            "errorMessageInvalid" => "Enter valid Financier Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "loan_paid_off",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Has Loan Been Paid Off?",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHiddden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "clearFields" => ['loan_amount'],
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['hypothecation'], 'Loan Paid'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "Please specify if loan is paid off",
                                            "errorMessageInvalid" => "Please select yes or no",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'loan_amount', 'equal' => ['y'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'loan_amount', 'equal' => ['n'] ],
                                            ]
                                        ],
                                    ],

                                    [
                                        "fieldKey" => "loan_amount",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Outstanding Loan Amount",
                                        "fieldHolder" => "Enter Outstanding Loan Amount",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHiddden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Outstanding Amount is required",
                                            "errorMessageInvalid" => "Enter Valid Outstanding Amount",
                                        ],
                                    ],

                                    [
                                        "fieldKey" => "insurance_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Insurance Type",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "Third Party",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['insurance_type'], 'Insurance Type' ),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Insurance Type is required",
                                            "errorMessageInvalid" => "Select Valid Insurance Type",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "insurance_exp_date",
                                        "inputType" => "calender",
                                        "fieldLabel" => "Insurance Expiry Date",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Insurance Expiry Date is required",
                                            "errorMessageInvalid" => "Enter valid Insurance Expiry Date",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rc_pin_code",
                                        "inputType" => "pin_code_search",
                                        "inputChange" => "dynamic_location",
                                        "fieldLabel" => "RC Area & PIN Code",
                                        "fieldHolder" => "Enter Pincode",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 6,
                                        "clearFields" => ["rc_state", "rc_city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "RC Pincode is required",
                                            "errorMessageInvalid" => "Enter Valid RC Pincode",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rc_state",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_state",
                                        "fieldLabel" => "RC State",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "clearFields" => ["rc_city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "RC State is required",
                                            "errorMessageInvalid" => "Please select a RC state from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rc_city",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_city",
                                        "fieldLabel" => "RC City",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "dependsOn" => "rc_state",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "RC City is required",
                                            "errorMessageInvalid" => "Please select a RC city from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rc_address",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "RC Address",
                                        "fieldHolder" => "Enter RC Address",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 200,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Rc Address is required",
                                            "errorMessageInvalid" => "Enter Valid RC Address",
                                        ],
                                    ],

                                ],
                            ],
                            [
                                "sectionId" => "customer_details",
                                "sectionTitle" => "Customer Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "title",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Salutation",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['title'], 'Salutation'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('title'),
                                            "errorMessageRequired" => "Please select valid Salutation",
                                            "errorMessageInvalid" => "Select Valid Salutation",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "contact_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Company Name",
                                        "fieldHolder" => "Enter Company name",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 50,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "Contact Person is Required",
                                            "errorMessageInvalid" => "Enter Valid Contact Person Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "first_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "First Name",
                                        "fieldHolder" => "Enter First Name",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 50,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "First Name is required",
                                            "errorMessageInvalid" => "Enter Valid First Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "last_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Last Name",
                                        "fieldHolder" => "Enter Last Name",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 50,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "Last Name is Required",
                                            "errorMessageInvalid" => "Enter Valid Last Name",
                                        ],
                                    ],
                                  
                                    [
                                        "fieldKey" => "mobile",
                                        "inputType" => "phone",
                                        "fieldLabel" => "Mobile",
                                        "fieldHolder" => "Enter Mobile Number",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 10,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('mobile'),
                                            "errorMessageRequired" => "Mobile Number is Required",
                                            "errorMessageInvalid" => "Enter Valid 10 digit Mobile Number starting with 6-9",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "email",
                                        "inputType" => "email",
                                        "fieldLabel" => "Email",
                                        "fieldHolder" => "Enter Email Address",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('email'),
                                            "errorMessageRequired" => "Email is Required",
                                            "errorMessageInvalid" => "Enter Valid Email",
                                        ],
                                    ],

                                    [
                                        "fieldKey" => "contact_method",
                                        "inputType" => "checkbox_group",
                                        "fieldLabel" => "Preferred Contact Method",
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['contact_method'], 'Contact Method'),
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Contact Method Type is required",
                                            "errorMessageInvalid" => "Select a valid Contact Method",
                                        ],
                                    ],

                                    [
                                        "fieldKey" => "pin_code",
                                        "inputType" => "pin_code_search",
                                        "inputChange" => "dynamic_location",
                                        "fieldLabel" => "Pincode",
                                        "fieldHolder" => "Enter Pincode",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 6,
                                        "clearFields" => ["state", "city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Pincode is required",
                                            "errorMessageInvalid" => "Enter Valid Pincode",
                                        ],

                                        'addons' => [
                                            [
                                                "fieldKey" => "copy",
                                                'inputType' => 'action',
                                                "inputChange" => "dynamic_copy",
                                                "inputIcon" => "copy",
                                                "fieldLabel" => "",
                                                'isDisabled' => false,
                                                'tooltip' => "Copy RC Address",
                                                "conditionalApply" => [
                                                    'copyValue' => [
                                                        ['fieldKey'=>'pin_code', 'copyFieldKey' => 'rc_pin_code' ],
                                                        ['fieldKey'=>'address', 'copyFieldKey' => 'rc_address' ],
                                                        ['fieldKey' => 'state','copyFieldKey' => 'rc_state' ],
                                                        ['fieldKey' => 'city','copyFieldKey' => 'rc_city' ],
                                                    ],
                                                ],
                                            ]
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "state",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_state",
                                        "fieldLabel" => "State",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "clearFields" => ["city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "State is required",
                                            "errorMessageInvalid" => "Please select a state from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "city",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_city",
                                        "fieldLabel" => "City",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "dependsOn" => "state",
                                        "clearFields"=> [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "City is required",
                                            "errorMessageInvalid" => "Please select a city from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "address",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Address",
                                        "fieldHolder" => "Enter Address",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 200,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Address is required",
                                            "errorMessageInvalid" => "Enter Valid Address",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_notes",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Notes",
                                        "fieldHolder" => "Enter any additional notes about the customer",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 500,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Notes are required",
                                            "errorMessageInvalid" => "Enter valid notes",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "reason_for_selling",
                                        "inputType" => "dropdownIds",
                                        "inputChange" => "",
                                        "fieldLabel" => "Reason for Selling",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['reason_for_selling'] ?? [], 'Reason for Selling'),
                                        'clearFields' => ["rs_make", "rs_model", "rs_variant", "rs_subsection", "rs_reason", "buying_horizon", "budget" ],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Reason for selling is required",
                                            "errorMessageInvalid" => "Select a valid reason",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'rs_make', 'not_equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'rs_model', 'not_equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'rs_variant', 'not_equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'rs_subsection', 'not_equal' => ['5'] ],
                                                ['fieldKey'=>'rs_reason', 'equal' => ['', '1', '2', '3', '4', '5', '6', '7', '8'] ],
                                                ['fieldKey'=>'buying_horizon', 'not_equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'budget', 'not_equal' => ['1', '2', '3', '4'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'rs_make', 'equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'rs_model', 'equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'rs_variant', 'equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'rs_subsection', 'equal' => ['5'] ],
                                                ['fieldKey'=>'rs_reason', 'not_equal' => ['', '1', '2', '3', '4', '5', '6', '7', '8'] ],
                                                ['fieldKey'=>'buying_horizon', 'equal' => ['1', '2', '3', '4'] ],
                                                ['fieldKey'=>'budget', 'equal' => ['1', '2', '3', '4'] ],
                                            ],
                                            'isOptionsShowGroup'=>[
                                                ['fieldKey'=>'rs_make', 'equal' => ['1', '2'], "optionsGroup" => ['Popular'] ], 
                                                ['fieldKey'=>'rs_make', 'not_equal' => ['1', '2'], "optionsGroup" => ['Others'] ],   
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rs_subsection",
                                        "inputType" => "dropdownIds",
                                        "inputChange" => "",
                                        "fieldLabel" => "Please Specify",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "reason_for_selling",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['rs_subsection_options'] ?? [], 'Subsection'),
                                        'clearFields' => ["rs_reason"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Please specify the reason",
                                            "errorMessageInvalid" => "Select a valid option",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'rs_reason', 'not_equal' => ['9'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'rs_reason', 'equal' => ['9'] ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rs_make",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_models",
                                        "fieldLabel" => "Make (Interested)",
                                        "isHidden" => true,
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isSearch" => true,
                                        "isGroup" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn"=> "reason_for_selling",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['makes'] ?? [], 'Makes'),
                                        "clearFields" => ["rs_model", "rs_variant"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Make is required",
                                            "errorMessageInvalid" => "Select Valid make",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rs_model",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_variants",
                                        "fieldLabel" => "Model (Interested)",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "rs_make",
                                        "clearFields" => ["rs_variant"],
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['models'] ?? [], 'Models'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Model is required",
                                            "errorMessageInvalid" => "Select Valid model",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rs_variant",
                                        "inputType" => "dynamic_dropdown",
                                        "fieldLabel" => "Variant (Interested)",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "rs_model",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['variants'] ?? [], 'Variants'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Variant is required",
                                            "errorMessageInvalid" => "Select Valid variant",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rs_reason",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Other Reason",
                                        "fieldHolder" => "Specify other reason",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 255,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Please specify other reason",
                                            "errorMessageInvalid" => "Enter valid text",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "buying_horizon",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Buying Horizon",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "reason_for_selling",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['buying_horizon'] ?? [], 'Select Buying Horizon'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Buying horizon is required",
                                            "errorMessageInvalid" => "Select a valid buying horizon",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "budget",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Budget",
                                        "fieldHolder" => "Select Budget Range",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['budget_range'], 'Budget'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Budget is required",
                                            "errorMessageInvalid" => "Select a valid budget range",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'statusConfig' => (object)[

                'columns' => [
                    [
                        'title'=> 'Status',
                        'data'=> [
                            ['key' => ['status_name'],  'label' => '', 'type' => 'text' ],
                            ['key' => ['sub_status_name'], 'label' => 'Sub Status', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Followup',
                        'data'=> [
                            ['key' => ['followup_date'],  'label' => 'Followup Date', 'type' => 'date' ],
                        ]
                    ],
                    [
                        'title'=> 'Evaluation',
                        'data'=> [
                            ['key' => ['evaluation_done'],  'label' => 'Evaluation Done', 'type' => 'text', 'class' => 'badge bg-secondary badge-cus text-uppercase' ],
                            ['key' => ['evaluation_date'],  'label' => 'Evaluated Date', 'type' => 'date' ],
                        ]
                    ],
                ],

                "fields" => [
                    [
                        "fieldLabel" => "Lead Status",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "leadStatus",
                                "sectionTitle" => "Status of Lead",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "status",
                                        "inputType" => "dropdownIds",
                                        "inputChange" => "dynamic_substatus",
                                        "fieldLabel" => "Status",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "clearFields" => ["sub_status", "followup_date", "remarks"],
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['pm_status'], 'Status'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Status is required",
                                            "errorMessageInvalid" => "Select a valid Status",
                                        ],
                                        "doApply" => [
                                            'isOptionsDisabled'=>[
                                                ['config'=>'evaluation_done',  'fieldKey'=>'evaluation_done', 'not_equal' => ['y', ''], "options" => ['5'] ],                                            
                                            ],
                                        ],
                                        "conditionalApply" => [

                                            'isOptionsDisabled'=>[
                                                // Fresh (1) -> can only go to Follow up (2) or Lost (5)
                                                ['fieldKey'=>'status', 'equal' => ['1'], "options" => ['1', '3', '4'] ],
                                                // Follow up (2) -> can only go to Deal Done (3) or Lost (5)
                                                ['fieldKey'=>'status', 'equal' => ['2'], "options" => ['1', '4'] ],
                                                // Deal Done (3) -> can only go to Purchased (4) or Lost (5), requires evaluation_done=Yes
                                                ['fieldKey'=>'status', 'equal' => ['3'], "options" => ['1', '2', '3']],
                                                // Purchased (4) -> can only go to Lost (5), cannot go back
                                                ['fieldKey'=>'status', 'equal' => ['4'], "options" => ['1', '2', '3', '4'] ],
                                                // Lost (5) -> final status, no changes allowed
                                                ['fieldKey'=>'status', 'equal' => ['5'], "options" => ['1', '2', '3', '4', '5'] ],                                            
                                            ],

                                            'isHidden' => [
                                                // Hide sub_status for Fresh (1), Purchased (4) statuses
                                                ['fieldKey'=>'sub_status', 'equal' => ['1', '4'] ],
                                                // Hide purchase fields except for Purchased (4)
                                                ['fieldKey'=>'price_selling', 'not_equal' => ['4'] ],
                                                ['fieldKey'=>'file_doc1', 'not_equal' => ['4'] ],
                                                ['fieldKey'=>'file_doc2', 'not_equal' => ['4'] ],
                                                ['fieldKey'=>'is_exchange', 'not_equal' => ['4'] ],
                                                 // Hide token_amount except for Deal Done (3) and Purchased (4)
                                                ['fieldKey'=>'token_amount', 'not_equal' => ['3', '4','5'] ],
                                                // Hide followup_date for Lost (5)
                                                ['fieldKey'=>'followup_date', 'equal' => ['5'] ],
                                                // Hide evaluation fields except for Follow up (2)
                                                ['fieldKey'=>'evaluation_date', 'equal' => ['1', '2', '3', '4', '5'] ],
                                            ],

                                            'isRequired' => [
                                                // Sub-status required for Follow up (2), Deal Done (3), and Lost (5)
                                                ['fieldKey'=>'sub_status', 'not_equal' => ['1', '4'] ],
                                                // Pricing fields required for Deal Done (3)
                                                ['fieldKey'=>'price_customer', 'equal' => ['3', '4'] ],
                                                ['fieldKey'=>'price_quote', 'equal' => ['3', '4'] ],
                                                ['fieldKey'=>'price_expenses', 'equal' => ['3', '4'] ],
                                                ['fieldKey'=>'price_margin', 'equal' => ['3', '4'] ],
                                                ['fieldKey'=>'price_agreed', 'equal' => ['3', '4'] ],
                                                // Token amount required for Deal Done (3) when sub_status is Token Paid (1) or Token Pending (2)
                                                ['fieldKey'=>'token_amount', 'equal' => ['3', '4'] ],
                                                // Purchase fields required for Purchased (4)
                                                ['fieldKey'=>'price_selling', 'equal' => ['4'] ],
                                                // DISABLED FOR TESTING: ['fieldKey'=>'file_doc1', 'equal' => ['4'] ],
                                                // DISABLED FOR TESTING: ['fieldKey'=>'file_doc2', 'equal' => ['4'] ],
                                                ['fieldKey'=>'is_exchange', 'equal' => ['4'] ],
                                                // Followup date required for Follow up status (2)
                                                ['fieldKey'=>'followup_date', 'equal' => ['2'] ],
                                                // Evaluation place required for Follow up status (2)
                                            ],
                                        ],
                                     
                                    ],
                                    [
                                        "fieldKey" => "sub_status",
                                        'inputType' => 'dynamic_dropdown',
                                        "fieldLabel" => "Sub Status",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "status",
                                        "fieldOptionIds" => [],
                                        "conditionalApply" => [
                                            'copyValue' => [
                                                ['equal' => ['7'], 'fieldKey' => 'evaluation_date', 'copyFieldKey' => 'followup_date'],
                                            ],
                                        ],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Sub Status is required",
                                            "errorMessageInvalid" => "Select a valid Sub Status",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'evaluation_place', 'not_equal' => ['7'] ],
                                                ['fieldKey'=>'evaluation_done', 'not_equal' => ['7'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'evaluation_place', 'equal' => ['7'] ],
                                            ],
                                        ],
                                    
                                    ],
                                    [
                                        "fieldKey" => "lead_classification",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Lead Classification",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['pm_classify'], 'Lead Classification'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alpha'),
                                            "errorMessageRequired" => "Lead classification is required",
                                            "errorMessageInvalid" => "Select a valid lead classification",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "followup_date",
                                        "inputType" => "calender_time",
                                        "calenderType" => "from_current",
                                        "fieldLabel" => "Next follow-up date",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Followup Date & Time is required",
                                            "errorMessageInvalid" => "Select valid Followup Date & Time",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "remarks",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Remarks",
                                        "fieldHolder" => "Enter Remarks",
                                        "tooltip" => "Please enter at least 10 characters.",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Remarks is required",
                                            "errorMessageInvalid" => "Enter Valid Remarks",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "evaluation_place",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Evaluation Place",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['pm_evaluation_place'], 'Evaluation Place'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Evaluation Place is required",
                                            "errorMessageInvalid" => "Select a valid Evaluation Place",
                                        ],
                                    ],
                                

                                ],
                            ],

                            [
                                "sectionId" => "pricing",
                                "sectionId" => "pricing",
                                "sectionTitle" => "Pricing",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "price_indicative",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Indicative Market Price",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Indicative Price is required",
                                            "errorMessageInvalid" => "Enter a valid Indicative Place",
                                        ],
                                    ],    
                                    [
                                        "fieldKey" => "price_customer",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Customer Expected Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Customer Expected Price is required",
                                            "errorMessageInvalid" => "Enter a valid Customer Expected Price",
                                        ],
                                    ],    
                                    [
                                        "fieldKey" => "price_expenses",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Estimated Refurbishment Cost",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Estimated Refurbishment Cost is required",
                                            "errorMessageInvalid" => "Enter a valid Refurbishment Cost",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "price_quote",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Retailer Offered Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Retailer Offered Price is required",
                                            "errorMessageInvalid" => "Enter a valid Retailer Offered Price",
                                        ],
                                    ],    
                                    [
                                        "fieldKey" => "price_margin",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Provisioned Margin",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Provisioned Margin is required",
                                            "errorMessageInvalid" => "Enter a valid Provisioned Margin",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "price_agreed",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Agreed Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Agreed Price is required",
                                            "errorMessageInvalid" => "Enter a valid Agreed Price",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "token_amount",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Token Amount",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Token price is required",
                                            "errorMessageInvalid" => "Enter a valid Token price",
                                        ],
                                    ],
                                      
                                ],
                            ],
                            [
                                "sectionId" => "purchase",
                                "sectionTitle" => "Purchase",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "price_selling",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Final Purchase Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 9,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Final Purchase Price is required",
                                            "errorMessageInvalid" => "Enter a valid Final Purchase Price",
                                        ],
                                    ],    
                                    [
                                        "fieldKey" => "file_doc1",
                                        "inputType" => "file",
                                        "fieldLabel" => "Price Agreement",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "mimeType" => ['images','pdf'],
                                            "errorMessageRequired" => "Price Agreement is required",
                                            "errorMessageInvalid" => "Upload a valid Price Agreement (jpg,jpeg,png,pdf only)",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "file_doc2",
                                        "inputType" => "file",
                                        "fieldLabel" => "RC",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "mimeType" => ['images','pdf'],
                                            "errorMessageRequired" => "RC is required",
                                            "errorMessageInvalid" => "Upload a valid RC (jpg,jpeg,png,pdf only)",
                                        ],
                                    ],
                                    
                                    [
                                        "fieldKey" => "is_exchange",
                                        "inputType" => "radio",
                                        "fieldLabel" => "Exchange",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['is_exchange'], 'Exchange'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "Exchange Option is required",
                                            "errorMessageInvalid" => "Select a valid Exchange Option",
                                        ],
                                    ],

                                ],
                            ],
                          
                        ],
                    ],
                ],
            ],
            'vahanConfig' => (object)[
                // Config properties

                'meta' => [
                    'title' => 'Vahan Details',
                    'dataPath' => 'detail.vahanInfo',
                    'showImages' => false,
                    'showDocuments' => false,
                    'showButtons' => false,
                    'loadedCheckPath' => 'detail.vahanInfo',
                ],

                    // Vehicle Information
                'fields' => [
                        'registrationNumber' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'registrationNumber',
                        'label' => 'Registration Number',
                        'val' => ''
                    ],
                    'registrationDate' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'registrationDate',
                        'label' => 'Registration Date',
                        'format' => 'date',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'registeredAt' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view', 
                        'key' => 'registeredAt',
                        'label' => 'Registered At',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'vehicleClassDescription' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'vehicleClassDescription', 
                        'label' => 'Vehicle Class',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'vehicleCatgory' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'vehicleCatgory',
                        'label' => 'Vehicle Category',
                        'defaultValue' => 'Not Available', 
                        'val' => ''
                    ],
                    'bodyTypeDescription' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'bodyTypeDescription',
                        'label' => 'Body Type',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'makerModel' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'makerModel',
                        'label' => 'Make/Model',
                        'val' => ''
                    ],
                    'makerDescription' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'makerDescription',
                        'label' => 'Maker Description',
                        'val' => ''
                    ],
                    'color' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'color',
                        'label' => 'Color',
                        'val' => ''
                    ],
                    'manufacturedMonthYear' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'manufacturedMonthYear',
                        'label' => 'Manufactured',
                        'val' => ''
                    ],
                    'rcStatus' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'rcStatus',
                        'label' => 'RC Status',
                        'val' => ''
                    ],
                    'stautsMessage' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'stautsMessage',
                        'label' => 'Status Message',
                        'val' => ''
                    ],
                    'statusAsOn' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'statusAsOn',
                        'label' => 'Status As On',
                        'format' => 'date',
                        'val' => ''
                    ],

                    // Technical Specifications
                    'chassisNumber' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'chassisNumber',
                        'label' => 'Chassis Number',
                        'val' => ''
                    ],
                    'engineNumber' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'engineNumber',
                        'label' => 'Engine Number',
                        'val' => ''
                    ],
                    'fuelDescription' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'fuelDescription',
                        'label' => 'Fuel Type',
                        'val' => ''
                    ],
                    'cubicCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'cubicCapacity',
                        'label' => 'Cubic Capacity',
                        'val' => '',
                        'suffix' => ' CC'
                    ],
                    'numberOfCylinders' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'numberOfCylinders',
                        'label' => 'Number of Cylinders',
                        'val' => ''
                    ],
                    'seatingCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'seatingCapacity',
                        'label' => 'Seating Capacity',
                        'val' => ''
                    ],
                    'standingCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'standingCapacity',
                        'label' => 'Standing Capacity',
                        'val' => ''
                    ],
                    'sleeperCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'sleeperCapacity',
                        'label' => 'Sleeper Capacity',
                        'val' => ''
                    ],
                    'grossVehicleWeight' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'grossVehicleWeight',
                        'label' => 'Gross Vehicle Weight',
                        'val' => '',
                        'suffix' => ' Kg'
                    ],
                    'unladenWeight' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'unladenWeight',
                        'label' => 'Unladen Weight',
                        'val' => '',
                        'suffix' => ' Kg'
                    ],
                    'wheelbase' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'wheelbase',
                        'label' => 'Wheelbase',
                        'val' => ''
                    ],
                    'normsDescription' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'normsDescription',
                        'label' => 'Norms Description',
                        'val' => ''
                    ],

                    // Owner Information
                    'ownerName' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'ownerName',
                        'label' => 'Owner Name',
                        'val' => ''
                    ],
                    'ownerSerialNumber' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'ownerSerialNumber',
                        'label' => 'Owner Serial Number',
                        'val' => ''
                    ],
                    'fatherName' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'fatherName',
                        'label' => 'Father Name',
                        'val' => ''
                    ],
                    'permanentAddress' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'permanentAddress',
                        'label' => 'Permanent Address',
                        'val' => ''
                    ],
                    'presentAddress' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'presentAddress',
                        'label' => 'Present Address',
                        'val' => ''
                    ],
                    'rcMobileNo' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'rcMobileNo',
                        'label' => 'RC Mobile Number',
                        'val' => ''
                    ],

                    // Insurance Information
                    'insuranceCompany' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'insuranceCompany',
                        'label' => 'Insurance Company',
                        'val' => ''
                    ],
                    'insurancePolicyNumber' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'insurancePolicyNumber',
                        'label' => 'Insurance Policy Number',
                        'val' => ''
                    ],
                    'insuranceUpto' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'insuranceUpto',
                        'label' => 'Insurance Valid Until',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'financier' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'financier',
                        'label' => 'Financier',
                        'val' => ''
                    ],

                    // Permits and Compliance
                    'fitnessUpto' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'fitnessUpto',
                        'label' => 'Fitness Valid Until',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'taxPaidUpto' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'taxPaidUpto',
                        'label' => 'Tax Paid Until',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'pucNumber' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'pucNumber',
                        'label' => 'PUC Number',
                        'val' => ''
                    ],
                    'pucExpiryDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'pucExpiryDate',
                        'label' => 'PUC Expiry Date',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'nationalPermitNumber' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nationalPermitNumber',
                        'label' => 'National Permit Number',
                        'val' => ''
                    ],
                    'nationalPermitExpiryDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nationalPermitExpiryDate',
                        'label' => 'National Permit Expiry',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'nationalPermitIssuedBy' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nationalPermitIssuedBy',
                        'label' => 'National Permit Issued By',
                        'val' => ''
                    ],
                    'statePermitNumber' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitNumber',
                        'label' => 'State Permit Number',
                        'val' => ''
                    ],
                    'statePermitType' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitType',
                        'label' => 'State Permit Type',
                        'val' => ''
                    ],
                    'statePermitIssuedDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitIssuedDate',
                        'label' => 'State Permit Issued Date',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'statePermitExpiryDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitExpiryDate',
                        'label' => 'State Permit Expiry',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'blackListStatus' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'blackListStatus',
                        'label' => 'Black List Status',
                        'val' => ''
                    ],
                    'nocDetails' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nocDetails',
                        'label' => 'NOC Details',
                        'val' => ''
                    ],
                    'nonUseFrom' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nonUseFrom',
                        'label' => 'Non Use From',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'nonUseTo' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nonUseTo',
                        'label' => 'Non Use To',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'rcNonUseStatus' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'rcNonUseStatus',
                        'label' => 'RC Non Use Status',
                        'val' => ''
                    ],
                    'stateCd' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'stateCd',
                        'label' => 'State Code',
                        'val' => ''
                    ]
             ]
            ]
          

        ];
        return  $data;
    }


    private function stock()
    {
        GLOBAL $config;
        
        $data =  [
            'menu' => $this->getConfig('my-stock', 'menu'),
            'sidebar' => (object)[
                'showSidebar' => true
            ],
            'detail' => (object)[
                'showSidebar' => true, 'sidebarItems' => []
            ],
            'grid' => (object)[
                'title' => "My Stock",
                'pagination' => (object)[
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'start_count' => 0,
                    'end_count' => 0, 
                    'perPageOptions' => [10, 25, 50, 100]
                ],
                'list' => (array)[],
                'header' => (array)[
                    //  [
                    //     'type'=>'button',
                    //     'label' => "Export",
                    //     'icon' => "file-earmark-spreadsheet",
                    //     'validation' => ['show' => true, 'disabled' => false],
                    //     'class' => "btn-outline-dark",
                    //     'conditional' => [
                    //         'onclick' =>[
                    //             'meta' => ['key' => 'export', 'type'=>'get', 'action' => "exportData"],
                    //         ],
                    //     ]
                    // ]
                ],

                'searchConfig' => (object)[
                        'fields' => [                           
                            [
                                'fieldKey' => 'id',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Stock ID',
                                'fieldHolder' => 'Enter Stock ID',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 10,
                                'validation' => [
                                    'validationPattern' => get_field_regex('alphanumeric'),
                                    'errorMessageRequired' => 'Inventory ID is required',
                                    'errorMessageInvalid' => 'Enter Valid Inventory ID (letters and numbers only)',
                                ],
                            ],
                            [
                                'fieldKey' => 'reg_num',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Registration Number',
                                'fieldHolder' => 'Enter Registration Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 100,
                                'isCaps'=>true,
                                'validation' => [
                                    'validationPattern' => get_field_regex('reg_num'),
                                    'errorMessageRequired' => 'RegNo is required',
                                    'errorMessageInvalid' => 'Enter Valid RegNo',
                                ],
                            ],
                            [
                                'fieldKey' => 'chassis',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => ' VIN / Chassis Number',
                                'fieldHolder' => 'Enter VIN / Chassis Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 100,
                                'isCaps'=>true,
                                'validation' => [
                                    'validationPattern' => get_field_regex('reg_num'),
                                    'errorMessageRequired' => 'Chassis is required',
                                    'errorMessageInvalid' => 'Enter Valid Chassis',
                                ],
                            ],
                            [
                                'fieldKey' => 'make',
                                'inputType' => 'dynamic_dropdown',
                                "inputChange" => ["dynamic_models"],
                                'fieldLabel' => 'Make',
                                'fieldHolder' => 'Make',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                "isSearch" => true,
                                "isGroup" => true,
                                'defaultInputValue' => '',
                                'value' => '',
                                "fieldOptions" => $this->buildOptions($this->commonConfig['makes'] ?? [], 'Makes'),
                                "clearFields" => ["model"],
                                'validation' => [
                                    "validationPattern" => get_field_regex('numeric'),
                                    'errorMessageRequired' => 'Make is required',
                                    'errorMessageInvalid' => 'Select Valid make',
                                ],
                            ],
                            [
                                'fieldKey' => 'model',
                                'inputType' => 'dynamic_dropdown',
                                "inputMethod" => "",
                                'fieldLabel' => 'Model',
                                'fieldHolder' => 'Model',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                "fieldOptions" => $this->buildOptions($this->commonConfig['models'] ?? [], 'Models'),
                                'validation' => [
                                    "validationPattern" => get_field_regex('numeric'),
                                    'errorMessageRequired' => 'Model is required',
                                    'errorMessageInvalid' => 'Select Valid model',
                                ],
                            ],
                        ],
                    ],


               'columns' => [
                    [
                       'title'=> 'Images',
                       'data'=> [
                           ['key' => ['id'], 'ref' => ['images'], 'label' => '', 'type' => 'image' ],
                       ]
                    ],
                    [
                        'title'=> 'Stock Details',
                        'data'=> [
                            ['key' => ['formatted_id'], 'label' => 'Stock ID', 'type' => 'text', 'class' => 'fw-bold'  ],
                            ['key' => ['added_on'], 'label' => 'Created', 'type' => 'date' ],
                            ['key' => ['updated_on'], 'label' => 'Updated', 'type' => 'date' ],
                            ['key' => ['executive_name'], 'icon'=>'person-fill-gear', 'attachKey'=>'executive', 'label' => 'Executive', 'type' => 'attach', 'role_main' => 'y' ],
                        ]
                    ],                    
                    [
                        'title'=> 'Vehicle Details',
                        'data'=> [
                            ['key' => ['make_name', 'model_name', 'variant_name'], 'label' => '', 'type' => 'concat', 'class' => 'fw-bold' ],
                            ['key' => ['car_type_name'], 'label' => 'CarType', 'type' => 'text' ],
                            ['key' => ['chassis'], 'label' => 'Chassis No', 'type' => 'text' ],
                            ['key' => ['reg_num'], 'label' => 'Reg No', 'type' => 'text' ],
                            ['key' => ['reg_date'], 'label' => 'Reg Date', 'type' => 'date' ],
                            ['key' => ['reg_type_name'], 'label' => 'Reg Type', 'type' => 'text' ],
                            ['key' => ['mfg_month_name','mfg_year'], 'label' => 'Mfg Year & Month', 'type' => 'text' ],
                            ['key' => ['certification_type_name'], 'label' => 'Certification Type', 'type' => 'text' ],
                            ['key' => ['listing_price'], 'label' => 'Listing Price', 'type' => 'numeric_format', 'val' => '' ],
                        ]
                    ],
                    [
                        'title'=> 'Status',
                        'data'=> [
                            ['key' => [], 'icon'=>'clock-history', 'attachKey'=>'history', 'label' => '', 'type' => 'attach', 'tooltip' => 'Status History' ],
                            ['key' => ['status_name'], 'label' => '', 'type' => 'badge', 'class'=>['1'=>'bg-success','1'=>'bg-secondary'] ],
                        ]
                    ],
                    [
                        'title'=> 'Actions',
                        'data'=> [
                            [
                                'label' => 'View',
                                'type' => 'link',
                                'class' => "btn-outline-dark",
                                'icon' => "eye",
                                'meta' => ['type'=>'route', 'action'=>'detail/:id']
                            ],
                        ]
                    ]
                ],
            ],

            'overview'=>(object)[
                // Config properties
                'meta' => [
                    'title' => 'Stock Overview',
                    'dataPath' => 'detail',
                    'showImages' => true,
                    'showDocuments' => true,
                    'showButtons' => true,
                    'loadedCheckPath' => 'detail',
                ],

                // Stock Info
                'fields' => [
                    'id' => [ 'category' => 'Stock Info', 
                            'type' => 'view',
                            'key' => 'formatted_id',
                            'label' => 'ID', 
                            'val' => '',
                            ],
                    'status' => [ 'category' => 'Stock Info', 'label' => 'Status', 'key' => 'status_name', 'type' => 'view',  'val' => '', ],

                    // Vehicle Details
                    'car_type' => [ 'category' => 'Vehicle Details', 'label' => 'Car Type', 'key' => 'car_type_name', 'type' => 'view', 'val' => '' ],
                    'source_other' => [ 'category' => 'Vehicle Details', 'label' => 'Vehicle Source', 'key' => 'source_other_name', 'type' => 'view', 'val' => '' ],
                    'reg_type' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Type', 'key' => 'reg_type_name', 'type' => 'view', 'val' => '' ],
                    'reg_num' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Number', 'key' => 'reg_num', 'type' => 'view',  'val' => '', ],
                    'reg_date' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Date', 'key' => 'reg_date', 'type' => 'date',  'val' => '', ],
                    'mfg_year' => [ 'category' => 'Vehicle Details', 'label' => 'Manufacture Year', 'key' => 'mfg_year', 'type' => 'view',  'val' => '', ],
                    'mfg_month' => [ 'category' => 'Vehicle Details', 'label' => 'Manufacture Month', 'key' => 'mfg_month_name', 'type' => 'view',  'val' => '', ],
                    'make' => [ 'category' => 'Vehicle Details', 'label' => 'Make', 'key' => 'make_name', 'type' => 'view',  'val' => '', ],
                    'model' => [ 'category' => 'Vehicle Details', 'label' => 'Model', 'key' => 'model_name', 'type' => 'view',  'val' => '', ],
                    'variant' => [ 'category' => 'Vehicle Details', 'label' => 'Variant', 'key' => 'variant_name', 'type' => 'view',  'val' => '', ],
                    'chassis' => [ 'category' => 'Vehicle Details', 'label' => 'Chassis Number', 'key' => 'chassis', 'type' => 'view',  'val' => '', ],
                    'transmission' => [ 'category' => 'Vehicle Details', 'label' => 'Transmission', 'key' => 'transmission_name', 'type' => 'view', 'val' => '' ],
                    'mileage' => [ 'category' => 'Vehicle Details', 'label' => 'Mileage', 'key' => 'mileage', 'type' => 'view',  'val' => '', ],
                    'fuel' => [ 'category' => 'Vehicle Details', 'label' => 'Fuel Type', 'key' => 'fuel_name', 'type' => 'view', 'val' => '' ],
                    'color' => [ 'category' => 'Vehicle Details', 'label' => 'Exterior Color', 'key' => 'color_name', 'type' => 'view', 'val' => '' ],
                    'base_color' => [ 'category' => 'Vehicle Details', 'label' => 'Base Color', 'key' => 'base_color_name', 'type' => 'view', 'val' => '' ],
                    'interior_color' => [ 'category' => 'Vehicle Details', 'label' => 'Interior Color', 'key' => 'interior_color_name', 'type' => 'view', 'val' => '' ],
                    'interior_base_color' => [ 'category' => 'Vehicle Details', 'label' => 'Interior Base Color', 'key' => 'interior_base_color', 'type' => 'view', 'val' => '' ],
                    'owners' => [ 'category' => 'Vehicle Details', 'label' => 'No. of Owners', 'key' => 'owners_name', 'type' => 'view', 'val' => '' ],
                    'hypothecation' => [ 'category' => 'Vehicle Details', 'label' => 'Hypothecation', 'key' => 'hypothecation_name', 'type' => 'view', 'val' => '' ],
                    'insurance'=> [ 'category' => 'Vehicle Details', 'label' => 'Insurance', 'key' => 'insurance_type_name', 'type' => 'view', 'val' => '' ],
                    'insurance_exp_date' => [ 'category' => 'Vehicle Details', 'label' => 'Insurance Expiry Date', 'key' => 'insurance_exp_date', 'type' => 'view',  'val' => '', ],

                    // Purchase Details
                    'full_name' => [ 'category' => 'Purchase Details', 'label' => 'Customer', 'key' => 'title,first_name,last_name', 'type' => 'view',  'val' => '', ],
                    'mobile' => [ 'category' => 'Purchase Details', 'label' => 'Mobile', 'key' => 'mobile', 'type' => 'view',  'val' => '', ],
                    'email' => [ 'category' => 'Purchase Details', 'label' => 'Email', 'key' => 'email', 'type' => 'view',  'val' => '', ],
                    'executive' => [ 'category' => 'Purchase Details', 'label' => 'Purchase Executive', 'key' => 'user_name', 'type' => 'view',  'val' => '', ],
                    'source' => [ 'category' => 'Purchase Details', 'label' => 'Source', 'key' => 'source_name', 'type' => 'view',  'val' => '', ],
                    'source_sub' => [ 'category' => 'Purchase Details', 'label' => 'Sub Source', 'key' => 'source_sub_name', 'type' => 'view',  'val' => '', ], 
                    'state' => [ 'category' => 'Purchase Details', 'label' => 'State', 'key' => 'state_name', 'type' => 'view',  'val' => '', ],
                    'city' => [ 'category' => 'Purchase Details', 'label' => 'City', 'key' => 'city_name', 'type' => 'view',  'val' => '', ],
                    'address' => [ 'category' => 'Purchase Details', 'label' => 'Address', 'key' => 'address', 'type' => 'view',  'val' => '', ],
                    'pin_code' => [ 'category' => 'Purchase Details', 'label' => 'Pin Code', 'key' => 'pin_code', 'type' => 'view',  'val' => '', ],
                    
                    // Dates Info
                    // 'followup_date' => [ 'category' => 'Dates Info', 'label' => 'Follow-up Date', 'key' => 'followup_date', 'type' => 'view',  'val' => '', ],
                    // 'evaluation_date' => [ 'category' => 'Dates Info', 'label' => 'Evaluation Date', 'key' => 'evaluation_date', 'type' => 'view',  'val' => '', ],
                    // 'created' => [ 'category' => 'Dates Info', 'label' => 'Created Date', 'key' => 'created', 'type' => 'view',  'val' => '', ],
                    // 'updated' => [ 'category' => 'Dates Info', 'label' => 'Updated Date', 'key' => 'updated', 'type' => 'view',  'val' => '', ],

                    // Media (used by dedicated sections, not displayed as fields)
                    'images' => [ 'category' => 'Images', 'label' => 'Images', 'key' => 'images', 'type' => 'media',  'val' => '', ],
                    'documents' => [ 'category' => 'Documents', 'label' => 'Documents', 'key' => 'documents', 'type' => 'media',  'val' => '', ],
                ]
            ],


            'images'=> (object)[

              'front' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Front straight shot",
                    "fieldKey"=> "front",
                    "isRequired"=> true,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "front",
                        "imgName"=> "Front straight shot",
                        "imgMand"=> "y",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/front.png"
                    ]
                ],

                'rhs' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "RHS shot",
                    "fieldKey"=> "rhs",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rhs",
                        "imgName"=> "RHS shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rhs.png"
                    ]
                ],

               'rhs-ang' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "RHS angular shot",
                    "fieldKey"=> "rhs-ang",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rhs-ang",
                        "imgName"=> "RHS angular shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rhs-ang.png"
                    ]
                ],

                'rear' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Rear straight shot",
                    "fieldKey"=> "rear",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rear",
                        "imgName"=> "Rear straight shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "{'X':[6,99,-4,4],'Y':[-3,4,1,99],'Z':[-1,6,-1,99]}",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rear.png"
                    ]
                ],

                 'lhs' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "LHS shot",
                    "fieldKey"=> "lhs",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "lhs",
                        "imgName"=> "LHS shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/lhs.png"
                    ]
                ],

               'lhs-ang' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "LHS angular shot",
                    "fieldKey"=> "lhs-ang",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "lhs-ang",
                        "imgName"=> "LHS angular shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/lhs-ang.png"
                    ]
                ],

               'windshield-int' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Interior windshield",
                    "fieldKey"=> "windshield-int",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "windshield-int",
                        "imgName"=> "Interior windshield",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/windshield.png"
                    ]
                ],

               'rear-door' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Rear Door open Shot",
                    "fieldKey"=> "rear-door",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rear-door",
                        "imgName"=> "Rear Door open Shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rear-door.png"
                    ]
                ],

               'frhs-door' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "FRHS door open shot",
                    "fieldKey"=> "frhs-door",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "frhs-door",
                        "imgName"=> "FRHS door open shot",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/frhs-door.png"
                    ]
                ],

               'speedometer' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Tachometer/Speedometer",
                    "fieldKey"=> "speedometer",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "speedometer",
                        "imgName"=> "Tachometer/Speedometer",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/speedometer.png"
                    ]
                ],

               'roof' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Sun/moon/Roof-top",
                    "fieldKey"=> "roof",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "roof",
                        "imgName"=> "Sun/moon/Roof-top",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/roof.png"
                    ]
                ],

                'dashboard' =>  [
                      "inputType"=> "car_img",
                      "fieldLabel"=> "Infotainment console",
                      "fieldKey"=> "dashboard",
                      "isRequired"=> false,
                      'src' => "",
                      'file' => null,
                      'isUploading' => false, 
                      'queueStatus' => 'idle',
                      "imgPart"=> (object)[
                            "imgId"=> "",
                            "imgSno"=> "dashboard",
                            "imgName"=> "Infotainment console",
                            "imgMand"=> "n",
                            "imgLogo"=> "",
                            "imgOrientation"=> "L",
                            "imgAction"=> "add",
                            "ImgEdit"=> "No",
                            "imgLat"=> "",
                            "imgLong"=> "",
                            "imgTime"=> "",
                            "imgFile"=> "",
                            "imgFlag"=> "0",
                            "imgPath"=> "",
                            "imgData"=> "",
                            "imgSubData"=> "",
                            "imgAngle"=> "",
                            "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/dashboard.png"
                      ]
                 ],

               'steering' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Steering control buttons",
                    "fieldKey"=> "steering",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "steering",
                        "imgName"=> "Steering control buttons",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/steering.png"
                    ]
                ],

               'start-btn' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Smart start button",
                    "fieldKey"=> "start-btn",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "start-btn",
                        "imgName"=> "Smart start button",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/start-btn.png"
                    ]
                ],


                'img-1' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 1",
                    "fieldKey"=> "img-1",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-1",
                        "imgName"=> "Other Image 1",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-2' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 2",
                    "fieldKey"=> "img-2",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-2",
                        "imgName"=> "Other Image 2",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                
                'img-3' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 3",
                    "fieldKey"=> "img-3",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-3",
                        "imgName"=> "Other Image 3",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-4' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 4",
                    "fieldKey"=> "img-4",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-4",
                        "imgName"=> "Other Image 4",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-5' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 5",
                    "fieldKey"=> "img-5",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-5",
                        "imgName"=> "Other Image 5",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-6' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 6",
                    "fieldKey"=> "img-6",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-6",
                        "imgName"=> "Other Image 6",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-7' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 7",
                    "fieldKey"=> "img-7",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-7",
                        "imgName"=> "Other Image 7",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'img-8' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 8",
                    "fieldKey"=> "img-8",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-8",
                        "imgName"=> "Other Image 8",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],
                 'img-9' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 9",
                    "fieldKey"=> "img-9",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-9",
                        "imgName"=> "Other Image 9",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],
                 'img-10' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 10",
                    "fieldKey"=> "img-10",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-10",
                        "imgName"=> "Other Image 10",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],
                 'img-11' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Other Image 11",
                    "fieldKey"=> "img-11",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "img-11",
                        "imgName"=> "Other Image 11",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/other.png"
                    ]
                ],

                'pedals' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Pedals Photo",
                    "fieldKey"=> "pedals",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "pedals",
                        "imgName"=> "Pedals Photo",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/pedals.png"
                    ]
                ],

               'key' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Car Key Photo",
                    "fieldKey"=> "key",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "key",
                        "imgName"=> "Car Key Photo",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/key.png"
                    ]
                ],

               'rc' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "RC Book / Chassis No.Plate",
                    "fieldKey"=> "rc",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "rc",
                        "imgName"=> "RC Book / Chassis No.Plate",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/rc.png"
                    ]
                ],

               'insurance' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "insurance copy",
                    "fieldKey"=> "insurance",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "insurance",
                        "imgName"=> "insurance copy",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/insurance-copy.png"
                    ]
                ],

               'chassis' =>  [
                    "inputType"=> "car_img",
                    "fieldLabel"=> "Chassis Print Embossing",
                    "fieldKey"=> "chassis",
                    "isRequired"=> false,
                    'src' => "",
                    'file' => null,
                    'isUploading' => false, 
                    'queueStatus' => 'idle',
                    "imgPart"=> (object)[
                        "imgId"=> "",
                        "imgSno"=> "chassis",
                        "imgName"=> "Chassis Print Embossing",
                        "imgMand"=> "n",
                        "imgLogo"=> "",
                        "imgOrientation"=> "L",
                        "imgAction"=> "add",
                        "ImgEdit"=> "No",
                        "imgLat"=> "",
                        "imgLong"=> "",
                        "imgTime"=> "",
                        "imgFile"=> "",
                        "imgFlag"=> "0",
                        "imgPath"=> "",
                        "imgData"=> "",
                        "imgSubData"=> "",
                        "imgAngle"=> "",
                        "imgOverlayLogo"=> $config['base_url']."/assets/images/image-thumbs/chassis.png"
                    ]
                ],
            ],

           'history' => [
                [
                    'title'=> 'Updated Details',
                    'data'=> [                        
                        ['key' => ['created'], 'label' => 'Updated Date', 'type' => 'date' ],
                        ['key' => ['created_by'], 'label' => 'Updated By', 'type' => 'text' ],
                    ]
                ],
                [
                    'title'=> 'Status Details',
                    'data'=> [
                        ['key' => ['status_name'], 'label' => 'Status', 'type' => 'badge' ],
                        ['key' => ['listing_price'], 'label' => 'Listing Price', 'type' => 'numeric_format' ],
                        ['key' => ['action_type'], 'label' => 'Action Type', 'type' => 'text' ],
                        ['key' => ['refurb_name'], 'label' => 'Refurbishement Type', 'type' => 'text' ],
                        ['key' => ['refurb_date'], 'label' => 'Refurbishment Date', 'type' => 'text' ],
                        ['key' => ['remarks'], 'label' => 'Remarks', 'type' => 'text' ],
                    ]
                ],
                [
                    'title'=> 'Certification Details',
                    'data'=> [
                        ['key' => ['certified_by'], 'label' => 'Certified By', 'type' => 'text' ],
                        ['key' => ['certification_date'], 'label' => 'Certification Date', 'type' => 'text' ],
                        ['key' => ['booked_date'], 'label' => 'Booked Date', 'type' => 'text' ],
                        ['key' => ['sold_date'], 'label' => 'Sold Date', 'type' => 'text' ],
                        ['key' => ['certification_remarks'], 'label' => 'Certification Remarks', 'type' => 'text' ],

                    ]
                ],
            ],


            

           'status' => (object)[
                // 1 => [
                //     'label' => 'Fresh',
                //     'eligible_statuses' => [2, 3, 7],
                //     'fields' => [
                //         [
                //             'name' => 'lead_classification',
                //             'label' => 'Lead Classification',
                //             'type' => 'select',
                //             'options' => ['Hot', 'Warm', 'Cold'],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('field_name'),
                //                 'msg' => 'Required Lead Classification field'
                //             ]
                //         ],
                //         [
                //             'name' => 'followup_datetime',
                //             'label' => 'Followup Date & Time',
                //             'type' => 'datetime-local',
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('followup_datetime'),
                //                 'msg' => 'Required Followup Date & Time field'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Remarks',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ]
                //     ]
                // ],
                // 2 => [
                //     'label' => 'Followup',
                //     'eligible_statuses' => [3, 7],
                //     'fields' => [
                //         [
                //             'name' => 'sub_status',
                //             'label' => 'Followup Status',
                //             'type' => 'select',
                //             'options' => $this->buildOptions($this->commonConfig['lead_statuses'][2]['sub_statuses'], 'Select Followup Status'),
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('is_numeric'),
                //                 'msg' => 'Required Followup Status field'
                //             ]
                //         ],
                //         [
                //             'name' => 'lead_classification',
                //             'label' => 'Lead Classification',
                //             'type' => 'select',
                //             'options' => ['Hot', 'Warm', 'Cold'],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('field_name'),
                //                 'msg' => 'Required Lead Classification field'
                //             ]
                //         ],
                //         [
                //             'name' => 'followup_datetime',
                //             'label' => 'Followup Date & Time',
                //             'type' => 'datetime-local',
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('followup_datetime'),
                //                 'msg' => 'Required Followup Date & Time field'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Remarks',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ]
                //     ]
                // ],
                // 3 => [
                //     'label' => 'Evaluation',
                //     'eligible_statuses' => [7],
                //     'sub_status' => [
                //         1 => 'Today',
                //         2 => 'Upcoming',
                //         3 => 'Overdue',
                //     ],
                //     'fields' => [
                //         [
                //             'name' => 'sub_status',
                //             'label' => 'Evaluation Status',
                //             'type' => 'select',
                //             'options' => $this->buildOptions($this->commonConfig['lead_statuses'][3]['sub_statuses'], 'Select Evaluation Status'),
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('is_numeric'),
                //                 'msg' => 'Required Evaluation Status field'
                //             ]
                //         ],
                //         [
                //             'name' => 'lead_classification',
                //             'label' => 'Lead Classification',
                //             'type' => 'select',
                //             'options' => ['Hot', 'Warm', 'Cold'],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('field_name'),
                //                 'msg' => 'Required Lead Classification field'
                //             ]
                //         ],
                //         [
                //             'name' => 'followup_datetime',
                //             'label' => 'Followup Date & Time',
                //             'type' => 'datetime-local',
                //             'value' => '',
                //             'placeholder' => 'Select date and time',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('followup_datetime'),
                //                 'msg' => 'Required Followup Date & Time field'
                //             ]
                //         ],
                //         [
                //             'name' => 'evaluation_place',
                //             'label' => 'Evaluation Place',
                //             'type' => 'select',
                //             'options' => [
                //                 ['value' => '1', 'label' => 'Showroom'],
                //                 ['value' => '2', 'label' => 'Field'],
                //             ],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                'regex'=>get_field_regex('is_numeric'),
                //                 'msg' => 'Required Evaluation Place field'
                //             ]
                //         ],
                //         [
                //             'name' => 'evaluation_type',
                //             'label' => 'Evaluation Type',
                //             'type' => 'select',
                //             'options' =>  $this->buildOptions($this->commonConfig['lead_statuses'][3]['evaluation_type'], 'Select Evaluation Type'),
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('is_numeric'),
                //                 'msg' => 'Required Evaluation Type field'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'placeholder' => 'Enter Remarks',
                //             'value' => '',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ],
                //     ]
                // ],
                // 4 => [
                //     'label' => 'Evaluated',
                //     'eligible_statuses' => [5, 7],
                //     'fields' => [
                //         [
                //             'name' => 'lead_classification',
                //             'label' => 'Lead Classification',
                //             'type' => 'select',
                //             'options' => ['Hot', 'Warm', 'Cold'],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('field_name'),
                //                 'msg' => 'Required Lead Classification field'
                //             ]
                //         ],
                //         [
                //             'name' => 'followup_datetime',
                //             'label' => 'Followup Date & Time',
                //             'type' => 'datetime-local',
                //             'value' => '',
                //             'placeholder' => 'Select date and time',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('followup_datetime'),
                //                 'msg' => 'Required Followup Date & Time field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_quote',
                //             'label' => 'Price Quote',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Price Quote',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('numeric'),
                //                 'msg' => 'Required Price Quote'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_customer',
                //             'label' => 'Price Customer',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Customer Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Customer'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_selling',
                //             'label' => 'Price Selling',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Selling Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Selling'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_indicative',
                //             'label' => 'Price Indicative',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Indicative Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Indicative'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Remarks',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ],
                //     ]
                // ],
                // 5 => [
                //     'label' => 'Deal Done',
                //     'eligible_statuses' => [6, 7],
                //     'fields' => [
                //         [
                //             'name' => 'lead_classification',
                //             'label' => 'Lead Classification',
                //             'type' => 'select',
                //             'options' => ['Hot', 'Warm', 'Cold'],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('field_name'),
                //                 'msg' => 'Required Lead Classification field'
                //             ]
                //         ],
                //         [
                //             'name' => 'followup_datetime',
                //             'label' => 'Followup Date & Time',
                //             'type' => 'datetime-local',
                //             'value' => '',
                //             'placeholder' => 'Select date and time',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('followup_datetime'),
                //                 'msg' => 'Required Followup Date & Time field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_quote',
                //             'label' => 'Price Quote',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Price Quote',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Quote field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_customer',
                //             'label' => 'Price Customer',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Customer Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Customer field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_selling',
                //             'label' => 'Price Selling',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Selling Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Selling field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_expenses',
                //             'label' => 'Price Expenses',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Expenses Price',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Expenses field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_indicative',
                //             'label' => 'Price Indicative',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Indicative Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Indicative field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_agreement_doc',
                //             'label' => 'Price Agreement',
                //             'type' => 'file',
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => '', 
                //                 'msg' => 'Price Agreement document is required'
                //             ]
                //         ],
                //         [
                //             'name' => 'relationship_proof_doc',
                //             'label' => 'Relationship Proof',
                //             'type' => 'file',
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => '', 
                //                 'msg' => 'Relationship Proof document is required'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Remarks',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ],
                //         [
                //             'name' => 'is_exchange',
                //             'label' => 'Is Exchange',
                //             'type' => 'radio',
                //             'options' => [
                //                 ['value' => '1', 'label' => 'Yes'],
                //                 ['value' => '0', 'label' => 'No'],
                //             ],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => '', 
                //                 'msg' => 'Please select if this is an Exchange deal'
                //             ]
                //         ],
                //     ]
                // ],
                // 6 => [
                //     'label' => 'Purchased',
                //     'eligible_statuses' => [7],
                //     'fields' => [
                //         [
                //             'name' => 'lead_classification',
                //             'label' => 'Lead Classification',
                //             'type' => 'select',
                //             'options' => ['Hot', 'Warm', 'Cold'],
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('field_name'),
                //                 'msg' => 'Required Lead Classification field'
                //             ]
                //         ],
                //         [
                //             'name' => 'followup_datetime',
                //             'label' => 'Followup Date & Time',
                //             'type' => 'datetime-local',
                //             'value' => '',
                //             'placeholder' => 'Select date and time',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('followup_datetime'),
                //                 'msg' => 'Required Followup Date & Time field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_quote',
                //             'label' => 'Price Quote',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Price Quote',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Quote field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_customer',
                //             'label' => 'Price Customer',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Customer Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Customer field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_selling',
                //             'label' => 'Price Selling',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Selling Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Selling field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_expenses',
                //             'label' => 'Price Expenses',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Expenses Price',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Expenses field'
                //             ]
                //         ],
                //         [
                //             'name' => 'price_indicative',
                //             'label' => 'Price Indicative',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Indicative Price',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => get_field_regex('numeric'),
                //                 'msg' => 'Required Price Indicative field'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Remarks',
                //             'validation' => [
                //                 'required' => false,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ],
                //     ]
                // ],
                // 7 => [
                //     'label' => 'Lost',
                //     'eligible_statuses' => [],
                //     'fields' => [
                //         [
                //             'name' => 'sub_status',
                //             'label' => 'Lost Reason',
                //             'type' => 'select',
                //             'options' => $this->buildOptions($this->commonConfig['lead_statuses'][7]['sub_statuses'], 'Select Lost Reason'),
                //             'value' => '',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex'=>get_field_regex('is_numeric'),
                //                 'msg' => 'Required Evaluation Status field'
                //             ]
                //         ],
                //         [
                //             'name' => 'remarks',
                //             'label' => 'Remarks',
                //             'type' => 'text',
                //             'value' => '',
                //             'placeholder' => 'Enter Remarks',
                //             'validation' => [
                //                 'required' => true,
                //                 'visible' => true,
                //                 'regex' => '',
                //                 'msg' => 'Invalid Remarks field'
                //             ]
                //         ]
                //     ]
                // ]
            ],

            'addConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "My Stock",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "stock-details",
                                "sectionTitle" => "Stock Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "branch",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_executies",
                                        "fieldLabel" => "Branch",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                                        "clearFields" => ["executive"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Branch is required",
                                            "errorMessageInvalid" => "Please select a valid Branch",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "executive",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_variants",
                                        "fieldLabel" => "Executive",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['executive'] ?? [], 'Executive'),
                                        "dependsOn" => "branch",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Executive is required",
                                            "errorMessageInvalid" => "Please select a valid model from the list",
                                        ],
                                    ],
                                ]
                            ],
                            // [
                            //     "sectionId" => "customer_details",
                            //     "sectionTitle" => "Customer Details",
                            //     "isExpandedByDefault" => true,
                            //     "fields" => [
                            //         [
                            //             "fieldKey" => "title",
                            //             "inputType" => "dropdownIds",
                            //             "fieldLabel" => "Salutation",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "",
                            //             "value" => "",
                            //             "fieldOptionIds" => $this->buildOptions($this->commonConfig['title'], 'Salutation'),
                            //             "validation" => [
                            //                 "validationPattern" => get_field_regex('title'),
                            //                 "errorMessageRequired" => "Please select valid Salutation",
                            //                 "errorMessageInvalid" => "Select Valid Salutation",
                            //             ],
                            //         ],
                            //         [
                            //             "fieldKey" => "first_name",
                            //             "inputType" => "alphanumeric",
                            //             "fieldLabel" => "First Name",
                            //             "fieldHolder" => "Enter First Name",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "",
                            //             "value" => "",
                            //             "maxLength" => 50,
                            //             "validation" => [
                            //                 "validationPattern" => get_field_regex('name'),
                            //                 "errorMessageRequired" => "First Name is required",
                            //                 "errorMessageInvalid" => "Enter Valid First Name",
                            //             ],
                            //         ],
                            //         [
                            //             "fieldKey" => "last_name",
                            //             "inputType" => "alphanumeric",
                            //             "fieldLabel" => "Last Name",
                            //             "fieldHolder" => "Enter Last Name",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "",
                            //             "value" => "",
                            //             "maxLength" => 50,
                            //             "validation" => [
                            //                 "validationPattern" => get_field_regex('name'),
                            //                 "errorMessageRequired" => "Last Name is Required",
                            //                 "errorMessageInvalid" => "Enter Valid Last Name",
                            //             ],
                            //         ],
                            //         [
                            //             "fieldKey" => "mobile",
                            //             "inputType" => "numeric",
                            //             "fieldLabel" => "Mobile",
                            //             "fieldHolder" => "Enter Mobile Number",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "",
                            //             "value" => "",
                            //             "maxLength" => 10,
                            //             "validation" => [
                            //                 "validationPattern" => get_field_regex('mobile'),
                            //                 "errorMessageRequired" => "Mobile Number is Required",
                            //                 "errorMessageInvalid" => "Enter Valid 10 digit Mobile Number starting with 6-9",
                            //             ],
                            //         ],
                            //         [
                            //             "fieldKey" => "email",
                            //             "inputType" => "alphanumeric",
                            //             "fieldLabel" => "Email",
                            //             "fieldHolder" => "Enter Email Address",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "",
                            //             "value" => "",
                            //             "maxLength" => 100,
                            //             "validation" => [
                            //                 "validationPattern" => get_field_regex('email'),
                            //                 "errorMessageRequired" => "Email is Required",
                            //                 "errorMessageInvalid" => "Enter Valid Email",
                            //             ],
                            //         ],
                            //     ],
                            // ],



                            // VEHICLE DETAILS BLOCK ADDED HERE
                            [
                                "sectionId" => "vehicle_details",
                                "sectionTitle" => "Vehicle Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "source_other",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Vehicle Source",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['source_other'], 'Vehicle Source'),
                                        "clearFields" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Vehicle Source is required",
                                            "errorMessageInvalid" => "Select a valid Vehicle Source",
                                        ],
                                        "conditionalApply" => [
                                            'issetValue' => [
                                                ['fieldKey'=>'car_type', 'equal' => ['1','3', '5','7'], 'value' => '3' ],
                                                ['fieldKey'=>'car_type', 'equal' => ['2'], 'value' => '2' ],
                                                ['fieldKey'=>'car_type', 'equal' => ['4', '6'], 'value' => '1' ],
                                                ['fieldKey'=>'car_type', 'equal' => ['', '0'], 'value' => '' ], // Clear when deselected
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "car_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Car Type",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['car_type'], 'Car Type'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Car Type is required",
                                            "errorMessageInvalid" => "Select Valid Car Type.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "reg_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Registration Type",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "isHidden" => false,
                                        "isBr" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['reg_type'], 'Registration Type'),
                                        "clearFields" => ["contact_name"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Registration Type is required",
                                            "errorMessageInvalid" => "Select a valid Registration Type",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'reg_num', 'equal' => ['', '1'] ],
                                                ['fieldKey'=>'reg_date', 'equal' => ['', '1'] ],
                                                ['fieldKey'=>'contact_name', 'not_equal' => ['3'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'reg_num', 'not_equal' => ['', '1'] ],
                                                ['fieldKey'=>'reg_date', 'not_equal' => ['', '1'] ],
                                                ['fieldKey'=>'contact_name', 'equal' => ['', '3'] ],
                                            ],
                                            'setFieldLabel' => [
                                                ['fieldKey'=>'first_name', 'equal' => ['3'], 'fieldLabel'=>'Contact Person First Name'],
                                                ['fieldKey'=>'last_name', 'equal' => ['3'], 'fieldLabel'=>'Contact Person Last Name'],
                                                ['fieldKey'=>'first_name', 'not_equal' => ['3'], 'fieldLabel'=>'First Name'],
                                                ['fieldKey'=>'last_name', 'not_equal' => ['3'], 'fieldLabel'=>'Last Name'],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "reg_num",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Registration Number",
                                        "fieldHolder" => "Enter Registration Number",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 30,
                                        "isCaps" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('reg_num'),
                                            "errorMessageRequired" => "Registration Number is required",
                                            "errorMessageInvalid" => "Enter Valid Registration Number",
                                        ],
                                        'addons' => [
                                            [
                                                "fieldKey" => "vaahan",
                                                'inputType' => 'component',
                                                "inputChange" => "dynamic_vaahan",
                                                'isDisabled' => false,
                                                'tooltip' => 'Fetch from Vaahan',
                                            ]
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "reg_date",
                                        "inputType" => "calender",
                                        "calenderType" => "upto_current",
                                        "fieldLabel" => "Registration Date",
                                        "isRequired" =>false,
                                        "isReadOnly" => true,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Registration Date is required",
                                            "errorMessageInvalid" => "Enter valid Registration Date",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mfg_year",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Manufacturing Year",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['years'], 'Years'),
                                        "dependsOn" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Manufacturing Year is required",
                                            "errorMessageInvalid" => "Select a valid manufacturing year",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mfg_month",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Manufacturing Month",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['months'], 'Months'),
                                        "dependsOn" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Manufacturing Month is required",
                                            "errorMessageInvalid" => "Select a valid manufacturing month",
                                        ],
                                    ],
                                   [
                                        "fieldKey" => "make",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_models",
                                        "fieldLabel" => "Make",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "isSearch" => true,
                                        "isGroup" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['makes'] ?? [], 'Makes'),
                                        "clearFields" => ["model","variant","color","interior_color"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Make is required",
                                            "errorMessageInvalid" => "Please select a valid make",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "model",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_variants",
                                        "fieldLabel" => "Model",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['models'] ?? [], 'Models'),
                                        "dependsOn" => "make",
                                        "clearFields" => ["variant"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Model is required",
                                            "errorMessageInvalid" => "Please select a valid model from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "variant",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "",
                                        "fieldLabel" => "Variant",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['variants'] ?? [], 'Variants'),
                                        "dependsOn" => "model",
                                        "clearFields" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Variant is required",
                                            "errorMessageInvalid" => "Please select a valid variant from the list",
                                        ],
                                    ],
                                     [
                                        "fieldKey" => "color",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "",
                                        "fieldLabel" => "Exterior Color",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "make",
                                        "fieldOptionIds" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Exterior Color is required",
                                            "errorMessageInvalid" => "Select Valid Exterior Color.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "interior_color",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "",
                                        "fieldLabel" => "Interior Color / Upholstery",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "make",
                                        "fieldOptionIds" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Interior Color is required",
                                            "errorMessageInvalid" => "Select Valid Interior Color.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "chassis",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "VIN / Chassis Number",
                                        "fieldHolder" => "Enter Vehicle Chassis",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 30,
                                        "isCaps" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('chassis'),
                                            "errorMessageRequired" => "Chassis is required",
                                            "errorMessageInvalid" => "Enter Valid Chassis",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "transmission",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Transmission",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['transmission'], 'Transmission'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Transmission is required",
                                            "errorMessageInvalid" => "Select Valid Transmission.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mileage",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Mileage",
                                        "fieldHolder" => "Enter Mileage in KMs",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Mileage is required",
                                            "errorMessageInvalid" => "Enter Valid Mileage",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "fuel",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Fuel",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['fuel'], 'Fuel'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Fuel is required",
                                            "errorMessageInvalid" => "Select Valid Fuel.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "fuel_end",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Fuel Endorsement (as per RC)",
                                        "tooltip" => "Does the RC book show the same fuel type?",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Fuel Endorsement'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "Fuel Endorsement is required",
                                            "errorMessageInvalid" => "Select Valid Fuel Endorsement.",
                                        ],
                                    ],
                                    // [
                                    //     "fieldKey" => "color",
                                    //     "inputType" => "dropdownIds",
                                    //     "fieldLabel" => "Color",
                                    //     "isRequired" => true,
                                    //     "isReadOnly" => true,
                                    //     "defaultInputValue" => "",
                                    //     "value" => "",
                                    //     "fieldOptionIds" => $this->buildOptions($this->commonConfig['colors'], 'Color'),
                                    //     "validation" => [
                                    //         "validationPattern" => get_field_regex('numeric'),
                                    //         "errorMessageRequired" => "Color is required",
                                    //         "errorMessageInvalid" => "Select Valid Color.",
                                    //     ],
                                    // ],
                                    [
                                        "fieldKey" => "owners",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Owners",
                                        "isRequired" => true,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['owners'], 'Owners'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Owners is required",
                                            "errorMessageInvalid" => "Select Valid Owners.",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "listing_price",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Listing Price",
                                        "fieldHolder" => "Enter Listing Price",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 10,
                                        "allowDecimal" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Listing Price is required",
                                            "errorMessageInvalid" => "Enter Valid Listing Price (numbers only)",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "remarks",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Remarks",
                                        "fieldHolder" => "Enter Remarks",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Remarks is required",
                                            "errorMessageInvalid" => "Enter Valid Remarks",
                                        ],
                                    ],
                                ],
                            ],
                            // [
                            //     "sectionId" => "others",
                            //     "sectionTitle" => "Others",
                            //     "isExpandedByDefault" => true,
                            //     "fields" => [
                            //         // [
                            //         //     "fieldKey" => "pin_code",
                            //         //     "inputType" => "numeric",
                            //         //     "inputChange" => "dynamic_location",
                            //         //     "fieldLabel" => "Pincode",
                            //         //     "fieldHolder" => "Enter Pincode",
                            //         //     "isRequired" => true,
                            //         //     "isReadOnly" => false,
                            //         //     "defaultInputValue" => "",
                            //         //     "value" => "",
                            //         //     "maxLength" => 6,
                            //         //     "clearFields" => ["state", "city"],
                            //         //     "validation" => [
                            //         //         "validationPattern" => get_field_regex('numeric'),
                            //         //         "errorMessageRequired" => "Pincode is required",
                            //         //         "errorMessageInvalid" => "Enter Valid Pincode",
                            //         //     ],
                            //         // ],
                            //         // [
                            //         //     "fieldKey" => "state",
                            //         //     "inputType" => "dynamic_dropdown",
                            //         //     "inputMethod" => "dynamic_state",
                            //         //     "fieldLabel" => "State",
                            //         //     "isRequired" => false,
                            //         //     "isReadOnly" => true,
                            //         //     "defaultInputValue" => "",
                            //         //     "value" => "",
                            //         //     "fieldOptions" => [],
                            //         //     "dependsOn" => "state",
                            //         //     "validation" => [
                            //         //         "validationPattern" => get_field_regex('numeric'),
                            //         //         "errorMessageRequired" => "Model is required",
                            //         //         "errorMessageInvalid" => "Please select a state from the list",
                            //         //     ],
                            //         // ],
                            //         // [
                            //         //     "fieldKey" => "city",
                            //         //     "inputType" => "dynamic_dropdown",
                            //         //     "inputMethod" => "dynamic_city",
                            //         //     "fieldLabel" => "City",
                            //         //     "isRequired" => false,
                            //         //     "isReadOnly" => true,
                            //         //     "defaultInputValue" => "",
                            //         //     "value" => "",
                            //         //     "fieldOptions" => [],
                            //         //     "dependsOn" => "city",
                            //         //     "validation" => [
                            //         //         "validationPattern" => get_field_regex('numeric'),
                            //         //         "errorMessageRequired" => "City is required",
                            //         //         "errorMessageInvalid" => "Please select a city from the list",
                            //         //     ],
                            //         // ],
                            //         // [
                            //         //     "fieldKey" => "branch_location",
                            //         //     "inputType" => "alphanumeric",
                            //         //     "fieldLabel" => "Branch Address",
                            //         //     "fieldHolder" => "Enter Address",
                            //         //     "isRequired" => true,
                            //         //     "isReadOnly" => false,
                            //         //     "defaultInputValue" => "",
                            //         //     "value" => "",
                            //         //     "maxLength" => 100,
                            //         //     "validation" => [
                            //         //         "validationPattern" => get_field_regex('field_address'),
                            //         //         "errorMessageRequired" => "Address is required",
                            //         //         "errorMessageInvalid" => "Enter Valid Address",
                            //         //     ],
                            //         // ],
                            //         [
                            //             "fieldKey" => "hypothecation",
                            //             "inputType" => "dropdownIds",
                            //             "fieldLabel" => "Hypothecation",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "",
                            //             "value" => "",
                            //             "fieldOptionIds" => $this->buildOptions($this->commonConfig['hypothecation'], 'Hypothecation'),
                            //             "validation" => [
                            //                 "validationPattern" => get_field_regex('alphanumeric'),
                            //                 "errorMessageRequired" => "Hypothecation is required",
                            //                 "errorMessageInvalid" => "Select Valid Hypothecation.",
                            //             ],
                            //         ],
                            //         [
                            //             "fieldKey" => "insurance_type",
                            //             "inputType" => "dropdownIds",
                            //             "fieldLabel" => "Insurance Type",
                            //             "isRequired" => true,
                            //             "isReadOnly" => false,
                            //             "defaultInputValue" => "Third Party",
                            //             "value" => "",
                            //             "fieldOptionIds" => $this->buildOptions($this->commonConfig['insurance_type'], 'Insurance Type' ),
                            //             "validation" => [
                            //                 "validationPattern" => "",
                            //                 "errorMessageRequired" => "Insurance Type is required",
                            //                 "errorMessageInvalid" => "Select Valid Insurance Type",
                            //             ],
                            //             "conditionalFields" => [
                            //                 "2" => [
                            //                     [
                            //                         "fieldKey" => "insurance_exp_date",
                            //                         "inputType" => "calender",
                            //                         "fieldLabel" => "Insurance Expiry Date",
                            //                         "isRequired" => true,
                            //                         "isEnable" => true,
                            //                         "defaultInputValue" => "",
                            //                         "value" => "",
                            //                         "validation" => [
                            //                             "validationPattern" => "",
                            //                             "errorMessageRequired" => "Insurance Expiry Date is required",
                            //                             "errorMessageInvalid" => "Enter valid Insurance Expiry Date",
                            //                         ],
                            //                     ],
                            //                 ],
                            //                 "3" => [
                            //                     [
                            //                         "fieldKey" => "insurance_exp_date",
                            //                         "inputType" => "calender",
                            //                         "fieldLabel" => "Insurance Expiry Date",
                            //                         "isRequired" => true,
                            //                         "isEnable" => true,
                            //                         "defaultInputValue" => "",
                            //                         "value" => "",
                            //                         "validation" => [
                            //                             "validationPattern" => "",
                            //                             "errorMessageRequired" => "Insurance Expiry Date is required",
                            //                             "errorMessageInvalid" => "Enter valid Insurance Expiry Date",
                            //                         ],
                            //                     ],
                            //                 ],
                                           
                            //             ],
                            //         ],


                            //         // [
                            //         //     "fieldKey" => "source",
                            //         //     "inputType" => "dynamic_dropdown",
                            //         //     "inputChange" => "dynamic_subsources",
                            //         //     "fieldLabel" => "Source",
                            //         //     "isRequired" => true,
                            //         //     "isEnable" => false,
                            //         //     "defaultInputValue" => "",
                            //         //     "value" => "",
                            //         //     "fieldOptions" => $this->buildOptions($this->commonConfig['sources'] ?? [], 'Source'),
                            //         //     "dependsOn" => "",
                            //         //     "clearFields" => ["source_sub"],
                            //         //     "validation" => [
                            //         //         "validationPattern" => get_field_regex('numeric'),
                            //         //         "errorMessageRequired" => "Source is Required",
                            //         //         "errorMessageInvalid" => "Please select a valid source",
                            //         //     ],
                            //         // ],
                            //         // [
                            //         //     "fieldKey" => "source_sub",
                            //         //     "inputType" => "dynamic_dropdown",
                            //         //     "inputMethod" => "",
                            //         //     "fieldLabel" => "Channel",
                            //         //     "isRequired" => false,
                            //         //     "isEnable" => false,
                            //         //     "defaultInputValue" => "",
                            //         //     "value" => "",
                            //         //     "fieldOptions" => $this->buildOptions($this->commonConfig['source_sub'] ?? [], 'Channel'),
                            //         //     "dependsOn" => "source",
                            //         //     "clearFields" => [],
                            //         //     "validation" => [
                            //         //         "validationPattern" => "",
                            //         //         "errorMessageRequired" => "Channel is required",
                            //         //         "errorMessageInvalid" => "Please select a valid channel",
                            //         //     ],
                            //         // ],
                            //     ]
                            // ]    
                        ],
                    ],
                ],
            ],

            'vahanConfig' => (object)[
                // Config properties

                'meta' => [
                    'title' => 'Vahan Details',
                    'dataPath' => 'detail.vahanInfo',
                    'showImages' => false,
                    'showDocuments' => false,
                    'showButtons' => false,
                    'loadedCheckPath' => 'detail.vahanInfo',
                ],

                    // Vehicle Information
                'fields' => [
                        'registrationNumber' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'registrationNumber',
                        'label' => 'Registration Number',
                        'val' => ''
                    ],
                    'registrationDate' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'registrationDate',
                        'label' => 'Registration Date',
                        'format' => 'date',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'registeredAt' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view', 
                        'key' => 'registeredAt',
                        'label' => 'Registered At',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'vehicleClassDescription' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'vehicleClassDescription', 
                        'label' => 'Vehicle Class',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'vehicleCatgory' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'vehicleCatgory',
                        'label' => 'Vehicle Category',
                        'defaultValue' => 'Not Available', 
                        'val' => ''
                    ],
                    'bodyTypeDescription' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'bodyTypeDescription',
                        'label' => 'Body Type',
                        'defaultValue' => 'Not Available',
                        'val' => ''
                    ],
                    'makerModel' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'makerModel',
                        'label' => 'Make/Model',
                        'val' => ''
                    ],
                    'makerDescription' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'makerDescription',
                        'label' => 'Maker Description',
                        'val' => ''
                    ],
                    'color' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'color',
                        'label' => 'Color',
                        'val' => ''
                    ],
                    'manufacturedMonthYear' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'manufacturedMonthYear',
                        'label' => 'Manufactured',
                        'val' => ''
                    ],
                    'rcStatus' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'rcStatus',
                        'label' => 'RC Status',
                        'val' => ''
                    ],
                    'stautsMessage' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'stautsMessage',
                        'label' => 'Status Message',
                        'val' => ''
                    ],
                    'statusAsOn' => [
                        'category' => 'Vehicle Information',
                        'type' => 'view',
                        'key' => 'statusAsOn',
                        'label' => 'Status As On',
                        'format' => 'date',
                        'val' => ''
                    ],

                    // Technical Specifications
                    'chassisNumber' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'chassisNumber',
                        'label' => 'Chassis Number',
                        'val' => ''
                    ],
                    'engineNumber' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'engineNumber',
                        'label' => 'Engine Number',
                        'val' => ''
                    ],
                    'fuelDescription' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'fuelDescription',
                        'label' => 'Fuel Type',
                        'val' => ''
                    ],
                    'cubicCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'cubicCapacity',
                        'label' => 'Cubic Capacity',
                        'val' => '',
                        'suffix' => ' CC'
                    ],
                    'numberOfCylinders' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'numberOfCylinders',
                        'label' => 'Number of Cylinders',
                        'val' => ''
                    ],
                    'seatingCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'seatingCapacity',
                        'label' => 'Seating Capacity',
                        'val' => ''
                    ],
                    'standingCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'standingCapacity',
                        'label' => 'Standing Capacity',
                        'val' => ''
                    ],
                    'sleeperCapacity' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'sleeperCapacity',
                        'label' => 'Sleeper Capacity',
                        'val' => ''
                    ],
                    'grossVehicleWeight' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'grossVehicleWeight',
                        'label' => 'Gross Vehicle Weight',
                        'val' => '',
                        'suffix' => ' Kg'
                    ],
                    'unladenWeight' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'unladenWeight',
                        'label' => 'Unladen Weight',
                        'val' => '',
                        'suffix' => ' Kg'
                    ],
                    'wheelbase' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'wheelbase',
                        'label' => 'Wheelbase',
                        'val' => ''
                    ],
                    'normsDescription' => [
                        'category' => 'Technical Specifications',
                        'type' => 'view',
                        'key' => 'normsDescription',
                        'label' => 'Norms Description',
                        'val' => ''
                    ],

                    // Owner Information
                    'ownerName' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'ownerName',
                        'label' => 'Owner Name',
                        'val' => ''
                    ],
                    'ownerSerialNumber' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'ownerSerialNumber',
                        'label' => 'Owner Serial Number',
                        'val' => ''
                    ],
                    'fatherName' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'fatherName',
                        'label' => 'Father Name',
                        'val' => ''
                    ],
                    'permanentAddress' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'permanentAddress',
                        'label' => 'Permanent Address',
                        'val' => ''
                    ],
                    'presentAddress' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'presentAddress',
                        'label' => 'Present Address',
                        'val' => ''
                    ],
                    'rcMobileNo' => [
                        'category' => 'Owner Information',
                        'type' => 'view',
                        'key' => 'rcMobileNo',
                        'label' => 'RC Mobile Number',
                        'val' => ''
                    ],

                    // Insurance Information
                    'insuranceCompany' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'insuranceCompany',
                        'label' => 'Insurance Company',
                        'val' => ''
                    ],
                    'insurancePolicyNumber' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'insurancePolicyNumber',
                        'label' => 'Insurance Policy Number',
                        'val' => ''
                    ],
                    'insuranceUpto' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'insuranceUpto',
                        'label' => 'Insurance Valid Until',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'financier' => [
                        'category' => 'Insurance Information',
                        'type' => 'view',
                        'key' => 'financier',
                        'label' => 'Financier',
                        'val' => ''
                    ],

                    // Permits and Compliance
                    'fitnessUpto' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'fitnessUpto',
                        'label' => 'Fitness Valid Until',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'taxPaidUpto' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'taxPaidUpto',
                        'label' => 'Tax Paid Until',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'pucNumber' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'pucNumber',
                        'label' => 'PUC Number',
                        'val' => ''
                    ],
                    'pucExpiryDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'pucExpiryDate',
                        'label' => 'PUC Expiry Date',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'nationalPermitNumber' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nationalPermitNumber',
                        'label' => 'National Permit Number',
                        'val' => ''
                    ],
                    'nationalPermitExpiryDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nationalPermitExpiryDate',
                        'label' => 'National Permit Expiry',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'nationalPermitIssuedBy' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nationalPermitIssuedBy',
                        'label' => 'National Permit Issued By',
                        'val' => ''
                    ],
                    'statePermitNumber' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitNumber',
                        'label' => 'State Permit Number',
                        'val' => ''
                    ],
                    'statePermitType' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitType',
                        'label' => 'State Permit Type',
                        'val' => ''
                    ],
                    'statePermitIssuedDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitIssuedDate',
                        'label' => 'State Permit Issued Date',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'statePermitExpiryDate' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'statePermitExpiryDate',
                        'label' => 'State Permit Expiry',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'blackListStatus' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'blackListStatus',
                        'label' => 'Black List Status',
                        'val' => ''
                    ],
                    'nocDetails' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nocDetails',
                        'label' => 'NOC Details',
                        'val' => ''
                    ],
                    'nonUseFrom' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nonUseFrom',
                        'label' => 'Non Use From',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'nonUseTo' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'nonUseTo',
                        'label' => 'Non Use To',
                        'format' => 'date',
                        'val' => ''
                    ],
                    'rcNonUseStatus' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'rcNonUseStatus',
                        'label' => 'RC Non Use Status',
                        'val' => ''
                    ],
                    'stateCd' => [
                        'category' => 'Permits and Compliance',
                        'type' => 'view',
                        'key' => 'stateCd',
                        'label' => 'State Code',
                        'val' => ''
                    ]
             ]
            ],

            'certificationConfig' => (object)[
                    'columns' => [
                        [
                            'title'=> 'Registration Date',
                            'data'=> [
                                ['key' => ['reg_date'],  'label' => 'Registration Date', 'type' => 'date' ],
                            ]
                        ],
                        [
                            'title'=> 'Age Criteria',
                            'data'=> [
                                ['key' => ['car_age'],  'label' => 'Vehicle Age', 'type' => 'text' ],
                                ['key' => ['car_age_allowed'],  'label' => 'Max Age Allowed', 'type' => 'text' ],
                            ],
                            'badge'=>['key' => 'age_criteria',  'yes' => 'Age Criteria Met', 'no' => 'Age Criteria Not Met' ],
                        ],
                        [
                            'title'=> 'Mileage Criteria',
                            'data'=> [
                                ['key' => ['mileage'],  'label' => 'Vehicle Mileage', 'type' => 'text' ],
                                ['key' => ['mileage_age_allowed'],  'label' => 'Max Mileage Allowed', 'type' => 'text' ],
                            ],
                            'badge'=>['key' => 'mileage_criteria',  'yes' => 'Mileage Criteria Met', 'no' => 'Mileage Criteria Not Met' ],
                        ],
                    ],
                    "fields" => [ 
                        [
                            "fieldLable" => "Certification",
                            "formType" => "expandable_form",
                            "sections" => [
                                [
                                    "sectionId" => "Certification_data",
                                    "sectionTitle" => "Certification Details",
                                    "isExpandedByDefault" => true,
                                    "fields" => [
                                            [        
                                                "fieldLabel" => "Certification Type",
                                                "fieldKey" => "certification_type",
                                                "inputType" => "dropdownIds",
                                                "isRequired" => true,
                                                "isReadOnly" => false,
                                                "defaultInputValue" => "",
                                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['certification_type'], 'Certification Type'),
                                                "dependsOn" => "",
                                                "value" => "",
                                                "validation" => [
                                                "validationPattern" => get_field_regex('numeric'),
                                                "errorMessageRequired" => "Certification type is required",
                                                "errorMessageInvalid" => "Select a valid certification type",
                                                ],
                                            ],
                                            [
                                                "fieldKey" => "certified_by",
                                                'inputType' => 'dynamic_dropdown',
                                                "fieldLabel" => "Certified By",
                                                "isRequired" => true,
                                                "isReadOnly" => false,
                                                "isHidden" => false,
                                                "defaultInputValue" => "",
                                                "value" => "",
                                                "fieldOptionIds" => [],
                                                "validation" => [
                                                    "validationPattern" => get_field_regex('id'),
                                                    "errorMessageRequired" => "Certified By is required",
                                                    "errorMessageInvalid" => "Select a valid executive in Certified By",
                                                ],
                                            ],
                                            [
                                                "fieldKey" => "certified_date",
                                                "inputType" => "calender",
                                                "calenderType" => "upto_current",
                                                "fieldLabel" => "Certification date",
                                                "isRequired" => true,
                                                "isReadOnly" => false,
                                                "defaultInputValue" => "",
                                                "value" => "", 
                                                "validation" => [
                                                    "validationPattern" =>"",
                                                    "errorMessageRequired" => "Certification date is required",
                                                    "errorMessageInvalid" => "Please select valid certificate date",
                                                ],
                                            ],
                                            [
                                                "fieldKey" => "certification_documents",
                                                "inputType" => "file",
                                                "fieldLabel" => "Certification Documents (Optional)",
                                                "isRequired" => false,
                                                "isReadOnly" => false,
                                                "isHidden" => false,
                                                "defaultInputValue" => "",
                                                "value" => "",
                                                "maxLength" => 250,
                                                "validation" => [
                                                    "validationPattern" => "",
                                                    "mimeType" => ['images','pdf'],
                                                    "errorMessageRequired" => "Certification document is required",
                                                    "errorMessageInvalid" => "Upload a valid certification document (jpg,png,doc)",
                                                ],
                                            ],
                                        ]
                                ], 
                                [
                                     "sectionId" => "certification-checklist",
                                    "sectionTitle" => "Certification Checklist",
                                            "isExpandedByDefault" => true,
                                            "fields" => [
                                                 [
                                                    "fieldKey" => "question1",
                                                    "inputType" => "radio",
                                                    "fieldLabel" => "1.Been maintained in accordance with the recommended service schedule at JLR authorised service center?",
                                                    "isReadOnly" => false,
                                                    "isHidden" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",
                                                    "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Finance'),
                                                    "validation" => [
                                                        "validationPattern" => "",
                                                        "errorMessageRequired" => "Question1 is required",
                                                        "errorMessageInvalid" => "Select a valid question 1 option",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "question2",
                                                    "inputType" => "radio",
                                                    "fieldLabel" => "2.Not met with a Major Accident which requires Structural Repairs etc, or Damage to Drivetrain / Airbags and other safety items, or suffered any flood damages?",
                                                    "isReadOnly" => false,
                                                    "isHidden" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",
                                                    "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Finance'),
                                                    "validation" => [
                                                        "validationPattern" => "",
                                                        "errorMessageRequired" => "Question2 is required",
                                                        "errorMessageInvalid" => "Select a valid question 2 option",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "question3",
                                                    "inputType" => "radio",
                                                    "fieldLabel" => "3.Two-Year of Warranty in the form of Residual OEM Warranty / Extended Warranty / Approved Warranty",
                                                    "isReadOnly" => false,
                                                    "isHidden" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",
                                                    "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Finance'),
                                                    "validation" => [
                                                        "validationPattern" => "",
                                                        "errorMessageRequired" => "Question3 is required",
                                                        "errorMessageInvalid" => "Select a valid question 3 option",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "question4",
                                                    "inputType" => "radio",
                                                    "fieldLabel" => "4.Two-Years of 24/7 Roadside Assistance",
                                                    "isReadOnly" => false,
                                                    "isHidden" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",
                                                    "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Finance'),
                                                    "validation" => [
                                                        "validationPattern" => "",
                                                        "errorMessageRequired" => "Question4 is required",
                                                        "errorMessageInvalid" => "Select a valid question 4 option",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "question5",
                                                    "inputType" => "radio",
                                                    "fieldLabel" => "5.Vehicle Provenance Certificate and Service History",
                                                    "isReadOnly" => false,
                                                    "isHidden" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",
                                                    "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Finance'),
                                                    "validation" => [
                                                        "validationPattern" => "",
                                                        "errorMessageRequired" => "Question5 is required",
                                                        "errorMessageInvalid" => "Select a valid question 5 option",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "question6",
                                                    "inputType" => "radio",
                                                    "fieldLabel" => "6.Clear Documentation and Vehicle Ownership History",
                                                    "isReadOnly" => false,
                                                    "isHidden" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",
                                                    "fieldOptionIds" => $this->buildOptions($this->commonConfig['active_type'], 'Finance'),
                                                    "validation" => [
                                                        "validationPattern" => "",
                                                        "errorMessageRequired" => "Question6 is required",
                                                        "errorMessageInvalid" => "Select a valid question 6 option",
                                                    ],
                                                ],

                                            ]
                                ],
                                [
                                    "sectionId" => "service_history-details",
                                    "sectionTitle" => "Service History Details",
                                            "isExpandedByDefault" => true,
                                            "fields" => [
                                                [
                                                    "fieldKey" => "date_of_sale",
                                                    "inputType" => "calender",
                                                    "calenderType" => "from_current",
                                                    "fieldLabel" => "Date of Sale",
                                                    "isRequired" => false,
                                                    "isReadOnly" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",                                                                                      					
                                                    "validation" => [
                                                        "validationPattern" =>"",
                                                        "errorMessageRequired" => "Date of sale is required",
                                                        "errorMessageInvalid" => "Please select valid sale date",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "date_of_handover",
                                                    "inputType" => "calender",
                                                    "calenderType" => "from_current",
                                                    "fieldLabel" => "Date of Handover",
                                                    "isRequired" => false,
                                                    "isReadOnly" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",                                                                                      					
                                                    "validation" => [
                                                        "validationPattern" =>"",
                                                        "errorMessageRequired" => "Date of handover is required",
                                                        "errorMessageInvalid" => "Please select valid handover date",
                                                    ],
                                                ],
                                                [
                                                    "fieldKey" => "date_of_warranty_start",
                                                    "inputType" => "calender",
                                                    "calenderType" => "from_current",
                                                    "fieldLabel" => "Date of Warranty Start",
                                                    "isRequired" => false,
                                                    "isReadOnly" => false,
                                                    "defaultInputValue" => "",
                                                    "value" => "",                                                                                      					
                                                    "validation" => [
                                                        "validationPattern" =>"",
                                                        "errorMessageRequired" => "Date of warranty start is required",
                                                        "errorMessageInvalid" => "Please select valid warranty start date",
                                                    ],
                                                ],
                                                ], 
                                ], 
                            ],  
                     ] 
                    ]
            ], 

        'approvalConfig' => [
            'meta' => [
                'title' => 'Certification Overview',
                'dataPath' => 'detail',
                'loadedCheckPath' => 'detail',
            ],

            // Simple overview fields grouped by category (view/date/numeric_format)
            'fields' => [
                'certification_type' => [
                    'category' => 'Certification Details',
                    'type'     => 'view',
                    'key'      => 'certification_type_name',
                    'label'    => 'Certification Type',
                ],
                'certified_by' => [
                    'category' => 'Certification Details',
                    'type'     => 'view',
                    'key'      => 'certified_by_name',
                    'label'    => 'Certified By',
                ],
                'certified_date' => [
                    'category' => 'Certification Details',
                    'type'     => 'date',
                    'key'      => 'certified_date',
                    'label'    => 'Certification Date',
                ],
                // Service history
                'date_of_sale' => [
                    'category' => 'Service History Details',
                    'type'     => 'date',
                    'key'      => 'date_of_sale',
                    'label'    => 'Date of Sale',
                ],
                'date_of_handover' => [
                    'category' => 'Service History Details',
                    'type'     => 'date',
                    'key'      => 'date_of_handover',
                    'label'    => 'Date of Handover',
                ],
                'date_of_warranty_start' => [
                    'category' => 'Service History Details',
                    'type'     => 'date',
                    'key'      => 'date_of_warranty_start',
                    'label'    => 'Date of Warranty Start',
                ],

                // Documents (overview)
                'documents' => [
                    'category' => 'Documents',
                    'type'     => 'media',
                    'key'      => 'documents',
                    'label'    => 'Documents',
                ],

            ],    
            'checklist'=>[
                'question1' => [
                    'category' => 'Certification Checklist',
                    'type'     => 'view',
                    'key'      => 'question1',
                    'label'    => '1. Been maintained in accordance with the recommended service schedule at JLR authorised service center?',
                ],
                'question2' => [
                    'category' => 'Certification Checklist',
                    'type'     => 'view',
                    'key'      => 'question2',
                    'label'    => '2. Not met with a Major Accident which requires Structural Repairs etc, or Damage to Drivetrain / Airbags and other safety items, or suffered any flood damages?',
                ],
                'question3' => [
                    'category' => 'Certification Checklist',
                    'type'     => 'view',
                    'key'      => 'question3',
                    'label'    => '3. Two-Year of Warranty in the form of Residual OEM Warranty / Extended Warranty / Approved Warranty',
                ],
                'question4' => [
                    'category' => 'Certification Checklist',
                    'type'     => 'view',
                    'key'      => 'question4',
                    'label'    => '4. Two-Years of 24/7 Roadside Assistance',
                ],
                'question5' => [
                    'category' => 'Certification Checklist',
                    'type'     => 'view',
                    'key'      => 'question5',
                    'label'    => '5. Vehicle Provenance Certificate and Service History',
                ],
                'question6' => [
                    'category' => 'Certification Checklist',
                    'type'     => 'view',
                    'key'      => 'question6',
                    'label'    => '6. Clear Documentation and Vehicle Ownership History',
                ],
            ],

            // interactive approval form (separate from the overview fields)
            'form' => [
                'fieldLabel' => 'UCM Certification Approval',
                'formType'   => 'expandable_form',
                'sections'   => [
                    [
                        'sectionId' => 'Certification_data',
                        'sectionTitle' => 'Certification Details',
                        'isExpandedByDefault' => true,
                        'fields' => [
                            [
                                'fieldLabel' => 'Certification Status',
                                'fieldKey'   => 'certification_status',
                                'inputType'  => 'dropdownIds',
                                'fieldHolder' => 'Certification Status',
                                'isRequired' => true,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'fieldOptionIds' => $this->buildOptions($this->commonConfig['certification_status'] ?? [], 'Certification Status'),
                                'value' => '',
                                'validation' => [
                                    'validationPattern' => get_field_regex('numeric'),
                                    'errorMessageRequired' => 'Certification status is required',
                                    'errorMessageInvalid'  => 'Select a valid certification status',
                                ],
                            ],
                            [
                                'fieldKey' => 'certification_remarks',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Approval/Rejection Notes',
                                'fieldHolder' => 'Please provide detailed notes for your approval or rejection decision',
                                'isRequired' => true,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 100,
                                'validation' => [
                                    'validationPattern' => get_field_regex('address'),
                                    'errorMessageRequired' => 'Approval/Rejection Notes is required',
                                    'errorMessageInvalid' => 'Enter valid approval/rejection notes',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    
    ];
        
        return  $data;
    }

    private function sm()
    {
        $data = [
            'menu' => $this->getConfig('sm', 'menu'),
            'sidebar' => (object)['showSidebar' => true, 'sidebarItems' => []],
            'grid' => (object)[
                'title' => 'Sales Master',
                'pagination' => (object)[
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'start_count' => 0,
                    'end_count' => 0,
                    'perPageOptions' => [10, 25, 50, 100]
                ],
                'list' => (array)[],
                'header' => (array)[
                    [
                        'type'=>'button',
                        'label' => "Add Sales Lead",
                        'icon' => "plus-circle",
                        'class' => "btn-dark",
                        'validation' => ['show' => true, 'disabled' => false],
                        'conditional' => [
                            'onclick' =>[
                                'meta' => ['key' => 'add_lead', 'type'=>'route', 'action' => "detail"],
                            ],
                        ]
                    ],
                    //  [
                    //     'type'=>'button',
                    //     'label' => "Export",
                    //     'icon' => "file-earmark-spreadsheet",
                    //     'validation' => ['show' => true, 'disabled' => false],
                    //     'class' => "btn-outline-dark",
                    //     'conditional' => [
                    //         'onclick' =>[
                    //             'meta' => ['key' => 'export', 'type'=>'get', 'action' => "exportData"],
                    //         ],
                    //     ]
                    // ]
                ],
                'searchConfig' => (object)[
                    'fields' => [
                         [
                                'fieldKey' => 'id',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Lead ID',
                                'fieldHolder' => 'Enter Lead ID',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 20,
                                'validation' => [
                                    'validationPattern' => get_field_regex('alphanumeric'),
                                    'errorMessageRequired' => 'ID is required',
                                    'errorMessageInvalid' => 'Enter Valid ID',
                                ],
                        ],
                        [
                            'fieldKey' => 'buyer_name',
                            'inputType' => 'alphanumeric',
                            'fieldLabel' => 'Name',
                            'fieldHolder' => 'Enter Customer Name',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 10,
                            'validation' => [
                                'validationPattern' => get_field_regex('name'),
                                'errorMessageRequired' => 'Name is required',
                                'errorMessageInvalid' => 'Enter Valid Name',
                            ],
                        ],
                        [
                            'fieldKey' => 'mobile',
                            'inputType' => 'phone',
                            'fieldLabel' => 'Mobile',
                            'fieldHolder' => 'Enter Mobile Number',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 10,
                            'validation' => [
                                'validationPattern' => get_field_regex('mobile'),
                                'errorMessageRequired' => 'Mobile is required',
                                'errorMessageInvalid' => 'Enter Valid Mobile',
                            ],
                        ],
                        [
                            'fieldKey' => 'email',
                            'inputType' => 'email',
                            'fieldLabel' => 'Email',
                            'fieldHolder' => 'Enter Email ID',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 100,
                            'validation' => [
                                'validationPattern' => get_field_regex('email'),
                                'errorMessageRequired' => 'Email is required',
                                'errorMessageInvalid' => 'Enter Valid email',
                            ],
                        ],
                        [
                            'fieldKey' => 'make',
                            'inputType' => 'dynamic_dropdown',
                            "inputChange" => "dynamic_models",
                            'fieldLabel' => 'Select Make',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            "isSearch" => true,
                            "fieldOptions" => $this->buildOptions($this->commonConfig['makes'] ?? [], 'Makes'),
                            "clearFields" => ["model"],
                            'validation' => [
                                "validationPattern" => get_field_regex('id'),
                                'errorMessageRequired' => 'Make is required',
                                'errorMessageInvalid' => 'Select Valid make',
                            ],
                        ],
                        [
                            'fieldKey' => 'model',
                            'inputType' => 'dynamic_dropdown',
                            "inputMethod" => "",
                            'fieldLabel' => 'Select Model',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            "fieldOptions" => $this->buildOptions($this->commonConfig['models'] ?? [], 'Models'),
                            'validation' => [
                                "validationPattern" => get_field_regex('numeric'),
                                'errorMessageRequired' => 'Model is required',
                                'errorMessageInvalid' => 'Select Valid model',
                            ],
                        ],
                        [
                                "fieldKey" => "lead_classification",
                                "inputType" => "dropdownIds",
                                "fieldLabel" => "Lead Classification",
                                "fieldHolder" => "Lead classification",
                                "isRequired" => false,
                                "isReadOnly" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['sm_classify'], 'Lead Classification'),
                                "validation" => [
                                    "validationPattern" => get_field_regex('alpha'),
                                    "errorMessageRequired" => "Lead classification is required",
                                    "errorMessageInvalid" => "Select a valid lead classification",
                                ],
                            ],
                            [
                                "fieldKey" => "branch",
                                "inputType" => "dynamic_dropdown",
                                "inputChange" => "dynamic_executies",
                                "fieldLabel" => "Branch",
                                "isRequired" => false,
                                "isReadOnly" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                                "clearFields" => ["executive"],
                                "validation" => [
                                    "validationPattern" => get_field_regex('id'),
                                    "errorMessageRequired" => "Branch is required",
                                    "errorMessageInvalid" => "Please select a valid Branch",
                                ],
                            ],
                            [
                                "fieldKey" => "executive",
                                "inputType" => "dynamic_dropdown",
                                "inputChange" => "dynamic_variants",
                                "fieldLabel" => "Executive",
                                "isRequired" => false,
                                "isReadOnly" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => $this->buildOptions($this->commonConfig['executive'] ?? [], 'Executive'),
                                "dependsOn" => "branch",
                                "validation" => [
                                    "validationPattern" => get_field_regex('id'),
                                    "errorMessageRequired" => "Executive is required",
                                    "errorMessageInvalid" => "Please select a valid model from the list",
                                ],
                            ],
                            [
                                "fieldKey" => "test_drive_done",
                                "inputType" => "checkbox_group",
                                "fieldLabel" => "Test Drive Completed",
                                "isReadOnly" => false,
                                "isHidden" => false,
                                "defaultInputValue" => "",
                                "value" => "",
                                "fieldOptionIds" => [['value' => 'y', 'label' => 'Yes']],
                                "validation" => [
                                    "validationPattern" => "",
                                    "errorMessageRequired" => "Test Drive Completed is required",
                                    "errorMessageInvalid" => "Select a valid Test Drive Completed option",
                                ],
                            ],
                    ],
                ],
                'columns' => [
                    [
                        'title'=> 'Lead Details',
                        'data'=> [
                            ['key' => ['formatted_id'], 'label' => 'ID', 'type' => 'text' ],
                            ['key' => ['created'], 'label' => 'Created', 'type' => 'date' ],
                            ['key' => ['source_name', 'source_sub_name'], 'label' => 'Source', 'type' => 'concat' ],
                            ['key' => ['executive_name'], 'icon'=>'person-fill-gear', 'attachKey'=>'executive', 'label' => '', 'type' => 'attach', 'role_main' => 'y' ],
                        ]
                    ],
                    [
                        'title'=> 'Buyer Details',
                        'data'=> [
                            ['key' => ['title', 'first_name', 'last_name'], 'label' => '', 'type' => 'text', 'class' => 'semibold'],
                            ['key' => ['buyer_type_name'], 'label' => 'Buyer Type', 'type' => 'text'],
                            ['key' => ['mobile'], 'label' => '', 'type' => 'text', 'class' => 'semibold', 'isMasked'=>'y'],
                            ['key' => ['email'], 'label' => '', 'type' => 'text', 'class' => 'semibold', 'isMasked'=>'y' ],
                            ['key' => ['city_name', 'state_name', 'pin_code'], 'label' => 'Location', 'type' => 'concat' ],
                        ]
                    ],
                    [
                        'title'=> 'Car Requirements',
                        'data'=> [
                            ['key' => ['vehicle_list'], 'label' => '', 'type' => 'concat', 'class' => 'semibold'],
                            ['key' => ['budget_range_name'], 'label' => 'Budget Range', 'type' => 'text' ],
                            ['key' => ['finance_name'], 'label' => 'Is Finance Selected', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Current Status',
                        'data'=> [
                            ['key' => [], 'icon'=>'clock-history', 'attachKey'=>'history', 'label' => '', 'type' => 'attach', 'tooltip' => 'Status History' ],
                            ['key' => ['status_name'], 'label' => '', 'type' => 'badge', 'class'=>['1'=>'bg-success','1'=>'bg-secondary'] ],
                            ['key' => ['sub_status_name'], 'label' => 'Sub Status', 'type' => 'text' ],
                            ['key' => ['followup_date'], 'label' => 'Followup Date', 'type' => 'date' ],
                        ]
                    ],
                    [
                        'title'=> 'Actions',
                        'data'=> [
                            [
                                'label' => 'View',
                                'type' => 'link',
                                'class' => "btn-secondary",
                                'icon' => "eye",
                                'meta' => ['type'=>'route', 'action'=>'detail/:id']
                            ],
                            [
                                'label' => 'Update Status',
                                'type' => 'link',
                                'class' => "btn-outline-secondary",
                                'icon' => "sort-up",
                                'meta' => ['type'=>'route', 'action'=>'detail/:id/status']
                            ],
                        ]
                    ]
                ],
            ],

             'history' => [
                [
                    'title'=> 'Updated Info',
                    'data'=> [
                        // ['key' => ['id'], 'label' => 'ID', 'type' => 'text' ],
                        ['key' => ['updated_date'], 'label' => 'Updated', 'type' => 'date' ],
                        ['key' => ['updated_by'], 'label' => 'Updated By', 'type' => 'text' ],
                    ]
                ],
                [
                    'title'=> 'Status Info',
                    'data'=> [
                        ['key' => ['status_name'], 'label' => 'Status', 'type' => 'text' ],
                        ['key' => ['sub_status_name'], 'label' => 'Sub Status', 'type' => 'text' ],
                        ['key' => ['followup_date'], 'label' => 'Follow-up date', 'type' => 'date' ],
                        ['key' => ['remarks'], 'label' => 'Remarks', 'type' => 'text' ],
                    ]
                ],
                [
                    'title'=> 'Pricing',
                    'data'=> [
                        ['key' => ['price_customer'], 'label' => 'Customer Offered Price', 'type' => 'numeric_format' ],
                        ['key' => ['price_quote'], 'label' => 'Retailer Offered Price', 'type' => 'numeric_format' ],
                        ['key' => ['price_agreed'], 'label' => 'Final Agreed Price', 'type' => 'numeric_format' ],
                        ['key' => ['price_indicative'], 'label' => 'Indicative Market Price', 'type' => 'numeric_format' ],
                        ['key' => ['price_sold'], 'label' => 'Final Sale Price', 'type' => 'numeric_format' ],
                        ['key' => ['price_margin'], 'label' => 'Provisioned Margin', 'type' => 'numeric_format' ],
                    ]
                ], 
                [
                    'title'=> 'Booking',
                    'data'=> [
                        ['key' => ['booked_vehicle'], 'label' => 'Stock ID', 'type' => 'text' ],
                        ['key' => ['booking_date'], 'label' => 'Booked Date', 'type' => 'date' ],
                    ]
                ], 
            ],

            'addConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "Sales Lead",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "lead-details",
                                "sectionId" => "lead-details",
                                "sectionTitle" => "Lead Details",
                                "isExpandedByDefault" => true,
                                "fields" => [ 

                                    [
                                        "fieldKey" => "source",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_subsources",
                                        "fieldLabel" => "Source",
                                        "isRequired" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptions" =>[],
                                        "dependsOn" => "",
                                        "clearFields" => ["source_sub"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Source is Required",
                                            "errorMessageInvalid" => "Please select a valid source",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "source_sub",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "",
                                        "fieldLabel" => "Channel",
                                        "fieldHolder" => "Select Channel",
                                        "isRequired" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptions" => [],
                                        "dependsOn" => "source",
                                        "clearFields" => [],
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Channel is required",
                                            "errorMessageInvalid" => "Please select a valid channel",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "branch",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_executies",
                                        "fieldLabel" => "Branch",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                                        "clearFields" => ["executive"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Branch is required",
                                            "errorMessageInvalid" => "Please select a valid Branch",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "executive",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_variants",
                                        "fieldLabel" => "Executive",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['executive'] ?? [], 'Executive'),
                                        "dependsOn" => "branch",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Executive is required",
                                            "errorMessageInvalid" => "Please select a valid Executive",
                                        ],
                                    ],
                                ]
                            ],
                            [
                                "sectionId" => "customer_details",
                                "sectionTitle" => "Customer Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "buyer_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Buyer Type",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['buyer_type'], 'Buyer Type'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('title'),
                                            "errorMessageRequired" => "Please select Buyer Type",
                                            "errorMessageInvalid" => "Select Valid Buyer Type",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "title",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Salutation",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        // "clearFields" => ["contact_name"],
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['title'], 'Salutation'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('title'),
                                            "errorMessageRequired" => "Please select Salutation",
                                            "errorMessageInvalid" => "Select Valid Salutation",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'contact_name', 'not_equal' => ['M/s'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'contact_name', 'equal' => ['', 'M/s'] ],
                                            ],
                                            'setFieldLabel' => [
                                                ['fieldKey'=>'first_name', 'equal' => ['M/s'], 'fieldLabel'=>'Contact Person First Name'],
                                                ['fieldKey'=>'last_name', 'equal' => ['M/s'], 'fieldLabel'=>'Contact Person Last Name'],
                                                ['fieldKey'=>'first_name', 'not_equal' => ['M/s'], 'fieldLabel'=>'First Name'],
                                                ['fieldKey'=>'last_name', 'not_equal' => ['M/s'], 'fieldLabel'=>'Last Name'],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "contact_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Company Name",
                                        "fieldHolder" => "Enter Company name",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 50,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "Contact Person is Required",
                                            "errorMessageInvalid" => "Enter Valid Contact Person Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "first_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "First Name",
                                        "fieldHolder" => "Enter First Name",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 50,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('name'),
                                            "errorMessageRequired" => "First Name is required",
                                            "errorMessageInvalid" => "Enter Valid First Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "last_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Last Name",
                                        "fieldHolder" => "Enter Last Name",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 50,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('name'),
                                            "errorMessageRequired" => "Last Name is Required",
                                            "errorMessageInvalid" => "Enter Valid Last Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mobile",
                                        "inputType" => "phone",
                                        "fieldLabel" => "Mobile",
                                        "fieldHolder" => "Enter Mobile Number",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 10,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('mobile'),
                                            "errorMessageRequired" => "Mobile Number is Required",
                                            "errorMessageInvalid" => "Enter Valid 10 digit Mobile Number starting with 6-9",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "email",
                                        "inputType" => "email",
                                        "fieldLabel" => "Email",
                                        "fieldHolder" => "Enter Email Address",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('email'),
                                            "errorMessageRequired" => "Email is Required",
                                            "errorMessageInvalid" => "Enter Valid Email",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "pin_code",
                                        "inputType" => "pin_code_search",
                                        "inputChange" => "dynamic_location",
                                        "fieldLabel" => "Pincode",
                                        "fieldHolder" => "Enter Pincode",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 6,
                                        "clearFields" => ["state", "city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Pincode is required",
                                            "errorMessageInvalid" => "Enter Valid Pincode",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "state",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_state",
                                        "fieldLabel" => "State",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "clearFields" => ["city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "State is required",
                                            "errorMessageInvalid" => "Please select a state from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "city",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_city",
                                        "fieldLabel" => "City",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => [],
                                        "dependsOn" => "state",
                                        "clearFields"=> [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "City is required",
                                            "errorMessageInvalid" => "Please select a city from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "address",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Address",
                                        "fieldHolder" => "Enter Address",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('address'),
                                            "errorMessageRequired" => "Address is required",
                                            "errorMessageInvalid" => "Enter Valid Address",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "notes",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Notes",
                                        "fieldHolder" => "Enter Notes",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Notes is required",
                                            "errorMessageInvalid" => "Enter Valid Notes",
                                        ],
                                    ],
                                ],
                            ],
                            [
                                "sectionId" => "customer_preferences",
                                "sectionTitle" => "Customer Preferences",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "budget_range",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Budget Range",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['budget_range'], 'Budget Range'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('title'),
                                            "errorMessageRequired" => "Please select Budget Range",
                                            "errorMessageInvalid" => "Select Valid Budget Range",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "color",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Base Colour",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['colors'], 'Base Colour'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Please select Base Colour",
                                            "errorMessageInvalid" => "Select Valid Base Colour",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "mileage_range",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Mileage Range",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['mileage_range'], 'Mileage Range'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Please select Mileage Range",
                                            "errorMessageInvalid" => "Select Valid Mileage Range",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "car_age",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Age of the Car",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['car_age'], 'Age of the Car'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Please select Age of the Car",
                                            "errorMessageInvalid" => "Select Valid Age of the Car",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "finance",
                                        "inputType" => "radio",
                                        "fieldLabel" => "Interested in Finance",
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['finance'], 'Finance'),
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Finance is required",
                                            "errorMessageInvalid" => "Select a valid Finance option",
                                        ],
                                    ],
                                ],
                            ],
                               
                        ],
                    ],
                ],
            ],
        'overview'=>(object)[
            
           'meta' => [
                'title' => 'Sales Lead Overview',
                'dataPath' => 'detail',
                'showImages' => false,
                'showDocuments' => false,
                'showButtons' => false,
                'loadedCheckPath' => 'detail',
              ],

 
            'fields' => [
                    // Lead Info
                    'id' => [ 'category' => 'Lead Info', 
                            'type' => 'view',
                            'key' => 'formatted_id',
                            'label' => 'Lead ID', 
                            'val' => '',
                            ],
                    'status' => [ 'category' => 'Lead Info', 'label' => 'Current Status', 'key' => 'status_name', 'type' => 'view',  'val' => '', ],
                    // 'status' => [ 'category' => 'Lead Info', 'label' => 'Sub Status', 'key' => 'sub_status_name', 'type' => 'view',  'val' => '', ],

                    // Buyer Details
                    'buyer_type' => [ 'category' => 'Buyer Details', 'label' => 'Buyer Type', 'key' => 'buyer_type_name', 'type' => 'view',  'val' => '', ],
                    'full_name' => [ 'category' => 'Buyer Details', 'label' => 'Customer Name', 'key' => 'title,first_name,last_name', 'type' => 'view',  'val' => '', ],
                    'mobile' => [ 'category' => 'Buyer Details', 'label' => 'Mobile', 'key' => 'mobile', 'type' => 'view',  'val' => '', ],
                    'email' => [ 'category' => 'Buyer Details', 'label' => 'Email', 'key' => 'email', 'type' => 'view',  'val' => '', ],

                    // Customer Preferences
                    'budget_range' => [ 'category' => 'Customer Preferences', 'label' => 'Budget Range', 'key' => 'budget_range_name', 'type' => 'view',  'val' => '', ],
                    'color' => [ 'category' => 'Customer Preferences', 'label' => 'Base Colour', 'key' => 'color_name', 'type' => 'view',  'val' => '', ],
                    'mileage_range' => [ 'category' => 'Customer Preferences', 'label' => 'Mileage Range', 'key' => 'mileage_range_name', 'type' => 'view',  'val' => '', ],
                    'car_age' => [ 'category' => 'Customer Preferences', 'label' => 'Age of the Car', 'key' => 'car_age_name', 'type' => 'view',  'val' => '', ],
                    'finance' => [ 'category' => 'Customer Preferences', 'label' => 'Interested in Finance', 'key' => 'finance_name', 'type' => 'view',  'val' => '', ],

                    // Location Info
                    'state' => [ 'category' => 'Location Info', 'label' => 'State', 'key' => 'state_name', 'type' => 'view',  'val' => '', ],
                    'city' => [ 'category' => 'Location Info', 'label' => 'City', 'key' => 'city_name', 'type' => 'view',  'val' => '', ],
                    'address' => [ 'category' => 'Location Info', 'label' => 'Address', 'key' => 'address', 'type' => 'view',  'val' => '', ],
                    'pin_code' => [ 'category' => 'Location Info', 'label' => 'Pin Code', 'key' => 'pin_code', 'type' => 'view',  'val' => '', ],

                    // Dealership Info
                    'dealer' => [ 'category' => 'Branch Info', 'label' => 'Branch', 'key' => 'branch_name', 'type' => 'view',  'val' => '', ],
                    'executive' => [ 'category' => 'Branch Info', 'label' => 'Executive', 'key' => 'executive_name', 'type' => 'view',  'val' => '', ],
                    'source' => [ 'category' => 'Branch Info', 'label' => 'Source', 'key' => 'source_name', 'type' => 'view',  'val' => '', ],
                    'source_sub' => [ 'category' => 'Branch Info', 'label' => 'Sub Source', 'key' => 'source_sub_name', 'type' => 'view',  'val' => '', ],

                    // Dates Info
                    'followup_date' => [ 'category' => 'Dates Info', 'label' => 'Follow-up Date', 'key' => 'followup_date', 'type' => 'date',  'val' => '', ],
                    // 'evaluation_date' => [ 'category' => 'Dates Info', 'label' => 'Evaluation Date', 'key' => 'evaluation_date', 'type' => 'view',  'val' => '', ],
                    'created' => [ 'category' => 'Dates Info', 'label' => 'Created Date', 'key' => 'created', 'type' => 'date',  'val' => '', ],
                    'updated' => [ 'category' => 'Dates Info', 'label' => 'Updated Date', 'key' => 'updated', 'type' => 'date',  'val' => '', ],

                    'status_name' => [ 'category' => 'Status Info', 'label' => 'Status', 'key' => 'status_name', 'type' => 'view',  'val' => '', ],
                    'sub_status_name' => [ 'category' => 'Status Info', 'label' => 'Sub Status', 'key' => 'sub_status_name', 'type' => 'view',  'val' => '', ],
                    'lead_classification' => [ 'category' => 'Status Info', 'label' => 'Lead Classification', 'key' => 'lead_classification', 'type' => 'view',  'val' => '', ],
                    'buying_horizon' => [ 'category' => 'Status Info', 'label' => 'Buying Horizon', 'key' => 'buying_horizon', 'type' => 'view',  'val' => '', ],
                    'customer_visited' => [ 'category' => 'Status Info', 'label' => 'Customer Visited', 'key' => 'customer_visited', 'type' => 'view',  'val' => '', ],
                    'customer_visited_date' => [ 'category' => 'Status Info', 'label' => 'Customer Visited Date', 'key' => 'customer_visited_date', 'type' => 'date',  'val' => '', ],
                    
                    'price_indicative' => [ 'category' => 'Pricing', 'label' => 'Indicative Market Price', 'key' => 'price_indicative', 'type' => 'numeric_format',  'val' => '', ],
                    'price_quote' => [ 'category' => 'Pricing', 'label' => 'Retailer Offered Price', 'key' => 'price_quote', 'type' => 'numeric_format',  'val' => '', ],
                    'price_customer' => [ 'category' => 'Pricing', 'label' => 'Customer Offered Price', 'key' => 'price_customer', 'type' => 'numeric_format',  'val' => '', ],
                    'price_agreed' => [ 'category' => 'Pricing', 'label' => 'Final Agreed Price', 'key' => 'price_agreed', 'type' => 'numeric_format',  'val' => '', ],
                    'price_margin' => [ 'category' => 'Pricing', 'label' => 'Provisioned Margin', 'key' => 'price_margin', 'type' => 'numeric_format',  'val' => '', ],
                    'price_sold' => [ 'category' => 'Pricing', 'label' => 'Final Sale Price', 'key' => 'price_sold', 'type' => 'numeric_format',  'val' => '', ],
                    'token_amount' => [ 'category' => 'Pricing', 'label' => 'Token Amount', 'key' => 'token_amount', 'type' => 'numeric_format',  'val' => '', ],
                    
                    'sold_by_name' => [ 'category' => 'Sale Details', 'label' => 'Sold By', 'key' => 'sold_by_name', 'type' => 'view',  'val' => '', ],
                    'sold_date' => [ 'category' => 'Sale Details', 'label' => 'Sold Date', 'key' => 'sold_date', 'type' => 'date',  'val' => '', ],
                    'delivery_date' => [ 'category' => 'Sale Details', 'label' => 'Delivery Date', 'key' => 'delivery_date', 'type' => 'date',  'val' => '', ],

                    
                    // Media
                    // 'images' => [ 'category' => 'Images', 'label' => 'Images', 'key' => 'images', 'type' => 'view',  'val' => '', ],
                    // 'documents' => [ 'category' => 'Documents', 'label' => 'Documents', 'key' => 'documents', 'type' => 'view',  'val' => '', ],
                ]
            ],
            'statusConfig' => (object)[
                'columns' => [
                    [
                        'title'=> 'Status',
                        'data'=> [
                            ['key' => ['status_name'],  'label' => '', 'type' => 'text' ],
                            ['key' => ['sub_status_name'], 'label' => 'Sub Status', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Followup',
                        'data'=> [
                            ['key' => ['followup_date'],  'label' => 'Followup Date', 'type' => 'date' ],
                        ]
                    ],
                    [
                        'title'=> 'Test Drive',
                        'data'=> [
                            ['key' => ['test_drive_done_name'],  'label' => 'Test Drive Done', 'type' => 'date' ],
                            ['key' => ['final_test_drive_date'],  'label' => 'Test Drive Date', 'type' => 'date' ],
                        ]
                    ],
                ],
                "fields" => [
                    [
                        "fieldLabel" => "Lead Status",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "leadStatus",
                                "sectionTitle" => "Status of Lead",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "status",
                                        "inputType" => "dropdownIds",
                                        "inputChange" => ["dynamic_substatus", "dynamic_booking_vehicles"],
                                        "fieldLabel" => "Status",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "clearFields" => ["sub_status", "followup_date", "remarks"],
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['sm_status'], 'Status'),
                                        "conditionalApply" => [

                                            'isOptionsDisabled'=>[
                                                ['fieldKey'=>'status', 'equal' => ['1'], "options" => ['4'] ],
                                                ['fieldKey'=>'status', 'equal' => ['2'], "options" => ['1', '4'] ],
                                                ['fieldKey'=>'status', 'equal' => ['3'], "options" => ['1', '2'] ],
                                                ['fieldKey'=>'status', 'equal' => ['4'], "options" => ['1', '2', '3', '5'] ],
                                                ['fieldKey'=>'status', 'equal' => ['5'], "options" => ['1', '2', '3', '4'] ],
                                            ],

                                            'isHidden' => [
                                                ['fieldKey'=>'sub_status', 'equal' => ['1', '4'] ],
                                                ['fieldKey'=>'token_amount', 'not_equal' => ['3',] ],
                                                ['fieldKey'=>'test_drive_done', 'equal' => ['5'] ],
                                                ['fieldKey'=>'price_selling', 'not_equal' => ['4'] ],
                                                ['fieldKey'=>'booked_vehicle', 'not_equal' => ['3'] ],
                                                ['fieldKey'=>'booking_date', 'not_equal' => ['3'] ],
                                                // ['fieldKey'=>'customer_visited', 'equal' => ['5']],
                                                ['fieldKey'=>'price_sold', 'not_equal' => ['4']],                                    
                                                ['fieldKey'=>'sold_by', 'not_equal' => ['4']],                                    
                                                ['fieldKey'=>'file_doc1', 'not_equal' => ['4']],                                       
                                                ['fieldKey'=>'sold_date', 'not_equal' => ['4']],
                                                ['fieldKey'=>'delivery_date', 'not_equal' => ['4']],
                                                ['fieldKey'=>'followup_date', 'not_equal' => ['2'] ],
                                                ['fieldKey'=>'price_indicative', 'equal' => ['1','2','3','4','5'] ],
                                                ['fieldKey'=>'price_customer', 'equal' => ['5'] ],
                                                ['fieldKey'=>'price_quote', 'equal' => ['5'] ],
                                                ['fieldKey'=>'price_agreed', 'equal' => ['5'] ],
                                                ['fieldKey'=>'price_margin', 'equal' => ['5'] ],
                                            ],

                                            'isRequired' => [
                                                ['fieldKey'=>'sub_status', 'not_equal' => ['1','4'] ],
                                                ['fieldKey'=>'price_selling', 'equal' => ['4'] ],
                                                ['fieldKey'=>'followup_date', 'equal' => ['2'] ],
                                                ['fieldKey'=>'remarks', 'equal' => ['1','2','3','4','5'] ],
                                                ['fieldKey'=>'price_sold', 'equal' => ['4']],  
                                                ['fieldKey'=>'price_customer', 'equal' => ['4'] ],
                                                ['fieldKey'=>'price_quote', 'equal' => ['4'] ],
                                                ['fieldKey'=>'price_agreed', 'equal' => ['4'] ],
                                                ['fieldKey'=>'price_margin', 'equal' => ['4'] ],
                                                ['fieldKey'=>'token_amount', 'equal' => ['3'] ],
                                                ['fieldKey'=>'sold_by', 'equal' => ['4']],  
                                                ['fieldKey'=>'sold_date', 'equal' => ['4']],
                                                ['fieldKey'=>'booked_vehicle', 'equal' => ['3'] ],
                                                ['fieldKey'=>'booking_date', 'equal' => ['3'] ],
                                            ],

                                            'isDisabled' => [
                                                ['fieldKey' => 'booked_vehicle', 'equal' => ['4']],
                                                ['fieldKey' => 'booked_date', 'equal' => ['4']],
                                            ],
                                        ],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Status is required",
                                            "errorMessageInvalid" => "Select a valid Status",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "sub_status",
                                        "inputType" => "dynamic_dropdown",
                                        "inputChange" => "dynamic_testdrive_vehicles",
                                        "fieldLabel" => "Sub Status",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "isBr" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "status",
                                        "fieldOptionIds" => [],
                                        "clearFields" => ['test_drive_vehicle', 'test_drive_place'],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Sub Status is required",
                                            "errorMessageInvalid" => "Select a valid Sub Status",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'test_drive_vehicle', 'not_equal' => ['9'] ],
                                                ['fieldKey'=>'test_drive_place', 'not_equal' => ['9'] ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "lead_classification",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Lead Classification",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['sm_classify'], 'Lead Classification'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alpha'),
                                            "errorMessageRequired" => "Lead classification is required",
                                            "errorMessageInvalid" => "Select a valid lead classification",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "buying_horizon",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Buying Horizon",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['buying_horizon'], 'Buying Horizon'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "Buying horizon is required",
                                            "errorMessageInvalid" => "Select a valid buying horizon",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "booked_vehicle",
                                        'inputType' => 'dynamic_dropdown',
                                        "fieldLabel" => "Booking Vehicle",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "isDisabled" => false, 
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "isSearch" => true,
                                        "fieldOptionIds" => [],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Booking Vehicle is required",
                                            "errorMessageInvalid" => "Select a valid Booking Vehicle",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "booking_date",
                                        "inputType" => "calender_time",
                                        "fieldLabel" => "Booking Date",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Followup Date & Time is required",
                                            "errorMessageInvalid" => "Select valid Followup Date & Time",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "followup_date",
                                        "inputType" => "calender_time",
                                        "fieldLabel" => "Next follow-up date",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Followup Date & Time is required",
                                            "errorMessageInvalid" => "Select valid Followup Date & Time",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "test_drive_vehicle",
                                        "inputType" => "dynamic_dropdown",
                                        "fieldLabel" => "Test Drive Vehicle",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "isBr" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "sub_status",
                                        "fieldOptionIds" => [],
                                        "clearFields" => ['test_drive_place'],
                                        "multiple" => true,
                                        "isSearch" => true,
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Test Drive Date is required",
                                            "errorMessageInvalid" => "Select a valid Test Drive Date",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "test_drive_place",
                                        'inputType' => 'radio',
                                        "fieldLabel" => "Test Drive Place",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "dependsOn" => "sub_status",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['testdrive_place'], 'Test Drive Place'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Test Drive Place is required",
                                            "errorMessageInvalid" => "Select a valid Test Drive Place",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "remarks",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Remarks",
                                        "fieldHolder" => "Enter Remarks",
                                        "tooltip" => "Please enter at least 10 characters.",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumericspecial'),
                                            "errorMessageRequired" => "Remarks is required",
                                            "errorMessageInvalid" => "Enter Valid Remarks",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_visited",
                                        "inputType" => "radio",
                                        "fieldLabel" => "Customer visited",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "isBr" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['customer_visited'], 'Customer Visited'),
                                        "clearFields" => [""],
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Customer Visited is required",
                                            "errorMessageInvalid" => "Select a valid Customer Visited option",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'customer_visited_date', 'equal' => ['n'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'customer_visited_date', 'equal' => ['y'] ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_visited_date",
                                        "inputType" => "calender",
                                        "calenderType" => "upto_current",
                                        "fieldLabel" => "Customer Visited Date",
                                        "isRequired" =>false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Customer Visited Date is required",
                                            "errorMessageInvalid" => "Enter valid Customer Visited Date",
                                        ],
                                    ],

                                ],
                            ], 
                            [
                                "sectionId" => "pricing",
                                "sectionTitle" => "Pricing",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "price_indicative",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Indicative Market Price",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Indicative Price is required",
                                            "errorMessageInvalid" => "Enter a valid Indicative Place",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "price_quote",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Retailer Offered Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Retailer Offered Price is required",
                                            "errorMessageInvalid" => "Enter a valid Retailer Offered Price",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "price_customer",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Customer Offered Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Customer Offered Price is required",
                                            "errorMessageInvalid" => "Enter a valid Customer Offered Price",
                                        ],
                                    ],    
                                    [
                                        "fieldKey" => "price_agreed",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Final Agreed Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Agreed Price is required",
                                            "errorMessageInvalid" => "Enter a valid Agreed Price",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "price_margin",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Provisioned Margin",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Provisioned Margin is required",
                                            "errorMessageInvalid" => "Enter a valid Provisioned Margin",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "price_sold",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Final Sale Price",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Final Sale Price is required",
                                            "errorMessageInvalid" => "Enter a valid Final Sale Price",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "token_amount",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "Token Amount",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 7,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Token Price is required",
                                            "errorMessageInvalid" => "Enter a valid Token Price",
                                        ],
                                    ],
                                      
                                ],
                            ],
                            [
                                "sectionId" => "saledetails",
                                "sectionTitle" => "Sale Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "sold_by",
                                        'inputType' => 'dynamic_dropdown',
                                        "fieldLabel" => "Sold By",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['executives'] ?? [], 'Executives'),
                                        // "fieldOptions" => $this->buildOptions($this->commonConfig['executives'] ?? [], 'Executives'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Sold By is required",
                                            "errorMessageInvalid" => "Select a valid executive in Sold By",
                                        ],
                                    ],
                                    

                                    [
                                        "fieldKey" => "file_doc1",
                                        "inputType" => "file",
                                        "fieldLabel" => "Price Agreement",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 250,
                                        "validation" => [
                                            "validationPattern" => "",
                                            "mimeType" => ['images','pdf'],
                                            "errorMessageRequired" => "Price Agreement is required",
                                            "errorMessageInvalid" => "Upload a valid Price Agreement (jpg,png,doc)",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "sold_date",
                                        "inputType" => "calender_time",
                                        "fieldLabel" => "Sold date",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Sold Date & Time is required",
                                            "errorMessageInvalid" => "Select valid Sold Date & Time",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "delivery_date",
                                        "inputType" => "calender_time",
                                        "fieldLabel" => "Delivery date",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "validation" => [
                                            "validationPattern" => "",
                                            "errorMessageRequired" => "Delivery Date & Time is required",
                                            "errorMessageInvalid" => "Select valid Delivery Date & Time",
                                        ],
                                    ],
                                ],
                            ],
                           
                        ],
                    ],
                ],
            ],

            'exact_vehicles' => (object)[
                "fields" => [
                    [
                        'fieldKey' => 'make',
                        'inputType' => 'dynamic_dropdown',
                        "inputChange" => ["dynamic_models"],
                        'fieldLabel' => 'Make',
                        'fieldHolder' => 'Make',
                        'isRequired' => false,
                        'isReadOnly' => false,
                        "isSearch" => true,
                        "isGroup" => true,
                        'defaultInputValue' => '',
                        'value' => '',
                        "fieldOptions" => $this->buildOptions([], 'Makes'),
                        "clearFields" => ["model"],
                        'validation' => [
                            "validationPattern" => get_field_regex('numeric'),
                            'errorMessageRequired' => 'Make is required',
                            'errorMessageInvalid' => 'Select Valid make',
                        ],
                    ],
                    [
                        'fieldKey' => 'model',
                        'inputType' => 'dynamic_dropdown',
                        "inputMethod" => "",
                        'fieldLabel' => 'Model',
                        'fieldHolder' => 'Model',
                        'isRequired' => false,
                        'isReadOnly' => false,
                        'defaultInputValue' => '',
                        'value' => '',
                        "fieldOptions" => $this->buildOptions([], 'Models'),
                        'validation' => [
                            "validationPattern" => get_field_regex('numeric'),
                            'errorMessageRequired' => 'Model is required',
                            'errorMessageInvalid' => 'Select Valid model',
                        ],
                    ],
                    [
                        "fieldKey" => "mfg_year",
                        "inputType" => "dropdownIds",
                        "fieldLabel" => "Manufacturing Year",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['years'], 'Years'),
                        "dependsOn" => "",
                        "value" => "",
                        "validation" => [
                            "validationPattern" => get_field_regex('numeric'),
                            "errorMessageRequired" => "Manufacturing Year is required",
                            "errorMessageInvalid" => "Select a valid manufacturing year",
                        ],
                    ],
                    [
                        "fieldKey" => "budget_range",
                        "inputType" => "dropdownIds",
                        "fieldLabel" => "Budget Range",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['vehicle_budget_range'], 'Budget Range'),
                        "validation" => [
                            "validationPattern" => get_field_regex('title'),
                            "errorMessageRequired" => "Please select Budget Range",
                            "errorMessageInvalid" => "Select Valid Budget Range",
                        ],
                    ],
                ],
                     
                // 'existingExactMatch' => (object)[
                //     'title'   => 'Existing Exact Matches',
                //     'columns' => [
                //         ['title' => '#', 'data' => [['key' => ['index'], 'label' => '#', 'type' => 'text']]],
                //         ['title' => 'Make', 'data' => [['key' => ['make_name','make'], 'label' => 'Make', 'type' => 'text']]],
                //         ['title' => 'Model', 'data' => [['key' => ['model_name','model'], 'label' => 'Model', 'type' => 'text']]],
                //         ['title' => 'Year', 'data' => [['key' => ['year','mfg_year'], 'label' => 'Year', 'type' => 'text']]],
                //         ['title' => 'Selected From', 'data' => [['key' => ['selected_form'], 'label' => 'Selected From', 'type' => 'text']]],
                //         ['title' => 'Match Details', 'data' => [['key' => ['match_details'], 'label' => 'Match Details', 'type' => 'view']]],
                //         ['title' => 'Actions', 'data' => [['meta' => ['type'=>'action','action'=>'delete','label'=>'Delete']]]]
                //     ]
                // ],
                // 'addExactMatch' => (object)[
                //     'title'   => 'Add New Exact Matches',
                //     'maxRows' => 10,
                //     'columns' => [
                //         ['title' => '#', 'data' => [['key' => ['id'], 'label' => 'Row', 'type' => 'text']]],
                //         ['title' => 'Make', 'data' => [['key' => ['make'], 'label' => 'Make', 'type' => 'dynamic_dropdown']]],
                //         ['title' => 'Model', 'data' => [['key' => ['model'], 'label' => 'Model', 'type' => 'dynamic_dropdown']]],
                //         ['title' => 'Year', 'data' => [['key' => ['year'], 'label' => 'Year', 'type' => 'alphanumeric']]],
                //         ['title' => 'Exact Match', 'data' => [['key' => ['inventory_matches','selllead_matches'], 'label' => 'Exact Match', 'type' => 'view']]],
                //         ['title' => 'Actions', 'data' => [['meta' => ['type'=>'action','action'=>'delete','label'=>'Delete']]]]
                //     ]
                // ]
            ],

            "testdriveAddConfig" => [
                "fieldLabel" => "Add Test Drive",
                "formType" => "expandable_form",
                "sections" => [
                [
                    "sectionId" => "addtestdrive",
                    "sectionTitle" => "Test Drive",
                    "isExpandedByDefault" => true,
                    "fields" => [
                    [
                        "fieldKey" => "scheduled_date",
                        "inputType" => "calender_time",
                        "calenderType" => "from_current",
                        "fieldLabel" => "Scheduled Date",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "validation" => [
                        "validationPattern" => "",
                        "errorMessageRequired" => "Scheduled Date is required",
                        "errorMessageInvalid" => "Enter valid Scheduled Date",
                        ],
                    ],
                    [
                        "fieldKey" => "test_drive_vehicle",
                        "inputType" => "dynamic_dropdown",
                        "inputMethod" => "dynamic_testdrivelist",
                        "fieldLabel" => "Vehicle",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "fieldOptions" => [],
                        "validation" => [
                        "validationPattern" => "^[0-9]+$",
                        "errorMessageRequired" => "Vehicle is required",
                        "errorMessageInvalid" => "Please select a Vehicle from the list",
                        ],
                    ],
                    [
                        "fieldKey" => "test_drive_place",
                        "inputType" => "dropdownIds",
                        "fieldLabel" => "Test Drive Place",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "fieldOptionIds" => [
                        ["value" => "1", "label" => "Showroom"],
                        ["value" => "2", "label" => "Home Visit"],
                        ["value" => "3", "label" => "Other"],
                        ["value" => "4", "label" => "Event"],
                        ],
                        "validation" => [
                        "validationPattern" => "^\d+$",
                        "errorMessageRequired" => "Please select Test Drive Place",
                        "errorMessageInvalid" => "Select Valid Test Drive Place",
                        ],
                    ],
                    [
                        "fieldKey" => "test_drive_status",
                        "inputType" => "dropdownIds",
                        "fieldLabel" => "Test Drive Status",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "fieldOptionIds" => [
                        ["value" => "1", "label" => "Scheduled"],
                        ["value" => "2", "label" => "Completed"],
                        ["value" => "3", "label" => "Cancelled"],
                        ["value" => "4", "label" => "No Show"],
                        ],
                        "validation" => [
                        "validationPattern" => "^\d+$",
                        "errorMessageRequired" => "Please select Test Drive Status",
                        "errorMessageInvalid" => "Select Valid Test Drive Status",
                        ],
                    ],
                    [
                        "fieldKey" => "form_doc",
                        "inputType" => "file",
                        "fieldLabel" => "Form",
                        "isRequired" => false,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "validation" => [
                        "validationPattern" => "",
                        "mimeType" => ["images", "pdf"],
                        "errorMessageRequired" => "Test Drive Form is required",
                        "errorMessageInvalid" => "Upload a valid Test Form (jpg,jpeg,png,pdf only)",
                        ],
                    ],
                    [
                        "fieldKey" => "completed_date",
                        "inputType" => "calender_time",
                        "calenderType" => "upto_current",
                        "fieldLabel" => "Completed Date",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "validation" => [
                        "validationPattern" => "",
                        "errorMessageRequired" => "Completed Date is required",
                        "errorMessageInvalid" => "Enter valid Completed Date",
                        ],
                    ],
                    ],
                ],
                ],
            ],

            "testdriveEditConfig" => [
                "fieldLabel" => "Edit Test Drive",
                "formType" => "expandable_form",
                "sections" => [
                [
                    "sectionId" => "edittestdrive",
                    "sectionTitle" => "Update Test Drive",
                    "isExpandedByDefault" => true,
                    "fields" => [
                    [
                        "fieldKey" => "scheduled_date",
                        "inputType" => "calender_time",
                        "calenderType" => "from_current",
                        "fieldLabel" => "Scheduled Date",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "isHidden" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "validation" => [
                        "validationPattern" => "",
                        "errorMessageRequired" => "Scheduled Date is required",
                        "errorMessageInvalid" => "Enter valid Scheduled Date",
                        ],
                    ],
                    [
                        "fieldKey" => "test_drive_place",
                        "inputType" => "dropdownIds",
                        "fieldLabel" => "Test Drive Place",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "fieldOptionIds" => [
                        ["value" => "1", "label" => "Showroom"],
                        ["value" => "2", "label" => "Home Visit"],
                        ["value" => "3", "label" => "Other"],
                        ["value" => "4", "label" => "Event"],
                        ],
                        "validation" => [
                        "validationPattern" => "^\d+$",
                        "errorMessageRequired" => "Please select Test Drive Place",
                        "errorMessageInvalid" => "Select Valid Test Drive Place",
                        ],
                    ],
                    [
                        "fieldKey" => "test_drive_status",
                        "inputType" => "dropdownIds",
                        "fieldLabel" => "Test Drive Status",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "fieldOptionIds" => [
                        ["value" => "1", "label" => "Scheduled"],
                        ["value" => "2", "label" => "Completed"],
                        ["value" => "3", "label" => "Cancelled"],
                        ["value" => "4", "label" => "No Show"],
                        ],
                        "validation" => [
                        "validationPattern" => "^\d+$",
                        "errorMessageRequired" => "Please select Test Drive Status",
                        "errorMessageInvalid" => "Select Valid Test Drive Status",
                        ],
                    ],
                    [
                        "fieldKey" => "form_doc",
                        "inputType" => "file",
                        "fieldLabel" => "Form",
                        "isRequired" => false,
                        "isReadOnly" => false,
                        "isHidden" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "validation" => [
                        "validationPattern" => "",
                        "mimeType" => ["images", "pdf"],
                        "errorMessageRequired" => "Test Drive Form is required",
                        "errorMessageInvalid" => "Upload a valid Test Form (jpg,jpeg,png,pdf only)",
                        ],
                    ],
                    [
                        "fieldKey" => "completed_date",
                        "inputType" => "calender_time",
                        "calenderType" => "upto_current",
                        "fieldLabel" => "Completed Date",
                        "isRequired" => true,
                        "isReadOnly" => false,
                        "isHidden" => false,
                        "defaultInputValue" => "",
                        "value" => "",
                        "validation" => [
                        "validationPattern" => "",
                        "errorMessageRequired" => "Completed Date is required",
                        "errorMessageInvalid" => "Enter valid Completed Date",
                        ],
                    ],
                    ],
                ],
                ],
            ],
        ];
        return $data;
    }

    private function invoice()
    {
        $data = [
            'sidebar' => (object)[
                'showSidebar' => true, 'sidebarItems' => []
            ],
            'grid' => (object)[
                'title' => "Invoice",
                'pagination' => (object)[
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'start_count' => 0,
                    'end_count' => 0, 
                    'perPageOptions' => [10, 25, 50, 100]
                ],
                'list' => (array)[],
                'header' => (array)[
                    //  [
                    //     'type'=>'button',
                    //     'label' => "Export",
                    //     'icon' => "file-earmark-spreadsheet",
                    //     'validation' => ['show' => true, 'disabled' => false],
                    //     'class' => "btn-outline-dark",
                    //     'conditional' => [
                    //         'onclick' =>[
                    //             'meta' => ['key' => 'export', 'type'=>'get', 'action' => "exportData"],
                    //         ],
                    //     ]
                    // ]
                ],
                'searchConfig' => (object)[
                    'fields' => [
                        [
                            'fieldKey' => 'invoice_number',
                            'inputType' => 'alphanumeric',
                            'fieldLabel' => 'Invoice Number',
                            'fieldHolder' => 'Search by Invoice Number',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 50,
                            'validation' => [
                                'validationPattern' => get_field_regex('alphanumericspecial'),
                                'errorMessageRequired' => 'Invoice Number is required',
                                'errorMessageInvalid' => 'Enter Valid Invoice Number',
                            ],
                        ],
                        [
                            'fieldKey' => 'customer_name',
                            'inputType' => 'alphanumeric',
                            'fieldLabel' => 'Customer Name',
                            'fieldHolder' => 'Search by Customer Name',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 255,
                            'validation' => [
                                'validationPattern' => get_field_regex('name'),
                                'errorMessageRequired' => 'Customer Name is required',
                                'errorMessageInvalid' => 'Enter Valid Customer Name',
                            ],
                        ],
                        [
                            'fieldKey' => 'customer_mobile',
                            'inputType' => 'numeric',
                            'fieldLabel' => 'Customer Mobile',
                            'fieldHolder' => 'Search by Mobile',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 15,
                            'validation' => [
                                'validationPattern' => get_field_regex('mobile'),
                                'errorMessageRequired' => 'Mobile is required',
                                'errorMessageInvalid' => 'Enter Valid Mobile',
                            ],
                        ],
                        [
                            'fieldKey' => 'registration_no',
                            'inputType' => 'alphanumeric',
                            'fieldLabel' => 'Registration No',
                            'fieldHolder' => 'Search by Registration No',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 50,
                            'validation' => [
                                'validationPattern' => get_field_regex('reg_num'),
                                'errorMessageRequired' => 'Registration No is required',
                                'errorMessageInvalid' => 'Enter Valid Registration No',
                            ],
                        ],
                    ],
                ],
                'columns' => [
                    [
                        'title'=> '#',
                        'data'=> [
                            ['key' => ['order_id'], 'label' => 'Order Id', 'type' => 'text' ],
                            ['key' => ['invoice_number'], 'label' => 'Invoice Number', 'type' => 'text' ],
                            ['key' => ['invoice_date'], 'label' => 'Invoice Date', 'type' => 'date' ],
                            ['key' => ['status_name'], 'label' => 'Status', 'type' => 'badge', 'class'=>['0'=>'bg-secondary','1'=>'bg-success'] ],
                        ]
                    ],
                    [
                        'title'=> 'Customer Details',
                        'data'=> [
                            ['key' => ['customer_name'], 'label' => 'Name', 'type' => 'text' ],
                            ['key' => ['customer_mobile'], 'label' => 'Mobile', 'type' => 'text' ],
                            ['key' => ['customer_email'], 'label' => 'Email', 'type' => 'text' ],
                            ['key' => ['customer_pan'], 'label' => 'PAN', 'type' => 'text' ],
                            ['key' => ['customer_gstin'], 'label' => 'GSTIN', 'type' => 'text' ],
                            ['key' => ['customer_billing_address'], 'label' => 'Billing Address', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Vehicle Details',
                        'data'=> [
                            ['key' => ['make_name'], 'label' => 'Make', 'type' => 'text' ],
                            ['key' => ['model_name'], 'label' => 'Model', 'type' => 'text' ],
                            ['key' => ['variant_name'], 'label' => 'Variant', 'type' => 'text' ],
                            ['key' => ['hsn_code'], 'label' => 'HSN Code', 'type' => 'text' ],
                            ['key' => ['mileage'], 'label' => 'Mileage', 'type' => 'text' ],
                            ['key' => ['registration_no'], 'label' => 'Reg No', 'type' => 'text' ],
                            ['key' => ['order_date'], 'label' => 'Order Date', 'type' => 'date' ],
                        ]
                    ],
                    [
                        'title'=> 'Invoice Details',
                        'data'=> [
                            ['key' => [], 'icon'=>'clock-history', 'attachKey'=>'history', 'label' => '', 'type' => 'attach', 'tooltip' => 'Status History' ],
                            ['key' => ['taxable_amt'], 'label' => 'Taxable Amount', 'type' => 'text' ],
                            ['key' => ['cess_rate'], 'label' => 'Cess Rate', 'type' => 'text' ],
                            ['key' => ['discount'], 'label' => 'Discount', 'type' => 'text' ],
                            ['key' => ['tcs_rate'], 'label' => 'TCS Rate', 'type' => 'text' ],
                            ['key' => ['total_amount'], 'label' => 'Total Amount', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'GST & Margin',
                        'data'=> [
                            ['key' => ['sgst_rate'], 'label' => 'SGST Rate', 'type' => 'text' ],
                            ['key' => ['cgst_rate'], 'label' => 'CGST Rate', 'type' => 'text' ],
                            ['key' => ['igst_rate'], 'label' => 'IGST Rate', 'type' => 'text' ],
                            ['key' => ['dealer_margin'], 'label' => 'Dealer Margin', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Actions',
                        'data'=> [
                            [
                                'label' => 'View',
                                'type' => 'link',
                                'class' => "btn-secondary",
                                'icon' => "eye",
                                'meta' => ['type'=>'route', 'action'=>'detail/:id']
                            ],
                        ]
                    ]
                ],
            ],

            'overview' => (object)[
                'meta' => [
                    'title' => 'Invoice Overview',
                    'dataPath' => 'detail',
                    'showImages' => false,
                    'showDocuments' => true,
                    'showButtons' => true,
                    'loadedCheckPath' => 'detail',
                ],

                'fields' => [
                    //Branch Details
                'branch_name' => [ 'category' => 'Branch Details', 'label' => 'Branch Name', 'key' => 'branch_name', 'type' => 'view', 'val' => '' ],
                'branch_address' => [ 'category' => 'Branch Details', 'label' => 'Address', 'key' => 'branch_address', 'type' => 'view', 'val' => '' ], 
                'branch_email' => [ 'category' => 'Branch Details', 'label' => 'Email', 'key' => 'branch_email', 'type' => 'view', 'val' => '' ],
                'branch_mobile' => [ 'category' => 'Branch Details', 'label' => 'Mobile', 'key' => 'branch_mobile', 'type' => 'view', 'val' => '' ],
                'branch_state_name' => [ 'category' => 'Branch Details', 'label' => 'State', 'key' => 'branch_state_name', 'type' => 'view', 'val' => '' ],
                'branch_city_name' => [ 'category' => 'Branch Details', 'label' => 'City', 'key' => 'branch_city_name', 'type' => 'view', 'val' => '' ],                

                // Customer Details
                'customer_type_view' => [ 'category' => 'Customer Info', 'label' => 'Customer Type', 'key' => 'customer_type_view', 'type' => 'view', 'val' => '' ],
                'customer_name' => [ 'category' => 'Customer Info', 'label' => 'Name', 'key' => 'customer_name', 'type' => 'view', 'val' => '' ],
                'customer_mobile' => [ 'category' => 'Customer Info', 'label' => 'Mobile', 'key' => 'customer_mobile', 'type' => 'view', 'val' => '' ],
                'customer_email' => [ 'category' => 'Customer Info', 'label' => 'Email', 'key' => 'customer_email', 'type' => 'view', 'val' => '' ],
                'customer_pan' => [ 'category' => 'Customer Info', 'label' => 'PAN', 'key' => 'customer_pan', 'type' => 'view', 'val' => '' ],
                'customer_gstin' => [ 'category' => 'Customer Info', 'label' => 'GSTIN', 'key' => 'customer_gstin', 'type' => 'view', 'val' => '' ],

                'customer_address' => [ 'category' => 'Customer Address', 'label' => 'Address', 'key' => 'customer_address', 'type' => 'view', 'val' => '' ],
                'customer_pin_code' => [ 'category' => 'Customer Address', 'label' => 'Pin Code', 'key' => 'customer_pin_code', 'type' => 'view', 'val' => '' ],
                'customer_state_name' => [ 'category' => 'Customer Address', 'label' => 'State', 'key' => 'customer_state_name', 'type' => 'view', 'val' => '' ],
                'customer_city_name' => [ 'category' => 'Customer Address', 'label' => 'City', 'key' => 'customer_city_name', 'type' => 'view', 'val' => '' ],

                'customer_billing_address' => [ 'category' => 'Customer Billing Address', 'label' => 'Address', 'key' => 'customer_billing_address', 'type' => 'view', 'val' => '' ],
                'billing_pin_code' => [ 'category' => 'Customer Billing Address', 'label' => ' Pin Code', 'key' => 'billing_pin_code', 'type' => 'view', 'val' => '' ],
                'billing_state_name' => [ 'category' => 'Customer Billing Address', 'label' => 'State', 'key' => 'billing_state_name', 'type' => 'view', 'val' => '' ],
                'billing_city_name' => [ 'category' => 'Customer Billing Address', 'label' => 'City', 'key' => 'billing_city_name', 'type' => 'view', 'val' => '' ],

                // Vehicle Details
                'make_name' => [ 'category' => 'Vehicle Details', 'label' => 'Make', 'key' => 'make_name', 'type' => 'view', 'val' => '' ],
                'model_name' => [ 'category' => 'Vehicle Details', 'label' => 'Model', 'key' => 'model_name', 'type' => 'view', 'val' => '' ],
                'variant_name' => [ 'category' => 'Vehicle Details', 'label' => 'Variant', 'key' => 'variant_name', 'type' => 'view', 'val' => '' ],
                'mileage' => [ 'category' => 'Vehicle Details', 'label' => 'Mileage', 'key' => 'mileage', 'type' => 'view', 'val' => '' ],
                'registration_no' => [ 'category' => 'Vehicle Details', 'label' => 'Registration No', 'key' => 'registration_no', 'type' => 'view', 'val' => '' ],
                'order_id' => [ 'category' => 'Vehicle Details', 'label' => 'Order Id', 'key' => 'order_id', 'type' => 'view', 'val' => '' ],
                'order_date' => [ 'category' => 'Vehicle Details', 'label' => 'Order Date', 'key' => 'order_date', 'type' => 'view', 'val' => '' ],

                // Invoice Details
                'status_name' => [ 'category' => 'Invoice Info', 'label' => 'Status', 'key' => 'status_name', 'type' => 'view', 'val' => '' ],
                'invoice_type' => [ 'category' => 'Invoice Info', 'label' => 'Invoice Type', 'key' => 'invoice_type', 'type' => 'view', 'val' => '' ],
                'invoice_number' => [ 'category' => 'Invoice Info', 'label' => 'Invoice Number', 'key' => 'invoice_number', 'type' => 'view', 'val' => '' ],
                'invoice_date' => [ 'category' => 'Invoice Info', 'label' => 'Invoice Date', 'key' => 'invoice_date', 'type' => 'view', 'val' => '' ],
                'irn_number' => [ 'category' => 'Invoice Info', 'label' => 'IRN Number', 'key' => 'irn_number', 'type' => 'view', 'val' => '' ],
                'invoice_cancellation_date' => [ 'category' => 'Invoice Info', 'label' => 'Invoice Cancellation Date', 'key' => 'invoice_cancellation_date', 'type' => 'view', 'val' => '' ],
                
                'hsn_code' => [ 'category' => 'Invoice Details', 'label' => 'HSN Code', 'key' => 'hsn_code', 'type' => 'view', 'val' => '' ],
                'taxable_amt' => [ 'category' => 'Invoice Details', 'label' => 'Base Price', 'key' => 'Taxable Amount', 'type' => 'view', 'val' => '' ],
                'cess_rate' => [ 'category' => 'Invoice Details', 'label' => 'Cess Rate (%)', 'key' => 'cess_rate', 'type' => 'view', 'val' => '' ],
                'cess_rate_value' => [ 'category' => 'Invoice Details', 'label' => 'Cess Value', 'key' => 'cess_rate_value', 'type' => 'view', 'val' => '' ],
                'discount' => [ 'category' => 'Invoice Details', 'label' => 'Discount', 'key' => 'discount', 'type' => 'view', 'val' => '' ],
                'tcs_rate' => [ 'category' => 'Invoice Details', 'label' => 'TCS Rate (%)', 'key' => 'tcs_rate', 'type' => 'view', 'val' => '' ],
                'tcs_rate_value' => [ 'category' => 'Invoice Details', 'label' => 'TCS Value', 'key' => 'tcs_rate_value', 'type' => 'view', 'val' => '' ],
                'roundoff_amt' => [ 'category' => 'Invoice Details', 'label' => 'Round Off Amount', 'key' => 'roundoff_amt', 'type' => 'view', 'val' => '' ],
                'total_amount' => [ 'category' => 'Invoice Details', 'label' => 'Total Amount', 'key' => 'total_amount', 'type' => 'view', 'val' => '' ],

                // GST & Margin
                'sgst_rate' => [ 'category' => 'GST & Margin', 'label' => 'SGST Rate (%)', 'key' => 'sgst_rate', 'type' => 'view', 'val' => '' ],
                'sgst_rate_value' => [ 'category' => 'GST & Margin', 'label' => 'SGST Rate Value', 'key' => 'sgst_rate_value', 'type' => 'view', 'val' => '' ],
                'cgst_rate' => [ 'category' => 'GST & Margin', 'label' => 'CGST Rate (%)', 'key' => 'cgst_rate', 'type' => 'view', 'val' => '' ],
                'cgst_rate_value' => [ 'category' => 'GST & Margin', 'label' => 'CGST Rate Value', 'key' => 'cgst_rate_value', 'type' => 'view', 'val' => '' ],
                'igst_rate' => [ 'category' => 'GST & Margin', 'label' => 'iGST Rate (%)', 'key' => 'igst_rate', 'type' => 'view', 'val' => '' ],
                'igst_rate_value' => [ 'category' => 'GST & Margin', 'label' => 'iGST Rate Value', 'key' => 'igst_rate_value', 'type' => 'view', 'val' => '' ],
                'dealer_margin' => [ 'category' => 'GST & Margin', 'label' => 'Dealer Margin', 'key' => 'dealer_margin', 'type' => 'view', 'val' => '' ],

                // Loan & Payment Details
                'opted_for_finance_view' => [ 'category' => 'Loan & Payment Details', 'label' => 'Opted For Finance', 'key' => 'opted_for_finance_view', 'type' => 'view', 'val' => '' ],
                'financier' => [ 'category' => 'Loan & Payment Details', 'label' => 'Financier', 'key' => 'financier', 'type' => 'view', 'val' => '' ],
                'finance_amount' => [ 'category' => 'Loan & Payment Details', 'label' => 'Finance Amount', 'key' => 'finance_amount', 'type' => 'view', 'val' => '' ],
                'advance_amount_paid' => [ 'category' => 'Loan & Payment Details', 'label' => 'Advance Amount Paid', 'key' => 'advance_amount_paid', 'type' => 'view', 'val' => '' ],
                'downpayment' => [ 'category' => 'Loan & Payment Details', 'label' => 'Downpayment', 'key' => 'downpayment', 'type' => 'view', 'val' => '' ],
                'emi' => [ 'category' => 'Loan & Payment Details', 'label' => 'EMI', 'key' => 'emi', 'type' => 'view', 'val' => '' ],
                'tenure' => [ 'category' => 'Loan & Payment Details', 'label' => 'Tenure (in months)', 'key' => 'tenure', 'type' => 'view', 'val' => '' ],
                'assured_buyback_view' => [ 'category' => 'Loan & Payment Details', 'label' => 'Assured Buyback', 'key' => 'assured_buyback_view', 'type' => 'view', 'val' => '' ],
                'remarks' => [ 'category' => 'Loan & Payment Details', 'label' => 'Remarks', 'key' => 'remarks', 'type' => 'view', 'val' => '' ],

                //actions
                'save_draft' => ['label' => 'Save As Draft','key' => 'save_draft','type' => 'button', 'auth' => 'dealer',
                    'conditions' => [ ['field' => 'status', 'value' => [1]], ],
                ],

                'issued' => [ 'label' => 'Issue Invoice', 'key' => 'issued', 'type' => 'button', 'auth' => 'dealer',
                    'conditions' => [ ['field' => 'status', 'value' => [1]],],
                ],

                'cancelled' => [ 'label' => 'Cancel Invoice', 'key' => 'cancelled', 'type' => 'button','auth' => 'dealer',
                    'conditions' => [ ['field' => 'status', 'value' => [2]],],
                    'compare' => ['equal' => ['invoice_date']],
                ],

                'request_cancelled' => ['label' => 'Request Cancel Invoice','key' => 'request_cancelled','type' => 'button', 'auth' => 'dealer',
                    'conditions' => [ ['field' => 'status', 'value' => [2]],],
                    'compare' => ['not_equal' => ['invoice_date']],
                ],

                'admin_approve' => [ 'label' => 'Approve Cancel Invoice', 'key' => 'admin_approve', 'type' => 'button', 'auth' => 'admin',
                    'conditions' => [
                        ['field' => 'status', 'value' => [3]],
                        ['field' => 'sub_status', 'value' => [1]],
                    ],
                ],

                'admin_reject' => [ 'label' => 'Reject Cancel Invoice', 'key' => 'admin_reject', 'type' => 'button', 'auth' => 'admin',
                    'conditions' => [
                        ['field' => 'status', 'value' => [3]],
                        ['field' => 'sub_status', 'value' => [1]],
                    ],
                ],

                'request_correction' => [ 'label' => 'Request Correction In Invoice', 'key' => 'request_correction', 'type' => 'button', 'auth' => 'admin',
                    'conditions' => [
                        ['field' => 'status', 'value' => [3]],
                        ['field' => 'sub_status', 'value' => [1]],
                    ],
                ],

                // Documents
                // 'documents' => [ 'category' => 'Documents', 'label' => 'Documents', 'key' => 'documents', 'type' => 'view',  'val' => '', ],
                ]
            ],

            'history' => [
                [
                    'title' => '#',
                    'data' => [
                        ['key' => ['status_name'], 'label' => 'Status', 'type' => 'badge', 'class'=>['0'=>'bg-secondary','1'=>'bg-success'] ],
                        ['key' => ['id'], 'label' => 'ID', 'type' => 'text' ],
                        ['key' => ['updated_name'], 'label' => 'Updated By', 'type' => 'text' ],
                        ['key' => ['updated'], 'label' => 'Updated On', 'type' => 'date' ],   
                    ]                   
                ],
                [
                    'title' => 'Customer Details',
                    'data' => [
                        ['key' => ['customer_name'], 'label' => 'Name', 'type' => 'text' ],
                        ['key' => ['customer_mobile'], 'label' => 'Mobile', 'type' => 'text' ],
                        ['key' => ['customer_email'], 'label' => 'Email', 'type' => 'text' ],
                        ['key' => ['customer_pan'], 'label' => 'PAN', 'type' => 'text' ],
                        ['key' => ['customer_gstin'], 'label' => 'GSTIN', 'type' => 'text' ],
                        ['key' => ['customer_billing_address'], 'label' => 'Billing Address', 'type' => 'text' ],
                    ]                   
                ],
                [
                    'title' => 'Vehicle Details',
                    'data' => [
                        ['key' => ['make_name'], 'label' => 'Make', 'type' => 'text' ],
                        ['key' => ['model_name'], 'label' => 'Model', 'type' => 'text' ],
                        ['key' => ['variant_name'], 'label' => 'Variant', 'type' => 'text' ],
                        ['key' => ['hsn_code'], 'label' => 'HSN Code', 'type' => 'text' ],
                        ['key' => ['mileage'], 'label' => 'Mileage', 'type' => 'text' ],
                        ['key' => ['registration_no'], 'label' => 'Reg No', 'type' => 'text' ],
                        ['key' => ['order_date'], 'label' => 'Order Date', 'type' => 'date' ],
                    ]                   
                ],
                [
                    'title' => 'Invoice Details',
                    'data' => [
                        ['key' => ['taxable_amt'], 'label' => 'Taxable Amount', 'type' => 'text' ],
                        ['key' => ['cess_rate'], 'label' => 'Cess Rate', 'type' => 'text' ],
                        ['key' => ['discount'], 'label' => 'Discount',  'type'=> "text" ],
                        ['key' => ['tcs_rate'], 'label'=> 	'TCS Rate', 'type'=> "text" ],
                        ['key' =>  ['total_amount'], 'label'=> "Total Amount", 'type'=> "text" ],
                    ]
                ],
                [
                    'title' => 'GST & Margin',
                    'data' => [
                        ['key' => ['sgst_rate'], 'label' => 'SGST Rate', 'type' => 'text' ],
                        ['key' => ['cgst_rate'], 'label' => 'CGST Rate', 'type' => 'text' ],
                        ['key' => ['igst_rate'], 'label' => 'IGST Rate', 'type' => 'text' ],
                        ['key' => ['dealer_margin'], 'label' => 'Dealer Margin', 'type' => 'text' ],
                    ]
                ],
                [
                    'title' => 'Loan & Payment Details',
                    'data' => [
                        ['key' => ['opted_for_finance_view'], 'label' => 'Opted For Finance', 'type' => 'text' ],
                        ['key' => ['financier'], 'label' => 'Financier', 'type' => 'text' ],
                        ['key' => ['finance_amount'], 'label' => 'Finance Amount', 'type' => 'text' ],
                        ['key' => ['advance_amount_paid'], 'label' => 'Advance Amount Paid', 'type' => 'text' ],
                        ['key' => ['downpayment'], 'label' => 'Downpayment', 'type' => 'text' ],
                        ['key' => ['emi'], 'label' => 'EMI', 'type' => 'text' ],
                        ['key' => ['tenure'], 'label' => 'Tenure (in months)', 'type' => 'text' ],
                        ['key' => ['assured_buyback_view'], 'label' => 'Assured Buyback', 'type' => 'text' ],
                        ['key' => ['remarks'], 'label' => 'Remarks', 'type' => 'text' ],
                    ]
                ],
            ],

            'customerAddConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "Customer Details",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "branch_details",
                                "sectionTitle" => "Branch Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "branch",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Branch",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Branch is required",
                                            "errorMessageInvalid" => "Please select a valid Branch",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "branch_state",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Branch State",
                                        "save" => false,
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "branch state is required",
                                            "errorMessageInvalid" => "Enter Valid branch state",
                                        ],
                                    ],
                                ],
                                
                            ],  
                            [
                                "sectionId" => "customer_info",
                                "sectionTitle" => "Customer Info",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "customer_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Customer Type",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['customer_type'], 'Registration Type'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('id'),
                                            "errorMessageRequired" => "Customer Type is required",
                                            "errorMessageInvalid" => "Select a valid Customer Type",
                                        ],

                                    ],
                                    [
                                        "fieldKey" => "customer_name",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Customer Name",
                                        "value" => "",
                                        "isRequired" => true,
                                        "maxLength" => 255,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('name'),
                                            "errorMessageRequired" => "Customer Name is required",
                                            "errorMessageInvalid" => "Enter Valid Customer Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_mobile",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Customer Mobile",
                                        "value" => "",
                                        "isRequired" => true,
                                        "maxLength" => 15,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('mobile'),
                                            "errorMessageRequired" => "Mobile is required",
                                            "errorMessageInvalid" => "Enter Valid Mobile",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_email",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Customer Email",
                                        "value" => "",
                                        "isRequired" => true,
                                        "maxLength" => 255,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('email'),
                                            "errorMessageRequired" => "Email is required",
                                            "errorMessageInvalid" => "Enter Valid Email",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_pan",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Customer PAN",
                                        "value" => "",
                                        "isRequired" => true,
                                        "maxLength" => 20,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('pan_number'),
                                            "errorMessageRequired" => "PAN is required",
                                            "errorMessageInvalid" => "Enter Valid PAN",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_gstin",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Customer GSTIN",
                                        "value" => "",
                                        "isRequired" => false,
                                        "maxLength" => 20,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('gstin'),
                                            "errorMessageInvalid" => "Enter Valid GSTIN",
                                        ],
                                    ],
                                ],
                            ],
                            [
                                "sectionId" => "customer_address",
                                "sectionTitle" => "Customer Address",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "customer_pin_code",
                                        "inputType" => "numeric",
                                        "inputChange" => "dynamic_location",
                                        "fieldLabel" => "Pincode",
                                        "fieldHolder" => "Enter Pincode",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 6,
                                        "clearFields" => ["state", "city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Pincode is required",
                                            "errorMessageInvalid" => "Enter Valid Pincode",
                                        ],
                                        'addons' => [
                                            [
                                                "fieldKey" => "copy",
                                                'inputType' => 'action',
                                                "inputChange" => "dynamic_copy",
                                                "inputIcon" => "copy",
                                                "fieldLabel" => "",
                                                'isDisabled' => false,
                                                'tooltip' => "Copy same for Billing Address",
                                                "conditionalApply" => [
                                                    'copyValue' => [
                                                        ['fieldKey'=>'billing_pin_code', 'copyFieldKey' => 'customer_pin_code' ],
                                                        ['fieldKey'=>'customer_billing_address', 'copyFieldKey' => 'customer_address' ],
                                                        ['fieldKey' => 'billing_state','copyFieldKey' => 'customer_state' ],
                                                        ['fieldKey' => 'billing_city','copyFieldKey' => 'customer_city' ],
                                                    ],
                                                ],
                                            ]
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_state",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_state",
                                        "fieldLabel" => "State",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptions" => [],
                                        "dependsOn" => "state",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Model is required",
                                            "errorMessageInvalid" => "Please select a state from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_city",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_city",
                                        "fieldLabel" => "City",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptions" => [],
                                        "dependsOn" => "city",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "City is required",
                                            "errorMessageInvalid" => "Please select a city from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_address",
                                        "inputType" => "text",
                                        "fieldLabel" => "Address",
                                        "fieldHolder" => "Enter Address",
                                        "isRequired" => true,
                                        "maxLength" => 200,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('field_address'),
                                            "errorMessageRequired" => "Address is required",
                                            "errorMessageInvalid" => "Enter Valid Address",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "customer_area",
                                        "inputType" => "text",
                                        "fieldLabel" => "Customer Area",
                                        "value" => "",
                                        "isRequired" => false,
                                        "maxLength" => 255,
                                    ],
                                ],
                            ],
                            [
                                "sectionId" => "customer_billing_address",
                                "sectionTitle" => "Customer Billing Address",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                    "fieldKey" => "customer_billing_address",
                                    "inputType" => "text",
                                    "fieldLabel" => "Address",
                                    "value" => "",
                                    "isRequired" => false,
                                    "maxLength" => 200,
                                    ],
                                    [
                                        "fieldKey" => "billing_pin_code",
                                        "inputType" => "numeric",
                                        "inputChange" => "dynamic_billing_location",
                                        "fieldLabel" => "Pincode",
                                        "fieldHolder" => "Enter Pincode",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "maxLength" => 6,
                                        "clearFields" => ["state", "city"],
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Billing Pincode is required",
                                            "errorMessageInvalid" => "Enter Valid Biiling Pincode",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "billing_state",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_state",
                                        "fieldLabel" => "State",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptions" => [],
                                        "dependsOn" => "state",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "Billing state is required",
                                            "errorMessageInvalid" => "Please select a state from the list",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "billing_city",
                                        "inputType" => "dynamic_dropdown",
                                        "inputMethod" => "dynamic_city",
                                        "fieldLabel" => "City",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptions" => [],
                                        "dependsOn" => "city",
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "City is required",
                                            "errorMessageInvalid" => "Please select a city from the list",
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'invoiceAddConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "Invoice Details",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "invoice_info",
                                "sectionTitle" => "Invoice Info",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [ 
                                        "fieldKey" => "invoice_date", 
                                        "inputType" => "date", 
                                        "fieldLabel" => "Invoice Date", 
                                        "value" => "",
                                        "isRequired" => true 
                                    ],
                                    [
                                        "fieldKey" => "invoice_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Invoice Type",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions($this->commonConfig['invoice_type'] ?? [], 'invoice' ),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "Invoice Type is required",
                                            "errorMessageInvalid" => "Select a valid Invoice Type",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "irn_number",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "IRN Number",
                                        "value" => "",
                                        "isRequired" => true,
                                        "maxLength" => 64,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageInvalid" => "Enter Valid IRN Number",
                                        ],
                                    ]
                                ],
                            ],
                            [
                                "sectionId" => "invoice_details",
                                "sectionTitle" => "Invoice Details",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "branch_state",
                                        "inputType" => "numeric",
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "Branch State",
                                        "save" => false,
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "branch state is required",
                                            "errorMessageInvalid" => "Enter Valid branch state",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "billing_state",
                                        "inputType" => "numeric",
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "Billing State",
                                        "save" => false,
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "billing state is required",
                                            "errorMessageInvalid" => "Enter Valid billing state",
                                        ],
                                    ],
                                    // [
                                    //     "fieldKey" => "hsn_code",
                                    //     "inputType" => "dropdownIds",
                                    //     "fieldLabel" => "HSN Code",
                                    //     "isRequired" => false,
                                    //     "isReadOnly" => false,
                                    //     "defaultInputValue" => "",
                                    //     "value" => "",
                                    //     "fieldOptionIds" => $this->buildDynamicOptions('HSN', 'getHsnCodes' ),
                                    //     "validation" => [
                                    //         "validationPattern" => get_field_regex('numeric'),
                                    //         "errorMessageRequired" => "Branch is required",
                                    //         "errorMessageInvalid" => "Please select a valid Branch",
                                    //     ],
                                    
                                    // ],
                                    [ 
                                        "fieldKey" => "taxable_amt", 
                                        "inputType" => "numeric_format", 
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "Taxable Amount", 
                                        "value" => "",
                                        "isRequired" => true 
                                    ],
                                    [ 
                                        "fieldKey" => "sgst_rate",
                                        "inputType" => "dropdownIds", 
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "SGST Rate (%)", 
                                        "value" => "",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "setFields" => ["sgst_rate_value"],
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '0'  => '0%',
                                                            '2.5'  => '2.5%',
                                                            '9' => '9%',
                                                            '20' => '20%',], 'GST'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "SGST is required",
                                            "errorMessageInvalid" => "Please select valid SGST",
                                        ],
                                        "conditionalApply" => [
                                            'isDisabled' => [
                                                ['fieldKey'=>'sgst', 'compare' => ['notequal' => ['billing_state', 'branch_state']] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'sgst', 'compare' => ['equal' => ['billing_state', 'branch_state']] ],
                                            ],
                                        ], 
                                    ],
                                    [ 
                                        "fieldKey" => "cgst_rate",
                                        "inputType" => "dropdownIds", 
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "CGST Rate (%)", 
                                        "value" => "",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "setFields" => ["cgst_rate_value"],
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '0'  => '0%',
                                                            '2.5'  => '2.5%',
                                                            '9' => '9%',
                                                            '20' => '20%',], 'GST'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "CGST is required",
                                            "errorMessageInvalid" => "Please select valid CGST",
                                        ],
                                        "conditionalApply" => [
                                            'isDisabled' => [
                                                ['fieldKey'=>'cgst', 'compare' => ['notequal' => ['billing_state', 'branch_state']] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'cgst', 'compare' => ['equal' => ['billing_state', 'branch_state']] ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "igst_rate",
                                        "inputType" => "dropdownIds", 
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "IGST Rate (%)", 
                                        "value" => "",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "setFields" => ["igst_rate_value"],
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '0'  => '0%',
                                                            '5'  => '5%',
                                                            '18' => '18%',
                                                            '40' => '40%',], 'GST'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "IGST is required",
                                            "errorMessageInvalid" => "Please select valid IGST",
                                        ],
                                        "conditionalApply" => [
                                            'isDisabled' => [
                                                ['fieldKey'=>'igst', 'compare' => ['equal' => ['billing_state', 'branch_state']] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'igst', 'compare' => ['notequal' => ['billing_state', 'branch_state']] ],
                                            ],
                                             
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "cess_rate",
                                        "inputType" => "dropdownIds", 
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "CESS Rate (%)", 
                                        "value" => "",
                                        "isRequired" => true,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "setFields" => ["cess_rate_value"],
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '20' => '20%',
                                                            '22' => '22%',
                                                            'NA' => 'N/A' ], 'GST'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "CESS is required",
                                            "errorMessageInvalid" => "Please select valid CESS",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "tcs_rate",
                                        "inputType" => "dropdownIds", 
                                        "inputChange" => "dynamic_amount",
                                        "fieldLabel" => "TCS Rate (%)", 
                                        "value" => "",
                                        "isRequired" => true,
                                        "isHidden" => false,
                                        "defaultInputValue" => "",
                                        "setFields" => ["tcs_rate_value"],
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '0' => '0%',
                                                            '1' => '1%',
                                                            '2' => '2%',
                                                            'NA' => 'N/A' ], 'GST'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageRequired" => "TCS is required",
                                            "errorMessageInvalid" => "Please select valid TCS",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "sgst_rate_value",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "SGST Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isDisabled" => true, 
                                        "save" => false,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageInvalid" => "Please select valid amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "cgst_rate_value",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "CGST Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isDisabled" => true, 
                                        "save" => false,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageInvalid" => "Please select valid amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "igst_rate_value",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "IGST Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isDisabled" => true, 
                                        "save" => false,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageInvalid" => "Please select valid amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "cess_rate_value",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "CESS Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isDisabled" => true, 
                                        "save" => false,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageInvalid" => "Please select valid amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "tcs_rate_value",
                                        "inputType" => "numeric_format",
                                        "fieldLabel" => "TCS Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => true,
                                        "isDisabled" => true, 
                                        "save" => false,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageInvalid" => "Please select valid amount",
                                        ],
                                    ],
                                    [ 
                                        "fieldKey" => "discount", 
                                        "inputType" => "numeric_format", 
                                        "fieldLabel" => "Discount",
                                        "value" => "", 
                                        "isRequired" => false 
                                    ],
                                    [ 
                                        "fieldKey" => "dealer_margin", 
                                        "inputType" => "numeric_format", 
                                        "fieldLabel" => "Dealer Margin", 
                                        "value" => "",
                                        "isRequired" => false 
                                    ],
                                    [ 
                                        "fieldKey" => "total_amount", 
                                        "inputType" => "numeric_format", 
                                        "fieldLabel" => "Final Invoice Amount", 
                                        "value" => "",
                                        "isReadOnly" => true,
                                        "isRequired" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageRequired" => "Total amount is required",
                                            "errorMessageInvalid" => "Please select valid amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "roundoff_amt", 
                                        "inputType" => "numeric_format", 
                                        "fieldLabel" => "Round Off Amount", 
                                        "value" => "",
                                        "isRequired" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('double'),
                                            "errorMessageRequired" => "Round off amount is required",
                                            "errorMessageInvalid" => "Please select valid round off amount",
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'paymentAddConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "Payment Details",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "loan_payment_info",
                                "sectionTitle" => "Loan / Payment Info",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [
                                        "fieldKey" => "opted_for_finance",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Opted for Finance / Hypothetication",
                                        "isRequired" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions([
                                                            'y' => 'Yes',
                                                            'n' => 'No',], 'finance'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "This field is required",
                                            "errorMessageInvalid" => "Select a valid option",
                                        ],
                                        "conditionalApply" => [
                                            'isHidden' => [
                                                ['fieldKey'=>'finance_type', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'financier', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'finance_amount', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'finance_doc', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'advance_amount_paid', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'downpayment', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'rate_of_intrest', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'emi', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'tenure', 'not_equal' => ['1'] ],
                                                ['fieldKey'=>'assured_buyback', 'not_equal' => ['1'] ],
                                            ],
                                            'isRequired' => [
                                                ['fieldKey'=>'finance_type', 'equal' => ['1'] ],
                                                ['fieldKey'=>'financier', 'equal' => ['1'] ],
                                                ['fieldKey'=>'finance_amount', 'equal' => ['1'] ],
                                                ['fieldKey'=>'downpayment', 'equal' => ['1'] ],
                                                ['fieldKey'=>'rate_of_intrest', 'equal' => ['1'] ],
                                                ['fieldKey'=>'emi', 'equal' => ['1'] ],
                                                ['fieldKey'=>'tenure', 'equal' => ['1'] ],
                                            ],
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "finance_type",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Finance_type",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "isReadOnly" => false,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '1' => 'Self',
                                                            '2' => 'Other',], 'Finance Type'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageRequired" => "This field is required",
                                            "errorMessageInvalid" => "Select a valid option",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "financier",
                                        "inputType" => "alphanumeric",
                                        "fieldLabel" => "Financier",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "maxLength" => 100,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('alphanumeric'),
                                            "errorMessageInvalid" => "Enter Valid Financier Name",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "finance_amount",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Finance Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageInvalid" => "Enter Valid Finance Amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "finance_doc",
                                        "inputType" => "file",
                                        "fieldLabel" => "Document",
                                        "value" => "",
                                        "defaultInputValue" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "maxLength" => 5,
                                        "validation" => [
                                            "validationPattern" => ['images', 'pdf'],
                                            "errorMessageRequired" => "Finance Document is required",
                                            "errorMessageInvalid" => "Upload a valid Finance Document (jpg,png,pdf)",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "advance_amount_paid",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Advance Amount Paid",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageInvalid" => "Enter Valid Advance Amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "downpayment",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Downpayment Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageInvalid" => "Enter Valid Downpayment Amount",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "rate_of_intrest",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "Rate of Interest (%)",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageInvalid" => "Enter Valid Rate of Interest",   
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "emi",
                                        "inputType" => "numeric",
                                        "fieldLabel" => "EMI Amount",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageInvalid" => "Enter Valid EMI Amount",
                                        ]
                                    ],
                                    [
                                        "fieldKey" => "tenure",
                                        "inputType" => "dropdownIds",
                                        "defaultInputValue" => "",
                                        "fieldOptionIds" => $this->buildOptions([
                                                            '6'  => '6', '12' => '12', '18' => '18', 
                                                            '24' => '24', '36'=> '36', "48" => '48',], 'tenure'),
                                        "fieldLabel" => "Tenure (in months)",
                                        "value" => "",
                                        "isRequired" => false,
                                        "isHidden" => true,
                                        "validation" => [
                                            "validationPattern" => get_field_regex('numeric'),
                                            "errorMessageInvalid" => "Select Valid Tenure",
                                        ]
                                    ],
                                    [
                                        "fieldKey" => "assured_buyback",
                                        "inputType" => "dropdownIds",
                                        "fieldLabel" => "Assured Buyback",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => true,
                                        "defaultInputValue" => "",
                                        "value" => "",
                                        "fieldOptionIds" => $this->buildOptions([
                                                            'y' => 'Yes', 'n' => 'No',], 'Assured Buyback'),
                                        "validation" => [
                                            "validationPattern" => get_field_regex('active'),
                                            "errorMessageRequired" => "This field is required",
                                            "errorMessageInvalid" => "Select a valid option",
                                        ]
                                    ],
                                    [
                                        "fieldKey" => "remarks",
                                        "inputType" => "text",
                                        "fieldLabel" => "Remarks",
                                        "isRequired" => true,
                                        "value" => "",
                                        "isRequired" => false,
                                        "maxLength" => 500,
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'documentsAddConfig' => (object)[
                "fields" => [
                    [
                        "fieldLabel" => "Documents",
                        "formType" => "expandable_form",
                        "sections" => [
                            [
                                "sectionId" => "documents",
                                "sectionTitle" => "Documents",
                                "isExpandedByDefault" => true,
                                "fields" => [
                                    [ 
                                        "fieldKey" => "pan_card_path", 
                                        "inputType" => "file", 
                                        "fieldLabel" => "PAN Card", 
                                        "value" => "",
                                        "defaultInputValue" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "maxLength" => 5,
                                        "validation" => [
                                            "validationPattern" => ['images', 'pdf'],
                                            "errorMessageRequired" => "pan card is required",
                                            "errorMessageInvalid" => "Upload a valid PAN card (jpg,png,pdf)",
                                        ],
                                    ],
                                    [ 
                                        "fieldKey" => "gst_certificate_path", 
                                        "inputType" => "file", 
                                        "fieldLabel" => "GST Certificate",
                                        "value" => "",
                                        "defaultInputValue" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "maxLength" => 5,
                                        "validation" => [
                                            "validationPattern" => ['images', 'pdf'],
                                            "errorMessageRequired" => "GST Certificate is required",
                                            "errorMessageInvalid" => "Upload a valid GST Certificate (jpg,png,pdf)",
                                        ],
                                    ],
                                    [
                                        "fieldKey" => "address_proof",
                                        "inputType" => "file",
                                        "fieldLabel" => "Address Proof",
                                        "value" => "",
                                        "defaultInputValue" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "maxLength" => 5,
                                        "validation" => [
                                            "validationPattern" => ['images', 'pdf'],
                                            "errorMessageRequired" => "Address Proof is required",
                                            "errorMessageInvalid" => "Upload a valid Address Proof (jpg,png,pdf)",
                                        ]
                                    ],
                                    [
                                        "fieldKey" => "identity_proof",
                                        "inputType" => "file",
                                        "fieldLabel" => "Identity Proof",
                                        "value" => "",
                                        "defaultInputValue" => "",
                                        "isRequired" => false,
                                        "isReadOnly" => false,
                                        "isHidden" => false,
                                        "maxLength" => 5,
                                        "validation" => [
                                            "validationPattern" => ['images', 'pdf'],
                                            "errorMessageRequired" => "Identity Proof is required",
                                            "errorMessageInvalid" => "Upload a valid Identity Proof (jpg,png,pdf)",
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
                              
        return $data;
    }
    


    private function exchange()
    {
        GLOBAL $config;
        
        $data =  [
            'sidebar' => (object)[
                'showSidebar' => true, 'sidebarItems' => []
            ],
            'grid' => (object)[
                'title' => "Exchange",
                'pagination' => (object)[
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'start_count' => 0,
                    'end_count' => 0, 
                    'perPageOptions' => [10, 25, 50, 100]
                ],
                'list' => (array)[],
                'header' => (array)[
                    // [
                    //     'type'=>'button',
                    //     'label' => "Export",
                    //     'icon' => "file-earmark-spreadsheet",
                    //     'validation' => ['show' => true, 'disabled' => false],
                    //     'class' => "btn-outline-dark",
                    //     'conditional' => [
                    //         'onclick' =>[
                    //             'meta' => ['key' => 'export', 'type'=>'get', 'action' => "exportData"],
                    //         ],
                    //     ]
                    // ]
                ],

                'searchConfig' => (object)[
                        'fields' => [
                            [
                                'fieldKey' => 'customer_name',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Name',
                                'fieldHolder' => 'Enter Customer Name',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 50,
                                'validation' => [
                                    'validationPattern' => get_field_regex('alphanumeric'),
                                    'errorMessageRequired' => 'Name is required',
                                    'errorMessageInvalid' => 'Enter Valid Name',
                                ],
                            ],
                            [
                                'fieldKey' => 'mobile',
                                'inputType' => 'numeric',
                                'fieldLabel' => 'Mobile',
                                'fieldHolder' => 'Enter Mobile Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 10,
                                'validation' => [
                                    'validationPattern' => get_field_regex('mobile'),
                                    'errorMessageRequired' => 'Mobile is required',
                                    'errorMessageInvalid' => 'Enter Valid Mobile',
                                ],
                            ],
                            [
                                'fieldKey' => 'reg_num',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => 'Registration Number',
                                'fieldHolder' => 'Enter Registration Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 100,
                                'isCaps'=>true,
                                'validation' => [
                                    'validationPattern' => get_field_regex('reg_num'),
                                    'errorMessageRequired' => 'RegNo is required',
                                    'errorMessageInvalid' => 'Enter Valid RegNo',
                                ],
                            ],
                            [
                                'fieldKey' => 'chassis',
                                'inputType' => 'alphanumeric',
                                'fieldLabel' => ' VIN / Chassis Number',
                                'fieldHolder' => 'Enter VIN / Chassis Number',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' => 100,
                                'isCaps'=>true,
                                'validation' => [
                                    'validationPattern' => get_field_regex('reg_num'),
                                    'errorMessageRequired' => 'Chassis is required',
                                    'errorMessageInvalid' => 'Enter Valid Chassis',
                                ],
                            ],
                            [
                                'fieldKey' => 'date',
                                'inputType' =>'date',
                                'fieldHolder' =>'Select Date',
                                'isRequired' => false,
                                'isReadOnly' => false,
                                'defaultInputValue' => '',
                                'value' => '',
                                'maxLength' =>'',
                                'isCaps'=>true,
                                'validation' => [
                                ]

                            ]
                        ],
                    ],


               'columns' => [
                    [
                        'title'=> '#',
                        'data'=> [
                            ['key' => ['formatted_id'], 'label' => 'ID', 'type' => 'text' ],
                            ['key' => ['inventory_id'], 'label' => 'Inventory ID', 'type' => 'text' ],
                            ['key' => ['created'], 'label' => 'Created', 'type' => 'date' ],
                            ['key' => ['source_name', 'source_sub_name'], 'label' => 'Source', 'type' => 'concat' ],
                        ]
                    ],
                    [
                        'title'=> 'Customer Details',
                        'data'=> [
                            ['key' => ['title', 'first_name', 'last_name'], 'label' => 'Name', 'type' => 'text' ],
                            ['key' => ['mobile'], 'label' => 'Mobile', 'type' => 'text' ],
                            ['key' => ['city_name', 'state_name', 'pin_code'], 'label' => 'Location', 'type' => 'concat' ],
                        ]
                    ],
                    [
                        'title'=> 'Used Vehicle Details',
                        'data'=> [
                            ['key' => ['make_name', 'model_name', 'variant_name'], 'label' => 'MMV', 'type' => 'concat' ],
                            ['key' => ['chassis'], 'label' => 'Chassis', 'type' => 'text' ],
                            ['key' => ['reg_num'], 'label' => 'Reg No', 'type' => 'text' ],
                            ['key' => ['reg_date'], 'label' => 'Reg Date', 'type' => 'date' ],
                        ]
                    ],
                    [
                        'title'=> 'New Vehicle Details',
                        'data'=> [
                             ['key' => ['new_chassis'], 'label' => 'New Car Chassis', 'type' => 'text' ],
                             ['key' => ['benefit_flag'], 'label' => 'Offer Exchange Benefit', 'type' => 'text' ],
                             ['key' => ['bonus_price'], 'label' => 'New Car Bonus Price', 'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Actions',
                        'data'=> [
                           ['key' => [], 'icon'=>'btn-outline-secondary', 'attachKey'=>'add_exch_vin_bonus', 'label' => 'UPDATE', 'type' => 'attach', 'role_main' => 'y' ],
                        ]
                    ]
                ],
            ],
            'overview'=>(object)[
                'meta' => [
                    'title' => 'Exchange Lead Overview',
                    'dataPath' => 'detail',
                    'showImages' => true,
                    'showDocuments' => true,
                    'showButtons' => true,
                    'loadedCheckPath' => 'detail',
                ],

                'fields' => [
                    // Lead Info
                    'id' => [ 'category' => 'Lead Info', 
                         'type' => 'view',
                         'key' => 'formatted_id',
                         'label' => 'ID', 
                          'val' => '',
                        ],
                'status' => [ 'category' => 'Lead Info', 'label' => 'Status', 'key' => 'status_name', 'type' => 'view',  'val' => '', ],

                // Customer Details
                'full_name' => [ 'category' => 'Customer Details', 'label' => 'Customer', 'key' => ['title', 'first_name','last_name'], 'type' => 'view',  'val' => '', ],
                'mobile' => [ 'category' => 'Customer Details', 'label' => 'Mobile', 'key' => 'mobile', 'type' => 'view',  'val' => '', ],
                'email' => [ 'category' => 'Customer Details', 'label' => 'Email', 'key' => 'email', 'type' => 'view',  'val' => '', ],
                'contact_name' => [ 'category' => 'Customer Details', 'label' => 'Contact Person', 'key' => 'contact_name', 'type' => 'view',  'val' => '', ],

                // Vehicle Details
                'reg_type' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Type', 'key' => 'reg_type_name', 'type' => 'view', 'val' => '' ],
                'reg_num' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Number', 'key' => 'reg_num', 'type' => 'view',  'val' => '', ],
                'reg_date' => [ 'category' => 'Vehicle Details', 'label' => 'Registration Date', 'key' => 'reg_date', 'type' => 'view',  'val' => '', ],
                'make' => [ 'category' => 'Vehicle Details', 'label' => 'Make', 'key' => 'make_name', 'type' => 'view',  'val' => '', ],
                'model' => [ 'category' => 'Vehicle Details', 'label' => 'Model', 'key' => 'model_name', 'type' => 'view',  'val' => '', ],
                'variant' => [ 'category' => 'Vehicle Details', 'label' => 'Variant', 'key' => 'variant_name', 'type' => 'view',  'val' => '', ],
                'chassis' => [ 'category' => 'Vehicle Details', 'label' => 'Chassis Number', 'key' => 'chassis', 'type' => 'view',  'val' => '', ],
                'mfg_year' => [ 'category' => 'Vehicle Details', 'label' => 'Manufacture Year', 'key' => 'mfg_year', 'type' => 'view',  'val' => '', ],
                'mfg_month' => [ 'category' => 'Vehicle Details', 'label' => 'Manufacture Month', 'key' => 'mfg_month', 'type' => 'view',  'val' => '', ],
                'transmission' => [ 'category' => 'Vehicle Details', 'label' => 'Transmission', 'key' => 'transmission_name', 'type' => 'view', 'val' => '' ],
                'mileage' => [ 'category' => 'Vehicle Details', 'label' => 'Mileage', 'key' => 'mileage', 'type' => 'view',  'val' => '', ],
                'fuel' => [ 'category' => 'Vehicle Details', 'label' => 'Fuel Type', 'key' => 'fuel_name', 'type' => 'view', 'val' => '' ],
                'color' => [ 'category' => 'Vehicle Details', 'label' => 'Exterior Color', 'key' => 'color_name', 'type' => 'view', 'val' => '' ],
                'base_color' => [ 'category' => 'Vehicle Details', 'label' => 'Base Color', 'key' => 'base_color_name', 'type' => 'view', 'val' => '' ],
                'interior_color' => [ 'category' => 'Vehicle Details', 'label' => 'Interior Color', 'key' => 'interior_color_name', 'type' => 'view', 'val' => '' ],
                'interior_base_color' => [ 'category' => 'Vehicle Details', 'label' => 'Interior Base Color', 'key' => 'interior_base_color_name', 'type' => 'view', 'val' => '' ],

                // Location Info
                'state' => [ 'category' => 'Location Info', 'label' => 'State', 'key' => 'state_name', 'type' => 'view',  'val' => '', ],
                'city' => [ 'category' => 'Location Info', 'label' => 'City', 'key' => 'city_name', 'type' => 'view',  'val' => '', ],
                'address' => [ 'category' => 'Location Info', 'label' => 'Address', 'key' => 'address', 'type' => 'view',  'val' => '', ],
                'pin_code' => [ 'category' => 'Location Info', 'label' => 'Pin Code', 'key' => 'pin_code', 'type' => 'view',  'val' => '', ],

                // Dealership Info
                'dealer' => [ 'category' => 'Dealership Info', 'label' => 'Dealer', 'key' => 'dealer_name', 'type' => 'view',  'val' => '', ],
                'executive' => [ 'category' => 'Dealership Info', 'label' => 'Executive', 'key' => 'user_name', 'type' => 'view',  'val' => '', ],
                'source' => [ 'category' => 'Dealership Info', 'label' => 'Source', 'key' => 'source_name', 'type' => 'view',  'val' => '', ],
                'source_sub' => [ 'category' => 'Dealership Info', 'label' => 'Sub Source', 'key' => 'source_sub_name', 'type' => 'view',  'val' => '', ],

                // Other Details
                'owners' => [ 'category' => 'Other Details', 'label' => 'No. of Owners', 'key' => 'owners_name', 'type' => 'view', 'val' => '' ],
                'hypothecation' => [ 'category' => 'Other Details', 'label' => 'Hypothecation', 'key' => 'hypothecation_name', 'type' => 'view', 'val' => '' ],
                'insurance_type' => [ 'category' => 'Other Details', 'label' => 'Insurance Type', 'key' => 'insurance_type_name', 'type' => 'view', 'val' => '' ],
                'insurance_exp_date' => [ 'category' => 'Other Details', 'label' => 'Insurance Expiry Date', 'key' => 'insurance_exp_date', 'type' => 'view',  'val' => '', ],

                // Dates Info
                'followup_date' => [ 'category' => 'Dates Info', 'label' => 'Follow-up Date', 'key' => 'followup_date', 'type' => 'view',  'val' => '', ],
                'created' => [ 'category' => 'Dates Info', 'label' => 'Created Date', 'key' => 'created', 'type' => 'view',  'val' => '', ],
                'updated' => [ 'category' => 'Dates Info', 'label' => 'Updated Date', 'key' => 'updated', 'type' => 'view',  'val' => '', ],

                // Media
                'images' => [ 'category' => 'Images', 'label' => 'Images', 'key' => 'images', 'type' => 'view',  'val' => '', ],
                'documents' => [ 'category' => 'Documents', 'label' => 'Documents', 'key' => 'documents', 'type' => 'view',  'val' => '', ],
                ]
            ],

           'history' => [ 
            ],

            'addConfig' => (object)[
                "fields" => [],
            ],
        ];
        return  $data;
    }

    private function dentMap()
    {
        $dent['config'] =  [
            "bodyTypes" => [
                [
                    "bodyType" => "suv"
                ],
                [
                    "bodyType" => "Hatchback",
                    "positions" => [
                        [
                            "position" => "Front-Right",
                            "damageparts" => [
                                ["part" => "Bumper corner", "color" => "#00C15A"],
                                ["part" => "Door", "color" => "#2D1DDF"],
                                ["part" => "Door handle", "color" => "#978FEB"],
                                ["part" => "Door glass", "color" => "#B4B0E1"],
                                ["part" => "Quarter glass", "color" => ""],
                                ["part" => "Tyre", "color" => "#000000"],
                                ["part" => "Rim", "color" => "#7E3433"],
                            ]
                        ],
                        [
                            "position" => "Front-Left",
                            "damageparts" => [
                                ["part" => "Bumper corner", "color" => "#007235"],
                                ["part" => "Door", "color" => "#27207E"],
                                ["part" => "Door handle", "color" => "#4A458B"],
                                ["part" => "Door glass", "color" => "#7B78A1"],
                                ["part" => "Quarter glass", "color" => ""],
                                ["part" => "Tyre", "color" => "#666666"],
                                ["part" => "Rim", "color" => "#A34746"],
                            ]
                        ],
                        [
                            "position" => "Rear-Right",
                            "damageparts" => [
                                ["part" => "Bumper corner", "color" => "#75D62B"],
                                ["part" => "Door", "color" => "#1D8EDF"],
                                ["part" => "Door handle", "color" => "#69AFE1"],
                                ["part" => "Door glass", "color" => "#94C6E9"],
                                ["part" => "Quarter glass", "color" => "#DDDD52"],
                                ["part" => "Tyre", "color" => "#464545"],
                                ["part" => "Rim", "color" => "#983C3B"],
                            ]
                        ],
                        [
                            "position" => "Rear-Left",
                            "damageparts" => [
                                ["part" => "Bumper corner", "color" => "#539223"],
                                ["part" => "Door", "color" => "#20517E"],
                                ["part" => "Door handle", "color" => "#6C7C8B"],
                                ["part" => "Door glass", "color" => "#B0BBC6"],
                                ["part" => "Quarter glass", "color" => "#B8AE70"],
                                ["part" => "Tyre", "color" => "#8B8B8B"],
                                ["part" => "Rim", "color" => "#854847"],
                            ]
                        ],
                        [
                            "position" => "Front",
                            "damageparts" => [
                                ["part" => "Bumper", "color" => "#4FCEE7"],
                                ["part" => "Windshield glass", "color" => "#DFBA72"],
                            ]
                        ],
                        [
                            "position" => "Rear",
                            "damageparts" => [
                                ["part" => "Bumper", "color" => "#977272"],
                                ["part" => "Windshield glass", "color" => "#6F2423"],
                            ]
                        ],
                        [
                            "position" => "Right",
                            "damageparts" => [
                                ["part" => "A pillar", "color" => "#20B240"],
                                ["part" => "B pillar", "color" => "#24A24E"],
                                ["part" => "C pillar", "color" => "#299C48"],
                                ["part" => "Fender", "color" => "#BD0661"],
                                ["part" => "Headlight", "color" => "#7F5352"],
                                ["part" => "ORVM", "color" => "#915B5B"],
                                ["part" => "Running boards", "color" => "#717070"],
                                ["part" => "Quarter panel", "color" => "#662726"],
                                ["part" => "Tail light", "color" => "#4CF677"],
                                ["part" => "Fog Lamp", "color" => "#33688E"],
                                ["part" => "Fog lamp cover", "color" => "#2864FB"],
                                ["part" => "Reflecter", "color" => "#FE5B5B"],
                            ]
                        ],
                        [
                            "position" => "Left",
                            "damageparts" => [
                                ["part" => "A pillar", "color" => "#22D6AC"],
                                ["part" => "B pillar", "color" => "#23A99B"],
                                ["part" => "C pillar", "color" => "#2E6F6E"],
                                ["part" => "Fender", "color" => "#9321E5"],
                                ["part" => "Headlight", "color" => "#9F5D5C"],
                                ["part" => "ORVM", "color" => "#681413"],
                                ["part" => "Running boards", "color" => "#393939"],
                                ["part" => "Quarter panel", "color" => "#592424"],
                                ["part" => "Tail light", "color" => "#21E552"],
                                ["part" => "Fog lamp", "color" => "#214EFF"],
                                ["part" => "Fog lamp cover", "color" => "#879AE4"],
                                ["part" => "Reflecter", "color" => "#FF9593"],
                            ]
                        ],
                        [
                            "position" => "Other",
                            "damageparts" => [
                                ["part" => "Bulkhead", "color" => ""],
                                ["part" => "Bonnet/Hood", "color" => "#617D47"],
                                ["part" => "Dickey", "color" => "#6BB366"],
                                ["part" => "Grills", "color" => "#717D80"],
                                ["part" => "Roof", "color" => "#8C5857"],
                            ]
                        ],
                    ]
                ]
            ],
            "damagetype" => [
                ["dtid" => "1", "name" => "Dent", "identity" => "DE"],
                ["dtid" => "2", "name" => "Crack", "identity" => "CR"],
                ["dtid" => "3", "name" => "Scratch", "identity" => "SC"],
                ["dtid" => "4", "name" => "Cold Dent", "identity" => "CD"],
                ["dtid" => "5", "name" => "Corrosion", "identity" => "CS"],
                ["dtid" => "6", "name" => "Repainted", "identity" => "RE"],
                ["dtid" => "7", "name" => "Paint Defects", "identity" => "PD"],
                ["dtid" => "8", "name" => "Replaced", "identity" => "RP"],
                ["dtid" => "9", "name" => "Repaired", "identity" => "RD"],
                ["dtid" => "10", "name" => "Seal Open", "identity" => "SO"],
            ]
        ];

        return $dent;
    }

    private function locations()
    {
        $data = [
            'grid' => (object)[
                'title' => 'Locations',
                'pagination' => (object)[
                    'total' => 0,
                    'pages' => 0,
                    'current_page' => 1,
                    'start_count' => 0,
                    'end_count' => 0,
                    'perPageOptions' => [10, 25, 50, 100]
                ],
                'list' => (array)[],
                'searchConfig' => (object)[
                    'fields' => [
                        [
                            'fieldKey' => 'cw_state',
                            'inputType' => 'dynamic_dropdown',
                            "inputChange" => "dynamic_cities",
                            'fieldLabel' => 'State',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            "isSearch" => true,
                            "fieldOptions" => [],
                            "clearFields" => ["cw_city"],
                        ],
                        [
                            'fieldKey' => 'cw_city',
                            'inputType' => 'dynamic_dropdown',
                            "inputMethod" => "",
                            'fieldLabel' => 'City',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            "isSearch" => true,
                            "fieldOptions" => [],
                        ],
                        [
                            'fieldKey' => 'cw_zip',
                            'inputType' => 'alphanumeric',
                            'fieldLabel' => 'Pincode',
                            'fieldHolder' => 'Enter Pincode',
                            'isRequired' => false,
                            'isReadOnly' => false,
                            'defaultInputValue' => '',
                            'value' => '',
                            'maxLength' => 100,
                            // 'isCaps'=>true,
                        ],
                    ],
                ],
                'columns' => [
                    [
                        'title'=> 'S No',
                        'data'=> [
                            ['key' => ['sno'],'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'State',
                        'data'=> [
                            ['key' => ['cw_state'],'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'City',
                        'data'=> [
                            ['key' => ['cw_city'],'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'Pincode',
                        'data'=> [
                            ['key' => ['cw_zip'],'type' => 'text' ],
                        ]
                    ],
                    [
                        'title'=> 'CW City ID',
                        'data'=> [
                            ['key' => ['cw_city_id'],'type' => 'text' ],
                        ]
                    ],
                ],
            ],
        ];
        return  $data;
    }
    private function dashboardNew(){
        $data=[
                "user"=>(array)[
                    "type"  =>  "dealer",
                    "role"  =>  "UCM"
                ],
                "dashboard-catg"=>(array)[
                    "over-view" => "OverView",
                    "evaluations" => "Evaluations",
                    "stocks" => "Stocks",
                    "over-all-sales" => "OverAll Sales",
                    "executive-performance" => "Executive Performance"
                ],
                "dashboard-elements"=>(array)[
                    "over-view" =>[
                        "m_title" =>"Dashboard Overview",
                        "branches"=>$this->buildOptions($this->commonConfig['branch'] ?? [], 'Branch'),
                        "section" =>[
                            "left-block-1"=>[
                                "title"=>"Today's Overview",
                                "data"=>[
                                    "ele-0" =>[
                                        "sub_title"=>"Evaluations Required",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images" =>[],
                                        "tag"   =>""
                                    ],
                                    "ele-1" =>[
                                        "sub_title"=>"Evaluations Done",
                                        "Total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images" =>[],
                                        "tag"   =>""
                                    ],
                                    "ele-2" =>[
                                        "sub_title"=>"Trade-in Done",
                                        "Total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images" =>[],
                                        "tag"   =>""
                                    ],
                                    "ele-3" =>[
                                        "sub_title"=>"Sales Leads",
                                        "Total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images" =>[],
                                        "tag"   =>""
                                    ],
                                    "ele-4" =>[
                                        "sub_title"=>"Trade Out",
                                        "Total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images" =>[],
                                        "tag"   =>""
                                    ],
                                ]
                            ],
                            "left-block-2"=>[
                                "title"=>"TAT Reports",
                                "data"=>[
                                    "ele-0"=>[
                                        "sub_title"=>"Evaluations Required - Evaluations Done",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/evaluation-required-64x64.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/evaluation-done1-64x64.png"
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                    "ele-1"=>[
                                        "sub_title"=>"Evaluations Done - Trade In Done",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/evaluation-done1-64x64.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/trade-in-done-64x64.png",
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                    "ele-2"=>[
                                        "sub_title"=>"Trade In - First RO Received",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/trade-in-done-64x64.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/ro-received-90x90.png",
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                    "ele-3"=>[
                                        "sub_title"=>"RO Closed - Booking",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/ro-closed-90x90.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/booking-90x90.png",
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                    "ele-4"=>[
                                        "sub_title"=>"Booking - Delivery",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/ro-closed-90x90.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/booking-90x90.png",
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                    "ele-5"=>[
                                        "sub_title"=>"Booking - Delivery",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/booking-90x90.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/delivery-90x90.png",
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                    "ele-6"=>[
                                        "sub_title"=>"Delivery - RC Transfer",
                                        "total" =>"",
                                        "title" =>"Days",
                                        "tag"   =>"(Avg)",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/delivery-90x90.png",
                                            "image-2"=>$config['base_url']."/assets/images/dashboard/rc-transfer-90x90.png",
                                        ],
                                        "design"=>"<span class='px-3 dasharrow'>→</span>"
                                    ],
                                ]
                            ],
                            "right-block-1"=>[
                                "title"=>"MTD",
                                "data"=>[

                                    "ele-0"=>[
                                        "sub_title"=>"Evaluations Required",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/evaluation-required-64x64.png"
                                        ]
                                    ],
                                    "ele-1"=>[
                                        "sub_title"=>"Evaluations Done",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/evaluation-done1-64x64.png"
                                        ]
                                    ],
                                    "ele-2"=>[
                                        "sub_title"=>"Trade-In Done",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                           "image-1"=>$config['base_url']."/assets/images/dashboard/trade-in-done-64x64.png"
                                        ]
                                    ],
                                    "ele-3"=>[
                                        "sub_title"=>"Sales Leads",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>$config['base_url']."/assets/images/dashboard/sales-leads-64x64.png"
                                        ]
                                    ],
                                    "ele-4"=>[
                                        "sub_title"=>"Trade Out",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                             "image-1"=>$config['base_url']."/assets/images/dashboard/trade-out-64x64.png"
                                        ]
                                    ]
                                ],
                                "title-1"=>"YTD",
                                "data1"=>[

                                    "ele-0"=>[
                                        "sub_title"=>"Evaluations Required",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>""
                                        ]
                                    ],
                                    "ele-1"=>[
                                        "sub_title"=>"Evaluations Done",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>""
                                        ]
                                    ],
                                    "ele-2"=>[
                                        "sub_title"=>"Trade-In Done",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>""
                                        ]
                                    ],
                                    "ele-3"=>[
                                        "sub_title"=>"Sales Leads",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>""
                                        ]
                                    ],
                                    "ele-4"=>[
                                        "sub_title"=>"Trade Out",
                                        "total" =>"",
                                        "JLR"   =>"",
                                        "Non JLR"=>"",
                                        "images"=>[
                                            "image-1"=>""
                                        ]
                                    ]
                                ]
                            ],
                            "right-block-2"=>[
                                "title"=>"MTD",
                                "data"=>[
                                    "ele-0"=>[ 
                                        "sub_title"=>"Trade-Ins",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-1"=>[ 
                                        "sub_title"=>"ROs Generated",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-2"=>[ 
                                        "sub_title"=>"Sales",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-3"=>[ 
                                        "sub_title"=>"Gross Profit",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-4"=>[ 
                                        "sub_title"=>"Net Profit",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                ],
                                "title-1"=>"YTD",
                                "data-1"=>[
                                    "ele-0"=>[ 
                                        "sub_title"=>"Trade-Ins",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-1"=>[ 
                                        "sub_title"=>"ROs Generated",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-2"=>[ 
                                        "sub_title"=>"Sales",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-3"=>[ 
                                        "sub_title"=>"Gross Profit",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                    "ele-4"=>[ 
                                        "sub_title"=>"Net Profit",
                                        "total" =>"",
                                        "currency"=>"",
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        return  $data;
    }
    
}
?>
