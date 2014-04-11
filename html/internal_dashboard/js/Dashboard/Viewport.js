/**
 * Internal operations dashboard viewport.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

Ext.namespace('XDMoD.Dashboard');

XDMoD.Dashboard.Viewport = Ext.extend(Ext.Viewport, {
    layout: 'border',

    constructor: function (config) {
        config = config || {};

        this.items = [
            {
                id: 'dashboard-header',
                frame: false,
                border: false,
                region: 'north',
                height: 40,
                bodyStyle: {
                    backgroundColor: '#fef5e9'
                },
                html: '<table><tr>' +
                    '<td style="width:300px;"><img src="images/masthead.png"></td>' +
                    '<td>Welcome, <b>' + dashboard_user_full_name + '</b>' +
                    ' [<a href="javascript:void(0)" onClick="return false;" id="header-logout">Logout</a>]</td>' +
                    '</tr></table>'
            },
            {
                xtype: 'tabpanel',
                activeTab: 0,
                frame: false,
                border: false,
                region: 'center',
                defaults: {
                    tabCls: 'tab-strip'
                },
                items: config.items
            }
        ];

        delete config.items;

        Ext.apply(config, {
            listeners: {
                'afterrender': {
                    fn: function () {
                        var logoutLink = Ext.get('header-logout');
                        logoutLink.on('click', this.logout, this);
                    },
                    scope: this
                }
            }
        });

        XDMoD.Dashboard.Viewport.superclass.constructor.call(this, config);
    },

    logout: function () {
        actionLogout();
    }
});

