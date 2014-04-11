/*  
 * JavaScript Document
 * Viewer
 * @author Amin Ghadersohi
 * @date 2013-June-13
 *
 *
 */
CCR.xdmod.ui.RoleSelector = Ext.extend(Ext.Button, {

    initComponent: function () {

        var self = this;

        var roleEntries = new Array();
        var activeIndex = 0;

        for (var x = 0; x < CCR.xdmod.ui.allRoles.length; x++) {

            if (CCR.xdmod.ui.allRoles[x].is_active == 1)
                activeIndex = x;

            roleEntries.push(

                new Ext.menu.CheckItem({

                    text: '<span style="color: #00f">' + CCR.xdmod.ui.allRoles[x].description + '</span>',
                    value: CCR.xdmod.ui.allRoles[x].param_value,
                    scope: this,
                    group: 'active_role_collection_' + this.id,

                    checked: (CCR.xdmod.ui.allRoles[x].is_active == 1),
                    handler: function (b, e) {

                        self.setText(b.text);
                        self.value = b.value;

                        if (self.changeHandler != undefined)
                            self.changeHandler(b.value);

                    } //handler 		

                })

            );

        } //for

        roleEntries[activeIndex].checked = true;

        Ext.apply(this, {

            iconCls: 'bookmark',

            value: roleEntries[activeIndex].value,
            text: roleEntries[activeIndex].text,

            menu: new Ext.menu.Menu({

                items: roleEntries

            })

        });

        CCR.xdmod.ui.RoleSelector.superclass.initComponent.apply(this, arguments);

    } //initComponent

}); //CCR.xdmod.ui.RoleSelector