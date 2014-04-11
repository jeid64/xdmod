Ext.namespace('XDMoD.UserManagement')

XDMoD.UserManagement.Panel = Ext.extend(Ext.TabPanel, {
    frame: false,
    border: false,
    activeTab: 0,

    defaults: {
        tabCls: 'tab-strip'
    },

    listeners: {
        tabchange: function (tabPanel, tab) {
            //alert(tab.id);
        }
    },

    constructor: function (config) {
        var account_requests = new XDMoD.AccountRequests();

        // NOTE: current_users is global.
        current_users = new XDMoD.CurrentUsers();

        var user_stats = new XDMoD.UserStats();

        this.items = [
            account_requests,
            current_users,
            user_stats
        ];

        XDMoD.UserManagement.Panel.superclass.constructor.call(this, config);
    }
});

