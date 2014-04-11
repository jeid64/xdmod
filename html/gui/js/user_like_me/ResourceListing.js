Ext.ns('XDMoD');

XDMoD.ResourceListing = Ext.extend(Ext.Panel,  {
           
        description_panel: null,
        store: '',
        
        dStore: null,
        
        updateResources: function(profile, app_kernels) {
        	  	
        	var a = Math.floor(Math.random()*301);
        	var b = Math.ceil(Math.random()*301) + 600;
 
        	var metric_index = (b % 2 == 0) ? '1' :  '2';
        	
        	this.store.clearFilter();
        	
			//if (a % 2 == 0) {
				this.store.filter([{
	            	fn: function(record) {
	                	return record.get('metric_' + metric_index) >= Math.min(a,b) && record.get('metric_' + metric_index) <= Math.max(a,b);
	            	}
	        	}]);
	        //}
	        
			this.store.sort([{field: 'metric_' + metric_index, direction: 'DESC'}]);
        	
	        /*
				this.store.reload({
					params: {
						'profile': profile, 
						'app_kernels' : app_kernels 
					}
				});
			*/
			
        },
        
 		initComponent: function(){
 		
 		var self = this; 
 		
     	var store = new Ext.data.ArrayStore({
        	proxy   : new Ext.data.MemoryProxy(),
        	fields  : ['id', 'resource_name', 'resource_host', 'metric_1', 'metric_2', 'description'],
        	sortInfo: {
            	field    : 'resource_name',
            	direction: 'ASC'
        	}
    	});

		self.store = store;

	    function doSort(num, filter) {
	    
			store.clearFilter();
			
			if (filter) {
				store.filter([{
	            	fn: function(record) {
	                	return record.get('metric_1') >= 200 && record.get('metric_1') <= 400;
	            	}
	        	}]);
	        }
	        
			store.sort([{field: 'metric_' + num, direction: 'DESC'}]);
			
	    };
	        	
        store.loadData([
        
    		[1,  'Resource 1',  'resource_1.xdmod.org',  359, 61, 'Resource description here'],
    		[2,  'Resource 2',  'resource_2.xdmod.org',  381, 82, 'Resource description here'],
     		[3,  'Resource 3',  'resource_3.xdmod.org',  12,  110, 'Resource description here'],
    		[4,  'Resource 4',  'resource_4.xdmod.org',  9,   482, 'Resource description here'],
    		[5,  'Resource 5',  'resource_5.xdmod.org',  462, 832, 'Resource description here'],
    		[6,  'Resource 6',  'resource_6.xdmod.org',  84,  128, 'Resource description here'],
    		[7,  'Resource 7',  'resource_7.xdmod.org',  71,  42, 'Resource description here'],
    		[8,  'Resource 8',  'resource_8.xdmod.org',  700, 83, 'Resource description here'],
    		[9,  'Resource 9',  'resource_9.xdmod.org',  10,  22, 'Resource description here'],
    		[10, 'Resource 10', 'resource_10.xdmod.org', 91,  616, 'Resource description here'],
    		[11, 'Resource 11', 'resource_11.xdmod.org', 62,  533, 'Resource description here'],
    		[12, 'Resource 12', 'resource_12.xdmod.org', 384, 828, 'Resource description here'],
    		[13, 'Resource 13', 'resource_13.xdmod.org', 88,  742, 'Resource description here'],
    		[14, 'Resource 14', 'resource_14.xdmod.org', 582, 83, 'Resource description here'],
    		[15, 'Resource 15', 'resource_15.xdmod.org', 910, 281, 'Resource description here']
    		    		
    	]);

    	
    	store.on('clear', function(s){
    	
			store.loadData([
				[1, 'fff a', 'resource_a.xdmod.org', 359, 61],
			    [2, 'fff b', 'resource_b.xdmod.org', 381, 82],
			    [3, 'fff c', 'resource_c.xdmod.org', 12,  110],
			    [4, 'fff d', 'resource_d.xdmod.org', 9,   482],
			 ]);  
			 
			//doSort('2', true);  

    	});

		var ulm_dataview = new Ext.DataView({
    
    		id: 'ulm_dv',
        	store: store,
        
	        tpl  : new Ext.XTemplate(
	            '<ul>',
	                '<tpl for=".">',
	                    '<li class="resource">',
	                        '<img width="64" height="64" src="gui/images/server.png" /><br>',
	                        '<strong class="resource_name">{resource_name}</strong>',
	                        '<span>{resource_host}</span>',
	                        '<script language="JavaScript">',
	                        'data={resource_name:"{resource_name}", description:"{description}"};',
	                        '</script>',
	                    '</li>',
	                '</tpl>',
	            '</ul>'
	        ),
        
	        plugins : [
	            new Ext.ux.DataViewTransition({
	                duration  : 550,
	                idProperty: 'id'
	            })
	        ], 
        
	        id: 'ulm_resources',
	        
	        itemSelector: 'li.resource',
	        overClass   : 'resource-hover',
	        singleSelect: true,
	        multiSelect : true,
	        autoScroll  : true,
	        
	        listeners: {
	        
	        	'click' : function(dv, index, node, e) {
	
	        		var re = new RegExp('data=(.+);', "g");
					var myArray = re.exec(node.innerHTML);

					nodeData = eval('(' + myArray[1] + ')');
						
					var sel_resource_details = '<table border=0><tr><td><img src="gui/images/server_info.png"></td><td><b>' + nodeData.resource_name + '</b><br><i>' + nodeData.description + '</i></td></tr></table>';
				
					description_panel = Ext.getCmp('details-panel').body;
					description_panel.update(sel_resource_details).setStyle('background','#efe');
					
	        		
	        	}
	        
	        }
	        
	    });
	    
	    
	    var tbar = new Ext.Toolbar({
	        items  : ['Sort on these fields:', '']
	    });
	
	    var btnSortA = new Ext.Button({
			text: 'Metric 1',
			listeners: {
				click: function(button, e) {
					doSort('1');                
				}
			}
	    });
	    
	    var btnSortB = new Ext.Button({
			text: 'Metric 2',
			listeners: {
				click: function(button, e) {
					doSort('2');                
				}
			}
	    });
	
	    var btnSortC = new Ext.Button({
			text: 'Metric 2 (Filter)',
			listeners: {
				click: function(button, e) {
					doSort('2', true);                
				}
			}
	    });

	    var btnSortD = new Ext.Button({
			text: 'New Data',
			listeners: {
				click: function(button, e) {
					store.removeAll();    
				}
			}
	    });
	    	        
	    tbar.add(btnSortA);
	    tbar.add(btnSortB);
	    tbar.add(btnSortC);
	    tbar.add(btnSortD);    
    
		    // --------------------------------------------
					
			Ext.apply(this, {

        		title: 'Resources',
        		layout: 'fit',
        		items : ulm_dataview,
        		height: 615,
        		width : 800
				
        	});	
    
        	XDMoD.ResourceListing.superclass.initComponent.call(this);
        	
    	}
            
});