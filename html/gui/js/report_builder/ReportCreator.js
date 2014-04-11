Ext.namespace('XDMoD');

// ==================================================

XDMoD.ReportCreator = Ext.extend(Ext.Panel,  {
                       
   initComponent: function(){
      
      this.expandGeneralInfo = true;
      
      this.needsSave = false;

      var self = this;

      // Public methods ===============================
          
      self.initializeFields = function(report_name) {
         
         txtReportName.setValue(report_name);
         txtReportTitle.setValue('');
         txtReportHeader.setValue('');
         txtReportFooter.setValue('');
         
         cmbFont.setValue('Arial');
         cmbFormat.setValue('Pdf');
         cmbSchedule.setValue('Once');
         cmbDelivery.setValue('E-mail');
         
         self.expandGeneralInfo = true;      
         self.reportCharts.initGridFunctions();
   
      };//self.initializeFields
      
      self.initReportGrid = function() {
      
         self.expandGeneralInfo = true;
         self.reportCharts.initGridFunctions();
         
      };//self.initReportGrid
    
      self.setReportName = function(value) {  
         txtReportName.setValue(value);
      };//self.setReportName
   
      self.setReportTitle = function(value) {
         txtReportTitle.setValue(value);
      };//self.setReportTitle
   
      self.setReportHeader = function(value) {
         txtReportHeader.setValue(value);
      };//self.setReportHeader
      
      self.setReportFooter = function(value) {
         txtReportFooter.setValue(value);
      };//self.setReportFooter   
      
      self.setReportFont = function(value) {
         cmbFont.setValue(value);
      };//self.setReportFont
         
      self.setReportFormat = function(value) {
         cmbFormat.setValue(value);
      };//self.setReportFormat
      
      self.setReportSchedule = function(value) {
         cmbSchedule.setValue(value);
      };//self.setReportSchedule
      
      self.setReportDelivery = function(value) {
         cmbDelivery.setValue(value);
      };//self.setReportDelivery
             
      self.setReportID = function(id) {
         self.report_id = id;
      };//self.setReportID
      
      self.getReportID = function() {
         return self.report_id;
      };//self.getReportID

      // ==============================================  
   
      var inputwidth = 250; 

      var thumbnailChartLayoutPreview = 'gui/images/report_generator/report_layout_1_up.png';
      
      var layoutThumbnailId = Ext.id();
      
      //----------------------------------------------
      
      this.setChartsPerPage = function(value) {
      
         // Need to temporarily disable the change event handler for the chart layout radio group's 'change' event,
         // since a report that was just loaded shouldn't appear as "dirty"
         
         rdoChartLayout.removeListener('change', rdoChartLayout.changeEventHandler);
         
         rdoChartLayout.setValue(value  + '_up');
         
         thumbnailChartLayoutPreview = 'gui/images/report_generator/report_layout_' + value + '_up.png';
 
         var containerChartLayoutPreview = document.getElementById(layoutThumbnailId); 
         
         if (containerChartLayoutPreview)
            containerChartLayoutPreview.src = thumbnailChartLayoutPreview;
         
         (function() {
            rdoChartLayout.on('change', rdoChartLayout.changeEventHandler);
         }).defer(400);

      };//setChartsPerPage
   
      this.on('activate', function() {
         document.getElementById(layoutThumbnailId).src = thumbnailChartLayoutPreview;
      }, this, {single: true});
      
      //----------------------------------------------

      this.dirtyConfig = function(field, nv, ov) {
               
         CCR.xdmod.reporting.dirtyState = true;
            
         self.needsSave = true;

         btnSaveReport.setDisabled(false);
         btnSaveReportAs.setDisabled(false);
   
      };//dirtyConfig

      //----------------------------------------------
            
      this.isDirty = function() {
      
         return self.needsSave;
         
      };

      //----------------------------------------------
         
      this.reportCharts = new XDMoD.ReportCreatorGrid ({
      
         region: 'center',
         title: 'Included Charts',
         parentRef: self,
         width: '35%'
         
      });
      
      this.on('activate', function() {
         if (self.reportCharts.reportStore.data.length == 0)
            (function(){ self.reportCharts.reportStore.removeAll(); }).defer(100);
      });
      
      //----------------------------------------------

      var font_store = new Ext.data.SimpleStore({
         fields : ['name'],
         data :[ ['Arial'] ]
      });
      
      //----------------------------------------------

      var cmbFont = new Ext.form.ComboBox({

         editable: false,
         width: 140,
         fieldLabel: 'Font',
         mode: 'local',
         store: font_store,
         triggerAction: 'all',
         displayField: 'name',
         hidden: true,
         valueField: 'name',
         emptyText: 'No Font Selected',
         listeners: {
            change : self.dirtyConfig
         }

      });//cmbFont
      
      cmbFont.setValue(1);
      
      //----------------------------------------------

      var format_store = new Ext.data.SimpleStore({
         fields : ['name', 'format'],
         data :[ ['PDF', 'Pdf'], ['Word Document', 'Doc'] ]
      });

      //----------------------------------------------
      
      var cmbFormat = new Ext.form.ComboBox({

         editable: false,
         width: 140,
         fieldLabel: 'Delivery Format',
         mode: 'local',
         store: format_store,
         triggerAction: 'all',
         displayField: 'name',
         valueField: 'format',
         emptyText: 'No Format Selected',
         
         listeners: {
         
            change : function(cb) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated delivery format', cb.getRawValue());
               self.dirtyConfig();
            
            }//change
            
         }//listeners

      });//cmbFormat
            
      cmbFormat.setValue(0);

      //----------------------------------------------

      var schedule_store = new Ext.data.SimpleStore({
         fields : ['schedule'],
         data : [ 
            ['Once'], ['Daily'], ['Weekly'], ['Monthly'],
            ['Quarterly'], ['Semi-annually'], ['Annually']
         ]
      });

      //----------------------------------------------

      var cmbSchedule = new Ext.form.ComboBox({

         editable: false,
         width: 140,
         fieldLabel: 'Schedule',
         mode: 'local',
         store: schedule_store,
         triggerAction: 'all',
         displayField: 'schedule',
         valueField: 'schedule',
         emptyText: 'No Schedule Selected',
         
         listeners: {
          
            /*
            select:function(combo, record, index) {
            
               cmbFormat.setDisabled(record.data.schedule == 'Once');
            
            },
            */
            
            change : function(cb) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated schedule', cb.getValue());
               self.dirtyConfig();
            
            }
            
         }//listeners

      });//cmbSchedule
      
      cmbSchedule.setValue(0);
      
      //----------------------------------------------
      
      var delivery_store =  new Ext.data.SimpleStore({
      
         fields : ['method'],
         data :[['E-mail' ]]
         
      });

      //----------------------------------------------

      var cmbDelivery = new Ext.form.ComboBox({

         editable: false,
         width: 140,
         fieldLabel: 'Delivery',
         mode: 'local',
         store: delivery_store,
         triggerAction: 'all',
         displayField: 'method',
         valueField: 'method',
         emptyText: 'No Format Selected',
         hidden: true,
         listeners: {
         
            change : function(cb) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated delivery type', cb.getValue());
               self.dirtyConfig();
            
            }//change
            
         }//listeners

      });//cmbDelivery

      var lblDelivery = new Ext.form.Label ({
         html: '<div style="font-size: 12px; padding-top: 5px">Delivery Method: <b>E-Mail</b><br /><br /></div>'
      });
            
      cmbDelivery.setValue(0);

      //----------------------------------------------

      var isSupplied = function(field) {

         return (Ext.util.Format.trim(field.getValue()).length > 0);
            
      }//isSupplied

      //----------------------------------------------

      var flushReloadReportCharts = function(report_id){

         var objParams = {
            operation: 'fetch_report_data',
            flush_cache: true,
            selected_report: report_id
         };
         
         var conn = new Ext.data.Connection;
         
         conn.request({
         
            url: 'controllers/report_builder.php', 
            params: objParams, 
            method: 'POST',
            
            callback: function(options, success, response) {
                        
               if (success) { 

                  var reportData = Ext.decode(response.responseText);

                  self.reportCharts.reportStore.loadData(reportData.results);
                  
               }
               else 
                  Ext.MessageBox.alert('Report Pool', 'There was a problem trying to prepare the report editor');
                  
            }//callback
            
         });//conn.request
			
      }//flushReloadReportCharts
      
      //----------------------------------------------
                  
      var saveReport = function (save_callback, override_config) {
 
         if (override_config == undefined) override_config = {};
         
         // The only required textual component to a report is the filename.  All other textual inputs are optional.
         
         if (override_config.generateCopy == undefined) {
            if (!isSupplied(txtReportName)) {
               CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'You must specify a name for this report.');
               return;
            }
         }

			var fieldsToValidate = [txtReportTitle, txtReportHeader, txtReportFooter];
			
			if (override_config.generateCopy == undefined)
			   fieldsToValidate.unshift(txtReportName);

			// Sanitization --------------------------------------------
			
			for (i = 0; i < fieldsToValidate.length; i++) {
			   
			   if ( isSupplied(fieldsToValidate[i]) && (fieldsToValidate[i].validate() == false) ) {

                  if (override_config.callback != undefined) override_config.callback(false, fieldsToValidate[i].formatMessage);
   		   		CCR.xdmod.ui.reportGeneratorMessage('Report Editor', fieldsToValidate[i].formatMessage, false);
   		   						
   		   		return;
			   
			   }//if (isSupplied...

			}//for
				
         /*
         if (!isSupplied('report_title')) {
            Ext.MessageBox.alert('Report Editor', 'You must specify a title for this report.');
            return;
         }
         */
                         
         if (self.reportCharts.reportStore.data.length == 0) {
            if (override_config.callback != undefined) override_config.callback(false, 'Report needs charts');
            CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'You must have at least one chart in this report.');
            return;
         }

         // ========================================         

         var reportData = {};
         
         reportData['operation'] = 'save_report';
         
         reportData['phase'] = (self.getReportID().length > 0) ? 'update' : 'create';
         
         reportData['report_id'] = self.getReportID();
                              
         reportData['report_name'] =     txtReportName.getValue(); 
         reportData['report_title'] =    txtReportTitle.getValue();  
         reportData['report_header'] =   txtReportHeader.getValue();  
         reportData['report_footer'] =   txtReportFooter.getRawValue();  
         
         reportData['charts_per_page'] = rdoChartLayout.getValue().charts_per_page;  
         
         reportData['report_font'] =     cmbFont.getRawValue();
         reportData['report_format'] =   cmbFormat.getValue();
         reportData['report_schedule'] = cmbSchedule.getRawValue();
         reportData['report_delivery'] = cmbDelivery.getRawValue();

         if (override_config.generateCopy != undefined) {
            reportData['phase'] = 'create';
            reportData['report_id'] = '';
            reportData['report_name'] = override_config.generateCopy;
         }
                     
         var chartCount = 1;
         
         // Iteration occurs such that the the store is traversed top-down (store order complies with grid ordering)
         
         self.reportCharts.reportStore.data.each(function() {
         
            var chartData = new Array();
            
            if(this.data['thumbnail_link'].indexOf("type=cached") != -1) {

               var tf_start = XDMoD.Reporting.getParamIn('start', this.data['thumbnail_link'], '&');
               var tf_end = XDMoD.Reporting.getParamIn('end', this.data['thumbnail_link'], '&');
               var cache_ref = XDMoD.Reporting.getParamIn('ref', this.data['thumbnail_link'], '&');
            
               //console.log(this.data['thumbnail_link']);
               
               //When the report chart in question has had its timeframe updated, then saved, the backend needs
               //to know where the blob was stored so it can transfer it accordingly.
               
               reportData['chart_cacheref_' + chartCount] = tf_start + ';' + tf_end + ';' + cache_ref;
               
            }
 
            if(this.data['thumbnail_link'].indexOf("type=volatile") != -1) {

               var tf_start = this.data['chart_date_description'].split(' to ')[0];
               var tf_end = this.data['chart_date_description'].split(' to ')[1];
                
               var cache_ref = XDMoD.Reporting.getParamIn('ref', this.data['thumbnail_link'], '&');
            
               var duplicate_id = (this.data['duplicate_id']) ? this.data['duplicate_id'] : '';
               
               reportData['chart_cacheref_' + chartCount] = tf_start + ';' + tf_end + ';' + 'xd_report_volatile_' + cache_ref + duplicate_id;
               
            }
         
            chartData.push(this.data['chart_id'].replace(/;/g, '%3B')); //Encode semicolon for active_role value
            chartData.push(this.data['chart_title'].replace(/;/g, '%3B'));
            chartData.push(this.data['chart_drill_details'].replace(/;/g, '%3B'));
            chartData.push(this.data['chart_date_description']);
            chartData.push(this.data['timeframe_type']);
            chartData.push(this.data['type']);

            reportData['chart_data_' + chartCount++] = chartData.join(';');
         
         });
         
         // ========================================
         
         var conn = new Ext.data.Connection;
         
         conn.request({
         
            url: 'controllers/report_builder.php', 
            params: reportData, 
            //successProperty: 'success',
            method: 'POST',
            
            callback: function(options, success, response) {
               
               if (success) {
               
                  var responseData = Ext.decode(response.responseText); 
                  
                  if (responseData.success) {
                     
                     self.parent.reportsOverview.reportStore.reload();
                        
                     if (override_config.generateCopy == undefined) {
                     
                        btnSaveReport.setDisabled(true);
                        self.needsSave = false;
                        CCR.xdmod.reporting.dirtyState = false;
                     
                        self.setReportID(responseData.report_id);
   
                        // This reload triggers (server-side) cache cleanup 
                        flushReloadReportCharts(responseData.report_id);
   
                        var action = responseData.phase.slice(0,1).toUpperCase() + responseData.phase.slice(1) + 'd';
                 
                        XDMoD.TrackEvent('Report Generator (Report Editor)', 'Report ' + action + ' successfully', reportData['report_name']);
                        
                        CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'Report ' + action + ' Successfully', true, function() {
                  
                           if (save_callback)
                              save_callback();
                           
                        });
                     
                     }
                     else {
                     
                        XDMoD.TrackEvent('Report Generator (Report Editor)', 'Report successfully saved as a copy', reportData['report_name']);
                        
                        if (override_config.callback != undefined) override_config.callback(true, 'Report saved successfully');
                        CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'Report successfully saved as a copy', true);
 
                     }
                     
                  }
                  else {
                  
                     
                     if (override_config.callback != undefined) override_config.callback(false, 'Unable to save report');
                     CCR.xdmod.ui.reportGeneratorMessage('Report Editor', responseData.message);
                     
                  }
                  
               }
               else {
               
                  if (override_config.callback != undefined) override_config.callback(false, 'Problem saving report');
                  CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'There was a problem creating / updating this report');
                  
               }
                  
            }//callback
            
         });//conn.request
                             
      };//saveReport

      //----------------------------------------------
      
      var saveReportAs = function(el) {
      
         var saveAsDialog = new XDMoD.SaveReportAsDialog({
         
            executeHandler: function(report_filename, callback) {
            
               saveReport(undefined, {
                  generateCopy: report_filename,
                  callback: callback
               });
               
            }
         
         });//saveAsDialog
      
         saveAsDialog.present(el, txtReportName.getValue()); 
      
      }//saveReportAs
      
      //----------------------------------------------
           
      var previewReport = function () {
      
         if (self.reportCharts.reportStore.data.length == 0) {
            CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'You must have at least one chart in this report.');
            return;
         }
         
         var reportData = {};
         
         if (self.isDirty() == true) {
                 
            // Generate data for preview on the client-side
            
            // ========================================         
            
            reportData['report_id'] =       self.getReportID(); 
            reportData['success'] =         true;
            reportData['charts'] =          new Array();
            
            // Iteration occurs such that the the store is traversed top-down (store order complies with grid ordering)
            
            var chartCount = 0;
            var date_utils = new DateUtilities();
             
            var chartData = {}; 
              
            self.reportCharts.reportStore.data.each(function() {
               
               var chart_page_position = chartCount % rdoChartLayout.getValue().charts_per_page;
                             
               if (chart_page_position == 0) {
               
                  chartData = {}; 
                  chartData['report_title']  = (chartCount == 0) ? '<span style="font-family: arial; font-size: 22px">' + Ext.util.Format.trim(txtReportTitle.getValue()) + '</span><br />' : '';
                  chartData['header_text']  = '<span style="font-family: arial; font-size: 12px">' + Ext.util.Format.trim(txtReportHeader.getValue()) + '</span>';
                  chartData['footer_text']  = '<span style="font-family: arial; font-size: 12px">' + Ext.util.Format.trim(txtReportFooter.getRawValue()) + '</span>';
                  
               }
            
               chartData['chart_title_'  + chart_page_position]   =   '<span style="font-family: arial; font-size: 16px">' + this.data['chart_title'] + '</span>';
               
               if (this.data['chart_drill_details'].length == 0) this.data['chart_drill_details'] = CCR.xdmod.org_abbrev;
               
               chartData['chart_drill_details_' + chart_page_position]  =   '<span style="font-family: arial; font-size: 12px">' + this.data['chart_drill_details'] + '</span>';
                
               /*
               if (this.data['chart_id'].indexOf('format=hc_jsonstore') != -1) {

                  // Charts introduced via the 'Usage Explorer' (HighChart Format) ...
                  
                  chartData['chart_id_' + chart_page_position] = '/controllers/usage_explorer.php?' +  this.data['chart_id'] + 
                                                                 '&scale=1&format=png&show_guide_lines=y&show_title=n&show_gradient=n';                                          
               
               }
               else {
               
                  // Charts introduced via the 'Usage' tab...
                
                  chartData['chart_id_' + chart_page_position] = '/rest/datawarehouse/explorer/plot/dimension=group_by/' +  this.data['chart_id'].replace(/&/g, '/') + 
                                                                 '/scale=2/show_guide_lines=y/show_title=n/show_gradient=n/format=png_inline?token=' + XDMoD.REST.token;     
         
               }
               */
               
               var s_date, e_date;
                  
               if (this.data['timeframe_type'].toLowerCase() == 'user defined') {
               
                  s_date = this.data['chart_date_description'].split(' to ')[0];
                  e_date = this.data['chart_date_description'].split(' to ')[1];
               
               }
               else {
                  
                  var endpoints = date_utils.getEndpoints(this.data['timeframe_type']); 
                  
                  s_date = endpoints.start_date;
                  e_date = endpoints.end_date;
               
               }
               
               // Overwrite chart_id and chart_date_description as necessary
               
               this.data['chart_date_description'] = s_date + ' to ' + e_date;
                        
               //chartData['chart_id_' + chart_page_position] = chartData['chart_id_' + chart_page_position].replace(/start_date=([\d\-]+)/g, 'start_date=' + s_date);
               //chartData['chart_id_' + chart_page_position] = chartData['chart_id_' + chart_page_position].replace(/end_date=([\d\-]+)/g, 'end_date=' + e_date);
               
               // Strip embedded title from chart (don't need repeated titles in cached report preview)
               //chartData['chart_id_' + chart_page_position] = chartData['chart_id_' + chart_page_position].replace(/&title=(.+)&scale/g, '&title=&scale');
                              
               chartData['chart_timeframe_' + chart_page_position]  =   '<span style="font-family: arial; font-size: 14px">' + this.data['chart_date_description']+ '</span>';
                   
               chartData['chart_id_' + chart_page_position] = this.data['thumbnail_link'];
               
               chartCount++;
                          
               if (chartCount % rdoChartLayout.getValue().charts_per_page == 0)
                  reportData['charts'].push(chartData);
               
            });
            
            // ===============================================
            
            var remaining_slots = chartCount % rdoChartLayout.getValue().charts_per_page;
            
            if (remaining_slots != 0) {
         
               // Pad the remaining slots
               
               for (i = remaining_slots; i < rdoChartLayout.getValue().charts_per_page; i++) {
               
                  chartData['chart_title_'  + i] = '';
                  chartData['chart_id_' + i] = 'img_placeholder.php?';
                  chartData['chart_timeframe_' + i] = '';
                  chartData['chart_drill_details_' + i] = '';

               }//for(...)
            
               reportData['charts'].push(chartData);
            
            }//if (remaining_slots != 0)
            
            // ========================================
            
            self.parent.reportPreview.initPreview(txtReportName.getValue(), self.getReportID(), XDMoD.REST.token, 1, reportData, rdoChartLayout.getValue().charts_per_page);
            
            
         }//if (self.isDirty() == true)
         else {
            self.parent.reportPreview.initPreview(txtReportName.getValue(), self.getReportID(), XDMoD.REST.token, 1, undefined, rdoChartLayout.getValue().charts_per_page);
         }
      
         self.parent.switchView(2); 
      
      }//previewReport
      
      //----------------------------------------------
      
      var rdoChartLayoutGroupID = Ext.id() + '-chart-layout-group';
      
      var rdoChartLayout = new Ext.form.RadioGroup({
      //var rdoChartLayout = {
         
         //xtype: 'radiogroup',
         defaultType: 'radio',
         columns: 1,
         margins: '23 0 0 0',
         cls: 'custom_search_mode_group',
         //width: 90,
         flex: 1,
         vertical: true,

         items: [
            
            {
               boxLabel: '1 Chart Per Page',
               checked: true,
               name: rdoChartLayoutGroupID,
               inputValue: '1_up',
               charts_per_page: 1
            }, 
            
            {
               boxLabel: '2 Charts Per Page',
               name: rdoChartLayoutGroupID,
               inputValue: '2_up',
               charts_per_page: 2
            }
               
         ],
            
         changeEventHandler: function(rg, rc) {

            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated layout for report', rc.charts_per_page + ' chart(s) per page');

            thumbnailChartLayoutPreview = 'gui/images/report_generator/report_layout_' + rc.inputValue + '.png';
            document.getElementById(layoutThumbnailId).src = thumbnailChartLayoutPreview;
            
            self.dirtyConfig();
         
         }//changeEventHandler
            
      });//rdoChartLayout
      
      rdoChartLayout.on('change', rdoChartLayout.changeEventHandler);
            
      //----------------------------------------------    
            
      var reportConfigBox = {marginLeft: '4px', marginTop: '7px'};

      var txtReportName = new XDMoD.LimitedField({

         fieldLabel: 'File Name',   
         characterLimit: 50,
         emptyText: '1 min, 50 max (required)',
         formatMessage: 'The filename must be at least 1 character long, and no longer than 50 characters long',
         vpattern: '^.{1,50}$',
         listeners: {
            change : function(t) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated file name for report', t.getValue());
               self.dirtyConfig();
               
            }
         }
                     
      });//txtReportName

      var txtReportTitle = new XDMoD.LimitedField({

         fieldLabel: 'Report Title',   
         characterLimit: 50,
         emptyText: '1 min, 50 max (optional)',
         formatMessage: 'The report title, if specified, must be no longer than 50 characters long',		
         vpattern: '^.{1,50}$',
         listeners: {
            change : function(t) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated title for report', t.getValue());
               self.dirtyConfig();
               
            }
         }
                     
      });//txtReportTitle

      var txtReportHeader = new XDMoD.LimitedField({

         fieldLabel: 'Header Text',   
         characterLimit: 40,
         emptyText: '1 min, 40 max (optional)',
         formatMessage: 'The header text, if specified, must be no longer than 50 characters long',		
         vpattern: '^.{1,40}$',
         listeners: {
            change : function(t) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated header text for report', t.getValue());
               self.dirtyConfig();
               
            }
         }
                     
      });//txtReportHeader

      var txtReportFooter = new XDMoD.LimitedField({

         fieldLabel: 'Footer Text',   
         characterLimit: 40,
         emptyText: '1 min, 40 max (optional)',
         formatMessage: 'The footer text, if specified, must be no longer than 50 characters long',		
         vpattern: '^.{1,40}$',
         listeners: {
            change : function(t) {
            
               XDMoD.TrackEvent('Report Generator (Report Editor)', 'Updated footer text for report', t.getValue());
               self.dirtyConfig();
               
            }
         }
                     
      });//txtReportFooter
                  
      //----------------------------------------------    
                  
      this.reportInfo = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'General Information',

         //bodyStyle:'padding:5px 5px 0',
 
         width: 220,
         
         defaults: {width: 200},
         labelAlign: 'top',
         //cls: 'user_profile_section_general',
         defaultType: 'textfield',
         style: reportConfigBox,

         items: [

            txtReportName,
            txtReportTitle,
            txtReportHeader,
            txtReportFooter
                        
         ]

      });//reportInfo

      //----------------------------------------------
               
      this.sectionChartLayout = new Ext.Panel({
            
         height: 120,
         width: 220,
         //border: true,
         frame: true,
               
         title: 'Chart Layout',
         region: 'center',
         cls: 'report_generator_chart_layout',
         
         style: reportConfigBox,
         
         layout: {
            type: 'hbox',
            pack: 'start',
            align: 'stretch'
         },
               
         items: [
            rdoChartLayout,
            {html:'<img id="' + layoutThumbnailId + '" src="' + thumbnailChartLayoutPreview + '">', width:70}
         ]
            
      });//sectionChartLayout
      
      //----------------------------------------------          

      this.scheduleOptions = new Ext.FormPanel({

         labelWidth: 95,
         frame:true,
         title: 'Scheduling',
         bodyStyle:'padding:5px 5px 0',
         width: 220,
 
         style: reportConfigBox,
 
         defaults: {width: 200},
         labelAlign: 'top',
         cls: 'user_profile_section_general',
         defaultType: 'textfield',

         items: [
            
            cmbFont,
            cmbFormat,
            cmbSchedule,
            
            cmbDelivery,
            
            cmbFormat,
            
            lblDelivery
                        
         ]

      });//scheduleOptions

      //----------------------------------------------    
                  
      this.reportOptions = new Ext.Panel({
      
         region: 'west',
         baseCls: 'x-plain',
         width: 243,
         //defaults: {margins: '15px 0 0 0'},
         autoScroll: true,
         
         items: [
            this.reportInfo,
            this.sectionChartLayout,
            this.scheduleOptions
         ]
         
      });//reportOptions
      
      //----------------------------------------------
      
      var toggleGeneralInfo = function() {
      
         self.reportInfo.toggleCollapse(false);
         
      }//toggleGeneralInfo
      
      //----------------------------------------------
      
		var sendReport = function(build_only, format) {
		    
         // If build_only is set (and set to true), then the report will be built and not e-mailed
		
         var action = build_only ? 'download' : 'send';
         
         if (self.getReportID().length == 0) {
            CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'You must save this report before you can ' + action + ' it.');
            return;
         }
		    
         if (self.isDirty() == true) {
            CCR.xdmod.ui.reportGeneratorMessage('Report Editor', 'You have made changes to this report which you must save before ' + action  + 'ing.');
            return;
         }
         
         var report_name = txtReportName.getValue();
		 
         self.parent.buildReport(report_name, self.getReportID(), self, build_only, format);
         
		}//sendReport
		
      //----------------------------------------------
            
      var returnToOverview = function() {
      
         if (self.isDirty() == true) {
			
			   XDMoD.TrackEvent('Report Generator (Report Editor)', 'Presented with Unsaved Changes dialog');
			   
            Ext.Msg.show({
            
               maxWidth: 800,
               minWidth: 400,
               
               title: 'Unsaved Changes',
               msg: "There are unsaved changes.<br />Do you wish to save this report before closing the Report Editor?<br /><br />If you press <b>No</b>, you will lose all your changes.",
               buttons: Ext.MessageBox.YESNOCANCEL,
               
               fn: function(resp) {
                  
                  if (resp == 'cancel') {
                  
                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'User cancelled Unsaved Changes dialog');
                     return;
                  
                  }
                  
                  if (resp == 'yes') {
                  
                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'User chose to save changes via Unsaved Changes dialog');
                     saveReport(function() { self.parent.switchView(0); });
                     
                  }
                     
                  if (resp == 'no') {
                  
                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'User chose to not save changes via Unsaved Changes dialog');
                  
                     btnSaveReport.setDisabled(true);
                     self.needsSave = false;
                     self.parent.switchView(0);
                  
                  }

               },
               
               icon: Ext.MessageBox.QUESTION
               
            });//Ext.Msg.show
            
         }//if (self.isDirty() == true) 
         else {
            self.parent.switchView(0);
         }
         
      }//returnToOverview
      
      //----------------------------------------------

      this.on('activate', function(p) {
 
         // Make sure that the 'General Information' panel is visible (by default) when the Report Editor becomes active
         // (we don't want this happening during report previewing, however)
              
         if (p.expandGeneralInfo == true)
            p.reportInfo.expand();

         p.expandGeneralInfo = false;
         
      });
      
      var btnSaveReport = new Ext.Button({
      
         iconCls: 'btn_save',
         text: 'Save',
         disabled: true,
         
         handler: function(){  
         
            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on the Save button');
            saveReport(); 
         
         }
         
      });//btnSaveReport

      var btnSaveReportAs = new Ext.Button({
      
         iconCls: 'btn_save',
         text: 'Save As',
         tooltip: 'Create and save a copy of this report.',
         disabled: true,
         
         handler: function(){  
         
            XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on the Save As button');
            saveReportAs(this); 
         
         }
         
      });//btnSaveReportAs
            
      this.allowSaveAs = function(b) {

         btnSaveReportAs.setDisabled(!b);
         
      };
      
      Ext.apply(this, {
      
         title: 'Report Editor', 
         
         layout: 'border',
         
         cls: 'report_edit',
         
         items : [ this.reportOptions, this.reportCharts ],
         
         plugins: [new Ext.ux.plugins.ContainerMask ({ masked:false })],
         
         tbar: {
      
            items: [
            
               /*
               { xtype: 'button',   id: 'btn_creator_save_reporta',          iconCls: 'btn_general_info',       text: 'General Info',
                   handler: toggleGeneralInfo
               },
               */
               
               btnSaveReport,
               btnSaveReportAs,
                   
               { 
                 xtype: 'button',   
                 iconCls: 'btn_preview',            
                 text: 'Preview',
                 tooltip: 'See a visual representation of the selected report.',
                 handler: previewReport
               },  

               new XDMoD.Reporting.ReportExportMenu({
                  instance_module: 'Report Editor',
                  sendMode: true,
                  exportItemHandler: sendReport
               }),
                                
               new XDMoD.Reporting.ReportExportMenu({
                  instance_module: 'Report Editor',
                  exportItemHandler: sendReport
               }),
               
               '->',               
               
               { 
               
                 xtype: 'button',   
                 iconCls: 'btn_return_to_overview', 
                 text: 'Return To <b>My Reports</b>',
                 
                 handler: function() {
                     
                     XDMoD.TrackEvent('Report Generator (Report Editor)', 'Clicked on Return To My Reports');
                     returnToOverview();
                     
                 }
                  
               }
            
            ]
         
         }//tbar

      });//Ext.apply
      
      XDMoD.ReportCreator.superclass.initComponent.call(this);
      
	}//initComponent
   
});//XDMoD.ReportCreator
