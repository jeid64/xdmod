Ext.namespace('XDMoD');

var displayExceptionEmails = function() {

   ServerRequest({
         
      url: '../controllers/user_admin.php',
      params:  { operation: 'enum_exception_email_addresses' },
      method: 'POST',
      callback: function(options, success, response) {
         
         if (success) {
         
            var json = Ext.util.JSON.decode(response.responseText);
         
            if (json.status == 'success'){
         
               var addresses = new Array();
               
               for(var i = 0; i < json.email_addresses.length; i++)
                  addresses.push(json.email_addresses[i]);
               
               var message = 'The following addresses can be mapped to multiple XDMoD accounts:<br><br>' + addresses.join("<br />");
               
               CCR.xdmod.ui.generalMessage('Exception E-Mail Addresses', message, true, 5000)
               
            }
            else {
               CCR.xdmod.ui.userManagementMessage(json.status, false);
            }	
         
         }
         else {
            CCR.xdmod.ui.userManagementMessage('There was a problem connecting to the portal service provider.', false);
         }
         
      }//callback
         
   });//ServerRequest
      
}//displayExceptionEmails

// =====================================================================================

XDMoD.ExistingUsers = Ext.extend(Ext.Panel,  {

   initFlag: 0,
   userStore: null,     // Assigned to Ext.data.JsonStore in initComponent(...)
   
   userStoreLoadReset: true,  // If the userStore is reloaded, userStoreLoadReset 
                              // determines whether the UserDetails section is to be reset 
   
   groupToggle: null,   // Assigned to Ext.SplitButton in initComponent(...)
   cachedUserTypeID: 0,
   cachedAutoSelectUserID: undefined,

   reloadUserList: function(user_type, select_user_with_id) {
 
      this.cachedAutoSelectUserID = select_user_with_id;
      
      if (this.userTypes.length == 0) {
      
         //"Current Users" tab has not yet been visited.  Simply cache the user type ID so that when the 
         // user types store does load, the intended category can be fetched.
         
         this.cachedUserTypeID = user_type;
         
      }
      
      for (var i = 0; i < this.userTypes.length; i++) {
      
         if (this.userTypes[i].id == user_type) {
         
            this.cachedUserTypeID = user_type;
         
            this.groupToggle.setText(this.userTypes[i].text);
            Dashboard.ControllerProxy(this.userStore, { operation: 'list_users', group: this.userTypes[i].id });
            
            return;
            
         }//if
         
      }//for
         
   },

   initComponent: function(){

      function formatDate(value){
         return value ? value.dateFormat('M d, Y') : '';
      }

      this.userTypes = new Array();
      
      var self = this;
      var selected_user_id = -1;
      var selected_username = '';
      
      var mapping_cached_person_name = '';
      var mapping_cached_person_id = '';
      var cached_user_type = '';

      var user_update_callback = undefined; 
      
      var settingsAreDirty = false; 

      // variable used to set a state/phase regarding the means in which the combo box data store gets reloaded
      var tg_user_list_phase = '';

      var tg_user_list_page_size = 300;

      // shorthand alias
      var fm = Ext.form;

      // ------------------------------------------
      
      self.setCallback = function (callback) {
         user_update_callback = callback;
      }
      
      // ------------------------------------------   
         
      function userRenderer(val, metaData, record, rowIndex, colIndex, store){
                 
         var entryData = store.getAt(rowIndex).data;
   
         var color;

         if (entryData.account_is_active == '1') color = '000';
         if (entryData.account_is_active == '0') color = 'f00';

         return '<div style="color: #' + color + '">' + val + '</div>';

      }//userRenderer
      
      // ------------------------------------------

      function loggedInRenderer(val, metaData, record, rowIndex, colIndex, store){
      
         if (val != 0) {
         
            color = '#00f';
         
            d = new Date(val);
            d = DateUtilities.convertDateToProperString(d);
            
         } 
         else {
         
            color = '#888';
            d = 'Never logged in';
         
         }
         
         return '<div style="color: ' + color + '">' + d + '</div>';

      }//loggedInRenderer

      // ------------------------------------------

      var cm = new Ext.grid.ColumnModel({

         defaults: {
            sortable: true,
            hideable: false,       
            resizable: true
         },
        
         columns: [
            {
            
               id: 'common',
               header: 'Username',
               dataIndex: 'username',
               width: 90,
               renderer: userRenderer
               
            }, 
            {
            
               header: 'First Name',
               dataIndex: 'first_name',
               width: 120,
               renderer: userRenderer
               
            }, 
            {
            
               header: 'Last Name',
               dataIndex: 'last_name',
               width: 120,
               renderer: userRenderer
               
            },
            {
            
               header: 'Last Logged In',
               dataIndex: 'last_logged_in',
               width: 150,
               renderer: loggedInRenderer
               
            }
         ]

      });//cm

      // ------------------------------------------------------------------

      var comboChangeHandler = function(f, newValue, oldValue) {
      
         settingsAreDirty = true;
         saveIndicator.show();
         
      };//comboChangeHandler
      
      // ------------------------------------------------------------------

      var storeUserType = new DashboardStore({
      
         url: '../controllers/user_admin.php',
         root: 'user_types',
         autoLoad: true,
         baseParams: {'operation' : 'enum_user_types'},
         fields: ['id', 'type']
         
      });//storeUserType
      
      var cmbUserType = new Ext.form.ComboBox({

         editable: false,
         //disabled: true,
         width: 165,
         listWidth: 165,
         fieldLabel: 'User Type',
         store: storeUserType,
         displayField: 'type',
         triggerAction: 'all',
         valueField: 'id',
         emptyText: 'No User Type Selected',
         listeners: { change: comboChangeHandler }
         
      });//cmbUserType

      cmbUserType.on('disable', function() {
         cmbUserType.reset();
      });
            
      // ------------------------------------------------------------------
      
      var mnuUserTypeFilter = new Ext.menu.Menu({
      
         plain: true,
         showSeparator: false,
         cls: 'no-icon-menu',
                     
         items: []
         
      });//mnuUserTypeFilter
      
      mnuUserTypeFilter.on('click', function(menu, menuItem, e) {
      
         if(self.inDirtyState() == true) {
         
            Ext.Msg.show({
                  
               maxWidth: 800,
               minWidth: 400,
               
               title: 'Unsaved Changes',
               
               msg: "There are unsaved changes to this account.<br />" +
                    "Do you wish to save these changes before continuing?<br /><br />" +
                    "If you choose <b>No</b>, you will lose all your changes.",
                    
               buttons: {yes: "Yes (go back and save)", no: "No (discard changes)"},
               
               fn: function(resp) {
                                       
                  if (resp == 'yes')
                     return;
                     
                  if (resp == 'no') {
                     self.resetDirtyState();
                     saveIndicator.hide();
                     self.reloadUserList(menuItem.type_id);
                  }
               
               },
               
               icon: Ext.MessageBox.QUESTION
                     
            });//Ext.Msg.show
          
            return;
              
         }//if(self.inDirtyState() == true)
         
         self.reloadUserList(menuItem.type_id);
      
      });//mnuUserTypeFilter

      // ------------------------------------------------------------------
            
      this.on('activate', function() {
      
         ServerRequest({

            url: '../controllers/user_admin.php', 
            params: {operation: 'enum_user_types'},	
            method: 'POST',
            callback: function(options, success, response) { 

               if (success) {
                                    
                  var json = Ext.util.JSON.decode(response.responseText);

                  for (var i = 0; i < json.user_types.length; i++) {
                  
                     self.userTypes.push({ text: json.user_types[i].type + ' Users', id: json.user_types[i].id });
                     
                     mnuUserTypeFilter.addItem({ text: json.user_types[i].type + ' Users', type_id: json.user_types[i].id });
                     
                  }//for
                  
                  // Add entry to account for XSEDE Users...   
                  self.userTypes.push({ text: 'XSEDE Users', id: CCR.xdmod.XSEDE_USER_TYPE });
                  mnuUserTypeFilter.addItem({ text: 'XSEDE Users', type_id: CCR.xdmod.XSEDE_USER_TYPE });
                  
                  var user_type_to_load = (self.cachedUserTypeID > 0) ? self.cachedUserTypeID : json.user_types[0].id;
                  
                  self.reloadUserList(user_type_to_load, self.cachedAutoSelectUserID);
                  
               }
               else {
               
                  CCR.xdmod.ui.userManagementMessage('There was a problem connecting to the portal service provider.', false);
               
               }

            }//callback

         });//ServerRequest
         
      }, this, {single: true});
      
      // ------------------------------------------
      
      /*
      var storeInstitution = new DashboardStore({
      
         url: '../controllers/user_admin.php',
         autoLoad: true,
         root: 'institutions',
         baseParams: { 'operation' : 'enum_institutions' },
         fields: ['id', 'name']
         
      });//storeInstitution
      		      
      var cmbInstitution = new Ext.form.ComboBox({

         name: 'myGroups',
         editable: false,
         width: 165,
         listWidth: 310,
         fieldLabel: 'Institution',
         store: storeInstitution,
         displayField: 'name',
         triggerAction: 'all',
         valueField: 'id',
         emptyText: 'No Institution Selected',
         listeners: { change: comboChangeHandler }
         
      });//cmbInstitution
      */

      var cmbInstitution = new CCR.xdmod.ui.InstitutionDropDown({
         //disabled: true,
         fieldLabel: 'Institution',
         emptyText: 'No Institution Selected',
         width: 165,
         listWidth: 310,
         listeners: { change: comboChangeHandler }
      });
      
      cmbInstitution.on('disable', function() {
         cmbInstitution.reset();
      });
            
      // ------------------------------------------

      var storeUserListing = new DashboardStore({

         autoload: true,
         url: '../controllers/user_admin.php',
         baseParams: {operation: 'list_users', group: ''},
         storeId: 'storeUserListing',
         root: 'users',
         fields: ['id', 'username', 'first_name', 'last_name', 'account_is_active', 'last_logged_in']

      });//storeUserListing

      this.userStore = storeUserListing;

      storeUserListing.on('load', function(store, records, options){

         if (self.userStoreLoadReset == false) {
            self.userStoreLoadReset = true;
            return;
         }
         
         userEditor.setTitle('User Details');
         
         userEditor.showMask();
         cmbUserType.setDisabled(true);
         btnSaveChanges.setDisabled(true);
         existingUserEmailField.setValue('');

         if (self.initFlag == 1) {
         
            Ext.getCmp('txtAccountTimestamps').update('');
         
            /*
            Ext.getCmp('txtAccountCreated').update('');
            Ext.getCmp('txtAccountUpdated').update('');
            */
         
            Ext.getCmp('txtAccountStatus').update('');	
            
         }

         self.initFlag = 1;

         cmbUserMapping.setValue('');
         cmbInstitution.setDisabled(true);

         roleGrid.reset();
         
         lblXSEDEUser.hide();
         cmbUserType.show();
         
         if (self.cachedAutoSelectUserID != undefined) {
            
            var targetIndex = store.find('id', self.cachedAutoSelectUserID);
            
            grid.getSelectionModel().selectRow(targetIndex);
            
            grid.fireEvent('cellclick', grid, targetIndex);

            self.cachedAutoSelectUserID = undefined;
         
         }

      });//storeUserListing.on('load')

      // ------------------------------------------

      var userManagementAction = function(objParams) {

         ServerRequest({

            url: '../controllers/user_admin.php', 
            params: objParams,	
            method: 'POST',
            callback: function(options, success, response) { 

               if (success) {

                  var json = Ext.util.JSON.decode(response.responseText);
                  
                  if (json.success) {

                     if (objParams.operation == 'delete_user' || objParams.operation == 'update_user') {

                        if (objParams.operation == 'delete_user') {

                           selected_user_id = -1;
                           selected_username = '';
                        
                        }
                        
                        if (objParams.operation == 'update_user') {
                        
                           self.userStoreLoadReset = false;
                           fetchUserDetails(objParams.uid, false);
                        
                        }
                        
                        // Refresh the user list based on the currently selected user list view
                        self.reloadUserList(self.cachedUserTypeID);

                     }

                     if (objParams.operation !== 'empty_report_image_cache') {
                     
                        if (user_update_callback) {
                           user_update_callback();
                        }
                     
                     }
                     
                  }//if (json.success)
                                    
                  CCR.xdmod.ui.userManagementMessage(json.message, json.success);

               }
               else {
                  CCR.xdmod.ui.userManagementMessage('There was a problem connecting to the portal service provider.', false);
               }

            }//callback

         });//ServerRequest

      }//userManagementAction

      // ------------------------------------------

      var usersTypeSplitButton = new Ext.Button({

         scope: this,
         width: 50,
         text: '',
         cls: 'no-icon-menu',

         menu: mnuUserTypeFilter

      });//usersTypeSplitButton

      this.groupToggle = usersTypeSplitButton;

      // ------------------------------------------

      var actionEmptyReportImageCache = function() {

         Ext.Msg.show({
         
            maxWidth: 800,
            title: 'Empty Report Image Cache',
            msg: "Are you sure you want to empty the report image cache for user <b>" + selected_username + "</b> ?",
            buttons: Ext.Msg.YESNO,
            fn: function(resp) { 
               if (resp == 'yes'){
                  userManagementAction({operation: 'empty_report_image_cache', uid: selected_user_id});
               }
            },
         
            icon: Ext.MessageBox.QUESTION
            
         });      
      
      };//actionEmptyReportImageCache

      // ------------------------------------------

      var actionDeleteAccount = function() {

         if (selected_user_id == -1) {
            CCR.xdmod.ui.userManagementMessage('You must first select a user to delete.', false);
            return;
         }
         
         Ext.Msg.show({
         
            maxWidth: 800,
            title: 'Delete User',
            msg: "Are you sure you want to delete user <b>" + selected_username + "</b> from the portal ?",
            buttons: Ext.Msg.YESNO,
            fn: function(resp) { 
               if (resp == 'yes'){
                  userManagementAction({operation: 'delete_user', uid: selected_user_id});
               }
            },
         
            icon: Ext.MessageBox.QUESTION
            
         });

      };//actionDeleteAccount

      // ------------------------------------------

      var actionToggleAccountStatus = function(e) {

            var action = document.getElementById('lblAccountState').innerHTML.split(' ')[0];            

            if (selected_user_id == -1) {
               CCR.xdmod.ui.userManagementMessage('You must first select a user to ' + action.toLowerCase() + '.', false);
               return;
            }

            Ext.Msg.show({
            
               maxWidth: 800,
               title: action + ' User',
               msg: "Are you sure you want to " + action.toLowerCase() + " access for user <b>" + selected_username + "</b> ?",
               buttons: Ext.Msg.YESNO,
               
               fn: function(resp) { 
                  if (resp == 'yes'){
                     userManagementAction({operation: 'update_user', uid: selected_user_id, is_active: (action == 'Enable') ? 'y' : 'n' });
                  }
               },
               
               icon: Ext.MessageBox.QUESTION
               
            });//Ext.Msg.show      
      
      }//actionToggleAccountStatus

      // ------------------------------------------

      var actionPasswordReset = function() {

         if (selected_user_id == -1) {
            CCR.xdmod.ui.userManagementMessage('You must first select a user you wish to send a password reset e-mail to.', false);
            return;
         }
         
         Ext.Msg.show({
         
            maxWidth: 800,
            title: 'Password Reset',
            msg: "Are you sure you want to send a password reset e-mail to <b>" + selected_username + "</b> ?",
            buttons: Ext.Msg.YESNO,
         
            fn: function(resp) { 
               if (resp == 'yes'){
                  userManagementAction({operation: 'pass_reset', uid: selected_user_id});
               }
            },
         
            icon: Ext.MessageBox.QUESTION
         
         });//Ext.Msg.show

      };//actionPasswordReset

      // ------------------------------------------

      var existingUserEmailField = new XDMoD.LimitedField({ 

         fieldLabel: 'E-Mail Address',   
         characterLimit: 200,
         emptyText: '6 min, 200 max',
         vpattern: '^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,3}$',						
         //id: 'existinguser_email_addr', 
         width: 165,
         flex: 1

      });//existingUserEmailField
      
      existingUserEmailField.on('change', function(f, newValue, oldValue) {
      
         settingsAreDirty = true;
         saveIndicator.show();
         
      });

      // ------------------------------------------      
      
      var cmbUserMapping = new CCR.xdmod.ui.TGUserDropDown({
      
         controllerBase: '../controllers/sab_user.php',
         dashboardMode: true,
         user_management_mode: true,
         fieldLabel: 'Map To',
         emptyText: 'User not mapped',
         hiddenName: 'nm_existing_user_mapping',
         width: 165,
         listeners: { change: comboChangeHandler }
         
      });//cmbUserMapping
      
      // ------------------------------------------

		var roleGridClickHandler = function() {
		
         var sel_roles = roleGrid.getSelectedRoles();         
         cmbInstitution.setDisabled(sel_roles.itemExists('cc') == -1);
         
         saveIndicator.show();
         
		};
		
      var roleGrid = new XDMoD.Admin.RoleGrid({
         cls: 'admin_panel_section_role_assignment',
         selectionChangeHandler: roleGridClickHandler,
         border: false
      });

      // ------------------------------------------
      
      self.inDirtyState = function() {
      
         return (roleGrid.isInDirtyState() || settingsAreDirty);
         
      }//inDirtyState

      self.resetDirtyState = function() {
      
         settingsAreDirty = false;
         roleGrid.setDirtyState(false);
         
      }//resetDirtyState
      
      // ------------------------------------------

      var roleSettings = new Ext.Panel({

         title: 'Role Assignment',
         columns: 1,
         layout: 'fit',

         flex: .55, 
         items: [
            roleGrid
         ]

      });//roleSettings

      // ------------------------------------------


      var lblXSEDEUser = new Ext.form.Label({
         fieldLabel: 'User Type',
         html: '<b style="color: #00f">XSEDE User</b>'
      });
      
      lblXSEDEUser.hide();
      
      var userSettings = new Ext.FormPanel({

         flex: .45,

         labelWidth: 95,
         frame:true,
         title: 'Settings',
         bodyStyle:'padding:5px 5px 0',
         defaults: {width: 170},
         cls: 'admin_panel_existing_user_settings',
         labelAlign: 'top',
         defaultType: 'textfield',
         region: 'west',

         items: [

            existingUserEmailField,
            cmbUserType,
            lblXSEDEUser,
            cmbUserMapping,
            cmbInstitution
                        
         ]

      });//userSettings

      // ------------------------------------------

      var btnSaveChanges = new Ext.Button({

         text: 'Save Changes',
         cls: 'admin_panel_btn_save',
         iconCls: 'admin_panel_btn_save_icon', 

         handler: function() {
            
            existingUserEmailField.removeClass('admin_panel_invalid_text_entry');
            cmbUserMapping.removeClass('admin_panel_invalid_text_entry');
            cmbInstitution.removeClass('admin_panel_invalid_text_entry');
            
            // ===========================================
                           
            if (existingUserEmailField.getValue().length == 0 && cached_user_type != CCR.xdmod.XSEDE_USER_TYPE) {
            
               // All non-XSEDE accounts MUST have an e-mail address set

               existingUserEmailField.addClass('admin_panel_invalid_text_entry'); 
               CCR.xdmod.ui.userManagementMessage('This XDMoD user must have a valid e-mail address', false);
               return;               
               
            }
            
            // ===========================================
            
            var email_field_status = existingUserEmailField.validate();

            // If a value in the e-mail address field is specified, it must be in the correct format
            if (!email_field_status && existingUserEmailField.getValue().length > 0) {
            
               existingUserEmailField.addClass('admin_panel_invalid_text_entry'); 
               CCR.xdmod.ui.userManagementMessage('When specified, this user must have a valid e-mail address', false);
               return;
               
            }

            var invalid_user_selected = (cmbUserMapping.getValue() == cmbUserMapping.getRawValue()) && 
                                        (cmbUserMapping.getValue() != mapping_cached_person_name);

            if (invalid_user_selected) {

               cmbUserMapping.addClass('admin_panel_invalid_text_entry');
               CCR.xdmod.ui.userManagementMessage('Cannot find <b>' + cmbUserMapping.getValue() + '</b> in the directory.<br>Please select a name from the drop-down list.', false);
               return;

            }//if (invalid_user_selected)

            var established_person_id = (cmbUserMapping.getValue() == mapping_cached_person_name) ? mapping_cached_person_id : cmbUserMapping.getValue(); 


            // ===========================================         

            var sel_roles = roleGrid.getSelections();         

            if (roleGrid.areRolesSpecified() == false) {

               CCR.xdmod.ui.userManagementMessage('This user must have at least one role.', false);            
               return;
               
            }

            if (roleGrid.isPrimaryRoleSpecified() == false) {

               CCR.xdmod.ui.userManagementMessage('This user must have a primary role assigned.', false);            
               return;
               
            }            

            // ===========================================

            var sel_roles = roleGrid.getSelections();
            
            if ( (sel_roles.mainRoles.itemExists('cc') == 1) && (cmbInstitution.getValue().length == 0) ){
            
               cmbInstitution.addClass('admin_panel_invalid_text_entry');
               CCR.xdmod.ui.userManagementMessage('An institution must be specified for a user having a role of Campus Champion.', false);
               return;
            
            }

            var objParams = {

               operation: 'update_user',
               uid: selected_user_id,
               email_address: existingUserEmailField.getValue(),
               roles: Ext.util.JSON.encode(sel_roles),
               assigned_user: established_person_id,
               
               institution: (cmbInstitution.getValue().length == 0) ? '-1' : cmbInstitution.getValue(),
               
               user_type: cmbUserType.getValue()
               
            };

            ServerRequest({

               url: '../controllers/user_admin.php', 
               params: objParams,	
               method: 'POST',
               callback: function(options, success, response) { 

                  if (success) {

                     var json = Ext.util.JSON.decode(response.responseText);

                     self.resetDirtyState();
                     saveIndicator.hide();
                     
                     CCR.xdmod.ui.userManagementMessage(json.status, json.success);
                     
                     // Reload user list only if the previously updated user was relocated into another "user type" group
                     if ((json.success == true) && json.user_type != self.cachedUserTypeID)
                        self.reloadUserList(self.cachedUserTypeID);
                     
                     if ((json.success == true) && (user_update_callback)) {
                        user_update_callback();
                     }

                  }
                  else {
                     CCR.xdmod.ui.userManagementMessage('There was a problem connecting to the portal service provider.', false);
                  }

               }//callback

            });//ServerRequest

         }//handler

      });//btnSaveChanges

      // ------------------------------------------

      var mnuItemPasswordReset = new Ext.menu.Item({
         text: 'Send Password Reset',
         //hidden: true, 
         handler: actionPasswordReset
      });
      
      var mnuActions = new Ext.menu.Menu({
      
         plain: true,
         showSeparator: false,
         cls: 'no-icon-menu',
                     
         items: [
            { text: '<span id="lblAccountState">Disable This Account</span>',  handler: actionToggleAccountStatus },
            mnuItemPasswordReset,
            { text: 'Delete This Account',                                     handler: actionDeleteAccount },
            '-',
            { text: 'Empty Report Image Cache',                                handler: actionEmptyReportImageCache }
         ]
         
      });
      
      mnuActions.render();

      // ------------------------------------------
      
      var accessSettings = new Ext.FormPanel({

         flex: .25,

         labelWidth: 95,
         frame:true,
         title: 'Access Details',
         bodyStyle:'padding:5px 5px 0',
         defaults: {width: 170},
         cls: 'admin_panel_existing_user_settings',
         labelAlign: 'top',
         defaultType: 'textfield',
         
         layout: 'column',

         items: [

            { xtype: 'tbtext', text:'<p>Time Created:</p><p>Last Logged In:</p><p>Last Updated:</p>', columnWidth: 0.35 },
            { xtype: 'tbtext', id: 'txtAccountTimestamps', text: '', width: 168, cls: 'admin_panel_timestamp', style: 'font-size: 11px' }
                        
         ]

      });//accessSettings
            
      // ------------------------------------------
            
      var innerPanel = new Ext.Panel({
      
         layout: {
            type: 'hbox',
            padding: '0 0 0 0',
            align: 'stretch'
         },
         
         flex: .75, 
         border: false,
         
         items: [
            roleSettings,
            userSettings
         ],
         
         baseCls: 'x-plain'
         
      });

      // ------------------------------------------

      var outerPanel = new Ext.Panel({
      
         layout: {
            type: 'vbox',
            padding: '0 0 0 0',
            align: 'stretch'
         },
         
         border: false,
         
         items: [
            innerPanel,
            accessSettings
         ],
         
         baseCls: 'x-plain'
         
      });

      // ------------------------------------------
                  
      var userEditor = new Ext.Panel({

         id: 'admin_panel_user_editor',
         title: 'User Information',
         region: 'center',
         //flex: .55,
         margins: '2 2 2 0',
         
         border: true,
         layout: 'fit',
         //width: 450,

         
         tbar: {
         
            items: [
            
               { xtype: 'tbtext', text:'Status: ' },
               { xtype:'tbtext', id: 'txtAccountStatus', text: '' },
            
               '->', 
               
               new Ext.Button({
               
                  text: 'Actions',
                  menu: mnuActions
               
               })
                  
            ]
            
         },

         plugins: [new Ext.ux.plugins.ContainerMask ({ msg:'Select A User From The List To The Left', masked:true, maskClass: 'admin_panel_editor_mask' })],

         items: [
               outerPanel
         ]

      });//userEditor

      // ------------------------------------------

      var fetchUserDetails = function(user_id, reset_controls) {
      
         ServerRequest({
         
            url: '../controllers/user_admin.php',
            params:  { operation: 'get_user_details', uid: user_id },
            method: 'POST',
            callback: function(options, success, response) {
         
               if (success) {
         
                  roleGrid.setDirtyState(false);
                  settingsAreDirty = false;
                  
                  saveIndicator.hide();
         
                  existingUserEmailField.removeClass('admin_panel_invalid_text_entry');
                  cmbUserMapping.removeClass('admin_panel_invalid_text_entry');
                  cmbInstitution.removeClass('admin_panel_invalid_text_entry');
            
                  var json = Ext.util.JSON.decode(response.responseText);
         
                  if (json.status == 'success'){
         
                     // Account status details ---------------
                                          
                     Ext.getCmp('txtAccountTimestamps').update('<p>' + json.user_information.time_created + '</p>' 
                                                             + '<p>' + json.user_information.time_last_logged_in + '</p>' 
                                                             + '<p>' + json.user_information.time_updated + '</p>' 
                                                              );
                     
                     
                     /*
                     Ext.getCmp('txtAccountCreated').update(json.user_information.time_created);
                     Ext.getCmp('txtAccountUpdated').update(json.user_information.time_updated);
                     */
                     
                     Ext.getCmp('txtAccountStatus').update(json.user_information.is_active);
         
                     var mnuItemAccountStatus = document.getElementById('lblAccountState');
                     
                     if (json.user_information.is_active == 'active') {
                        mnuItemAccountStatus.innerHTML = 'Disable This Account';
                        Ext.getCmp('txtAccountStatus').removeClass('admin_panel_user_user_status_disabled');
                        Ext.getCmp('txtAccountStatus').addClass('admin_panel_user_user_status_active');
                     }
                     else {
                        mnuItemAccountStatus.innerHTML = 'Enable This Account';
                        Ext.getCmp('txtAccountStatus').removeClass('admin_panel_user_user_status_active');
                        Ext.getCmp('txtAccountStatus').addClass('admin_panel_user_user_status_disabled');
                     }
                     
                     if (reset_controls == true) {
                     
                        userEditor.setTitle('User Details: ' + json.user_information.formal_name);
            
                        existingUserEmailField.setValue(json.user_information.email_address);
      
                        // Remaining inputs ----------------------
                        
                        mapping_cached_person_name = json.user_information.assigned_user_name;
                        mapping_cached_person_id = json.user_information.assigned_user_id;
                        
                        cached_user_type = json.user_information.user_type;
                        
                        cmbUserMapping.initializeWithValue(json.user_information.assigned_user_id, json.user_information.assigned_user_name);
                        
                        if (json.user_information.user_type == CCR.xdmod.XSEDE_USER_TYPE) {
                        
                           // XSEDE-derived User: Can't change user type
                           
                           cmbUserType.hide();
                           lblXSEDEUser.show();
                           
                           mnuItemPasswordReset.hide();
                           
                           //cmbUserMapping.reset();
                           
                        }
                        else {

                           // All other (non-XSEDE-derived) users
                           
                           lblXSEDEUser.hide();
                           cmbUserType.show();
            
                           mnuItemPasswordReset.show();
            
                           cmbUserType.setDisabled(false);
                           cmbUserType.setValue(json.user_information.user_type);
                        
                        }
                        
                        // -----------------------------
            
                        if (json.user_information.institution != '-1') {
                        
                           cmbInstitution.setDisabled(false);
                           cmbInstitution.initializeWithValue(json.user_information.institution, json.user_information.institution_name);

                        }
                        else
                           cmbInstitution.setDisabled(true);
                           
                        // -----------------------------
            
                        tg_user_list_phase = 'load_user';
            
                        roleGrid.setRoles(json.user_information.roles);
                        
                        roleGrid.setCenterConfig(XDMoD.Admin.Roles.CENTER_DIRECTOR, json.user_information.center_director_sites);
                        roleGrid.setCenterConfig(XDMoD.Admin.Roles.CENTER_STAFF, json.user_information.center_staff_sites);
                        
                        roleGrid.setPrimaryRole(json.user_information.primary_role);
            
                        userEditor.hideMask();
                        btnSaveChanges.setDisabled(false);
                     
                     }//if (reset_controls == true)
         
                  }
                  else {
                     CCR.xdmod.ui.userManagementMessage(json.status, false);
                  }	
         
               }
               else {
                  CCR.xdmod.ui.userManagementMessage('There was a problem connecting to the portal service provider.', false);
               }
         
            }//callback
         
         });//ServerRequest
      
      }//fetchUserDetails
      
      // ------------------------------------------     
      
      var grid = new Ext.grid.GridPanel({

         store: storeUserListing,
         cm: cm,
         title: 'Existing Users',
         region: 'west',
         layout: 'fit',
         width: 510,
         enableHdMenu: false,
         clicksToEdit: 1,
         border: true,
         margins: '2 0 2 2',
         

         viewConfig: { 
            emptyText: 'No users in this category currently exist'
         },
         
         tbar: {
            items: [
               {xtype: 'tbtext', cls: 'admin_panel_tbtext', text:'Displaying', flex: 1 },
               usersTypeSplitButton, 
               '->',
               {xtype: 'tbtext', cls: 'admin_panel_tbtext', text:'<a href="javascript:void(0)" onClick="displayExceptionEmails()">List Exception E-Mails</a>', flex: 1 }
            ]
         },
                     
         listeners: {

            'render' : function() {
               Ext.getBody().on("contextmenu", Ext.emptyFn, null, {preventDefault: true});
            },

            'cellclick' : function(grid, rowindex, colindex, e) {
               
               if ( (selected_user_id != -1) && (self.inDirtyState() == true) ){
               
                  Ext.Msg.show({
                     
                        maxWidth: 800,
                        minWidth: 400,
                        
                        title: 'Unsaved Changes',
                        
                        msg: "There are unsaved changes to this account.<br />" +
                             "Do you wish to save these changes before continuing?<br /><br />" +
                             "If you choose <b>No</b>, you will lose all your changes.",
                             
                        buttons: {yes: "Yes (go back and save)", no: "No (discard changes)"},
                        
                        fn: function(resp) {
                                                
                           if (resp == 'yes') {
               
                              var targetIndex = grid.store.find('id', selected_user_id);
                              grid.getSelectionModel().selectRow(targetIndex);
               
                              return;
                              
                           }
                              
                           if (resp == 'no') {
               
                              self.resetDirtyState();
                              saveIndicator.hide();
                  
                              selected_user_id = grid.store.getAt(rowindex).data.id;
                              selected_username = grid.store.getAt(rowindex).data.username;
               
                              fetchUserDetails(grid.store.getAt(rowindex).data.id, true);
               
                           }
               
                        },
                        
                        icon: Ext.MessageBox.QUESTION
                        
                  });//Ext.Msg.show      
                  
                  return;
                 
               }
               
               selected_user_id = this.store.getAt(rowindex).data.id;
               selected_username = this.store.getAt(rowindex).data.username;
               
               fetchUserDetails(this.store.getAt(rowindex).data.id, true);
         
            }//'cellclick'

         }//listeners

      });//grid
      
      // Disable key navigation of the user grid
      grid.getSelectionModel().onKeyPress = Ext.emptyFn;
			
      // ------------------------------------------

      var saveIndicator = new Ext.Toolbar.TextItem({
         html: '<span style="color: #f00">(There are unsaved changes to this account)</span>',
         hidden: true
      });
      
      Ext.apply(this, {

         title: 'Current Users',
         border: false,
         
         bbar: 

            {
               items: [
               
                  btnSaveChanges,
                  saveIndicator,
                  
                  '->',
                  
                  new Ext.Button({
                     text: 'Close',
                     iconCls: 'general_btn_close', 
                     handler: function() {
                        self.parentWindow.hide();
                     }
                  })
                  
               ]
            },

            layout: 'border',
 
                items: [
                  grid,
                  userEditor
               ]
               /*           
            items: {

               layout: 'border',
               border: false,
               frame: true,

            }
            */

      });//Ext.apply

      XDMoD.ExistingUsers.superclass.initComponent.call(this);

   }//initComponent

});//XDMoD.ExistingUsers
