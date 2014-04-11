/*
   SessionManager abstracts accesses to a PHP-managed session, allowing javascript calls
   to ultimately set session variables.
*/

Ext.namespace('XDMoD.SessionManager');

XDMoD.SessionManager.SetVariable = function(variable, value) {

	// For features which require a synchronous request mechanism
		
	if (window.XMLHttpRequest) {              
    	AJAX=new XMLHttpRequest();              
  	}
  	else {                                  
    	AJAX=new ActiveXObject("Microsoft.XMLHTTP");
  	}
  
  	if (AJAX) {
  	
    	AJAX.open("POST", 'controllers/session_pool.php', false);
    	AJAX.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    	AJAX.send("operation=set_variable&variable=" + variable + "&value=" + value);

      var json = Ext.util.JSON.decode(AJAX.responseText);
    	 
    	if (!json.success) {
         alert('Session Manager:\n' + json.message);
    	}
    	                                  
  	} 
  	else {
  	
      alert('Session Manager:\nThere was a problem sending the request');  
		
  	} 
  	
}//XDMoD.SessionManager.SetVariable