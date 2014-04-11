XDMoD.ProfileRoleDelegation = Ext.extend(Ext.Panel,  {

   height:340,
   layoutConfig:{ columns:1 },
   border:false,
   buttonAlign: 'center',
   frame: true,
   resizable:false,
   title:'Role Delegation',
   //padding: '10 12 0 10',
   cls:'role_manager',

   initComponent: function(){

      var self = this;
      
      var storeCenterStaff = new Ext.data.JsonStore({
      
         url: 'controllers/role_manager.php',
         baseParams: {operation: 'enum_center_staff_members'},
         root: 'members',
         autoLoad: true,
         fields: ['id', 'name']
         
      });//storeCenterStaff
 
      // ---------------------------------------------------------
           
      storeCenterStaff.on('load', function(store, records, options){
      
         lblStatus.setVisible(records.length == 0);
         
         sectionAssign.setVisible(records.length != 0);
         lblMemberStatus.setVisible(records.length != 0);
      
      });
      
      // ---------------------------------------------------------
      
      var cmbCenterStaff = new Ext.form.ComboBox({

         editable: false,
         width: 140,
         fieldLabel: 'Staff Member',
         store: storeCenterStaff,
         triggerAction: 'all',
         displayField: 'name',
         valueField: 'id',
         emptyText: 'No Member Selected',
         
         listeners:{

            select:function(combo, value, index) {

   				var conn = new Ext.data.Connection;
   				conn.request({
   
    					url: 'controllers/role_manager.php', 
   					params: {operation: 'get_member_status', member_id: combo.getValue()},	
   					method: 'POST',
    					callback: function(options, success, response) { 
   
   						if (success) {
                        
   							var json = Ext.util.JSON.decode(response.responseText);
   							
   							if (json.success) {
   													  
                           if (json.eligible == true)
                              document.getElementById('role_manager_lbl_member_status').innerHTML = '';
                           else 
                              document.getElementById('role_manager_lbl_member_status').innerHTML = '<b>' + combo.getRawValue() + '</b><br />' + json.message;
   							     
                           btnElevateUser.setVisible(json.eligible);
                           btnDowngradeUser.setVisible(!json.eligible);
                           //lblMemberStatus.setVisible(!json.eligible);  <-- does not work well in Opera
                           
   						   }
   						   else {
   							  
                           document.getElementById('role_manager_lbl_member_status').innerHTML = '<b>' + combo.getRawValue() + '</b><br /><span style="color: #00f">' + json.message + '</span>';
                           lblMemberStatus.setVisible(true);

                           btnElevateUser.setVisible(false);
                           btnDowngradeUser.setVisible(false);   							  

                        }
   							
   						}
   						else {
   							Ext.MessageBox.alert('Role Manager', 'There was a problem connecting to the portal service provider.');
   						}
   
   					}//callback
   
   				});//conn.request
               
            }//select

         }//listeners

      });//cmbCenterStaff
      
      // ---------------------------------------------------------
      
      var sectionAssign = new Ext.FormPanel({

         labelWidth: 95,
         hidden: true,
         frame:true,
         title: 'Delegate Center Director Privileges',
         bodyStyle:'padding:5px 5px 0',
         width: 350,
         defaults: {width: 200},
         defaultType: 'textfield',

         items: [

            cmbCenterStaff
            
         ]

      });//sectionAssign      

      // ---------------------------------------------------------   
            
      var btnElevateUser = new Ext.Button({
      
         text: 'Upgrade Staff Member To Center Director',
         hidden: true,
         cls: 'btn_elevate_user',
         
         handler: function() { 	   

            var conn = new Ext.data.Connection;
            conn.request({
            
               url: 'controllers/role_manager.php', 
               params: {operation: 'upgrade_member', member_id: cmbCenterStaff.getValue()},	
               method: 'POST',
               callback: function(options, success, response) { 
            
               	if (success) {
                     
               		var json = Ext.util.JSON.decode(response.responseText);
               		
               		if (json.success) {
               		
               		   document.getElementById('role_manager_lbl_member_status').innerHTML = '<b>' + cmbCenterStaff.getRawValue() + '</b><br />' + json.message;
                        btnElevateUser.setVisible(false);
                        btnDowngradeUser.setVisible(true);
                        lblMemberStatus.setVisible(true);
                     
                     }
               	   else
               		  Ext.MessageBox.alert('Role Manager', json.message);

               		
               	}
               	else {
               		Ext.MessageBox.alert('Role Manager', 'There was a problem connecting to the portal service provider.');
               	}
            
               }//callback
            
            });//conn.request
            
         },
         
         flex:1

      });//btnElevateUser

      // ---------------------------------------------------------   
            
      var btnDowngradeUser = new Ext.Button({
      
         text: 'Revoke Center Director Privileges',
         hidden: true,
         cls: 'btn_downgrade_user',
         
         handler: function() { 	   

            var conn = new Ext.data.Connection;
            conn.request({
            
               url: 'controllers/role_manager.php', 
               params: {operation: 'downgrade_member', member_id: cmbCenterStaff.getValue()},	
               method: 'POST',
               callback: function(options, success, response) { 
            
               	if (success) {
                     
               		var json = Ext.util.JSON.decode(response.responseText);
               		
               		if (json.success) {
                     
                        btnDowngradeUser.setVisible(false);
                        btnElevateUser.setVisible(true);
                        
                        document.getElementById('role_manager_lbl_member_status').innerHTML = '';

                        //lblMemberStatus.setVisible(false);
                     
                     }
               	   else
               		  Ext.MessageBox.alert('Role Manager', json.message);

               		
               	}
               	else {
               		Ext.MessageBox.alert('Role Manager', 'There was a problem connecting to the portal service provider.');
               	}
            
               }//callback
            
            });//conn.request
            
         },
         
         flex:1

      });//btnDowngradeUser

      // ---------------------------------------------------------   
            
      var lblMemberStatus = new Ext.form.Label ({
         hidden: true,
         html: '<center><div class="lbl_member_status" id="role_manager_lbl_member_status">Select a staff member using the list above.</div></center>'
      });

      // ---------------------------------------------------------   
      
      var lblStatus = new Ext.form.Label ({
         hidden: true,
         html: '<center><div style="padding-top: 6px; color: #000">No staff members for your center could be found.<br /><br />' +
               'Please contact the XDMoD portal team at<br /><a href="mailto:' + CCR.xdmod.tech_support_recipient + '?subject=Center Staff Accounts">' + CCR.xdmod.tech_support_recipient + '</a>' +
               '<br />to request center staff accounts.<br /><br />' + 
               'You will be able to use this feature once<br />center staff accounts have been established.</div></center>'
      });      

      // ---------------------------------------------------------      

      this.on('render', function() {
         storeCenterStaff.load();
      });
      
      Ext.apply(this, {

         items:[ 
            sectionAssign,
            lblMemberStatus,
            btnElevateUser,
            btnDowngradeUser,
            lblStatus
         ],
         
         bbar: {
         
            items: [
               '->',
               self.parentWindow.getCloseButton()
            ]
            
         }

      });
      
      XDMoD.ProfileRoleDelegation.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.ProfileRoleDelegation