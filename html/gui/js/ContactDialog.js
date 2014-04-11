XDMoD.ContactDialog = Ext.extend(Ext.Window,  {

   width: 470,
   height: (CCR.xdmod.use_captcha == true && CCR.xdmod.logged_in == false) ? 435 : 307,
   modal: true,
   resizable: false,
   
   title: "Contact Us",
   iconCls: 'contact_16',  
   bodyStyle:'padding:15px 13px 0',
   
   initComponent: function(){

      var txtName = new XDMoD.LimitedField({

         fieldLabel: 'Name',   
         characterLimit: 100,
         width: 200,
         emptyText: '1 min, 100 max',
         formatMessage: 'The name must be at least 1 character long, and not contain special symbols<br />($, ^, #, <, >, ", :, \\, /, !)',
         vpattern: '^[^$#<>":/\\\\!]{1,100}$'
         
      });//txtName   

      var txtEmail = new XDMoD.LimitedField({

         fieldLabel: 'E-Mail',   
         characterLimit: 200,
         width: 200,
         emptyText: '6 min, 200 max',
         formatMessage: 'You must specify a valid email address<br>(e.g. user@domain.com)',
         vpattern: regex_email
         
      });//txtEmail

      var txtMessage = new Ext.form.TextArea({
      
         anchor: '100%'
         
      });//txtMessage

      var captchaField = new Ext.Panel();
      
      if (CCR.xdmod.use_captcha == true && CCR.xdmod.logged_in == false) {

         captchaField = new XDMoD.CaptchaField({
         
            style: 'margin-left: 47px'
            
         });//captchaField
      
      }//if (CCR.xdmod.use_captcha == true && CCR.xdmod.logged_in == false)

      var btnSubmit = new Ext.Button({
      
         text: 'Send Message',
         iconCls: 'contact_btn_send',
         
         handler: function () {
         
            XDMoD.TrackEvent('Contact Window', 'Clicked Send Message button');
            processContactForm();
               
         }
      
      });//btnSubmit
      
      var processContactForm = function() {
      
         var timestamp_secs = XDMoD.Tracking.timestamp / 1000;
         
         var fieldsToValidate = [txtName.id, txtEmail.id];
         
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

         if (txtMessage.getValue().trim().length == 0) {
            CCR.xdmod.ui.userManagementMessage("Please specify a message", false);
            return;
         }

         var params = {
          
            'operation': 'contact',
            'name': txtName.getValue(),
            'email': txtEmail.getValue(),    
            'message': txtMessage.getValue().trim(),
            'username': CCR.xdmod.ui.username,
            'token': XDMoD.REST.token,
            'timestamp': timestamp_secs
               
         };//params          

         if (CCR.xdmod.use_captcha == true && CCR.xdmod.logged_in == false) {
                        
            if (captchaField.getResponseField().trim().length == 0) {
               CCR.xdmod.ui.userManagementMessage("Specify a value in the captcha field", false);
               return;
            }

            params['recaptcha_challenge_field'] = captchaField.getChallengeField();
            params['recaptcha_response_field'] = captchaField.getResponseField().trim();
                     
         }
   
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
            
      }//processContactForm
                         
      var signUpSection = new Ext.Panel({

         width: 300,
         
         baseCls: 'x-plain',
         
         items: [

            new Ext.FormPanel({

               labelWidth: 45,
               frame:true,
               
               title: 'Please provide the following information',
               bodyStyle:'padding:5px 5px 0',
               width: 430,

               items: [
               
                  txtName,
                  txtEmail
                             
               ]//items

            }),

            new Ext.FormPanel({

               hideLabels: true,
               frame:true,
               
               title: 'Message',
               style:'margin-top:15px',
               width: 430,

               items: [
               
                  txtMessage
                        
               ]//items

            }),
            
            captchaField

         ]         
         
      });//signUpSection
      
      var successSection = new Ext.Panel({
         
         baseCls: 'x-plain',
         
         html: '<center><br /><br /><img src="gui/images/signup_success.png"><br /><br />' + 
               'Thank you for your message.<br /><br />A team member will be in touch with you shortly.</center>'
         
      });//successSection
      
      var self = this;
      
      self.on('afterrender', function() {

          if (CCR.xdmod.logged_in == true) {

              XDMoD.REST.Call({
              
                action: 'portal/profile/fetch',
                    
                callback: function(data) {
       
                    if (data.success) {

                        txtName.setValue(data.results.first_name + ' ' + data.results.last_name);
                        txtEmail.setValue(data.results.email_address);

                    }

                },

                resume: true
                
            });//XDMoD.REST.Call

        }//if (CCR.xdmod.logged_in == true)

      });//self.on('afterrender', ...
      
      self.on('close', function() {
      
         XDMoD.TrackEvent('Contact Window', 'Closed Window');
         
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

      XDMoD.ContactDialog.superclass.initComponent.call(this);
        	
   }//initComponent
        
});//XDMoD.ContactDialog