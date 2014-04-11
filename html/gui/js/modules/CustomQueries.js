/*  
* JavaScript Document
* @author Amin Ghadersohi
* @date 2012-Apr
* 
*/

XDMoD.Module.CustomQueries = function (config) {

   XDMoD.Module.CustomQueries.superclass.constructor.call(this, config);

};//XDMoD.Module.CustomQueries

// ===========================================================================

Ext.apply(XDMoD.Module.CustomQueries, {

   setConfig: function(config, name) {

      var tabPanel = Ext.getCmp('main_tab_panel');

      tabPanel.setActiveTab('custom_query');

   }//setConfig
  
});//Ext.apply

// ===========================================================================

Ext.extend(XDMoD.Module.CustomQueries, XDMoD.PortalModule, {

   module_id: 'custom_query',

   usesToolbar: true,
   
   toolbarItems: {
    
      durationSelector: true,

      exportMenu: {
               
         enable: true,
   
         config: [
         
            XDMoD.ExportOption.PNG,
            XDMoD.ExportOption.PNG_SMALL,
            XDMoD.ExportOption.PNG_HD,
            XDMoD.ExportOption.PNG_POSTER,
            '-',
            XDMoD.ExportOption.SVG
            
         ]

      },//durationSelector
      
      printButton: true,
      reportCheckbox: true,
      
   },
   
   chartStore: false,

   // ------------------------------------------------------------------  

   mask: function(message) {

      var viewer = CCR.xdmod.ui.Viewer.getViewer();
      if (viewer.el) viewer.el.mask();

   },//mask

   unmask: function(message) {
   
      var viewer = CCR.xdmod.ui.Viewer.getViewer();
      if (viewer.el) viewer.el.unmask();
      
   },//unmask

   // ------------------------------------------------------------------  

   reloadChart: function() {
   
      this.chartStore.load();
      
   },//reloadChart

   // ------------------------------------------------------------------  

   initComponent: function() {
   
      var self = this;
      
      var chartScale = 1;
      var chartWidth = 757;
      var chartHeight = 400;

      // Interrogate various components for parameters to send to the chart controller

      var getBaseParams = function () {

         var baseParams = {};
         baseParams.title = titleField.getValue();
         baseParams.subtitle = subtitleField.getValue();
         baseParams.query = queryComboBox.getValue();
         baseParams.limit = numberField.getValue();
         baseParams.start_date =  self.getDurationSelector().getStartDate().format('Y-m-d');
         baseParams.end_date =  self.getDurationSelector().getEndDate().format('Y-m-d');
         baseParams.timeseries = 'n';
         baseParams.aggregation_unit = 'Auto';

         return baseParams;

      };//getBaseParams

      // ---------------------------------------------------------

      var chartStore = new CCR.xdmod.CustomJsonStore({

         storeId: 'hchart_store_' + this.id,
         autoDestroy: false,
         root: 'data',
         totalProperty: 'totalCount',
         successProperty: 'success',
         messageProperty: 'message',
         
         fields: [
            'chart',
            'credits',
            'title',
            'subtitle',
            'xAxis',
            'yAxis',
            'tooltip',
            'legend', 
            'series',
            'plotOptions',
            'credits',
            'dimensions',
            'metrics',
            'exporting',
            'reportGeneratorMeta'
         ],

         baseParams: {
            operation: 'get_data'
         },

         proxy: new Ext.data.HttpProxy({
            method: 'POST',
            url: 'controllers/custom_query.php'
         }),

         listeners: {
      
            'exception': function(dp, type, action, opt, response, arg) {

               if(response.success == false) {
               
                  Ext.MessageBox.alert("Error", response.message || 'Unknown Error');

                  if(response.message == 'Session Expired') {
                     CCR.xdmod.ui.actionLogout.defer(1000);
                  }
                  else {
                  
                     // Handle exceptions from the controller

                     Ext.MessageBox.alert("Error", response.message || 'Unknown Error');
                     var viewer = CCR.xdmod.ui.Viewer.getViewer();
                     if (viewer.el) viewer.el.unmask();
                     
                  }
                  
               }//if(response.success == false)
               
            }//exception
        
         }//listeners
      
      });//chartStore

      this.chartStore = chartStore;

      // ---------------------------------------------------------

      chartStore.on('beforeload', function() {
      
         if (!self.getDurationSelector().validate()) return;

         this.mask('Loading...');
         highChartPanel.un('resize', onResize, this);	

         chartStore.baseParams = {};
         Ext.apply(this.chartStore.baseParams, getBaseParams.call(this));

         maximizeScale.call(this);

         chartStore.baseParams.timeframe_label = self.getDurationSelector().getDurationLabel(),
         chartStore.baseParams.operation = 'get_data';
         chartStore.baseParams.scale = 1;
         chartStore.baseParams.format = 'hc_jsonstore';
         chartStore.baseParams.width = chartWidth*chartScale;
         chartStore.baseParams.height = chartHeight*chartScale;
         chartStore.baseParams.controller_module = self.getReportCheckbox().getModule();

      }, this);

      // ---------------------------------------------------------

      chartStore.on('load', function(chartStore) {
      
         this.firstChange = true;
         
         if (chartStore.getCount() != 1) {

            this.unmask();
            return;

         }

         var reportGeneratorMeta = chartStore.getAt(0).get('reportGeneratorMeta');

         var m = reportGeneratorMeta.chart_args.match(/&subtitle=(.*?)&/);
         
         if (m) reportGeneratorMeta.params_title = m[1];

         self.getReportCheckbox().storeChartArguments(reportGeneratorMeta.chart_args,
                                                reportGeneratorMeta.title,
                                                reportGeneratorMeta.params_title,
                                                reportGeneratorMeta.start_date,
                                                reportGeneratorMeta.end_date,
                                                reportGeneratorMeta.included_in_report);

         highChartPanel.on('resize', onResize, this);	//re-register this after loading/its unregistered beforeload
         this.unmask();

      }, this);

      // ---------------------------------------------------------

      self.on('print_clicked', function() {

         var parameters = chartStore.baseParams;
	     
         parameters['operation'] = 'get_data';
         parameters['scale'] = CCR.xdmod.ui.hd1280Scale;
         parameters['format'] = 'png';
         parameters['width'] = 757;
         parameters['height'] = 400;								
		    
         var params = '';
         
         for(i in parameters) {
			   params += i + '=' + parameters[i] +'&'
         }
         
         params = params.substring(0,params.length-1);
         
         Ext.ux.Printer.print({
         
            getXTypes: function () { return 'html';}, 
            html: '<img src="/controllers/custom_query.php?'+params+'" />'
				  
         });

      });//self.on('print_clicked', ...

      // ---------------------------------------------------------

      self.on('export_option_selected', function (opts){
         
         var parameters = chartStore.baseParams;

         Ext.apply(parameters, opts);

         CCR.invokePost("controllers/custom_query.php", parameters);

      });//self.on('export_option_selected', …

      // ---------------------------------------------------------
   
      self.on('duration_change', function(d) {
      
         // User needs to select a query before loading the chart
         if ( '' != queryComboBox.getValue() ) self.reloadChart();
             
      });//self.on('duration_change', …
 
      // ---------------------------------------------------------
   
      var highChartPanel = new CCR.xdmod.ui.HighChartPanel({
         id: 'hc-panel' + this.id,
         store: chartStore
      });
   
      // ---------------------------------------------------------
       
      var chartPanel = new Ext.Panel({
      
         region: 'center',
         layout: 'fit',
         header: false,
         
         tools: [],
   
         border: false,
         items: [highChartPanel]
   
      });//chartPanel
   
      // ---------------------------------------------------------
   
      var view = new Ext.Panel({
   
         region: 'center',
         margins: '2 2 2 0',
         border: false,
         items: [chartPanel]
         
      });//view
   
      // ---------------------------------------------------------
   
      var titleField = new Ext.form.TextField({
         fieldLabel: 'Title',
         name: 'title'
      });
   
      // ---------------------------------------------------------
   
      var subtitleField = new Ext.form.TextField({
         fieldLabel: 'Subtitle',
         name: 'subtitle'
      });
   
      // ---------------------------------------------------------
   
      var numberField = new Ext.ux.form.SpinnerField({
         fieldLabel: 'Slices',
         name: 'slices',
         value: 8
      });
   
      // ---------------------------------------------------------
   
      var queryComboBox = new Ext.form.ComboBox({
      
         autoSelect: false,
         emptyText: 'Select a query',
         fieldLabel: 'Query',
         name: 'query_type',
         xtype: 'combo',
         mode: 'local',
         editable: false,
         width: 275,
         disabled: false,
         valueField: 'id',
         displayField: 'text',
         triggerAction: 'all',
   
         // Add tooltips to long query names
         tpl: '<tpl for="."><div ext:qtip="{text}" class="x-combo-list-item">{text}</div></tpl>',
         
         store: new Ext.data.ArrayStore({
         
            id: 0,
           
            fields: [
               'id',
               'text'
            ],
   
            data: [
               ['q1','Research Funding Supported by XSEDE'],
               ['q2','Research Funding Directly Supported by XSEDE'],
               ['q3','Resources Delivered by Supporting Agency'],
               ['q4','NSF Research Funding Supported by XSEDE'],
               ['q5','NSF/MPS Research Funding Supported by XSEDE'],
               ['q6','NSF/MPS Award Counts'],
               ['q7','NSF Research Funding w/NSF values']
            ]
   
         }),//store
   
         listeners: {
         
            scope: this,
   
            'select': function(combo, record, index) {
   
               titleField.setValue(combo.getRawValue());
               this.reloadChart();
   
            }//select
   
         }//listeners
   
      });//queryComboBox
   
      // ---------------------------------------------------------
   
      var leftPanel = new Ext.FormPanel({
      
         split: true,
         bodyStyle: 'padding:5px 5px 0;',
         collapsible: true,
         header: true,
         title: 'Custom Query Options',
         autoScroll: true,
         width: 400,
         margins: '2 0 2 2',
         border: true,
         labelWidth: 80,
         region: 'west',
         // Show the title on the collapsed panel
         
         plugins: new Ext.ux.collapsedPanelTitlePlugin('Custom Query Options'),
   
         items: [
         
            {
            
               xtype: 'fieldset',
               title: 'Queries',
               autoHeight: true,
               layout: 'form',
               hideLabels: false,
               collapsible: true,
               
               defaults: {
                  anchor: '0'// '-20' // leave room for error icon
               },
               
               items: [
                  
                  {
                     fieldLabel: 'Query',
                     xtype: 'compositefield',
                     items: [
                        queryComboBox
                     ]
                  },
   
                  titleField,
                  subtitleField,
                  numberField
               ]
   
            }//fieldset
   
         ]
   
      });//leftPanel
       
      // ---------------------------------------------------------
   
      function maximizeScale() {
      
         var vWidth = highChartPanel.getWidth();
         var vHeight = highChartPanel.getHeight() - (chartPanel.tbar? chartPanel.tbar.getHeight() : 0);
   
         chartScale = ((vWidth / 757) + (vHeight / 400))/2;
   
         if (chartScale < CCR.xdmod.ui.minChartScale) {
         
            chartScale = CCR.xdmod.ui.minChartScale;
            
         }
         
         var aspect = vWidth/vHeight;
   
         if (aspect < 0.5) { //width is less than the height
   
            chartWidth = highChartPanel.getWidth()/chartScale;
            chartHeight = (highChartPanel.getWidth()/0.5)/chartScale;
   
         }
         else if (aspect > 4) { //width is more than 4 times of the height
   
            chartWidth = highChartPanel.getWidth()/chartScale;
            chartHeight = (highChartPanel.getWidth()/4)/chartScale;
   
         }
         else {
   
            chartWidth = highChartPanel.getWidth()/chartScale;
            chartHeight = highChartPanel.getHeight()/chartScale;
   
         }
   
      }//maximizeScale
   
      // ---------------------------------------------------------
   
      function onResize(t, adjWidth, adjHeight) {
      
         maximizeScale.call(this);
         highChartPanel.setSize(adjWidth, adjHeight);
         
      }//onResize
       
      view.on('resize', onResize, this);
   
      // ---------------------------------------------------------
   
      Ext.apply(this, {
         
         items: [leftPanel, view]
         
      });
   
      XDMoD.Module.CustomQueries.superclass.initComponent.apply(this, arguments);

   }//initComponent
   
});//XDMoD.Module.CustomQueries