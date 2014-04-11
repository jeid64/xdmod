Ext.namespace('XDMoD', 'Compliance');

// ===========================================================================

Compliance.showInfo = function (e, id, requirement, tip) {
   
   var ttip = new Ext.ToolTip({
   
      target: id,
      showDelay: 500,
      html: '<b><font color="#000000">' + requirement + '</font></b><br>' + tip
      
   });

   ttip.on('hide', function(t) {
      t.destroy();
   });   

   ttip.showAt([e.clientX + 10, e.clientY + 10]); 
   
}//Compliance.showInfo
      
// ===========================================================================

Compliance.showResourceInfo = function (e, code, desc, processors, resource_start_date, official_end_date) {

   var markup =  '<b>' + code + '</b><br />' + 
                 'Type: <i>' + desc + '</i><br />' + 
                 'Processors: <i>' + processors + '</i><br />' + 
                 'In Service: <i>' + resource_start_date + '</i><br />' + 
                 'End Of Service: <i>' + official_end_date + '</i>';
         
   var ttip = new Ext.ToolTip({
   
      target: e.target,
      showDelay: 500,
      html: markup
      
   });

   ttip.on('hide', function(t) {
      t.destroy();
   });   

   ttip.showAt([e.clientX + 10, e.clientY + 10]); 

}//Compliance.showResourceInfo

// ===========================================================================

Compliance.Opac = function(e) {

   if(Compliance.PrevElement !== undefined) {

      for (var i = 0; i < Compliance.PrevElement.childNodes.length; i++)
         Compliance.PrevElement.childNodes[i].style.opacity = "1";   

   }
   
   Compliance.PrevElement = e.target.parentElement;
   
   for (var i = 0; i < e.target.parentElement.childNodes.length; i++)
      e.target.parentElement.childNodes[i].style.opacity = "0.8";

}//Compliance.Opac

// ===========================================================================

XDMoD.Module.Compliance = Ext.extend(XDMoD.PortalModule,  {

   module_id: 'compliance',
   
   initComponent: function(){
       
      var self = this;

      var emptyTextRef = Ext.id();
      
      var txtItemMonthDisplay = new Ext.Toolbar.TextItem();
      var txtItemQuarterDisplay = new Ext.Toolbar.TextItem();
      var txtItemYearDisplay = new Ext.Toolbar.TextItem();
      
      var tbSeparatorMonth = new Ext.Toolbar.Separator({hidden: true});
      var tbSeparatorQuarter = new Ext.Toolbar.Separator({hidden: true});
      var tbSeparatorYear = new Ext.Toolbar.Separator({hidden: true});

      var txtResourceCount = new Ext.Toolbar.TextItem();
      var tbSeparatorResourceCount = new Ext.Toolbar.Separator({hidden: true});

      var txtJobLevelCollectionStart = new Ext.Toolbar.TextItem();
      var tbSeparatorCollectionStart = new Ext.Toolbar.Separator({hidden: true});
      var txtResourceLevelCollectionStart = new Ext.Toolbar.TextItem();

      var descriptionPlaceholderText = '<img src="gui/images/compliance_instructions.png">'; //'Select a cell above to view more detailed information here.';

      // ----------------------------------------  

      var generateLegend = function(c) {
      
         var markup = '<div><table border=0><tr>';
         
         for (var i = 0; i < c.length; i++)
            markup += '<td style="background-color: ' + c[i].color + '" width=30>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td style="padding: 0 10px 0 0">&nbsp;' + c[i].label + '</td>';
      
         markup += '</tr></table></div>';
         
         return markup;
      
      }//generateLegend
                                  
      // ----------------------------------------      

      var cStore = new Ext.data.JsonStore({

         autoDestroy: false,
         root: 'data',
         
         proxy: new Ext.data.HttpProxy({
            method: 'POST',
            url: 'controllers/compliance.php'
         })

      });//this.reportStore

      cStore.on('metachange', function(store, meta) {
            
         meta.fields[0].renderer = providerRenderer;
         
         for (var i = 1; i < meta.fields.length; i++ )
            meta.fields[i].renderer = recordingRenderer;
         
         cGrid.reconfigure(store, new Ext.ux.grid.LockingColumnModel(meta.fields));

        
      });//cStore.on('metachange',...

      // ----------------------------------------     

      cStore.on('load', function(store) {
         
         if (store.reader.jsonData.success === false) {
            CCR.xdmod.ui.login_reminder.show();
            return;
         }

         if (store.reader.jsonData.resource_count == 0) {
             
             var ref = document.getElementById(emptyTextRef);
             
             if (store.reader.jsonData.organizations.length > 1) {
             
               var org_list = '- <b>' + store.reader.jsonData.organizations.join('</b><br />- <b>');
               ref.innerHTML = 'No compliance data is available for: <br /><br />' + org_list + '<br />';
               
             }
             else
               ref.innerHTML = 'No compliance data is available for <b>' + store.reader.jsonData.organizations[0] + '</b>';
             
             
             ref.innerHTML += '<br />with respect to the selected timeframe.';
             
             
             viewPanel.getLayout().setActiveItem(0);
             
             return;
         
         }//if (store.reader.jsonData.resource_count == 0)

         viewPanel.getLayout().setActiveItem(1);
         
         txtItemMonthDisplay.setText('Month: <b>' + store.reader.jsonData.timeframes.month.start_date + '</b> to <b>' + store.reader.jsonData.timeframes.month.end_date + '</b>');
         txtItemQuarterDisplay.setText('Quarter: <b>' + store.reader.jsonData.timeframes.quarter.start_date + '</b> to <b>' + store.reader.jsonData.timeframes.quarter.end_date + '</b>');
         txtItemYearDisplay.setText('Year: <b>' + store.reader.jsonData.timeframes.year.start_date + '</b> to <b>' + store.reader.jsonData.timeframes.year.end_date + '</b>');
         
         tbSeparatorMonth.show();
         tbSeparatorQuarter.show();
         //tbSeparatorYear.show();
         
         var collection_stats = store.reader.jsonData.collection_stats;
         
         txtResourceCount.setText(store.reader.jsonData.resource_count + ' resources');
         tbSeparatorResourceCount.show();
         
         txtJobLevelCollectionStart.setText('Job-level data collected as early as <b>' + collection_stats.job_level + '</b>');
         tbSeparatorCollectionStart.show();
         txtResourceLevelCollectionStart.setText('Resource-level data collected as early as <b>' + collection_stats.resource_level + '</b>');
      
      });//cStore.on('metachange',...
      
      // ---------------------------------------- 

      var colorValue = function(value) {
      
         var color = '#555';
         
         if (value == '100') color = '#040';
         if (value == '0') color = '#f00';
         if (value > 0 && value < 100) color = '#00f';
            
         if (value == 'no jobs' || value == 'not rep.') color = '#000';
         
         return color;
              
      };//colorValue                

      // ---------------------------------------- 

      var prepValue = function(value) {
      
         return value + ((value != "n/p") && (value != "n/a") && (value != "no jobs") && (value != "not rep.") ? '%' : '');
              
      };//prepValue

      // ---------------------------------------- 
            
      var recordingRenderer = function (value, meta, record, row_index, col_index) {
         
         var bgcolor_month = '#ffffff';
         var bgcolor_quarter = '#eae9e9'; //'#fbf4d5';
         var bgcolor_year = '#ffffff' //'#dff1fa'; // '#d6ffc4';
   
         meta.css += ' compliance_stat_cell';
   
         if (row_index == 0) {               
                           
            return '<div style="width: 100%">' +
                   '<div style="float: left; width: 33%; background-color: ' + bgcolor_month + '"><b>Month</b></div>' + 
                   '<div style="float: left; width: 33%; background-color: ' + bgcolor_quarter + '"><b>Quarter</b></div>' + 
                   '<div style="float: left; width: 33%; background-color: ' + bgcolor_year + '"><b>Year</b></div>' + 
                   '</div>';
                   
         }     
         else {
            
            if (record.json.section_break == true) {
         
               if(record.json.requirement == 'Record Count') {

                  if (value) {
                     return '<div style="width: 100%; height: 100%">' + 
                            '<div style="float: left; text-align: right; width: 33%; opacity: 0.8; font-weight: bold; filter: alpha(opacity = 80); background-color: #f1f5c7">' + value.m_value + '</div>' + 
                            '<div style="float: left; text-align: right; width: 33%; opacity: 0.8; font-weight: bold; filter: alpha(opacity = 80); background-color: #e1e893">' + value.q_value + '</div>' + 
                            '<div style="float: left; text-align: right; width: 34%; opacity: 0.8; font-weight: bold; filter: alpha(opacity = 80); background-color: #f1f5c7">' + value.y_value + '</div>' + 
                            '</div>';          
                  }
               
               
               }
               else {
               
                  meta.css += ' compliance_section_break';
                  return '';
                  
               }
         
            }
            else {
            
               if (value) {
                  return '<div class="compliance_stats" style="width: 100%; height: 13px;">' + 
                         '<div onmouseover="Compliance.Opac(event)" style="float: left; text-align: right; width: 33%;  background-color: ' + bgcolor_month + '; color: ' + colorValue(value.m_value) + '">' + prepValue(value.m_value) + '</div>' + 
                         '<div onmouseover="Compliance.Opac(event)" style="float: left; text-align: right; width: 33%;  background-color: ' + bgcolor_quarter + '; color: ' + colorValue(value.q_value) + '">' + prepValue(value.q_value) + '</div>' + 
                         '<div onmouseover="Compliance.Opac(event)" style="float: left; text-align: right; width: 34%;  background-color: ' + bgcolor_year + '; color: ' + colorValue(value.y_value) + '">' + prepValue(value.y_value) + '</div>' + 
                         '</div>';
               }
         
            }
            
         }//if (value)
         
      }//recordingRenderer
      
      // ---------------------------------------- 
            
      var providerRenderer = function (value, meta, record, row_index, col_index) {
      
         //meta.attr = 'style="background-color:#eee;font-weight:bold; padding-bottom: 12px"';

         if (row_index > 0) {
         
            if (record.json.section_break == true) {

               meta.css += ' compliance_section_break';
               
               if(record.json.requirement == 'Record Count')
                  meta.css += ' job_count';

               return value;
         
            }
            else {
               
               meta.css += ' compliance_requirement_cell';
                   
               if (record.json.is_requested == true) meta.css += ' requested_requirement';      
               
               var info_tag = '';
               
               if (record.json.tooltip.length > 0) {
               
                  var info_tag_id = Ext.id();
                  
                  info_tag = '<div style="float: right; padding-top: 2px; border: 0px solid"><img id=\"' + info_tag_id + '\" onmouseover=\'Compliance.showInfo(event, "' + info_tag_id + '", "' + value + '", "' +  
                             record.json.tooltip + '")\' src="gui/images/compliance_info.png"></div>';
                             
               }
               
               return '<div><div style="float: left">' + value + 
                      '</div>' + info_tag + '</div>';
            
            }
            
         }
         else {
            
            meta.css += ' compliance_empty_requirement_cell';
            return value;
         
         }
         
      }//providerRenderer

      // ---------------------------------------- 
          
      var resolveRecordFromGridIndicies =  function(ri, ci, call_track_event) {
      
         var col_header = cGrid.getColumnModel().getColumnHeader(ci);
               
         var match = />(.+)</.exec(col_header);
         
         if (call_track_event && call_track_event === true) {
         
            XDMoD.TrackEvent('Compliance', 'Selected cell in grid', Ext.encode({
            
               resource: match[1],
               requirement: cStore.getAt(ri).json.requirement
               
            }));
         
         }
               
         return cStore.getAt(ri).get(match[1]);

      }//resolveRecordFromGridIndicies

      // ---------------------------------------- 
            
      var cellSelModel = new Ext.grid.CellSelectionModel({
      
         listeners: {
         
            'beforecellselect': function (sm, ri, ci) {
            
               if (ri == 0 || ci == 0) return false;
            
               var record = resolveRecordFromGridIndicies(ri, ci);

               if (record.desc == '[section_break]') return false;
            
            },
            
            'cellselect': function(sm, ri, ci) {
              
               if (ci == 0) return;
               
               var record = resolveRecordFromGridIndicies(ri, ci, true);
               
               document.getElementById(cpRef).innerHTML = record.desc;

            }//cellselect
            
         }//listeners
         
      });//cellSelModel
      
      // ---------------------------------------- 
            
      var cGrid = new Ext.grid.GridPanel({

         
         layout: 'fit',
         store: cStore,
         
         enableColumnMove: false,
         
         cls: 'compliance_grid',
      
         autoScroll: true,
         
         selModel: cellSelModel,
         
         enableHdMenu: false,
         loadMask: true,
         
         colModel: new Ext.ux.grid.LockingColumnModel([]),
         view: new Ext.ux.grid.LockingGridView(),
         
         bbar: {
         
            items: [

                new Ext.Toolbar.TextItem({
                  html: '<span style="color: #000">Requirements in <b style="color: #000">black text</b> are <b style="color: #000">supported by XSEDE</b>; ' + 
                        'those in <b style="color: #978f8e">gray text</b> have been <b style="color: #978f8e">proposed</b></span>; ' + 
                        'n/a = not applicable; n/p = not provided'  
               }), 

               '->',
               
               txtResourceCount,
               tbSeparatorResourceCount,
               txtJobLevelCollectionStart,
               tbSeparatorCollectionStart,
               txtResourceLevelCollectionStart
                           
            ]
            
         }

      });//cGrid		

      // ---------------------------------------- 		

      var cpRef = Ext.id() + '-compliance-description';

      var commentsPanel = new Ext.Panel(
      {
         region: 'south',
         autoScroll: true,
         //split: true,  //enable to allow resizing
         title: 'Description',
         height: 130,
         html: '<div style="font-family: Arial; width: 100%; height: 100%; background-color: #fff;"><div style="position: absolute; margin: 5px; font-size: 14px" id="' + cpRef + '">' + descriptionPlaceholderText + '</div></div>' 

      });//commentsPanel

      // ----------------------------------------    	 

      var fetchComplianceData = function(mode, label) {

         XDMoD.TrackEvent('Compliance', 'Timeframe changed', label);
         
         btnTimeframeToggle.setText('<span style="color: #00f">' + label + '</span>');

         txtItemMonthDisplay.setText('');   tbSeparatorMonth.hide();
         txtItemQuarterDisplay.setText(''); tbSeparatorQuarter.hide();
         txtItemYearDisplay.setText('');    //tbSeparatorYear.hide();

         txtResourceCount.setText('');
         tbSeparatorResourceCount.hide();
         
         txtJobLevelCollectionStart.setText('');
         tbSeparatorCollectionStart.hide();
         txtResourceLevelCollectionStart.setText('');
      
         document.getElementById(cpRef).innerHTML = descriptionPlaceholderText;

         cGrid.getStore().load({
            params: {
               'timeframe_mode': mode.toLowerCase().replace(' ', '_')
            }
         });
          
      };//fetchComplianceData
 
      // ----------------------------------------  
            
      var btnTimeframeToggle = new Ext.Button({
      
         text: '<span style="color: #00f">M/Q/Y To Date</span>',
         menu: new Ext.menu.Menu({
            items: [
               {text: '<span style="color: #00f">Previous M/Q/Y</span>',  handler: function() { fetchComplianceData('Previous', 'Previous M/Q/Y'); } },
               {text: '<span style="color: #00f">M/Q/Y To Date</span>',   handler: function() { fetchComplianceData('To Date',  'M/Q/Y To Date'); } }
            ]
         })
               
      
      });//btnTimeframeToggle

      // ----------------------------------------  

      var viewPanel = new Ext.Panel({
      
            frame: false,
            layout: 'card',
            activeItem: 1, 
            region: 'center',
            
            items: [
            
               new Ext.Panel({
                  html : '<div class="x-grid-empty"><span id="' + emptyTextRef + '"></span></div>',
                  layout: 'fit'
               }),

               cGrid

            ] 

      });//viewPanel
      
      viewPanel.on('afterrender', function(pnl) {
      
         cGrid.getStore().load({
            params: {
               timeframe_mode: 'to_date'
            }
         });

      });

      // ----------------------------------------  
      
      Ext.apply(this, {
      
         title: 'Compliance',
 
         tbar: {
         
            items: [
            
               new Ext.Toolbar.TextItem({
                  text: 'Timeframe:'
               }),
            
               btnTimeframeToggle,
               
               '->',
               
               txtItemMonthDisplay, tbSeparatorMonth,
               txtItemQuarterDisplay, tbSeparatorQuarter,
               txtItemYearDisplay, tbSeparatorYear

               /*
               new Ext.Toolbar.TextItem({
                  html: generateLegend([
                     {label: 'Active', color: '#000' }
                     {label: 'Decommissioned In Timeframe', color: '#6e38f6' }
                  ])
               })
               */
                                             
            ]
            
         },
                  
         items: [

            viewPanel,
            commentsPanel
         
         ]
         
      });//Ext.apply
      
      XDMoD.Module.Compliance.superclass.initComponent.call(this);
   
   }//initComponent

});//XDMoD.Module.Compliance