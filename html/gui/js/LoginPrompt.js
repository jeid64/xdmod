// LoginPrompt.js

// Login prompt conditionally presented to a user whose 
// session has expired.

var SUCCESS = '#080';
var FAIL = '#f00';

XDMoD.LoginPrompt = Ext.extend(Ext.Window,  {

   rest_call_config: null,   // Needs to be overridden by making a call to setRESTConfig(...)
   
	width:333,
	height:180,
	layout:'table',
	layoutConfig:{ columns:1 },
	frame: true,
	modal:true,
	closable:false,
	closeAction:'hide',
	resizable:false,
	title:'Session Expired',
   padding: '10 0 0 10',
	
	setRESTConfig: function(config) {
	  this.rest_call_config = config;
	  if (config.title) this.sectionGeneral.setTitle(config.title);
	},
	
	initComponent: function(){

      var self = this;
      
      // -----------------------------------------------------------------
            
      var presentOverlay = function(status, message, customdelay, cb) {
         
         var delay = customdelay ? customdelay : 2000;
         
         relogin_panel_container.showMask('<div class="overlay_message" style="color:' + status + '">' + message + '</div>');
                        
         (function() { 
            
            relogin_panel_container.hideMask(); 
            if (cb) cb();
            
         }).defer(delay);
                        
      }//presentOverlay

      // -----------------------------------------------------------------
      
      var usernameField = new XDMoD.LimitedField({

         fieldLabel: 'Username',   
         characterLimit: 50,
         disabled: true,
         value: CCR.xdmod.ui.username,
         width: 180,
         formatMessage: 'The username must consist of alphanumeric characters only (minimum: 5)<br>Or can be an e-mail address',
         vpattern: '^[a-zA-Z0-9@.]{5,200}$'   
            
      });


      var passwordField = new XDMoD.LimitedField({

         fieldLabel: 'Password',   
         characterLimit: 20,
         width: 180,
         vpattern: '^(.){5,20}$',	
         formatMessage: 'The password must be at least 5 characters long',
         vpattern: '^.{5,20}$',			
         inputType: 'password',		
                  
         listeners: {
                     
            'keydown': function (a,e) {
                        
               if (e.getCharCode() == 13){
                  this.blur();
                  processLogin();
               }
      
            }//keydown
			
         }//listeners  
            
      });
      
            
      var processLogin = function() {
                  
         // Sanitization --------------------------------------------
            
         if (!(passwordField.validate())) {
         
            presentOverlay (
            
               FAIL, 
               passwordField.formatMessage,
               null, 
                  
               function(){ 
         		            
                  passwordField.focus(true);
         		            						               
               }
                  
            );//presentOverlay  
            
            return;
         
         }//if
         
         // ---------------------------------------------------------
         
         var restArgs = {
            'username' : usernameField.getValue(),
            'password' : encodeURIComponent(passwordField.getValue())
         };				
         
         XDMoD.REST.Call({
            action: 'authentication/utilities/login', 
            arguments: restArgs, 
            callback: loginCallback
         });
				
      }//processLogin


      
      // -----------------------------------------------------------------				            

      self.sectionGeneral = new Ext.FormPanel({
	    
         labelWidth: 95,
         frame:true,
         title: 'Log Back Into XDMoD',
         width: 300,
         defaults: {width: 200},
         defaultType: 'textfield',
	
         items: [
	        
	           usernameField, 
              passwordField
					
	        ]
	        
      });

      // -----------------------------------------------------------------
      
      var btnCancel = new Ext.Button({
         text: 'Log in as different user',
         flex: 1,
         handler: function() {
            location.href = 'index.php';
         }
      });

      // -----------------------------------------------------------------
      	    
      var btnLogin = new Ext.Button({
         
         text: 'Log Back In',
         flex: 1,
         
         handler: function() {

            processLogin();
				
         }
         
      });


     // -----------------------------------------------------------------
      
      var loginCallback = function(responseData) {

            if (responseData.success) {
            
               // Cache the new token
               XDMoD.REST.token = responseData.results.token;
                                            
               self.hide();
               passwordField.setValue('');
            
            
               // Upon successful re-login, look in the REST call configuration to see if a custom callback (override) has
               // been specified.  If so, invoke that callback.
               
               if (self.rest_call_config && self.rest_call_config.successCallback) {
                  self.rest_call_config.successCallback();
                  return;
               }

               // ... Otherwise, check the REST call configuration to see if the call that triggered the login prompt
               // should be re-attempted.
                              
               if (self.rest_call_config && self.rest_call_config.resume == true)
                  XDMoD.REST.Call(self.rest_call_config);
            
            }
            else{
            				   
               presentOverlay (
                  
                  FAIL, 
                  responseData.message,
                  null, 
                     
                  function(){ 
						            
                     passwordField.focus(true);
						            						               
                  }
                        
               );//presentOverlay   

            }
            
      }//loginCallback
      
      // -----------------------------------------------------------------

      var relogin_panel_container = new Ext.Panel({
		
         id: 'panel_outer_container', 
         baseCls:'x-plain',
         plugins: [new Ext.ux.plugins.ContainerMask ({ msg:'', masked:false })],
         
         items:[ 
         
            self.sectionGeneral,
            
            {
            
               anchor:'100%',
               baseCls:'x-plain',
               layout:'hbox',
               padding: '10 0 0 0',

               items: [btnCancel, {xtype: 'spacer', width: 20}, btnLogin]
            
            }
                        
          ]
		
		});
		
      // -----------------------------------------------------------------
            
      this.on('show', function() { 
               
         (function() { 
            passwordField.focus(true);
         }).defer(500);
 
      });

      // -----------------------------------------------------------------		
		      
      Ext.apply(this, {

         items:[ relogin_panel_container ]
         
		});
		
 		XDMoD.LoginPrompt.superclass.initComponent.call(this);
        	
	}
	
});

