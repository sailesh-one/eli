<?php

// Leads route handler (protected, optimized)
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_users.php';

$users = new Users();
// $module_name = $users->module_name;
// print_r($GLOBALS['api_user']); exit;
$user_id = $GLOBALS['api_user']['uid'] ?? null;


$action = strtolower($_POST['action'] ?? '');

        $fieldGroups = [
            ["heading" => "Basic Details", "id" => "outlet_code", "label" => "Outlet Code", "type" => "text", "required" => false, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Basic Details", "id" => "name", "label" => "Branch Name", "type" => "text", "required" => true, "validation" => get_field_regex("branch"), "error" => "" ],
            ["heading" => "Basic Details", "id" => "pin_code", "label" => "Pin Code", "type" => "text", "maxlength" => 6, "required" => true, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Basic Details", "id" => "state", "label" => "State", "type" => "select", "required" => true, "validation" => get_field_regex("numeric"), "error" => "", "options" => [ '' => "Select"], 'view_id' => 'state_name'],
            ["heading" => "Basic Details", "id" => "city", "label" => "City", "type" => "select", "required" => true, "validation" => get_field_regex("numeric"), "error" => "" , "options" => [ '' => "Select"], 'view_id' => 'city_name'],
            ["heading" => "Basic Details", "id" => "area", "label" => "Area", "type" => "textarea", "rows" => 1, "required" => false, "validation" => get_field_regex("field_name"), "error" => "" ],
            ["heading" => "Basic Details", "id" => "address", "label" => "Address", "type" => "textarea", "rows" => 1, "required" => true, "validation" => get_field_regex("branch"), "error" => "" ],
            ["heading" => "Basic Details", "id" => "country", "label" => "Country", "type" => "text", "required" => false, "validation" => get_field_regex("field_name"), "error" => "" ],
            ["heading" => "Basic Details", "id" => "google_map_link", "label" => "Google Map Link", "type" => "text", "required" => false, "validation" => get_field_regex("url"), "error" => "" ],
            [ "heading"   => "Basic Details", "id" => "franchise_type", "label" => "Franchise Type", "type" => "check_box", "validation"=> get_field_regex("array_numeric"), "required" => true, "error"=> "",
                    "options" => [ 1 => "Sales Retailer", 2 => "Authorised Repairer", ], 'view_id' => 'franchise_type_name' ],
            ["heading" => "Basic Details", "id" => "invoicing_enabled", "label" => "Invoicing Enabled", "type" => "select", "required" => true, "validation" => get_field_regex("is_active"), "error" => "",
                    "options" => ['' => "Select", 'n' => "No", 'y' => "Yes", ], 'view_id' => "invoicing_enabled_value" ],
            ["heading" => "Basic Details", "id" => "main_branch", "label" => "Main Branch", "type" => "select", "required" => true, "validation" => get_field_regex("is_active"), "error" => "",
                    "options" => [ '' => "Select", 'n' => "No", 'y' => "Yes", ], 'view_id' => "main_branch_value" ],

            ["heading" => "Contact Details", "id" => "contact_name", "label" => "Contact Name", "type" => "text", "required" => false, "validation" => get_field_regex("name"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_country_code", "label" => "Contact Country Code", "type" => "text", "required" => false, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_mobile", "label" => "Contact Mobile", "type" => "text", "required" => false, "validation" => get_field_regex("mobile"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_country_code2", "label" => "Contact Country Code 2", "type" => "text", "required" => false, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_mobile2", "label" => "Contact Mobile 2", "type" => "text", "required" => false, "validation" => get_field_regex("mobile"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_telephone", "label" => "Contact Telephone", "type" => "text", "required" => false, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_email", "label" => "Contact Email", "type" => "email", "required" => false, "validation" => get_field_regex("email"), "error" => "" ],
            ["heading" => "Contact Details", "id" => "contact_email2", "label" => "Contact Email 2", "type" => "email", "required" => false, "validation" => get_field_regex("email"), "error" => "" ],

            ["heading" => "Legal Details", "id" => "registered_name", "label" => "Registered Name", "type" => "text", "required" => false, "validation" => get_field_regex("name"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "gstin", "label" => "GSTIN", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "pan", "label" => "PAN", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "tan", "label" => "TAN", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "payment_terms", "label" => "Payment Terms", "type" => "textarea", "rows" => 1, "required" => false, "validation" => get_field_regex("textarea"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "general_terms", "label" => "General Terms", "type" => "textarea", "rows" => 1, "required" => false, "validation" => get_field_regex("textarea"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "eoe_terms", "label" => "E&OE Terms", "type" => "textarea", "rows" => 1, "required" => false, "validation" => get_field_regex("textarea"), "error" => "" ],
            ["heading" => "Legal Details", "id" => "jurisdiction_terms", "label" => "Jurisdiction Terms", "type" => "textarea", "rows" => 1, "required" => false, "validation" => get_field_regex("textarea"), "error" => "" ],

            ["heading" => "Payment Details", "id" => "bank_name1", "label" => "Bank Name 1", "type" => "text", "required" => false, "validation" => get_field_regex("field_name"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_account_number1", "label" => "Bank Account Number 1", "type" => "text", "required" => false, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_ifsc_code1", "label" => "Bank IFSC Code 1", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_upi_id1", "label" => "Bank UPI ID 1", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_name2", "label" => "Bank Name 2", "type" => "text", "required" => false, "validation" => get_field_regex("field_name"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_account_number2", "label" => "Bank Account Number 2", "type" => "text", "required" => false, "validation" => get_field_regex("numeric"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_ifsc_code2", "label" => "Bank IFSC Code 2", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
            ["heading" => "Payment Details", "id" => "bank_upi_id2", "label" => "Bank UPI ID 2", "type" => "text", "required" => false, "validation" => get_field_regex("alphanumeric"), "error" => "" ],
        ];

switch ($action) {

    case 'get_fields':
        api_response(200, 'ok', 'Form fields fetched successfully', $fieldGroups);
        break;

    case 'getdealerships':
        $dealerships = []; 
        $page = $_POST['page'] ?? 1;
        $perPage = $_POST['perPage'] ?? 0;
        $filters = $_POST['search'] ?? [];
        
        $dealerships = $users->getdealerships($filters, $page, $perPage);
        api_response(200, 'ok', 'Dealerships Fetched', $dealerships);
        break;
        
    case 'add' :
        if(isset($_POST['sub_action']) && $_POST['sub_action'] === 'dealer_group') {
            $errors = [];
            if(empty($_POST['name'])) $errors['name'] = "Name is required.";
            if(empty($_POST['short_name'])) $errors['short_name'] = "Short Name is required.";
            if(!empty($_POST['name']) && !validate_field_regex("alpha", $_POST['name'])) {
                $errors['name'] = "Invalid name.";
            }
            if(!empty($_POST['short_name']) && !validate_field_regex("url", $_POST['short_name'])) {
                $errors['short_name'] = "Invalid short Name.";
            }
            if(!empty($_POST['website_url']) && !validate_field_regex("url", $_POST['website_url'])) {
                $errors['website_url'] = "Invalid website URL.";
            }
            if(count($errors) > 0) {
                api_response(400, 'fail', 'Validation failed.', [], [], $errors);
            }
            $data = $users->addDealerGroup();
            if($data) {
                api_response(200, 'ok', 'Dealership added successfully.', $data);
            } else {
                api_response(500, 'fail', 'Failed to add dealership.', [], []);
            }
            break;
        }
        else {
            $data = $_POST['form'] ?? [];
            $dealer_id = $_POST['dealer_id'] ?? null;
            $errors = [];
            if (empty($dealer_id)) {
                api_response(400, 'fail', 'Missing Dealer ID', [], []);
            }
            $filteredData = [];
            foreach ($data as $id => $value) {
                $value = trim($value);

                $fieldDef = array_filter($fieldGroups, fn($f) => $f['id'] === $id);
                $fieldDef = array_values($fieldDef)[0] ?? null;

                $label = $fieldDef['label'] ?? $id;
                $required = $fieldDef['required'] ?? false;
                $validation = $fieldDef['validation'] ?? '';

                if ($required && $value === '') {
                    $errors[$id] = "$label is required";
                    continue;
                }

                if ($value !== '' && !validate_field_regex($validation, $value)) {
                    $errors[$id] = "$label is not valid";
                }
                $filteredData[$id] = $value;
            }
            if (!empty($errors)) {
                api_response(400, 'fail', 'Validation failed', [], [], $errors);
            }
            // if($data['main_branch'] === 'y')
            // {
            //     $main_branch_check = $users->checkMainBranchExists($dealer_id);
            //     if($main_branch_check) {
            //         api_response(400, 'fail', 'Already Main branch exists', [], []);
            //     }
            // }
            $result = $users->addDealerBranch($dealer_id, $data);
            if ($result) {
                api_response(200, 'ok', 'Dealership added successfully', $result);
            } else {
                api_response(500, 'fail', 'Failed to add dealership', [], []);
            }
            break;
        }

    case 'update':
        if(isset($_POST['sub_action']) && $_POST['sub_action'] === 'dealer_group') {
            $errors = [];
            if(empty($_POST['id'])) {
                $errors['id'] = "Dealership ID is required.";
            }
            if(empty($_POST['name'])) $errors['name'] = "Name is required.";
            if(empty($_POST['short_name'])) $errors['short_name'] = "Short Name is required.";
            if(!empty($_POST['name']) && !validate_field_regex("alpha", $_POST['name'])) {
                $errors['name'] = "Invalid name.";
            }
            if(!empty($_POST['short_name']) && !validate_field_regex("url", $_POST['short_name'])) {
                $errors['short_name'] = "Invalid short Name.";
            }
            if(!empty($_POST['website_url']) && !validate_field_regex("url", $_POST['website_url'])) {
                $errors['website_url'] = "Invalid website URL.";
            }
            if(count($errors) > 0) {
                api_response(400, 'fail', 'Validation failed.', [], [], $errors);
            }
            $data = $users->editDealerGroup();
            if($data) {
                api_response(200, 'ok', 'Dealership updated successfully.', $data);
            } else {
                api_response(500, 'fail', 'Failed to update dealership.', [], []);
            }
            break;
        }
        else {
            $data = $_POST['form'] ?? [];
            $branch_id = $_POST['branch_id'] ?? null;
            $filteredData = [];
            $errors = [];

            if (empty($branch_id)) {
                api_response(400, 'fail', 'Missing Branch ID', [], []);
            }

            foreach ($data as $id => $value) {
                $value = trim($value);

                $fieldDef = array_filter($fieldGroups, fn($f) => $f['id'] === $id);
                $fieldDef = array_values($fieldDef)[0] ?? null;

                $label = $fieldDef['label'] ?? $id;
                $required = $fieldDef['required'] ?? false;
                $validation = $fieldDef['validation'] ?? '';

                if ($required && $value === '') {
                    $errors[$id] = "$label is required";
                    continue;
                }

                if ($value !== '' && !preg_match($validation, $value)) {
                    $errors[$id] = "$label is not valid";
                }
                $filteredData[$id] = $value;
            }

            if (!empty($errors)) {
                api_response(400, 'fail', 'Validation failed', [], [], $errors);
            }

            $result = $users->editDealerBranch($branch_id, $filteredData);
            if ($result) {
                api_response(200, 'ok', 'Dealership updated successfully', $result);
            } else {
                api_response(500, 'fail', 'Failed to update dealership', [], []);
            }
            break;
        }

    case 'delete':
        $dealership_id = $_POST['dealership_id'] ?? null;
        $active = $_POST['active'] ?? null;

        if (empty($dealership_id)) {
            api_response(400, 'fail', 'Missing dealership ID', [], []);
        }
        $result = $users->deletedealership($dealership_id,$active);
        if ($result) {
            api_response(200, 'ok', 'Dealership deleted successfully', $result);
        } else {
            api_response(500, 'fail', 'Failed to delete dealership', [], []);
        }
        break;

    case 'get_view':
        $branch_id = $_POST['branch_id'] ?? null;
        if (empty($branch_id)) {
            api_response(400, 'fail', 'Missing Branch ID', [], []);
        }
        $branches = $users->getBranchData($branch_id);
        $data = array (
            'branches' => $branches ?: [],
        );
        if ($data) {
            api_response(200, 'ok', 'Branch details fetched successfully', $data);
        } else {
            api_response(404, 'fail', 'Branch not found', [], []);
        }
        break;

    case 'exportdata':
        $filters = [];
        $filters['search_data'] = $_POST['search_data'] ?? '';
        $data = $users->exportDealerships($fieldGroups, $filters);

        if ($data) {
            api_response(200, 'ok', 'Dealerships exported successfully', $data);
        } else {
            api_response(500, 'fail', 'Failed to export dealerships', [], []);
        }
        break;


    default:
        api_response(400, 'fail', 'Invalid action for leads');
        break;


}
