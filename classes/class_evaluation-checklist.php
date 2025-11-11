<?php

class EvaluationChecklist
{
    public $connection;

    public function __construct()
    {
        global $connection;
        $this->connection = $connection;
    }

    public function getSectionsList()
    {
        $temp_query = "SELECT * FROM `evaluation_templates`"; 
        $temp_res = mysqli_query($this->connection,$temp_query);
        $templates = [];
        while( $temp_row = mysqli_fetch_assoc($temp_res) ){
            $temp_row['status'] = (int) $temp_row['status'];
            $templates[$temp_row['id']] = $temp_row;
        }
       $query = "SELECT * FROM evaluation_checklist WHERE evaluation_type = '".mysqli_real_escape_string($this->connection,$_POST['mpi'])."' ORDER BY sort_order";
       $res = mysqli_query($this->connection,$query);
       if(!$res)
       {
         logSqlError(mysqli_error($this->connection),$query,"getsectionslist");
         return ['sections-list' => []];
       }

       $items_cnt_query = "SELECT e.id,COUNT(c.id) AS item_count,sort_order FROM evaluation_checklist AS e LEFT JOIN evaluation_checklist_items AS c 
                           ON c.checklist_id = e.id GROUP BY e.id";
 
       $items_cnt_query_res = mysqli_query($this->connection,$items_cnt_query);
       if(!$items_cnt_query_res)
       {
         logSqlError(mysqli_error($this->connection),$items_cnt_query,"getsectionslist");
         return ['sections-list' => []];
       }
       
       
        $counts = [];
        while($row = mysqli_fetch_assoc($items_cnt_query_res)) 
        {
            $counts[$row['id']] = $row['item_count'];
        }

        $sections = [];
        while($row = mysqli_fetch_assoc($res)) 
            {
                $section_name = mb_convert_encoding($row['section_name'], 'UTF-8', 'UTF-8');
                
                $sections[] = [
                    'id' => $row['id'],
                    'evaluation_type' => $row['evaluation_type'],
                    'section_name' => $section_name,
                    'active' => $row['active'],
                    'items_count' => isset($counts[$row['id']]) ? $counts[$row['id']] : 0,
                    'sort_order' => $row['sort_order']
                ];
            }
                    
        return ['templates'=>$templates,'sections-list' => $sections];
    }

    public function getItemsList()
    {
        $query = "SELECT 
                    e.id as section_id,
                    c.id,
                    c.item_name as name,
                    c.field_type as field_type,
                    c.options as options,
                    c.active as active
                FROM evaluation_checklist AS e
                LEFT JOIN evaluation_checklist_items AS c 
                    ON c.checklist_id = e.id
                ORDER BY e.id ASC, c.id ASC";

        $res = mysqli_query($this->connection, $query);
        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, "getitemslist");
            return ["items-list"=>[]];
        }

        $itemsList = [];

        while ($row = mysqli_fetch_assoc($res)) {
            $sectionId = (int)$row['section_id'];

            if (!isset($itemsList[$sectionId])) {
                $itemsList[$sectionId] = [
                    'id'    => $sectionId,
                    'items' => []
                ];
            }

            if (!empty($row['id'])) {
                $name       = mb_convert_encoding($row['name'], 'UTF-8', 'UTF-8');
                $field_type = $row['field_type'];
                $active     = $row['active'];

                $options = [];
                if (!empty($row['options'])) {
                    $decoded = json_decode($row['options'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $options = $decoded; 
                    } else {
                        $options = array_map('trim', explode(',', $row['options']));
                    }
                }

                $itemsList[$sectionId]['items'][] = [
                    'id'         => (int)$row['id'],
                    'name'       => $name,
                    'field_type' => $field_type,
                    'options'    => $options,
                    'active'     => $active
                ];
            }
        }

        $itemsList = array_values($itemsList);

        return ["items-list"=>$itemsList]; 
    }

    public function addSection()
    {
        // Duplicate check
        $section_name = mysqli_real_escape_string($this->connection,$_POST['section_name']);
        $evaluation_type = mysqli_real_escape_string($this->connection,$_POST['evaluation_type']);

        $dup_query = "SELECT COUNT(*) as cnt FROM evaluation_checklist 
                       WHERE section_name = '$section_name' AND evaluation_type = $evaluation_type";

        $dup_res = mysqli_query($this->connection,$dup_query);
        
        if(!$dup_res)
        {
            logSqlError(mysqli_error($this->connection),$dup_query,"addsection");
            return false;
        }

        $count_res = mysqli_fetch_assoc($dup_res);
        $count = $count_res['cnt'];
        if($count > 0)
        {
            api_response(409,"fail","Duplicate entry.");
        }

        // Get the next sort_order value
        $max_query = "SELECT MAX(sort_order) as max_order FROM evaluation_checklist WHERE evaluation_type = '".mysqli_real_escape_string($this->connection,$_POST['evaluation_type'])."'";
        $max_res = mysqli_query($this->connection, $max_query);
        $max_row = mysqli_fetch_assoc($max_res);
        $next_order = ($max_row['max_order'] !== null) ? ((int)$max_row['max_order'] + 1) : 1;


        $query = "INSERT INTO evaluation_checklist (evaluation_type,section_name,sort_order) VALUES ('".mysqli_real_escape_string($this->connection,$_POST['evaluation_type'])."','".strtoupper(mysqli_real_escape_string($this->connection,$_POST['section_name']))."','$next_order')";
        $res = mysqli_query($this->connection,$query);

        if(!$res)
        {
            logSqlError(mysqli_error($this->connection),$query,"addsection");
            return false;
        }

       return true;
    }

    public function editSection()
    {
        // Duplicate check        
        $section_name = mysqli_real_escape_string($this->connection,$_POST['section_name']);
        $evaluation_type = mysqli_real_escape_string($this->connection,$_POST['evaluation_type']);

        $dup_query = "SELECT COUNT(*) as cnt FROM evaluation_checklist 
                       WHERE section_name = '$section_name' AND evaluation_type = $evaluation_type AND id != '".mysqli_real_escape_string($this->connection,$_POST['section_id'])."'";
        
        $dup_res = mysqli_query($this->connection,$dup_query);
        
        if(!$dup_res)
        {
            logSqlError(mysqli_error($this->connection),$dup_query,"editsection");
            return false;
        }
        $count_res = mysqli_fetch_assoc($dup_res);
        $count = $count_res['cnt'];
        if($count > 0)
        {
            api_response(409,"fail","Duplicate entry.");
        }

        $query = "UPDATE evaluation_checklist SET evaluation_type = '".mysqli_real_escape_string($this->connection,$_POST['evaluation_type'])."',section_name = '".strtoupper(mysqli_real_escape_string($this->connection,$_POST['section_name']))."',active = '".mysqli_real_escape_string($this->connection,$_POST['active'])."' WHERE id = '".mysqli_real_escape_string($this->connection,$_POST['section_id'])."'";
        $res = mysqli_query($this->connection,$query);

        if(!$res)
        {
            logSqlError(mysqli_error($this->connection),$query,"editsection");
            return false;
        }

       return true;
    }

    public function addSubitem()
    {
        $item_name = trim(mysqli_real_escape_string($this->connection, $_POST['name']));
        $checklist_id = mysqli_real_escape_string($this->connection, $_POST['checklist_id']);
        $field_type = mysqli_real_escape_string($this->connection, $_POST['field_type']);
        $mpi = mysqli_real_escape_string($this->connection,$_POST['mpi']);

        // Duplicate check
        $query = "SELECT COUNT(*) AS cnt FROM evaluation_checklist_items AS ei
                  INNER JOIN evaluation_checklist AS ec ON ei.checklist_id = ec.id WHERE ei.item_name = '$item_name'
                  AND ec.evaluation_type = '$mpi'";

        $res = mysqli_query($this->connection, $query);

        if (!$res) {
            logSqlError(mysqli_error($this->connection), $query, "addsubitem");
            return false;
        }

        $count_res = mysqli_fetch_assoc($res);
        if ($count_res['cnt'] > 0) { 
            api_response(409, "fail", "Duplicate entry.");
        }

        // Collect option values
        $options = [];
        if (!empty($_POST['options'])) {
            $decoded = is_string($_POST['options']) 
                ? json_decode($_POST['options'], true) 
                : $_POST['options'];

            if (is_array($decoded)) {
                foreach ($decoded as $opt) {
                    if (isset($opt['value'])) {
                        $options[] = $opt['value'];
                    }
                }
            }
        }

        $options_json = mysqli_real_escape_string($this->connection, json_encode($options));

        // Insert query
        $ins_query = "INSERT INTO evaluation_checklist_items (checklist_id, item_name, field_type, options) 
                    VALUES ('$checklist_id','$item_name','$field_type','$options_json')";

        $ins_res = mysqli_query($this->connection, $ins_query);

        if (!$ins_res) {
            logSqlError(mysqli_error($this->connection), $ins_query, "addsubitem");
            return false; 
        }

        return true;
    }

    public function editSubitem()
    {
        $item_name = trim(mysqli_real_escape_string($this->connection,$_POST['name']));
        $item_id = (int) $_POST['item_id'];
        $mpi = mysqli_real_escape_string($this->connection,$_POST['mpi']);

        $query = "SELECT COUNT(*) AS cnt FROM evaluation_checklist_items AS ei
                  INNER JOIN evaluation_checklist AS ec ON ei.checklist_id = ec.id WHERE ei.item_name = '$item_name'
                  AND ec.evaluation_type = '$mpi' AND ei.id != $item_id";
        
        $res = mysqli_query($this->connection,$query);
        
        if(!$res) {
            logSqlError(mysqli_error($this->connection),$query,"editsubitem");
            return false; 
        }

        $count_res = mysqli_fetch_assoc($res);
        $count = $count_res['cnt'];
        if($count > 0) 
        {
            api_response(409,"fail","Duplicate entry.");
        }

        $options = [];

        if($_POST['field_type'] == "text")
        {
            $text_option = mysqli_real_escape_string($this->connection,trim($_POST['options'])); 
            $options_json = json_encode($text_option);
        }
        else
        {
           if (!empty($_POST['options'])) {
            $raw_options = is_string($_POST['options']) 
                ? json_decode($_POST['options'], true) 
                : $_POST['options'];

            if (is_array($raw_options)) {
                foreach ($raw_options as $opt) {
                    if (isset($opt['value']) && is_string($opt['value'])) {
                        $options[] = $opt['value'];  
                    }
                }
            }
        }

         $options_json = mysqli_real_escape_string($this->connection, json_encode($options));
        }

        $edit_query = "UPDATE evaluation_checklist_items SET item_name = '".mysqli_real_escape_string($this->connection,$_POST['name'])."',
                       field_type = '".mysqli_real_escape_string($this->connection,$_POST['field_type'])."',
                       options = '$options_json',
                       active = '".mysqli_real_escape_string($this->connection,$_POST['active'])."' WHERE id = '".mysqli_real_escape_string($this->connection,$_POST['item_id'])."'";
        

        $res = mysqli_query($this->connection,$edit_query);

        if(!$res) {
            logSqlError(mysqli_error($this->connection),$edit_query,"editsubitem");
            return false; 
        }

        return true;
    }

    public function updateSectionOrder()
    {
        if (empty($_POST['order']) || !is_array($_POST['order'])) {
            api_response(400, "fail", "Invalid order data.");
        }

        foreach ($_POST['order'] as $item) 
        {
            $id = mysqli_real_escape_string($this->connection, $item['id']);
            $sort_order = mysqli_real_escape_string($this->connection, $item['sort_order']);
            $query = "UPDATE evaluation_checklist SET sort_order = '$sort_order' WHERE id = '$id'";
            $res = mysqli_query($this->connection, $query);

            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, "update_section_order");
                return false;
            }
        }
        return true;
    }
    public function inactiveSectionsItems($template_id,$status)
    {
        $template_id = mysqli_real_escape_string($this->connection, $template_id);
        $section_query = "SELECT id FROM evaluation_checklist_new WHERE template_id = $template_id";
        $section_res = mysqli_query($this->connection,$section_query);
        $section_ids = [];
        while($section_row = mysqli_fetch_assoc($section_res) )
        {
            $section_ids[] = $section_row['id'];
        }
        if( count($section_ids)>0 )
        {
            $up_query = "UPDATE evaluation_checklist_new SET active ='".$status."' WHERE template_id = $template_id";
            mysqli_query($this->connection,$up_query);

            $up_item_query = "UPDATE evaluation_checklist_items_new SET active ='".$status."' where checklist_id in (".implode(',',$section_ids).")";
            mysqli_query($this->connection,$up_item_query);
        }    
    }
    public function saveTemplateInfo($request)
    {
        $templates = [];
        $id = mysqli_real_escape_string($this->connection, $request['id']);
        $template_name = mysqli_real_escape_string($this->connection, $request['template_name']);
        $template_description = mysqli_real_escape_string($this->connection, $request['template_description']);
        $status = mysqli_real_escape_string($this->connection, $request['status']);
       
        if( !empty($request['id']) && $request['id']>0 )
        {            
            $query = "UPDATE evaluation_templates SET 
                template_name = '".$template_name."',
                template_description = '".$template_description."',
                status = '".$status."'
                WHERE id= $id";
            $res = mysqli_query($this->connection,$query);           
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, "evaluation-savetemplate");
                return ["status"=>false];
            }
            $item_status = ($status)?'y':'n';
            $this->inactiveSectionsItems($id,$item_status);

            $temp_query = "SELECT * FROM `evaluation_templates`"; 
            $temp_res = mysqli_query($this->connection,$temp_query);
            $templates = [];
            while( $temp_row = mysqli_fetch_assoc($temp_res) ){
                $temp_row['status'] = (int)$temp_row['status'];
                $templates[$temp_row['id']] = $temp_row;
            }
            return ["status"=>true,"data"=>$templates];
        }
        else{
            $query = "INSERT INTO evaluation_templates SET 
                template_name = '".$template_name."',
                template_description = '".$template_description."',
                status = '".$status."'";
            $res = mysqli_query($this->connection,$query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, "evaluation-savetemplate");
                return ["status"=>false];
            }            
            $temp_query = "SELECT * FROM `evaluation_templates`"; 
            $temp_res = mysqli_query($this->connection,$temp_query);
            $templates = [];
            while( $temp_row = mysqli_fetch_assoc($temp_res) ){
                $templates[$temp_row['id']] = $temp_row;
            }
            return ["status"=>true,"data"=>$templates];
        }
    }
    public function getChecklistItems()
    {
        // Use static cache for template and checklist data to avoid repeated queries
        static $templates = null;
        static $checklistStructure = null;
        
        $evaluations = [
            'templates' => [],
            'checklist' => [],
            'count' => [] // Initialize empty count structure
        ];
        
        // Get templates (cached)
        if ($templates === null) {
            $templates = [];
            $temp_query = "SELECT * FROM `evaluation_templates`";
            $temp_res = mysqli_query($this->connection, $temp_query);
            if ($temp_res) {
                while ($temp_row = mysqli_fetch_assoc($temp_res)) {
                    $temp_row['status'] = (int)$temp_row['status'];
                    $templates[$temp_row['id']] = $temp_row;
                }
            }
        }
        $evaluations['templates'] = $templates;
        
        // Get checklist structure with items (cached)
        if ($checklistStructure === null) {
            $checklistStructure = [];
            $items = [];
            
            // Get all checklist items in a single query
            $item_query = "SELECT * FROM evaluation_checklist_items_new WHERE active = 'y'";
            $item_res = mysqli_query($this->connection, $item_query);
            if (!$item_res) {
                logSqlError(mysqli_error($this->connection), $item_query, 'sellleads-getEvaluation', true);
            } else {
                while ($item_row = mysqli_fetch_assoc($item_res)) 
                { 
                    $item_data = [];
                    $item_data['isUpdate']     = false; 
                    $item_data['inputType']     = $item_row['type']; 
                    $item_data['fieldLabel']    = mb_convert_encoding($item_row['item_name'], 'UTF-8', 'UTF-8'); 
                    $item_data['fieldKey']      = $item_row['id']; 
                    $item_data['isRequired']    = ($item_row['required'] == 'yes') ? 'yes' : 'no';
                    $item_data['isEnable']      = false;
                    $item_data['refurb_cost']   = "";                                      
                    $item_data['remarks']       = "";                                      
                    $item_data['fieldOptionIds'] = !empty($item_row['option_suboptions'])?json_decode($item_row['option_suboptions'], true):[];                                     
                    $items[$item_row['checklist_id']][] = $item_data;
                }
            }
            
            // Get checklist sections
            $query = "SELECT * FROM evaluation_checklist_new WHERE active = 'y' ORDER BY sort_order ASC";
            $res = mysqli_query($this->connection, $query);
            if (!$res) {
                logSqlError(mysqli_error($this->connection), $query, 'evaluation-checklist', true);
            } else {
                while ($row = mysqli_fetch_assoc($res)) {
                    $checklistStructure[$row['template_id']]['fields'][] = [
                        "isUpdate" => false,
                        "inputType"=>"ref_expand",
                        "fieldKey" => $row['id'],
                        "fieldLabel" => $row['checklist_title'],
                        "sort_order" => $row['sort_order'],                   
                        "fields" => isset($items[$row['id']]) ? $items[$row['id']] : []
                    ];
                }
            }
        }
        
        // Start with the base checklist structure
        $evaluations['checklist'] = $checklistStructure;

        return $evaluations;
    
    }
    public function deleteTemplateList($template_id, $checklist_ids)
    {
        if($template_id>0){
            $del_query = "DELETE FROM evaluation_checklist_new WHERE template_id = $template_id";
            $del_res = mysqli_query($this->connection,$del_query);
            if(!$del_res){
                logSqlError(mysqli_error($this->connection),$del_query,"delete-template");
                return ["status"=>false];
            }
            $temp_query = "DELETE FROM `evaluation_checklist_items_new` where checklist_id IN (".implode(",",$checklist_ids).") "; 
            mysqli_query($this->connection,$temp_query);
            return ["status"=>true];
        }
        return ["status"=>false];
    }

    public function addGroup($template_id,$section)
    {
        if( !empty($template_id) && !empty($section) )
        {
            $section_query = "INSERT INTO evaluation_checklist_new SET 
                        template_id = '".$template_id."',
                        checklist_title = '".mysqli_real_escape_string($this->connection,$section['fieldLabel'])."',
                        sort_order = '".mysqli_real_escape_string($this->connection,$section['sort_order'])."',
                        active = 'y'";
                    //echo $section_query;
            mysqli_query($this->connection,$section_query);
            $inserted_id = mysqli_insert_id($this->connection);
            if( $inserted_id >0 )
            {
                foreach($section['fields'] as $key=>$val)
                {    
                    $this->addItem($inserted_id,$val);
                }
            }
        }
    }
    public function addItem($checklist_id,$val)
    {
        if(!empty($val) && $checklist_id>0 )
        {
            $options = $sub_options = [];                
            $options['inputType'] = $val['inputType'];
            $options['isRequired'] = $val['isRequired'];
            $options['fieldKey'] = "";
            $options['defaultInputValue'] = "";                   
            foreach( $val['fieldOptionIds'] as $kk=>$vv )
            {                  
                $options["fieldOptionIds"][] = ["value" =>$vv['fieldLabel'],"label"=>$vv['fieldLabel'] ];
                foreach( $vv['fieldSubOptions'] as $i=>$j ){
                    
                    $sub_options['conditionalFields'][$vv['fieldLabel']][0]['inputType'] = $vv['inputType'];
                    $sub_options['conditionalFields'][$vv['fieldLabel']][0]['isRequired'] = $val['isRequired'];
                    $sub_options['conditionalFields'][$vv['fieldLabel']][0]['fieldKey'] = "";
                    $sub_options['conditionalFields'][$vv['fieldLabel']][0]['defaultInputValue'] = "";
                    $sub_options['conditionalFields'][$vv['fieldLabel']][0]['fieldOptionIds'][] = ["value"=>$i+1,"label"=>$j['fieldLabel']];
                }                        
            }
            $item_query = "INSERT INTO evaluation_checklist_items_new SET 
            checklist_id = $checklist_id,
            item_name = '".mysqli_real_escape_string($this->connection,$val['fieldLabel'])."',
            type = '".mysqli_real_escape_string($this->connection,$val['inputType'])."',
            options = '".mysqli_real_escape_string($this->connection,json_encode($options))."',
            sub_options = '".mysqli_real_escape_string($this->connection,json_encode($sub_options))."',
            option_suboptions = '".(!empty($val['fieldOptionIds']) ? mysqli_real_escape_string($this->connection,json_encode($val['fieldOptionIds'])) : '')."',
            required = '".(!empty($val['isRequired']) ? mysqli_real_escape_string($this->connection,$val['isRequired']) : 'no')."'";
        
            mysqli_query($this->connection,$item_query);
        }
    }
    public function deleteItems($items)
    {
        if( is_array($items) && count($items)>0){
            $up_query = "UPDATE evaluation_checklist_items_new SET active='n' where id in (".implode(',',$items).")";
            mysqli_query($this->connection,$up_query);
        }
    }
    public function deleteGroups($group,$template_id)
    {
        if( is_array($group) && count($group)>0){
            $up_query = "UPDATE evaluation_checklist_new SET active='n' where id in (".implode(',',$group).") and template_id=$template_id";
            mysqli_query($this->connection,$up_query);

            $up_item_query = "UPDATE evaluation_checklist_items_new SET active='n' where checklist_id in (".implode(',',$group).")";
            mysqli_query($this->connection,$up_item_query);

        }
    }

    public function saveCheckList($request)
    {        
        $checklist = $request['data'];
        $template_id = $request['template_id'];

        //echo '<pre>'; print_r($_POST['delete_items']); print_r($_POST['delete_groups']); echo '</pre>'; 
        if( !empty($checklist) && $template_id >0  )
        {
            if( !empty($_POST['delete_items']) && count($_POST['delete_items'])>0 )
            {
                $this->deleteItems($_POST['delete_items']);
            }
            if( !empty($_POST['delete_groups']) && count($_POST['delete_groups'])>0 )
            {
                $this->deleteGroups($_POST['delete_groups'],$template_id);
            }
          
            try
            {
                foreach( $checklist as $k=>$section )
                {
                    if( $section['fieldKey'] == "new" )
                    {
                        $this->addGroup($template_id,$section); 
                    }

                    if( $section['fieldKey'] >0 && $section['isUpdate'] )
                    {
                        $up_section = "UPDATE evaluation_checklist_new SET 
                            checklist_title = '".mysqli_real_escape_string($this->connection,$section['fieldLabel'])."'
                            where template_id = $template_id and id = ". $section['fieldKey'];
                        
                        mysqli_query($this->connection,$up_section);
                        
                    }
                    foreach( $section['fields'] as $key=>$val )
                    {
                        if( $val['fieldKey'] == "new" ){
                            $this->addItem($section['fieldKey'],$val);
                        }
                        if($val['isUpdate'] && $val['fieldKey']>0)
                        {
                            $options = $sub_options = [];                
                            $options['inputType'] = $val['inputType'];
                            $options['isRequired'] = $val['isRequired'];
                            $options['fieldKey'] = "";
                            $options['defaultInputValue'] = "";                   
                            foreach( $val['fieldOptionIds'] as $kk=>$vv )
                            {                  
                                $options["fieldOptionIds"][] = ["value" =>$vv['fieldLabel'],"label"=>$vv['fieldLabel'] ];
                                foreach( $vv['fieldSubOptions'] as $i=>$j ){
                                
                                    $sub_options[$vv['fieldLabel']][0]['inputType'] = $vv['inputType'];
                                    $sub_options[$vv['fieldLabel']][0]['isRequired'] = $val['isRequired'];
                                    $sub_options[$vv['fieldLabel']][0]['fieldKey'] = "";
                                    $sub_options[$vv['fieldLabel']][0]['defaultInputValue'] = "";
                                    $sub_options[$vv['fieldLabel']][0]['fieldOptionIds'][] = ["value"=>$i+1,"label"=>$j['fieldLabel']];
                                }                        
                            }

                            $item_query = "UPDATE evaluation_checklist_items_new SET 
                                item_name = '".mysqli_real_escape_string($this->connection,$val['fieldLabel'])."',
                                type = '".mysqli_real_escape_string($this->connection,$val['inputType'])."',
                                options = '".mysqli_real_escape_string($this->connection,json_encode($options))."',
                                sub_options = '".mysqli_real_escape_string($this->connection,json_encode($sub_options))."',
                                option_suboptions = '".(!empty($val['fieldOptionIds']) ? mysqli_real_escape_string($this->connection,json_encode($val['fieldOptionIds'])) : '')."',
                                required = '".(!empty($val['isRequired']) ? mysqli_real_escape_string($this->connection,$val['isRequired']) : 'no')."'
                                where id = '".$val['fieldKey']."'";
                            mysqli_query($this->connection,$item_query);    
                            //echo $item_query."<br>";
                        }
                    }
                }
                return ["status"=>true];
            }
            catch(Exception $e){
                return ["status"=>false];
            }
        }
        return ["status"=>false];
    }
}

?>