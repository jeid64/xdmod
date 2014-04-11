Ext.namespace('XDMoD');

XDMoD.CreateUser = Ext.extend(Ext.Panel,  {

   usersRecentlyAdded: false,   // This flag is set to true when a new user has been created via this form
   userTypeRecentlyAdded: 0,    // This holds the value of the user_type of the last user created
   
   initComponent: function(){

		// --------------------------------
			
      var me = this;
      var base_controller = '../controllers/user_admin.php';
		
		var account_request_id = '';                 //conditionally overridden in the call to initialize()
      var account_creation_callback = undefined;   //conditionally overridden in the call to initialize()
      
      var cmbUserMapping = new CCR.xdmod.ui.TGUserDropDown({
         dashboardMode: true,
         user_management_mode: true,
         controllerBase: '../controllers/sab_user.php',
         fieldLabel: 'Map To',
         emptyText: 'User not mapped',
         hiddenName: 'nm_new_user_mapping',
         width: 150
      });

		// --------------------------------
		
      var cmbInstitution = new CCR.xdmod.ui.InstitutionDropDown({
         controllerBase: base_controller,
         disabled: true,
         fieldLabel: 'Institution',
         emptyText: 'No Institution Selected',
         width: 205
      });
      
      cmbInstitution.on('disable', function() {
         cmbInstitution.reset();
      });
            
		// --------------------------------

      var storeUserType = new DashboardStore({
      
         url: base_controller,
         root: 'user_types',
         baseParams: {'operation' : 'enum_user_types'},
         fields: ['id', 'type']
         
      });//storeUserType
      
      var cmbUserType = new Ext.form.ComboBox({

         editable: false,
         //disabled: true,
         //width: 150,
         //listWidth: 150,
         fieldLabel: 'User Type',
         store: storeUserType,
         displayField: 'type',
         triggerAction: 'all',
         valueField: 'id',
         emptyText: 'No User Type Selected',
         width: 160
         
      });//cmbUserType

      cmbUserType.on('disable', function() {
         cmbUserType.reset();
      });
            
		// --------------------------------
		
		var genRandPassword = function (length) {
		
         chars = "abcdefghijklmnopqrstuvwxyz!@#$%-_=+ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
         pass = "";
         
         for(x=0;x<length;x++) {
            i = Math.floor(Math.random() * 62);
            pass += chars.charAt(i);
         }
         
         return pass;
  
      }//genRandPassword

		// --------------------------------
		
      var btnFindUser = new Ext.Button({
               
         text: 'Find',
         width: 60,
         cls: 'user_mapping_find_button',
                        
         handler: function() {

            var fieldsToValidate = [txtFirstName, txtLastName];

				// Sanitization --------------------------------------------
				
				for (i = 0; i < fieldsToValidate.length; i++) {
				
				  fieldsToValidate[i].setValue(fieldsToValidate[i].getValue().trim());
				  fieldsToValidate[i].removeClass('admin_panel_invalid_text_entry');
				
				}
				
				for (i = 0; i < fieldsToValidate.length; i++) {
					
					if (!fieldsToValidate[i].validate()) {
					
						fieldsToValidate[i].addClass('admin_panel_invalid_text_entry');
						
						CCR.xdmod.ui.userManagementMessage(fieldsToValidate[i].formatMessage, false);
										
						return;
						
					}
					
				}//for
        
            me.setUserMapping(txtLastName.getValue() + ', ' + txtFirstName.getValue(), true);
            
         }//handler
               
      });//btnFindUser
            
      var txtUsername = new XDMoD.LimitedField({

         fieldLabel: 'Username',   
         characterLimit: 200,
         emptyText: '5 min, 200 max',
         formatMessage: 'The username must consist of alphanumeric characters only (minimum: 5)<br>Or can be an e-mail address',
         vpattern: '^[a-zA-Z0-9@.]{5,200}$'
         
      });//txtUsername

      var txtPassword = new XDMoD.LimitedField({

         fieldLabel: 'Password',   
         characterLimit: 20,
         emptyText: '5 min, 20 max',
         formatMessage: 'The password must be at least 5 characters long',
         vpattern: '^.{5,20}$',	
         
      });//txtPassword
            
		var fsUserDetails = new Ext.form.FieldSet({
				 
			title: 'User Details',
			cls: 'admin_panel_user_details',

			items: [
			
			   txtUsername,
			   txtPassword,
				
            new Ext.Button({
               text: 'Generate Password',
               cls:'admin_panel_password_generate_button', 
               handler: function() {
               
                  var rand = genRandPassword(15);
                  txtPassword.setValue(rand);
                  
               }
            }),

            cmbUserMapping,
            btnFindUser,

            cmbInstitution

			]
			
		});
      
		// --------------------------------

      var txtFirstName = new XDMoD.LimitedField({

         fieldLabel: 'First Name',   
         characterLimit: 50,
         emptyText: '1 min, 50 max',
         formatMessage: 'The first name must be at least 1 character long',
         vpattern: '^.{1,50}$'
					
      });//txtFirstName

      var txtLastName = new XDMoD.LimitedField({

			fieldLabel: 'Last Name',   
			characterLimit: 50,
			emptyText: '1 min, 50 max',
			formatMessage: 'The last name must be at least 1 character long',
			vpattern: '^.{1,50}$'
					
      });//txtLastName

      var txtEmailAddress = new XDMoD.LimitedField({

			fieldLabel: 'E-Mail Address',   
			characterLimit: 200,
			emptyText: '6 min, 200 max',
			formatMessage: 'You must specify a valid email address<br>(e.g. user@domain.com)',
			vpattern: '^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$',		
					
      });//txtEmailAddress            		

		var fsUserInformation = new Ext.form.FieldSet({
		
			title: 'User Information',
			cls: 'admin_panel_section_user_information',
			items: [
			
			   txtFirstName,
            txtLastName,
            txtEmailAddress

			]
			
		});

		// --------------------------------

      this.setFirstName = function(v) {
         txtFirstName.setValue(v);
      }

      this.setLastName = function(v) {
         txtLastName.setValue(v);
      }
      
      this.setEmailAddress = function(v) {
         txtEmailAddress.setValue(v);
      }

      this.setUsername = function(v) {
         txtUsername.setValue(v);
      }
      
      this.setUserMapping = function(v, b) {
         
         ServerRequest({

            url: '../controllers/sab_user.php', 
            
            params: {
               operation: 'enum_tg_users',
               start: 0,
               limit: 300,
               search_mode: 'formal_name',
               userManagement: 'y',
               dashboard_mode: 1,
               pi_only: 'n',
               query: v
            },	
            
            method: 'POST',
            callback: function(options, success, response) { 

               if (success) {

                  var json = Ext.util.JSON.decode(response.responseText);
                  
                  if (json.success) {

                     if (json.total_user_count == 1) {

                        cmbUserMapping.initializeWithValue(json.users[0].person_id, json.users[0].person_name);
                        
                     }
                     else {
                     
                        if (b && b == true) {
                        
                           if (json.total_user_count > 1)
                              CCR.xdmod.ui.userManagementMessage('Multiple matches found for \'' + v + '\'', false);
                           else
                              CCR.xdmod.ui.userManagementMessage('No match could be found for \'' + v + '\'', false);
                        }
                        
                     }
                     
                  }//if (json.success)

               }

            }//callback

         });//ServerRequest

      };//this.setUserMapping
      		
		// --------------------------------		

		var roleGridClickHandler = function() {
	
         var sel_roles = newUserRoleGrid.getSelectedRoles();

         cmbInstitution.setDisabled(sel_roles.itemExists('cc') == -1);

      };

      var newUserRoleGrid = new XDMoD.Admin.RoleGrid({
         cls: 'admin_panel_section_role_assignment_n',
         role_description_column_width: 140,
         layout: 'fit',
         height: 200,
         selectionChangeHandler: roleGridClickHandler
      });

      // --------------------------------

      this.setCallback = function(callback) {
      
         account_creation_callback = callback;
      
      }
      
      
      this.reset = function() {
      
         account_request_id = '';
         account_creation_callback = undefined;
         
      }

      // --------------------------------

      this.initialize = function(config) {
      
         if (config.accountRequestID)
            account_request_id = config.accountRequestID;
            
         if (config.accountCreationCallback)
            account_creation_callback = config.accountCreationCallback;
         
         this.setFirstName();
         this.setLastName();
         this.setEmailAddress();
         this.setUsername();
         txtPassword.setValue('');
         
         cmbUserMapping.reset();
         cmbUserType.reset();
         
         cmbInstitution.reset();
         cmbInstitution.setDisabled(true);
         
         newUserRoleGrid.reset();
         
      }
      
      var fsRoleAssignment = new Ext.form.FieldSet({

         //layout: 'fit',
         //height: 226,
         //width: 300,
         title: 'Role Assignment',

         items: [
            newUserRoleGrid
         ]

      });

      // --------------------------------

      var fsUserType = new Ext.form.FieldSet({

         //layout: 'fit',
         //width: 300,
         title: 'Additional Settings',
         labelAlign: 'left',

         items: [
            cmbUserType
         ]

      });

      // --------------------------------

      var btnCreateUser = new Ext.Button({

         text: 'Create User',

         iconCls: 'admin_panel_btn_create_user',

         handler: function() {            
            
            var institution = (cmbInstitution.getValue().length > 0) ? cmbInstitution.getValue() : -1;
            			   
				var fieldsToValidate = [txtFirstName.id, txtLastName.id, txtEmailAddress.id, txtUsername.id, txtPassword.id];

				// Sanitization --------------------------------------------
				
				var incomplete_fields = false;
				
				cmbUserMapping.removeClass('admin_panel_invalid_text_entry');
				cmbInstitution.removeClass('admin_panel_invalid_text_entry');
				cmbUserType.removeClass('admin_panel_invalid_text_entry');
				
				for (i = 0; i < fieldsToValidate.length; i++)
				  Ext.getCmp(fieldsToValidate[i]).removeClass('admin_panel_invalid_text_entry');
				
				for (i = 0; i < fieldsToValidate.length; i++) {
					
					if (!Ext.getCmp(fieldsToValidate[i]).validate()) {
					
						Ext.getCmp(fieldsToValidate[i]).addClass('admin_panel_invalid_text_entry');
						incomplete_fields = true;
						
						CCR.xdmod.ui.userManagementMessage(Ext.getCmp(fieldsToValidate[i]).formatMessage, false);
										
						return;
						
					}	
					
				}
				
				if (cmbUserMapping.getValue() == '') {
				
					cmbUserMapping.addClass('admin_panel_invalid_text_entry');
					incomplete_fields = true;
										
					CCR.xdmod.ui.userManagementMessage('This user must be mapped to a XSEDE Account<br>(Using the drop-down list)', false);
					return;
					
				}
				
				if (cmbUserMapping.getValue() == cmbUserMapping.getRawValue()) {

					cmbUserMapping.addClass('admin_panel_invalid_text_entry');
					incomplete_fields = true;
										
					CCR.xdmod.ui.userManagementMessage('Cannot find <b>' + cmbUserMapping.getValue() + '</b> in the directory.<br>Please select a name from the drop-down list.', false);
					return;
									
				}
				 
				if (incomplete_fields) {
				   CCR.xdmod.ui.userManagementMessage('Please supply information to the fields highlighted in pink.', false);
					return;
				}
				
				mapped_user_id = cmbUserMapping.getValue();

            // ===========================================     

            var sel_roles = newUserRoleGrid.getSelections();       

            if (newUserRoleGrid.areRolesSpecified() == false) {

               CCR.xdmod.ui.userManagementMessage('This user must have at least one role.', false);            
               return;
               
            }

            if (newUserRoleGrid.isPrimaryRoleSpecified() == false) {

               CCR.xdmod.ui.userManagementMessage('This user must have a primary role assigned.', false);            
               return;
               
            }            

            var sel_roles = newUserRoleGrid.getSelections();

            // ===========================================
            				
            if ( (sel_roles.mainRoles.itemExists('cc') == 1) && (cmbInstitution.getValue().length == 0) ){
               cmbInstitution.addClass('admin_panel_invalid_text_entry');
               CCR.xdmod.ui.userManagementMessage('An institution must be specified for a user having a role of Campus Champion.', false);
               return;
            }
            
            if (cmbUserType.getValue().length == 0){
               cmbUserType.addClass('admin_panel_invalid_text_entry');
               CCR.xdmod.ui.userManagementMessage('This user must have a type associated with it.', false);
               return;
            }
            						
				// Submit request --------------------------------------------

            var objParams = {

               operation: 'create_user',

               account_request_id: account_request_id,
               
               first_name: Ext.getCmp(fieldsToValidate[0]).getValue(),
               last_name: Ext.getCmp(fieldsToValidate[1]).getValue(),
               email_address: Ext.getCmp(fieldsToValidate[2]).getValue(),
               username: Ext.getCmp(fieldsToValidate[3]).getValue(),
               password: encodeURIComponent(Ext.getCmp(fieldsToValidate[4]).getValue()),

               roles: Ext.util.JSON.encode(sel_roles),
               assignment: mapped_user_id,
               institution: institution,
               user_type: cmbUserType.getValue()

            };

				ServerRequest({

 					url: base_controller, 
					params: objParams,	
					method: 'POST',
 					callback: function(options, success, response) { 

						if (success) {

							var json = Ext.util.JSON.decode(response.responseText);
							
							if (json.success){
							
                        me.usersRecentlyAdded = true;
                        me.userTypeRecentlyAdded = json.user_type;
                        
                     }
                     
							CCR.xdmod.ui.userManagementMessage(json.message, json.success);
							 
						   if (json.success) {
						      
						      if (account_creation_callback) account_creation_callback();
						   
						   }
							 
						}
						else {
							CCR.xdmod.ui.userManagementMessage('There was a problem connecting to the portal service provider.', false);
						}

					}//callback

				});//ServerRequest
			
			}
		
		});
		
		// --------------------------------
						
		Ext.apply(this, {
		
				//frame: true,
				title: 'New User',
				layout: 'border',
				border: false,
				
				bbar: {
					items: [
					   btnCreateUser, 
					   '->',				   
                  new Ext.Button({
                     text: 'Close',
                     iconCls: 'general_btn_close', 
                     handler: function() {
                        me.parentWindow.hide();
                     }
                  })
               ]
				},
				items: {
					region: 'center',
					layout: 'column',
					border: false,
					baseCls:'x-plain',
					layoutConfig: {columns: 2},
					items: [{
					   border: false,
						columnWidth:1,
						//width: 330,
						baseCls:'x-plain',
						bodyStyle:'padding:5px 0px 5px 5px',
						items:[fsUserInformation, fsUserDetails]
					},{
                  border: false,
						//columnWidth:.5, 
						width: 300,
						baseCls:'x-plain',
						bodyStyle:'padding:5px 5px 5px 5px',
						items:[fsRoleAssignment, fsUserType]
					}]
				}

		});
		
 		XDMoD.CreateUser.superclass.initComponent.call(this);
        	
	},//initComponent
	
	onRender : function(ct, position){
    	
		XDMoD.CreateUser.superclass.onRender.call(this, ct, position);
        	
	}
        
});