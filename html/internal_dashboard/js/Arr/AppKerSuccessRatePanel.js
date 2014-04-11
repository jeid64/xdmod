/**
 * ARR active tasks grid.
 *
 * @author Nikolay A. Simakov <nikolays@ccr.buffalo.edu>
 */


Ext.namespace('XDMoD', 'XDMoD.Arr','CCR', 'CCR.xdmod', 'CCR.xdmod.ui');
Ext.QuickTips.init();  // enable tooltips


XDMoD.Arr.AppKerSuccessRatePanel = function (config)
{
   XDMoD.Arr.AppKerSuccessRatePanel.superclass.constructor.call(this, config);
}; 

Ext.apply(XDMoD.Arr.AppKerSuccessRatePanel,
{
    //static stuff
   
});

Ext.extend(XDMoD.Arr.AppKerSuccessRatePanel, Ext.Panel, {
   title: 'App Kernel Success Rates Table',
   resourcesList:[ "blacklight",
                   "edge",
                   "edge12core",
                   "lonestar4",
                   "kraken"
                  /*"alamo",
   "blacklight",
   "edge",
   "edge12core",
   "edgegpu",
   "gordon",
   "hotel",
   "india",
   "keeneland",
   "kraken",
   "lonestar4",
   "ranger",
   "sierra",
   "trestles",
   "xray"*/],
   problemSizeList:[1,2,4,8,16,32,64,128],
   appKerList:["xdmod.app.chem.gamess.node",
               "xdmod.app.chem.nwchem.node",
               "xdmod.app.md.namd.node",
               "xdmod.benchmark.hpcc.node",
               "xdmod.benchmark.io.ior.node",
               "xdmod.benchmark.io.mpi-tile-io.node",
               "xdmod.benchmark.mpi.imb",
               "xdmod.app.chem.gamess",
               "xdmod.app.chem.nwchem",
               "xdmod.app.md.namd",
               "xdmod.benchmark.hpcc",
               "xdmod.benchmark.io.ior",
               "xdmod.benchmark.io.mpi-tile-io",
               
      /*"xdmod.app.chem.gamess",
      "xdmod.app.chem.nwchem",
      "xdmod.app.climate.cesm",
      "xdmod.app.climate.wrf",
      "xdmod.app.md.amber",
      "xdmod.app.md.charmm",
      "xdmod.app.md.cpmd",
      "xdmod.app.md.lammps",
      "xdmod.app.md.namd",
      "xdmod.app.md.namd-gpu",
      "xdmod.app.phys.quantum_espresso",
      "xdmod.benchmark.gpu.shoc",
      "xdmod.benchmark.graph.graph500",
      "xdmod.benchmark.hpcc",
      "xdmod.benchmark.io.ior",
      "xdmod.benchmark.io.mpi-tile-io",
      "xdmod.benchmark.mpi.imb",
      "xdmod.benchmark.mpi.omb",
      "xdmod.benchmark.npb",
      "xdmod.benchmark.osjitter",*/

   ],
   getSelectedResources: function()
   {
      var resources = [];
      var selNodes = this.resourcesTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         resources.push(node.text);
      });
      return resources;
   },
   getSelectedProblemSizes: function()
   {
      var problemSize = [];
      var selNodes = this.problemSizesTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         problemSize.push(node.text);
      });
      return problemSize;
   },
   getSelectedAppKers: function()
   {
      var appKers = [];
      var selNodes = this.appKerTree.getChecked();
      Ext.each(selNodes, function(node){
         //if(!node.disabled)
         appKers.push(node.text);
      });
      return appKers;
   },
   initComponent: function(){
      var appKerSuccessRateGrid=new XDMoD.Arr.AppKerSuccessRateGrid({
         scope:this,
         region:"center"
      });
      
      this.appKerSuccessRateGrid=appKerSuccessRateGrid
      
      this.showAppKerCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show App Kernel Details',
         checked:true,
         scope: this,
         handler: reloadAll
      });
      this.showAppKerTotalCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show App Kernel Totals',
         checked:true,
         scope: this,
         handler: reloadAll
      });
      this.showResourceTotalCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Resource Totals',
         scope: this,
         checked:true,
         handler: reloadAll
      });
      this.showUnsuccessfulTasksDetailsCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Details of Unsuccessful Tasks',
         scope: this,
         checked:false,
         handler: reloadAll
      });
      this.showSuccessfulTasksDetailsCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Details of Successful Tasks',
         scope: this,
         checked:false,
         handler: reloadAll
      });
      this.showInternalFailureTasksCheckbox=new Ext.form.Checkbox({
         boxLabel  : 'Show Tasks with Internal Failure',
         scope: this,
         checked:false,
         handler: reloadAll
      });
      var optionsPanel = new Ext.Panel({
         //region: 'north',
         //height: 140,
         layout:'vbox',
         margins: '0 0 0 0',
         border: false,
         autoScroll: true,
         useArrows: true,
         layoutConfig: {
            align: 'stretch'
         },
         items: [
            this.showAppKerCheckbox,
            this.showAppKerTotalCheckbox,
            this.showResourceTotalCheckbox,
            this.showUnsuccessfulTasksDetailsCheckbox,
            this.showSuccessfulTasksDetailsCheckbox,
            this.showInternalFailureTasksCheckbox
         ],
         flex: 2
      });
      
      this.resourcesTree = new Ext.tree.TreePanel({
         title: 'Resources',
         id: 'tree_resources_' + this.id,
         useArrows: true,
         autoScroll: true,
         animate: false,
         enableDD: false,
         region: 'north',
         //height: 200,
         root: new Ext.tree.AsyncTreeNode(
         {
            nodeType: 'async',
            text: 'Resources',
            draggable: false,
            id: 'resources',
            expanded: true,
            children: this.resourcesList.map(function (resource){
               return {
                  text:resource,
                  nick:resource,
                  type:"resource",
                  checked:true,
                  iconCls:"resource",
                  leaf: true
                  }
            })
         }),
         rootVisible: false,
         containerScroll: true,
         tools: [{
           id: 'unselect',
           qtip: 'De-select all selected resources.',
           scope: this,
           handler: function()
           {
             this.resourcesTree.un('checkchange',reloadAll,this);
             var lastNode = null;
             var selectAll = true;
             
             this.resourcesTree.getRootNode().cascade(function(n) {
                var ui = n.getUI();
                if(ui.isChecked()) selectAll=false;
                lastNode = n;
             });
             
             if(selectAll){
                this.resourcesTree.getRootNode().cascade(function(n) {
                  var ui = n.getUI();
                  if(!ui.isChecked()) ui.toggleCheck(true);
                  lastNode = n;
                });
             }
             else{
                this.resourcesTree.getRootNode().cascade(function(n) {
                   var ui = n.getUI();
                   if(ui.isChecked()) ui.toggleCheck(false);
                   lastNode = n;
                });
             }
             if(lastNode) reloadAll.call(this);
             this.resourcesTree.on('checkchange',reloadAll,this);
             }
          },{
             id: 'refresh',
             qtip: 'Refresh',
             hidden: true,
             scope: this,
             handler: reloadAll
          }],
         margins: '0 0 0 0',
         border: false,
         split: true,
         flex: 4
      });
      this.problemSizesTree = new Ext.tree.TreePanel({
         flex: 0.5,
         title: "Problem Size (Cores or Nodes)",
          id: 'tree_nodes_' + this.id,
          useArrows: true,
          autoScroll: true,
          animate: false,
          enableDD: false,
         // loader: nodesTreeLoader,

          root:new Ext.tree.AsyncTreeNode(
          {
             nodeType: 'async',
             text: 'Resources',
             draggable: false,
             id: 'resources',
             expanded: true,
             children: this.problemSizeList.map(function (nodesSize){
                return {
                   text:String(nodesSize),
                   qtip:(nodesSize==1)?nodesSize+"node":nodesSize+"nodes",
                   type:"node",
                   checked:true,
                   iconCls:"node",
                   leaf: true
                   }
             })
          }),
          tools: [{
             id: 'unselect',
             qtip: 'De-select all selected resources.',
             scope: this,
             handler: function()
             {
               this.problemSizesTree.un('checkchange',reloadAll,this);
               var lastNode = null;
               var selectAll = true;
               
               this.problemSizesTree.getRootNode().cascade(function(n) {
                  var ui = n.getUI();
                  if(ui.isChecked()) selectAll=false;
                  lastNode = n;
               });
               
               if(selectAll){
                  this.problemSizesTree.getRootNode().cascade(function(n) {
                    var ui = n.getUI();
                    if(!ui.isChecked()) ui.toggleCheck(true);
                    lastNode = n;
                  });
               }
               else{
                  this.problemSizesTree.getRootNode().cascade(function(n) {
                     var ui = n.getUI();
                     if(ui.isChecked()) ui.toggleCheck(false);
                     lastNode = n;
                  });
               }
               if(lastNode) reloadAll.call(this);
               this.problemSizeTree.on('checkchange',reloadAll,this);
               }
            },{
               id: 'refresh',
               qtip: 'Refresh',
               hidden: true,
               scope: this,
               handler: reloadAll
            }],
          rootVisible: false,
          containerScroll: true,
          margins: '0 0 0 0',
          border: false,
          flex: 2
      });
      this.appKerTree = new Ext.tree.TreePanel(
      {
         title: 'App Kernels',
         id: 'tree_appker_' + this.id,
         useArrows: true,
         autoScroll: true,
         animate: false,
         enableDD: false,
         region: 'north',
         //height: 200,
         root: new Ext.tree.AsyncTreeNode(
         {
            nodeType: 'async',
            text: 'App Kernels',
            draggable: false,
            id: 'appker',
            expanded: true,
            children: this.appKerList.map(function (appker){
               return {
                  text:appker,
                  nick:appker,
                  type:"app_kernel",
                  checked:true,
                  iconCls:"appkernel",
                  leaf: true
                  }
            })
         }),
         tools: [{
            id: 'unselect',
            qtip: 'De-select all selected resources.',
            scope: this,
            handler: function()
            {
              this.appKerTree.un('checkchange',reloadAll,this);
              var lastNode = null;
              var selectAll = true;
              
              this.appKerTree.getRootNode().cascade(function(n) {
                 var ui = n.getUI();
                 if(ui.isChecked()) selectAll=false;
                 lastNode = n;
              });
              
              if(selectAll){
                 this.appKerTree.getRootNode().cascade(function(n) {
                   var ui = n.getUI();
                   if(!ui.isChecked()) ui.toggleCheck(true);
                   lastNode = n;
                 });
              }
              else{
                 this.appKerTree.getRootNode().cascade(function(n) {
                    var ui = n.getUI();
                    if(ui.isChecked()) ui.toggleCheck(false);
                    lastNode = n;
                 });
              }
              if(lastNode) reloadAll.call(this);
              this.appKerTree.on('checkchange',reloadAll,this);
              }
           },{
              id: 'refresh',
              qtip: 'Refresh',
              hidden: true,
              scope: this,
              handler: reloadAll
         }],
         rootVisible: false,
         containerScroll: true,
         margins: '0 0 0 0',
         border: false,
         split: true,
         flex: 4
      });
     var leftPanel = new Ext.Panel({
         split: true,
         collapsible: true,
         title: 'App Kernel/Resource Query',
         //collapseMode: 'mini',
         //header: false,
         width: 325,
         layout:{
            type:'vbox',
            align:'stretch'
         },
         region: 'west',
         margins: '2 0 2 2',
         border: true,
         plugins: new Ext.ux.collapsedPanelTitlePlugin(),
         items: [optionsPanel,this.resourcesTree,this.problemSizesTree,this.appKerTree]
      });
      
      this.durationToolbar = new CCR.xdmod.ui.DurationToolbar({
          id: 'duration_selector_' +  this.id,
          alignRight: false,
          showRefresh: true,
          showAggregationUnit: false,
          handler: function () {reloadAll.call(this);},
          //handler:  this.reloadAll,
          scope: this //also scope of handle
      });
       
      this.durationToolbar.dateSlider.region = 'south';
      
      function exportFunction(format, showTitle, scale, width,height)
      {
       var parameters = appKerSuccessRateGrid.store.baseParams;
    
       parameters['scale'] = scale || 1;
        parameters['show_title'] = showTitle;
        parameters['format'] = format;
       parameters['inline'] = 'n';
       //parameters['start'] = THIS.chartPagingToolbar.cursor;
       //parameters['limit'] = THIS.chartPagingToolbar.pageSize;
       parameters['width'] =  width || 757;
          parameters['height'] = height || 400;
       if(format == 'svg') parameters['font_size'] = 0;
       
          CCR.invokePost("controllers/arr_controller.php", parameters);                
      };
      var exportButton = new Ext.Button({
         id: 'export_button_' + this.id,
         text: 'Export',
         iconCls: 'export',
         tooltip: 'Export chart data',
         menu: [
           {
             text: 'CSV - comma Separated Values', iconCls: 'csv',
             handler: function ()
             {
               exportFunction('csv', false);
             }
           }
         ]
       });
      this.durationToolbar.addItem('-');
      this.durationToolbar.addItem(exportButton);
      
      var getBaseParams = function ()
      {
        var selectedResources = this.getSelectedResources();   
        var selectedProblemSizes = this.getSelectedProblemSizes();
        var selectedAppKers = this.getSelectedAppKers();
         
        var baseParams = {};
        baseParams.start_date =  this.durationToolbar.getStartDate().format('Y-m-d');
        baseParams.end_date =  this.durationToolbar.getEndDate().format('Y-m-d');
        baseParams.resources = selectedResources.join(';');
        baseParams.problemSizes = selectedProblemSizes.join(';');
        baseParams.appKers = selectedAppKers.join(';');
        baseParams.showAppKer=this.showAppKerCheckbox.getValue();
        baseParams.showAppKerTotal=this.showAppKerTotalCheckbox.getValue();
        baseParams.showResourceTotal=this.showResourceTotalCheckbox.getValue();
        baseParams.showUnsuccessfulTasksDetails=this.showUnsuccessfulTasksDetailsCheckbox.getValue();
        baseParams.showSuccessfulTasksDetails=this.showSuccessfulTasksDetailsCheckbox.getValue();
        baseParams.showInternalFailureTasks=this.showInternalFailureTasksCheckbox.getValue();
        
        baseParams.format='json';
        return baseParams;
      };
      
      this.appKerSuccessRateGrid.store.on('beforeload', function() {
         if ( ! this.durationToolbar.validate() ) return;

         var baseParams = {};
         Ext.apply(baseParams, getBaseParams.call(this));
         
         baseParams.operation = 'get_ak_success_rates';
         
         this.appKerSuccessRateGrid.store.baseParams=baseParams
         
      }, this);
      
      function reloadAll()
      {
         this.appKerSuccessRateGrid.store.load();
      }
      
      
      Ext.apply(this, {
         layout: 'border',
         tbar:this.durationToolbar,
         items: [this.appKerSuccessRateGrid,leftPanel]
         /*{items: [new Ext.Button({text: 'Refresh',
            handler: function() {
               CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);               
            }
         })]
         }*/
      });//Ext.apply
      
      XDMoD.Arr.AppKerSuccessRatePanel.superclass.initComponent.apply(this, arguments);
   }//initComponent
});//XDMoD.Arr.AppKerSuccessRatePanel
