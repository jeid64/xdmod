Ext.namespace('XDMoD');

XDMoD.UserLikeMe = Ext.extend(Ext.Panel,  {

	cls: 'ulm_panel',
	
	layout: 'border',
	//layout:'table',
	//layoutConfig:{ columns:3 },
	
	border:false,
	frame: false,
	title:'User Like Me',
	
	initComponent: function(){

		var detailsPanel = {

			id: 'details-panel',
        	title: 'Explanation',
        	height: 200,
			collapsible: true,
			split: true,
        	region: 'south',
        	bodyStyle: 'padding:15px 0 0 15px;background:#eef;',
			autoScroll: true,
			html: 'Click on a resource to view more information'

    	};			

 		var resource_list = new XDMoD.ResourceListing({
			region: 'center',
			layout: 'fit',
 			border: false
 		}); 
 		
 		var appkernel_list = new XDMoD.AppKernelList({
 			region: 'center',
 			//border: false,
 			resource_panel: resource_list
 		});
	
		var profile_list = new XDMoD.ProfileList({
			region: 'north',
			height: 200,
			split: true,
			appkernel_panel: appkernel_list
		});

		Ext.apply(this, {
						
			items: [
			
				{
					region: 'west',
					width: 300,
					layout: 'border',
					split: true,
					margins: '2 0 2 2',
					items: [
						profile_list, 
						//{xtype: 'spacer', height: 20}, 
						appkernel_list
					]
				},

				
				{
					layout: 'border',
					region: 'center',
				margins: '2 2 2 0',
					items: [resource_list, detailsPanel]
				}
						
				/*
			    {colwidth: 0.3, sectionProfiles },
			    sectionResourceSuggestions,
			    sectionAppKernels,
			   	sectionDescription
			   	*/
			
			]

		});
	
 		XDMoD.UserLikeMe.superclass.initComponent.call(this);
        	
	}
	
});