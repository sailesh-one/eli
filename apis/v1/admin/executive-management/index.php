<?php

// Leads route handler (protected, optimized)
global $auth;
require_once $_SERVER['DOCUMENT_ROOT'] . '/common/common_regex.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_users.php';

$users = new Users();
// $module_name = $users->module_name;
// print_r($GLOBALS['api_user']); exit;
$user_id = $GLOBALS['api_user']['uid'] ?? null;
// if( !$auth->checkModuleAccess($module_name) )
// {
//     api_response(403, 'fail', 'Forbidden: insufficient permissions', [], []);
// }

// $users->dealer_id = $user_id ?? null;

$action = strtolower($_POST['action'] ?? '');

// echo $action; exit;

switch ($action) {
    case 'get':
        $leads = [];        
        if(true)
        {              
            $filters = $errors = [];
            if (count($errors)>0) api_response(400, 'Validation failed.', [], $errors);
            $page = $_POST['page'] ?? 1;
            $perPage = $_POST['perPage'] ?? 10;
            $filters = $_POST['search'] ?? [];
            
            $leads = $users->getdealers($filters, $page, $perPage);
            $dealerships = $users->getdealerships();
            $roles = $users->getroles(1);

            $data = [
                'leads'        => $leads,
                'dealerships'  => $dealerships,
                'roles'        => $roles
            ];
            api_response(200,'ok', 'Leads fetched', $data);
        }
        api_response(403,'fail', 'Forbidden: insufficient permissions', []);
        break;

    case 'add':
        $data = $_POST;

        // Validation check
        $errors = [];
        if(empty($data['name']))
        {
          $errors['name'] = "Name is required.";
        }
        else if(!empty($data['name']) && !validate_field_regex("alpha",$data['name']))
        {
           $errors['name'] = "Invalid name.";
        }

        if(empty($data['email']))
        {
          $errors['email'] = "Email is required.";
        }
        else if(!empty($data['email']) && !validate_field_regex("email",$data['email']))
        {
           $errors['email'] = "Invalid email.";
        }
        
        if(empty($data['mobile']))
        {
          $errors['mobile'] = "Mobile is required.";
        }
        else if(!empty($data['mobile']) && !validate_field_regex("mobile",$data['mobile']))
        {
           $errors['mobile'] = "Invalid mobile.";
        }

        if(empty($data['role_id']))
        {
          $errors['role_id'] = "Select role.";
        }
        else if(!empty($data['role_id']) && !validate_field_regex("id",$data['role_id']))
        {
           $errors['role_id'] = "Invalid Role.";
        }

        if(empty($data['dealership_id']))
        {
          $errors['dealership_id'] = "Select dealership.";
        }
        else if(!empty($data['dealership_id']) && !validate_field_regex("id",$data['dealership_id']))
        {
           $errors['dealership_id'] = "Invalid Dealership.";
        }

         if (empty($data['branch_ids']) || !is_array($data['branch_ids'])) {
            $errors['branch_ids'] = "Select at least one branch.";
            } else {
               foreach ($data['branch_ids'] as $branch_id) {
                  if (!validate_field_regex("numeric", $branch_id)) {
                     $errors['branch_ids'] = "Invalid branch selection.";
                     break;
                  }
               }
         }

        if(count($errors) > 0)
        {
           api_response(400,"fail","Validation failed.",[],[],$errors);
        }
      
        $result = $users->adddealer($data);
        if ($result) {
            api_response(200, 'ok', 'Dealer added successfully', [$result]);
        } else {
            api_response(500, 'fail', 'Failed to add dealer', []);
        }
        break;

    case 'edit':
        $data = $_POST ;

        // Validation check
        $errors = [];

        if(empty($data['id']))
        {
           $errors['id'] = "ID is required.";
        }

        if(empty($data['name']))
        {
          $errors['name'] = "Name is required.";
        }
        else if(!empty($data['name']) && !validate_field_regex("alpha",$data['name']))
        {
           $errors['name'] = "Invalid name.";
        }

        if(empty($data['email']))
        {
          $errors['email'] = "Email is required.";
        }
        else if(!empty($data['email']) && !validate_field_regex("email",$data['email']))
        {
           $errors['email'] = "Invalid email.";
        }
        
        if(empty($data['mobile']))
        {
          $errors['mobile'] = "Mobile is required.";
        }
        else if(!empty($data['mobile']) && !validate_field_regex("mobile",$data['mobile']))
        {
           $errors['mobile'] = "Invalid mobile.";
        }

        if(empty($data['role_id']))
        {
          $errors['role_id'] = "Select role.";
        }
        else if(!empty($data['role_id']) && !validate_field_regex("id",$data['role_id']))
        {
           $errors['role_id'] = "Invalid Role.";
        }

        if(empty($data['dealership_id']))
        {
          $errors['dealership_id'] = "Select dealership.";
        }
        else if(!empty($data['dealership_id']) && !validate_field_regex("id",$data['dealership_id']))
        {
           $errors['dealership_id'] = "Invalid Dealership.";
        }
        
         if (empty($data['branch_ids']) || !is_array($data['branch_ids'])) {
            $errors['branch_ids'] = "Select at least one branch.";
            } else {
               foreach ($data['branch_ids'] as $branch_id) {
                  if (!validate_field_regex("numeric", $branch_id)) {
                     $errors['branch_ids'] = "Invalid branch selection.";
                     break;
                  }
               }
         }
        
        if(count($errors) > 0)
        {
           api_response(400,"fail","Validation failed.",[],[],$errors);
        }
    
        $result = $users->editdealer($data);
        if ($result) {
            api_response(200, 'ok', 'Dealer updated successfully', [$result]);
        } else {
            api_response(500, 'fail', 'Failed to update dealer', []);
        }
        break; 

    default:
        api_response(400, 'fail', 'Invalid action for leads');
        break;

}
