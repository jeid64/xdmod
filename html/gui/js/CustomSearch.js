/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * This class contains functionality for the search usage tab
 *
 */
CCR.xdmod.ui.CustomSearch = function (config) {

    CCR.xdmod.ui.CustomSearch.superclass.constructor.call(this, config);

} // CCR.xdmod.ui.CustomSearch

Ext.extend(CCR.xdmod.ui.CustomSearch, Ext.Panel, {

    initComponent: function () {
        var keyStyle = {
            marginLeft: '4px',
            marginRight: '4px',
            fontSize: '11px',
            textAlign: 'center'
        };

        var valueStyle = {
            marginLeft: '1px',
            marginRight: '1px',
            textAlign: 'center',
            fontFamily: 'arial,"Times New Roman",Times,serif',
            fontSize: '11px',
            letterSpacing: '0px'
        };

        function reloadSummary() {
            //generateButton.setDisabled(true);
            mainPanel.el.unmask();
            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) viewer.el.mask('Loading...');
            durationToolbar.disable();

            var newStartDate = durationToolbar.getStartDate().format('Y-m-d');
            var newEndDate = durationToolbar.getEndDate().format('Y-m-d');
            var aggregationUnit = durationToolbar.getAggregationUnit();

            var person_id = cmbUserMappingViewer.getValue();
            if (person_id == '') {
                person_id = -1;
            }
            personSearchStore.baseParams.person_id = person_id;
            personSearchStore.baseParams.start_date = newStartDate;
            personSearchStore.baseParams.end_date = newEndDate;
            personSearchStore.baseParams.is_pi = isPICheckBox.getValue() ? 'y' : 'n';
            personSearchStore.baseParams.aggregation_unit = aggregationUnit;

            personSearchStore.loadStartTime = new Date().getTime();

            personSearchStore.load();
            summaryToolbar.show();
        }

        var durationToolbar = new CCR.xdmod.ui.DurationToolbar({
            id: 'duration_selector_' + this.id,
            showRefresh: false,
            alignRight: false,
            handler: reloadSummary
        });

        var cmbUserMappingViewer = new CCR.xdmod.ui.TGUserDropDown({
            id: 'user_list_' + this.id,
            fieldLabel: 'People',
            emptyText: 'All of ' + CCR.xdmod.ui.activeOrganization,
            listeners: {
                'change': function (f, newValue, oldValue) {
                    if (cmbUserMappingViewer.validate()) {
                        reloadSummary.call(this);
                    } else {
                        Ext.MessageBox.alert("Error", "The specified person is invalid.");
                        f.focus(true);
                    }
                }
            }
        });


        var isPICheckBox = new Ext.form.Checkbox({
            id: 'is_pi_' + this.id,
            boxLabel: 'Aggregate by PI group',
            listeners: {
                'check': {
                    fn: function (t, checked) {
                        cmbUserMappingViewer.displayPIsOnly(checked);
                        reloadSummary();
                    }
                }
            }
        });

        var rdoSearchModes = {

            xtype: 'radiogroup',
            defaultType: 'radio',
            columns: 1,
            cls: 'custom_search_mode_group',
            width: 90,
            vertical: true,

            items: [

                {
                    boxLabel: 'Formal Name',
                    checked: true,
                    name: 'user_search_mode',
                    inputValue: 'formal_name'
                },

                {
                    boxLabel: 'Username',
                    name: 'user_search_mode',
                    inputValue: 'username'
                }

            ],

            listeners: {
                change: function (rg, rc) {

                    cmbUserMappingViewer.setSearchMode(rc.inputValue);

                }
            }

        }; //rdoSearchModes


        var personSearchStore = new Ext.data.JsonStore({
            url: 'controllers/ui_data/person_search.php',
            autoDestroy: false,

            baseParams: {
                person_id: -1
            },
            root: 'data',
            fields: ['job_count',
                'total_waitduration_hours',
                'avg_waitduration_hours',
                'total_cpu_hours',
                'avg_cpu_hours',
                'total_su',
                'avg_su',
                'min_processors',
                'max_processors',
                'avg_processors',
                'total_wallduration_hours',
                'avg_wallduration_hours',
                'charts',
                'details'
            ],
            totalProperty: 'totalCount',
            successProperty: 'success',
            messageProperty: 'message',
            listeners: {
                'load': function (tstore) {


                },
                'exception': function (dp, type, action, opt, response, arg) {
                    if (response.success == false) {
                        //todo: show a re-login box instead of logout
                        Ext.MessageBox.alert("Error", response.message);
                        if (response.message == 'Session Expired') {
                            CCR.xdmod.ui.actionLogout.defer(1000);
                        } else {
                            var viewer = CCR.xdmod.ui.Viewer.getViewer();
                            if (viewer.el) viewer.el.unmask();
                            durationToolbar.enable();
                            //mainPanel.el.unmask();
                            generateButton.setDisabled(false);
                            stopButton.setDisabled(true);

                            clearButton.setDisabled(false);
                        }
                    }
                }
            }
        });
        var view = new Ext.DataView({
            itemSelector: '',
            loadingText: "Loading...",
            region: 'center',
            multiSelect: true,
            //store: personSearchStore,
            autoScroll: true
        });


        var generateButton = new Ext.Button({
            id: 'search_button_' + this.id,
            text: 'Search',
            tooltip: 'Perform search with selected parameters.',
            handler: reloadSummary,
            style: keyStyle,
            iconCls: 'query_execute',
            cls: 'custom_search_execute_button'
        });

        var clearButton = new Ext.Button({
            text: 'Clear',
            tooltip: 'Clear search results',
            handler: function () {
                this.setDisabled(true);
                portal.removeAll(true);
                generateButton.setDisabled(false);
                summaryToolbar.hide();
                detailsPanel.hide();
                mainPanel.showMask();
                CCR.xdmod.ui.toastMessage('Clear Results', 'Complete');

            },
            disabled: true,
            style: keyStyle,
            iconCls: 'results_clear'
        });
        var saveButton = new Ext.Button({
            iconCls: 'query_save',
            text: 'Save',
            tooltip: 'Save query for future reference.',
            style: keyStyle,
            disabled: true
        });

        var loadButton = new Ext.Button({
            iconCls: 'query_load',
            text: 'Load',
            tooltip: 'Load a previously saved query.',
            style: keyStyle,
            disabled: true
        });

        var stopButton = new Ext.Button({
            iconCls: 'query_stop',
            text: 'Stop',
            style: keyStyle,
            disabled: true,
            handler: function () {}
        });

        var jobsItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var totalSuItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var averageSuItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var totalCPUItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var averageCPUItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var totalWaitItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var averageWaitItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var totalWallItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var averageWallItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var minProcessorItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var maxProcessorItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });
        var averageProcessorItem = new Ext.Toolbar.TextItem({
            text: '    ',
            style: valueStyle
        });

        var portal = new Ext.ux.Portal({
            region: 'center',
            border: false,
            //tbar: toolbar,
            items: []
        });

        var summaryToolbar = new Ext.Toolbar({
            cls: 'xd-toolbar',
            border: false,
            hidden: true,
            items: [{
                xtype: 'buttongroup',
                columns: 1,
                title: 'Jobs',
                items: [{
                        xtype: 'tbtext',
                        text: 'Total:',
                        style: keyStyle
                    },
                    jobsItem
                ]
            }, {
                xtype: 'buttongroup',
                columns: 2,
                title: 'Service (su)',
                items: [{
                        xtype: 'tbtext',
                        text: 'Total:',
                        style: keyStyle
                    }, {
                        xtype: 'tbtext',
                        text: 'Avg (Per Job):',
                        style: keyStyle
                    },
                    totalSuItem, averageSuItem
                ]
            }, {
                xtype: 'buttongroup',
                columns: 2,
                title: 'CPU Time (h)',
                items: [{
                        xtype: 'tbtext',
                        text: 'Total:',
                        style: keyStyle
                    }, {
                        xtype: 'tbtext',
                        text: 'Avg (Per Job):',
                        style: keyStyle
                    },
                    totalCPUItem, averageCPUItem
                ]
            }, {
                xtype: 'buttongroup',
                columns: 2,
                title: 'Wait Time (h)',
                items: [{
                        xtype: 'tbtext',
                        text: 'Total:',
                        style: keyStyle
                    }, {
                        xtype: 'tbtext',
                        text: 'Avg (Per Job):',
                        style: keyStyle
                    },
                    totalWaitItem, averageWaitItem
                ]
            }, {
                xtype: 'buttongroup',
                columns: 2,
                title: 'Wall Time (h)',
                items: [{
                        xtype: 'tbtext',
                        text: 'Total:',
                        style: keyStyle
                    }, {
                        xtype: 'tbtext',
                        text: 'Avg (Per Job):',
                        style: keyStyle
                    },
                    totalWallItem, averageWallItem
                ]
            }, {
                xtype: 'buttongroup',
                columns: 3,
                title: 'Processors',
                items: [{
                        xtype: 'tbtext',
                        text: 'Min:',
                        style: keyStyle
                    }, {
                        xtype: 'tbtext',
                        text: 'Max:',
                        style: keyStyle
                    }, {
                        xtype: 'tbtext',
                        text: 'Avg (Per Job):',
                        style: keyStyle
                    },
                    minProcessorItem,
                    maxProcessorItem,
                    averageProcessorItem
                ]
            }]
        });

        var detailsTemplate = new Ext.XTemplate(
            '<tpl for="." >',
            '<table>',
            '<tr>',
            '<td class="details_key">{key}:</td>',
            '<td class="details_value">{value}</td>',
            '</tr>',
            '</table>',
            '</tpl>'
        );
        var detailsPanel = new Ext.tree.TreePanel({
            hiddenNodes: [],
            margins: '2 0 2 2',
            useArrows: true,
            autoScroll: true,
            animate: true,
            enableDD: false,
            loader: new Ext.tree.TreeLoader(), // Note: no dataurl, register a TreeLoader to make use of createNode()
            lines: false,
            rootVisible: false,
            title: 'Details',
            width: 450,
            region: 'west',
            collapsible: true,
            split: true,
            root: {
                nodeType: 'async',
                text: 'Details',
                draggable: false,
                id: 'source'
            },
            tbar: {
                items: [' ',
                    new Ext.form.TextField({
                        width: 150,
                        emptyText: 'Filter',
                        enableKeyEvents: true,
                        listeners: {
                            render: function (f) {
                                detailsPanel.filter = new Ext.tree.TreeFilter(detailsPanel, {
                                    clearBlank: true,
                                    autoClear: true
                                });
                            },
                            keydown: {
                                fn: function filterTree(t, e) {
                                    var text = t.getValue();
                                    Ext.each(detailsPanel.hiddenNodes, function (n) {
                                        n.ui.show();
                                    });

                                    if (!text) {
                                        detailsPanel.filter.clear();
                                        return;
                                    }
                                    detailsPanel.expandAll();

                                    var re = new RegExp('^.*' + Ext.escapeRe(text) + '.*', 'i');

                                    detailsPanel.filter.filterBy(function (n) {
                                        return !n.attributes.isLeaf || re.test(n.text);
                                    });

                                    // hide empty items that weren't filtered
                                    detailsPanel.hiddenNodes = [];

                                    detailsPanel.root.cascade(function (n) {
                                        if (!re.test(n.text) && (n.ui && n.ui.ctNode.offsetHeight < 3)) {
                                            n.ui.hide();
                                            detailsPanel.hiddenNodes.push(n);
                                        }
                                    });
                                },
                                buffer: 100,
                                scope: detailsPanel
                            },
                            scope: detailsPanel
                        }
                    }), ' ', ' ', {
                        iconCls: 'icon-expand-all',
                        tooltip: 'Expand All',
                        handler: function () {
                            detailsPanel.root.expand(true);
                        },
                        scope: detailsPanel
                    }, '-', {
                        iconCls: 'icon-collapse-all',
                        tooltip: 'Collapse All',
                        handler: function () {
                            detailsPanel.root.collapse(true);
                        },
                        scope: detailsPanel
                    }
                ]
            }

        });

        var portalPanel = new Ext.Panel({
            tbar: summaryToolbar,
            layout: 'fit',
            region: 'center',
            items: [portal]
        });

        var mainPanel = new Ext.Panel({
            layout: 'border',
            region: 'center',
            items: [detailsPanel, portalPanel],
            plugins: [new Ext.ux.plugins.ContainerMask({
                msg: 'Select query parameters and press search to start.',
                masked: true
            })]
        });

        Ext.apply(this, {
            tbar: durationToolbar,
            layout: 'fit',
            items: [{
                tbar: [

                    {
                        xtype: 'buttongroup',
                        cls: 'button_group_custom_search',
                        title: 'Search By',
                        columns: 1,
                        height: 65,
                        width: 300,
                        defaults: {
                            scale: 'small'
                        },
                        items: [
                            rdoSearchModes
                        ]
                    },

                    {
                        xtype: 'buttongroup',
                        cls: 'button_group_custom_search',
                        title: 'Search For',
                        columns: 1,
                        height: 65,
                        defaults: {
                            scale: 'small'
                        },
                        items: [
                            cmbUserMappingViewer,
                            /*{
									iconCls: 'textbox_clear',
									tooltip: 'Clear',
									handler: function()
									{
										cmbUserMappingViewer.clearValue();
									}
									
								},*/
                            isPICheckBox
                        ]
                    },

                    {
                        xtype: 'buttongroup',
                        //title: 'Query',
                        columns: 2,
                        height: 65,

                        defaults: {
                            scale: 'small',
                            iconAlign: 'top'
                        },
                        items: [

                            generateButton //,
                            //saveButton,
                            //loadButton
                        ]
                    }
                    /*,
						{
							xtype: 'buttongroup',
							//title: 'Query',
							columns:1,
							
							defaults: 
							{
								scale: 'small',
								iconAlign:'top'
							}, 
							items: 
							[
								clearButton
							]
						}*/
                ],
                layout: 'fit',
                items: [
                    mainPanel
                ]
            }]
        });

        // Call parent (required)
        CCR.xdmod.ui.CustomSearch.superclass.initComponent.apply(this, arguments);

        function reloadPortlets(store) {
            if (store.getCount() <= 0) return;

            var details = Ext.util.JSON.decode(store.getAt(0).get('details'));
            //			detailsTemplate.overwrite(detailsPanel.body, details);

            var root = new Ext.tree.AsyncTreeNode({
                nodeType: 'async',
                text: 'Details',
                draggable: false,
                id: 'source',
                children: details
            });
            detailsPanel.setRootNode(root);
            detailsPanel.render();
            root.expand();
            portal.removeAll(true);

            var portletWidth = 525 * .8;
            var portalColumns = new Array();
            //alert(portal.getWidth());
            portalColumnsCount = Math.max(1, Math.floor(portal.getWidth() / portletWidth));
            //alert(Math.floor(portal.getWidth()/portletWidth);
            for (var i = 0; i < portalColumnsCount; i++) {
                var portalColumn = new Ext.ux.PortalColumn({
                    width: portletWidth,
                    style: 'padding:2px 2px 2px 2px'
                });
                portalColumns.push(portalColumn);
                portal.add(portalColumn);
            }

            var charts = Ext.util.JSON.decode(store.getAt(0).get('charts'));

            for (var i = 0; i < charts.length; i++) {
                portalColumns[i % portalColumnsCount].add(new Ext.ux.Portlet({
                    title: charts[i].title,
                    html: '<map id="CustomSearchMap' + charts[i].random_id + '" name="CustomSearchMap' + charts[i].random_id + '">' + charts[i].chart_map + '</map><img src="' + charts[i].chart_url + '" usemap="#CustomSearchMap' + charts[i].random_id + '"/>'
                }));
            }
        }
        personSearchStore.on('load', function (store) {
            if (store.getCount() <= 0) {
                CCR.xdmod.ui.toastMessage('Load Data', 'No Results');
                return;
            }
            jobsItem.setText(parseInt(store.getAt(0).get('job_count')).numberFormat("#,#"));
            totalSuItem.setText(parseFloat(store.getAt(0).get('total_su')).numberFormat("#,#.0"));
            averageSuItem.setText(parseFloat(store.getAt(0).get('avg_su')).numberFormat("#,#.00"));
            totalCPUItem.setText(parseFloat(store.getAt(0).get('total_cpu_hours')).numberFormat("#,#.0"));
            averageCPUItem.setText(parseFloat(store.getAt(0).get('avg_cpu_hours')).numberFormat("#,#.00"));
            totalWaitItem.setText(parseFloat(store.getAt(0).get('total_waitduration_hours')).numberFormat("#,#.0"));
            averageWaitItem.setText(parseFloat(store.getAt(0).get('avg_waitduration_hours')).numberFormat("#,#.00"));
            totalWallItem.setText(parseFloat(store.getAt(0).get('total_wallduration_hours')).numberFormat("#,#.0"));
            averageWallItem.setText(parseFloat(store.getAt(0).get('avg_wallduration_hours')).numberFormat("#,#.00"));

            minProcessorItem.setText(parseInt(store.getAt(0).get('min_processors')).numberFormat("#,#"));
            maxProcessorItem.setText(parseInt(store.getAt(0).get('max_processors')).numberFormat("#,#"));
            averageProcessorItem.setText(parseInt(store.getAt(0).get('avg_processors')).numberFormat("#,#"));

            reloadPortlets(store);

            portal.doLayout();
            summaryToolbar.doLayout();
            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) viewer.el.unmask();
            durationToolbar.enable();

            //generateButton.setDisabled(false);
            //stopButton.setDisabled(true);

            //clearButton.setDisabled(false);
            detailsPanel.show();

            var loadTime = (new Date().getTime() - store.loadStartTime) / 1000.0;

            CCR.xdmod.ui.toastMessage('Load Data', 'Complete in ' + loadTime + 's');
        }, this);

        /*
		portal.on('resize',function(t)
		{
			reloadPortlets(personSearchStore);
		},this);*/

    }

});