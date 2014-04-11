/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2012-Apr (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
*
* This class contains the Usage Explorer tab
*
*/
 
XDMoD.Module.UsageExplorer = function (config) {

    XDMoD.Module.UsageExplorer.superclass.constructor.call(this, config);

}; //XDMoD.Module.UsageExplorer

// ===========================================================================

Ext.apply(XDMoD.Module.UsageExplorer, {

    setConfig: function (config, name) {

        var tabPanel = Ext.getCmp('main_tab_panel');
		
		CCR.xdmod.ui.usageExplorer.un('afterlayout', CCR.xdmod.ui.usageExplorer.loadAll);
		
        tabPanel.setActiveTab('usage_explorer');

        function loadSummaryChart() {
            CCR.xdmod.ui.usageExplorer.mask('Loading...');
            CCR.xdmod.ui.usageExplorer.createQueryFunc(null, null, name, config);
        } //loadSummaryChart

 		CCR.xdmod.ui.usageExplorer.maximizeScale.call(CCR.xdmod.ui.usageExplorer);
		CCR.xdmod.ui.usageExplorer.on('dwdesc_loaded', function () {
			CCR.xdmod.ui.usageExplorer.queriesGridPanel.store.on('load', function (t, records) {
				loadSummaryChart();

			}, this, {
				single: true
			});

			this.queriesGridPanel.store.load();
		}, null, { 
			single: true
		});
		CCR.xdmod.ui.usageExplorer.dwDescriptionStore.load();
    }, //setConfig

    // ------------------------------------------------------------------

    seriesContextMenu: function (point, datasetId, datasetName) {

        XDMoD.TrackEvent('Usage Explorer', 'Clicked on chart to access context menu', Ext.encode({
           'x-axis': point.ts ? Highcharts.dateFormat('%Y-%m-%d', point.ts) : point.series.data[point.x].category, 
           'y-axis': point.y,
           'series': datasetName
        }));        


		var sortItems = [];
		for(var i = 0; CCR.xdmod.ui.AddDataPanel.sort_types.length > i ; i++)
		{
			sortItems.push(
				{
					text: CCR.xdmod.ui.AddDataPanel.sort_types[i][1],

                    handler: function (b) {
                        CCR.xdmod.ui.usageExplorer.updateDataset(datasetId, key, value);
                    }
				});
		}
		
		var displayItems = [];
		for(var i = 0; CCR.xdmod.ui.AddDataPanel.display_types.length > i ; i++)
		{
			displayItems.push(
				{
					text: CCR.xdmod.ui.AddDataPanel.display_types[i][1],

                    handler: function (b) {
                        CCR.xdmod.ui.usageExplorer.updateDataset(datasetId, key, value);
                    }
				});
		}
	
        var menu = new Ext.menu.Menu({

            showSeparator: false,
 			ignoreParentClicks: true,
            items: [
 				{

                    text: 'Edit series [' + datasetName + ']',
                    iconCls: 'edit_data',

                    handler: function (b) {
                        XDMoD.TrackEvent('Usage Explorer', 'Clicked on Edit series option in context menu');
                        CCR.xdmod.ui.usageExplorer.editDataset(datasetId);
                    }

                },
              

                {

                    text: 'Delete',
                    iconCls: 'delete_data',

                    handler: function (b) {
                        XDMoD.TrackEvent('Usage Explorer', 'Clicked on Delete option in context menu');
                        CCR.xdmod.ui.usageExplorer.removeDataset(datasetId);
                    }

                }

            ]

        }); //menu

        menu.showAt(Ext.EventObject.getXY());

    } //seriesContextMenu

}); //Ext.apply(XDMoD.Module.UsageExplorer

// ===========================================================================

Ext.extend(XDMoD.Module.UsageExplorer, XDMoD.PortalModule, {

    module_id: 'usage_explorer',

    usesToolbar: true,

    toolbarItems: {

        roleSelector: true,
        durationSelector: true,
        exportMenu: true,
        printButton: true,
        reportCheckbox: true,

    },

    show_filters: true,
    font_size: 3,
    legend_type: 'bottom_center',
    swap_xy: false,
	 share_y_axis: false,
    trend_line: false,
    color: 'auto',
    shadow: false,
    std_err: false,
    timeseries: true,
    log_scale: false,
    value_labels: false,
    display_type: 'line',
    line_type: 'Solid',
    line_width: 2,
    combine_type: 'side',
    sort_type: 'label_asc',

    ignore_global: false,
    long_legend: true,
    featured: false,

    // ------------------------------------------------------------------

    getDataSeries: function () {

        var data = [];

        this.dataSeriesStore.each(function (record) {
            data.push(record.data);
        });

        return data;

    }, //getDataSeries

    // ------------------------------------------------------------------

    getGlobalFilters: function () {

        var ret = [];

        this.filtersStore.each(function (record) {
            ret.push(record.data);
        });

        return {
            data: ret,
            total: ret.length
        }

    }, //getGlobalFilters

    // ------------------------------------------------------------------

    getConfig: function () {

        var dataSeries = this.getDataSeries();
        var dataSeriesCount = dataSeries.length;

        var title = this.chartTitleField.getValue();

        if (title == '') {
            var rec = this.queriesGridPanel.getSelectionModel().getSelected();
            if (rec) title = rec.get('name');
        }

        var config = {

            featured: this.featuredCheckbox.getValue(),

            active_role: this.getRoleSelector().value,
            swap_xy: this.chartSwapXYField.getValue(),
			share_y_axis: this.shareYAxisField.getValue(),
            timeseries: this.timeseries,
            title: title,
            legend_type: this.legendTypeComboBox.getValue(),
            font_size: this.fontSizeSlider.getValue(),
            show_filters: this.chartShowSubtitleField.getValue(),
            data_series: {
                data: dataSeries,
                total: dataSeriesCount
            },
            aggregation_unit: this.getDurationSelector().getAggregationUnit(),
            global_filters: this.getGlobalFilters(),
            start_date: this.getDurationSelector().getStartDate().format('Y-m-d'),
            end_date: this.getDurationSelector().getEndDate().format('Y-m-d'),
            start: this.chartPagingToolbar.cursor,
            limit: this.chartPagingToolbar.pageSize

        }; //config

        return config;

    }, //getConfig

    // ------------------------------------------------------------------

    reset: function (preserveFilters) {

        this.disableSave = true;
        this.timeseries = true;
        this.datasetTypeRadioGroup.setValue(this.timeseries ? 'timeseries_cb' : 'aggregate_cb', true);
        this.chartTitleField.setValue('');

        //perhaps these should be saved to the user profile separately
        this.legendTypeComboBox.setValue('bottom_center');
        this.fontSizeSlider.setValue(3);
        this.featuredCheckbox.setValue(false);
        this.chartSwapXYField.setValue(false);
		this.shareYAxisField.setValue(false);
        this.dataSeriesStore.removeAll(false);
		if(!preserveFilters) this.filtersStore.removeAll(false);
        this.disableSave = false;

    }, //reset

    // ------------------------------------------------------------------	

    createQueryFunc: function (b, em, queryName, config, preserveFilters) {

        var sm = this.queriesGridPanel.getSelectionModel();
        sm.clearSelections();

        if (!config) {
            this.reset(preserveFilters);
            config = this.getConfig();
        }

        this.loadQuery(config, false);

        var index = this.queriesGridPanel.store.findBy(function (record) {

            if (record.get('name') === queryName) {
                return true;
            }

        }); //.find('name', queryName, 0, false, true);

        if (index > -1) {

            this.queriesGridPanelSM.un('rowselect', this.queriesGridPanelSMRowSelect, this);
            sm.selectRow(index);
            this.queriesGridPanelSM.on('rowselect', this.queriesGridPanelSMRowSelect, this);

            var view = this.queriesGridPanel.getView();

            view.focusRow(index);

        } else {

            var r = new this.queriesGridPanel.store.recordType({
                name: queryName,
                config: Ext.util.JSON.encode(config)
            });
            this.queriesGridPanel.store.insert(0, r);

        }

    }, //createQueryFunc

    // ------------------------------------------------------------------

    saveQueryFunc: function (name, oldName) {

        if (this.disableSave || this.disableSave === true) return;
        if (!oldName) oldName = '';

        if (!name || name == '') {

            var r = this.queriesGridPanel.getSelectionModel().getSelected();

            if (r) name = r.get('name');

            if (name == '') {
                return;
            }

        } //if(!name || name == '')

        var config = this.getConfig();

        //look to see if query of that name is already saved with the same config
        var index = this.queriesGridPanel.store.findBy(function (record) {

            if (record.get('name') === name) {
                return true;
            }

        }); //.find('name', name, 0, false, true);

        if (index > -1) {

            var rec = this.queriesGridPanel.store.getAt(index);

            if (rec.get('config') == Ext.util.JSON.encode(config)) {
                return;
            } else {
                var newConfig = Ext.util.JSON.decode(rec.get('config'));
                Ext.apply(newConfig, config); //apply the new setting onto the old one so that any hidden properties can be preserved.
                rec.set('config', Ext.util.JSON.encode(newConfig));
            }

        } //if(index > -1)

    }, //saveQueryFunc

    // ------------------------------------------------------------------

    loadQuery: function (config, reload) {

        if (!config) return;

        this.disableSave = true;

        if (!config.active_role) {

            config.active_role = this.getRoleSelector().value;

        }

        for (var i = 0; i < this.getRoleSelectorItems().length; i++) {

            if (this.getRoleSelectorItems()[i].value == config.active_role && !this.getRoleSelectorItems()[i].checked) {

                if (this.getRoleSelectorItems()[i].setChecked) {

                    this.getRoleSelectorItems()[i].setChecked(true, true);
                    this.getRoleSelectorItems()[i].handler.call(this, this.getRoleSelectorItems()[i], true);

                }

                break;

            } //if(this.roleCategorySelectorItems[i].value ...

        } //for(var i = 0; i < this.roleCategorySelectorItems.length; i++)

        this.getDurationSelector().setValues(config.start_date, config.end_date, config.aggregation_unit);

        this.timeseries = config.timeseries ? true : false;
        this.datasetTypeRadioGroup.setValue(this.timeseries ? 'timeseries_cb' : 'aggregate_cb', true);
        this.chartTitleField.setValue(config.title);
        this.legendTypeComboBox.setValue(config.legend_type);
        this.fontSizeSlider.setValue(config.font_size);
        this.chartSwapXYField.setValue(config.swap_xy);
		this.shareYAxisField.setValue(config.share_y_axis);
        this.chartShowSubtitleField.setValue(config.show_filters);
        this.dataSeriesStore.loadData(config.data_series, false);
        this.filtersStore.loadData(config.global_filters ? config.global_filters : {
            data: [],
            total: 0
        }, false);
        this.chartPagingToolbar.cursor = config.start ? config.start : 0;
        this.chartPagingToolbar.pageSize = config.limit ? config.limit : 10;
        this.chartPageSizeField.setValue(config.limit ? config.limit : 10);

        this.featuredCheckbox.setValue(config.featured);

        this.disableSave = false;
        if (reload) this.reloadChart.call(this, 300);

    }, //loadQuery

    // ------------------------------------------------------------------

    mask: function (message) {

        var viewer = CCR.xdmod.ui.Viewer.getViewer();
        if (viewer.el) viewer.el.mask();

    }, //mask

    // ------------------------------------------------------------------

    unmask: function () {

        var viewer = CCR.xdmod.ui.Viewer.getViewer();
        if (viewer.el) viewer.el.unmask();

    }, //unmask

    // ------------------------------------------------------------------

    initComponent: function () {

        var self = this;

        var chartScale = 1;
        var chartThumbScale = 0.45;
        var chartWidth = 740;
        var chartHeight = 345;

        var realms = [];

        var metricsMenu = new Ext.menu.Menu({
            showSeparator: false,
            ignoreParentClicks: true
        });

        var filtersMenu = new Ext.menu.Menu({
            showSeparator: false,
            ignoreParentClicks: true
        });

        this.allDimensions = [];
        this.allMetrics = [];

        // ---------------------------------------------------------

        this.dwDescriptionStore = new CCR.xdmod.CustomJsonStore({

            url: 'controllers/usage_explorer.php',
            fields: ['realms'],
            root: 'data',
            totalProperty: 'totalCount',
            idProperty: 'name',
            messageProperty: 'message',
            scope: this,

            baseParams: {
                'operation': 'get_dw_descripter'
            }

        }); //dwDescriptionStore

        this.on('role_selection_change', function (b, e) {

            this.dwDescriptionStore.load();

            if (e) {
                this.saveQuery.call(this, 100);
                this.reloadChart.call(this, 200);
            }

        }, this);

        this.dwDescriptionStore.on('beforeload', function () {

            this.mask('Loading...');

            this.dwDescriptionStore.baseParams.active_role = this.getRoleSelector().value;

        }, this);

        this.dwDescriptionStore.on('load', function (store) {

            var filterItems = [];
            var filterMap = {};

            this.allDimensions = [];
            this.allMetrics = [];

            metricsMenu.removeAll(true);
            filtersMenu.removeAll(true);

            if (store.getCount() > 0) {

                realms = store.getAt(0).get('realms');

                for (realm in realms) {

                    var realm_metrics = realms[realm]['metrics'];

                    var realmItems = [];
                    realmItems.push('<b class="menu-title">' + realm + ' Metrics:</b><br/>');

                    for (x in realm_metrics) {

                        if (realm_metrics[x].text == undefined) continue;

                        this.allMetrics.push([x, realm_metrics[x].text]);

                        realmItems.push({

                            text: realm_metrics[x].text,
                            iconCls: 'chart',
                            realm: realm,
                            metric: x,
                            scope: this,
                            handler: function (b, e) {
                            
                                XDMoD.TrackEvent('Usage Explorer', 'Selected a metric from the Add Data menu', Ext.encode({
                                   realm: b.realm,
                                   metric: b.text
                                }));

                                addDataButtonHandler.call(b.scope, b.scope.datasetsGridPanel.toolbars[0].el, b.metric, b.realm, realms);
                                
                            }

                        });

                    } //for(x in realm_metrics)

                    metricsMenu.add({

                        text: realm,
                        iconCls: 'realm',
                        menu: realmItems,
                        disabled: realmItems.length <= 0

                    });

                    var realm_dimensions = realms[realm]['dimensions'];

                    for (x in realm_dimensions) {

                        if (x == 'none' || realm_dimensions[x].text == undefined) continue;

                        this.allDimensions.push([x, realm_dimensions[x].text]);

                        if (filterMap[x] == undefined) {

                            filterMap[x] = filterItems.length;

                            filterItems.push({

                                text: realm_dimensions[x].text,
                                iconCls: 'menu',
                                realms: [realm],
                                dimension: x,
                                scope: this,

                                handler: function (b, e) {
                                
                                    XDMoD.TrackEvent('Usage Explorer', 'Selected a filter from the Create Filter menu', b.text);
                                    filterButtonHandler.call(b.scope, b.scope.filtersGridPanel.toolbars[0].el, b.dimension, b.text, b.realms);

                                }

                            });

                        } else {

                            if (filterItems[filterMap[x]].realms.indexOf(realm) == -1) {
                                filterItems[filterMap[x]].realms.push(realm);
                            }

                        }

                    } //for(x in realm_dimensions)

                } //for(realm in realms)

                filterItems.sort(function (a, b) {

                    var nameA = a.text.toLowerCase(),
                        nameB = b.text.toLowerCase();

                    if (nameA < nameB) //sort string ascending
                        return -1;
                    if (nameA > nameB)
                        return 1;

                    return 0; //default return value (no sorting)	

                }); //filterItems.sort(...

                filtersMenu.addItem(filterItems);

                this.dimensionsCombo.store.loadData(this.allDimensions, false);
                this.metricsCombo.store.loadData(this.allMetrics, false);

            } //if(store.getCount() > 0)

            this.unmask();
            this.fireEvent('dwdesc_loaded');

        }, this); //dwDescriptionStore.on('load',…

        // ---------------------------------------------------------

        this.on('duration_change', function (d) {

            this.saveQuery.call(this, 100);
            this.reloadChart.call(this, 200);

        });

        // ---------------------------------------------------------

        this.datasetTypeRadioGroup = new Ext.form.RadioGroup({

            fieldLabel: 'Dataset Type',

            items: [{
                id: 'aggregate_cb',
                boxLabel: 'Aggregate',
                name: 'dataset_group',
                inputValue: 1,
                checked: !this.timeseries
            }, {
                id: 'timeseries_cb',
                boxLabel: 'Timeseries',
                name: 'dataset_group',
                inputValue: 2,
                checked: this.timeseries
            }],

            listeners: {

                scope: this,

                'change': function (radioGroup, checkedRadio) {

                    XDMoD.TrackEvent('Usage Explorer', 'Changed Dataset Type', Ext.encode({type: checkedRadio.boxLabel}));

                    this.timeseries = checkedRadio.inputValue == 2;
                    var cm = this.datasetsGridPanel.getColumnModel();
                    var ind = cm.getIndexById('x_axis');
                    if (ind > -1) cm.setHidden(ind, this.timeseries);
                    ind = cm.getIndexById('trend_line');
                    if (ind > -1) cm.setHidden(ind, !this.timeseries);
                    this.saveQuery.call(this, 200);
                    this.reloadChart.call(this, 2000);

                } //change

            } //listeners

        }); //this.datasetTypeRadioGroup

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

                    if (n != o) {
                    
                        XDMoD.TrackEvent('Usage Explorer', 'Updated chart title', t.getValue());
                    
                        this.saveQuery.call(this, 100);
                        this.reloadChart.call(this, 1000);
                        
                    }

                }, //change

                specialkey: function (t, e) {

                    if (t.isValid(false) && e.getKey() == e.ENTER) {
                    
                        //XDMoD.TrackEvent('Usage Explorer', 'Updated chart title', t.getValue());
                        this.saveQuery.call(this, 100);
                        this.reloadChart.call(this, 200);
                        
                    }

                } //specialkey

            } //listeners

        }); //this.chartTitleField

        // ---------------------------------------------------------

        this.chartShowSubtitleField = new Ext.form.Checkbox({

            fieldLabel: 'Show Filters',
            name: 'show_filters',
            boxLabel: 'Show Query Filters in subtitle',
            checked: this.show_filters,

            listeners: {

                scope: this,

                'check': function (checkbox, check) {

                    XDMoD.TrackEvent('Usage Explorer', 'Clicked on Show Query Filters in subtitle checkbox', Ext.encode({ checked: check }));

                    this.show_filters = check;
                    this.saveQuery.call(this, 100);
                    this.reloadChart.call(this, 1000);

                } //check

            } //listeners

        }); //this.chartShowSubtitleField

        // ---------------------------------------------------------

        this.chartSwapXYField = new Ext.form.Checkbox({

            fieldLabel: 'Invert Axis',
            name: 'swap_xy',
            boxLabel: 'Swap the X and Y axis',
            checked: this.swap_xy,

            listeners: {

                scope: this,

                'check': function (checkbox, check) {

                    XDMoD.TrackEvent('Usage Explorer', 'Clicked on the Invert Axis checkbox', Ext.encode({checked: check}));

                    this.swap_xy = check;
                    this.saveQuery.call(this, 100);
                    this.reloadChart.call(this, 1000);

                } //check 

            } //listeners

        }); //this.chartSwapXYField

        // ---------------------------------------------------------
		
		
		 this.shareYAxisField = new Ext.form.Checkbox({

            fieldLabel: 'Share Y Axis',
            name: 'share_y_axis',
            boxLabel: 'Single Y axis',
            checked: this.share_y_axis,

            listeners: {

                scope: this,

                'check': function (checkbox, check) {

                    XDMoD.TrackEvent('Usage Explorer', 'Clicked on the Share Y Axis checkbox', Ext.encode({checked: check}));

                    this.share_y_axis = check;
                    this.saveQuery.call(this, 100);
                    this.reloadChart.call(this, 1000);

                } //check

            } //listeners

        }); //this.shareYAxisField

        // ---------------------------------------------------------

        this.legendTypeComboBox = new Ext.form.ComboBox({

            fieldLabel: 'Legend',
            name: 'legend_type',
            xtype: 'combo',
            mode: 'local',
            editable: false,
            tpl: '<tpl for="."><div ext:qtip="{text}" class="x-combo-list-item">{text}</div></tpl>',

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

                    XDMoD.TrackEvent('Usage Explorer', 'Updated legend placement', Ext.encode({legend_type: record.get('id')}));

                    this.legend_type = record.get('id');
                    this.saveQuery.call(this, 200);
                    this.reloadChart.call(this, 2000);

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

                    XDMoD.TrackEvent('Usage Explorer', 'Used the font size slider', Ext.encode({font_size: t.getValue()}));

                    this.font_size = t.getValue();
                    this.saveQuery.call(this, 200);
                    this.reloadChart.call(this, 2000);

                } //change

            } //listeners

        }); //this.fontSizeSlider

        // ---------------------------------------------------------

        this.featuredCheckbox = new Ext.form.Checkbox({

            fieldLabel: 'Featured',
            name: 'featured',
            boxLabel: 'Show in the Summary tab',
            checked: this.featured,

            listeners: {

                scope: this,

                'check': function (checkbox, check) {

                    XDMoD.TrackEvent('Usage Explorer', 'Toggled Show in the Summary tab checkbox', Ext.encode({checked: check}));

                    this.featured = check;
                    this.saveQuery.call(this, 100);

                } //check

            } //listeners

        }); //this.featuredCheckbox

        // ---------------------------------------------------------

        var getBaseParams = function () {

            var title = this.chartTitleField.getValue();

            if (title == '') {

                var rec = this.queriesGridPanel.getSelectionModel().getSelected();
                if (rec) title = rec.get('name');

            }

            var baseParams = {};
            baseParams.show_title = 'n';
            baseParams.timeseries = this.timeseries ? 'y' : 'n';

            baseParams.aggregation_unit = this.getDurationSelector().getAggregationUnit();
            baseParams.start_date = this.getDurationSelector().getStartDate().format('Y-m-d');
            baseParams.end_date = this.getDurationSelector().getEndDate().format('Y-m-d');

            baseParams.active_role = self.getRoleSelector().value;

            baseParams.global_filters = escape(Ext.util.JSON.encode(this.getGlobalFilters()));
            baseParams.title = title;
            baseParams.show_filters = this.show_filters;

            return baseParams;

        }; //getBaseParams

        // ---------------------------------------------------------

        var xAxisCheckColumn = new Ext.grid.CheckColumn({

            id: 'x_axis',
            sortable: false,
            dataIndex: 'x_axis',
            header: 'X',
            scope: this,
            width: 20,
            hidden: this.timeseries,

            onMouseDown: function (e, t) {

                if (Ext.fly(t).hasClass(this.createId())) {

                    e.stopEvent();
                    var index = this.grid.getView().findRowIndex(t);

                    for (var i = 0; i < this.grid.store.getCount(); i++) {

                        var record = this.grid.store.getAt(i);
                        record.set(this.dataIndex, i == index);

                    } //for

                } //if(Ext.fly(t).hasClass(this.createId()))

            } //onMouseDown

        }); //xAxisCheckColumn

        // ---------------------------------------------------------

        var logScaleCheckColumn = new Ext.grid.CheckColumn({

            id: 'log_scale',
            sortable: false,
            dataIndex: 'log_scale',
            header: 'Log',

            tooltip: 'Use a logarithmic scale for this data',
            scope: this,
            width: 30,
            
            checkchange: function(rec, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled option in Data grid', Ext.encode({
                  column: data_index,
                  realm: rec.data.realm,
                  metric: rec.data.metric,
                  checked: checked
               }));

            }//checkchange

        }); //logScaleCheckColumn

        // ---------------------------------------------------------

        var valueLabelsCheckColumn = new Ext.grid.CheckColumn({

            id: 'value_labels',
            sortable: false,
            dataIndex: 'value_labels',
            header: 'Labels',

            tooltip: 'Show value labels in the chart',
            scope: this,
            width: 50,

            checkchange: function(rec, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled option in Data grid', Ext.encode({
                  column: data_index,
                  realm: rec.data.realm,
                  metric: rec.data.metric,
                  checked: checked
               }));

            }//checkchange

        }); //valueLabelsCheckColumn

        // ---------------------------------------------------------

        var stdErrCheckColumn = new Ext.grid.CheckColumn({

            id: 'std_err',
            sortable: false,
            dataIndex: 'std_err',
            disabledDataIndex: 'has_std_err',
            enabledNotDataIndex: 'log_scale',
            header: 'Err Bars',

            tooltip: 'Show Standard Error Bars (Where applicable and non log scale)',
            scope: this,
            width: 60,
            
            checkchange: function(rec, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled option in Data grid', Ext.encode({
                  column: data_index,
                  realm: rec.data.realm,
                  metric: rec.data.metric,
                  checked: checked
               }));

            }//checkchange

        }); //stdErrCheckColumn

        // ---------------------------------------------------------

        var longLegendCheckColumn = new Ext.grid.CheckColumn({

            id: 'long_legend',
            sortable: false,
            dataIndex: 'long_legend',
            header: 'Long Legend',

            tooltip: 'Show filters in legend',
            scope: this,
            width: 100,
            
            checkchange: function(rec, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled option in Data grid', Ext.encode({
                  column: data_index,
                  realm: rec.data.realm,
                  metric: rec.data.metric,
                  checked: checked
               }));

            }//checkchange

        }); //longLegendCheckColumn

        // ---------------------------------------------------------

        var ignoreGlobalFiltersCheckColumn = new Ext.grid.CheckColumn({

            id: 'ignore_global',
            sortable: false,
            dataIndex: 'ignore_global',
            header: 'Ignore Query Filters',
            tooltip: 'Ingore Query Filters',
            scope: this,
            width: 140,
            
            checkchange: function(rec, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled option in Data grid', Ext.encode({
                  column: data_index,
                  realm: rec.data.realm,
                  metric: rec.data.metric,
                  checked: checked
               }));

            }//checkchange

        }); //ignoreGlobalFiltersCheckColumn

        // ---------------------------------------------------------

        var trendLineCheckColumn = new Ext.grid.CheckColumn({

            id: 'trend_line',
            sortable: false,
            dataIndex: 'trend_line',
            header: 'Trend Line',
            tooltip: 'Show Trend Line',
            scope: this,
            width: 80,
            hidden: this.timeseries,

            checkchange: function(rec, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled option in Data grid', Ext.encode({
                  column: data_index,
                  realm: rec.data.realm,
                  metric: rec.data.metric,
                  checked: checked
               }));

            }//checkchange

        }); //trendLineCheckColumn

        // ---------------------------------------------------------

        this.dataSeriesStore = new Ext.data.JsonStore({

            root: 'data',
            autoDestroy: true,
            idIndex: 0,

            fields: [
                'id',
                'metric',
                'realm',
                'group_by',
                'x_axis',
                'log_scale',
                'has_std_err',
                'std_err',
                'value_labels',
                'display_type',
                'line_type',
                'line_width',
                'combine_type',
                'sort_type',
                'filters',
                'ignore_global',
                'long_legend',
                'trend_line',
                'color',
                'shadow'
            ]

        }); //this.dataSeriesStore

        // ---------------------------------------------------------

        //delay saving on updated records, chances are user is moving mouse to 
        //next checkbox
        this.dataSeriesStore.on('update', function () {

            this.saveQuery.call(this, 200);
            this.reloadChart.call(this, 2000);

        }, this);

        // ---------------------------------------------------------

        //load the stuff quickly when the query is loaded
        this.dataSeriesStore.on('load', function () {

            this.reloadChart.call(this, 10);

        }, this);

        // ---------------------------------------------------------

        //on adding the dataset, make it appear fast
        this.dataSeriesStore.on('add', function () {

            this.saveQuery.call(this, 1);
            this.reloadChart.call(this, 10);

        }, this);

        // ---------------------------------------------------------

        //on removing the dataset, make it disappear fast
        this.dataSeriesStore.on('remove', function () {

            this.saveQuery.call(this, 1);
            this.reloadChart.call(this, 10);

        }, this);

        // ---------------------------------------------------------

        var addDatasetButton = new Ext.Button({

            iconCls: 'add_data',
            text: 'Add Data',
            menu: metricsMenu,

            handler: function (i, e) {
                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Add Data button');
                //metricsMenu.show(i.el,'tl-bl?');
            }

        }); //addDatasetButton

        // ---------------------------------------------------------

        this.editDataset = function (datasetId) {

            var record = null;
            var sm = this.datasetsGridPanel.getSelectionModel();

            //datasetId provides override for interation with chart series
            if (datasetId !== undefined && datasetId !== null) {

                //find row where id - datasetId;
                var datasetIndex = this.dataSeriesStore.findBy(function (_record, _id) {

                    if (Math.abs(datasetId - _record.data.id) < 1e-14) {
                        return true;
                    }

                }, this);

                if (datasetIndex < 0) {

                    //alert('Invalid index'+datasetIndex);
                    return;

                }

                sm.selectRow(datasetIndex);
                record = sm.getSelected();

            } else {
                record = sm.getSelected();
            }

            // ---------------------------------------------------------

            var addDataPanel = new CCR.xdmod.ui.AddDataPanel({

                id: Math.random(),
                update_record: true,
                //record_id: record.data.id,
                realm: record.data.realm,
                metric: record.data.metric,
                group_by: record.data.group_by,
                realms: realms,
                timeseries: this.timeseries,
                value_labels: record.data.value_labels,
                has_std_err: record.data.has_std_err,
                std_err: record.data.std_err,
                log_scale: record.data.log_scale,
                display_type: record.data.display_type,
                line_type: record.data.line_type,
                line_width: record.data.line_width,
                combine_type: record.data.combine_type,
                sort_type: record.data.sort_type,
                filters: record.data.filters,
                dimensionsCombo: this.dimensionsCombo,
                ignore_global: record.data.ignore_global,
                long_legend: record.data.long_legend,
                trend_line: record.data.trend_line,
                shadow: record.data.shadow,
                color: record.data.color,

                active_role: this.getRoleSelector().value,

                cancel_function: function () {

                    addDataMenu.closable = true;
                    addDataMenu.close();

                },

                add_function: function () {

                    record.data.realm = this.realm;
                    record.data.metric = this.metric;
                    record.data.group_by = this.group_by;
                    record.data.has_std_err = this.has_std_err;
                    record.data.std_err = this.std_err;
                    record.data.log_scale = this.log_scale;
                    record.data.value_labels = this.value_labels;
                    record.data.display_type = this.display_type;
                    record.data.line_type = this.line_type;
                    record.data.line_width = this.line_width;
                    record.data.combine_type = this.combine_type;
                    record.data.x_axis = this.x_axis;
                    record.data.sort_type = this.sort_type;
                    record.data.filters = this.getSelectedFilters();
                    record.data.ignore_global = this.ignore_global;
                    record.data.long_legend = this.long_legend;
                    record.data.trend_line = this.trend_line;
                    record.data.color = this.color;
                    record.data.shadow = this.shadow;

                    record.commit();
                    addDataMenu.closable = true;
                    addDataMenu.close();

                }

            }); //addDataPanel

            // ---------------------------------------------------------

            var addDataMenu = new Ext.Window({

                showSeparator: false,
                resizable: false,
                items: [addDataPanel],
                closable: false,
                scope: this,
                ownerCt: this,

                listeners: {

                    'beforeclose': function (t) {

                        return t.closable;

                    },

                    'close': function (t) {

                        addDataPanel.hideMenu();
                        t.scope.unmask();

                    },

                    'show': function (t) {

                        t.scope.mask();

                    }

                } //listeners

            }); //addDataMenu

            addDataMenu.show();

            // ---------------------------------------------------------

            var xy = addDataMenu.el.getAlignToXY(this.datasetsGridPanel.toolbars[0].el, 'tl-bl?');
            xy = addDataMenu.el.adjustForConstraints(xy);
            addDataMenu.setPosition(xy);

            //addDataMenu = Ext.menu.MenuMgr.get(addDataMenu);		
            //addDataMenu.show(this.datasetsGridPanel.toolbars[0].el,'tl-bl?');

        }; //this.editDataset

        // ---------------------------------------------------------

        var editDatasetButton = new Ext.Button({

            iconCls: 'edit_data',
            text: 'Edit',
            tooltip: 'Edit highlighted data series',
            disabled: true,
            scope: this,

            handler: function (i, e) {

                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Dataset Edit button');

                this.editDataset.call(this);

            }

        }); //editDatasetButton

        // ---------------------------------------------------------

        this.removeDataset = function (datasetId) {

            var sm = this.datasetsGridPanel.getSelectionModel();

            //datasetId providex over ride for interation with chart series
            if (datasetId !== undefined && datasetId !== null) {

                //find row where id - datasetId;
                var datasetIndex = this.dataSeriesStore.findBy(function (_record, _id) {

                    if (Math.abs(datasetId - _id) < 0.00000000000001) {
                        return true;
                    }

                }, this);

                if (datasetIndex < 0) {

                    //alert('Invalid index'+datasetIndex);
                    return;

                }

                var record = sm.getSelected();
                this.dataSeriesStore.remove(record);

            } else {

                var records = this.datasetsGridPanel.getSelectionModel().getSelections();
                this.dataSeriesStore.remove(records);

            }

        } //this.removeDataset

        // ---------------------------------------------------------

        var removeDatasetButton = new Ext.Button({

            iconCls: 'delete_data',
            text: 'Delete',
            tooltip: 'Delete highlighted data series',
            disabled: true,
            scope: this,

            handler: function (i, e) {
            
                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Dataset Delete button');
                i.scope.removeDataset.call(i.scope);

            }

        }); //removeDatasetButton

        // ---------------------------------------------------------

        this.dimensionsCombo = CCR.xdmod.ui.getComboBox(this.allDimensions, ['id', 'text'], 'id', 'text', false, 'None');
        this.metricsCombo = CCR.xdmod.ui.getComboBox(this.allMetrics, ['id', 'text'], 'id', 'text', false, 'None');

        this.displayTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.display_types, ['id', 'text'], 'id', 'text', false, 'None');
        this.lineTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.line_types, ['id', 'text'], 'id', 'text', false, 'Solid');
        this.lineWidthsCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.line_widths, ['id', 'text'], 'id', 'text', false, 2);
        this.combineTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.combine_types, ['id', 'text'], 'id', 'text', false, 'None');
        this.sortTypesCombo = CCR.xdmod.ui.getComboBox(CCR.xdmod.ui.AddDataPanel.sort_types, ['id', 'text'], 'id', 'text', false, 'None');

        // ---------------------------------------------------------

        this.datasetsGridPanel = new Ext.grid.GridPanel({

            header: false,
            height: 190,
            id: 'grid_datasets_' + this.id,
            useArrows: true,
            autoScroll: true,
            sortable: false,
            //enableDragDrop: true,
            enableHdMenu: false,
            margins: '0 0 0 0',
            loadMask: true,

            sm: new Ext.grid.RowSelectionModel({

                singleSelect: true,

                listeners: {

                    selectionchange: function (sm) {

                        var disabled = sm.getCount() <= 0;
                        
                        if (sm.getCount() == 1) {

                           var sel = sm.getSelections()[0].data;
                           XDMoD.TrackEvent('Usage Explorer', 'Selected a dataset from the list', sel.realm + ' -> ' + sel.metric);
                           
                        }
                        
                        removeDatasetButton.setDisabled(disabled);
                        editDatasetButton.setDisabled(disabled);
                    
                    }

                } //listeners

            }), //Ext.grid.RowSelectionModel

            plugins: [

                //this.metricsGridPanelEditor,
                xAxisCheckColumn,
                logScaleCheckColumn,
                valueLabelsCheckColumn,
                stdErrCheckColumn,
                longLegendCheckColumn,
                ignoreGlobalFiltersCheckColumn,
                trendLineCheckColumn //,
                //new Ext.ux.plugins.ContainerBodyMask ({ msg:'No data selected.<br/> Click on <img class="x-panel-inline-icon add_data" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to add data.', masked:true})

            ], //plugins

            listeners: {

                scope: this,

                rowdblclick: function (t, rowIndex, e) {
                    XDMoD.TrackEvent('Usage Explorer', 'Double-clicked on dataset entry in list');
                    this.editDataset.call(this);
                }

            }, //listeners

            store: this.dataSeriesStore,

            viewConfig: {

                emptyText: 'No data selected.<br/> Click on <img class="x-panel-inline-icon add_data" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to add data.'

            }, //viewConfig

            columns: [

                //xAxisCheckColumn,
                {
                    id: 'realm',
                    tooltip: 'Realm',
                    width: 50,
                    header: 'Realm',
                    dataIndex: 'realm'
                },

                {
                    id: 'metric',
                    tooltip: 'Metric',
                    width: 200,
                    header: 'Metric',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.metricsCombo),
                    dataIndex: 'metric'
                },

                {
                    id: 'group_by',
                    tooltip: 'Group the results by this dimension',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.dimensionsCombo),
                    width: 60,
                    header: 'Grouping',
                    dataIndex: 'group_by'
                },

                logScaleCheckColumn,
                valueLabelsCheckColumn,
                stdErrCheckColumn,

                {
                    id: 'display_type',
                    tooltip: 'Display Type',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.displayTypesCombo),
                    width: 60,
                    header: 'Display',
                    dataIndex: 'display_type'
                },

                {
                    id: 'line_type',
                    tooltip: 'Line Type',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.lineTypesCombo),
                    width: 60,
                    header: 'Line',
                    dataIndex: 'line_type'
                },

                {
                    id: 'line_width',
                    tooltip: 'Line Width',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.lineWidthsCombo),
                    width: 60,
                    header: 'Line Width',
                    dataIndex: 'line_width'
                },

                {
                    id: 'combine_type',
                    tooltip: 'Dataset Alignment - How the data bar/lines/areas are aligned relative to each other',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.combineTypesCombo),
                    width: 100,
                    header: 'Align',
                    dataIndex: 'combine_type'
                },

                {
                    id: 'sort_type',
                    tooltip: 'Sort Type - How the data will be sorted',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.sortTypesCombo),
                    width: 130,
                    header: 'Sort',
                    dataIndex: 'sort_type'
                },

                longLegendCheckColumn,
                ignoreGlobalFiltersCheckColumn,
                trendLineCheckColumn

            ], //columns

            tbar: [
                addDatasetButton,
                '->', '-',
                editDatasetButton,
                '-',
                removeDatasetButton
            ]

        }); //this.datasetsGridPanel

        // ---------------------------------------------------------

        this.filtersStore = new Ext.data.GroupingStore({

            groupField: 'dimension_id',

            sortInfo: {

                field: 'dimension_id',
                direction: 'ASC' // or 'DESC' (case sensitive for local sorting)

            },

            reader: new Ext.data.JsonReader(

                {
                    totalProperty: 'totalCount',
                    successProperty: 'success',
                    idProperty: 'id',
                    root: 'data',
                    messageProperty: 'message'
                },

                [
                    'id',
                    'value_id',
                    'value_name',
                    'dimension_id',
                    'realms',
                    'checked'
                ]

            )

        }); //this.filtersStore

        // ---------------------------------------------------------

        this.filtersStore.on('load', function () {

            if (this.filtersStore.getCount() > 0) {
                //this.filtersGridPanel.hideMask();
            }

        }, this);

        this.filtersStore.on('add', function () {

            //this.filtersGridPanel.hideMask();

            this.saveQuery.call(this, 200);
            this.reloadChart.call(this, 2000);

        }, this);

        this.filtersStore.on('remove', function () {

            //if(this.filtersStore.getCount() == 0)	
            //{
            //this.filtersGridPanel.showMask();
            //}

            this.saveQuery.call(this, 200);
            this.reloadChart.call(this, 2000);

        }, this);

        this.filtersStore.on('update', function () {

            //this.saveFiltersToUserProfile();
            this.saveQuery.call(this, 200);
            this.reloadChart.call(this, 2000);

        }, this);

        // ---------------------------------------------------------

        var checkAllButton = new Ext.Button({

            text: 'Check All',
            scope: this,

            handler: function (b, e) {

                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Check All in Query Filters pane');

                this.filtersStore.each(function (r) {
                    r.set('checked', true);
                });

            } //handler

        }); //checkAllButton

        // ---------------------------------------------------------

        var uncheckAllButton = new Ext.Button({

            text: 'Uncheck All',
            scope: this,

            handler: function (b, e) {

                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Uncheck All in Query Filters pane');

                this.filtersStore.each(function (r) {
                    r.set('checked', false);
                });

            } //handler

        }); //uncheckAllButton

        // ---------------------------------------------------------

        var activeFilterCheckColumn = new Ext.grid.CheckColumn({

            id: 'checked',
            sortable: false,
            dataIndex: 'checked',
            header: 'Global',
            tooltip: 'Check this column to apply filter globally',
            scope: this,
            width: 50,
            hidden: false,
            
            checkchange: function(record, data_index, checked) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Toggled filter checkbox', Ext.encode({
                  dimension: record.data.dimension_id,
                  value: record.data.value_name,
                  checked: checked
               }));
                           
            }//checkchange

        }); //activeFilterCheckColumn

        // ---------------------------------------------------------

        var removeFilterItem = new Ext.Button({

            iconCls: 'delete_filter',
            tooltip: 'Delete highlighted filter(s)',
            text: 'Delete',
            disabled: true,
            scope: this,

            handler: function (i, e) {

                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Delete in Query Filters pane');

                var records = this.filtersGridPanel.getSelectionModel().getSelections();

   				 for (i = 0; i < records.length; i++) {
   
   				    XDMoD.TrackEvent('Usage Explorer', 'Confirmed deletion of filter', Ext.encode({
   
   				       dimension: records[i].data.dimension_id,
   				       value: records[i].data.value_name
   				     
   				    }));
               
                }//for (each record selected)
            
                this.filtersGridPanel.store.remove(records);
                //this.saveFiltersToUserProfile();

            } //handler

        }); //removeFilterItem

        // ---------------------------------------------------------

        this.filtersGridPanel = new Ext.grid.GridPanel({

            header: false,
            height: 170,
            id: 'grid_filters_' + this.id,
            useArrows: true,
            autoScroll: true,
            sortable: false,
            enableHdMenu: false,
            margins: '0 0 0 0',

            sm: new Ext.grid.RowSelectionModel({

                singleSelect: false,

                listeners: {

                    'rowselect': function(sm, row_index, record) {
                      
                       XDMoD.TrackEvent('Usage Explorer', 'Selected a query filter', Ext.encode({
                          dimension: record.data.dimension_id,
                          value: record.data.value_name
                       }));

                    },

                    'rowdeselect': function(sm, row_index, record) {

                       XDMoD.TrackEvent('Usage Explorer', 'De-selected a query filter', Ext.encode({
                          dimension: record.data.dimension_id,
                          value: record.data.value_name
                       }));
                                           
                    },                    

                    'selectionchange': function (sm) {                        
                        removeFilterItem.setDisabled(sm.getCount() <= 0);
                    }

                } //listeners

            }), //Ext.grid.RowSelectionModel

            plugins: [
                activeFilterCheckColumn //,
                //new Ext.ux.plugins.ContainerBodyMask ({ msg:'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.', masked:true})
            ],

            autoExpandColumn: 'value_name',
            store: this.filtersStore,
            loadMask: true,

            view: new Ext.grid.GroupingView({

                forceFit: true,
                groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})',
                emptyText: 'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.<br/><br/>' + 'Filters will apply to query data, where the realm of the filter matches the data.'

            }),

            columns: [

                activeFilterCheckColumn, {
                    id: 'dimension',
                    tooltip: 'Dimension',
                    renderer: CCR.xdmod.ui.gridComboRenderer(this.dimensionsCombo),
                    width: 80,
                    header: 'Dimension',
                    dataIndex: 'dimension_id'
                }, {
                    id: 'value_name',
                    tooltip: 'Filter',
                    width: 100,
                    header: 'Filter',
                    dataIndex: 'value_name'
                }, {
                    id: 'realms',
                    tooltip: 'Realms that this filter applies to',
                    width: 80,
                    header: 'Realms',
                    dataIndex: 'realms'
                }

            ],

            tbar: [

                {

                    scope: this,
                    iconCls: 'add_filter',
                    text: 'Create Filter',
                    menu: filtersMenu,

                    handler: function (i, e) {
                    
                        XDMoD.TrackEvent('Usage Explorer', 'Clicked on Create Filter button');
                        //filtersMenu.show(i.el,'tl-bl?');
                        
                    }

                },

                '->',
                '-',
                checkAllButton,
                '-',
                uncheckAllButton,
                '-',
                removeFilterItem

            ]

        }); //this.filtersGridPanel

        // ---------------------------------------------------------

        var filterButtonHandler = function (el, dim_id, dim_label, realms) {

            if (!dim_id || !dim_label) return;


            var filterDimensionPanel = new CCR.xdmod.ui.FilterDimensionPanel({
                origin: '',
                dimension_id: dim_id,
                realms: realms,

                active_role: this.getRoleSelector().value,
                dimension_label: dim_label,
                selectedFilters: []

            }); //filterDimensionPanel

            filterDimensionPanel.on('cancel', function () {

                addFilterMenu.closable = true;
                addFilterMenu.close();

            });

            filterDimensionPanel.on('ok', function () {

                for (var filter = 0; filter < filterDimensionPanel.selectedFilters.length; filter++) {

                    var oldRec = addFilterMenu.scope.filtersStore.findBy(function (r) {

                        if (r.data.id == filterDimensionPanel.selectedFilters[filter].id) {
                            return true;
                        }

                    }); //oldRec

                    if (oldRec == -1) {

                        var record_config = {

                            id: filterDimensionPanel.selectedFilters[filter].id,
                            value_id: filterDimensionPanel.selectedFilters[filter].value_id,
                            value_name: filterDimensionPanel.selectedFilters[filter].value_name,
                            dimension_id: filterDimensionPanel.selectedFilters[filter].dimension_id,
                            realms: filterDimensionPanel.selectedFilters[filter].realms,
                            checked: true

                        };
                        
                        var r = new addFilterMenu.scope.filtersStore.recordType(record_config);

                        addFilterMenu.scope.filtersStore.addSorted(r);
                        
                        XDMoD.TrackEvent('Usage Explorer', 'Introduced filter', Ext.encode(record_config));

                    } else {

                        var r = addFilterMenu.scope.filtersStore.getAt(oldRec);
                        r.set('checked', true);

                    }

                } //for(var filter = 0;...

                //addFilterMenu.scope.saveFiltersToUserProfile();
                //addFilterMenu.scope.saveQuery.call(this,200);
                addFilterMenu.closable = true;
                addFilterMenu.close();

            }); //filterDimensionPanel

            var addFilterMenu = new Ext.Window({

                resizable: false,
                showSeparator: false,
                items: [filterDimensionPanel],
                scope: this,
                closable: false,

                listeners: {

                    'beforeclose': function (t) {
                        return t.closable;
                    },

                    'close': function (t) {
                        t.scope.unmask();
                    },

                    'show': function (t) {
                        t.scope.mask();
                    }

                } //listeners

            }); //addFilterMenu

            addFilterMenu.show();
            addFilterMenu.center();
            var xy = addFilterMenu.el.getAlignToXY(this.filtersGridPanel.toolbars[0].el, 'tl-bl?');

            //constrain to the viewport.
            xy = addFilterMenu.el.adjustForConstraints(xy);
            addFilterMenu.setPosition(xy);

        } //filterButtonHandler

        // ---------------------------------------------------------

        var addDataButtonHandler = function (el, metric, realm, realms) {

            var addDataPanel = new CCR.xdmod.ui.AddDataPanel({

                id: Math.random(),
                realm: realm,
                metric: metric,
                group_by: 'none',
                realms: realms,
                timeseries: this.timeseries,
                value_labels: this.value_labels,
                has_std_err: this.has_std_err,
                std_err: this.std_err,
                log_scale: this.log_scale,
                display_type: this.display_type,
                line_type: this.line_type,
                line_width: this.line_width,
                combine_type: this.combine_type,
                sort_type: this.sort_type,
                filters: null,
                dimensionsCombo: this.dimensionsCombo,
                ignore_global: this.ignore_global,
                long_legend: this.long_legend,
                trend_line: this.trend_line,
                shadow: this.shadow,
                color: this.color,

                active_role: this.getRoleSelector().value,

                cancel_function: function () {

                    addDataMenu.closable = true;
                    addDataMenu.close();

                }, //cancel_function

                add_function: function () {

                    var r = new addDataMenu.scope.datasetsGridPanel.store.recordType({

                        id: Math.random(),
                        realm: this.realm,
                        metric: this.metric,
                        group_by: this.group_by,
                        has_std_err: this.has_std_err,
                        std_err: this.std_err,
                        log_scale: this.log_scale,
                        value_labels: this.value_labels,
                        display_type: this.display_type,
                        line_type: this.line_type,
                        line_width: this.line_width,
                        combine_type: this.combine_type,
                        x_axis: this.x_axis,
                        sort_type: this.sort_type,
                        filters: this.getSelectedFilters(),
                        ignore_global: this.ignore_global,
                        long_legend: this.long_legend,
                        trend_line: this.trend_line,
                        shadow: this.shadow,
                        color: this.color

                    });

                    addDataMenu.scope.datasetsGridPanel.store.add(r);
                    addDataMenu.closable = true;
                    addDataMenu.close();

                } //add_function

            }); //addDataPanel

            var addDataMenu = new Ext.Window({

                showSeparator: false,
                items: [addDataPanel],
                closable: false,
                scope: this,
                ownerCt: this,
                resizable: false,

                listeners: {

                    'beforeclose': function (t) {
                        return t.closable;
                    },

                    'close': function (t) {
                        addDataPanel.hideMenu();
                        t.scope.unmask();
                    },

                    'show': function (t) {
                        t.scope.mask();
                    }

                } //listeners

            }); //addDataMenu

            addDataMenu.show();
            addDataMenu.center();
            var xy = addDataMenu.el.getAlignToXY(el, 'tl-bl?');
            xy = addDataMenu.el.adjustForConstraints(xy);
            addDataMenu.setPosition(xy);

        } //addDataButtonHandler

        // ---------------------------------------------------------

		
        this.createQuery = new Ext.Button({

            xtype: 'button',
            tooltip: 'New Query',
            iconCls: 'new_ue',
            scope: this,
			
            handler: function (b) {
            
               XDMoD.TrackEvent('Usage Explorer', 'Clicked on New Query button');
            
				var createQueryHandler = function()
				{						
					var text = nameField.getValue();

					if (text === '') {
						Ext.Msg.alert('Name Invalid', 'Please enter a valid name');
					} else {
						var recIndex = this.queriesGridPanel.store.findBy(function (record) {
							if (record.get('name') === text) {
									win.close();
								return true;
							}
						}); 
						if (recIndex < 0) 
						{
							this.createQueryFunc(null, null, text, undefined, preserveFilters.getValue());
								win.close();
						} 
						else 
						{
							Ext.Msg.alert('Name in use', 'Please enter a unique name');
						}
					}
				};
				
				var preserveFilters = new Ext.form.Checkbox({
				
					xtype: 'checkbox',

               listeners: {

                scope: this,

                'check': function (checkbox, check) {

                    XDMoD.TrackEvent('Usage Explorer', 'Create Query -> Clicked the Preserve Filters checkbox', Ext.encode({checked: check}));
                    
                 }//check
 
               }//listeners

				});
				
				var nameField = new Ext.form.TextField(
				{
					fieldLabel: 'Query Name',
					
					listeners: {
						scope: this,
						specialkey: function(field, e){
							// e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
							// e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
							if (e.getKey() == e.ENTER) {
							   XDMoD.TrackEvent('Usage Explorer', 'Create Query -> Pressed enter in textbox', Ext.encode({input_text: field.getValue()}));
								createQueryHandler.call(this);
							}
						},
						afterrender: function(field,l)
						{
							nameField.focus(true,100);
						}
					}
				});
				var win = new Ext.Window({
					width: 300,
					//height: 100,
					resizable: false, 
					modal: true,
					title: 'Create Query',
					layout:'fit',
					scope: this,
					items: nameField,
					
					listeners: {
			         close: function() {
			            XDMoD.TrackEvent('Usage Explorer', 'Create Query prompt closed');
			         }
					},
					
					buttons: [
						new Ext.Toolbar.TextItem('Preserve Filters'),
						preserveFilters,
						{
							text: 'Ok',
							scope: this,
							handler: function() {
							   XDMoD.TrackEvent('Usage Explorer', 'Create Query -> Clicked on Ok');
							   createQueryHandler.call(this);
						   }
						   
						},{
							text: 'Cancel',
							handler: function(){
							   	XDMoD.TrackEvent('Usage Explorer', 'Create Query -> Clicked on Cancel');
								win.close();
							}
						}
					]
				});
				
				win.show(this);		

            } //handler

        }); //this.createQuery

        // ---------------------------------------------------------

        this.saveAsQuery = new Ext.Button({

            xtype: 'button',
            tooltip: 'Save As',
            iconCls: 'save_as',
            scope: this,

            handler: function (b) {

               XDMoD.TrackEvent('Usage Explorer', 'Clicked on Save As button');

                Ext.Msg.prompt(

                    'Save As', 'Please enter a name for the query:',

                    function (btn, text) {

                        if (btn == 'ok') {

                            XDMoD.TrackEvent('Usage Explorer', 'Save As -> Confirmed new name', Ext.encode({text_field: text}));
                            
                            if (text === '') {
                                Ext.Msg.alert('Name Invalid', 'Please enter a valid name');
                            } else {

                                var recIndex = this.queriesGridPanel.store.findBy(function (record) {
                                    if (record.get('name') === text) {
                                        return true;
                                    }

                                }); //.find('name', text, 0, false, true);

                                if (recIndex < 0) {
                                    this.createQueryFunc(null, null, text, this.getConfig());
                                } else {
                                    Ext.Msg.alert('Name in use', 'Please enter a unique name');
                                }

                            }

                        } //if (btn == 'ok')
                        else {
                           XDMoD.TrackEvent('Usage Explorer', 'Closed Save As prompt');
                        }

                    }, //function(btn, text)

                    this,
                    false

                ); //Ext.Msg.prompt

            } //handler

        }); //this.saveAsQuery

        // ---------------------------------------------------------

        this.deleteQuery = new Ext.Button({

            xtype: 'button',
            tooltip: 'Delete selected query',
            iconCls: 'delete2',
            scope: this,

            handler: function (b, e) {

                XDMoD.TrackEvent('Usage Explorer', 'Clicked on Delete selected query button');

                var sm = this.queriesGridPanel.getSelectionModel();
                var rec = sm.getSelected();

                Ext.Msg.show({

                    scope: this,
                    maxWidth: 800,
                    minWidth: 400,
                    title: 'Delete Selected Query',
                    msg: 'Are you sure you want to delete ' + rec.get('name') + '?<br><b>This action cannot be undone.</b>',
                    buttons: Ext.Msg.YESNO,

                    fn: function (resp) {

                        if (resp === 'yes') {

                            this.reset();

                            XDMoD.TrackEvent('Usage Explorer', 'Confirmed deletion of query', rec.data.name);

                            this.queriesGridPanel.store.remove(rec);
                            this.reloadChart.call(this, 1);

                        } //if (resp === 'yes')
                        else {
                           XDMoD.TrackEvent('Usage Explorer', 'Dismissed query deletion confirm dialog');
                        }
                        
                    }, //fn

                    icon: Ext.MessageBox.QUESTION

                }); //Ext.Msg.show

            } //handler

        }); //this.deleteQuery

        // ---------------------------------------------------------

        var searchField = new Ext.form.TwinTriggerField({

            xtype: 'twintriggerfield',
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            hasSearch: false,
            enableKeyEvents: true,
            emptyText: 'Search',

            onTrigger1Click: function () {

                XDMoD.TrackEvent('Usage Explorer', 'Cleared query search field');
               
                if (this.hasSearch) {

                    this.el.dom.value = '';
                    this.store.baseParams.search_text = '';
                    this.store.load();
                    this.triggers[0].hide();
                    this.hasSearch = false;

                }

            }, //onTrigger1Click

            onTrigger2Click: function () {

                var v = this.getRawValue();

                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }

                XDMoD.TrackEvent('Usage Explorer', 'Using query search field', Ext.encode({search_string: v}));

                this.store.baseParams.search_text = v;
                this.store.load();
                this.hasSearch = true;
                this.triggers[0].show();

            }, //onTrigger2Click

            listeners: {

                scope: this,

                'specialkey': function (field, e) {

                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.getKey() == e.ENTER) {
                        searchField.onTrigger2Click();
                    }

                }

            } //listeners

        }); //searchField

        // ---------------------------------------------------------

        this.queriesGridPanelSMRowSelect = function (t, rowIndex, r) {
            
            XDMoD.TrackEvent('Usage Explorer', 'Selected query from list', r.data.name);
            
            this.loadQuery(Ext.util.JSON.decode(r.data.config));

        };

        this.queriesGridPanelSM = new Ext.grid.RowSelectionModel({

            singleSelect: true

        }); //sm

        this.queriesGridPanelSM.on('rowselect', this.queriesGridPanelSMRowSelect, this);

        this.queriesGridPanel = new Ext.grid.GridPanel({

            tbar: [

                this.createQuery,
                '-',
                this.saveAsQuery,
                '->',
                this.deleteQuery,
                ' ',
                '-',
                searchField

            ],

            height: 150,
            autoScroll: true,
            rowNumberer: true,

            border: true,
            stripeRows: true,
            enableHdMenu: false,
            hideHeaders: true,
            autoExpandColumn: 'name',
            scope: this,

            viewConfig: {
                forceFit: true
            },

            flex: 80,

            store: new CCR.xdmod.CustomJsonStore({

                restful: true,

                proxy: new CCR.xdmod.CustomHttpProxy({

                    api: {

                        read: 'controllers/usage_explorer.php?operation=read_queries',
                        create: 'controllers/usage_explorer.php?operation=create_query', // Server MUST return idProperty of new record

                        update: {
                            url: 'controllers/usage_explorer.php?operation=update_query',
                            method: 'POST'
                        },

                        destroy: 'controllers/usage_explorer.php?operation=delete_query'

                    }

                }), //proxy 

                idProperty: 'name',
                root: 'data',

                fields: [
                    'name',
                    'config'
                ],

                writer: new Ext.data.JsonWriter({

                    encode: true,
                    writeAllFields: false

                }), //writer

                listeners: {

                    scope: this,

                    write: function (store, action, result, res, rs) {

                        //console.log(action);
                        var sm = this.queriesGridPanel.getSelectionModel();

                        var recIndex = store.findBy(function (record) {

                            if (record.get('name') === rs.id) {
                                return true;
                            }

                        }); //.find('name', rs.id, 0, false, true);;

                        if (recIndex > -1 && action === 'create') {
                            sm.selectRow(recIndex, false);
                        }


                    } //write

                } //listeners

            }), //new CCR.xdmod.CustomJsonStore

            columns: [

                {
                    header: 'Query',
                    dataIndex: 'name',
                    editor: new Ext.form.TextField({})
                }

            ], //columns

            sm: this.queriesGridPanelSM

        }); //this.queriesGridPanel

        // ---------------------------------------------------------

        searchField.store = this.queriesGridPanel.store; // have to "late bind"

        var leftPanel = new Ext.FormPanel({

            split: true,
            bodyStyle: 'padding:5px 5px 0;',
            collapsible: true,
            header: true,
            title: 'Query Options',
            autoScroll: true,
            width: 375,
            margins: '2 0 2 2',
            border: true,
            region: 'west',
            plugins: new Ext.ux.collapsedPanelTitlePlugin('Query Options'),

            items: [

                {

                    xtype: 'fieldset',
                    title: 'Queries',
                    autoHeight: true,
                    layout: 'form',
                    hideLabels: true,
                    collapsible: true,
                    items: [this.queriesGridPanel]

                },

                {

                    xtype: 'fieldset',
                    title: 'Options',
                    autoHeight: true,
                    layout: 'form',
                    hideLabels: false,
                    collapsible: true,
                    collapsed: false,

                    defaults: {
                        anchor: '0' // '-20' // leave room for error icon
                    },

                    items: [

                        this.featuredCheckbox,
                        this.datasetTypeRadioGroup,
                        this.chartTitleField,
                        this.legendTypeComboBox,
                        this.fontSizeSlider,
                        this.chartSwapXYField,
						this.shareYAxisField

                    ]

                },

                {

                    xtype: 'fieldset',
                    title: 'Data',
                    autoHeight: true,
                    layout: 'form',
                    hideLabels: true,
                    collapsible: true,
                    items: [this.datasetsGridPanel]

                },

                {

                    xtype: 'fieldset',
                    title: 'Query Filters',
                    qtip: 'These filters apply to all data.',
                    autoHeight: true,
                    layout: 'form',

                    defaults: {
                        anchor: '0' // '-20' // leave room for error icon
                    },

                    hideLabels: false,
                    collapsible: true,

                    items: [

                        this.chartShowSubtitleField,
                        this.filtersGridPanel

                    ]

                }

            ]

        }); //leftPanel

        // ---------------------------------------------------------

        CCR.xdmod.catalog['usage_explorer']['2'] = {
            highlight: leftPanel.id,
            title: 'Creating A Chart (Part 2/3)',
            width: 300,
            height: 200,
            description: 'click on the - to delete an existing chart'
        };

        CCR.xdmod.catalog['usage_explorer']['1'] = {
            highlight: leftPanel.id,
            title: 'Creating A Chart (Part 1/3)',
            width: 343,
            height: 205,
            padding: '0px',
            description: '<img src="gui/images/assistant/usage_explorer_001.png">'
        };

        CCR.xdmod.catalog['usage_explorer']['3'] = {
            highlight: leftPanel.id,
            title: 'Creating A Chart (Part 3/3)',
            width: 300,
            height: 400,
            description: 'you are done'
        };

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
                url: 'controllers/usage_explorer.php'
            })

        }); //chartStore

        // ---------------------------------------------------------

        chartStore.on('beforeload', function () {

            if (!this.getDurationSelector().validate()) return;

            this.mask('Loading...');
            highChartPanel.un('resize', onResize, this);

            chartStore.baseParams = {};
            Ext.apply(chartStore.baseParams, getBaseParams.call(this));

            chartStore.baseParams.start = this.chartPagingToolbar.cursor;
            chartStore.baseParams.limit = this.chartPagingToolbar.pageSize;

            this.maximizeScale.call(this);
            var dataSeries = this.getDataSeries();
            chartStore.dataSeriesLength = dataSeries.length;

            chartStore.baseParams.timeframe_label = this.getDurationSelector().getDurationLabel(),
            chartStore.baseParams.operation = 'get_data';
            chartStore.baseParams.data_series = escape(Ext.util.JSON.encode(dataSeries));
            chartStore.baseParams.swap_xy = this.swap_xy;
			chartStore.baseParams.share_y_axis = this.share_y_axis;
            chartStore.baseParams.show_guide_lines = 'y';
            chartStore.baseParams.show_title = 'y';
			chartStore.baseParams.showContextMenu = 'y';
            chartStore.baseParams.scale = 1;
            chartStore.baseParams.format = 'hc_jsonstore';
            chartStore.baseParams.width = chartWidth * chartScale;
            chartStore.baseParams.height = chartHeight * chartScale;
            chartStore.baseParams.legend_type = this.legendTypeComboBox.getValue();
            chartStore.baseParams.font_size = this.fontSizeSlider.getValue();
            chartStore.baseParams.featured = this.featured;

            chartStore.baseParams.controller_module = self.getReportCheckbox().getModule();

        }, this); //chartStore.on('beforeload', ...

        // ---------------------------------------------------------

        chartStore.on('load', function (chartStore) {

            this.firstChange = true;

            if (chartStore.getCount() != 1) {
                this.unmask();
                return;
            }

            var noData = chartStore.dataSeriesLength === 0;

            chartViewPanel.getLayout().setActiveItem(noData ? 1 : 0);

            self.getExportMenu().setDisabled(noData);

            self.getPrintButton().setDisabled(noData);

            self.getReportCheckbox().setDisabled(noData);

            var reportGeneratorMeta = chartStore.getAt(0).get('reportGeneratorMeta');

            self.getReportCheckbox().storeChartArguments(reportGeneratorMeta.chart_args,
                reportGeneratorMeta.title,
                reportGeneratorMeta.params_title,
                reportGeneratorMeta.start_date,
                reportGeneratorMeta.end_date,
                reportGeneratorMeta.included_in_report);

            highChartPanel.on('resize', onResize, this); //re-register this after loading/its unregistered beforeload

            var pagingData = this.chartPagingToolbar.getPageData();

            if (pagingData.activePage > pagingData.pages) this.chartPagingToolbar.changePage(1);

            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) viewer.el.unmask();

        }, this); //chartStore.on('load', ...

        // ---------------------------------------------------------

        var reloadChartFunc = function () {
            chartStore.load();
        }

        var reloadChartTask = new Ext.util.DelayedTask(reloadChartFunc, this);

        this.reloadChart = function (delay) {
            reloadChartTask.delay(delay || 150);
        };

        var saveQueryTask = new Ext.util.DelayedTask(this.saveQueryFunc, this);

        this.saveQuery = function (delay, name, oldName) {
            saveQueryTask.delay(10, this.saveQueryFunc, this, [name, oldName]);
        };

        if (!this.chartDefaultPageSize) this.chartDefaultPageSize = 10;

        // ---------------------------------------------------------

        this.chartPageSizeField = new Ext.form.NumberField({

            id: 'chart_size_field_' + this.id,
            fieldLabel: 'Chart Size',
            name: 'chart_size',
            minValue: 1,
            maxValue: 50,
            allowDecimals: false,
            decimalPrecision: 0,
            incrementValue: 1,
            alternateIncrementValue: 2,
            accelerate: true,
            width: 45,
            //emptyText: this.defaultPageSize,
            value: this.chartDefaultPageSize,

            listeners: {

                scope: this,

                'change': function (t, newValue, oldValue) {

                    if (t.isValid(false) && newValue != t.ownerCt.pageSize) {

                        XDMoD.TrackEvent('Usage Explorer', 'Changed chart limit', newValue);
                        //t.ownerCt.cursor = 0;
                        t.ownerCt.pageSize = newValue;
                        this.saveQuery.call(this, 100);
                        t.ownerCt.doRefresh();

                    }

                }, //change

                'specialkey': function (t, e) {

                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN

                    if (t.isValid(false) && e.getKey() == e.ENTER /*&& t.getValue() != t.ownerCt.pageSize */ ) {

                        XDMoD.TrackEvent('Usage Explorer', 'Changed chart limit', t.getValue());
                        
                        // this.parent.onHandle();
                        // t.ownerCt.cursor = 0;
                        t.ownerCt.pageSize = t.getValue();
                        this.saveQuery.call(this, 100);
                        t.ownerCt.doRefresh();

                    }

                } //specialkey

            } //listeners

        }); //this.chartPageSizeField

        // ---------------------------------------------------------

        this.chartPagingToolbar = new CCR.xdmod.ui.CustomPagingToolbar({

            pageSize: this.chartDefaultPageSize,
            store: chartStore,
            beforePageText: 'Chart',
            displayInfo: true,
            //hidden: this.timeseries, 
            displayMsg: 'Data Series {0} - {1} of {2}',
            scope: this,
			   showRefresh: false,
            //emptyMsg: "No data",

            items: [
                '-',
                'Chart Limit',
                this.chartPageSizeField,
                'Data Series'
            ],

            updateInfo: function () {

                if (this.displayItem) {

                    var count = this.store.getCount();
                    var msg = count == 0 ?

                    this.emptyMsg :

                    String.format(
                        this.displayMsg,
                        this.cursor + 1, Math.min(this.store.getTotalCount(), this.cursor + this.pageSize), this.store.getTotalCount()
                    );

                    this.displayItem.setText(msg);

                } //if(this.displayItem)

            } //updateInfo

        }); //this.chartPagingToolbar

        // ---------------------------------------------------------

        this.firstChange = true;

        this.chartPagingToolbar.on('afterlayout', function () {

            this.chartPagingToolbar.on('change', function (total, pageObj) {

                XDMoD.TrackEvent('Usage Explorer', 'Loaded page of data', pageObj.activePage + ' of ' + pageObj.pages);            
                
                if (!this.firstChange) {
                    alert('cpt');
                    this.saveQuery.call(this, 1);
                } else {
                    this.firstChange = false;
                }

                return true;

            }, this);

        }, this, {
            single: true
        });

        // ---------------------------------------------------------

        var assistPanel = new CCR.xdmod.ui.AssistPanel({

            region: 'center',
            border: false,
            headerText: 'No data is available for viewing',
            subHeaderText: 'Please refer to the instructions below:',
            graphic: 'gui/images/usage_explorer_instructions.png',
            userManualRef: 'usage+explorer'

        }); //assistPanel

        // ---------------------------------------------------------

        var highChartPanel = new CCR.xdmod.ui.HighChartPanel({

            //title: 'High Charts', 
            id: 'hc-panel' + this.id,
            store: chartStore

        }); //assistPanel

        // ---------------------------------------------------------

        var chartViewPanel = new Ext.Panel({

            frame: false,
            layout: 'card',
            activeItem: 0,
            tbar: this.chartPagingToolbar,
            region: 'center',

            border: true,

            items: [
                highChartPanel,
                assistPanel
            ]

        }); //chartViewPanel

        // ---------------------------------------------------------

        var viewGrid = new Ext.ux.DynamicGridPanel({

            id: 'view_grid_' + this.id,
            storeUrl: 'controllers/usage_explorer.php',
            autoScroll: true,
            rowNumberer: true,
            region: 'center',
            remoteSort: true,
            showHdMenu: false,
            border: false,
            usePaging: true,
            lockingView: false

        }); //viewGrid

        // ---------------------------------------------------------

        var reloadViewGridFunc = function () {

            viewGrid.store.baseParams = {};
            Ext.apply(viewGrid.store.baseParams, getBaseParams.call(this));

            viewGrid.store.baseParams.operation = 'get_data';
            viewGrid.store.baseParams.offset = viewGrid.bottomToolbar.cursor;
            viewGrid.store.baseParams.limit = viewGrid.bottomToolbar.pageSize;
            viewGrid.store.baseParams.format = 'jsonstore';
            viewGrid.store.load();

        } //reloadViewGridFunc

        // ---------------------------------------------------------

        var reloadViewTask = new Ext.util.DelayedTask(reloadViewGridFunc, this);

        this.reloadViewGrid = function (delay) {

            reloadViewTask.delay(delay || 2300);

        };

        // ---------------------------------------------------------

        var view = new Ext.Panel({

            region: 'center',
            layout: 'border',
            margins: '2 2 2 0',
            border: false,
            items: [chartViewPanel]

        }); //view

        // ---------------------------------------------------------

        self.on('print_clicked', function () {

            var parameters = chartStore.baseParams;

            parameters['operation'] = 'get_data';
            parameters['scale'] = 1; //CCR.xdmod.ui.hd1280Scale;
            parameters['format'] = 'png';
            parameters['start'] = this.chartPagingToolbar.cursor;
            parameters['limit'] = this.chartPagingToolbar.pageSize;
            parameters['width'] = 757 * 2;
            parameters['height'] = 400 * 2;
            parameters['show_title'] = 'y';

            var params = '';

            for (i in parameters) {
                params += i + '=' + parameters[i] + '&'
            }

            params = params.substring(0, params.length - 1);

            Ext.ux.Printer.print({

                getXTypes: function () {
                    return 'html';
                },
                html: '<img src="/controllers/usage_explorer.php?' + params + '" />'

            });

        }); //self.on('print_clicked', ...

        // ---------------------------------------------------------

        self.on('export_option_selected', function (opts) {

            var parameters = chartStore.baseParams;

            Ext.apply(parameters, opts);

            CCR.invokePost("controllers/usage_explorer.php", parameters);

        }); //self.on('export_option_selected', ...

        // ---------------------------------------------------------

         this.loadAll = function(){

            this.maximizeScale.call(this);

            this.on('dwdesc_loaded', function () {
				this.queriesGridPanel.store.on('load', function (t, records) {
					if (records.length > 0) {
						this.queriesGridPanel.getSelectionModel().selectFirstRow();
					} else {
						this.reloadChart.call(this);
					}
				}, this, {
					single: true
				});
                this.queriesGridPanel.store.load();
            }, this, {
                single: true
            });
            this.dwDescriptionStore.load();
        }; //loadAll

        this.on('afterlayout', this.loadAll, this, {
            single: true
        });

        // ---------------------------------------------------------

        this.maximizeScale = function() {

            chartWidth = chartViewPanel.getWidth();
            chartHeight = chartViewPanel.getHeight() - (chartViewPanel.tbar ? chartViewPanel.tbar.getHeight() : 0);

        }; //maximizeScale

        // ---------------------------------------------------------

        function onResize(t) {

            this.maximizeScale.call(this);

        } //onResize

        // ---------------------------------------------------------

        Ext.apply(this, {

            items: [leftPanel, view]

        }); //Ext.apply

        XDMoD.Module.UsageExplorer.superclass.initComponent.apply(this, arguments);

        this.addEvents("dwdesc_loaded");

    } //initComponent

}); //XDMoD.Module.UsageExplorer