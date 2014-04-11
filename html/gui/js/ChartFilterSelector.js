/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * This class contains functionality for the chart filter panel in the usage tab
 *
 */
CCR.xdmod.ui.ChartFilterSelector = function (config) {
    CCR.xdmod.ui.ChartFilterSelector.superclass.constructor.call(this, config);
}

Ext.extend(CCR.xdmod.ui.ChartFilterSelector, Ext.form.FormPanel, {
    itemType: 'item',
    itemTypeLabel: 'Item',
    bodyStyle: 'padding:10px;',
    loadData: function (data, itemType, itemTypeLabel) {
        if (this.isRendered) {
            this.getForm().findField('itemselector').reset();
        }
        this.itemType = itemType;
        this.itemTypeLabel = itemTypeLabel;
        if (data && data.length > 0) {
            this.ds.loadData(data);
        } else {
            this.ds.removeAll(true);
        }
    },

    isRendered: false,
    getSelected: function () {
        if (this.isRendered) {
            var itemSelector = this.getForm().findField('itemselector');

            var values = [];

            for (var i = 0; i < itemSelector.toMultiselect.view.store.getCount(); i++) {
                values.push(itemSelector.toMultiselect.view.store.getAt(i).get((itemSelector.toMultiselect.view.valueField != null) ? itemSelector.toMultiselect.view.valueField : 'value'));
            }

            return values;
        } else {
            return [];
        }
    },
    getSelectedText: function () {
        if (this.isRendered) {
            var itemSelector = this.getForm().findField('itemselector');

            var values = [];

            for (var i = 0; i < itemSelector.toMultiselect.view.store.getCount(); i++) {
                values.push(itemSelector.toMultiselect.view.store.getAt(i).get((itemSelector.toMultiselect.view.displayField != null) ? itemSelector.toMultiselect.view.displayField : 'text'));
            }

            return values;
        } else {
            return [];
        }
    },
    initComponent: function () {
        this.ds = new Ext.data.ArrayStore({
            data: [],
            fields: ['value', 'text'],
            sortInfo: {
                field: 'text',
                direction: 'ASC'
            }
        });

        var self = this;
        
        var items = [{
            fieldLabel: 'Select custom data elements to display.  The column on the left contains<br/>the list of datasets that are available for display while the column on the<br/>right shows the datasets currently selected for display.  If no datasets<br/>are selected in the right column then all available datasets will be<br/>represented. <br/> Note: Charts will display only the top n datasets, where n is determined by<br/>the metric and chart dimensions. The remaining datasets will be grouped into<br/>a single dataset labelled "All|Average|Maximum|Minimum <i>m-n</i> others".',
            labelSeparator: '',
            xtype: 'itemselector',
            name: 'itemselector',
            //frame: true,
            scope: this,
            imagePath: 'gui/lib/extjs/examples/ux/images/',
            
            listeners: {
               migrate_to_from: function(rec_data) {
                  self.fireEvent('migrate_to_from', rec_data);
               },
               migrate_from_to: function(rec_data) {
                  self.fireEvent('migrate_from_to', rec_data);
               }
            },
                 
            multiselects: [{
                width: 200,
                //frame: false,
                height: 200,
                store: this.ds,
                displayField: 'text',
                valueField: 'value'                
            }, {
                //frame: false,
                width: 200,
                height: 200,
                store: [],
                displayField: 'text',
                valueField: 'value',
                tbar: [{
                    scope: this,
                    text: 'clear',
                    tooltip: 'Clear all filters',
                    handler: function () {
                        self.fireEvent('filters_cleared');
                        this.getForm().findField('itemselector').reset();
                    }
                }]
            }]
        }];

        var buttons = [{
            scope: this,
            text: 'Apply',
            handler: function () {
                if (this.getForm().isValid()) {
                    this.fireEvent("selectionchange", this.itemType, this.itemTypeLabel, this.getSelected(), this.getSelectedText());
                }
            }
        }, {
            scope: this,
            text: 'Reset',
            handler: function () {
                this.fireEvent("selectionreset");
            }
        }];

        Ext.apply(this, {
            width: 442,
            height: 380,
            labelAlign: 'top',
            border: false,
            //hideLabels: true,
            items: items,
            buttons: buttons
        });
        this.on('render', function () {
            this.isRendered = true;
        }, this, {
            single: true
        });

        CCR.xdmod.ui.ChartFilterSelector.superclass.initComponent.apply(this, arguments);

        this.addEvents("selectionchange", "migrate_from_to", "migrate_to_from", "filters_cleared");
    }
});