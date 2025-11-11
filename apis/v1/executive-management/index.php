<?php
  require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_users.php';
  
  $branches = $GLOBALS['dealership']['branches'];
  $users = new Users();

  $action = $_POST['action'] ?? "";
  
  switch($action)
  {
      case "get":
        $page = $_POST['page'] ?? 1;
        $perPage = $_POST['perPage'] ?? 10;
        $search_filters = $_POST['search_filters'] ?? [];
        $roles = [];
        $dealers = [];
        $dealers = $users->getDealersList($search_filters,$page,$perPage);
        $roles = $users->getroles(1);  
        if(!empty($roles) && !empty($dealers))
        {
            $data = [
               "leads" => $dealers,
               "roles" => $roles,
               "branches" => $branches,
               "login_user_id" => $users->login_user_id
            ];
            api_response(200,"ok","Dealers data fetched succcessfully.",$data);
        }
        else
        {
            api_response(500,'fail', 'Failed to fetch dealers data.',[]);
        }
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

      
          $allowed_branch_ids = array_column($branches, 'branch_id');
         if (empty($data['branch_ids']) || !is_array($data['branch_ids']) || count($data['branch_ids']) == 0) {
            $errors['branch_ids'] = "Select at least one branch.";
         } else {
            foreach ($data['branch_ids'] as $branch_id) {
               if (!validate_field_regex("numeric", $branch_id)) {
                     $errors['branch_ids'] = "Invalid branch selection.";
                     break;
               }
               if (!in_array($branch_id, $allowed_branch_ids, true)) {
                     $errors['branch_ids'] = "Selected branch ($branch_id) is not valid. Allowed: " . implode(", ", $allowed_branch_ids);
                     break;
               }
            }
         }

        if(count($errors) > 0)
        {
           api_response(400,"fail","Validation failed.",[],[],$errors);
        }

        $result = $users->addExecutive($data);
        if ($result) 
        {
            api_response(200, 'ok', 'Dealer added successfully', [$result]);
        } 
        else 
        {
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

         $allowed_branch_ids = array_column($branches, 'branch_id');
         if (empty($data['branch_ids']) || !is_array($data['branch_ids']) || count($data['branch_ids']) == 0) {
            $errors['branch_ids'] = "Select at least one branch.";
         } else {
            foreach ($data['branch_ids'] as $branch_id) {
               if (!validate_field_regex("numeric", $branch_id)) {
                     $errors['branch_ids'] = "Invalid branch selection.";
                     break;
               }
               if (!in_array($branch_id, $allowed_branch_ids, true)) {
                     $errors['branch_ids'] = "Selected branch ($branch_id) is not valid. Allowed: " . implode(", ", $allowed_branch_ids);
                     break;
               }
            }
         }

        if(count($errors) > 0)
        {
           api_response(400,"fail","Validation failed.",[],[],$errors);
        }

        $result = $users->editExecutive($data);
        if ($result) 
        {
            api_response(200, 'ok', 'Dealer updated successfully', [$result]);
        } 
        else
        {
            api_response(500, 'fail', 'Failed to update dealer', []);
        }
        break; 

    default:
        api_response(400, 'fail', 'Invalid action for leads');
        break;
  }

?>