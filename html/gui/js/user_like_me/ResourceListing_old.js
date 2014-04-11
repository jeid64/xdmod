Ext.ns('XDMoD');

XDMoD.ResourceListing = Ext.extend(Ext.grid.EditorGridPanel,  {
           
        description_panel: null,
        store: '',
        
        updateResources: function(profile, app_kernels) {
        
			this.store.reload({
				params: {
					'profile': profile, 
					'app_kernels' : app_kernels 
				}
			});
			
        },
        
 		initComponent: function(){
 		
    		var fm = Ext.form;
    		var self = this;
    		
			// --------------------------------------------
					    
		    function resourceEntryRenderer(val, p, r) {

		    	return "<div class='resource_entry'>" + val + "<br><div class='host'>" + r.data['site_host'] + "</div></div>";
		    	
		    }//treatAsLink
		    
			// --------------------------------------------		    
		    				    
		    var cm = new Ext.grid.ColumnModel({
		    
		    	defaults: {sortable: false, hideable: false},
		    	
		        columns: [

		            {
		                id: 'common',
		                header: 'Role',
		                dataIndex: 'site_title',
		                renderer: resourceEntryRenderer
		            }
		        ]

		    });

		    // --------------------------------------------

			this.store = new Ext.data.JsonStore({
			
		    	autoDestroy: true,
		    	url: 'controllers/user_like_me/json_resources.php',
		    	//storeId: 'myStore',
		    	root: 'resources',
		    	fields: ['site_title', 'site_host', 'site_details'],
		    	listeners: {
		    		'load': function() {
		    			description_panel = Ext.getCmp('details-panel').body;
		    			description_panel.update('Click on a resource to view more information').setStyle('background','#eef');
		    		}
		    	}
			
			});

		    // --------------------------------------------
					
			Ext.apply(this, {

        		hideHeaders: true,
        		store: this.store,
        		cm: cm,
        		title: 'Suggested Resources',
        		frame: false,
				enableColumnResize: false,
				autoExpandColumn: 'common'
				
        	});	

			this.on('rowclick', function(grid, rowIndex, e){ 
			
				var row = grid.store.getAt(rowIndex);
				var sel_resource_details = '<table border=0><tr><td><img src="gui/images/server_info.png"></td><td><b>' + row.data.site_title + '</b><br>' + row.data.site_details + '</td></tr></table>';
				
				description_panel = Ext.getCmp('details-panel').body;
				description_panel.update(sel_resource_details).setStyle('background','#efe');
				
			});

			this.store.load();
			
        	XDMoD.ResourceListing.superclass.initComponent.call(this);
        	
    	}
            
});