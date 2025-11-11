<?php
   require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/class_evaluation-checklist.php';
   $action = ($_REQUEST['action']) ? $_REQUEST['action'] : '';

   $eval_checklist = new EvaluationChecklist();
   
   switch($action)
   {
     case "getsections":
         $errors = [];

         if($_POST['mpi'] == "") $errors['mpi'] = "MPI value is required.";

         if($_POST['mpi'] != "" && $_POST['mpi']<=0)
         {
            $errors['mpi'] = "Invalid mpi value.";
         }
         
         if(count($errors) > 0)
         {
             api_response(400,"fail","Validation error.",[],[],$errors);
         }
         
         $sections = [];
         $sections = $eval_checklist->getSectionsList();
         if($sections)
         {
            api_response(200,"ok","Sections list fetched successfully.",$sections);
         }
         else
         {
            api_response(200,"empty","Fetched empty sections list.",[]);
         }
         break;

     case "getitems":
           $items = [];
           $items =$eval_checklist->getItemsList();
          
           if($items)
           {
             api_response(200,"ok","Items list fetched successfully.",$items);
           }
           else
           {
             api_response(200,"empty","Fetched empty items list.",[]);
           }
           break;

      case "savesection":
           // Validation
           $errors = [];
           
           if(!isset($_POST['section_name'])) $errors['section_name'] = "Section name is required.";

           if(!empty($_POST['section_name']) && !validate_field_regex("alpha",$_POST["section_name"]))
           {
             $errors["section_name"] = "Invalid section name.";
           }
           
           if($_POST['evaluation_type'] == "") $errors['evaluation_type'] = "Evaluation type is required.";

           if(($_POST['evaluation_type'] != "") && $_POST["evaluation_type"] <=0)
           {
             $errors["evaluation_type"] = "Invalid evaluation type.";
           }

           if(!empty($_POST['active']) && !validate_field_regex('active',$_POST['active']))
           {
             $errors['active'] = 'Invalid active type.';
           }

           if(count($errors) > 0)
           {
             api_response(400,"fail","Validation error.",[],[],$errors);
           }

           if($_POST['sub_action'] == "addsection")
           {
              $result = $eval_checklist->addSection();
      
              if($result)
              {
                api_response(200,"ok","Section added successfully.");
              }
              else
              {
                api_response(500,"fail","Failed to add section.",[]);
              }
           }
           else if($_POST['sub_action'] == "editsection")
           {
              $result = $eval_checklist->editSection();
              if($result)
              {
                api_response(200,"ok","Section details updated successfully.");
              }
              else
              {
                api_response(500,"fail","Failed to update .",[]);
              }
           }
           break;

      case "savesubitemdata":
            $errors = [];
             
            // Validation
            if(empty($_POST['name'])) $errors['name'] = 'Item name is required.';
            if(empty($_POST['field_type'])) $errors['field_type'] = 'Field type is required.';
            if(empty($_POST['options'])) $errors['options'] = 'Option value is required.';

            if(!empty($_POST['field_type']) && !validate_field_regex('alpha',$_POST['field_type']))
            {
              $errors['field_type'] = "Invalid field type.";
            }
            if(!empty($_POST['checklist_id']) && !validate_field_regex('numeric',$_POST['checklist_id']))
            {
              $errors['checklist_id'] = "Invalid checklist id.";
            }

            if(!empty($_POST['active']) && !validate_field_regex('active',$_POST['active']))
            {
              $errors['active'] = 'Invalid active type.';
            }
            
            if(count($errors) > 0)
            {
              api_response(400,"fail","Validation error.",[],[],$errors);
            }
             
            if($_POST['subitem_action'] == 'addSubitem')
            {
               $result = $eval_checklist->addSubitem();
               if($result)
               {
                 api_response(200,"ok","Sub item added successfully.");
               }
               else
               {
                 api_response(500,"fail","Failed to add sub item.",[]);
               }
            }
            else if($_POST['subitem_action'] == 'editSubitem')
            {
               $result = $eval_checklist->editSubitem();
               if($result)
               {
                 api_response(200,"ok","Sub item details updated successfully.");
               }
               else
               {
                 api_response(500,"fail","Failed to update sub item details.",[]);
               }
            }
            break;

    case 'update_section_order':
        $result = $eval_checklist->updateSectionOrder();
        if($result)
        {
        api_response(200,"ok","Section order updated successfully.");
        }
        else
        {
        api_response(500,"fail","Failed to update section order.",[]);
        }
        break;
    case 'saveTemplate':
        $errors = [];
        $request = $_POST['data'];
        if( empty($request['template_name']) )
        { 
            $errors['template_name'] = "Template name is required"; 
        }
        if(!empty($request['template_name']) && !validate_field_regex('alpha',$request['template_name']))
        {
            $errors['template_name'] = "Invalid template name.";
        }
        if( !empty($request['template_description']) && !validate_field_regex('alphanumericspecial',$request['template_description']) )
        {
            $errors['template_description'] = "Invalid template description.";
        }
        if( !isset($request['status']) )
        { 
            $errors['status'] = "Status is required"; 
        }
        if( count($errors)>0 ) api_response(400,"fail","validation failed.",[],[],$errors);
        $result = $eval_checklist->saveTemplateInfo($request);
        if( $result['status'] ){ api_response(200,"ok","Template details are saved.",$result['data']); }
        else api_response(400,"fail","Failed to update the details.");
        break;
    case 'getchecklistItems':
        $items = [];
        $items = $eval_checklist->getChecklistItems();
        
        if($items)
        {
            api_response(200,"ok","Items list fetched successfully.",$items);
        }
        else
        {
            api_response(200,"empty","Fetched empty items list.",[]);
        }
      break;
    case 'savechecklist':
        $status = $eval_checklist->saveCheckList($_POST);
        if($status['status'])
        {
            api_response(200,"ok","Checklist saved successfully.");
        }
        else
        {
            api_response(500,"fail","Failed to save checklist.",[]);
        }
        //echo '<pre>'; print_r($sections); echo '</pre>'; exit;
      break;
    default:
        api_response(400,'fail', 'Invalid action for leads');
        break;
   }

?>