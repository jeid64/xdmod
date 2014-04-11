XDMoD.InitAssistant = function(cfg) {
                
   //console.log('in InitAssistant');
   
   if (Object.keys(cfg).length == 0) return;

   new XDMoD.Assistant({
         
      catalog_pointer: cfg,
      
      highlight: cfg['1'].highlight,
      title: cfg['1'].title,
            
      width: cfg['1'].width,
      height: cfg['1'].height,
   
      html: cfg['1'].description,
      
      padding: cfg['1'].padding,
      
      order: 1,
      
      showNextButton: (cfg['2'] != undefined)
                     
   }).init();                

}//XDMoD.InitAssistant

// ==============================================================================

XDMoD.Assistant = Ext.extend(Ext.Window,  {

	initComponent: function(){

      var self = this;
      var barrier = false;
      var barrier_next = false;
      
      var clickedNext = false;
      
      var spotlight_id = '';
      
      var reposition = function(d) {
      
         if (d == undefined) d = 0;
         
         new Ext.util.DelayedTask(function() {
         
            // The following accounts for positioning to the right of said component
            // todo: account for other positionings (right, top, bottom)
            
            var y_coord = Ext.fly(self.highlight).getTop();
            var right_pos = Ext.fly(self.highlight).getRight();
   
            self.setPosition(right_pos + 20, y_coord);
         
         }).delay(d);
               
      }//reposition
      
      // ----------------------------------------------                     

      self.init = function() {
         
         spotlight_id = 'slid_' + Ext.id();
         
         $('#' + self.highlight).spotlight({
         
            speed: 1000,
            animate: true, //Ext.isIE ? false : true,
            
            mask_id: spotlight_id,
         
            beforeHide: function() {
               
               if (barrier == false) {
                  barrier = true;
                  self.close();
               }
            
            },//beforeHide
            
            onShow: function() {

               reposition();
               
               new Ext.util.DelayedTask(function() {
                  self.show();
               }).delay(100);
               
               Ext.EventManager.onWindowResize(function() {

                  reposition(100);
     
               });
                             
            },//onShow
            
            onHide: function() {
            
               if (clickedNext == true && barrier_next == false) {
               
                  barrier_next = true;
                  
                  var next = self.order + 1;

                  new XDMoD.Assistant({
                        
                     catalog_pointer: self.catalog_pointer,
                     
                     highlight: self.catalog_pointer[next].highlight,
                     title: self.catalog_pointer[next].title,
                           
                     width: self.catalog_pointer[next].width,
                     height: self.catalog_pointer[next].height,
                  
                     html: self.catalog_pointer[next].description,
                     
                     padding: self.catalog_pointer[next].padding,
                     
                     order: next,
                     mode: 'next',
                     
                     showNextButton: (self.catalog_pointer[next + 1] != undefined)
                                    
                  }).init();               
               
               }
               
            }//onHide
         
         });
                                  
      };//self.init

      // ----------------------------------------------
      
      var footer_items = ['->'];
      
      var btnNext = new Ext.Button({
            
         text: 'Next',
         iconCls: 'assist_next',
         
         handler: function() {
            
            clickedNext = true;
            self.close();
            
         }
               
      });

      var btnDone = new Ext.Button({
            
         text: 'Close',
         iconCls: 'general_btn_close',
         
         handler: function() {
            self.close();
         }
               
      });
         
            
      if (self.showNextButton)
         footer_items.push(btnNext);
      else
         footer_items.push(btnDone);
      
      
      Ext.apply(this, {

         draggable: false,
         resizable: false,
         
         bodyStyle: {
            background: '#f6f0ae',
            padding: self.padding || '10px'
         },
                  
         bbar: {
         
            items: footer_items
         
         },
         
         listeners: {
         
            'show': function(w) {
            
               document.getElementById(w.id).style.zIndex = 9999;
               
            },
            
            'beforeclose': function() {
               
               if (barrier == false) {
                
                  barrier = true;
                     
                  $('#' + spotlight_id).click();
               
               }
               
            }
         
         }

      });
                           
 		XDMoD.Assistant.superclass.initComponent.call(this);
        	
	}//initComponent
	
});//XDMoD.Assistant