/*
 * JavaScript Document
 * @author Ryan Gentner
 * @date 2013-June-03
 *
 * Report Generator Module
 */
 
Ext.namespace('XDMoD');
   
XDMoD.Module.ReportGenerator = Ext.extend(XDMoD.PortalModule,  {

   module_id: 'report_generator',
   
   // Public reference to the store associated with the XDMoD.AvailableCharts instance
   // in this class (so that any chart add/remove operation callbacks handled elsewhere in the
   // portal can directly reload the store as-needed.)
   
   chartPoolStore: null,
   
   initComponent: function(){
       
      var self = this;
      
      // ----------------------------------------

      var reportManager = new XDMoD.ReportManager({
      
         region: 'center'

      });//reportManager
 
      var chartPool = new XDMoD.AvailableCharts({
      
         region: 'east',
         split: true,
         width: 460,
         minSize: 460, 
         maxSize:460
         
      });//chartPool
            
      this.chartPoolStore = chartPool.reportStore;
      
      // ----------------------------------------

      Ext.apply(this, {
      
         title: 'Report Generator',
         
         items:[
            reportManager,
            chartPool
         ]
            
      });//Ext.apply
      
      XDMoD.Module.ReportGenerator.superclass.initComponent.call(this);
   
   }//initComponent

});//XDMoD.Module.ReportGenerator
