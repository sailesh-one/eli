const template = /*html*/`
     <div>
       <button class="btn btn-primary text-light mt-5 ms-5 d-flex justify-content-center align-items-start" @click="openAddModal">Add Role</button>
       <!--Add new role modal start-->
         <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
         <div class="modal-dialog">
            <div class="modal-content">
               <div class="modal-header">
               <h1 class="modal-title fs-5 modal-font" id="addModalLabel">Add New Role</h1>
               <button type="button" class="btn-close" @click="closeAddModal"></button>
               </div>
               <div class="modal-body">
                 <table class="container-fluid">
                  <tbody>
                   <tr>
                     <td>
                      <label class="mt-3 ms-2 me-3">Enter Role name:</label>
                      <input class="form-control form-control-sm mt-3 ms-2 w-50 inline-style" type="text" autocompleted="off" v-model="role_name"/>
                     </td>
                    </tr>
                    <tr>
                     <td>
                      <label class="mt-3 ms-2 me-3">Enter Description:</label>
                      <input class="form-control form-control-sm mt-3 w-50 inline-style me-5" type="text" autocompleted="off" v-model="role_desc"/>
                     </td>
                     </tr>
                     <tr>
                     <td>
                      <label class="mt-3 ms-2 me-4">Select active:</label>
                       <select class="form-select form-select-sm mt-3 ms-4 w-50 inline-style me-5" v-model="is_active">
                          <option value="">Select active type</option>
                          <option value="y">y</option>
                          <option value="n">n</option>
                        </select>
                     </td>
                    </tr>
                   </tbody>
                  </table>    
                </div>
               <div class="modal-footer">
               <button type="button" class="btn btn-secondary" @click="closeAddModal">Cancel</button>
               <button type="button" class="btn btn-success" @click="addRole">Add Role</button>
               </div>
            </div>
         </div>
         </div>
       <!--Add new role modal end-->
     </div>
`;
export const AdminRoles = {
    name: 'Admin-Roles',
    template,
    data()
    {
        return{
           role_name : '',
           role_type:'',
           is_active:'',
           role_desc:'',
        }
    },
    mounted()
    {

    },
    methods:{
        openAddModal()
        {
          $("#addModal").modal('show');
        },
        closeAddModal()
        {
          $("#addModal").modal('hide');
          this.resetFields();
        },
        resetFields()
        {
           this.role_name = '';
           this.role_desc = '';
           this.is_active = '';
        },
        async addRole()
        {
           try
           {
              const data = {
               "action" : "add_role",
               "role_name" : this.role_name,
               "description" : this.role_desc,
               "is_active" : this.is_active,
               "role_type" : 0 
              };

              const response = await $http('POST',`${g.$base_url_api}/admin/admin-roles`,{...data},{});
              if(response.body.status === "ok")
              {
                 $toast('success', response.body.msg || 'Role added.');
                 this.closeAddModal();
              }
              else if(response.body.status === "fail")
              {
                 alert("status:"+response.body.message);
                 $toast('danger', response.body.msg || 'Failed to add role.');
                 this.closeAddModal();
              }
           }
           catch(error)
           {
             alert(error);
           }
        },
        async editRole()
        {
           try
           {
              const data = {
               "action" : "edit_role",
               "role_name" : this.role_name,
               "is_active" : this.is_active,
               "role_type" : 0 
              };

              const response = await $http('POST',`${g.$base_url_api}/admin/admin-roles`,{...data},{});
              if(response.body.status === "ok")
              {
                 alert("status:"+response.body.status);
                 $toast('success', response.body.message || 'Role edited.');
                 this.closeEditModal();
              }
              else if(response.body.status === "ok")
              {
                 alert("status:"+response.body.status);
                 $toast('danger', response.body?.message || 'Failed to edit role.');
                 this.closeEditModal();
              }
           }
           catch(error)
           {
             alert(error);
           }
        }
    }
}
