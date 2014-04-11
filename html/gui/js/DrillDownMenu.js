/*  
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2010-Aug-03
 *
 * This class contains the funcationality for the menu presented on drilldown on charts in xdmod
 *
 *
 * @class CCR.xdmod.ui.DrillDownMenu
 * @extends Ext.menu.Menu
 *
 * @constructor
 * @param {Object} config The configuration options
 * @ptype drilldownmenu
 */
CCR.xdmod.ui.DrillDownMenu = function (config) {

    CCR.xdmod.ui.DrillDownMenu.superclass.constructor.call(this, config);

} // CCR.xdmod.ui.DrillDownMenu


Ext.extend(CCR.xdmod.ui.DrillDownMenu, Ext.menu.Menu, {
    node: null,
    handler: function (drillDown) {

    },
    drillDownGroupBys: [],
    initComponent: function () {
        var items = [];
        if (this.drillDownGroupBys != '') {
            var groupByDescripter = this.drillDownGroupBys.split(',');

            for (var i = 0; i < groupByDescripter.length; i++) {
                var gbd = groupByDescripter[i].split('-');
                if (gbd.length == 2 &&
                    (this.node.attributes.parameters == null ||
                        (this.node.attributes.parameters[gbd[0]] == null &&
                            //this.node.getPath('text').search(gbd[1]) == -1 &&

                            ((gbd[0] != 'provider') || (this.node.attributes.parameters['resource'] == null && this.node.getPath('text').search('by Resource') == -1)) //&&

                            // ((gbd[0] != 'pi') || (this.node.attributes.parameters['person'] == null && this.node.getPath('text').search('by Person') == -1))

                        )
                    )) {
                    var disabled = false;
                    if (this.queryGroupname != 'my') {
                        for (var j = 0; j < CCR.xdmod.ui.disabledMenus[this.role].length; j++) {
                            if (CCR.xdmod.ui.disabledMenus[this.role][j].group_by == gbd[0] && CCR.xdmod.ui.disabledMenus[this.role][j].realm == this.realm) {
                                disabled = true;
                            }
                        }
                    }
                    var childItems = [
                        '<b class="menu-title">Available metrics:</b>'
                    ];
                    childItems.push({
                        text: 'test',
                        iconCls: 'chart'
                    });
                    items.push(
                        new Ext.menu.Item({
                            scope: this,
                            drillDown: groupByDescripter[i],
                            paramLabel: gbd[1],
                            text: gbd[1],
                            iconCls: 'drill',
                            disabled: disabled,
                            /*menu: {
								xtype: 'menu',
								showSeparator: false,
								items: childItems
							},*/
                            handler: function (b, e) {
                                this.handler(b.drillDown);
                            }
                        })
                    );
                }
            }
        }
        if (this.valueParam == 0) {
            //Ext.Msg.alert('Attention', 'Further drilldown is not available for this bar. The value of the selected datapoint is 0.');
            var items = [];
            items.push('<b class="menu-title">Further drilldown is not available for this bar.<br/>');
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });
        } else
        if (this.groupByIdParam < -9999) {
            //Ext.Msg.alert('Attention', 'Drilldown for this bar is not available at this time.');
            var items = [];
            items.push('<b class="menu-title">Drilldown for this bar is not available at this time.</b><br/>');
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });

        } else
        if (items.length > 0) {
            items.sort(function (a, b) {
                if (a.text == b.text) return 0;
                if (a.text < b.text) return -1;
                if (a.text > b.text) return 1;
            })
            if (this.label !== null) {
                items.unshift('<b class="menu-title">For ' + this.label.wordWrap(40, '<br/>') + ', Drilldown to:</b><br/>');
            } else {
                items.unshift('<b class="menu-title">Drilldown to:</b><br/>');
            }
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });
        } else {
            var items = [];
            items.push('<b class="menu-title">No further drilldowns available.</b><br/>');
            Ext.apply(this, {
                showSeparator: false,
                items: items
            });
        }
        // Call parent (required)
        CCR.xdmod.ui.DrillDownMenu.superclass.initComponent.apply(this, arguments);
    }

}); // CCR.xdmod.ui.DrillDownMenu