<?php
  class User
  {
      public $con;

      public function __construct()
      {
        global $connection,$auth;
        $this->con = $connection;
      }

      // Admin users 

      public function getUsers($page = 1,$perPage = 10)
      {
          $count_query = "SELECT COUNT(*) as total FROM users_admin";
          $count_res = mysqli_query($this->con,$count_query);
          if(!$count_res)
          {
            logSqlError(mysqli_error($this->con), $count_query, 'admin-users', true);
          }
          $count_cnt = mysqli_fetch_assoc($count_res);
          
          $count = $count_cnt['total'];

          $start = ($page - 1) * $perPage;
          $start_count = $count > 0 ? ($start + 1) : 0;
          $end_count = min($start + $perPage, $count);

          $user_query = "SELECT u.id,u.name,u.email,u.mobile,u.password,u.active,r.role_name as role_name,u.created_at,u.updated_at FROM `users_admin` u left join config_roles r on r.id = u.id and r.role_type = 0 WHERE 1 LIMIT $start , $perPage"; 
          $user_res = mysqli_query($this->con,$user_query);
          if(!$user_res)
          {
            logSqlError(mysqli_error($this->con), $user_query, 'admin-users', true);
          }
          $users_list = [];
          while($row = mysqli_fetch_assoc($user_res))
          {
            $users_list[] = $row;
          }

          if(empty($users_list))
          {
              api_response(200,"empty","Empty users list.");
          }
          api_response(200,"ok","Users list fetched successsfully.",["users_list" => $users_list,"start_count" => $start_count,"end_count" => $end_count,"total" => $count]);
      }

       // Admin roles

      public function getRoles()
      {
          $role_query = "SELECT id as role_id,role_type,role_name,description,active FROM `config_roles` where role_type = 0"; 
          $role_res = mysqli_query($this->con,$role_query);
          if(!$role_res)
          {
            logSqlError(mysqli_error($this->con), $role_query, 'admin-roles', true);
          }
          $roles_list = [];
          while($row = mysqli_fetch_assoc($role_res))
          {
            $roles_list[] =$row;
          }
          if(empty($roles_list))
          {
              api_response(200,"empty","Empty roles list.");
          }
          else
          {
             api_response(200,"ok","Roles list fetched successsfully.",["roles_list" => $roles_list]);
          }
      }

      public function addUser()
      {
         $dup_ins_query = "SELECT COUNT(*) as cnt FROM users_admin WHERE name = '".mysqli_real_escape_string($this->con,$_POST['name'])."' AND email = '".mysqli_real_escape_string($this->con,$_POST['email'])."' OR mobile = '".mysqli_real_escape_string($this->con,$_POST['mobile'])."'";
         $dup_ins_res = mysqli_query($this->con,$dup_ins_query);
         if(!$dup_ins_res)
         {
           logSqlError(mysqli_error($this->con),$dup_ins_query,"add-user",true);
         }

         $dup_count_res = mysqli_fetch_assoc($dup_ins_res);
         $dup_count = $dup_count_res['cnt'];
         if($dup_count > 0)
         {
           api_response(409,'fail','Duplicate entry.');
           return false;
         }
         $role_id_query = "SELECT id as role_id from config_roles WHERE role_name = '".mysqli_real_escape_string($this->con,$_POST['role_name'])."'";
         $role_id_res = mysqli_query($this->con,$role_id_query);
         $role_id_data = mysqli_fetch_assoc($role_id_res);
         $role_id = $role_id_data['role_id'];

         $ins_query = "INSERT INTO users_admin (name,email,mobile,role_id,active) VALUES ('".mysqli_real_escape_string($this->con,$_POST['name'])."','".mysqli_real_escape_string($this->con,$_POST['email'])."','".mysqli_real_escape_string($this->con,$_POST['mobile'])."',$role_id,'".mysqli_real_escape_string($this->con,$_POST['is_active'])."')";
         $ins_res = mysqli_query($this->con,$ins_query);
         if(!$ins_res)
         {
           logSqlError(mysqli_error($this->con),$ins_res,"add-user",true);
           return false;
         }
         $insert_id = mysqli_insert_id($this->con);
         $user = [];
         $res = mysqli_query($this->con,"SELECT * FROM users_admin WHERE id = $insert_id");
         if($res)
         {
           $user = mysqli_fetch_assoc($res);
         }
         return $user;
      }
  }

?>