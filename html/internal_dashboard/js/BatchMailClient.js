Ext.ns('XDMoD');

XDMoD.BatchMailClient = Ext.extend(Ext.Window, {

   width: 600,
   height: 500,
   title: 'Batch E-Mail',
   modal: true,
   
   initComponent: function(){
      
      var self = this;

      var current_group_visibility = 'all';
      var current_role_visibility = 'any';
      
      this.on('activate', function() {
      
         ServerRequest({

            url: 'controllers/controller.php', 
            params: {operation: 'enum_user_types_and_roles'},	
            method: 'POST',
            callback: function(options, success, response) { 

               if (success) {
                                
                  var json = Ext.util.JSON.decode(response.responseText);

                  for (var i = 0; i < json.user_types.length; i++){
                  
                     mnuUserTypeFilter.addItem({ text: json.user_types[i].type + ' Users', type_id: json.user_types[i].id });
                  
                  }

                  for (var i = 0; i < json.user_roles.length; i++)
                     mnuUserRoleFilter.addItem({ text: json.user_roles[i].description , role_id: json.user_roles[i].role_id });
                                    
               }
               else {
               
                  CCR.xdmod.ui.generalMessage('Batch E-Mail Client', 'There was a problem connecting to the portal service provider.', false);
               
               }

            }//callback

         });//ServerRequest

         ServerRequest({

            url: 'controllers/mailer.php', 
            params: {operation: 'enum_presets'},	
            method: 'POST',
            callback: function(options, success, response) { 

               if (success) {
                                
                  var json = Ext.util.JSON.decode(response.responseText);

                  for (var i = 0; i < json.presets.length; i++)
                     mnuMessagePresets.addItem({ text: '<span style="color: #00f">' + json.presets[i] + '</span>' });
                                    
               }
               else {
               
                  CCR.xdmod.ui.generalMessage('Batch E-Mail Client', 'There was a problem connecting to the portal service provider.', false);
               
               }

            }//callback

         });//ServerRequest
                  
      }, this, {single: true});

      // ---------------------------------

      var mnuUserRoleFilter = new Ext.menu.Menu({
      
         plain: true,
         showSeparator: false,
         cls: 'no-icon-menu',
                     
         items: [{ text: 'Any Role', role_id: 'any' }]
         
      });//mnuUserRoleFilter

      // ---------------------------------
            
      mnuUserRoleFilter.on('click', function(menu, menuItem, e) {

         current_role_visibility = menuItem.role_id;
         
         btnUserRoleFilter.setText('<b class="selected_menu_item">' +  menuItem.text + '</b>');
      
      });//mnuUserRoleFilter
      
      // ---------------------------------
      
      var btnUserRoleFilter = new Ext.Button({
      
         xtype: 'button',       
         iconCls: 'btn_role',               
         text: '<b class="selected_menu_item">Any Role</b>',    
         
         menu: mnuUserRoleFilter
                  
      });//btnUserRoleFilter
        
      // ---------------------------------

      var mnuUserTypeFilter = new Ext.menu.Menu({
      
         plain: true,
         showSeparator: false,
         cls: 'no-icon-menu',
                     
         items: [{ text: 'All Users', type_id: 'all' }]
         
      });//mnuUserTypeFilter
      
      mnuUserTypeFilter.on('click', function(menu, menuItem, e) {
         
         current_group_visibility = menuItem.type_id;
         
         btnUserTypeFilter.setText('<b class="selected_menu_item">' +  menuItem.text + '</b>');
      
      });//mnuUserTypeFilter
      
      // ---------------------------------
      
      var btnUserTypeFilter = new Ext.Button({
      
         xtype: 'button',       
         iconCls: 'btn_group',               
         text: '<b class="selected_menu_item">All Users</b>',    
         
         menu: mnuUserTypeFilter
                  
      });//btnUserTypeFilter

      // ---------------------------------

      var txtSubject =  new Ext.form.TextField({ 
         layout: 'fit',
         region: 'north',
         margins: '0 0 15 0',
         emptyText: 'Specify a subject here.'
      });
      
      var txtMessage =  new Ext.form.TextArea({ 
         layout: 'fit',
         region: 'center',
         emptyText: 'Specify a message you wish to send.'
      });
      
      var mnuMessagePresets = new Ext.menu.Menu({
      			
         plain: true,
         showSeparator: false,
         cls: 'no-icon-menu',
            
         items: [
            {text: '<span style="color: #00f">Custom</span>'},
            '-'        
         ], 
            
         listeners: {
         
            itemclick: {
            
               fn: function(baseItem, e) {
                                      
                  var selectedPreset = baseItem.text.toString();
                         
                  btnMessageTemplateSelector.setText(selectedPreset);
                  
                  var strippedPreset = selectedPreset.replace('<span style="color: #00f">', '').replace('</span>', '');
                  
                  if (strippedPreset == 'Custom') {
                     
                     txtSubject.reset();
                     txtMessage.reset();
                     
                  }
                  else {

                     txtSubject.setValue(strippedPreset);

                     ServerRequest({
            
                        url: 'controllers/mailer.php', 
                        params: {
                           operation: 'fetch_preset_message', 
                           preset: strippedPreset
                        },	
                        method: 'POST',
                        callback: function(options, success, response) { 
            
                           if (success) {
                                            
                              var json = Ext.util.JSON.decode(response.responseText);
                                    
                              txtMessage.setValue(json.content);
                                          
                           }
                           else {
                           
                              CCR.xdmod.ui.generalMessage('Batch E-Mail Client', 'There was a problem connecting to the portal service provider.', false);
                           
                           }
            
                        }//callback
            
                     });//ServerRequest
                           
                  }

               }//fn
            
            }//itemclick
            
         }//listeners
         	
      });//menu

      // ---------------------------------

      var btnMessageTemplateSelector = new Ext.Button({
      
         text: '<span style="color: #00f">Custom</span>',
                  
         menu: mnuMessagePresets
         
      });//btnMessageTemplateSelector
      
      // ---------------------------------

      var deliverMessage = function (mode, messageSubject, messageToSend, emails) {

         ServerRequest({
         
            url: 'controllers/mailer.php', 
            params: {
               'operation' : mode,
               'group_filter' : current_group_visibility,
               'role_filter' : current_role_visibility,
               'subject' : messageSubject,
               'message': messageToSend,
               'target_addresses' : (emails != undefined) ? emails : ''
            },	
            
            method: 'POST',
            callback: function(options, success, response) { 
                        
               if (success) {
               
                  var json = Ext.util.JSON.decode(response.responseText);
               
                  if(json.success == true) {
                                          
                     if (mode == 'enum_target_addresses') {
                                             
                        if (json.count == 0) {
                     
                           CCR.xdmod.ui.generalMessage('Batch E-Mail Client', 'No accounts are available under the group and role you selected', false);
                           return;
                        
                        }
                        
                        var p = new XDMoD.RecipientVerificationPrompt({
                        
                           recipients: json.response,
                           
                           verificationCallback: function(emails) {
                              deliverMessage('send_plain_mail', messageSubject, messageToSend, emails);
                           }
                           
                        });
                        
                        p.show();
                        
                     }
 
                     if (mode == 'send_plain_mail') {

                        CCR.xdmod.ui.generalMessage('Batch E-Mail Client', 'Your message has been sent successfully', true);
                        
                     }
                                         
                  }
                  else {
                  
                     CCR.xdmod.ui.generalMessage('Batch E-Mail Client', json.message, false);
                     
                  }
               
               }//if
         
            }//callback
         
         });//ServerRequest

      }//deliverMessage
		
      // ---------------------------------
                        
      Ext.apply(this, {
       
          padding: '5 5 5 5',
          title: 'E-Mail Client',
          width: 700,
          height: 360,
          resizable: false,
          layout: 'border',
          
          tbar: {
             items: [
                {xtype: 'tbtext', html: 'Send this message to'}, 
                btnUserTypeFilter,
                {xtype: 'tbtext', html: 'having a role of'}, 
                btnUserRoleFilter,
                '->',
                 '|', 
                {xtype: 'tbtext', html: 'Use Template:'},
                btnMessageTemplateSelector
             ]
          },
                   
          bbar: {
             items: [
             
                '->',
                
                new Ext.Button({
                
                   text: 'Send Now',
                   iconCls: 'btn_email',
                   handler: function() {
                   
                        var messageSubject = Ext.util.Format.trim(txtSubject.getValue());
                        var messageToSend = Ext.util.Format.trim(txtMessage.getValue());
                        
                        if(messageToSend.length == 0 || messageSubject.length == 0) {
                                                   
                           CCR.xdmod.ui.generalMessage('Batch E-Mail Client', 'You must specify a subject and/or message first', false);
                           
                           return;
                           
                        }//if(messageToSend.length == 0)
                        
                        deliverMessage('enum_target_addresses', messageSubject, messageToSend);
                                                 
                   }//handler
                   
                })
                
             ]   
             
          },
          
          items: [
       
             new Ext.Panel({
          
                margins: '7 7 7 7',
                region: 'center',
                baseCls: 'x-plain',
                layout: 'border',
                items: [
                  txtSubject,
                  txtMessage
                ]
                
             })
             
          ]     
      
       });//Ext.apply
      
      XDMoD.BatchMailClient.superclass.initComponent.call(this);
      
   }//initComponent
   
});//XDMoD.BatchMailClient