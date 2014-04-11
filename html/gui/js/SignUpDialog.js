XDMoD.SignUpDialog = Ext.extend(Ext.Window,  {

   width: 591,
   height: (CCR.xdmod.use_captcha == true) ? 460 : 330,
   modal: true,
   resizable: false,
   
   title: "Sign Up Today",
   iconCls: 'signup_16',  
   bodyStyle:'padding:15px 13px 0',
   
   initComponent: function(){

      var txtFirstName = new XDMoD.LimitedField({

         fieldLabel: 'First Name',   
         characterLimit: 50,
         emptyText: '1 min, 50 max',
         formatMessage: 'The first name must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,50}$'
         
      });//txtFirstName   

      var txtLastName = new XDMoD.LimitedField({

         fieldLabel: 'Last Name',   
         characterLimit: 50,
         emptyText: '1 min, 50 max',
         formatMessage: 'The last name must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,50}$'
         
      });//txtLastName  

      var txtPosition = new XDMoD.LimitedField({

         fieldLabel: 'Position',   
         characterLimit: 200,
         emptyText: '1 min, 200 max',
         formatMessage: 'The position must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,200}$'
         
      });//txtPosition  
      
      var txtOrganization = new XDMoD.LimitedField({

         fieldLabel: 'Organization',   
         characterLimit: 200,
         emptyText: '1 min, 200 max',
         formatMessage: 'The organization must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,200}$'
         
      });//txtOrganization  
      
      var txtEmail = new XDMoD.LimitedField({

         fieldLabel: 'E-Mail',   
         characterLimit: 200,
         emptyText: '6 min, 200 max',
         formatMessage: 'You must specify a valid email address<br>(e.g. user@domain.com)',
         vpattern: regex_email
         
      });//txtEmail

      var txtAdditionalInformation = new Ext.form.TextArea({
      
         anchor: '100%',
         emptyText: 'How do you fit into the XSEDE management structure?'
         
      });//txtAdditionalInformation
            
      var captchaField = new Ext.Panel();
      
      if (CCR.xdmod.use_captcha == true) {

         captchaField = new XDMoD.CaptchaField({
         
            style: 'margin-left: 110px'
            
         });//captchaField
      
      }//if (CCR.xdmod.use_captcha == true)

      var btnSubmit = new Ext.Button({
      
         text: 'Submit My Information',
         iconCls: 'contact_btn_send',
         
         handler: function () {
         
            XDMoD.TrackEvent('Sign Up Window', 'Clicked on Submit My Information button');
            processSignUp();
               
         }
      
      });//btnSubmit
      
      var processSignUp = function() {
      
         var fieldsToValidate = [txtFirstName.id, txtLastName.id, txtEmail.id, txtOrganization.id, txtPosition.id];
         
         // Sanitization --------------------------------------------
         
         for (i = 0; i < fieldsToValidate.length; i++)
           Ext.getCmp(fieldsToValidate[i]).removeClass('invalid_text_entry');
         
         for (i = 0; i < fieldsToValidate.length; i++) {
            
            if (!Ext.getCmp(fieldsToValidate[i]).validate()) {
            
            	Ext.getCmp(fieldsToValidate[i]).addClass('invalid_text_entry');
            	
            	CCR.xdmod.ui.userManagementMessage(Ext.getCmp(fieldsToValidate[i]).formatMessage, false);
            					
            	return;
            	
            }

         }//for

         var params = {
          
            'operation' : 'sign_up',
            'first_name': txtFirstName.getValue(),
            'last_name': txtLastName.getValue(),    
            'organization': txtOrganization.getValue(),
            'title': txtPosition.getValue(),
            'email': txtEmail.getValue(),
            'field_of_science': 'not available',
            'additional_information': txtAdditionalInformation.getValue().trim()
               
         };//params
            
         if (CCR.xdmod.use_captcha == true) {     
                                     
            if (captchaField.getResponseField().trim().length == 0) {
               CCR.xdmod.ui.userManagementMessage("Specify a value in the captcha field", false);
               return;
            }
            
            params['recaptcha_challenge_field'] = captchaField.getChallengeField();
            params['recaptcha_response_field'] = captchaField.getResponseField().trim();
               
         }//if (CCR.xdmod.use_captcha == true)
   
         var conn = new Ext.data.Connection();
         
         conn.request({
         
            url: 'controllers/mailer.php',
            method: 'POST',
            params: params,
         
            callback: function(options, success, response) { 
               
               if (success) {
            	         
                  var json = Ext.decode(response.responseText);
                  
                  if (json.success == true) {
                     
                     self.getLayout().setActiveItem(1);
                     btnSubmit.setDisabled(true);
                     
                  }
                  else {
                  
                     CCR.xdmod.ui.userManagementMessage(json.message, false);
                  
                  }
         
               }
               else {
               
                  CCR.xdmod.ui.userManagementMessage("There was a problem connecting to the XDMoD service", false);
                  
               }
                     
            }//callback
            
         });//Ext.data.Connection.request
            
      }//processSignUp
                         
      var signUpSection = new Ext.Panel({

         width: 310,
         
         baseCls: 'x-plain',
         
         items: [

            new Ext.FormPanel({

               labelWidth: 78,
               frame:true,
               
               title: 'Provide the following information',
               bodyStyle:'padding:5px 5px 0',
               width: 550,

               items: [
               
                  {
                  
                     layout: 'column',
                  
                     items:[
                     
                        //column 1
                        {
                        
                           columnWidth: .5,
                           layout: 'form',
   
                           items: [
                              txtFirstName,
                              txtLastName,
                              txtEmail
                           ]
                           
                        },
                        
                        //column 2
                        {
                        
                           columnWidth: .5,
                           layout: 'form',
   
                           items: [
                              txtOrganization,
                              txtPosition
                           ]
                           
                        }
                        
                     ]//items
                     
                  }//column_layout
                             
               ]//items

            }),

            new Ext.FormPanel({

               hideLabels: true,
               frame:true,
               
               title: 'Any additional information',
               style:'margin-top:15px',
               width: 550,

               items: [
               
                  txtAdditionalInformation
                        
               ]//items

            }),
            
            captchaField

         ]         
         
      });//signUpSection
      
      var successSection = new Ext.Panel({
         
         baseCls: 'x-plain',
         
         html: '<center><br /><br /><img src="gui/images/signup_success.png"><br /><br />' + 
               'Thank you for signing up.<br /><br />A team member will be in touch with you shortly.</center>'
         
      });//successSection
      
      var self = this;
      
      self.on('close', function() {
      
         XDMoD.TrackEvent('Sign Up Window', 'Closed Window');
         
      });//self.on('close', ...
      
      // --------------------------------

      Ext.apply(this, {

         layout: 'card',
         activeItem: 0,
         
         bbar: {
         
            items: [
   
               btnSubmit,
               
               '->',
   
               new Ext.Button({
               
                   text: 'Close',
                   iconCls: 'general_btn_close',
                   
                   handler: function () {
   
                      self.close();
                      
                   }
                   
               })
      
            ]
            
         },
         
         items: [

            signUpSection,
            successSection

         ]

      });

      XDMoD.SignUpDialog.superclass.initComponent.call(this);
        	
   }//initComponent
        
});//XDMoD.SignUpDialog