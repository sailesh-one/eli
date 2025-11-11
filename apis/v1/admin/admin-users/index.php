<?php
 require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_admin.php';
 
 $action = ($_REQUEST['action']) ? $_REQUEST['action'] : '';

 $user = new User();

 switch($action)
 {
    case 'getusers': 
        try
        {
          $page = isset($_POST['page']) ? $_POST['page'] : 1;
          $perPage = isset($_POST['perPage']) ? $_POST['perPage'] : 1;

          $user->getUsers($page,$perPage);
        }
        catch(Throwable $e)
        {
           api_response(400,'fail','Users list error: '. $e->getMessage(),[],[]);
        }
        break;
     case 'saveuser': 
        try
        {
         //  Validation check start
         $errors = [];
         if(empty($_POST['name'])) $errors['name'] = "Name is required.";
         if(empty($_POST['email'])) $errors['email'] = "Email is required.";
         if(empty($_POST['mobile'])) $errors['mobile'] = "Mobile is required.";
         if(empty($_POST['role_name'])) $errors['role_name'] = "Role is required.";
         
         if(!empty($_POST['name']) && !validate_field_regex("alpha",$_POST['name']))
         {
            $errors['name'] = "Invalid name.";
         }
         if(!empty($_POST['email']) && !validate_field_regex("email",$_POST['email']))
         {
            $errors['email'] = "Invalid email.";
         }
         if(!empty($_POST['mobile']) && !validate_field_regex("mobile",$_POST['mobile']))
         {
            $errors['mobile'] = "Invalid mobile.";
         }
         if(!empty($_POST['role_name']) && !validate_field_regex("alpha",$_POST['role_name']))
         {
            $errors['role_name'] = "Invalid role.";
         }
         //  Validation check end

          $result = $user->addUser();
          if($result)
          {
             api_response(200,"ok","User added successfully.",[$result]);
          }
          else
          {
             api_response(500,"fail","Failed to add user added successfully.",[]);
          }
        }
        catch(Throwable $e)
        {
           api_response(400,'fail','Users list error: '. $e->getMessage(),[],[]);
        }
        break;
        case 'getroles': 
        try
        {
          $result = $user->getRoles();
        }
        catch(Throwable $e)
        {
           api_response(400,'fail','Roles list error: '. $e->getMessage(),[],[]);
        }
        break;
    
 }

?>