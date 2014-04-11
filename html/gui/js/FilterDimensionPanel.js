/*  
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2011-Dec-22
 *
 */
CCR.xdmod.ui.FilterDimensionPanel = function (config) {
    CCR.xdmod.ui.FilterDimensionPanel.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.FilterDimensionPanel


Ext.extend(CCR.xdmod.ui.FilterDimensionPanel, Ext.Panel, {
    dimension_id: '',
    defaultPageSize: 10,
    selectedFilters: [],
    getSelectedFilterIds: function () {
        var ret = [];
        for (var i = 0; i < this.selectedFilters.length; i++) {
            ret.push(this.selectedFilters[i].id);
        }
        return ret;
    },
    initComponent: function () {
    
        var origin = this.origin ? this.origin + ' -> ' : '';
        
        var store = new CCR.xdmod.CustomJsonStore({
            url: 'controllers/usage_explorer.php',
            fields: ['checked', 'name', 'id'],
            root: 'data',
            totalProperty: 'totalCount',
            idProperty: 'name',
            messageProperty: 'message',
            scope: this,
            baseParams: {
                operation: 'get_dimension',
                dimension_id: this.dimension_id,
                realm: this.realms[0],
                active_role: this.active_role
            }
        });
        store.on('beforeload', function (t, op) {
            t.baseParams.selectedFilterIds = this.getSelectedFilterIds().join(',');
            dimensionGrid.showMask();
        }, this);
        store.on('load', function (t, op) {
            dimensionGrid.hideMask();
        }, this);
		store.on('exception', function (t, op) {
            dimensionGrid.hideMask();
        }, this);
        var onCheckChange = function (index, record) {

            var filter = {
                id: /*this.realm+'_'+*/ this.dimension_id + '=' + record.data['id'],
                value_id: record.data['id'],
                value_name: record.data['name'],
                dimension_id: this.dimension_id,
                realms: this.realms,
                equals: function (other) {
                    return this.id == other.id && this.value_id == other.value_id && this.value_name == other.value_name && this.dimension_id == other.dimension_id && this.realms.join('') == other.realms.join('');
                }
            };
            var index = -1;
            for (var i = 0; i < this.selectedFilters.length; i++) {
                if (this.selectedFilters[i].equals(filter)) {
                    index = i;
                    break;
                }
            }

            if (record.data['checked']) {
                if (index < 0) {
                    this.selectedFilters.push(filter);
                }
            } else {
                if (index > -1) {
                    this.selectedFilters.splice(index, 1);
                }
            }

        }

        var checkColumn = new Ext.grid.CheckColumn({
            id: 'checked',
            width: 35,
            dataIndex: 'checked',
            scope: this,
                        
            onMouseDown: function (e, t) {
                
                if (Ext.fly(t).hasClass(this.createId())) {
                    
                    e.stopEvent();
                    var index = this.grid.getView().findRowIndex(t);
                    var record = this.grid.store.getAt(index);
                    record.set(this.dataIndex, !record.data[this.dataIndex]);
                                        
                    XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Toggled item in list', Ext.encode(record.data));
                    
                    onCheckChange.call(this.scope, index, record);
                }
                
            }
        });

        var dimensionGrid = new Ext.grid.GridPanel({
            id: 'filter_dimensions_' + this.id,
            store: store,

            autoScroll: true,
            rowNumberer: true,
            border: true,
            stripeRows: true,
            enableHdMenu: false,
            hideHeaders: true,
            disableSelection: true,
            autoExpandColumn: 'name',
            scope: this,

            viewConfig: {
                forceFit: true,
                scrollOffset: 2 // the grid will never have scrollbars
            },
            plugins: [checkColumn, new Ext.ux.plugins.ContainerBodyMask({
                msg: 'Loading...',
                masked: true
            })],
            columns: [
                checkColumn, {
                    header: '',
                    width: 300,
                    sortable: false,
                    dataIndex: 'name',
                    id: 'name'
                }
            ],
            listeners: {
                'rowmousedown': function (t, rowIndex, e) {
                    var record = t.store.getAt(rowIndex);
                    record.set('checked', !record.data['checked']);
                    XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Toggled item in list', Ext.encode(record.data));
                    onCheckChange.call(t.scope, rowIndex, record);
                }
            }
        });

        var pagingToolbar = new Ext.PagingToolbar({
            pageSize: this.defaultPageSize,
            store: store,
            displayInfo: true,
            displayMsg: 'Items {0} - {1} of {2}',
            emptyMsg: "No data"
        });

        pagingToolbar.on('change', function (total, pageObj) {

           XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Loaded page of data', pageObj.activePage + ' of ' + pageObj.pages);   
                
        });
                
        store.baseParams.start = pagingToolbar.start;
        store.baseParams.limit = pagingToolbar.pageSize;
        store.load();

        var searchField = new Ext.form.TwinTriggerField({
            xtype: 'twintriggerfield',
            validationEvent: false,
            validateOnBlur: false,
            trigger1Class: 'x-form-clear-trigger',
            trigger2Class: 'x-form-search-trigger',
            hideTrigger1: true,
            hasSearch: false,
            enableKeyEvents: true,
            onTrigger1Click: function () {
               
                XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Cleared search field');
               
                if (this.hasSearch) {
                    this.el.dom.value = '';
                    store.baseParams.start = 0
                    store.baseParams.limit = pagingToolbar.pageSize;
                    store.baseParams.search_text = '';
                    store.load();
                    this.triggers[0].hide();
                    this.hasSearch = false;
                }
            },

            onTrigger2Click: function () {
            
                XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Used search field', Ext.encode({search_text: this.getRawValue()}));
                
                var v = this.getRawValue();
                if (v.length < 1) {
                    this.onTrigger1Click();
                    return;
                }
                store.baseParams.start = 0
                store.baseParams.limit = pagingToolbar.pageSize;
                store.baseParams.search_text = v;
                store.load();
                this.hasSearch = true;
                this.triggers[0].show();
            },
            listeners: {
                'specialkey': function (field, e) {
                    // e.HOME, e.END, e.PAGE_UP, e.PAGE_DOWN,
                    // e.TAB, e.ESC, arrow keys: e.LEFT, e.RIGHT, e.UP, e.DOWN
                    if (e.getKey() == e.ENTER) {
                        searchField.onTrigger2Click();
                    }
                }
            }
        });

        Ext.apply(this, {
            title: '<img class="x-panel-inline-icon filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> Filter by ' + this.dimension_label,
            width: 450,
            height: 375,
            border: false,
            layout: 'border',
            bodyStyle: 'padding:5px 5px 0',
            tbar: [
                '->',
                'Search:',
                searchField
            ],
            items: [{
                xtype: 'panel',
                border: false,
                region: 'center',
                layout: 'fit',
                items: dimensionGrid,
                bbar: pagingToolbar
            }],
            buttons: [{
                scope: this,
                text: 'Ok',
                handler: function (b, e) {
                    XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Clicked on Ok');
                    b.scope.fireEvent('ok');
                }
            }, {
                scope: this,
                text: 'Cancel',
                handler: function (b, e) {
                    XDMoD.TrackEvent('Usage Explorer', origin + 'Filter Pane -> Clicked on Cancel');
                    b.scope.fireEvent('cancel');
                }
            }]
        });

        CCR.xdmod.ui.FilterDimensionPanel.superclass.initComponent.apply(this, arguments);

        this.addEvents("ok");
        this.addEvents("cancel");
    }
});