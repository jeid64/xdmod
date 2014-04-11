Ext.ns('XDMoD');

XDMoD.ProfileList = Ext.extend(Ext.grid.EditorGridPanel,  {
        
        appkernel_panel: null,
            
 		initComponent: function(){
 		
    		var fm = Ext.form;
    		var self = this;
			
			// --------------------------------------------
				    
			var sm = new Ext.grid.RowSelectionModel({
				singleselect: true
			});
			
		    var cm = new Ext.grid.ColumnModel({
		    	defaults: {sortable: false, hideable: false},
		        columns: [
		            {
		                id: 'common',
		                header: 'Role',
		                dataIndex: 'profile_label'
		            }
		        ],
		        autoExpandColumn: 'common'
		    });

		    // --------------------------------------------

			var store = new Ext.data.JsonStore({
		    	autoDestroy: true,
		    	url: 'controllers/user_like_me/json_profiles.php',
		    	root: 'profiles',
		    	fields: ['profile_label', 'profile_id'],
				listeners: 
				{
					'load': function (daterangesStore)
					{
						//alert(self.getSelectionModel());
						
						//self.getSelectionModel().selectFirstRow();   //alert('nice'); //me.setValue(daterangesStore.getAt(me.initial_index).get('id'));	
					}
				}
			});


			
		    // --------------------------------------------
					
			Ext.apply(this, {
        		border: false,
        		hideHeaders: true,
        		store: store,
        		cm: cm,
        		height: 200,
        		title: 'Profiles',
        		frame: false,
				enableColumnResize: false,
				selModel: sm,
				autoExpandColumn: 'common'
        		
        	});	

			this.on('rowclick', function(grid, rowIndex, e){ 
			
				var row = grid.store.getAt(rowIndex);
				var sel_profile = row.data.profile_id;
								
				this.appkernel_panel.invokeController(this.appkernel_panel.store, {'profile' : sel_profile });
		
			});
			
			this.on('render', function() {
				
	   			var isel = function() {
	   			
					self.getSelectionModel().selectRow(2);
					self.fireEvent('rowclick', self, 2);
				
				};
			
				isel.defer(200);

			});

			store.load();
			
        	XDMoD.ProfileList.superclass.initComponent.call(this);
        	
    	}
    	 
});