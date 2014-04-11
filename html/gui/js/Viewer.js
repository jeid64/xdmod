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
How to add a tab:
1) make a tab: either an Ext.Panel or something. for example: UsageExplorer.js
2) go to the classes\User\Roles directory. There you will find all the role classes you must add a line to each role that would have access to the tab/module (note that the center staff role just extends from center director and doesnt have any differences so far):
$this->addPermittedModule(new \User\Elements\Module('my_summary', false, "Summary"));
The first param of the Module class is an identifier for the module/tab, the second one is whether it should be selected by default and the third one is the title of the tab.
3) Then go to the Viewer.js file in the html\gui\js folder and scroll to line 617 or so and find the following loop and add an else statement to the if statement inside of it to match the module/tab that you just added:
for(var i = 0 ; i < tabs.length; i++)
{
if(tabs[i].tab == 'my_summary')
{
if(!CCR.xdmod.ui.mySummaryViewer)
{
CCR.xdmod.ui.mySummaryViewer = new CCR.xdmod.ui.MySummaryPortal({
myUsage: true,
title: tabs[i].title,
tooltip : 'Displays grid statistics pertinent to jobs by your role',
region: 'center',
border: true,
id: 'my_summary'
});
}
tabPanel.add(CCR.xdmod.ui.mySummaryViewer);
if(tabs[i].isDefault == true && !isCallCached)
{
tabPanel.setActiveTab(CCR.xdmod.ui.mySummaryViewer);
}
} else ...
}
*/ 


xdmodviewer = function () {

    return {

        init: function () {

            Ext.History.init();
            Ext.QuickTips.init();

            Ext.apply(Ext.QuickTips.getQuickTip(), {

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

        var tabPanel = Ext.getCmp('main_tab_panel');

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
                operation: 'get_tabs'
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

            id: 'main_tab_panel',
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

                    if (tab && tab.id != 'tg_usage' && tab.id != 'app_kernels' && tab.id != 'allocations') {
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

                if (parts[0] == 'main_tab_panel') {

                    var tabPanel = Ext.getCmp(parts[0]);
                    var tabId = parts[1];

                    tabPanel.show();
                    tabPanel.setActiveTab(tabId);

                } else {

                    var tabPanel = Ext.getCmp('main_tab_panel');
                    var tabId = parts[0];
                    tabPanel.show();
                    tabPanel.setActiveTab(tabId);

                }

            } //if (token)

        }); //Ext.History.on('change',…

        // ---------------------------------------------------------

        var userZone = [

            XDMoD.GlobalToolbar.Profile

        ]; //userZone

        var mgrZone = [

            XDMoD.GlobalToolbar.Dashboard,
            XDMoD.GlobalToolbar.Profile

        ]; //mgrZone

        // ---------------------------------------------------------

        var additionalWelcomeDetails = (CCR.xdmod.ui.isDeveloper == true) ? '<span style="color: #6e30fa">[Developer]</span>' : '';

        // ---------------------------------------------------------

        var tb = new Ext.Toolbar({

            region: 'center',

            items: [

                XDMoD.GlobalToolbar.Logo,

                {
                    xtype: 'tbtext',
                    text: 'Hello, <b id="welcome_message">' + CCR.xdmod.ui.fullName + '</b> ' + additionalWelcomeDetails + ' (<a href="javascript:CCR.xdmod.ui.actionLogout()" id="logout_link">logout</a>)'
                },

                '->',

               XDMoD.GlobalToolbar.CustomCenterLogo,

                {
                    xtype: 'buttongroup',
                    items: (CCR.xdmod.ui.isManager) ? mgrZone : userZone
                },

                {
                    xtype: 'buttongroup',

                    items: [

                        XDMoD.GlobalToolbar.About(),
                        XDMoD.GlobalToolbar.Contact(),
                        XDMoD.GlobalToolbar.Help(tabPanel),

                    ]

                }

            ] //items

        }); //Ext.Toolbar

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

            CCR.xdmod.ui.login_reminder.show().hide();

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
                                id: 'tg_summary'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.tgSummaryViewer);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.tgSummaryViewer);

                        break;

                        // ==============================================================

                    case 'dashboard':

                        if (!CCR.xdmod.ui.dashboard) {

                            CCR.xdmod.ui.dashboard = new CCR.xdmod.ui.Dashboard({

                                title: tabs[i].title,
                                tooltip: 'Displays summary reports',
                                region: 'center',
                                border: true,
                                id: 'dashboard'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.dashboard);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.dashboard);

                        break;

                        // ==============================================================

                    case 'my_usage':

                        if (!CCR.xdmod.ui.chartViewerMyUsage) {

                            CCR.xdmod.ui.chartViewerMyUsage = new XDMoD.Module.Usage({

                                myUsage: true,
                                title: tabs[i].title,
                                tooltip: 'Displays your resource usage',
                                id: 'my_usage'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.chartViewerMyUsage);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.chartViewerMyUsage);

                        break;

                        // ==============================================================

                    case 'tg_usage':

                        if (!CCR.xdmod.ui.chartViewerTGUsage) {

                            CCR.xdmod.ui.chartViewerTGUsage = new XDMoD.Module.Usage({

                                title: tabs[i].title,
                                tooltip: 'Displays usage',
                                id: 'tg_usage'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.chartViewerTGUsage);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.chartViewerTGUsage);

                        break;

                        // ==============================================================

                    case 'my_allocations':

                        if (!CCR.xdmod.ui.allocationViewer) {

                            CCR.xdmod.ui.allocationViewer = new XDMoD.Module.Allocations({

                                title: tabs[i].title,
                                tooltip: 'Displays your allocation usage',
                                id: 'allocations'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.allocationViewer);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.allocationViewer);

                        break;

                        // ==============================================================

                    case 'app_kernels':

                        if (!CCR.xdmod.ui.appKernelViewer) {

                            CCR.xdmod.ui.appKernelViewer = new XDMoD.Module.AppKernels({

                                title: tabs[i].title,
                                tooltip: 'Displays data reflecting the reliability and performance of grid resources',
                                id: 'app_kernels'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.appKernelViewer);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.appKernelViewer);

                        break;

                        // ==============================================================

                    case 'report_generator':

                        if (!CCR.xdmod.ui.reportGenerator) {

                            CCR.xdmod.ui.reportGenerator = new XDMoD.Module.ReportGenerator({
                                id: 'report_tab_panel'
                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.reportGenerator);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.reportGenerator);

                        break;

                        // ==============================================================

                    case 'custom_search':

                        if (!CCR.xdmod.ui.customSearch) {

                            CCR.xdmod.ui.customSearch = new CCR.xdmod.ui.CustomSearch({

                                id: 'search_usage',
                                title: tabs[i].title

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.customSearch);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.customSearch);

                        break;

                        // ==============================================================

                    case 'data_miner':

                        if (!CCR.xdmod.ui.appKernelExplorer) {

                            CCR.xdmod.ui.appKernelExplorer = new XDMoD.Module.AppKernelExplorer({
                                id: 'data_miner',
                                title: tabs[i].title
                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.appKernelExplorer);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.appKernelExplorer);

                        break;

                        // ==============================================================

                    case 'usage_explorer':

                        if (!CCR.xdmod.ui.usageExplorer) {

                            CCR.xdmod.ui.usageExplorer = new XDMoD.Module.UsageExplorer({
                                id: 'usage_explorer',
                                title: tabs[i].title
                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.usageExplorer);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.usageExplorer);

                        break;

                        // ==============================================================                  

                    case 'compliance':

                        if (!CCR.xdmod.ui.complianceTab) {

                            CCR.xdmod.ui.complianceTab = new XDMoD.Module.Compliance({
                                id: 'compliance_tab'
                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.complianceTab);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.complianceTab);

                        break;

                        // ==============================================================  

                    case 'sci_impact':

                        if (!CCR.xdmod.ui.impact) {

                            CCR.xdmod.ui.impact = new XDMoD.Module.SciImpact({

                                title: tabs[i].title,
                                tooltip: 'Scientific Impact by user, organization, and project',
                                id: 'sci_impact'

                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.impact);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.impact);

                        break;

                        // ==============================================================

                    case 'custom_query':

                        if (!CCR.xdmod.ui.customQuery) {

                            CCR.xdmod.ui.customQuery = new XDMoD.Module.CustomQueries({
                                id: 'custom_query',
                                title: tabs[i].title
                            });

                        }

                        tabPanel.add(CCR.xdmod.ui.customQuery);

                        if (tabs[i].isDefault == true && !isCallCached)
                            tabPanel.setActiveTab(CCR.xdmod.ui.customQuery);

                        break;

                        // ==============================================================  

                    default:

                        console.log("Unknown module: " + tabs[i].tab);

                        break;

                    } //switch(tabs[i].tab)

                } //for(var i = 0; i < tabs.length; i++)      

                if (mainPanel.el) mainPanel.el.unmask();

                consultCallCache();

                //Conditionally present the profile if an e-mail address has not been set
                xsedeProfilePrompt();

            }); ////viewStore.on('load',...

        }, this, {
            single: true
        }); //mainPanel.on('render',…

        CCR.xdmod.ui.Viewer.viewerInstance = this;

    } //initComponent

}); //CCR.xdmod.ui.Viewer