/*

   XDMoD.PortalModule

   Author: Ryan Gentner
   Last Updated: Wednesday, June 12, 2013 
   
   Each of the tabs / modules in XDMoD extend XDMoD.PortalModule.  This class provides functional UI components
   which are common among most tabs / modules, such as
   
   - Role drop-down selector
   - Duration selector
   - Filter dialog (?)
   - Display menu (?)
   - Export menu
   - Add to report checkbox
   
*/

Ext.namespace('XDMoD');

XDMoD.ToolbarItem = {
   
   ROLE_SELECTOR: 0,
   DURATION_SELECTOR: 1,
   EXPORT_MENU: 2,
   PRINT_BUTTON: 3,
   REPORT_CHECKBOX: 4
   
};//XDMoD.ToolbarItem

// ===========================================================================

XDMoD.ExportOption = {
   
   CSV: 0,
   XML: 1,
   PNG: 2,
   PNG_WITH_TITLE: 3,
   PNG_SMALL: 4,
   PNG_SMALL_WITH_TITLE: 5,
   PNG_HD: 6,
   PNG_HD_WITH_TITLE: 7,
   PNG_POSTER: 8,
   PNG_POSTER_WITH_TITLE: 9, 
   SVG: 10,
   SVG_WITH_TITLE: 11
   
};//XDMoD.ExportOption

// ===========================================================================

XDMoD.PortalModule = Ext.extend(Ext.Panel,  {

   usesToolbar: false,
  
   toolbarItems: {
      
      roleSelector: false,
      durationSelector: false,
      exportMenu: false,
      printButton: false,
      reportCheckbox: false
      
   },//toolbarItems
   
   // customOrder: The top-town order of entries in this array corresponds to 
   //              the left-right ordering of components in the top toolbar.
        
   customOrder: [
 
      XDMoD.ToolbarItem.ROLE_SELECTOR,
      XDMoD.ToolbarItem.DURATION_SELECTOR,
      XDMoD.ToolbarItem.EXPORT_MENU,
      XDMoD.ToolbarItem.PRINT_BUTTON,
      XDMoD.ToolbarItem.REPORT_CHECKBOX

   ],   

   // ------------------------------------------------------------------
      
   initComponent: function(){
       
      var self = this;
      
      self.addEvents('role_selection_change', 'duration_change', 'export_option_selected', 'print_clicked');
            
      var createRoleSelector = function() {
         
         var roleCategorySelectorItems = new Array();
         
         var roleCategoryGroup = Ext.id();

         for(x in CCR.xdmod.ui.roleCategories) {

            if (CCR.xdmod.ui.roleCategories[x] == 'separator') {
         
               if (roleCategorySelectorItems.length > 0) {
                  roleCategorySelectorItems.push('-');
               }
               
            }
            else {

               roleCategorySelectorItems.push (
            
                  new Ext.menu.CheckItem({
                     
                     text: CCR.xdmod.ui.roleCategories[x],
                     value: x,
                  
                     scope: this,
                     group: roleCategoryGroup,
                  
                     handler: function(b, e) {
               
                        roleCategorySelectorButton.setText(b.text);
                        roleCategorySelectorButton.value = b.value;

                        XDMoD.TrackEvent(self.title, 'Role changed', b.text);
            
                        self.fireEvent('role_selection_change', b, e);
               
                     }//handler
  
                  })

               );//roleCategorySelectorItems.push

            }
         
         }//for(x in CCR.xdmod.ui.roleCategories)

         roleCategorySelectorItems[0].checked = true;

         var roleCategorySelectorButton = new Ext.Button({

            iconCls: 'bookmark',
            scope: this,
            width: 100,
            value: roleCategorySelectorItems[0].value,
            text: roleCategorySelectorItems[0].text,

            set: function(value) {
               this.value = value;
               this.setText(CCR.xdmod.ui.roleCategories[value]);
            },
            
            tooltip: 'Filter Usage',

            menu: new Ext.menu.Menu({
            
               showSeparator: false,
               items: roleCategorySelectorItems

            })

         });//roleCategorySelectorButton

         self.getRoleSelectorItems = function() {
         
            return roleCategorySelectorItems;
            
         }//getRoleSelectorItems
      
         return roleCategorySelectorButton;

      }//createRoleSelector
      
      // ----------------------------------------  

      var exportFunction = function (format, showTitle, scale, width, height) {

         var parameters = {};

         parameters['scale'] = scale || 1;
         //parameters['show_title'] = showTitle;
         parameters['show_title'] = showTitle ? 'y' : 'n';
         parameters['format'] = format;
         parameters['inline'] = 'n';

         parameters['width'] =  width || 757;
         parameters['height'] = height || 400;
         
         if(format == 'svg') parameters['font_size'] = 0;

         XDMoD.TrackEvent(self.title, 'Export Menu Used', Ext.encode(parameters), true);

         self.fireEvent('export_option_selected', parameters);
                
      };//exportFunction

      // ----------------------------------------  
              
      var createExportMenu = function(config) {
      
         //console.log(config);
         
         var activeOptions = [

            XDMoD.ExportOption.CSV,
            XDMoD.ExportOption.XML,
            '-',
            XDMoD.ExportOption.PNG,
            XDMoD.ExportOption.PNG_WITH_TITLE,
            XDMoD.ExportOption.PNG_SMALL,
            XDMoD.ExportOption.PNG_SMALL_WITH_TITLE,
            XDMoD.ExportOption.PNG_HD,
            XDMoD.ExportOption.PNG_HD_WITH_TITLE,
            XDMoD.ExportOption.PNG_POSTER,
            XDMoD.ExportOption.PNG_POSTER_WITH_TITLE,
            XDMoD.ExportOption.SVG,
            XDMoD.ExportOption.SVG_WITH_TITLE

         ];//activeOptions

         if (config.length > 0) activeOptions = config.slice(0);
         
         var menuContent = [];
         var i = 0;
         
         for(i = 0; i < activeOptions.length; i++) {
         
            switch(activeOptions[i]) {
            
               case XDMoD.ExportOption.CSV:
               
                  menuContent.push({
                     text: 'CSV - Comma Separated Values', iconCls: 'csv',
                     handler: function () {
                        exportFunction('csv', false);
                     }
                  });
                  
                  break;
               
               
               case XDMoD.ExportOption.XML:

                  menuContent.push({
                     text: 'XML - Extensible Markup Language', iconCls: 'xml',
                     handler: function () {
                        exportFunction('xml', false);
                     }                  
                  });           
               
                  break;
                  
                  
               case XDMoD.ExportOption.PNG:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', false, 1,916,484);
                     }             
                  });           
               
                  break;
                  
 
               case XDMoD.ExportOption.PNG_WITH_TITLE:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics w/ Title', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', true, 1,916,484);
                     }             
                  });           
               
                  break;                 


               case XDMoD.ExportOption.PNG_SMALL:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics - Small', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', false, 1, 640, 380);
                     }
                  });           
               
                  break;
                  
 
               case XDMoD.ExportOption.PNG_SMALL_WITH_TITLE:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics - Small w/ Title', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', true, 1, 640, 380);
                     }             
                  });           
               
                  break;    


               case XDMoD.ExportOption.PNG_HD:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics - HD', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', false, 1, 1280, 720);
                     }             
                  });           
               
                  break;
                  
 
               case XDMoD.ExportOption.PNG_HD_WITH_TITLE:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics - HD w/ Title', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', true, 1, 1280, 720);
                     }             
                  });           
               
                  break;                 


               case XDMoD.ExportOption.PNG_POSTER:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics - Poster', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', false, 1, 1920, 1080);
                     }             
                  });           
               
                  break;
                  
 
               case XDMoD.ExportOption.PNG_POSTER_WITH_TITLE:

                  menuContent.push({
                     text: 'PNG - Portable Network Graphics - Poster w/ Title', iconCls: 'png',
                     handler: function () {
                        exportFunction('png', true, 1, 1920, 1080);
                     }         
                  });           
               
                  break; 


               case XDMoD.ExportOption.SVG:

                  menuContent.push({
                     text: 'SVG - Scalable Vector Graphics', iconCls: 'png',
                     handler: function () {
                        exportFunction('svg', false, 1,757,400);
                     }             
                  });           
               
                  break;
                  
                  
               case XDMoD.ExportOption.SVG_WITH_TITLE:

                  menuContent.push({
                     text: 'SVG - Scalable Vector Graphics w/ Title', iconCls: 'png',
                     handler: function () {
                        exportFunction('svg', true, 1,757,400);
                     }             
                  });
                  
                  break;
                  
                  
               default:
                   
                  menuContent.push(activeOptions[i]);
                  
                  break; 
                          
                                                                                          
            }//switch
            
         }//for
         
         var exportButton = new Ext.Button({
        
            text: 'Export',
            iconCls: 'export',
            tooltip: 'Export chart data',
            
            //disabled: true,

            menu: menuContent

         });//new Ext.Button
         
         self.getExportMenu = function() {
            return exportButton;
         };
         
         return exportButton;
        
      }//createExportMenu
      
      // ----------------------------------------  
       
      var createPrintButton = function() {
      
         var printButton = new Ext.Button({ 

            text: 'Print',
            iconCls: 'print',
            tooltip: 'Print chart',
            //disabled: true,
            scope: this,
            handler: function() {

               XDMoD.TrackEvent(self.title, 'Print Button Clicked');

               self.fireEvent('print_clicked');

            }//handler

         });//printButton

         self.getPrintButton = function() {
            return printButton;
         };
         
         return printButton;		

      }//createPrintButton
      
      // ----------------------------------------  

      var createReportCheckbox = function(module_id) {
      
         var reportCheckbox = new CCR.xdmod.ReportCheckbox({
            disabled: false, 
            hidden: false, 
            module: module_id
         });
         
         reportCheckbox.on('toggled_checkbox', function(v) {
         
            XDMoD.TrackEvent(self.title, 'Clicked on Available For Report checkbox', v);
                  
         });//reportCheckbox.on('toggled_checkbox',...
         
         self.getReportCheckbox = function() {
            return reportCheckbox;
         };
         
         return reportCheckbox;	

      }//createReportCheckbox
      
      // ----------------------------------------
      
      var moduleConfig = {
      
         layout: 'border',
         frame: false,
         border: false
 
      };
      
      if (self.usesToolbar == true) {
      
         moduleConfig.tbar = new Ext.Toolbar({
            items: []
         });

         var tbItemIndex = 0;
         
         for (tbItemIndex = 0; tbItemIndex < self.customOrder.length; tbItemIndex++) {
         
            var currentItem = self.customOrder[tbItemIndex];
            
            var employSeparator = true;
            
            if (currentItem['item'] !== undefined) {

               employSeparator = (currentItem['separator'] !== undefined) ? currentItem['separator'] : true;
               
               currentItem = currentItem['item'];

            }
            
            switch(currentItem) {
            
               case XDMoD.ToolbarItem.ROLE_SELECTOR:
               
                  if (self.toolbarItems.roleSelector == true) {
                     
                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');

                     var roleSelector = createRoleSelector();
                     
                     self.getRoleSelector = function() {
                        return roleSelector;
                     };
                     
                     moduleConfig.tbar.addItem('Role:');
                     moduleConfig.tbar.addItem(roleSelector);
            
                  }//if (self.toolbarItems.roleSelector == true)
                  
                  break;
                  
               case XDMoD.ToolbarItem.DURATION_SELECTOR:

                  var durationConfig = {};
                  
                  if (self.toolbarItems.durationSelector != undefined && self.toolbarItems.durationSelector['enable'] != undefined) {
                     
                     if (self.toolbarItems.durationSelector['config'] != undefined)
                        durationConfig = self.toolbarItems.durationSelector['config'];
                     
                     self.toolbarItems.durationSelector = self.toolbarItems.durationSelector['enable'];
                     
                  }//if (self.toolbarItems.durationSelector['enable'] != undefined)
                                    
                  // ----------------------------------
                  
                  if (self.toolbarItems.durationSelector == true) {
                  
                     var previousItems = new Array();
   
                     moduleConfig.tbar.items.each(function(item) {
                     
                        previousItems.push(item);
                        
                     });
   
                     if (previousItems.length > 0 && employSeparator)
                        previousItems.push('-');

                     var baseConfig = {
                         
                        items: previousItems,
                           
                        handler: function(d) {
                           
                           XDMoD.TrackEvent(self.title, 'Timeframe updated', Ext.encode(d));

                           self.fireEvent('duration_change', d);
                           
                        }
                           
                     };//baseConfig
                     
                     Ext.apply(baseConfig, durationConfig);
               
                     var durationToolbar = new CCR.xdmod.ui.DurationToolbar(baseConfig);
                     
                     self.getDurationSelector = function() {
                        return durationToolbar;
                     };
                     
                     moduleConfig.tbar = durationToolbar;
                  
                  }//if (self.toolbarItems.durationSelector == true)
                  
                  break;
                  
               case XDMoD.ToolbarItem.EXPORT_MENU:

                  var exportConfig = [];
                  
                  if (self.toolbarItems.exportMenu != undefined && self.toolbarItems.exportMenu['enable'] != undefined) {
                     
                     if (self.toolbarItems.exportMenu['config'] != undefined)
                        exportConfig = self.toolbarItems.exportMenu['config'];
                     
                     self.toolbarItems.exportMenu = self.toolbarItems.exportMenu['enable'];
                     
                  }//if (self.toolbarItems.exportMenu['enable'] != undefined)
  
                  // ----------------------------------
  
                  if (self.toolbarItems.exportMenu == true) {

                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');
            
                     moduleConfig.tbar.addItem(createExportMenu(exportConfig));

                  }//if (self.toolbarItems.exportMenu == true)
                  
                  break;
                  
               case XDMoD.ToolbarItem.PRINT_BUTTON:

                  if (self.toolbarItems.printButton == true) {

                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');
            
                     moduleConfig.tbar.addItem(createPrintButton());
                        
                  }
                     
                  break;
                  
               case XDMoD.ToolbarItem.REPORT_CHECKBOX:

                  if (self.toolbarItems.reportCheckbox == true) {

                     if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                        moduleConfig.tbar.addItem('-');
            
                     moduleConfig.tbar.addItem(createReportCheckbox(self.module_id));
                             
                  }
                  
                  break;
                  
               default:

                  if (moduleConfig.tbar.items.getCount() > 1 && employSeparator)
                     moduleConfig.tbar.addItem('-');

                  moduleConfig.tbar.addItem(currentItem);
                                 
                  break;
                  
            }//switch
            
         }//for
         
      }//if (self.usesToolbar == true)
     
      // ----------------------------------------      

      Ext.apply(this, moduleConfig);//Ext.apply
      
      XDMoD.PortalModule.superclass.initComponent.call(this);
   
   }//initComponent

});//XDMoD.PortalModule