// JavaScript Document
/*
* JavaScript Document
* @author Amin Ghadersohi
* @date 2011-Feb-07 (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
*
* This class contains functionality for managing the main tabs of the xdmod portal user interface.
*
*/ 

xdmodviewer = function () {

    return {

        init: function () {

            Ext.History.init();
            Ext.QuickTips.init();

            Ext.apply(Ext.QuickTips.getQuickTip(), {

                maxWidth: 400,
                minWidth: 100,
                showDelay: 400,
                dismissDelay: 1000000

            });

            var viewer = new CCR.xdmod.ui.Viewer();
            viewer.render('viewer');

        } //init

    };

}(); //xdmodviewer

// ===========================================================================

CCR.xdmod.ui.Viewer = function (config) {

    CCR.xdmod.ui.Viewer.superclass.constructor.call(this, config);

} //CCR.xdmod.ui.Viewer

// ===========================================================================

Ext.apply(CCR.xdmod.ui.Viewer, {

    getViewer: function () {

        return CCR.xdmod.ui.Viewer.viewerInstance;

    }, //getViewer

    // ------------------------------------------------------------------

    refreshView: function (tab_id) {

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) viewer.el.mask('Loading...');

        var tree = Ext.getCmp('tree_tg_usage');

        if (!tree) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        var nodeToSelect = tree.getSelectionModel().getSelectedNode();

        if (!nodeToSelect) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        tree.getSelectionModel().unselect(nodeToSelect, true);
        tree.getSelectionModel().select(nodeToSelect);

    }, //refreshView

    // ------------------------------------------------------------------

    gotoChart: function (sub_role_category, menu_id, realm, id, durationSelectorId, chartToolbarSerialized) {

        var viewer = CCR.xdmod.ui.Viewer.getViewer();

        if (viewer.el) viewer.el.mask('Loading...');

        var tabPanel = Ext.getCmp('main_tab_panel2');

        if (!tabPanel) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        tabPanel.setActiveTab('tg_usage');

        var tree = Ext.getCmp('tree_tg_usage');

        if (!tree) {

            if (viewer.el) viewer.el.unmask();
            return;

        }

        var root = tree.getRootNode();

        tree.expandPath(root.getPath(), null, function (success, node) {

            if (!success) {

                if (viewer.el) viewer.el.unmask();
                return;

            }

            var menuNode = node.findChild('id', menu_id);

            tree.expandPath(menuNode.getPath(), null, function (success2, node2) {

                if (!success2) {

                    if (viewer.el) viewer.el.unmask();
                    return;

                }

                var roleCategorySelector = Ext.getCmp('role_category_selector_tg_usage');

                if (roleCategorySelector) {

                    roleCategorySelector.set(sub_role_category);

                }

                var durationSelector = Ext.getCmp('duration_selector_tg_usage');

                if (durationSelector) {

                    var sourceDurationSelector = Ext.getCmp(durationSelectorId);
                    var durationSelectorSerialized = sourceDurationSelector.serialize(true);
                    durationSelector.unserialize(durationSelectorSerialized);

                }

                var nodeToSelect = node2.findChild('id', id);

                if (!nodeToSelect) {

                    if (viewer.el) viewer.el.unmask();
                    return;

                }

                if (tree.getSelectionModel().isSelected(nodeToSelect)) tree.getSelectionModel().unselect(nodeToSelect, true);
                nodeToSelect.attributes.chartSettings = chartToolbarSerialized.replace(/`/g, '"');
                tree.getSelectionModel().select(nodeToSelect);

            }); //tree.expandPath(menuNode.getPath(),...

        }); //tree.expandPath(root.getPath(),...

    } //gotoChart

}); //Ext.apply(CCR.xdmod.ui.Viewer

// ===========================================================================

Ext.extend(CCR.xdmod.ui.Viewer, Ext.Viewport, {

    initComponent: function () {

        var viewStore = new Ext.data.JsonStore({

            url: 'controllers/user_interface.php',
            autoDestroy: true,
            autoLoad: false,
            root: 'data',
            successProperty: 'success',
            messageProperty: 'message',
            totalProperty: 'totalCount',

            fields: [
                'tabs'
            ],

            baseParams: {

                operation: 'get_tabs',
                public_user: CCR.xdmod.publicUser

            }

        }); //viewStore

        // ---------------------------------------------------------

        viewStore.on('exception', function (dp, type, action, opt, response, arg) {

            if (response.success == false) {

                Ext.MessageBox.alert("Error", response.message);

            }

        }, this);

        // ---------------------------------------------------------

        var tabPanel = new Ext.TabPanel({

            id: 'main_tab_panel2',
            frame: false,
            border: false,
            activeTab: 0,
            region: 'center',

            defaults: {
                tabCls: 'tab-strip'
            },

            listeners: {

                'tabchange': function (tabPanel, tab) {

                    CCR.xdmod.ui.activeTab = tab;
                    
                    if (tab)
                        XDMoD.TrackEvent("Tab Change", tab.title);

                    if (tab.id != 'tg_usage' && tab.id != 'app_kernels' && tab.id != 'allocations') {
                        Ext.History.add(tabPanel.id + CCR.xdmod.ui.tokenDelimiter + tab.id);
                    }

                } //tabchange

            } //listeners

        }); //tabPanel

        // ---------------------------------------------------------

        // Handle this change event in order to restore the UI to the appropriate history state
        Ext.History.on('change', function (token) {

            if (token) {

                Ext.menu.MenuMgr.hideAll();

                var parts = token.split(CCR.xdmod.ui.tokenDelimiter);

                if (parts[0] == 'main_tab_panel2') {

                    var tabPanel = Ext.getCmp(parts[0]);
                    var tabId = parts[1];

                    tabPanel.show();
                    tabPanel.setActiveTab(tabId);

                } else {

                    var tabPanel = Ext.getCmp('main_tab_panel2');
                    var tabId = parts[0];

                    tabPanel.show();
                    tabPanel.setActiveTab(tabId);

                }

            } //if(token)

        }); //Ext.History.on('change',…

        // ---------------------------------------------------------

        var tb = new Ext.Toolbar({

            region: 'center',

            items: [

                XDMoD.GlobalToolbar.Logo,

                {
                    xtype: 'tbtext',
                    text: 'Hello, <b><a href="javascript:CCR.xdmod.ui.actionLogin()">Sign In</a></b> to view personalized information.'
                },

                '->',

                XDMoD.GlobalToolbar.CustomCenterLogo,

                {

                    xtype: 'buttongroup',

                    items: [

                        XDMoD.GlobalToolbar.SignUp,
                        XDMoD.GlobalToolbar.About(),
                        XDMoD.GlobalToolbar.Contact(true),
                        XDMoD.GlobalToolbar.Help(tabPanel),

                    ] //items

                } //buttongroup

            ] //items

        }); //toolbar

        // ---------------------------------------------------------

        var mainPanel = new Ext.Panel({

            layout: 'border',
            tbar: tb,
            items: [tabPanel]

        }); //mainPanel

        // ---------------------------------------------------------

        Ext.apply(this, {

            id: 'xdmod_viewer',
            layout: 'fit',
            items: [mainPanel]

        }); //Ext.apply(this

        // ---------------------------------------------------------

        CCR.xdmod.ui.Viewer.superclass.initComponent.apply(this, arguments);

        // ---------------------------------------------------------

        mainPanel.on('render', function () {

            if (mainPanel.el) mainPanel.el.mask('Loading...');
            viewStore.load();

            viewStore.on('load', function (store) {

                if (mainPanel.el) mainPanel.el.mask('Loading...');
                if (store.getCount() <= 0) return;

                var tabs = Ext.util.JSON.decode(store.getAt(0).get('tabs'));

                for (var i = 0; i < tabs.length; i++) {

                    switch (tabs[i].tab) {

                    case 'tg_summary':

                        if (!CCR.xdmod.ui.tgSummaryViewer) {

                            CCR.xdmod.ui.tgSummaryViewer = new XDMoD.Module.Summary({

                                title: tabs[i].title,
                                tooltip: 'Displays summary information',
                                region: 'center',
                                border: true,
                                id: 'tg_summary',
                                public_user: CCR.xdmod.publicUser

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.tgSummaryViewer);

                        if (tabs[i].isDefault == true)
                            tabPanel.setActiveTab(CCR.xdmod.ui.tgSummaryViewer);

                        break;

                        // ==============================================================

                    case 'tg_usage':

                        if (!CCR.xdmod.ui.chartViewerTGUsage) {

                            CCR.xdmod.ui.chartViewerTGUsage = new XDMoD.Module.Usage({

                                title: tabs[i].title,
                                tooltip: 'Displays usage',
                                id: 'tg_usage',
                                public_user: CCR.xdmod.publicUser

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.chartViewerTGUsage);

                        if (tabs[i].isDefault == true)
                            tabPanel.setActiveTab(CCR.xdmod.ui.chartViewerTGUsage);

                        break;

                        // ==============================================================

                    default:

                        console.log("Unknown module: " + tabs[i].tab);

                        break;

                    } //switch(tabs[i].tab)

                } //for(var i = 0; i < tabs.length; i++)

                if (mainPanel.el) mainPanel.el.unmask();
                //CCR.xdmod.ui.actionLogin();

            }); //viewStore.on('load',...

        }, this, {
            single: true
        }); //mainPanel.on('render',…

        CCR.xdmod.ui.Viewer.viewerInstance = this;

    } //initComponent

}); //CCR.xdmod.ui.Viewer