Ext.ns('XDMoD');

XDMoD.AppKernelList = Ext.extend(Ext.grid.EditorGridPanel, {
           
		selected_profile: '',
		selected_app_kernels: [],
		store: null,
		resource_panel: null,

		invokeController: function (targetStore, parameters) {
	    	
	    	if (parameters.operation == null){
	    		//alert('an operation must be specified');
	    		//return;
	    	}
	    	
	    	var self = this;
	    	
	    	Ext.Ajax.request({
	    	
            url : targetStore.url,
            method : 'POST',
            params : parameters,
            timeout: 60000,  // 1 Minute
	      	    
            success : function(response) {
	      	    	
               var responseData = Ext.decode(response.responseText);
               self.selected_profile = responseData.profile;
	            	
               targetStore.loadData(responseData);
	      	    
            }
	      	    
	  		}); 
		},
  					
 		initComponent: function() {
 		
    		var fm = Ext.form;
    		var self = this;
    		
			// --------------------------------------------

		    var ccInclude = new Ext.grid.CheckColumn({
		    
		       header: 'Include',
		       dataIndex: 'include',
		       width: 25,
		       
		       onMouseDown : function(e, t) {
				
					if (t.className && t.className.indexOf('x-grid3-cc-' + this.id) != -1) {
							
						var index = this.grid.getView().findRowIndex(t);
						var record = this.grid.store.getAt(index);
			                        
						e.stopEvent();
					
						if (record.data['primary'] == true) {
							Ext.MessageBox.alert('Role Manager', 'You cannot uncheck this role because it is the primary role.');
							return;
						}
					
						record.set(this.dataIndex, !record.data[this.dataIndex])
						
						self.selected_app_kernels.length = 0;
						
						this.grid.store.each(function(r){
						
							if(r.data[this.dataIndex])
								self.selected_app_kernels.push(r.data.text);
						
						}, this);
						
						self.resource_panel.updateResources(self.selected_profile, self.selected_app_kernels.join(';'));
									
					}
					
			   }
				
		    });
			
			var createAppKernelLink  = function (rowData, id) {

				var btn = new  Ext.Toolbar.TextItem ({
					renderTo: id,
					text : rowData.data['data_link'],
					cls: 'ulm_data_link',
					scope : this
				});
				
			}
			
			// --------------------------------------------		
					    
		    function treatAsLink(val, p, r) {

				var cid = Ext.id();
				
				createAppKernelLink.defer(1, this, [r, cid]);
				
				return '<div id="' + cid + '">';
		    	
		    }//treatAsLink
		    
			// --------------------------------------------		    
		    				    
		    var cm = new Ext.grid.ColumnModel({
		    
		    	defaults: {sortable: false, hideable: false},
		    	
		        columns: [
		        
		         	ccInclude,
		         	
		            {
		                id: 'common',
		                header: 'Role',
		                dataIndex: 'text'
		            },
		            
		            {
		            	id: 'links',
		            	header: 'Link', 
		            	dataIndex: 'data_link',
		            	renderer: treatAsLink,
		            	width: 40
		            }

		        ]
		        
		    });

		    // --------------------------------------------

			this.store = new Ext.data.JsonStore({
		    	autoDestroy: true,
		    	url: 'controllers/user_like_me/json_appkernels.php',
		    	//storeId: 'myStore',
		    	root: 'appkernels',
		    	fields: ['text', 'include', 'data_link'],
		    	listeners: {
		    	
		    		'load' : function() {
		    						
		    			if (self.selected_profile == '') return;
		    			
						self.selected_app_kernels.length = 0;
		    							
						this.each(function(r){
						
							if(r.data['include'])
								self.selected_app_kernels.push(r.data.text);
								
						}, self);
						
						self.resource_panel.updateResources(self.selected_profile, self.selected_app_kernels.join(';'));

		    		}
		    		
		    	}
		    	
			});

		    // --------------------------------------------
					
			Ext.apply(this, {
        		
        		hideHeaders: true,
        		store: this.store,
        		cm: cm,
        		width: 202,
        		height: 250,
        		layout: 'fit',
        		title: 'Application Kernels',
        		frame: false,
				enableColumnResize: false,
				plugins: [ccInclude],
		        autoExpandColumn: 'common'
		        
        	});	

			this.on('cellclick', function(g, rowindex, colindex, e){ 
			
				if (colindex == 2) {
				
					g.getSelectionModel().clearSelections();
					var record = g.store.getAt(rowindex);
					
					Ext.getCmp('main_tab_panel').setActiveTab(5);
					
					CCR.xdmod.ui.gotoAppKernel(record.data['text']);
	
				}
				
			});
			
			this.invokeController(this.store, {});
			
        	XDMoD.AppKernelList.superclass.initComponent.call(this);
        	
    	}
            
});