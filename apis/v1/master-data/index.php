<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_master-data.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_curl.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_vahan.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_files.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_configs.php';

$action = ($_REQUEST['action']) ? $_REQUEST['action'] : '';

$masterdata = new MasterData();
$branch_id = $GLOBALS['dealership']['branch_id'] ?? null;
$dealer_id = $GLOBALS['dealership']['dealership_id'] ?? null;

$masterdata->dealer_id = $dealer_id;

switch ($action) {
  case 'getCollections':
    $collections = $_REQUEST['collections'] ?? '';
    
    if (!empty($collections) && is_string($collections)) {
        $collections = array_map('trim', explode(',', $collections));
    } else {
        $collections = [];
    }

    $results = [];
    foreach ($collections as $col) {
        if (method_exists($masterdata, $col)) {
            $results[$col] = $masterdata->$col();
        } else {
            $results[$col] = ['error' => "Unknown collection: $col"];
        }
    }
    api_response(200, "ok", "Collections fetched.", $results);
    break;

  case 'getYears':
    $years = [];
    $years = $masterdata->getYears();

    if ($years) {
      api_response(200, "ok", "Years list fetched successfully.", $years);
    } else {
      api_response(200, "empty", "Fetched empty years list.", []);
    }
    break;

  case 'getMakes':
    $makesList = [];
    $makesList = $masterdata->getMakes();
    if ($makesList) {
      api_response(200, "ok", "Makes list fetched successfully.", $makesList);
    } else {
      api_response(200, "empty", "Fetched empty makes list.", []);
    }
    break;

  case 'getMakesFiltered':
    $filter = $_POST['filter'] ?? 'all'; // 'jlr', 'non-jlr', or 'all'
    $makesList = $masterdata->getMakesFiltered($filter);
    if ($makesList) {
      api_response(200, "ok", "Makes list fetched successfully.", $makesList);
    } else {
      api_response(200, "empty", "Fetched empty makes list.", []);
    }
    break;

  case 'getmodelsbyMake':
    $errors = [];
    $models = [];

    // validation - treat "0" as empty since it's typically the default "Select Make" value
    if (empty($_POST['make']) || $_POST['make'] === '0')  $errors['make'] = "Make is required.";

    if (!empty($_POST['make']) && $_POST['make'] !== '0' && !validate_field_regex('id', $_POST['make'])) {
      $errors['make'] = "Invalid make.";
    }

    if (count($errors) > 0) {
      api_response(400, "fail", "Validation error.", [], [], $errors);
    }

    $models = $masterdata->getModelsByMake();

    if ($models) {
      api_response(200, "ok", "Models list fetched successfully.", $models);
    } else {
      api_response(200, "ok", "Fetched empty makes list.", []);
    }
    break;

  // case "getvariantsbyModel":
  //   $errors = [];
  //   $variants = [];

  //   // validation - treat "0" as empty since it's typically the default "Select Model" value
  //   if (empty($_POST['model']) || $_POST['model'] === '0') $errors['model'] = "Model is required.";

  //   if (!empty($_POST['model']) && $_POST['model'] !== '0' && !validate_field_regex('id', $_POST['model'])) {
  //     $errors['model'] = "Invalid model.";
  //   }

  //   if (count($errors) > 0) {
  //     api_response(400, "fail", "Validation error.", [], [], $errors);
  //   }

  //   $variants = $masterdata->getvariantsbyModel();
  //   if ($variants) {
  //     api_response(200, "ok", "Variants list fetched successfully.", $variants);
  //   } else {
  //     api_response(200, "empty", "Fetched empty variants list.", []);
  //   }
  //   break;

  case 'getAllSources':
      $sourcesData['purchase-master'] = $masterdata->getAllSourcesSubSources('purchase-master');
      $sourcesData['sales-master'] = $masterdata->getAllSourcesSubSources('sales-master');
         api_response(200, "ok", "Sources and sub-sources fetched successfully.", $sourcesData);
      break;

  case 'getSources':
      $type = $_POST['type'] ?? '';
      $sources = $masterdata->getSources($type);

      if (!empty($sources['list'])) {
          api_response(200, "ok", "Sources fetched successfully.", $sources);
      }else{
          api_response(200, "fail", "Failed to fetch sources.", []);
      }
      break;


  case 'getSubSources':
    $errors = [];
    $source_id = null;
    if (isset($_POST['source']) && $_POST['source'] !== '') {
      if (!is_numeric($_POST['source'])) {
        $errors['source'] = 'Invalid source';
      } else {
        $source_id = intval($_POST['source']);
      }
    }
    if (count($errors) > 0) {
      api_response(400, 'fail', 'Validation failed.', [], $errors);
    }
    $subsources = $masterdata->getSubSources($source_id);
    if ($subsources !== false) {
      api_response(200, "ok", "Subsources fetched successfully.", $subsources);
    } else {
      api_response(200, "fail", "Failed to fetch subsources.", []);
    }
    break;

  case 'getExecutives':
    $executives = $masterdata->getExecutives();
    if ($executives !== false) {
      api_response(200, "ok", "Executives fetched successfully.", $executives);
    } else {
      api_response(200, "fail", "Failed to fetch executives.", []);
    }
    break;

  case "getRoleModules":
    $errors = [];
    $get = isset($_POST['get']) ? $_POST['get'] : '';
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    // validation    
    if (empty($get)) $errors['get'] = 'Target Id is required.';
    if (!empty($get) && !validate_field_regex('alpha', $get)) {
      $errors['get'] = "Invalid Fetch Target.";
    }
    if (empty($id)) $errors['id'] = 'Target Id is required.';
    if (!empty($id) && !validate_field_regex('id', $id)) {
      $errors['id'] = "Invalid Target Id.";
    }
    if (count($errors) > 0) {
      api_response(400, "fail", "Validation error.", [], [], $errors);
    }

    if ($get == 'roles') {
      $result = $masterdata->getModuleMappedRoles($id);
    } else if ($get == 'modules') {
      //  $result = $masterdata->getRoleMappedModules($id);
    } else {
      api_response(403, "fail", "Invalid data requested.", []);
    }
    api_response(200, "ok", "", $result);
    break;

    case 'getVahanDetails':
        $vahan = new Vahan();
        $errors = [];              
        if(empty($_POST['reg_num']))
        {
           $errors['reg_num'] = "Registration number is required";
        }
        if(count($errors)>0)
        {
          api_response(400,"fail","Validation failed.",[],[],$errors);
        }

        $vahan->reg_number = $_POST['reg_num'];
        $response = $vahan->regNumberValidation();
        if( $response )
        {
            // Always fetch complete Vahan response
            // This automatically saves to vahan_api_log table in the funciton itself. 
            $vahan_response = $vahan->fetchVahanDetailsComplete();
            
            if( $vahan_response['status'] ) {
                // Return the data - car button should ONLY save to vahan_api_log
                // The sellleads/inventory tables will be updated on form submit
                api_response(200,"ok","Vahan details fetched successfully.",$vahan_response['data']);
            } else {
                api_response(400,"fail",$vahan_response['msg'],[],[],"Vahan data not found");
            }
        }
        else{
            api_response(400,"fail","Validation failed.",[],[],["Registration number is not valid."]);
        }
        break;
    

    case 'getBranches':
        $errors = [];
        if (empty($_POST['dealership_id'])) {
            $errors['dealership_id'] = "Dealership is required.";
        } elseif (!validate_field_regex("numeric", $_POST['dealership_id'])) {
            $errors['dealership_id'] = "Invalid Dealership.";
        }
        if (count($errors) > 0) {
            api_response(400, "fail", "Validation failed.", [], [], $errors);
        }
        $branches = $masterdata->getBranches($_POST['dealership_id']);
        if ($branches !== false) {
            api_response(200, "ok", "Branches fetched successfully.", $branches);
        }
        break;

    case 'getColors':
        $errors = [];
        $make_id = isset($_POST['make_id']) ? (int)$_POST['make_id'] : null;
        
        // Log for debugging
        $colors = $masterdata->getColorsByMake($make_id);
        if ($colors['status'] == 1) {
            api_response(200, "ok", "Colors fetched successfully.", $colors);
        } else {
            api_response(200, "fail", "Failed to fetch colors.", []);
        }
        break;

    case 'getStateCityByPincode':
       
          $data = $masterdata->getStateCityByPincode($_POST['pin_code']);
          if ($data !== false) {
              api_response(200, "ok", "State and City are fetched successfully from pincode.", $data);
          } else {
              api_response(400, "fail", "No data found for the given pincode.", []);
          }
        break;

    case 'getMMVData':
      $mmv_data = $masterdata->getMMVData();
      if (!empty($mmv_data)) {
          api_response(
              200, "ok", "MMV Data fetched successfully.", $mmv_data);
      } else {
          api_response(200, "ok", "No MMV Data found.", []);
      }
      break;

    case 'viewdoc':
        $Dwntype = 'inline';
        $filename = $_POST['filename'];
        $uniq_id = uniqid(); 
        $tmpdir = sys_get_temp_dir().'/'.$uniq_id;
        $file = new Files();
        $res = $file->downloadFiles("docs",$filename,$tmpdir); 
        ob_clean();
        header("Content-Type: {$res['ContentType']}");
        header("Content-Disposition: $Dwntype; filename=".pathinfo($filename)['basename']."");
        readfile($tmpdir.$uniq_id);
        unlink($tmpdir.$uniq_id);
        break;

    case 'getAllStatesCitiesPincodes':
      $locations_data = $masterdata->getAllStatesCitiesPincodes();
      if (!empty($locations_data)) {
          api_response(200, "ok", "All locations data fetched successfully.", $locations_data);
      } else {
          api_response(400, "fail", "Failed to fetch locations data.", []);
      }
      break;

    case 'getExecutivesByBranch' :
       if (empty($branch_id) || !is_array(json_decode($branch_id, true))) {
            api_response(400,'fail','Validation failed.', [], [], 'Branch ID is not valid.');
        }
        $users = getUsersByBranchIds($branch_id);
        if(!empty($users))
        {
            api_response(200,'ok', 'Users fetched successfully.', ['branches' => $users]);
        }
        else
        {
            api_response(200,'ok', 'No users found.', ['users' => []]);
        }
        break;
          

    case 'getAllColors':
      $colorsData = $masterdata->getAllColors();
      if ($colorsData['status'] == 1) {
          api_response(200, "ok", "Colors fetched successfully.", $colorsData);
      } else {
          api_response(500, "fail", "Failed to fetch colors.", []);
      }
      break;

    case 'getAllStatus':
      $errors = [];
      if (empty($_POST['type'])) {
          $errors['type'] = "Type is required.";
      } elseif (!validate_field_regex("alphanumericspecial", $_POST['type'])) {
          $errors['type'] = "Invalid Type.";
      }

      if (count($errors) > 0) {
          api_response(400, "fail", "Validation failed.", [], [], $errors);
      }

      // Normalize the type
      $type = strtolower($_POST['type']);
      
      // Map full names to abbreviations
      $typeMap = [
          'purchase-master' => 'pm',
          'purchasemaster' => 'pm',
          'purchase_master' => 'pm',
          'sales-master' => 'sm',
          'salesmaster' => 'sm',
          'sales_master' => 'sm',
      ];
      
      // Check if type needs to be mapped
      if (isset($typeMap[$type])) {
          $type = $typeMap[$type];
      }
      
      
      // Validate that we have a valid type (pm or sm)
      if (!in_array($type, ['pm', 'sm'])) {
          api_response(400, "fail", "Invalid type. Must be 'pm', 'purchase-master', 'sm', or 'sales-master'.", [], [], ['type' => 'Invalid type']);
      }

      $config_status = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';

      $status_key = $type . '_status';
      $sub_status_key = $type . '_sub_status';

      $statusList = [];

      // Build main status list
      foreach ($config_status[$status_key] as $statusId => $statusLabel) {
          $subStatuses = $config_status[$sub_status_key][$statusId] ?? [];

          $subStatusList = [];
          foreach ($subStatuses as $subId => $subLabel) {
              $subStatusList[] = [
                  'sub_status_id' => (string)$subId,
                  'sub_status_name' => $subLabel
              ];
          }

          $statusList[] = [
              'status_id' => (string)$statusId,
              'status_name' => $statusLabel,
              'sub_status' => $subStatusList
          ];
      }

      api_response(
          200,
          "ok",
          "Status fetched successfully.",
          ['status' => $statusList]
      );
      break;


    case 'getSubStatus':
      $errors = [];
      if (empty($_POST['type'])) {
          $errors['type'] = "Type is required.";
      } elseif (!validate_field_regex("alphanumericspecial", $_POST['type'])) {
          $errors['type'] = "Invalid Type.";
      }
      if (empty($_POST['status'])) {
          $errors['status'] = "Status is required.";
      } elseif (!validate_field_regex("numeric", $_POST['status'])) {
          $errors['status'] = "Invalid Status.";
      }
      if (count($errors) > 0) {
          api_response(400, "fail", "Validation failed.", [], [], $errors);
      }

      // Normalize the type
      $type = strtolower($_POST['type']);
      
      // Map full names to abbreviations
      $typeMap = [
          'purchase-master' => 'pm',
          'purchasemaster' => 'pm',
          'purchase_master' => 'pm',
          'sales-master' => 'sm',
          'salesmaster' => 'sm',
          'sales_master' => 'sm',
      ];
      
      // Check if type needs to be mapped
      if (isset($typeMap[$type])) {
          $type = $typeMap[$type];
      }
      
      // Validate that we have a valid type (pm or sm)
      if (!in_array($type, ['pm', 'sm'])) {
          api_response(400, "fail", "Invalid type. Must be 'pm', 'purchase-master', 'sm', or 'sales-master'.", [], [], ['type' => 'Invalid type']);
      }

      $config_status = require $_SERVER['DOCUMENT_ROOT'] . '/common/common_config.php';
      $status = $_POST['status'];
      
      // Sub-status array name (e.g., pm_sub_status, sm_sub_status)
      $sub_status_key = $type . "_sub_status";
      
      $moduleConfig = new moduleConfig();
      $sublist = $moduleConfig->buildOptions($config_status[$sub_status_key][$status] ?? [], 'Sub Status');
      
      if ($sublist) {
          api_response(200, "ok", "Sub Status fetched successfully.", ['list' => $sublist]);
      } else {
          api_response(200, "ok", "No Sub Status found.", ['list' => []]);
      }
      break;

    case 'getDentConfig':
     $moduleConfig = new moduleConfig();
     $dentConfig = $moduleConfig->getConfig("dent-map");
      if (!empty($dentConfig)) {
          api_response(200, "ok", "Dent Config fetched successfully.", $dentConfig);
      } else {
          api_response(500, "fail", "Failed to fetch dent Config.", []);
      }
      break;

    case 'getmasterstates':
      $statesList = [];
      $statesList = $masterdata->getMasterStates();
      if ($statesList) {
          api_response(200, "ok", "All States data fetched successfully.", $statesList);
      } else {
          api_response(500, "fail", "Failed to fetch master states data.", []);
      }
      break;

    case 'getcitiesbystate':
        $errors = [];
        $citiesByState = [];
        // validation - treat "0" as empty since it's typically the default "Select State" value
        if (empty($_POST['cw_state']) || $_POST['cw_state'] === '0')  $errors['cw_state'] = "State is required.";

        if (!empty($_POST['cw_state']) && $_POST['cw_state'] !== '0' && !validate_field_regex('id', $_POST['cw_state'])) {
          $errors['cw_state'] = "Invalid state.";
        }

        if (count($errors) > 0) {
          api_response(400, "fail", "Validation error.", [], [], $errors);
        }
        $citiesByState = $masterdata->getCitiesByState();

        if ($citiesByState) {
          api_response(200, "ok", "Cities list fetched successfully.", $citiesByState);
        } else {
          api_response(200, "empty", "Fetched empty cities list.", []);
        }
        break;

    default:
        api_response(400,"fail","Action not found.");
        break;
}

?>
