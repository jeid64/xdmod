/*  
* JavaScript Document
* @author Amin Ghadersohi
* @date 2012-4-27
*
* the panel for adding data to a chart in the usage explorer
*/

CCR.xdmod.ui.AddDataPanel = function (config)
{
    CCR.xdmod.ui.AddDataPanel.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.AddDataPanel


Ext.apply(CCR.xdmod.ui.AddDataPanel, 
{
	colors: 
	[
		['auto'],
        ['000000'], ['993300'], ['333300'], ['003300'], ['003366'], ['000080'], ['333399'], ['333333'],
        ['800000'], ['FF6600'], ['808000'], ['008000'], ['008080'], ['0000FF'], ['666699'], ['808080'],
        ['FF0000'], ['FF9900'], ['99CC00'], ['339966'], ['33CCCC'], ['3366FF'], ['800080'], ['969696'],
        ['FF00FF'], ['FFCC00'], ['FFFF00'], ['00FF00'], ['00FFFF'], ['00CCFF'], ['993366'], ['C0C0C0'],
        ['FF99CC'], ['FFCC99'], ['FFFF99'], ['CCFFCC'], ['CCFFFF'], ['99CCFF'], ['CC99FF']//, ['FFFFFF']
    ],
	display_types:
	[
		['line','Line'],
		['column','Bar'],
		//['bar', 'Horizontal Bar'],
		['area','Area'],
		['scatter','Scatter'],
		['spline','Spline'],
		['areaspline','Area Spline'],
		['pie','Pie'] 
	],
	line_types:
	[
		['Solid','Solid',''],
        ['ShortDash','ShortDash','6,2'],
        ['ShortDot','ShortDot','2,2'],
        ['ShortDashDot','ShortDashDot','6,2,2,2'],
        ['ShortDashDotDot','ShortDashDotDot','6,2,2,2,2,2'],
        ['Dot','Dot','2,6'],
        ['Dash','Dash','8,6'],
        ['LongDash','LongDash','16,6'],
        ['DashDot','DashDot','8,6,2,6'],
        ['LongDashDot','LongDashDot','16,6,2,6'],
        ['LongDashDotDot','LongDashDotDot','16,6,2,6,2,6']
	],
	line_widths:
	[
		[1,'1'],
       	[2,'2'],
	   	[3,'3'],
	   	[4,'4'],
	   	[5,'5'],
	   	[6,'6'],
	   	[7,'7'],
	   	[8,'8']
	],
	combine_types:
	 [
		['side', 'Side by Side'],
		['stack','Stacked'],
		['percent','Percentage']
	],
	sort_types: 
	[
		['none', 'None'],
		['value_asc','Values Ascending'],
		['value_desc','Values Descending'],
		['label_asc','Labels Ascending'],
		['label_desc','Labels Descending']
	]
});

Ext.extend(CCR.xdmod.ui.AddDataPanel, Ext.Panel,
{
	color: 'auto',
	log_scale: false,
	std_err: false,
	value_labels: false,
	display_type: 'column',
	combine_type: 'side',
	sort_type: 'label_asc',
	ignore_global: false,
	long_legend: true,
	update_record: false,
	x_axis: false,
	has_std_err: false,
	trend_line: false,
	line_type: 'Solid',
	line_width: 2,
	shadow: false,
	getSelectedFilters: function()
	{
		var ret = [];
		this.filtersStore.each(
		function(record)
		{
			ret.push(record.data);
		});
		return {
			data: ret,
			total: ret.length
		};
	},
    initComponent: function ()
    {	
		if(!this.line_type || this.line_type === '') this.line_type = 'Solid';	
		if(!this.line_width || this.line_width === 0) this.line_width = 2;
		
		var filtersMenu = new Ext.menu.Menu(
		{
			 showSeparator: false,
			 ignoreParentClicks:true
		});	
		var filterItems = [];
		var filterMap = {};
		filtersMenu.removeAll(true);
		
		var realm_dimensions = this.realms[this.realm]['dimensions'];
		
		for(x in realm_dimensions)
		{
			if(x == 'none' || realm_dimensions[x].text == undefined) continue;
		
			if(filterMap[x] == undefined  )
			{
				filterMap[x] = filterItems.length;
				
				filterItems.push(
				{
					text: realm_dimensions[x].text,
					iconCls: 'menu',
					realms: [this.realm],
					dimension: x,
					scope: this,
					handler: function(b,e)
					{
					   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected filter from menu', b.text);
						filterButtonHandler.call(b.scope,filtersGridPanel.toolbars[0].el,b.dimension, b.text, b.realms);
					}
				});
				
			}else
			{
				if(filterItems[filterMap[x]].realms.indexOf(this.realm) == -1)
				{
					filterItems[filterMap[x]].realms.push(this.realm);
				}
			}
		}
			
	
		
		filterItems.sort(
			function(a,b)
			{
				var nameA=a.text.toLowerCase(), nameB=b.text.toLowerCase();
				if (nameA < nameB) //sort string ascending
					return -1 ;
				if (nameA > nameB)
					return 1;
				return 0 ;//default return value (no sorting)	
			}
		);

		filtersMenu.addItem(filterItems);
	
		var filterButtonHandler = function(el, dim_id, dim_label,realms)
		{
			if (!dim_id || !dim_label) return;
					
			var filterDimensionPanel = new CCR.xdmod.ui.FilterDimensionPanel({
			   origin: 'Data Series Definition',
				dimension_id: dim_id, 
				realms: realms,
				active_role: this.active_role,
				dimension_label: dim_label,
				selectedFilters: []
			});
			
			filterDimensionPanel.on('cancel',function()
			{
			   
			   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Cancelled filters panel');
			
				addFilterMenu.closable = true;
				addFilterMenu.hide();
				
			});
			
			filterDimensionPanel.on('ok',function()
			{
			
				for(var filter = 0 ; filter < filterDimensionPanel.selectedFilters.length ; filter++)
				{
					var oldRec =addFilterMenu.scope.filtersStore.findBy(function(r)
					{
						if(r.data.id === filterDimensionPanel.selectedFilters[filter].id)
						{
							return true;
						}
					}); 
					if( oldRec == -1)
					{
					
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
						
						XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Introduced filter', Ext.encode(record_config));
						
					}
					else
					{
						var r = addFilterMenu.scope.filtersStore.getAt(oldRec);
						r.set('checked', true);
					}
				}
				
				//addFilterMenu.scope.saveFiltersToUserProfile();
				addFilterMenu.closable = true;
				addFilterMenu.hide();
			});
			
			var addFilterMenu = new Ext.menu.Menu(
			{
				showSeparator: false,
				items: [filterDimensionPanel],
				scope: this,
				closable: false,
				listeners : 
				{
					'beforehide' : function(t)
					 {
						return t.closable;
					},
					'hide': function(t)
					{
						t.scope.el.unmask();
					},
					'show': function(t)
					{
						t.scope.el.mask();
					}
				}
			});	
			addFilterMenu.ownerCt = this;
			addFilterMenu = Ext.menu.MenuMgr.get(addFilterMenu);
			addFilterMenu.show(el,'tl-bl?');		
		}
	
		var realmData = [];
		for(realm in this.realms)
		{
			realmData.push([realm]);
		}
		
		var metricData = [];
		for(metric in this.realms[this.realm]['metrics'])
		{
			metricData.push([metric,this.realms[this.realm]['metrics'][metric].text]);
		}
		
		var dimenionsData = [];
		for(dimension in this.realms[this.realm]['dimensions'])
		{
			dimenionsData.push([dimension,this.realms[this.realm]['dimensions'][dimension].text]);
		}		
		
		
		
		var activeFilterCheckColumn = new Ext.grid.CheckColumn(
		{
			id: 'checked',
			sortable: false, 
			dataIndex: 'checked',
			header: 'Local',
			tooltip: 'Check this column to apply filter to this dataset',
			scope: this,
			width: 50,
			hidden: false,
			
         checkchange: function(record, data_index, checked) {
         
            XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Toggled filter checkbox', Ext.encode({
               dimension: record.data.dimension_id,
               value: record.data.value_name,
               checked: checked
            }));
                        
         }//checkchange
			
		});
		this.filtersStore = new Ext.data.GroupingStore(
		{
			autoDestroy: true,
			idIndex: 0, 
        	groupField:'dimension_id',
			sortInfo: 
			{
				field: 'dimension_id',
				direction: 'ASC' // or 'DESC' (case sensitive for local sorting)
			},
			reader: new Ext.data.JsonReader(
				{
					totalProperty: 'total',
					idProperty: 'id',
					root: 'data'
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
		});
				
		if(this.filters)this.filtersStore.loadData(this.filters,false);
		
		/*this.masterFiltersStore.each(
			function(record)
			{
				var r = new this.filtersStore.recordType(jQuery.extend(true,{},record.data));
				this.filtersStore.add(r);
			},
			this
		);*/
		var checkAllButton = new Ext.Button({
			text: 'Check All',
			scope: this,
			handler: function(b,e)
			{
			   
			   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on Check All in Local Filters pane');
			
				this.filtersStore.each(function(r)
				{
					r.set('checked', true);
				});
			}
		});
		var uncheckAllButton = new Ext.Button({
			text: 'Uncheck All',
			scope: this,
			handler: function(b,e)
			{
			
			   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on Uncheck All in Local Filters pane');
			   
				this.filtersStore.each(function(r)
				{
					r.set('checked', false);
				});
			}
		});
		var removeFilterItem = new Ext.Button(
		{
			iconCls: 'delete_filter',
			tooltip: 'Delete highlighted filter(s)',
			text: 'Delete',
			disabled: true,
			scope: this,
			handler: function(i,e)
			{  
			   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on Delete in Local Filters pane');
			   
				var records = filtersGridPanel.getSelectionModel().getSelections();

				for (i = 0; i < records.length; i++) {

				  XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Confirmed deletion of filter', Ext.encode({

				     dimension: records[i].data.dimension_id,
				     value: records[i].data.value_name
				     
				  }));
            
            }//for (each record selected)

				filtersGridPanel.store.remove(records);
				
			}
		});
		
		var filtersGridPanel = new Ext.grid.GridPanel(
		{		
			header: false,	
			height: 130,
			id: 'grid_filters_' + this.id,
		    useArrows: true,
		    autoScroll: true,
			sortable: false,
			enableHdMenu: false,
			loadMask: true,
			margins: '0 0 0 0',
			view: new Ext.grid.GroupingView(
			{
				emptyText: 'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.',
				forceFit:true,
				groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
			}),
			sm: new Ext.grid.RowSelectionModel(
			{
				singleSelect: true,
				listeners: 
				{
				
               'rowselect': function(sm, row_index, record) {
                 
                  XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected a query filter', Ext.encode({
                     dimension: record.data.dimension_id,
                     value: record.data.value_name
                  }));
               
               },
               
               'rowdeselect': function(sm, row_index, record) {
               
                  XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> De-selected a query filter', Ext.encode({
                     dimension: record.data.dimension_id,
                     value: record.data.value_name
                  }));
                                      
               },   
                    
					'selectionchange': function(sm) {
						
						removeFilterItem.setDisabled(sm.getCount() <= 0);
	
					}
					
				}
			}),
			plugins: [
				activeFilterCheckColumn
				//,new Ext.ux.plugins.ContainerBodyMask ({ msg:'No filters created.<br/> Click on <img class="x-panel-inline-icon add_filter" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> to create filters.', masked:true})
			],
			
			autoExpandColumn: 'value_name',
			store: this.filtersStore,
		
			columns: [
				activeFilterCheckColumn,
				//{id: 'realm', tooltip: 'Realm', width: 80, header: 'Realm',  dataIndex: 'realm'},
				{id: 'dimension', tooltip: 'Dimension', renderer:  CCR.xdmod.ui.gridComboRenderer(this.dimensionsCombo), width: 80, header: 'Dimension',  dataIndex: 'dimension_id'},
				{id: 'value_name', tooltip: 'Filter', width: 100, header: 'Filter',  dataIndex: 'value_name'}
			],
			tbar: [
				{
					scope: this,
					iconCls: 'add_filter',
					text: 'Create Filter',
					handler: function() {
					 
			         XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on Create Filter');
					 
					},
					menu: filtersMenu
				},
				'->',
				'-',
				checkAllButton,
				'-',
				uncheckAllButton,
				'-',
				removeFilterItem
			]
		});
		/*
		this.filtersStore.on('add',function()
		{
			this.filtersGridPanel.hideMask();
			//this.filtersGridPanel.getTool('undo').show();
		},this);
		this.filtersStore.on('remove',function()
		{
			if(this.filtersStore.getCount() == 0)	
			{
				this.filtersGridPanel.showMask();
				//this.filtersGridPanel.getTool('undo').hide();
			}
		},this);*/
		this.has_std_err = this.realms[this.realm]['metrics'][this.metric].std_err;
		this.stdErrorCheckBox = new Ext.form.Checkbox(
		{
			fieldLabel: 'Std Err Bars',
			name: 'std_err',
			xtype: 'checkbox',
			boxLabel: 'Show the std err bars on each data point',
			disabled: ! this.has_std_err || this.log_scale,
			checked: this.std_err,
			listeners:
			{
				scope: this,
				'check': function(checkbox, check)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
					this.std_err = check;
				}
			}
		});
		
		this.trendLineCheckBox =  new Ext.form.Checkbox(
		{
			fieldLabel: 'Trend Line',
			name: 'trend_line',
			xtype: 'checkbox',
			boxLabel: 'Show trend line',
			checked: this.trend_line && this.timeseries,
			disabled: !this.timeseries,
			listeners: 
			{
				scope: this,
				'check': function(checkbox, check)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
					this.trend_line = check;
				}
			}
		});
		
		this.valueLabelsCheckbox = new Ext.form.Checkbox({
			fieldLabel: 'Value Labels',
			name: 'value_labels',
			xtype: 'checkbox',
			checked: this.value_labels,
			boxLabel: 'Show a value label on each data point',
			listeners:
			{
				scope: this,
				'check': function(checkbox, check)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
					this.value_labels = check;
				}
			}
		});
		
		this.displayTypeCombo = {
			flex: 2.5,
			fieldLabel: 'Display Type',
			name: 'display_type',
			xtype: 'combo',
			mode: 'local',
			editable: false,
			store: new Ext.data.ArrayStore({
				id: 0,
				fields: [
					'id',
					'text'
				],
				data: CCR.xdmod.ui.AddDataPanel.display_types
			}),
			disabled: false,
			value: this.display_type,
			valueField: 'id',
			displayField: 'text',
			triggerAction: 'all',
			listeners:
			{
				scope: this,
				'select': function(combo, record, index)
				{
				
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
				
					this.display_type = record.get('id');
					if(this.display_type === 'pie')
					{
						this.valueLabelsCheckbox.setValue(true);
					}
					this.lineTypeCombo.setDisabled(this.display_type !== 'line' && 
												   this.display_type !== 'spline' && 
												   this.display_type !== 'area' && 
												   this.display_type !== 'areaspline')
				}
			}
		};
		this.lineTypeCombo = new Ext.form.ComboBox({
			fieldLabel: 'Line Type',
			name: 'line_type',
			xtype: 'combo',
			mode: 'local',
			itemSelector: 'div.line-item',
			editable: false,
			store: new Ext.data.ArrayStore({
				id: 0,
				fields: [
					'id',
					'text',
					'dasharray'
				],
				data: CCR.xdmod.ui.AddDataPanel.line_types
			}),
			disabled: this.display_type !== 'line' && 
					  this.display_type !== 'spline' && 
					  this.display_type !== 'area' && 
					  this.display_type !== 'areaspline',
			value: this.line_type,
			valueField: 'id',
			displayField: 'text',
			triggerAction: 'all',
			tpl: new Ext.XTemplate(
				'<tpl for="."><div class="line-item">',
						'<span>',
						'<svg  xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns="http://www.w3.org/2000/svg" version="1.1"  width="185" height="14">',
						'<g fill="none" stroke="black" stroke-width="2">',
						'<path stroke-dasharray="{dasharray}" d="M 0 6 l 180 0" />',
						'</g>','</svg>','{text}','</span>',
				'</div></tpl>'
			),
			listeners:
			{
				scope: this,
				'select': function(combo, record, index)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Advanced -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
					this.line_type = record.get('id');
				}
			}
		});
		
		this.lineWidthCombo = new Ext.form.ComboBox({
			fieldLabel: 'Line Width',
			name: 'line_width',
			xtype: 'combo',
			mode: 'local',
			itemSelector: 'div.line-width-item',
			editable: false,
			store: new Ext.data.ArrayStore({
				id: 0,
				fields: [
					'id',
					'text'
				],
				data: CCR.xdmod.ui.AddDataPanel.line_widths
			}),
			disabled: this.display_type !== 'line' && 
					  this.display_type !== 'spline' && 
					  this.display_type !== 'area' && 
					  this.display_type !== 'areaspline',
			value: this.line_width,
			valueField: 'id',
			displayField: 'text',
			triggerAction: 'all',
			tpl: new Ext.XTemplate(
				'<tpl for="."><div class="line-width-item">',
						'<span>',
						'<svg  xmlns:xlink="http://www.w3.org/1999/xlink"  xmlns="http://www.w3.org/2000/svg" version="1.1"  width="185" height="14">',
						'<g fill="none" stroke="black" stroke-width="{id}">',
						'<path stroke-dasharray="" d="M 0 6 l 180 0" />',
						'</g>','</svg>','{text}','</span>',
				'</div></tpl>'
			),
			listeners:
			{
				scope: this,
				'select': function(combo, record, index)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Advanced -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
					this.line_width = record.get('id');
				}
			}
		});

		this.colorCombo = new Ext.form.ComboBox({
			fieldLabel: 'Color',
			name: 'color',
			xtype: 'combo',
			mode: 'local',
			itemSelector: 'div.color-item',
			editable: false,
			store: new Ext.data.ArrayStore({
				id: 0,
				fields: [
					'id',
					{name: 'color_inverse', convert: function (v, record) {if(record=='auto') return '000000'; return CCR.xdmod.ui.invertColor(record);}}
				],
				data: CCR.xdmod.ui.AddDataPanel.colors
			}),
			disabled: false,
			value: this.color,
			valueField: 'id',
			displayField: 'id',
			triggerAction: 'all',
			tpl: new Ext.XTemplate(
				'<tpl for="."><div class="color-item" style="border: 1px; background-color:#{id}; color:#{color_inverse}; " >',
						'<span >',
						'{id}',
						'</span>',
				'</div></tpl>'
			),
			listeners:
			{
				scope: this,
				'select': function(combo, record, index)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Advanced -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
				   
					this.color = record.get('id');
					if(this.color!=='auto')
					{
						document.getElementById(combo.id).style.backgroundImage = 'none';
						document.getElementById(combo.id).style.backgroundColor = '#'+this.color;
						document.getElementById(combo.id).style.color = '#'+record.get('color_inverse');
					}else
					{
						document.getElementById(combo.id).style.backgroundImage = 'url("../../gui/lib/extjs/resources/images/default/form/text-bg.gif")';
						document.getElementById(combo.id).style.backgroundColor = '#ffffff';
						document.getElementById(combo.id).style.color = '#000000';
					}
				},
				render: function(combo)
				{
					if(this.color!=='auto')
					{
						document.getElementById(combo.id).style.backgroundImage = 'none';
						document.getElementById(combo.id).style.backgroundColor = '#'+this.color;
						document.getElementById(combo.id).style.color = '#'+CCR.xdmod.ui.invertColor(this.color);
					}else
					{
						document.getElementById(combo.id).style.backgroundImage = 'url("../../gui/lib/extjs/resources/images/default/form/text-bg.gif")';
						document.getElementById(combo.id).style.backgroundColor = '#ffffff';
						document.getElementById(combo.id).style.color = '#000000';
					}
				}
			}
		});
		
		this.shadowCheckBox = new Ext.form.Checkbox(
		{
			fieldLabel: 'Shadow',
			name: 'shadow',
			xtype: 'checkbox',
			boxLabel: 'Cast a shadow',
			checked: this.shadow,
			listeners:
			{
				scope: this,
				'check': function(checkbox, check)
				{
				   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Advanced -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
					this.shadow = check;
				}
			}
		});
		
		this.displayTypeConfigButton = new Ext.Button(
		{
			flex: 1.5,
			xtype: 'button',
			text: 'Advanced',
			handler: function() {
			
			   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on the Advanced button');
			
			},
			menu: [
			{
				bodyStyle: 'padding:5px 5px 0;',
				xtype: 'form',
				items: [this.lineTypeCombo,this.lineWidthCombo, this.colorCombo, this.shadowCheckBox]
			}]
		});
		
		var form = new Ext.FormPanel(
		{
			labelWidth: 125, // label settings here cascade unless overridden
	    	bodyStyle:'padding:5px 5px 0',
	        defaults: {width: 325, anchor: 0},
			
			items: [	
				{
					fieldLabel: 'Realm', 
					name: 'realm', 
					xtype: 'combo',
					mode: 'local',
					editable: false,
				    store: new Ext.data.ArrayStore({
						id: 0,
						fields: [
							'id'
						],
						data: realmData // data is local
					}),
					disabled: true,
					value: this.realm,
					valueField: 'id',
					displayField: 'id',
					triggerAction: 'all'
				},
				{
					fieldLabel: 'Metric', 
					name: 'metric', 
					xtype: 'combo',
					mode: 'local',
					editable: false,
				    store: new Ext.data.ArrayStore({
						id: 0,
						fields: [
							'id',
							'text'
						],
						data: metricData // data is local
					}),
					disabled: false,
					value: this.metric,
					valueField: 'id',
					displayField: 'text',
					triggerAction: 'all',
					listeners:
					{
						scope: this,
						'select': function(combo, record, index)
						{
						
						   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
						
							this.metric = record.get('id');	
							this.has_std_err = this.realms[this.realm]['metrics'][this.metric].std_err;
							this.stdErrorCheckBox.setDisabled( ! this.has_std_err || this.log_scale);
							
						}
					}
				},
				{
					fieldLabel: 'Group by',
					name: 'dimension',
					xtype: 'combo',
					mode: 'local',
					editable: false,
				    store: new Ext.data.ArrayStore({
						id: 0,
						fields: [
							'id',
							'text'
						],
						data: dimenionsData // data is local
					}),
					disabled: false,
					value: this.group_by,
					valueField: 'id',
					displayField: 'text',
					triggerAction: 'all',
					listeners:
					{
						scope: this,
						'select': function(combo, record, index)
						{
						   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
							this.group_by = record.get('id');	
						}
					}
				},{
					fieldLabel: 'Sort Type',
					name: 'sort_type',
					xtype: 'combo',
					mode: 'local',
					editable: false,
				    store: new Ext.data.ArrayStore({
						id: 0,
						fields: [
							'id',
							'text'
						],
						data: CCR.xdmod.ui.AddDataPanel.sort_types
					}),
					disabled: false,
					value: this.sort_type,
					valueField: 'id',
					displayField: 'text',
					triggerAction: 'all',
					listeners:
					{
						scope: this,
						'select': function(combo, record, index)
						{
						   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected ' + combo.fieldLabel + ' using drop-down menu', record.get('id'));
							this.sort_type = record.get('id');	
						}
					}
				},
				{					
					fieldLabel: 'Display Type',
					xtype: 'compositefield',
					items: [
						this.displayTypeCombo,
						{
							fieldLabel: 'Combine Type',
							name: 'combine_type',
							xtype: 'combo',
							mode: 'local',
							flex: 2,
							editable: false,
							store: new Ext.data.ArrayStore({
								id: 0,
								fields: [
									'id',
									'text'
								],
								data: CCR.xdmod.ui.AddDataPanel.combine_types
							}),
							disabled: false,
							value: this.combine_type,
							valueField: 'id',
							displayField: 'text',
							triggerAction: 'all',
							listeners:
							{
								scope: this,
								'select': function(combo, record, index)
								{
								   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Selected combine type using drop-down menu', record.get('id'));
									this.combine_type = record.get('id');	
								}
							}
						},
						this.displayTypeConfigButton
					]
				},
				/*,
				{
					fieldLabel: 'X Axis',
					name: 'x_axis',
					xtype: 'checkbox',
					boxLabel: 'Use this data as the x axis values',
					disabled: this.timeseries, 
					checked: this.x_axis,
					listeners:
					{
						scope: this,
						'check': function(checkbox, check)
						{
							this.x_axis = check;
						}
					}
				}*/
				
				this.trendLineCheckBox,
				this.stdErrorCheckBox,
				{
					fieldLabel: 'Log Scale',
					name: 'log_scale',
					xtype: 'checkbox',
					boxLabel: 'Use a log scale y axis for this data',
					checked: this.log_scale,
					listeners:
					{
						scope: this,
						'check': function(checkbox, check)
						{
						   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
							this.log_scale = check;
							this.stdErrorCheckBox.setDisabled(! this.has_std_err || this.log_scale);
						}
					}
				},
				this.valueLabelsCheckbox,
				
				{
					fieldLabel: 'Long Legends',
					name: 'long_legend',
					boxLabel: 'Show filters in legend',
					checked: this.long_legend,
					xtype: 'checkbox',
					listeners:
					{
						scope: this,
						'check': function(checkbox, check)
						{
						   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
							this.long_legend = check;
						}
					}
					
				},
				{
					fieldLabel: 'Local Filters',
					 xtype:'panel',
					layout: 'fit',
					items: filtersGridPanel
				},
				{
					fieldLabel: 'Ignore Query Filters',
					name: 'ignore_global',
					xtype: 'checkbox',
					boxLabel: "Apply only local filters to this data series",
					checked: this.ignore_global,
					listeners:
					{
						scope: this,
						'check': function(checkbox, check)
						{
						   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + checkbox.fieldLabel, Ext.encode({ checked: check }));
							this.ignore_global = check;
						}
					}
				}
					
			]
		});
		
		
		
		Ext.apply(this,
		{
			items: [form ],
			layout: 'fit',
			width: 475,
			height: 540,
			border: false,
			title: '<img class="x-panel-inline-icon add_data" src="gui/lib/extjs/resources/images/default/s.gif" alt=""> Data Series Definition',
			buttons: 
			[
				 {
					scope: this,
					text: this.update_record?'Update':'Add',
					handler: function(b,e)
					{
					   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on ' + b.text);
						b.scope.add_function.call(this);
					}
				},
				{
					scope: this,
					text: 'Cancel',
					handler: function(b,e)
					{
					   XDMoD.TrackEvent('Usage Explorer', 'Data Series Definition -> Clicked on Cancel');
						b.scope.cancel_function.call(this);
					}
				}
			]
		});

		CCR.xdmod.ui.AddDataPanel.superclass.initComponent.apply(this, arguments);
		
		this.hideMenu =  function()
		{
			filtersMenu.hide();
		};
    }
});