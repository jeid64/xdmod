var Dashboard = {};

Dashboard.mdmFlag = false;

// ---------------------------------------------

Dashboard.modalLoginMessage = function() {

   if (Dashboard.mdmFlag == true) return;
   Dashboard.mdmFlag = true;
   
   new Ext.Window({
   
      title: 'XDMoD Dashboard',
      width: 300,
      height: 115,
      closable: false,
      modal: true,
      resizable: false,
      
      bodyStyle: {
         padding: 10, 
         color: '#f00',
         backgroundColor: '#fee',
         fontSize: '14px'
      },
      
      html: '<center><br />Your session has expired.<br /><br />' + 
            '<a href="' + window.location + '">Click here</a> to re-login to the dashboard.</center>'
      
   }).show();
   
}//Dashboard.modalLoginMessage

// ---------------------------------------------

/*
   Dashboard.ControllerProxy is to be used when requesting data to be populated into an ExtJS data store.
   This function has the ability to intercept and inspect any status messages that may be returned prior
   to delivering the 'response data' to the 'target store'
*/

Dashboard.ControllerProxy = function(targetStore, parameters) {
	
	if (parameters.operation == null){
		Ext.MessageBox.alert('Controller Proxy', 'An operation must be specified');
		return;
	}
	    		    	
	Ext.Ajax.request({
	
		url : targetStore.url,
		method : 'POST',
		params : parameters,
		timeout: 60000,  // 1 Minute, 
		async: false,
	      	    
      success : function(response) {

         var responseData = Ext.decode(response.responseText);
	    	
         if (responseData.status == 'not_logged_in') {
         
            Dashboard.modalLoginMessage();
            return false;
         
         }
	    
         if (targetStore == null)
            return true; 
	
         if (targetStore != null)
            targetStore.loadData(responseData);
	          	    
		},
		
		failure : function() {
		
         CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'Request error', false);
		
		}
			    
	});//Ext.Ajax.request
	  		
}//Dashboard.ControllerProxy

