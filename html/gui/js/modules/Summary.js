/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Feb-07 (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
*
* This class contains functionality for the summary tab of xdmod.
*
*/ 
XDMoD.Module.Summary = function (config) {

    XDMoD.Module.Summary.superclass.constructor.call(this, config);

} //XDMoD.Module.Summary

// ===========================================================================

Ext.extend(XDMoD.Module.Summary, XDMoD.PortalModule, {

    module_id: 'summary',

    usesToolbar: true,

    toolbarItems: {

        roleSelector: true,
        durationSelector: true

    },

    // ------------------------------------------------------------------

    initComponent: function () {

        var self = this;

        this.public_user = CCR.xdmod.publicUser;

        self.on('role_selection_change', function () {        
            self.reload();
        });

        self.on('duration_change', function () {
            self.reload();
        });

        // ----------------------------------------           

        this.summaryStore = new CCR.xdmod.CustomJsonStore({

            root: 'data',
            totalProperty: 'totalCount',
            autoDestroy: true,
            autoLoad: false,
            successProperty: 'success',
            messageProperty: 'message',

            fields: [
                'job_count',
                'active_person_count',
                'active_pi_count',
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
                'gateway_job_count',
                'active_allocation_count',
                'active_institution_count',
                'charts'
            ],

            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: 'controllers/ui_data/summary2.php'
            })

        }); //this.summaryStore

        // ----------------------------------------  

        this.summaryStore.on('exception', function (dp, type, action, opt, response, arg) {

            if (response.success == false) {

                //todo: show a re-login box instead of logout
                Ext.MessageBox.alert("Error", response.message || 'Unknown Error');

                if (response.message == 'Session Expired') {
                    CCR.xdmod.ui.actionLogout.defer(1000);
                }

            }

        }, this);

        // ----------------------------------------  

        this.toolbar = new Ext.Toolbar({
            border: false,
            cls: 'xd-toolbar'
        });

        this.portal = new Ext.ux.Portal({
            region: 'center',
            border: false,
            items: []
        });

        this.portalPanel = new Ext.Panel({
            tbar: this.toolbar,
            layout: 'fit',
            region: 'center',
            items: [this.portal]
        });

        this.mainPanel = new Ext.Panel({
            header: false,
            layout: 'border',
            region: 'center',
            title: '<h3>Summary</h3>',
            items: [this.portalPanel]
        });

        Ext.apply(this, {
            items: [this.mainPanel]
        });

        XDMoD.Module.Summary.superclass.initComponent.apply(this, arguments);

        this.mainPanel.on('afterrender', function () {

            var viewer = CCR.xdmod.ui.Viewer.getViewer();
            if (viewer.el) viewer.el.mask('Loading...');

            this.getDurationSelector().disable();

            this.summaryStore.loadStartTime = new Date().getTime();
            this.reload();

            this.summaryStore.on('load', this.updateUsageSummary, this);

        }, this, {
            single: true
        });

    }, //initComponent


    // ------------------------------------------------------------------

    updateUsageSummary: function (store) {

        this.mainPanel.setTitle('<h3>' + this.getRoleSelector().getText() + '\'s Usage Summary</h3>');

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.mask('Loading...');
        }

        this.getDurationSelector().disable();

        if (store.getCount() <= 0) {
            CCR.xdmod.ui.toastMessage('Load Data', 'No Results');
            return;
        }

        var record = store.getAt(0);

        var keyStyle = {
            marginLeft: '4px',
            marginRight: '4px',
            fontSize: '11px',
            textAlign: 'center'
        };

        var valueStyle = {
            marginLeft: '2px',
            marginRight: '2px',
            textAlign: 'center',
            fontFamily: 'arial,"Times New Roman",Times,serif',
            fontSize: '11px',
            letterSpacing: '0px'
        };

        var summaryFormat = [

            {

                title: 'Activity',
                items: [

                    {
                        title: 'Users',
                        fieldName: 'active_person_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'PIs',
                        fieldName: 'active_pi_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'Allocations',
                        fieldName: 'active_allocation_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'Institutions',
                        fieldName: 'active_institution_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }

                ]

            }, //Activity

            {

                title: 'Jobs',
                items: [

                    {
                        title: 'Total',
                        fieldName: 'job_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }, {
                        title: 'Gateway',
                        fieldName: 'gateway_job_count',
                        numberType: 'int',
                        numberFormat: '#,#'
                    }

                ]

            }, //Jobs

            {

                title: 'Service (XD SU)',
                items: [

                    {
                        title: 'Total',
                        fieldName: 'total_su',
                        numberType: 'float',
                        numberFormat: '#,#.0'
                    }, {
                        title: 'Avg (Per Job)',
                        fieldName: 'avg_su',
                        numberType: 'float',
                        numberFormat: '#,#.00'
                    }

                ]

            }, //Service (XD SU)

            {

                title: 'CPU Time (h)',
                items: [

                    {
                        title: 'Total',
                        fieldName: 'total_cpu_hours',
                        numberType: 'float',
                        numberFormat: '#,#.0'
                    }, {
                        title: 'Avg (Per Job)',
                        fieldName: 'avg_cpu_hours',
                        numberType: 'float',
                        numberFormat: '#,#.00'
                    }

                ]

            }, //CPU Time (h)

            {

                title: 'Wait Time (h)',
                items: [

                    {
                        title: 'Avg (Per Job)',
                        fieldName: 'avg_waitduration_hours',
                        numberType: 'float',
                        numberFormat: '#,#.00'
                    }

                ]

            }, //Wait Time (h)

            {

                title: 'Wall Time (h)',
                items: [{
                    title: 'Total',
                    fieldName: 'total_wallduration_hours',
                    numberType: 'float',
                    numberFormat: '#,#.0'
                }, {
                    title: 'Avg (Per Job)',
                    fieldName: 'avg_wallduration_hours',
                    numberType: 'float',
                    numberFormat: '#,#.00'
                }]

            }, //Wall Time (h)

            {

                title: 'Processors',
                items: [{
                    title: 'Max',
                    fieldName: 'max_processors',
                    numberType: 'int',
                    numberFormat: '#,#'
                }, {
                    title: 'Avg (Per Job)',
                    fieldName: 'avg_processors',
                    numberType: 'int',
                    numberFormat: '#,#'
                }]

            } //Processors

        ]; //summaryFormat

        this.toolbar.removeAll();

        Ext.each(summaryFormat, function (itemGroup) {

            var itemTitles = [],
                items = [];

            Ext.each(itemGroup.items, function (item) {

                var itemData = record.get(item.fieldName),
                    itemNumber;

                if (itemData) {

                    if (item.numberType === 'int') {
                        itemNumber = parseInt(itemData, 10);
                    } else if (item.numberType === 'float') {
                        itemNumber = parseFloat(itemData);
                    }

                    itemTitles.push({
                        xtype: 'tbtext',
                        text: item.title + ':',
                        style: keyStyle
                    });

                    items.push({
                        xtype: 'tbtext',
                        text: itemNumber.numberFormat(item.numberFormat),
                        style: valueStyle
                    });

                } //if (itemdata)

            }); //Ext.each(itemGroup.items, ...

            if (items.length > 0) {

                this.toolbar.add({
                    xtype: 'buttongroup',
                    columns: items.length,
                    title: itemGroup.title,
                    items: itemTitles.concat(items)
                });

            } //if (items.length > 0)

        }, this); //Ext.each(summaryFormat, …

        this.reloadPortlets(store);

        this.portal.doLayout();
        this.toolbar.doLayout();

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.unmask();
        }

        this.getDurationSelector().enable();

        var loadTime = (new Date().getTime() - store.loadStartTime) / 1000.0;
        CCR.xdmod.ui.toastMessage('Load Data', 'Complete in ' + loadTime + 's');

    }, //updateUsageSummary

    // ------------------------------------------------------------------

    reload: function () {

        if (!this.getDurationSelector().validate()) {
            return;
        }

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) {
            viewer.el.mask('Processing Query...');
        }

        this.getDurationSelector().disable();

        var startDate = this.getDurationSelector().getStartDate().format('Y-m-d');
        var endDate = this.getDurationSelector().getEndDate().format('Y-m-d');
        var aggregationUnit = this.getDurationSelector().getAggregationUnit();

        Ext.apply(this.summaryStore.baseParams, {

            start_date: startDate,
            end_date: endDate,
            aggregation_unit: aggregationUnit,
            query_group: this.getRoleSelector().value + '_summary',
            public_user: this.public_user

        });

        this.summaryStore.loadStartTime = new Date().getTime();
        this.summaryStore.removeAll(true);
        this.summaryStore.load();

    }, //reload

    // ------------------------------------------------------------------

    reloadPortlets: function (store) {

        if (store.getCount() <= 0) {
            return;
        }

        this.portal.removeAll(true);
		
		var portletAspect = 11.0 / 17.0;
        var portletWidth = 580;
		var portletPadding = 25;
		var portalWidth = this.portal.getWidth();
        var portalColumns = new Array();

        portalColumnsCount = Math.max(1, Math.round(portalWidth / portletWidth) );
		
		portletWidth = (portalWidth-portletPadding) / portalColumnsCount;

	//	alert(portalColumnsCount+' '+this.portal.getWidth()+' '+portletWidth);
        for (var i = 0; i < portalColumnsCount; i++) {

            var portalColumn = new Ext.ux.PortalColumn({
                width: portletWidth,
                style: 'padding:1px 1px 1px 1px'
            });

            portalColumns.push(portalColumn);
            this.portal.add(portalColumn);

        } //for

        var charts = Ext.util.JSON.decode(store.getAt(0).get('charts'));
        
        var getTrackingConfig = function(panel_ref) {
        
            return {
               title: truncateText(panel_ref.title),
               index: truncateText(panel_ref.config.index),
               start_date: panel_ref.config.start_date,
               end_date: panel_ref.config.end_date                  
            };
                                      
        }//getTrackingConfig

        for (var i = 0; i < charts.length; i++) {

            var config = charts[i];

            config = Ext.util.JSON.decode(config);
            config.active_role = this.getRoleSelector().value;
            config.start_date = this.getDurationSelector().getStartDate().format('Y-m-d');
            config.end_date = this.getDurationSelector().getEndDate().format('Y-m-d');
            config.aggregation_unit = this.getDurationSelector().getAggregationUnit();
            config.font_size = 2;

            var title = config.title;
            config.title = '';

            var portlet = new Ext.ux.Portlet({

                config: config,
                index: i,

                title: (function () {

                    if (title.length > 60) {
                        return title.substring(0, 57) + '...';
                    } else {
                        return title;
                    }

                }()),

                tools: [

                    {
                        id: 'gear',
                        hidden: this.public_user,
                        qtip: 'Edit in Usage Explorer',
                        scope: this,

                        handler: function (event, toolEl, panel, tc) {

                            var trackingConfig = getTrackingConfig(panel);
                            XDMoD.TrackEvent('Summary', 'Clicked On Edit in Usage Explorer tool', Ext.encode(trackingConfig));
                            
                            var config = panel.config;
                            config.font_size = 3;
                            config.title = panel.title;
                            config.featured = true;
                            config.summary_index = (config.preset ? 'summary_' : '') + config.index;
                            config.active_role = this.getRoleSelector().value;

                            XDMoD.Module.UsageExplorer.setConfig(config, config.summary_index);

                        } //handler

                    },

                    {
                        id: 'help'
                    }

                ],

                width: portletWidth,
                height: portletWidth * portletAspect,
                layout: 'fit',
                items: [],
                
                listeners: {
                
                  collapse: function(panel) {
                     
                     var trackingConfig = getTrackingConfig(panel);
                     XDMoD.TrackEvent('Summary', 'Collapsed Chart Entry', Ext.encode(trackingConfig));
                     
                  },//collapse

                  expand: function(panel) {
                  
                     var trackingConfig = getTrackingConfig(panel);
                     XDMoD.TrackEvent('Summary', 'Expanded Chart Entry', Ext.encode(trackingConfig));
                     
                  }//expand                  

                }//listeners

            }); //portlet

            var hcp = new CCR.xdmod.ui.HighChartPanel({

                credits: false,

                store: new CCR.xdmod.CustomJsonStore({

                    portlet: portlet,

                    listeners: {

                        load: function (store) {

                            var dimensions = store.getAt(0).get('dimensions');
                            var dims = '';
                            for (dimension in dimensions) {
                                dims += '<li><b>' + dimension + ':</b> ' + dimensions[dimension] + '</li>';
                            }
                            var metrics = store.getAt(0).get('metrics');

                            var mets = '';
                            for (metric in metrics) {
                                mets += '<li><b>' + metric + ':</b> ' + metrics[metric] + '</li>';
                            }
                            this.portlet.getTool('help').dom.qtip = '<ul>' + dims + '</ul><hr/>' + '<ul>' + mets + '</ul>';

                        } //load

                    }, //listeners

                    autoDestroy: true,
                    root: 'data',
                    autoLoad: true,
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
                        'dimensions',
                        'metrics',
                        'plotOptions',
                        'reportGeneratorMeta'
                    ],

                    baseParams: {
                        operation: 'get_data',
                        showContextMenu: false,
                        config: Ext.util.JSON.encode(config),
                        format: 'hc_jsonstore',
                        public_user: this.public_user,
                        aggregation_unit: this.getDurationSelector().getAggregationUnit(),
                        width: portletWidth,
                        height: portletWidth * portletAspect
                    },

                    proxy: new Ext.data.HttpProxy({
                        method: 'POST',
                        url: 'controllers/usage_explorer.php'
                    })

                }) //store

            }); //hcp

            portlet.add(hcp);

            portalColumns[i % portalColumnsCount].add(portlet);

        } //for (var i = 0; i < charts.length; i++)

    } //reloadPortlets

}); //XDMoD.Module.Summary