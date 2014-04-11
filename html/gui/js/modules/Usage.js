/*
* JavaScript Document
* Usage Tab
* @author Amin Ghadersohi
* @date 2011-Feb-07 (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
*
* This class contains functionality for the My/XD usage tabs
*
*/  

XDMoD.Module.Usage = function (config) {

    XDMoD.Module.Usage.superclass.constructor.call(this, config);

}; //XDMoD.Module.Usage

// ===========================================================================

Ext.apply(XDMoD.Module.Usage, {

    selectChildChart: function (nodeId, layoutId) {

        var tree = Ext.getCmp('tree_' + layoutId);

        if (!tree) {
            return;
        }

        var node = tree.getSelectionModel().getSelectedNode();

        if (!node) {
            return;
        }

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) viewer.el.mask('Loading...');

        tree.expandPath(node.getPath(), null, function (success, node) {

            if (!success) {
                if (viewer.el) viewer.el.unmask();
                return;
            }

            var nodeToSelect = node.findChild('id', nodeId, true);

            if (!nodeToSelect) {
                if (viewer.el) viewer.el.unmask();
                return;
            }

            if (node.attributes.chartSettings) {
                nodeToSelect.attributes.chartSettings = node.attributes.chartSettings;
            }

            if (node.attributes.filter) {
                nodeToSelect.attributes.filter = node.attributes.filter;
            }

            tree.getSelectionModel().select(nodeToSelect);

        }); //tree.expandPath

    }, //selectChildChart

    // ------------------------------------------------------------------

    drillChart: function (point, drillDowns, groupByNameAndUnit, groupById, groupByValue, value, queryGroupname, realmName) {

        var tree = Ext.getCmp('tree_tg_usage');

        if (!tree) {
            return;
        }

        var node = tree.getSelectionModel().getSelectedNode();

        if (!node) {
            return;
        }

        var roleCategorySelectorItems = Ext.getCmp('tg_usage').getRoleSelector();

        if (!roleCategorySelectorItems) {
            return;
        }

        groupByNameAndUnit = groupByNameAndUnit.split('-');

        if (groupByNameAndUnit.length < 2) return;

        var groupByName = groupByNameAndUnit[0];
        var groupByUnit = groupByNameAndUnit[1];
            
        
        XDMoD.TrackEvent('Usage', 'Clicked on chart to access drill-down menu', Ext.encode({
           'x-axis': point.ts ? Highcharts.dateFormat('%Y-%m-%d', point.ts) : point.series.data[point.x].category, 
           'y-axis': point.y,
           'label': (groupByName == 'none') ? '' : groupByUnit + '=' + groupByValue
        }));

        function drillDown(drillDown) {
            
            var drillDown = drillDown.split('-'); // only one drilldown per chart right now;

            var drillDownGroupByName = drillDown[0];
            var drillDownGroupByLabel = drillDown[1];

            XDMoD.TrackEvent('Usage', 'Clicked on drill-down menu item', drillDownGroupByLabel);
            
            var viewer = CCR.xdmod.ui.Viewer.getViewer();

            if (viewer.el) viewer.el.mask('Loading...');

            node.leaf = false;

            var drillNodeText = "by " + drillDownGroupByLabel;

            var nodeUIDetails = [groupByUnit + ": " + groupByValue + ""];
            var nodeTextAndDetails = drillNodeText + ' ' + groupByUnit + ": " + groupByValue + "";

            var drillNode;
            var nodeToSelect;

            var existingDrillNode = node.findChild('textanddetails', nodeTextAndDetails);

            if (existingDrillNode != null) {

                drillNode = existingDrillNode;
                nodeToSelect = drillNode;

            } else {

                var text = drillNodeText;
                var parameters = {
                    drilldowns: {}
                };

                if (node.attributes.parameters != null) {

                    for (i in node.attributes.parameters) {
                        parameters[i] = node.attributes.parameters[i];
                    }

                    parameters[groupByName] = groupById;

                    if (groupByName !== 'none') {

                        parameters.drilldowns[groupByName] = {
                            groupBy: groupById,
                            groupByName: groupByName,
                            groupByValue: groupByValue
                        };

                    } //if(groupByName !== 'none')

                } else {

                    parameters[groupByName] = groupById;

                    if (groupByName !== 'none') {

                        parameters.drilldowns[groupByName] = {
                            groupBy: groupById,
                            groupByName: groupByName,
                            groupByValue: groupByValue
                        };

                    } //if(groupByName !== 'none')

                }

                drillNode = new Ext.tree.TreeNode({

                    text: text,
                    id: 'statistic_' + realmName + '_' + groupByName + '_' + node.attributes.statistic + '_' + Math.random(),
                    statistic: node.attributes.statistic,
                    realm: realmName,
                    group_by: drillDownGroupByName,
                    query_group: queryGroupname,
                    node_type: 'statistic',
                    iconCls: "drill",
                    parameters: parameters,
                    leaf: true,
                    uiProvider: Ext.ux.tree.MultilineTreeNodeUI,
                    details: nodeUIDetails,
                    textanddetails: nodeTextAndDetails,
                    chartSettings: node.attributes.chartSettings,
                    defaultChartSettings: node.attributes.defaultChartSettings

                }); //drillNode

                nodeToSelect = node.appendChild(drillNode);

            }

            tree.expandPath(node.getPath(), null, function (success, node) {

                if (!success) {

                    if (viewer.el) viewer.el.unmask();
                    return;

                }

                tree.getSelectionModel().select(nodeToSelect);

            }); //tree.expandPath

        } //drillDown

        var role = roleCategorySelectorItems.value.split(":");

        if (!role[0] || !CCR.xdmod.ui.disabledMenus[role[0]]) {
            role = 'pub';
        } else {
            role = role[0];
        }

        var drillMenu = new CCR.xdmod.ui.DrillDownMenu({

            role: role,
            realm: realmName,
            drillDownGroupBys: drillDowns,
            handler: drillDown,
            node: node,
            valueParam: value,
            groupByIdParam: groupById,
            queryGroupname: roleCategorySelectorItems.value,
            label: (groupByName == 'none') ? null : groupByUnit + '=' + groupByValue

        }); //drillMenu

        drillMenu.showAt(Ext.EventObject.getXY());

    }, //drillChart

    // ------------------------------------------------------------------   

    selectStatistic: function (queryGroupname, permittedStats) {

        var tree = Ext.getCmp('tree_tg_usage');

        if (!tree) {
            return;
        }

        var node = tree.getSelectionModel().getSelectedNode();

        if (!node) {
            return;
        }

        var items = [];
        items.push('<b class="menu-title">Jump to metric:</b><br/>');

        /*
        if(node.parentNode && node.parentNode.attributes.node_type == 'group_by')
        {
        node.parentNode.eachChild(function(n)
        {
        if(n == node) return;
        items.push(
        new Ext.menu.Item({
        scope: this,
        text: n.text, 
        iconCls: 'chart',
        handler: function(b,e)
        {
        n.attributes.chartSettings = node.attributes.chartSettings;
        tree.getSelectionModel().select(n);
        }
        })
        );
        },this);
        }
        else if(node.parentNode && node.parentNode.attributes.node_type == 'statistic')*/
        {

            var groupbyNode = node;
            var lastStatisticNode = node;

            var newNodes = [];

            while (groupbyNode.parentNode && groupbyNode.parentNode.attributes.node_type != 'group_by') {

                lastStatisticNode = groupbyNode;
                groupbyNode = groupbyNode.parentNode;

                var newParentNode = new Ext.tree.TreeNode(Ext.apply({}, lastStatisticNode.attributes));
                newParentNode.id = lastStatisticNode.id + Math.random();
                newParentNode.leaf = false;
                newParentNode.expanded = true;

                newNodes.unshift(newParentNode);

            } //while

            var stats = permittedStats.split(',');

            groupbyNode.parentNode.eachChild(function (n) {

                if (n == groupbyNode) return;

                if (stats.indexOf(n.attributes.statistic) > 0) {

                    items.push(

                        new Ext.menu.Item({

                            scope: this,
                            text: n.text,
                            iconCls: 'chart',

                            handler: function (b, e) {

                                var on = n;

                                for (var i = 0; i < newNodes.length; i++) {

                                    var newNode = newNodes[i];

                                    var existingDrillNode = n.findChild('textanddetails', newNode.attributes.textanddetails);

                                    if (existingDrillNode != null) {

                                        tree.expandPath(existingDrillNode.getPath(), null, function (success, nn) {

                                            if (!success) {
                                                return;
                                            }

                                        });

                                        n = existingDrillNode;

                                    } else {

                                        newNode.attributes.statistic = n.attributes.statistic;
                                        n.appendChild(newNode);
                                        n.leaf = false;

                                        tree.expandPath(newNode.getPath(), null, function (success, nn) {

                                            if (!success) {
                                                return;
                                            }

                                        });

                                        n = newNode;

                                    }

                                } //for(var i = 0 ; i < newNodes.length; i++)

                                n.attributes.chartSettings = node.attributes.chartSettings;
                                tree.getSelectionModel().select(n);

                            } //handler

                        }) //new Ext.menu.Item

                    ); //items.push

                } //if(stats.indexOf(n.attributes.statistic) > 0)

            }, this); //groupbyNode.parentNode.eachChild(function(n)

        }

        if (items.length > 1) {

            var menu = new Ext.menu.Menu({
                showSeparator: false,
                items: items
            });

            menu.showAt(Ext.EventObject.getXY());

        } //if(items.length > 1)

    } //selectStatistic

}); //Ext.apply(XDMoD.Module.Usage

// ===========================================================================

Ext.extend(XDMoD.Module.Usage, XDMoD.PortalModule, {

    module_id: 'user_interface',

    usesToolbar: true,

    toolbarItems: {

        roleSelector: true,
        durationSelector: true,
        exportMenu: true,
        reportCheckbox: true

    },

    myUsage: false,
    treeDataURL: 'controllers/user_interface.php',
    chartDataURL: 'controllers/user_interface.php',

    chartDataFields: [

        'hc_jsonstore',
        'chart_url',
        'chart_map',
        'type',
        'id',
        'description',
        'group_description',
        'title',
        'params_title',
        'start_date',
        'end_date',
        //'comments',
        //'textual_legend',
        'subnotes',
        'date_description',
        'chart_args',
        'included_in_report',
        'random_id',
        'show_title',
        'short_title',
        //'legend_title',
        'filter_options',
        'reportGeneratorMeta',
        'realm',
        'group_by',
        'aggregation_unit',
        'statistic',
        'format',
        'chart_settings',
        'scale',
        'width',
        'height',
        'final_width',
        'final_height',
        'query_group'

    ],

    legend_type: 'bottom_center',
    font_size: 3,
    swap_xy: false,
    timeseries: true,

    // ------------------------------------------------------------------

    initComponent: function () {

        var self = this;

        var public_user = this.public_user || CCR.xdmod.publicUser;

        this.chartFilterSelector = new CCR.xdmod.ui.ChartFilterSelector({
        
           listeners: {
         
              migrate_to_from: function(rec_data) {
                 XDMoD.TrackEvent('Usage', 'Filter -> Removed entry from Selected list', rec_data.text);
              },

              migrate_from_to: function(rec_data) {
                 XDMoD.TrackEvent('Usage', 'Filter -> Added entry to Selected list', rec_data.text);
              },

              filters_cleared: function() {
                 XDMoD.TrackEvent('Usage', 'Filter -> Clicked on clear button in Selected list');
              }              
              
           }//listeners
         
        });//this.chartFilterSelector
  
        /*
        // Make this property public so the (Report Generator tab) drop handler can access the checkbox directly.
        this.reportCheckBox = new CCR.xdmod.ReportCheckbox({hidden: true, module: 'user_interface'});
      
        // This assignment is made so the chartStore 'load' handler can resolve the reference properly:
        var cbAvailableForReport = this.reportCheckBox;
        */

        this.realmTemplate = [
            '<b>test</b>'
        ];

        this.largeTemplate = [

            '<tpl for=".">',
            '<center>',
            '<div id="{random_id}">', '</div>',
            '</center>',
            '</tpl>'

        ]; //this.largeTemplate

        this.thumbTemplate = [

            '<tpl for=".">',
            '<div class="chart_thumb-wrap2" id="{id}">',
            '<div class="chart_thumb">',
            '<span class="chart_thumb_subtitle">{short_title}<br>{params_title}</span>',
            '<a href="javascript:XDMoD.Module.Usage.selectChildChart(' + "'" + '{id}' + "'" + ', ' + "'" + this.id + "'" + ');">',
            '<div id="{random_id}">', '</div>',
            '</a>',
            '</div>',
            '</div>',
            '</tpl>'

        ]; //this.thumbTemplate

        var commentsTemplate = new Ext.XTemplate(

            '<table class="xd-table">',
            '<tr>',
            '<td width="100%">',
            '<span class="comments_subnotes">{subnotes}</span>',
            '</td>',
            '</tr>',
            '<tr>',
            '<td width="100%">',
            '<span class="comments_description">{comments}</span>',
            '</td>',
            '</tr>',
            '</table>'

        ); //commentsTemplate

        var myUsage = this.myUsage;
        var layoutId = this.id;

        var chartScale = 1;
        var chartThumbScale = CCR.xdmod.ui.thumbChartScale;
        var chartWidth = 740;
        var chartHeight = 345;

        this.hiddenCharts = [];
        this.reloadingForRescale = false;

        var updateDisabledMenus = function (dontLoad) {

            var role = self.getRoleSelector().value.split(":");

            if (!role[0] || !CCR.xdmod.ui.disabledMenus[role[0]]) {
                role = 'pub';
            } else {
                role = role[0];
            }

            var selectedNode = tree.getSelectionModel().getSelectedNode();
            var selectFirst = false;

            tree.getRootNode().cascade(function (n) {

                var disableNode = false;

                for (var i = 0; i < CCR.xdmod.ui.disabledMenus[role].length && !disableNode; i++) {
                    disableNode = n.attributes.group_by == CCR.xdmod.ui.disabledMenus[role][i]['group_by'] && n.attributes.realm == CCR.xdmod.ui.disabledMenus[role][i]['realm'];
                }

                if (disableNode) {

                    selectFirst = n.isSelected() || selectFirst || (selectedNode && n.contains(selectedNode));
                    n.wasExpanded = n.isExpanded();
                    n.collapse(false);
                    n.disable();
                    n.wasNotLeaf = !n.leaf;
                    n.leaf = true;

                } else {

                    n.enable();

                    if (n.wasExpanded) {
                        n.expand(false);
                        n.wasExpanded = false;
                    }

                    n.leaf = n.wasNotLeaf && n.childNodes == [];

                }

            }, this); //tree.getRootNode().cascade		

            if (selectFirst) {

                var root = tree.getRootNode();

                if (root.hasChildNodes()) {

                    var child = root.findChildBy(function (n) {
                        return !n.disabled;
                    }, this);
                    if (child) tree.getSelectionModel().select(child);

                }

            } else
            if (dontLoad == undefined) reloadChartStore.call(this);

        } //updateDisabledMenus

        // ---------------------------------------------------------

            function reloadTree() {

                var selModel = tree.getSelectionModel();
                var selNode = selModel.getSelectedNode();

                if (selNode) selModel.unselect(selNode, true);

                tree.root.removeAll(true);
                tree.loader.on('load', selectFirstNode, this, {
                    single: true
                });
                tree.loader.load(tree.root);

            } //reloadTree

            // ---------------------------------------------------------		

        var treeTb = new Ext.Toolbar({

            items: [

                '->',

                {
                    iconCls: 'icon-collapse-all',
                    tooltip: 'Collapse all tree nodes',
                    handler: function () {
                        XDMoD.TrackEvent('Usage', 'Clicked on Collapse all tool above tree panel');
                        tree.root.collapse(true);
                    },
                    scope: this
                },

                {
                    iconCls: 'refresh',
                    tooltip: 'Refresh tree and clear drilldown nodes',
                    handler: function() {
                        XDMoD.TrackEvent('Usage', 'Clicked on Refresh tool above tree panel');
                        reloadTree();
                    },
                    scope: this
                }

            ]

        }); //treeTb

        // ---------------------------------------------------------

        var treeLoader = new Ext.tree.TreeLoader({

            dataUrl: this.treeDataURL,

            baseParams: {
                operation: 'get_menus',
                public_user: public_user
            },

            listeners: {

                'beforeload': {

                    fn: function (treeLoader, node) {

                        //treeLoader.baseParams.active_role = self.getRoleSelector().value;
                        treeLoader.baseParams.query_group = 'tg_usage';

                        if (node.attributes.realm) {
                            treeLoader.baseParams.realm = node.attributes.realm;
                        }

                        if (node.attributes.group_by) {
                            treeLoader.baseParams.group_by = node.attributes.group_by;
                        }

                    }, //fn

                    scope: this

                }, //beforeload

                'load': {

                    fn: function (treeLoader, node, response) {

                        var resp = Ext.decode(response.responseText);

                        if (resp.message) {

                            if (resp.message == 'Session Expired') {

                                Ext.MessageBox.alert("Error", resp.message);
                                CCR.xdmod.ui.actionLogout.defer(1000);

                            }

                        } //if (resp.message)

                    }, //fn

                    scope: this

                } //load

            } //listeners

        }); //treeLoader

        // ---------------------------------------------------------

        var tree = new Ext.tree.TreePanel({

            id: 'tree_' + this.id,
            useArrows: true,
            autoScroll: true,
            animate: false,
            enableDD: false,
            loader: treeLoader,

            root: {
                nodeType: 'async',
                //text: roleCategorySelectorItems[0].text,
                draggable: false,
                id: 'realm_', //set this to 'realms' to see all realms
                realm: CCR.xdmod.ui.enabledRealms, //
                filter: false
            },

            rootVisible: false,
            tbar: treeTb,

            // collapsible: true,
            region: 'center',
            width: 325,
            split: true,
            // title: 'Metrics',

            containerScroll: true,
            margins: '0 0 0 0',
            border: false,
            
            listeners: {
            
               expandnode: function(n) {
               
                  XDMoD.TrackEvent('Usage', 'Expanded item in tree panel', n.getPath('text'));

               },//expandnode
               
               collapsenode: function(n) {
               
                  XDMoD.TrackEvent('Usage', 'Collapsed item in tree panel', n.getPath('text'));

               }//collapsenode
               
            }//listeners

        });//tree

        // ---------------------------------------------------------

        var paramDescriptionPanel = new Ext.Panel({

            region: 'north',
            //split: true,
            height: 30,
            autoScroll: false,
            border: false

        }); //paramDescriptionPanel

        // ---------------------------------------------------------

        this.chartTitleField = new Ext.form.TextField({

            fieldLabel: 'Title',
            name: 'title',
            emptyText: 'Chart Title',
            validationDelay: 1000,
            enableKeyEvents: true,

            listeners: {

                scope: this,

                change: function (t, n, o) {
                
                    var selectedNode = tree.getSelectionModel().getSelectedNode();
                    
                    if (n != o && selectedNode.attributes.title != n) {

                        XDMoD.TrackEvent('Usage', 'Updated title', t.getValue());

                        selectedNode.attributes.title = n;
                        reloadChartStore.call(this, 999);
                    }

                }, //change

                specialkey: function (t, e) {
                    var selectedNode = tree.getSelectionModel().getSelectedNode();
                    if (t.isValid(false) && e.getKey() == e.ENTER && selectedNode.attributes.title !== t.getValue()) {
                        XDMoD.TrackEvent('Usage', 'Updated title', t.getValue());
                        selectedNode.attributes.title = t.getValue();
                        reloadChartStore.call(this, 999);
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
                    ['bottom_center', 'Bottom Center'],
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

                    XDMoD.TrackEvent('Usage', 'Updated legend placement', Ext.encode({legend_type: record.get('id')}));

                    this.legend_type = record.get('id');
                    reloadChartStore.call(this, 999);

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

                    XDMoD.TrackEvent('Usage', 'Used the font size slider', Ext.encode({font_size: t.getValue()}));

                    this.font_size = t.getValue();
                    reloadChartStore.call(this, 999);

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

                'check': function (checkbox, check) {

                    this.swap_xy = check;
                    reloadChartStore.call(this, 999);

                } //check

            } //listeners

        }); //this.chartSwapXYField

        // ---------------------------------------------------------		

        var leftPanel = new Ext.Panel({

            split: true,
            collapsible: true,
            //collapseMode: 'mini',
            //header: false,
            width: 375,
            layout: 'border',
            region: 'west',
            margins: '2 0 2 2',
            border: true,

            items: [{

                    xtype: 'form',
                    layout: 'fit',
                    region: 'north',
                    height: 90,
                    border: false,
                    items: [

                        {

                            xtype: 'fieldset',
                            header: false,
                            layout: 'form',
                            hideLabels: false,
                            border: false,

                            defaults: {
                                anchor: '0' // '-20' // leave room for error icon
                            },

                            items: [

                                //this.datasetTypeRadioGroup,
                                this.chartTitleField,
                                this.legendTypeComboBox,
                                this.fontSizeSlider
                                //this.chartSwapXYField

                            ] //items

                        }

                    ] //items

                },
                tree
            ],

            plugins: new Ext.ux.collapsedPanelTitlePlugin()

        }); //leftPanel

        // ---------------------------------------------------------		

        function selectFirstNode() {

            updateDisabledMenus.call(this, true);

            var node = tree.getSelectionModel().getSelectedNode();

            if (node != null) return;

            var root = tree.getRootNode();

            if (root.hasChildNodes()) {

                var child = root.findChildBy(function (n) {
                    return !n.disabled;
                }, this);

                if (child) {

                    tree.getSelectionModel().select(child);

                    tree.expandPath(child.getPath(), null, function (success, node) {

                    });

                } //if(child)

            } //if (root.hasChildNodes())

        } //selectFirstNode

        // ---------------------------------------------------------	

        tree.loader.on('load', selectFirstNode, this, {
            buffer: 500,
            single: true
        });

        tree.on('expandnode', function (node) {

            if (node.attributes.node_type == 'group_by') {

                if (node.attributes.chartSettings) {

                    node.eachChild(function (n) {

                        n.attributes.chartSettings = node.attributes.chartSettings;
                        return true;

                    }, this);

                } //if(node.attributes.chartSettings)

                if (node.attributes.filter) {

                    node.eachChild(function (n) {

                        n.attributes.filter = node.attributes.filter;
                        return true;

                    }, this);

                } //if(node.attributes.filter)

            } //if(node.attributes.node_type == 'group_by')

        }, this);

        // ---------------------------------------------------------	

        var THIS = this;

        function getChartParameters(n) {

            var newStartDate = self.getDurationSelector().getStartDate().format('Y-m-d');
            var newEndDate = self.getDurationSelector().getEndDate().format('Y-m-d');
            var aggregationUnit = self.getDurationSelector().getAggregationUnit();

            var parameters = {

                public_user: public_user,
                realm: n.attributes.realm,
                group_by: n.attributes.group_by,
                statistic: n.attributes.statistic,
                start_date: newStartDate,
                end_date: newEndDate,
                timeframe_label: self.getDurationSelector().getDurationLabel(),
                scale: 1,
                aggregation_unit: aggregationUnit,
                dataset_type: chartToolbar.getDatasetType(),
                thumbnail: 'n',
                query_group: self.getRoleSelector().value + '_usage',
                display_type: chartToolbar.getDisplayType(),
                combine_type: chartToolbar.getDataCombineMethod(),
                limit: chartToolbar.getLimit(),
                offset: chartToolbar.getOffset(),
                // show_legend: chartToolbar.getShowLegend(),
                log_scale: chartToolbar.getLogScale(),
                show_guide_lines: chartToolbar.getShowGuideLines(),
                show_trend_line: chartToolbar.getShowTrendLine(),
                show_error_bars: chartToolbar.getShowErrorBars(),
                show_aggregate_labels: chartToolbar.getShowAggregateLabels(),
                show_error_labels: chartToolbar.getShowErrorLabels(),
                show_title: 'y',
                width: chartWidth * chartScale,
                height: chartHeight * chartScale,
                legend_type: THIS.legendTypeComboBox.getValue(),
                font_size: THIS.fontSizeSlider.getValue()

            }; //parameters
            if (n.attributes.title) {
                parameters['title'] = n.attributes.title;
            }
            if (n.attributes.filter)
                parameters[n.attributes.group_by + '_filter'] = n.attributes.filter;

            if (n.attributes.parameters != null) {

                for (i in n.attributes.parameters) {
                    parameters[i] = n.attributes.parameters[i];
                }

            } //if (n.attributes.parameters != null)



            return parameters;

        } //getChartParameters

        // ---------------------------------------------------------

        function getMenuParameters(n) {

            var newStartDate = self.getDurationSelector().getStartDate().format('Y-m-d');
            var newEndDate = self.getDurationSelector().getEndDate().format('Y-m-d');
            var aggregationUnit = self.getDurationSelector().getAggregationUnit();

            var parameters = {

                public_user: public_user,
                realm: n.attributes.realm,
                group_by: n.attributes.group_by,
                start_date: newStartDate,
                end_date: newEndDate,
                timeframe_label: self.getDurationSelector().getDurationLabel(),
                aggregation_unit: aggregationUnit,
                width: CCR.xdmod.ui.thumbWidth * chartThumbScale,
                height: CCR.xdmod.ui.thumbHeight * chartThumbScale,
                scale: 1,
                dataset_type: chartToolbar.getDatasetType(),
                thumbnail: 'y',
                query_group: self.getRoleSelector().value + '_usage',
                display_type: chartToolbar.getDisplayType(),
                combine_type: chartToolbar.getDataCombineMethod(),
                limit: chartToolbar.getLimit(),
                offset: chartToolbar.getOffset(),
                //show_legend: chartToolbar.getShowLegend(),
                log_scale: chartToolbar.getLogScale(),
                show_guide_lines: chartToolbar.getShowGuideLines(),
                show_trend_line: chartToolbar.getShowTrendLine(),
                show_error_bars: chartToolbar.getShowErrorBars(),
                show_aggregate_labels: chartToolbar.getShowAggregateLabels(),
                show_error_labels: chartToolbar.getShowErrorLabels(),
                //show_guide_lines: 'n',
                format: 'session_variable', //session_variable for method 3, params for 1 and 2,
                legend_type: 'off', //THIS.legendTypeComboBox.getValue(),
                font_size: THIS.fontSizeSlider.getValue() - 3,
                show_title: 'n'

            }; //parameters

            if (n.attributes.filter) parameters[n.attributes.group_by + '_filter'] = n.attributes.filter;

            return parameters;

        } //getMenuParameters

        // ---------------------------------------------------------

        function onSelectNode(model, n) {

            if (!n || !n.text) return;
            if (!self.getDurationSelector().validate()) return;

            Ext.History.un('change', onHistoryChange);
            Ext.History.add(layoutId + CCR.xdmod.ui.tokenDelimiter + n.id, true);
            Ext.History.on('change', onHistoryChange);

            chartStore.loadStartTime = new Date().getTime();
            images.setTitle(n.getPath('text'));
            //descriptionPanel.setTitle(chartToolbar.getStatus());

            var viewer = CCR.xdmod.ui.Viewer.getViewer();

            if (viewer.el) viewer.el.mask('Loading...');
            self.getDurationSelector().disable();
            chartToolbar.disable();

            if (n.attributes.chartSettings) {
                chartToolbar.fromJSON(n.attributes.chartSettings);
            } else {
                chartToolbar.resetValues();
            }

            this.chartTitleField.setValue(n.attributes.title ? n.attributes.title : '');

            this.chartFilterSelector.loadData([], 'item', 'Item');

            if (chartToolbar.getDisplayType() == 'jobs') {

                images.setTitle('/' + self.getRoleSelector().getText() + '/Jobs');

                viewPanel.getLayout().setActiveItem(2);

                if (this.reloadingForRescale) return;

                var restoreTool = images.getTool('restore');
                if (restoreTool) restoreTool.hide();

                var tool = images.getTool('maximize');
                if (tool) tool.hide();

                var tool = images.getTool('plus');
                if (tool) tool.hide();

                var tool = images.getTool('minus');
                if (tool) tool.hide();

                var tool = images.getTool('gear');
                if (tool) tool.hide();

                var parameters = [];

                if (n.attributes.node_type == 'statistic') {
                    parameters = getChartParameters(n);
                } else if (n.attributes.node_type == 'group_by') {
                    parameters = getMenuParameters(n);
                }

                var newStartDate = self.getDurationSelector().getStartDate();
                var newEndDate = self.getDurationSelector().getEndDate();

                var durationDays = (newEndDate - newStartDate) / (60 * 60 * 24 * 1000);

                if (durationDays > 31) {

                    Ext.MessageBox.alert('Jobs Query', 'The duration of the jobs search cannot be longer than 31 days. This search will only show jobs for the 31 days before the selected end date.');
                    newStartDate = new Date(newEndDate - 60 * 60 * 24 * 31 * 1000);
                    parameters['start_date'] = newStartDate.format('Y-m-d');
                    //self.getDurationSelector().setValues(newStartDate.format('Y-m-d'), newEndDate.format('Y-m-d'));

                } //if(durationDays > 31)

                parameters['operation'] = 'get_param_descriptions';
                //parameterDescriptionStore.load({params: parameters});

                parameters['format'] = 'jsonstore';
                parameters['operation'] = 'get_jobs';
                //parameters.limit = null;
                //parameters.offset = null;

                detailsGrid.store.proxy.setUrl('controllers/user_interface.php', true);
                detailsGrid.store.baseParams = parameters;

                detailsGrid.store.load();
                detailsGrid.store.on('load', function (store) {

                    if (store.getCount() <= 0) {

                        var viewer = CCR.xdmod.ui.Viewer.getViewer();
                        if (viewer.el) viewer.el.unmask();

                        viewer.el.unmask();
                        return;

                    } //if (store.getCount() <= 0)

                    // updateDescription(store.reader.jsonData.message, '', '', false, store.reader.jsonData.subnotes );
                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                    if (viewer.el) viewer.el.unmask();

                    self.getDurationSelector().enable();
                    chartToolbar.enable();

                    /*
                    var filterOptions = Ext.util.JSON.decode(store.reader.jsonData.filter_options) ;
                    this.chartFilterSelector.loadData(filterOptions, n.attributes.group_by, n.attributes.group_by_label);
                    */

                    images.setTitle('/' + self.getRoleSelector().getText() + '/Jobs ' + store.reader.jsonData.start_date + ' to ' + store.reader.jsonData.end_date);

                    self.getExportMenu().menu.items.each(function (item, index, length) {
                        if (item.iconCls == 'png') item.disable();
                    }, this);

                    if (public_user != true)
                        self.getReportCheckbox().hide();

                }, this, {
                    single: true
                }); //detailsGrid.store.on('load', ...

            } //if (chartToolbar.getDisplayType() == 'jobs')
            else {

                if (chartToolbar.getDisplayType() == 'datasheet') {

                    if (n.attributes.node_type == 'realm') {

                        //TODO
                        var viewer = CCR.xdmod.ui.Viewer.getViewer();
                        if (viewer.el) viewer.el.unmask();
                        self.getDurationSelector().enable();
                        chartToolbar.enable();
                        return;

                    } //if (n.attributes.node_type == 'realm')

                    viewPanel.getLayout().setActiveItem(1);
                    if (this.reloadingForRescale) return;

                    var restoreTool = images.getTool('restore');
                    if (restoreTool) restoreTool.hide();

                    var tool = images.getTool('maximize');
                    if (tool) tool.hide();

                    var tool = images.getTool('plus');
                    if (tool) tool.hide();

                    var tool = images.getTool('minus');
                    if (tool) tool.hide();

                    var tool = images.getTool('gear');
                    if (tool) tool.hide();

                    this.fontSizeSlider.disable();
                    this.legendTypeComboBox.disable();


                    var parameters = [];

                    if (n.attributes.node_type == 'statistic') {
                        parameters = getChartParameters(n);
                    } else if (n.attributes.node_type == 'group_by') {
                        parameters = getMenuParameters(n);
                    }

                    //parameters['operation'] = 'get_param_descriptions';
                    //parameterDescriptionStore.load({params: parameters});

                    parameters['format'] = 'jsonstore';
                    parameters['operation'] = 'get_data';

                    viewGrid.store.proxy.setUrl('controllers/user_interface.php', true);
                    viewGrid.store.load({
                        params: parameters
                    });

                    viewGrid.store.on('load', function (store) {

                        if (store.getCount() <= 0) {

                            var viewer = CCR.xdmod.ui.Viewer.getViewer();
                            if (viewer.el) viewer.el.unmask();
                            viewer.el.unmask();
                            return;

                        } //if (store.getCount() <= 0)

                        //var tpl = new Ext.XTemplate ('<h1>'+self.getRoleSelector().getText()+'\'s Usage</h1>');
                        //tpl.overwrite(paramDescriptionPanel.body);

                        leftPanel.setTitle(self.getRoleSelector().getText() + '\'s View');
                        updateDescription(store.reader.jsonData.message, store.reader.jsonData.subnotes);

                        var viewer = CCR.xdmod.ui.Viewer.getViewer();
                        if (viewer.el) viewer.el.unmask();
                        self.getDurationSelector().enable();
                        chartToolbar.enable();

                        var filterOptions = Ext.util.JSON.decode(store.reader.jsonData.filter_options);
                        this.chartFilterSelector.loadData(filterOptions, n.attributes.group_by, n.attributes.group_by_label);

                        self.getExportMenu().menu.items.each(function (item, index, length) {
                            if (item.iconCls == 'png') item.disable();
                        }, this);

                        if (public_user != true)
                            self.getReportCheckbox().hide();

                    }, this, {
                        single: true
                    });

                } else {

                    viewPanel.getLayout().setActiveItem(0);
                    this.fontSizeSlider.enable();

                    if (n.attributes.node_type == 'realm') {

                        //TODO
                        //have a store and hit the store to give you summary
                        view.tpl = largeRealmTemplate;
                        view.refresh();
                        self.getDurationSelector().enable();
                        chartToolbar.disable();
                        this.legendTypeComboBox.disable();
                        this.chartTitleField.disable();

                        var viewer = CCR.xdmod.ui.Viewer.getViewer();
                        if (viewer.el) viewer.el.unmask();

                        var loadTime = (new Date().getTime() - chartStore.loadStartTime) / 1000.0;
                        CCR.xdmod.ui.toastMessage('Load Menu', 'Complete in ' + loadTime + 's');

                    } else if (n.attributes.node_type == 'statistic') {

                        view.tpl = largeChartTemplate;
                        var parameters = getChartParameters(n);
                        this.legendTypeComboBox.enable();
                        this.chartTitleField.enable();

                        parameters['interactive_elements'] = 'y';
                        chartStore.removeAll(true);

                        //var restoreTool = images.getTool('restore');
                        //if (restoreTool) restoreTool.hide();

                        XDMoD.TrackEvent('Usage', 'Selected Statistic Via Tree', n.getPath('text'));
                        
                        parameters['operation'] = 'get_charts';
                        chartStore.load({
                            params: parameters
                        });

                        //parameters['operation'] = 'get_param_descriptions';
                        //parameterDescriptionStore.load({params: parameters});

                    } else if (n.attributes.node_type == 'group_by') {

                        view.tpl = thumbnailChartTemplate;
                        var parameters = getMenuParameters(n);
                        chartStore.removeAll(true);
                        maximizeScale.call(this);
                        this.legendTypeComboBox.disable();
                        this.chartTitleField.disable();

                        parameters['operation'] = 'get_charts';
                        chartStore.load({
                            params: parameters
                        });
                        //parameters['operation'] = 'get_param_descriptions'; 
                        //parameterDescriptionStore.load({params: parameters});

                        chartStore.on('load', function (chartStore) {

                            if (chartStore.getCount() <= 0) {

                                var viewer = CCR.xdmod.ui.Viewer.getViewer();
                                if (viewer.el) viewer.el.unmask();
                                viewer.el.unmask();
                                return;

                            } //if (chartStore.getCount() <= 0)

                            leftPanel.setTitle(self.getRoleSelector().getText() + '\'s View');

                            var legend = '<ul>';
                            legend += '<li>' + chartStore.getAt(0).get('group_description') + '</li>';

                            for (var i = 0; i < chartStore.getCount(); i++) {
                                legend += '<li>' + chartStore.getAt(i).get('description') + '</li>';
                            }

                            legend += '</ul>';
                            updateDescription(legend, chartStore.getAt(0).get('subnotes'));

                            var filterOptions = Ext.util.JSON.decode(chartStore.getAt(0).get('filter_options'));
                            this.chartFilterSelector.loadData(filterOptions, n.attributes.group_by, n.attributes.group_by_label);

                            var chartSettings = Ext.util.JSON.decode(chartStore.getAt(0).get('chart_settings').replace(/`/g, '"'));

                            n.attributes.chartSettings = chartStore.getAt(0).get('chart_settings').replace(/`/g, '"');

                            if (!n.attributes.defaultChartSettings) n.attributes.defaultChartSettings = chartSettings;
                            chartToolbar.fromJSON(n.attributes.chartSettings);

                            XDMoD.TrackEvent('Usage', 'Selected Chart Category Via Tree', n.getPath('text'));

                            var viewer = CCR.xdmod.ui.Viewer.getViewer();
                            if (viewer.el) viewer.el.unmask();

                            if (this.charts) {

                                for (var i = 0; i < this.charts.length; i++) {
                                    delete this.charts[i];
                                }

                                delete this.charts;

                            } //if(this.charts)

                            this.charts = [];

                            var ind = 0;

                            chartStore.each(function (r) {

                                var id = r.get('random_id');
                                var el = Ext.get(id); // Get Ext.Element object

                                var task = new Ext.util.DelayedTask(function () {

                                    var baseChartOptions = {

                                        chart: {

                                            renderTo: id,
                                            width: CCR.xdmod.ui.thumbWidth * chartThumbScale,
                                            height: CCR.xdmod.ui.thumbHeight * chartThumbScale,
                                            animation: true,

                                            events: {

                                                load: function (e) {

                                                    if (this.series.length == 0) {

                                                        this.renderer.image('gui/images/report_thumbnail_no_data.png', 0, 0, this.chartWidth, this.chartHeight).add();

                                                    } //if (this.series.length == 0)

                                                } //load

                                            } //events

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
                                    chartOptions.credits.enabled = false;

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

                                    this.charts.push(new Highcharts.Chart(chartOptions));

                                }, this); //task

                                task.delay(0);

                                return true;

                            }, this); //chartStore.each(function(r)

                            self.getDurationSelector().enable();
                            chartToolbar.enable();

                            var restoreTool = images.getTool('restore');
                            if (restoreTool) restoreTool.show();

                            var tool = images.getTool('maximize');
                            if (tool) tool.hide();

                            var tool = images.getTool('plus');
                            if (tool) tool.show();

                            var tool = images.getTool('minus');
                            if (tool) tool.show();

                            var tool = images.getTool('gear');
                            if (tool) tool.hide();

                            self.getExportMenu().menu.items.each(function (item, index, length) {
                                if (item.iconCls == 'png') item.disable();
                            }, this);

                            if (public_user != true)
                                self.getReportCheckbox().hide();

                            var loadTime = (new Date().getTime() - chartStore.loadStartTime) / 1000.0;

                            CCR.xdmod.ui.toastMessage('Load Menu', 'Complete in ' + loadTime + 's');
                            this.reloadingForRescale = false;

                        }, this, {
                            single: true
                        }); //chartStore.on('load', ...

                    } //if (n.attributes.node_type == 'group_by')

                } //if (chartToolbar.getDisplayType() != 'datasheet')

            } //if (chartToolbar.getDisplayType() != 'jobs')

        } //onSelectNode


        // ---------------------------------------------------------

        var thumbnailChartTemplate = new Ext.XTemplate(this.thumbTemplate);
        var largeChartTemplate = new Ext.XTemplate(this.largeTemplate);
        var largeRealmTemplate = new Ext.XTemplate(this.realmTemplate);

        var chartStore = new Ext.data.JsonStore({

            updaters: [],
            storeId: 'chart_store_' + this.id,
            autoDestroy: false,
            root: 'data',
            totalProperty: 'totalCount',
            successProperty: 'success',
            messageProperty: 'message',
            fields: this.chartDataFields,

            baseParams: {

                operation: 'get_charts',
                public_user: public_user,
                controller_module: self.module_id

            },

            proxy: new Ext.data.HttpProxy({

                method: 'POST',
                url: this.chartDataURL

            })

        }); //chartStore

        // ---------------------------------------------------------

        chartStore.on('exception', function (dp, type, action, opt, response, arg) {

            if (response.success !== true) {

                Ext.MessageBox.alert("Error", response.message || 'Unknown Error');

                if (response.message == 'Session Expired') {

                    CCR.xdmod.ui.actionLogout.defer(1000);

                } else {

                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                    if (viewer.el) viewer.el.unmask();

                }

            } //if (response.success !== true)

        }, this);

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

            tool = images.getTool('gear');
            if (tool && CCR.xdmod.ui.usageExplorer) tool.show();

            tool = images.getTool('restore');
            if (tool) tool.show();

            tool = images.getTool('maximize');
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

            tool = images.getTool('gear');
            if (tool) tool.hide();

            tool = images.getTool('restore');
            if (tool) tool.hide();

        }, chartStore, {
            single: true
        });

        // ---------------------------------------------------------

        var view = new Ext.DataView({

            id: 'view_chart_' + this.id,
            title: 'Chart',
            loadingText: "Loading...",
            itemSelector: 'div.single-chart-container',
            style: 'overflow:auto',
            multiSelect: true,
            store: chartStore,
            autoScroll: true,
            tpl: largeChartTemplate

        }); //view

        // ---------------------------------------------------------

        if (public_user != true) {

            view.on('click', resetDropStyling);

            view.on('afterrender', function () {
                new ImageDragZone(view, {
                    ddGroup: 'singleChartDD'
                });
            });

        } //if(public_user != true)

        // ---------------------------------------------------------

        var viewGrid = new Ext.ux.DynamicGridPanel({

            id: 'view_grid_' + this.id,
            storeUrl: this.chartDataURL,
            autoScroll: true,
            rowNumberer: true

        }); //viewGrid

        // ---------------------------------------------------------

        var detailsGrid = new Ext.ux.DynamicGridPanel({

            id: 'job_grid_' + this.id,
            storeUrl: 'controllers/user_interface.php',
            autoScroll: true,
            rowNumberer: true,
            usePaging: true

        }); //detailsGrid

        // ---------------------------------------------------------

        var descriptionPanel = new Ext.Panel({

            region: 'south',
            autoScroll: true,
            collapsible: true,
            split: true,
            border: true,
            title: 'Description',
            height: 120,
            plugins: [new Ext.ux.collapsedPanelTitlePlugin()]

        }); //descriptionPanel

        // ---------------------------------------------------------

        function updateDescription(comments, subNotes) {

            commentsTemplate.overwrite(descriptionPanel.body, {
                'comments': comments,
                'subnotes': subNotes
            });

        }; //updateDescription

        // ---------------------------------------------------------

        self.on('export_option_selected', function (opts) {

            var selectedNode = tree.getSelectionModel().getSelectedNode();

            if (selectedNode != null) {

                var parameters = {};

                if (selectedNode.attributes.node_type == 'statistic') {

                    parameters = getChartParameters(selectedNode);

                } else {

                    if (selectedNode.attributes.node_type == 'group_by') {
                        parameters = getMenuParameters(selectedNode);
                    }

                }

                Ext.apply(parameters, opts);

                parameters['operation'] = 'get_data';

                CCR.invokePost("controllers/user_interface.php", parameters);

            } //if (selectedNode != null)

        }); //self.on('export_option_selected', ...

        // ---------------------------------------------------------

        var chartToolbar = new CCR.xdmod.ui.ChartToolbar({

            id: 'chart_toolbar_' + layoutId,
            handler: reloadChartFunc,

            resetFunction: function () {

                var selectedNode = tree.getSelectionModel().getSelectedNode();
                selectedNode.attributes.chartSettings = selectedNode.attributes.defaultChartSettings;
                tree.getSelectionModel().unselect(selectedNode, true);
                tree.getSelectionModel().select(selectedNode);

            },//resetFunction

            listeners: {
            
               chart_limit_field_updated: function(newValue) {

                  XDMoD.TrackEvent('Usage', 'Updated dataset limit', newValue);
                  
               }//chart_limit_field_updated
            
            }//listeners
            
        }); //chartToolbar

        // ---------------------------------------------------------

        function maximizeScale() {

            chartWidth = view.getWidth();
            chartHeight = view.getHeight() - (images.tbar ? images.tbar.getHeight() : 0);
			
            var model = tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node != null) {

                if (node.attributes.node_type == 'statistic') {
                    this.reloadingForRescale = true;
                }

            } //if (node != null)

        }; //maximizeScale

        // ---------------------------------------------------------

        function reloadChartFunc(chartSettingsObject) {

            var model = tree.getSelectionModel();
            var node = model.getSelectedNode();

            if (node != null) {

                if (chartSettingsObject) node.attributes.chartSettings = chartSettingsObject;

                if (node.attributes.node_type == 'group_by') {

                    node.eachChild(function (n) {

                        n.attributes.chartSettings = node.attributes.chartSettings || undefined;
                        return true;

                    }, this);

                    node.eachChild(function (n) {

                        n.attributes.filter = node.attributes.filter || undefined;
                        return true;

                    }, this);

                } //if(node.attributes.node_type == 'group_by')

                tree.getSelectionModel().unselect(node, true);
                tree.getSelectionModel().select(node);

            } //if (node != null)

        } //reloadChartFunc

        // ---------------------------------------------------------

        var reloadChartTask = new Ext.util.DelayedTask(reloadChartFunc, this);

        // ---------------------------------------------------------

        var reloadChartStore = function (delay) {

            reloadChartTask.delay(delay || 0);

        }; //reloadChartStore

        // ---------------------------------------------------------

        var viewGridPanel = new Ext.Panel({

            layout: 'fit',
            items: viewGrid

        }); //viewGridPanel

        // ---------------------------------------------------------

        var detailsGridPanel = new Ext.Panel({

            layout: 'fit',
            items: detailsGrid

        }); //detailsGridPanel

        // ---------------------------------------------------------

        var viewPanel = new Ext.Panel({

            frame: false,
            border: true,
            layout: 'card',
            activeItem: 0, // make sure the active item is set on the container config!
            region: 'center',

            items: [
                view,
                viewGridPanel,
                detailsGridPanel
            ]

        }); //viewPanel

        // ---------------------------------------------------------

        self.on('role_selection_change', function (b) {

            tree.getRootNode().setText(b.text);
            updateDisabledMenus.call(this);

        });

        self.on('duration_change', function (d) {

            reloadChartStore();

        });

        // ---------------------------------------------------------

        var legendPanel = new Ext.Panel({

            width: 220,
            collapsed: true,
            collapsible: true,
            collapseMode: 'mini',
            hideCollapseTool: true,
            region: 'east',
            split: true,
            margins: '0 0 0 1',
            autoScroll: true

        }); //legendPanel

        // ---------------------------------------------------------

        var images = new Ext.Panel({

            title: 'Viewer',
            region: 'center',
            margins: '2 1 2 0',
            layout: 'border',
            scope: this,
            //border: false,
            items: [viewPanel, descriptionPanel],

            tools: [

                {

                    id: 'maximize',
                    qtip: 'Resize Chart To Fit',
                    hidden: true,
                    scope: this,
                    handler: function () {

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if (node.attributes.node_type == 'statistic') {
                                maximizeScale.call(this);
                            }

                            onSelectNode.call(this, model, node);

                        } //if (node != null)

                    } //handler

                },

                {

                    id: 'restore',
                    qtip: 'Restore Chart Size',
                    hidden: true,
                    scope: this,
                    handler: function () {

                        XDMoD.TrackEvent('Usage', 'Clicked on the Restore Chart Size tool');

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if (node.attributes.node_type == 'statistic') {
                                chartScale = 1.0;
                            } else if (node.attributes.node_type == 'group_by') {
                                chartThumbScale = CCR.xdmod.ui.thumbChartScale;
                            }

                            this.reloadingForRescale = true;
                            onSelectNode.call(this, model, node);

                        } //if (node != null)

                    } //handler

                },

                {

                    id: 'minus',
                    qtip: 'Reduce Chart Size',
                    hidden: true,
                    scope: this,
                    handler: function () {

                        XDMoD.TrackEvent('Usage', 'Clicked on the Reduce Chart Size tool');

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                           if (node.attributes.node_type == 'group_by') {
                            if ((chartThumbScale - CCR.xdmod.ui.deltaThumbChartScale) > CCR.xdmod.ui.minChartScale) 
							{
								chartThumbScale -= CCR.xdmod.ui.deltaThumbChartScale;
								this.reloadingForRescale = true;
								onSelectNode.call(this, model, node);
							}
						   }

                        } //if (node != null)

                    }

                },

                {

                    id: 'plus',
                    qtip: 'Increase Chart Size',
                    hidden: true,
                    scope: this,
                    handler: function () {

                        XDMoD.TrackEvent('Usage', 'Clicked on the Increase Chart Size tool');

                        var model = tree.getSelectionModel();
                        var node = model.getSelectedNode();

                        if (node != null) {

                            if (node.attributes.node_type == 'group_by') {
                                if ((chartThumbScale + CCR.xdmod.ui.deltaThumbChartScale) < CCR.xdmod.ui.maxChartScale) 
								{
									chartThumbScale += CCR.xdmod.ui.deltaThumbChartScale;
									this.reloadingForRescale = true;
                            		onSelectNode.call(this, model, node);
								}
                            }

                           

                        } ////if (node != null)

                    } //handler

                },

                {

                    id: 'gear',
                    qtip: 'Configure in Usage Explorer',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        XDMoD.TrackEvent('Usage', 'Clicked on the Configure in Usage Explorer tool');

                        var n = tree.getSelectionModel().getSelectedNode();
                        if (!n) return; //if nothing selected

                        var dt = chartToolbar.getDisplayType();
                        var ct = chartToolbar.getDataCombineMethod();

                        var config = {

                            active_role: self.getRoleSelector().value,
                            timeseries: chartToolbar.getDatasetType() == 'timeseries',
                            title: chartStore.getAt(0).get('title'),
                            legend_type: this.legendTypeComboBox.getValue(),
                            font_size: this.fontSizeSlider.getValue(),
                            show_filters: true,
                            swap_xy: dt == 'h_bar',

                            data_series: {

                                data: [

                                    {
                                        id: Math.random(),
                                        metric: n.attributes.statistic,
                                        realm: n.attributes.realm,
                                        group_by: n.attributes.group_by,
                                        x_axis: false,
                                        log_scale: chartToolbar.getLogScale() == 'y',
                                        has_std_err: 'y',
                                        std_err: chartToolbar.getShowErrorBars() == 'y',
                                        value_labels: chartToolbar.getShowAggregateLabels() == 'y' || dt == 'pie',
                                        trend_line: chartToolbar.getShowTrendLine() == 'y',
                                        display_type: (dt == 'bar' || dt == 'h_bar' /*|| dt == 'pie'*/ || dt == 'auto') ? "column" : dt,
                                        combine_type: (ct == 'side' || ct == 'auto') ? "side" : ct == 'percentage' ? 'percent' : 'stack',
                                        sort_type: "value_desc",
                                        filters: {
                                            "data": [],
                                            "total": 0
                                        },
                                        ignore_global: false,
                                        long_legend: true
                                    }

                                ],

                                total: 1

                            },

                            aggregation_unit: self.getDurationSelector().getAggregationUnit(),

                            global_filters: {
                                data: [],
                                total: 0
                            },

                            start_date: self.getDurationSelector().getStartDate().format('Y-m-d'),
                            end_date: self.getDurationSelector().getEndDate().format('Y-m-d'),
                            start: chartToolbar.getOffset(),
                            limit: chartToolbar.getLimit()

                        }; //config 

                        //if (n.attributes.filter) parameters[n.attributes.group_by+'_filter'] = n.attributes.filter;
                        if (n.attributes.filter) {

                            var filters = n.attributes.filter.split(',');

                            for (var i = 0; i < filters.length; i++) {

                                config.global_filters.data.push({

                                    id: n.attributes.group_by + '=' + filters[i],
                                    value_id: filters[i],
                                    value_name: n.attributes.filterText[i],
                                    dimension_id: n.attributes.group_by,
                                    realms: [n.attributes.realm],
                                    checked: true

                                });

                                config.global_filters.total++;

                            } //for(var i = 0; i < filters.length; i++)

                        } //if (n.attributes.filter)

                        if (n.attributes.parameters && n.attributes.parameters.drilldowns) {

                            for (var i in n.attributes.parameters.drilldowns) {

                                config.global_filters.data.push({

                                    id: n.attributes.parameters.drilldowns[i].groupByName + '=' + n.attributes.parameters.drilldowns[i].groupBy,
                                    value_id: n.attributes.parameters.drilldowns[i].groupBy,
                                    value_name: n.attributes.parameters.drilldowns[i].groupByValue,
                                    dimension_id: n.attributes.parameters.drilldowns[i].groupByName,
                                    realms: [n.attributes.realm],
                                    checked: true

                                });

                                config.global_filters.total++;

                            } //for(var i in n.attributes.parameters.drilldowns)

                        } //if(n.attributes.parameters && n.attributes.parameters.drilldowns)

                        XDMoD.Module.UsageExplorer.setConfig(config, 'Usage:' + config.title);

                    } //handler

                },

                {

                    id: 'print',
                    qtip: 'Print',
                    hidden: true,
                    scope: this,

                    handler: function () {

                        XDMoD.TrackEvent('Usage', 'Clicked on the Print tool');

                        if (viewPanel.getLayout().activeItem == view) {

                            var selectedNode = tree.getSelectionModel().getSelectedNode();

                            if (selectedNode != null) {

                                if (selectedNode.attributes.node_type == 'group_by') {
                                    Ext.ux.Printer.print(view);
                                } else {

                                    var parameters = getChartParameters(selectedNode);

                                    parameters['operation'] = 'get_data';
                                    parameters['scale'] = 1; //CCR.xdmod.ui.hd1920cale;
                                    parameters['inline'] = 'y';
                                    parameters['format'] = 'png';
                                    parameters['width'] = 757 * 2;
                                    parameters['height'] = 400 * 2;

                                    var params = '';

                                    for (i in parameters) {
                                        params += i + '=' + parameters[i] + '&'
                                    }

                                    params = params.substring(0, params.length - 1);

                                    Ext.ux.Printer.print({

                                        getXTypes: function () {
                                            return 'html';
                                        },
                                        html: '<img src="/controllers/user_interface.php?' + params + '" />'

                                    });

                                }

                            } //if (selectedNode != null)

                        } else if (viewPanel.getLayout().activeItem == viewGridPanel) { //datasheet

                            Ext.ux.Printer.print(viewGrid);

                        }

                    } //handler

                }

            ] //tools

        }); //images

        // ---------------------------------------------------------

        var onHistoryChange = function (token) {

            if (token) {

                var parts = token.split(CCR.xdmod.ui.tokenDelimiter);

                if (parts[0] == layoutId) {

                    var treePanel = Ext.getCmp('tree_' + layoutId);
                    var nodeId = parts[1];
                    var nodeToSelect = treePanel.getNodeById(nodeId);

                    if (nodeToSelect) {

                        Ext.menu.MenuMgr.hideAll();
                        treePanel.getSelectionModel().select(nodeToSelect);

                    } //if(nodeToSelect)

                } //if (parts[0] == layoutId)

            } //if (token)

        }; //onHistoryChange

        // ---------------------------------------------------------

        var filterMenu = new Ext.menu.Menu({

            showSeparator: false,
            items: this.chartFilterSelector,
            closable: false,
            scope: this,
            ownerCt: this

        }); //filterMenu

        // ---------------------------------------------------------

        var btnFilter = new Ext.Button({

            scope: this,
            iconCls: 'filter',
            text: 'Filter',
            tooltip: 'Filter chart data',
            
            handler: function() {
            
               XDMoD.TrackEvent('Usage', 'Clicked on the Filter menu');
            
            },
            
            menu: filterMenu

        }); //btnFilter

        // ---------------------------------------------------------

        chartToolbar.chartConfigButton.menu.on('paramchange', function(paramName, paramValue) {

           XDMoD.TrackEvent('Usage', 'Display Menu Item Selected', Ext.encode({name: paramName, value: paramValue}));

        }, this);

        // ---------------------------------------------------------
        
        var tbItems = [

            XDMoD.ToolbarItem.ROLE_SELECTOR,
            XDMoD.ToolbarItem.DURATION_SELECTOR,
            btnFilter,

            chartToolbar.chartConfigButton, //<-- display button

            'Top ',

            {
                item: chartToolbar.limitField,
                separator: false
            },

            XDMoD.ToolbarItem.EXPORT_MENU

        ]; //tbItems

        if (public_user != true)
            tbItems.push(XDMoD.ToolbarItem.REPORT_CHECKBOX);

        // ---------------------------------------------------------      

        Ext.apply(this, {

            customOrder: tbItems,
            items: [leftPanel, images]

        }); //Ext.apply

        // ---------------------------------------------------------

        function onResize(t, adjWidth, adjHeight, rawWidth, rawHeight) {

            maximizeScale.call(this);
            if (this.chart) this.chart.setSize(adjWidth, adjHeight);

        }; //onResize

        // ---------------------------------------------------------

        view.on('resize', onResize, this);


		     // ---------------------------------------------------------

        view.on('afterrender', function () {

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
            chartToolbar.disable();

            this.chartFilterSelector.on('selectionchange', function (itemType, itemTypeLabel, selectedItems, selectedItemTexts) {

                XDMoD.TrackEvent('Usage', 'Filter -> Clicked on Apply');
                
                var selectedNode = tree.getSelectionModel().getSelectedNode();
                selectedNode.attributes.filter = selectedItems.join(',');
                selectedNode.attributes.filterText = selectedItemTexts;
                reloadChartStore(selectedNode.attributes.chartSettings || null);

            }, this);

            this.chartFilterSelector.on('selectionreset', function () {
                
                XDMoD.TrackEvent('Usage', 'Filter -> Clicked on Reset');
                
                var selectedNode = tree.getSelectionModel().getSelectedNode();
                selectedNode.attributes.filter = undefined;
                reloadChartStore(selectedNode.attributes.chartSettings || null);

            }, this);

            tree.getSelectionModel().on('selectionchange', onSelectNode, this);

            chartStore.on('load', function (chartStore) {

                if (chartStore.getCount() != 1) {

                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                    if (viewer.el) viewer.el.unmask();
                    viewer.el.unmask();
                    return;

                } //if (chartStore.getCount() != 1)

                var n = tree.getSelectionModel().getSelectedNode();

                if (n.attributes.node_type == 'statistic') {

                    if (public_user != true) {

                        self.getReportCheckbox().storeChartArguments(

                            chartStore.getAt(0).get('chart_args'),
                            chartStore.getAt(0).get('title'),
                            chartStore.getAt(0).get('params_title'),
                            chartStore.getAt(0).get('start_date'),
                            chartStore.getAt(0).get('end_date'),
                            chartStore.getAt(0).get('reportGeneratorMeta').included_in_report

                        ); //self.getReportCheckbox().storeChartArguments

                    } //if (public_user != true)

                    var legend = '<ul>';
                    legend += '<li>' + chartStore.getAt(0).get('group_description') + '</li>';

                    for (var i = 0; i < chartStore.getCount(); i++) {
                        legend += '<li>' + chartStore.getAt(i).get('description') + '</li>';
                    }

                    legend += '</ul>';

                    updateDescription(legend, chartStore.getAt(0).get('subnotes'));

                    //var tpl = new Ext.XTemplate ('<h1>'+self.getRoleSelector().getText()+'\'s Usage</h1>');
                    //tpl.overwrite(paramDescriptionPanel.body);

                    leftPanel.setTitle(self.getRoleSelector().getText() + '\'s View');
                    var filterOptions = Ext.util.JSON.decode(chartStore.getAt(0).get('filter_options'));

                    this.chartFilterSelector.loadData(filterOptions, n.attributes.group_by, n.attributes.group_by_label);
                    
                    XDMoD.TrackEvent(
                        'Usage', 
                        'Loaded Chart',
                        'Chart: ' + chartStore.getAt(0).get('title') + ', Params: ' + chartStore.getAt(0).get('params_title')
                    );

                    var chartSettings = Ext.util.JSON.decode(chartStore.getAt(0).get('chart_settings').replace(/`/g, '"'));

                    n.attributes.chartSettings = chartStore.getAt(0).get('chart_settings').replace(/`/g, '"');
                    if (!n.attributes.defaultChartSettings) n.attributes.defaultChartSettings = chartSettings;
                    chartToolbar.fromJSON(n.attributes.chartSettings);

                    self.getExportMenu().menu.items.each(function (item, index, length) {
                        if (item.iconCls == 'png') item.enable();
                    }, this);

                    if (public_user != true)
                        self.getReportCheckbox().show();

                    var viewer = CCR.xdmod.ui.Viewer.getViewer();
                    if (viewer.el) viewer.el.unmask();

                    delete this.chart;
                    this.chart = null;

                    var ind = 0;

                    chartStore.each(function (r) {

                        var id = r.get('random_id');
                        var el = Ext.get(id); // Get Ext.Element object

                        var task = new Ext.util.DelayedTask(function () {

                            var baseChartOptions = {

                                chart: {

                                    renderTo: id,
                                    width: chartWidth * chartScale,
                                    height: chartHeight * chartScale,
                                    animation: true,

                                    events: {

                                        load: function (e) {

                                            this.checkSeries = function () {

                                                if (this.series.length == 0) {

                                                    if (this.placeholder_element) this.placeholder_element.destroy();
                                                    this.placeholder_element = this.renderer.image('gui/images/report_thumbnail_no_data.png', (this.chartWidth - 400) / 2, (this.chartHeight - 300) / 2, 400, 300).add();

                                                } //if (this.series.length == 0)

                                            } //this.checkSeries

                                            this.checkSeries();

                                        }, //load

                                        redraw: function (e) {

                                            if(this.checkSeries) this.checkSeries();

                                        } //redraw

                                    } //events

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
                            chartOptions.credits.enabled = true;

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


                            this.chart = new Highcharts.Chart(chartOptions);

                        }, this); //task

                        task.delay(0);

                        return true;

                    }, this); //chartStore.each(function(r)

                    self.getDurationSelector().enable();
                    chartToolbar.enable();

                    var tool = images.getTool('maximize');
                    if (tool) tool.hide();

                    var tool = images.getTool('restore');
                    if (tool) tool.hide();

                    var tool = images.getTool('plus');
                    if (tool) tool.hide();

                    var tool = images.getTool('minus');
                    if (tool) tool.hide();

                    var tool = images.getTool('gear');
                    if (tool && CCR.xdmod.ui.usageExplorer) tool.show();

                    var loadTime = (new Date().getTime() - chartStore.loadStartTime) / 1000.0;

                    CCR.xdmod.ui.toastMessage('Load Chart', 'Complete in ' + loadTime + 's');

                    this.reloadingForRescale = false;

                } //if(n.attributes.node_type == 'statistic')

            }, this); //chartStore.on('load',...

        }, this, {
            single: true
        }); //view .on('afterrender',
		
        // Call parent (required)
        XDMoD.Module.Usage.superclass.initComponent.apply(this, arguments);

    } //initComponent

}); //XDMoD.Module.Usage