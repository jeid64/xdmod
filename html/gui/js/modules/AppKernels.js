
/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Feb-07 (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
* This class contains functionality for the App Kernels tab.
*
*/ 
XDMoD.Module.AppKernels = function (config) {

    XDMoD.Module.AppKernels.superclass.constructor.call(this, config);

} //XDMoD.Module.AppKernels

// ===========================================================================

//Add public static methods to the AppKernelViewer class
Ext.apply(XDMoD.Module.AppKernels, {

    /* called from thumbnail view 
     * When a user clicks on thumbnail of a app kernel chart, this function will find
     * the sub node, expand the path to it and then select it so that the chart view
     * will change to view the selected app kernel chart.
     */

    selectChildAppKernelChart: function (metric_id, resource_id, kernel_id) {

        if (metric_id == -1 || resource_id == -1 || kernel_id == -1) return;

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) viewer.el.mask('Loading...');

        var tree = Ext.getCmp('tree_app_kernels');

        if (!tree) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        var nn = tree.getSelectionModel().getSelectedNode();

        if (!nn) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        tree.expandPath(nn.getPath(), null, function (success, node) {

            if (!success) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            if (node.attributes.type == 'appkernel' && node.attributes.ak_id == kernel_id) {

                var nodeToExpand = node.findChild('resource_id', resource_id);

                tree.expandPath(nodeToExpand.getPath(), null, function (success2, node2) {

                    if (!success2) {

                        if (viewer.el) viewer.el.unmask();
                        return;

                    }

                    var nodeToSelect = node2.findChild('metric_id', metric_id, true);

                    if (!nodeToSelect) {

                        if (viewer.el) viewer.el.unmask();
                        return;

                    }

                    tree.getSelectionModel().select(nodeToSelect);

                }); //tree.expandPath(nodeToExpand...

            } else if (node.attributes.type == 'resource' && node.attributes.resource_id == resource_id) {

                var nodeToSelect = node.findChild('metric_id', metric_id, true);

                if (!nodeToSelect) {

                    if (viewer.el) viewer.el.unmask();
                    return;

                }

                tree.getSelectionModel().select(nodeToSelect);

            }

        }); //tree.expandPath(nn)

    }, //selectChildAppKernelChart

    // ------------------------------------------------------------------

    /*
     * When a user clicks a data series pertaining to the number of processing units on a app kernel chart,
     * this call will expand the node in the tree and select the sub node representing the chart for the
     * selectected number of processing units.
     */

    selectChildUnitsChart: function (num_units) {

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) viewer.el.mask('Loading...');

        var tree = Ext.getCmp('tree_app_kernels');

        if (!tree) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        var nn = tree.getSelectionModel().getSelectedNode();

        if (!nn) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        tree.expandPath(nn.getPath(), null, function (success, node) {

            if (!success) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            var nodeToSelect = node.findChild('num_proc_units', num_units, true);

            if (!nodeToSelect) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            tree.getSelectionModel().select(nodeToSelect);

        }); //tree.expandPath(nn

    }, //selectChildUnitsChart

    // ------------------------------------------------------------------

    /*
     * This function can be used to change the selected tab to app kernel tab and select the app kernel node
     * indicated by kernel_id.
     */

    gotoAppKernel: function (kernel_id) {

        var tabPanel = Ext.getCmp('main_tab_panel');

        if (!tabPanel) return;

        tabPanel.setActiveTab('app_kernels');

        var tree = Ext.getCmp('tree_app_kernels');
        if (!tree) return;

        var root = tree.getRootNode();

        tree.expandPath(root.getPath(), null, function (success, node) {

            if (!success) return;

            var kernelNode = node.findChild('ak_id', kernel_id);
            tree.getSelectionModel().select(kernelNode);

        });

    } //gotoAppKernel

}); //Ext.apply(XDMoD.Module.AppKernels, ...

// ===========================================================================

Ext.extend(XDMoD.Module.AppKernels, XDMoD.PortalModule, {

    module_id: 'application_kernels',

    usesToolbar: true,

    toolbarItems: {

        durationSelector: {

            enable: true,

            config: {
                showAggregationUnit: false
            }

        }, //durationSelector

        exportMenu: true,
        printButton: true

    },

    legend_type: 'bottom_center',
    font_size: 3,
    swap_xy: false,

    showDateChooser: true,

    chartDataFields: [

        'hc_jsonstore',
        'title',
        'start_date',
        'end_date',
        'random_id',
        'comments',
        'resource_description',
        'ak_id',
        'resource_id',
        'metric_id',
        'ak_name',
        'resource_name',
        'format',
        'scale',
        'width',
        'height',
        'final_width',
        'final_height',
        'show_guide_lines', {
            name: 'short_title',
            mapping: 'short_title',
            convert: CCR.xdmod.ui.shortTitle
        }

    ], //chartDataFields

    largeTemplate: [

        '<tpl for=".">',
        //'<center>',
        //	'<h2>{title}</h2>',
        //	'<span class="date_range">{start_date} to {end_date}</span>',
        //'</center>',
        //'<br />',
        '<center>',
        '<div id="{random_id}">', '</div>',
        '</center>',
        '</tpl>'

    ], //largeTemplate

    thumbTemplate: [

        '<tpl for=".">',
        '<div class="chart_thumb-wrap2" id="{ak_id}{resource_id}{metric_id}">',
        '<span class="ak_thumb_title">{ak_name}: {resource_name}</span>',
        '<div class="chart_thumb">',
        '<a href="javascript:XDMoD.Module.AppKernels.selectChildAppKernelChart({metric_id},{resource_id},{ak_id});">',

        '<div id="{random_id}">',
        '</div>',

        '</a>',
        '</div>',
        '<span class="ak_thumb_subtitle">{short_title}</span>',
        '</div>',
        '</tpl>'

    ],

    // ------------------------------------------------------------------

    initComponent: function () {

        var self = this;

        this.hiddenCharts = [];
        var layoutId = this.id;

        var chartScale = 1;
        var chartThumbScale = CCR.xdmod.ui.thumbChartScale;
        var chartWidth = 740;
        var chartHeight = 345;

        var treeTb = new Ext.Toolbar({

            items: [

                '->',

                {
                    iconCls: 'icon-collapse-all',
                    tooltip: 'Collapse All',
                    handler: function () {
                        XDMoD.TrackEvent('App Kernels', 'Clicked on Collapse All button above tree panel');
                        tree.root.collapse(true);
                    },
                    scope: this
                },

                {
                    iconCls: 'refresh',
                    tooltip: 'Refresh tree and clear drilldown nodes',

                    handler: function () {

                        XDMoD.TrackEvent('App Kernels', 'Clicked on Refresh button above tree panel');

                        var selModel = tree.getSelectionModel();
                        var selNode = selModel.getSelectedNode();

                        if (selNode) selModel.unselect(selNode, true);
                        tree.root.removeAll(true);
                        tree.loader.on('load', selectFirstNode, this, {
                            single: true
                        });
                        tree.loader.load(tree.root);

                    },

                    scope: this
                }

            ] //items

        }); //treeTb

        // ---------------------------------------------------------

        var tree = new XDMoD.RESTTree({

            restAction: 'appkernel/explorer/tree',

            determineArguments: function (node) {

                var call_arguments = {
                    ak: node.attributes.ak_id,
                    resource: node.attributes.resource_id,
                    metric: node.attributes.metric_id,
                    num_proc_units: node.attributes.num_proc_units,
                    collected: node.attributes.collected
                };

                return call_arguments;
            },

            listeners: {

                'beforeappend': function (t, p, n) {
                    n.setIconCls(n.attributes.type);
                    var start_time = self.getDurationSelector().getStartDate() / 1000.0;
                    var end_time = self.getDurationSelector().getEndDate() / 1000.0;
                    var enabled = (start_time <= n.attributes.end_ts && n.attributes.end_ts <= end_time) ||
                        (n.attributes.start_ts <= end_time && end_time <= n.attributes.end_ts);
                    if (enabled) n.enable();
                    else n.disable();
                },
            
               expandnode: function(n) {
               
                  XDMoD.TrackEvent('App Kernels', 'Expanded item in tree panel', n.getPath('text'));

               },//expandnode
               
               collapsenode: function(n) {
               
                  XDMoD.TrackEvent('App Kernels', 'Collapsed item in tree panel', n.getPath('text'));

               }//collapsenode

            },

            root: {
                nodeType: 'async',
                text: this.title,
                draggable: false,
                id: 'app_kernels'
            },

            id: 'tree_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: true,
            enableDD: false,

            rootVisible: false,
            tbar: treeTb,

            //collapsible: true,
            region: 'west',
            //width: 325,
            //split: true,
            header: false,
            //title: this.title,
            // margins: '2 0 2 2',
            containerScroll: true,
            border: false,
            region: 'center'

        }); //tree

        // ---------------------------------------------------------

        function selectFirstNode() {

            var node = tree.getSelectionModel().getSelectedNode();
            if (node) return;

            var root = tree.getRootNode();

            if (root.hasChildNodes()) {

                var child = root.item(0);
                tree.getSelectionModel().select(child);

            }

        } //selectFirstNode

        tree.loader.on('load', selectFirstNode, this, {
            buffer: 500,
            single: true
        });

        // ---------------------------------------------------------

        function updateDescriptionLarge(s, showResource) {

            var data = {
                comments: s.getAt(0).get('comments'),
                resource_name: s.getAt(0).get('resource_name'),
                resource_description: s.getAt(0).get('resource_description')
            };

            if (showResource)
                commentsTemplateWithResource.overwrite(commentsPanel.body, data);
            else
                commentsTemplateWithoutResource.overwrite(commentsPanel.body, data);

        } //updateDescriptionLarge

        // ---------------------------------------------------------

        function getParameters(n) {

            var parameters = {

                show_change_indicator: toggleChangeIndicator.pressed ? 'y' : 'n',
                collected: n.attributes.collected,
                start_time: self.getDurationSelector().getStartDate() / 1000.0,
                end_time: self.getDurationSelector().getEndDate() / 1000.0,
                timeframe_label: self.getDurationSelector().getDurationLabel(),
                //title: this.chartTitleField.getValue(),
                legend_type: this.legendTypeComboBox.getValue(),
                font_size: this.fontSizeSlider.getValue(),
                swap_xy: this.swap_xy

            }; //parameters

            if (n.attributes.type == 'units') {

                parameters['num_proc_units'] = n.attributes.num_proc_units;
                parameters['metric'] = n.attributes.metric_id;
                parameters['resource'] = n.attributes.resource_id;
                parameters['ak'] = n.attributes.ak_id;
                parameters['scale'] = 1;
                parameters['format'] = 'session_variable';
                parameters['show_title'] = 'y';
                parameters['width'] = chartWidth * chartScale;
                parameters['height'] = chartHeight * chartScale;
                parameters['show_control_plot'] = toggleControlPlot.pressed ? 'y' : 'n';
                parameters['discrete_controls'] = toggleDiscreteControls.pressed ? 'y' : 'n';
                parameters['show_control_zones'] = toggleControlZones.pressed ? 'y' : 'n';
                parameters['show_running_averages'] = toggleRunningAverages.pressed ? 'y' : 'n';
                parameters['show_control_interval'] = toggleControlInterval.pressed ? 'y' : 'n';

            } else if (n.attributes.type == 'metric') {

                parameters['metric'] = n.attributes.metric_id;
                parameters['resource'] = n.attributes.resource_id;
                parameters['ak'] = n.attributes.ak_id;
                parameters['scale'] = 1;
                parameters['format'] = 'session_variable';
                parameters['show_title'] = 'y';
                parameters['width'] = chartWidth * chartScale;
                parameters['height'] = chartHeight * chartScale;

            } else if (n.attributes.type == 'resource') {

                parameters['resource'] = n.attributes.resource_id;
                parameters['ak'] = n.attributes.ak_id;
                parameters['width'] = CCR.xdmod.ui.thumbWidth * chartThumbScale;
                parameters['height'] = CCR.xdmod.ui.thumbHeight * chartThumbScale;
                parameters['scale'] = 1;
                parameters['format'] = 'session_variable';
                parameters['thumbnail'] = 'y';
                parameters['show_guide_lines'] = 'n';
                parameters['font_size'] = parameters['font_size'] - 3;

            } else if (n.attributes.type == 'appkernel') {

                parameters['ak'] = n.attributes.ak_id;
                parameters['metric'] = 4;
                parameters['width'] = CCR.xdmod.ui.thumbWidth * chartThumbScale;
                parameters['height'] = CCR.xdmod.ui.thumbHeight * chartThumbScale;
                parameters['scale'] = 1;
                parameters['format'] = 'session_variable';
                parameters['thumbnail'] = 'y';
                parameters['show_guide_lines'] = 'n';
                parameters['font_size'] = parameters['font_size'] - 3;

            }

            return parameters;

        } //getParameters

        // ---------------------------------------------------------

        function onSelectNode(model, n) {
            if (!n || n.disabled) {
                tree.getRootNode().eachChild(function (nn) {
                    if (!nn.disabled) {
                        tree.getSelectionModel().select(nn);
                        return false;
                    }
                    return true;
                });
                return
            }

            if (!self.getDurationSelector().validate()) return;

            Ext.History.un('change', onHistoryChange);
            Ext.History.add(layoutId + CCR.xdmod.ui.tokenDelimiter + n.id, true);
            Ext.History.on('change', onHistoryChange);

            images.setTitle(n.getPath('text'));

            if (n.attributes.type == 'units')
                toggleControlPlot.show();
            else
                toggleControlPlot.hide();

            var isChart = n.attributes.type == 'units' || n.attributes.type == 'metric';
            var isMenu = n.attributes.type == 'resource' || n.attributes.type == 'appkernel';

            XDMoD.TrackEvent('App Kernels', 'Selected ' + n.attributes.type + ' From Tree', n.getPath('text'));
            
            if (isChart || isMenu) {

                var viewer = CCR.xdmod.ui.Viewer.getViewer();
                if (viewer.el) viewer.el.mask('Loading...');
                self.getDurationSelector().disable();

                if (isChart) {
                    view.tpl = largeChartTemplate;
                } else if (isMenu) {
                    view.tpl = thumbnailChartTemplate;
                }

                var parameters = getParameters.call(this, n);

                if (isChart) {
                    view.tpl = largeChartTemplate;
                } else if (isMenu) {
                    view.tpl = thumbnailChartTemplate;
                }

                chartStore.load({
                    params: parameters
                });

            } else {

                var viewer = CCR.xdmod.ui.Viewer.getViewer();
                if (viewer.el) viewer.el.unmask();

            }

        } //onSelectNode

      

        var thumbnailChartTemplate = new Ext.XTemplate(this.thumbTemplate);

        var largeChartTemplate = new Ext.XTemplate(this.largeTemplate);

        // ---------------------------------------------------------

        var chartStore = new Ext.data.JsonStore({

            highChartPanels: [],
            storeId: 'Performance',
            autoDestroy: false,
            root: 'results',
            totalProperty: 'num',
            successProperty: 'success',
            messageProperty: 'message',
            fields: this.chartDataFields,

            proxy: new CCR.xdmod.RESTDataProxy({

                url: 'rest/appkernel/explorer/plot'

            })

        }); //chartStore

        // ---------------------------------------------------------

        chartStore.on('exception', function (dp, type, action, opt, response, arg) {

            var resp = Ext.decode(response.responseText);

            if (resp.success !== true) {

                Ext.MessageBox.alert("Error", resp.message || 'Unknown Error');

                if (

                    resp.message.indexOf('Session Expired') > -1 ||
                    resp.message.indexOf('Invalid token specified') > -1 ||
                    resp.message.indexOf('Token invalid or expired.  You must authenticate before using this call.') > -1

                ) {
                    CCR.xdmod.ui.actionLogout.defer(1000);
                } else {
                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                    if (viewer.el) viewer.el.unmask();
                }

            } //if (resp.success !== true)

        }, this); //chartStore.on('exception', …

        // ---------------------------------------------------------

        chartStore.on('beforeload', function () {

            if (!self.getDurationSelector().validate()) return;
            maximizeScale.call(this);
            view.un('resize', onResize, this);

        }, this);

        // ---------------------------------------------------------

        chartStore.on('load', function (chartStore) {

            var model = tree.getSelectionModel();
            var n = model.getSelectedNode();

            if (!n) return;

            var isChart = n.attributes.type == 'units' || n.attributes.type == 'metric';
            var isMenu = n.attributes.type == 'resource' || n.attributes.type == 'appkernel';

            if (isChart && this.chart) {

                delete this.chart;
                this.chart = null;

            }

            if (isMenu) {

                if (this.charts) {

                    for (var i = 0; i < this.charts.length; i++)
                        delete this.charts[i];

                    delete this.charts;

                } //if(this.charts)

                this.charts = [];

            } //if(isMenu)

            updateDescriptionLarge(chartStore, n.attributes.type != 'appkernel');

            XDMoD.TrackEvent('App Kernels', 'Loaded AK Data', n.getPath('text'));

            var tool = images.getTool('restore');
            if (tool)
                if (isMenu) tool.show();
                else tool.hide();

            tool = images.getTool('plus');
            if (tool)
                if (isMenu) tool.show();
                else tool.hide();

            tool = images.getTool('minus');
            if (tool)
                if (isMenu) tool.show();
                else tool.hide();

            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) viewer.el.unmask();

            var ind = 0;

            chartStore.each(function (r) {

                var id = r.get('random_id');
                var el = Ext.get(id); // Get Ext.Element object

                var task = new Ext.util.DelayedTask(function () {

                    var baseChartOptions = {

                        chart: {

                            renderTo: id,
                            width: isMenu ? CCR.xdmod.ui.thumbWidth * chartThumbScale : chartWidth * chartScale,
                            height: isMenu ? CCR.xdmod.ui.thumbHeight * chartThumbScale : chartHeight * chartScale,
                            animation: true

                        },

                        loading: {

                            labelStyle: {
                                top: '45%'
                            }

                        },

                        exporting: {
                            enabled: false
                        },

                        credits: {
                            enabled: true
                        }

                    }; //baseChartOptions

                    var chartOptions = r.get('hc_jsonstore');
                    jQuery.extend(true, chartOptions, baseChartOptions);

                    chartOptions.exporting.enabled = false;
                    chartOptions.credits.enabled = isChart;

                    function evalFormatters(o) {

                        for (var name in o) {

                            var otype = typeof (o[name]);

                            if (otype == 'object') {
                                evalFormatters(o[name]);
                            }

                            if (name == 'formatter' || name == 'labelFormatter') {
                                o[name] = new Function(o[name]);
                            }

                            if (name === 'click') {
                                o[name] = new Function(o[name]);
                            }

                        } //for(var name in o)

                    } //evalFormatters

                    evalFormatters(chartOptions);

                    if (isMenu)
                        this.charts.push(new Highcharts.Chart(chartOptions));
                    else
                        this.chart = new Highcharts.Chart(chartOptions);

                }, this); //task

                task.delay(0);

                return true;

            }, this); //chartStore.each(...

            view.on('resize', onResize, this);

            if (isMenu)
                self.getExportMenu().disable();
            else
                self.getExportMenu().enable();

            self.getDurationSelector().enable();

        }, this); //chartStore.on('load',...

        // ---------------------------------------------------------

        chartStore.on('load', function (chartStore) {

            var tool = images.getTool('refresh');
            if (tool) tool.show();

            tool = images.getTool('plus');
            if (tool) tool.show();

            tool = images.getTool('minus');
            if (tool) tool.show();

            tool = images.getTool('print');
            if (tool) tool.show();

            tool = images.getTool('restore');
            if (tool) tool.show();

        }, chartStore, {
            single: true
        });

        // ---------------------------------------------------------

        chartStore.on('clear', function (chartStore) {

            var tool = images.getTool('refresh');
            if (tool) tool.hide();

            tool = images.getTool('plus');
            if (tool) tool.hide();

            tool = images.getTool('minus');
            if (tool) tool.hide();

            tool = images.getTool('print');
            if (tool) tool.hide();

            tool = images.getTool('restore');
            if (tool) tool.hide();

        }, chartStore, {
            single: true
        });

        // ---------------------------------------------------------

        var view = new Ext.DataView({

            loadingText: "Loading...",
            itemSelector: 'chart_thumb-wrap',
            style: 'overflow:auto',
            multiSelect: true,
            store: chartStore,
            autoScroll: true,
            tpl: largeChartTemplate

        }); //view

        // ---------------------------------------------------------

        var viewPanel = new Ext.Panel({

            layout: 'fit',
            region: 'center',
            items: view,
            border: true

        }); //viewPanel

        // ---------------------------------------------------------

        var commentsTemplateWithResource = new Ext.XTemplate(

            '<table class="xd-table">',
            '<tr>',
            '<td width="50%">',
            '<span class="kernel_description_label">Application Kernel:</span><br/> <span class="kernel_description">{comments}</span>',
            '</td>',
            '<td width="50%">',
            '<span class="kernel_description_label">{resource_name}:</span> <br/><span class="kernel_description">{resource_description}</span>',
            '</td>',
            '</tr>',
            '</table>'

        ); //commentsTemplateWithResource

        // ---------------------------------------------------------

        var commentsTemplateWithoutResource = new Ext.XTemplate(

            '<table class="xd-table">',
            '<tr>',
            '<td width="100%">',
            '<span class="kernel_description_label">Application Kernel:</span><br/> <span class="kernel_description">{comments}</span>',
            '</td>',
            '</tr>',
            '</table>'

        ); //commentsTemplateWithoutResource

        // ---------------------------------------------------------

        var commentsPanel = new Ext.Panel({

            region: 'south',
            autoScroll: true,
            border: true,
            collapsible: true,
            split: true,
            title: 'Description',
            height: 130

        }); //commentsPanel

        // ---------------------------------------------------------

        var reloadChartFunc = function () {

            var model = tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node != null) {

                tree.getSelectionModel().unselect(node, true);
                tree.getSelectionModel().select(node);

            } //if (node != null)

        } //reloadChartFunc

        // ---------------------------------------------------------

        var reloadChartTask = new Ext.util.DelayedTask(reloadChartFunc, this);

        var reloadChartStore = function (delay) {

            reloadChartTask.delay(delay || 0);

        }; //reloadChartStore

        // ---------------------------------------------------------

        self.on('duration_change', function (d) {
            var start_time = self.getDurationSelector().getStartDate() / 1000.0;
            var end_time = self.getDurationSelector().getEndDate() / 1000.0;
            tree.getRootNode().cascade(function (n) {
                var enabled = (start_time <= n.attributes.end_ts && n.attributes.end_ts <= end_time) ||
                    (n.attributes.start_ts <= end_time && end_time <= n.attributes.end_ts);
                if (enabled) n.enable();
                else n.disable();
                //if(!enabled)n.setText(n.text+'*');

                return true;
            });
            reloadChartStore();

        }); //self.on('duration_change',...

        // ---------------------------------------------------------

        self.on('export_option_selected', function (opts) {

            var selectedNode = tree.getSelectionModel().getSelectedNode();

            if (selectedNode != null) {

                var parameters = getParameters.call(self, selectedNode);
                parameters['inline'] = 'n';

                Ext.apply(parameters, opts);

                var imgOrURL = '/rest/appkernel/explorer/' + (parameters['format'] == 'png' || parameters['format'] == 'svg' || parameters['format'] == 'eps' ? 'plot' : 'dataset');

                CCR.invokePost(imgOrURL + '?token=' + XDMoD.REST.token, parameters);

            } //if (selectedNode != null)

        }); //self.on('export_option_selected', ...

        // ---------------------------------------------------------

        function maximizeScale() {

            chartWidth = view.getWidth();
            chartHeight = view.getHeight() - (images.tbar ? images.tbar.getHeight() : 0);

        }; //maximizeScale

        // ---------------------------------------------------------

        var images = new Ext.Panel({

            title: 'Viewer',
            region: 'center',
            margins: '2 2 2 0',
            layout: 'border',
            split: true,
            //border: false,
            items: [viewPanel, commentsPanel],

            tools: [

                {

                    id: 'restore',
                    qtip: 'Restore Chart Size aa',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if (node.attributes.metric_id != null)
                                chartScale = 1.0;
                            else
                                chartThumbScale = CCR.xdmod.ui.thumbChartScale;

                            onSelectNode.call(this, model, node);

                        } //if (node != null)

                    } //handler

                }, //restore

                {

                    id: 'minus',
                    qtip: 'Reduce Chart Size',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                       
                            if ((chartThumbScale - CCR.xdmod.ui.deltaThumbChartScale)  > CCR.xdmod.ui.minChartScale) 
							{
								chartThumbScale -= CCR.xdmod.ui.deltaThumbChartScale;

                            	onSelectNode.call(this, model, node);
							}

                        } //if (node != null)

                    } //handler

                }, //minus

                {

                    id: 'plus',
                    qtip: 'Increase Chart Size',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if ((chartThumbScale + CCR.xdmod.ui.deltaThumbChartScale) < CCR.xdmod.ui.maxChartScale) 
							{
								chartThumbScale += CCR.xdmod.ui.deltaThumbChartScale;

                            	onSelectNode.call(this, model, node);
							}

                        } ////if (node != null)

                    } //handler

                } //plus

            ] //tools

        }); //images

        // ---------------------------------------------------------

        var onHistoryChange = function (token) {

            if (token) {

                var parts = token.split(CCR.xdmod.ui.tokenDelimiter);

                if (parts[0] == layoutId) {

                    var treePanel = Ext.getCmp('tree_' + layoutId);
                    var nodeId = parts[1];

                    Ext.menu.MenuMgr.hideAll();
                    treePanel.getSelectionModel().select(treePanel.getNodeById(nodeId));

                } //if (parts[0] == layoutId)

            } //if (token)

        }; //onHistoryChange

        // ---------------------------------------------------------

        // Handle this change event in order to restore the UI to the appropriate history state
        // Ext.History.on('change', onHistoryChange);

        self.on('print_clicked', function () {

            var model = tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node != null) {

                if (node.attributes.metric_id != null) {

                    var parameters = getParameters.call(this, node);
                    parameters['scale'] = 1; //CCR.xdmod.ui.hd1280Scale;
                    parameters['inline'] = 'y';
                    parameters['format'] = 'png';
                    parameters['width'] = 757 * 2;
                    parameters['height'] = 400 * 2;

                    var params = '';

                    for (i in parameters)
                        params += i + '=' + parameters[i] + '/'

                    params = params.substring(0, params.length - 1);

                    Ext.ux.Printer.print({

                        getXTypes: function () {
                            return 'html';
                        },
                        html: '<img src="/rest/appkernel/explorer/plot/' + params + '?XDMoD.REST.token=' + XDMoD.REST.token + '" />'

                    });

                } else {
                    Ext.ux.Printer.print(view);
                }

            } //if (node != null)

        }); //self.on('print_clicked', ...

        // ---------------------------------------------------------

        var toggleChangeIndicator = new Ext.Button({

            text: 'Change Indicators',
            enableToggle: true,
            scope: this,
            iconCls: 'exclamation',
            
            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },
            
            pressed: false,
            tooltip: 'On each app kernel plot, show an exclamation point icon if and whenever the a change occurred to the execution environment of the app kernel (library version, compiler version, etc).'

        }); //toggleChangeIndicator

        var toggleRunningAverages = new Ext.Button({

            text: 'Running Averages',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            
            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },
            
            pressed: true,
            tooltip: 'Show the running average values as a dashed line on the chart. The running average is the linear average of the last five values.'

        }); //toggleRunningAverages

        var toggleControlInterval = new Ext.Button({

            text: 'Control Band',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,
            tooltip: 'Show a band on the chart representing the values of the running average considered "In Control" at any given time. <br>A control region is picked to be first few points in a dataset and updated whenever an execution environment change is detected by the app kernel system. The control band then is calculated by clustering the control region into two sets based on the median and then finding the average of each set. The two averages define the control band.'

        }); //toggleControlInterval

        var toggleControlZones = new Ext.Button({

            text: 'Control Zones',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',

            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },

            pressed: true,
            tooltip: 'Show a red interval on the plot when the control value falls below -0.5, indicating an out of control (worse than expected) running average, and a green interval when the control value is greater than 0, indicating a better than control (better than expected) running average. Other running average values are considered "In Control"'

        }); //toggleControlZones

        // ---------------------------------------------------------

        var toggleControlPlot = new Ext.Button({

            text: 'Control Plot',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            
            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },
            
            pressed: false,

            listeners: {

                show: function () {

                    if (this.pressed)
                        toggleDiscreteControls.show();
                    else
                        toggleDiscreteControls.hide();

                    toggleControlZones.show();
                    toggleRunningAverages.show();
                    toggleControlInterval.show();

                }, //show

                hide: function () {

                    toggleDiscreteControls.hide();
                    toggleControlZones.hide();
                    toggleRunningAverages.hide();
                    toggleControlInterval.hide();

                }, //hide

                toggle: function (t, pressed) {

                    if (!pressed)
                        toggleDiscreteControls.hide();
                    else
                        toggleDiscreteControls.show();

                } //toggle

            }, //listeners

            tooltip: 'Plot the value of the control on the chart as a dotted line. The control is calculated as the distance of the running average to the nearest boundary of the control band, normalized over the range of the control band.'

        }); //toggleControlPlot

        // ---------------------------------------------------------

        var toggleDiscreteControls = new Ext.Button({

            text: 'Discrete Controls',
            enableToggle: true,
            hidden: true,
            scope: this,
            iconCls: '',
            
            toggleHandler: function(b) {
               XDMoD.TrackEvent('App Kernels', 'Clicked on ' + b.getText(), Ext.encode({pressed: b.pressed}));
               reloadChartStore();
            },
            
            hidden: true,
            pressed: false,
            tooltip: 'Convert the control values from real numbers to discrete values of [-1, 0, 1]. Values less than zero become -1 and values greater than zero become 1.'

        }); //toggleDiscreteControls

        this.chartTitleField = new Ext.form.TextField({

            fieldLabel: 'Title',
            name: 'title',
            emptyText: 'Chart Title',
            validationDelay: 1000,
            enableKeyEvents: true,

            listeners: {

                scope: this,

                change: function (t, n, o) {

                    if (n != o) {
                    
                        XDMoD.TrackEvent('App Kernels', 'Updated title', t.getValue());
                    
                        reloadChartStore.call(this);
                    
                    }

                }, //change

                specialkey: function (t, e) {

                    if (t.isValid(false) && e.getKey() == e.ENTER) {
                    
                        XDMoD.TrackEvent('App Kernels', 'Updated title', t.getValue());
                     
                        reloadChartStore.call(this);
                     
                    }

                } //specialkey

            } //listeners

        }); //this.chartTitleField

        // ---------------------------------------------------------

        this.legendTypeComboBox = new Ext.form.ComboBox({

            fieldLabel: 'Legend',
            name: 'legend_type',
            xtype: 'combo',
            mode: 'local',
            editable: false,

            store: new Ext.data.ArrayStore({

                id: 0,

                fields: [
                    'id',
                    'text'
                ],

                data: [
                    ['top_center', 'Top Center'],
                    ['bottom_center', 'Bottom Center (Default)'],
                    ['left_center', 'Left'],
                    ['left_top', 'Top Left'],
                    ['left_bottom', 'Bottom Left'],
                    ['right_center', 'Right'],
                    ['right_top', 'Top Right'],
                    ['right_bottom', 'Bottom Right'],
                    ['floating_top_center', 'Floating Top Center'],
                    ['floating_bottom_center', 'Floating Bottom Center'],
                    ['floating_left_center', 'Floating Left'],
                    ['floating_left_top', 'Floating Top Left'],
                    ['floating_left_bottom', 'Floating Bottom Left'],
                    ['floating_right_center', 'Floating Right'],
                    ['floating_right_top', 'Floating Top Right'],
                    ['floating_right_bottom', 'Floating Bottom Right'],
                    ['off', 'Off']
                ]

            }), //store

            disabled: false,
            value: this.legend_type,
            valueField: 'id',
            displayField: 'text',
            triggerAction: 'all',

            listeners: {

                scope: this,

                'select': function (combo, record, index) {

                    XDMoD.TrackEvent('App Kernels', 'Updated legend placement', Ext.encode({legend_type: record.get('id')}));
                    
                    this.legend_type = record.get('id');
                    reloadChartStore.call(this, 2000);

                } //select

            } //listeners

        }); //this.legendTypeComboBox

        // ---------------------------------------------------------

        this.fontSizeSlider = new Ext.slider.SingleSlider({

            fieldLabel: 'Font Size',
            name: 'font_size',
            minValue: -5,
            maxValue: 10,
            value: this.font_size,
            increment: 1,
            plugins: new Ext.slider.Tip(),

            listeners: {

                scope: this,

                'change': function (t, n, o) {

                    XDMoD.TrackEvent('App Kernels', 'Used the font size slider', Ext.encode({font_size: t.getValue()}));

                    this.font_size = t.getValue();
                    reloadChartStore.call(this, 2000);

                } //change

            } //listeners

        }); //this.fontSizeSlider

        // ---------------------------------------------------------

        this.chartSwapXYField = new Ext.form.Checkbox({

            fieldLabel: 'Invert Axis',
            name: 'swap_xy',
            boxLabel: 'Swap the X and Y axis',
            checked: this.swap_xy,

            listeners: {

                scope: this,

                check: function (checkbox, check) {

                    XDMoD.TrackEvent('App Kernels', 'Clicked on Swap the X and Y axis', Ext.encode({checked: check}));
                     
                    this.swap_xy = check;
                    reloadChartStore.call(this, 2000);

                } //check

            } //listeners

        }); //this.chartSwapXYField

        // ---------------------------------------------------------

        var leftPanel = new Ext.Panel({

            split: true,
            bodyStyle: 'padding:5px 5px ;',
            collapsible: true,
            header: true,
            title: 'Query Options',
            autoScroll: true,
            width: 375,
            margins: '2 0 2 2',
            border: true,
            region: 'west',
            layout: 'border',

            plugins: new Ext.ux.collapsedPanelTitlePlugin('Query Options'),

            items: [

                {
                    xtype: 'form',
                    layout: 'fit',
                    region: 'north',
                    height: 97,
                    border: false,

                    items: [{

                        xtype: 'fieldset',
                        header: false,
                        layout: 'form',
                        hideLabels: false,
                        border: false,

                        defaults: {
                            anchor: '0' // '-20' // leave room for error icon
                        },

                        items: [

                            //this.chartTitleField,
                            this.legendTypeComboBox,
                            this.fontSizeSlider,
                            this.chartSwapXYField

                        ]

                    }]

                },

                tree

            ] //items

        }); //leftPanel

        // ---------------------------------------------------------

        Ext.apply(this, {

            customOrder: [

                XDMoD.ToolbarItem.DURATION_SELECTOR,
                XDMoD.ToolbarItem.EXPORT_MENU,
                XDMoD.ToolbarItem.PRINT_BUTTON,

                {
                    item: toggleChangeIndicator,
                    separator: true
                },

                {
                    item: toggleRunningAverages,
                    separator: false
                },

                {
                    item: toggleControlInterval,
                    separator: false
                },

                {
                    item: toggleControlZones,
                    separator: false
                },

                {
                    item: toggleControlPlot,
                    separator: false
                },

                {
                    item: toggleDiscreteControls,
                    separator: false
                }

            ],

            items: [leftPanel, images]

        }); //Ext.apply

        // ---------------------------------------------------------

        function onResize(t, adjWidth, adjHeight, rawWidth, rawHeight) {

            maximizeScale();
            if (this.chart) this.chart.setSize(adjWidth, adjHeight);

        }; //onResize
  // ---------------------------------------------------------

        view.on('render', function () {

            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) viewer.el.mask('Loading...');
			
			{
				var thumbAspect = CCR.xdmod.ui.thumbAspect;
				var thumbWidth = CCR.xdmod.ui.thumbWidth * chartThumbScale;
				
				var portalWidth = view.getWidth() - (CCR.xdmod.ui.scrollBarWidth - CCR.xdmod.ui.thumbPadding/2); // comp for scrollbar
				portalColumnsCount = Math.max(1, Math.round(portalWidth / thumbWidth) );
				
				thumbWidth = portalWidth / portalColumnsCount;
				thumbWidth -= CCR.xdmod.ui.thumbPadding;

				chartThumbScale = thumbWidth / (chartThumbScale * CCR.xdmod.ui.thumbWidth);	
			}
			
            self.getDurationSelector().disable();

            tree.getSelectionModel().on('selectionchange', onSelectNode, this);

        }, this, {
            single: true
        });
		
        // Call parent (required)
        XDMoD.Module.AppKernels.superclass.initComponent.apply(this, arguments);

    } //initComponent

}); //Ext.extend(XDMoD.Module.AppKernels, Ext.Panel