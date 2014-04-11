XDMoD.ProfileGeneralSettings = Ext.extend(Ext.Panel,  {

   height:340,
   layoutConfig:{ columns:1 },
   border:false,
   frame: true,
   resizable:false,
   title:'General',
   //padding: '10 12 0 10',
   
   // Dictates whether closing the profile editor logs out the user automatically
   perform_logout_on_close: false,
      
   init: function() {

      XDMoD.REST.Call({
         action: 'portal/profile/fetch',
         callback: this.cbProfile,
         resume: true
      });
            
   },
   
   initComponent: function(){

      var self = this;

      // ------------------------------------------------
      
      var user_profile_firstname = new XDMoD.LimitedField({ 	
         fieldLabel: 'First Name',   
         characterLimit: 50,
         emptyText: '1 min, 50 max',
         formatMessage: 'The first name must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,50}$'
      });
            
      var user_profile_lastname = new XDMoD.LimitedField({ 	
         fieldLabel: 'Last Name',   
         characterLimit: 50,
         emptyText: '1 min, 50 max',
         formatMessage: 'The last name must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,50}$'
      });
      
      var user_profile_email_addr = new XDMoD.LimitedField({ 	
         fieldLabel: 'E-Mail Address',   
         characterLimit: 200,
         emptyText: '6 min, 200 max',
         formatMessage: 'You must specify a valid email address<br>(e.g. user@domain.com)',
         vpattern: regex_email
      });

      var user_profile_new_pass = new XDMoD.LimitedField({ 	
         fieldLabel: 'Password',   
         characterLimit: 20,
         width: 120,
         vpattern: '^(.){5,20}$',	
         formatMessage: 'The password must be at least 5 characters long',	
         inputType: 'password',		
         disabled: true,
         cls: 'user_profile_password_field'
      });

      var user_profile_new_pass_again = new XDMoD.LimitedField({ 
         fieldLabel: 'Password Again',   
         characterLimit: 20,
         width: 120,
         vpattern: '^(.){5,20}$',	
         formatMessage: 'The password must be at least 5 characters long',	
         inputType: 'password',		
         disabled: true,
         cls: 'user_profile_password_field'
      });            
                        
      // ------------------------------------------------
            
      var active_layout_index = XDMoD.ProfileEditorConstants.PASSWORD;
            
      // ------------------------------------------------
      
      var switchToSection = function(id) {

         rpanel = sectionBottom.getLayout();
         
         if (rpanel != 'card'){
               
            rpanel.setActiveItem(id);
                     
         }     
      
      }//switchToSection

      // ------------------------------------------------

      this.cbProfile = function(data) {
         
         if (data.success) {

            user_profile_firstname.setValue(data.results.first_name);
            user_profile_lastname.setValue(data.results.last_name);
            user_profile_email_addr.setValue(data.results.email_address);
            
            if (data.results.email_address.length == 0)
               user_profile_email_addr.addClass('user_profile_invalid_text_entry');
            
            // ================================================
            
            active_layout_index = XDMoD.ProfileEditorConstants.PASSWORD;
            
            if (data.results.is_xsede_user == true) {
            
               if (data.results.first_time_login && (data.results.email_address.length != 0)) {
 
                  // If the user is logging in for the first time and does have an e-mail address set
                  // (due to it being specified in the XDcDB), welcome the user and inform them they
                  // have an opportunity to update their e-mail address.  
                  
                  if(data.results.autoload_suppression == true) {
                     
                     //If the user has updated their profile on first login already, there is no need to suggest an e-mail change
                     active_layout_index = XDMoD.ProfileEditorConstants.XSEDE_SPLASH;
                     
                  }
                  else {
                  
                     active_layout_index = XDMoD.ProfileEditorConstants.WELCOME_EMAIL_CHANGE;
                     user_profile_email_addr.addClass('user_profile_highlight_entry');
                  
                  }
                  
               }
               else if (data.results.first_time_login && (data.results.email_address.length == 0)) {

                  // If the user is logging in for the first time and does *not* have an e-mail address set,
                  // welcome the user and inform them that he/she needs to set an e-mail address.
                  
                  active_layout_index = XDMoD.ProfileEditorConstants.WELCOME_EMAIL_NEEDED;
                  XDMoD.Profile.logoutOnClose = true;
                       
               
               }
               else if (data.results.email_address.length == 0) {
               
                  // Regardless of whether the user is logging in for the first time or not, the lack of 
                  // an e-mail address requires attention
                  
                  active_layout_index = XDMoD.ProfileEditorConstants.EMAIL_NEEDED;                   
                  XDMoD.Profile.logoutOnClose = true;

               }
               else {
               
                  // The XSEDE user has logged in at least a second time and has no issues with their e-mail address
                  
                  active_layout_index = XDMoD.ProfileEditorConstants.XSEDE_SPLASH;
               
               }
               
            }//if (data.results.is_xsede_user == true)

            // ================================================
            
            lblRole.on('afterrender', function() {
               document.getElementById('profile_editor_current_role').innerHTML = data.results.active_role;
            });
               
            self.parentWindow.show();

         }
         else {
            Ext.MessageBox.alert('My Profile', 'There was a problem retrieving your profile information.');
         }

      };//cbProfile
      
      // ------------------------------------------------
            
      var cbProfileUpdate = function(data) {
 
         if (data.success == false) {
            CCR.xdmod.ui.generalMessage('My Profile', data.message, false, 4000);
            return;
         }
         
         XDMoD.Profile.logoutOnClose = false;
         
         CCR.xdmod.ui.generalMessage('My Profile', data.message, true);
         
         var f_name = user_profile_firstname.getValue();
         var l_name = user_profile_lastname.getValue();
         
         document.getElementById('welcome_message').innerHTML = f_name + ' ' + l_name;   
         
         self.parentWindow.close(); 
         
      }//cbProfileUpdate

      // ------------------------------------------------
      
      var optPasswordUpdate = new Ext.form.RadioGroup({

         fieldLabel: 'Update',
         cls: 'user_profile_option_password_update',
         columns: 2,
         width: 180,
         
         items: [
            { boxLabel: 'Keep Existing', name: 'group_view', inputValue: 'keep', checked: true }, 
            { boxLabel: 'Update', name: 'group_view', inputValue: 'update', checked: false }
         ],
         
         listeners: {

            'change' : function(rg, ch) { 

               user_profile_new_pass.setDisabled(rg.getValue().getGroupValue() == 'keep');
               user_profile_new_pass_again.setDisabled(rg.getValue().getGroupValue() == 'keep');

               if (rg.getValue().getGroupValue() == 'keep') {
                  user_profile_new_pass.setValue('');
                  user_profile_new_pass_again.setValue('');
                  user_profile_new_pass.removeClass('user_profile_invalid_text_entry');
                  user_profile_new_pass_again.removeClass('user_profile_invalid_text_entry');
               }

            }//change

         }//listeners

      });//optPasswordUpdate
      
      // ------------------------------------------------
      
      var lblRole = new Ext.form.Label ({
         html: '<div style="width: 300px; font-size: 12px; padding-top: 5px">Current Role: <b style="margin-left: 26px"><span id="profile_editor_current_role"></span></b><br /></div>'
      });
 
      // ------------------------------------------------     
      
      var sectionGeneral = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'User Information',
         bodyStyle:'padding:5px 5px 0',
         width: 350,
         defaults: {width: 200},
         cls: 'user_profile_section_general',
         defaultType: 'textfield',

         items: [

            user_profile_firstname,
            user_profile_lastname,
            user_profile_email_addr,

            lblRole
            //cmbFieldOfScience
            
         ]

      });//sectionGeneral
            
      // ------------------------------------------------
          
      var sectionPassword = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'Update Password',
         bodyStyle:'padding:5px 5px 0',
         width: 350,
         height: 150,
         defaults: {width: 200},
         cls: 'user_profile_section_password',
         defaultType: 'textfield',
         
         items: [
         
            optPasswordUpdate,
            user_profile_new_pass,
            
            {xtype: 'tbtext', cls: 'user_profile_entry_password_requirements', text: '5 min, 20 max'},

            user_profile_new_pass_again,
            
            {xtype: 'tbtext', width: 200, cls: 'user_profile_entry_password_requirements', text: '5 min, 20 max'}

         ]

      });//sectionPassword

      // ------------------------------------------------
                   
      var renderXSEDEMessage = function(config) {
      
         if (config == undefined) config = {};
         
         if (config.display_banner == undefined) config.display_banner = false;
                  
         var message = (config.message != undefined) ? '<tr><td align=center style="font-size: 11px">' + config.message + '</td></tr>' : '';

         var bannerRow = '';
         
         if (config.display_banner == true) {
         
            var spacing = (config.message == undefined) ? '<br/>' : '';
            
            bannerRow = '<tr><td align=center valign=top>' + spacing + '<img src="gui/images/xsede_profile_banner.png"></td></tr>';
         
         }
         
         return '<table border=0 height=100% width=100%>' + 
                 bannerRow +
                 message + 
                '<tr><td align=center valign=bottom style="font-size: 11px">' +
                'If you require elevated access<br />(e.g. Center Director, Program Officer),<br />' +
                'please contact <a href="mailto:' + CCR.xdmod.tech_support_recipient + '">' + CCR.xdmod.tech_support_recipient + '</a><br />' +
                'to request such privileges.</td></tr>' + 
                '</table>';
         
      }//renderXSEDEMessage
      
      // ------------------------------------------------
            
      var sectionXSEDEWelcomeEmailChange = new Ext.FormPanel({

         labelWidth: 95,
         frame:false,
         bodyStyle:'padding:0px 5px',
         width: 350,
         height: 150,
         //defaults: {width: 200},
         
         items: [
            
            {
               xtype: 'tbtext', 
               text: renderXSEDEMessage({
                  message:'<b>Welcome, XSEDE User</b><br />The e-mail address above is currently associated with your XSEDE account. ' +
                          'Please update this e-mail address as necessary if you wish to have XDMoD-specific content delivered to an alternative address.'
               })
            }

         ]

      });//sectionXSEDEWelcomeEmailChange
      
      // ------------------------------------------------
                   
      var sectionXSEDEWelcomeEmailNeeded = new Ext.FormPanel({

         labelWidth: 95,
         frame:false,
         //title: 'Welcome XSEDE User',
         bodyStyle:'padding:0px 5px',
         width: 350,
         height: 150,
         //defaults: {width: 200},
         
         items: [
            
            {
               xtype: 'tbtext',
               text: renderXSEDEMessage({
                  message:'<b>Welcome, XSEDE User</b><br />An e-mail address is required in order to use certain features of XDMoD as well as ' +
                          'receive important messages from the XDMoD team.'
               })
            }

         ]

      });//sectionXSEDEWelcomeEmailNeeded

      // ------------------------------------------------
            
      var sectionXSEDEEmailNeeded = new Ext.FormPanel({

         labelWidth: 95,
         frame:false,
         //title: 'Welcome XSEDE User',
         bodyStyle:'padding:0px 5px',
         width: 350,
         height: 150,
         //defaults: {width: 200},
         
         items: [
            
            {
               xtype: 'tbtext', 
               text: renderXSEDEMessage({
                  display_banner: true, 
                  message:'An e-mail address is required in order to use certain features of XDMoD as well as receive important messages from the XDMoD team.'
               })
            }
         ]

      });//sectionXSEDEEmailNeeded

      // ------------------------------------------------
      
      var sectionXSEDESplash = new Ext.FormPanel({

         labelWidth: 95,
         frame:false,
         //title: 'Welcome XSEDE User',
         bodyStyle:'padding:0px 5px',
         width: 350,
         height: 150,
         //defaults: {width: 200},
         
         items: [
            
            {
               xtype: 'tbtext', 
               text: renderXSEDEMessage({
                  display_banner: true
               })
            }

         ]

      });//sectionXSEDESplash
      
      // ------------------------------------------------
      
      var sectionBottom = new Ext.Panel({

         labelWidth: 95,
         bodyStyle:'padding:0px 0px',
         layout: 'fit',
         cls: 'user_profile_section_password',
         
         layout: 'card',
         activeItem: 0,
         
         items: [
            sectionPassword,
            sectionXSEDEWelcomeEmailChange,
            sectionXSEDEWelcomeEmailNeeded,
            sectionXSEDEEmailNeeded,
            sectionXSEDESplash            
         ]

      });//sectionBottom
      
      sectionBottom.on('afterrender', function() {
         
         switchToSection(active_layout_index);
         
      });
      
      // ------------------------------------------------


      var btnUpdate = new Ext.Button({
      
         iconCls: 'user_profile_btn_update_icon', 
         cls: 'user_profile_btn_update',
         text: 'Update',
         handler: function() {
               
            user_profile_email_addr.removeClass('user_profile_highlight_entry');
            
            var fieldsToValidate = [user_profile_firstname, user_profile_lastname, user_profile_email_addr];

            if (optPasswordUpdate.getValue().getGroupValue() == 'update') {
               fieldsToValidate.push(user_profile_new_pass, user_profile_new_pass_again);
            }

            // Sanitization --------------------------------------------

            var incomplete_fields = false;

            for (i = 0; i < fieldsToValidate.length; i++) {

               if (fieldsToValidate[i].validate())
                  fieldsToValidate[i].removeClass('user_profile_invalid_text_entry');
               else {

                  fieldsToValidate[i].addClass('user_profile_invalid_text_entry');
                  incomplete_fields = true;

                  CCR.xdmod.ui.generalMessage('My Profile', fieldsToValidate[i].formatMessage, false);
                  
                  return;

               }

            }//for

            if (incomplete_fields) {
               Ext.MessageBox.alert('My Profile', 'Please supply valid information to the fields highlighted in pink.');
               return;
            }

            if (optPasswordUpdate.getValue().getGroupValue() == 'update') {

               if (user_profile_new_pass.getValue() != user_profile_new_pass_again.getValue()) {

                  CCR.xdmod.ui.generalMessage('My Profile', 'The passwords you specified do not match each other.', false);

                  return;

               }

            }

            // ------------------------------------------------------------

            var updateParams = {};

            updateParams['operation'] = 'update_profile';
            updateParams['first_name'] = encodeURIComponent(user_profile_firstname.getValue());
            updateParams['last_name'] = encodeURIComponent(user_profile_lastname.getValue());
            updateParams['email_address'] = user_profile_email_addr.getValue();

            if (optPasswordUpdate.getValue().getGroupValue() == 'update')
               updateParams['password'] = encodeURIComponent(user_profile_new_pass.getValue());

            XDMoD.REST.Call({
               action: 'portal/profile/update',
               arguments: updateParams,
               callback: cbProfileUpdate
            });


         }//handler

      });//btnUpdate

      // ------------------------------------------------

      Ext.apply(this, {

         items:[ 
            sectionGeneral, 
           // self.sectionPassword
            sectionBottom
         ],
         
         bbar: {
         
            items: [
               btnUpdate,
               '->',
               self.parentWindow.getCloseButton()
            ]
            
         }

      });

      XDMoD.ProfileGeneralSettings.superclass.initComponent.call(this);
      
   }//initComponent

});//XDMoD.ProfileGeneralSettings