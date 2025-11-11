<?php 
   require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_users.php';
   $action = ($_REQUEST['action']) ? $_REQUEST['action'] : '';
   $users = new Users();
   
   switch($action)
   {
      case 'list':
         $result = $users->getmodules();
         $permissions = $users->getrolepermissions();
         $roles = $users->getroles();
         $data = array_merge(
            $result ?: [],
            $permissions ?: [],
            $roles ?: []
         );
         if ($data) {
               api_response(200, 'ok', 'Fetched data', $data);
         } else {
               api_response(500, 'fail', 'Failed to fetch data', []);
         }
         break;

      case 'removerolepermission':
         $role_id = isset($_POST['role_id']) ? $_POST['role_id'] : '';
         $module_id = isset($_POST['module_id']) ? $_POST['module_id'] : '';
         $submodule_id = isset($_POST['submodule_id']) ? $_POST['submodule_id'] : NULL;
         
         if( empty($role_id) || empty($module_id) ){
               api_response(400, 'fail', 'Role ID, Module ID are required', [], []);
         }

         $result = $users->removerolepermission($role_id, $module_id,$submodule_id);
         if ($result) {
               api_response(200, 'ok', 'Permission removed successfully');
         } else {
               api_response(500, 'fail', 'Failed to remove permission', []);
         }
         break;

      case 'addrolepermission':
         $role_id = isset($_POST['role_id']) ? $_POST['role_id'] : '';
         $module_id = isset($_POST['module_id']) ? $_POST['module_id'] : '';
         $submodule_id = isset($_POST['submodule_id']) ? $_POST['submodule_id'] : NULL;

         if( empty($role_id) || empty($module_id) ){
               api_response(400, 'fail', 'Role ID, Module ID are required', [], []);
         }
         $result = $users->addrolepermission($role_id,$module_id,$submodule_id);
         if ($result) {
               api_response(200, 'ok', 'Permission added successfully');
         } else {
               api_response(500, 'fail', 'Failed to add permission', []);
         }
         break;

      case 'add': 
      try 
      {
         $role_name = isset($_POST['role_name']) ? $_POST['role_name'] : '';
         $description = isset($_POST['description']) ? $_POST['description'] : '';
         $is_active = isset($_POST['active']) ? $_POST['active'] : '';
         $role_type = isset($_POST['role_type']) ? $_POST['role_type'] : '';
         $role_main = isset($_POST['role_main']) ? $_POST['role_main'] : '';

         $errors = [];

         if(empty($role_name)) $errors['role_name'] = "Role name is required.";
         
         if(!empty($role_name) && !validate_field_regex("alpha",trim($role_name)))
         {
               $errors['role_name'] = "Invalid role name.";
         }
        
         if(!empty($is_active) && !validate_field_regex("active",$is_active))
         {
            $errors['active'] = "Invalid active type.";
         }
         if(!empty($role_main) && !validate_field_regex("active",$role_main))
         {
             $errors['role_main'] = "Invalid main role type.";
         }

         if(count($errors) > 0)
         {
            api_response(400,"fail","Validation failed.",[],[],$errors);
         }

         $users->addRole($role_name,$description,$is_active,$role_type, $role_main);
      }
      catch(Throwable $e)
      {
         api_response(400,'fail','Add role error: '. $e->getMessage());
      }
      break;

      case 'edit': 
      try 
      {
         $role_name = isset($_POST['role_name']) ? $_POST['role_name'] : '';
         $is_active = isset($_POST['active']) ? $_POST['active'] : '';
         $role_type = isset($_POST['role_type']) ? $_POST['role_type'] : '';
         $description = isset($_POST['description']) ? $_POST['description'] : '';
         $role_main = isset($_POST['role_main']) ? $_POST['role_main'] : '';

         $errors = [];
         
         if(empty($role_name)) $errors['role_name'] = "Role name is required.";
         
         if(!empty($role_name) && !validate_field_regex("alpha",trim($role_name)))
         {
            $errors['role_name'] = "Invalid role name.";
         }
        
         if(!empty($is_active) && !validate_field_regex("active",$is_active))
         {
            $errors['active'] = "Invalid active type.";
         }
         if(!empty($role_main) && !validate_field_regex("active",$role_main))
         {
             $errors['role_main'] = "Invalid main role type.";
         }
         if($role_main == 'y' && $is_active == 'n') {
            $errors['role_main'] = "Main role cannot be inactive.";
         }

         if(count($errors) > 0)
         {
            api_response(400,"fail","Validation failed.",[],[],$errors);
         }

         $users->editRole($role_name,$is_active,$role_type,$description, $role_main);
      }
      catch(Throwable $e)
      {
         api_response(400,'fail','Edit role error: '. $e->getMessage());
      }
      break;
    
   }

?>