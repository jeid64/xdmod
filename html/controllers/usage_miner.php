<?php


   
   // Based on the following article from Microsoft:
   // http://support.microsoft.com/kb/323308
   // Internet Explorer file downloads over SSL do not work with the cache control headers.  This issue 
   // would prevent image (PNG) export options from being recognized as PNGs (the Content-Type would resolve to 'HTML Document')
   // and would prohibit the downloading of such images to the user's local file system.
   
   // We can control the cache control HTTP headers sent to the client (specifically Internet Explorer browsers) such 
   // that 'no-store' and 'no-cache' are absent.  In doing so, we permit the client to cache the contents.
   
   if ( preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])
   ) {
      session_cache_limiter("private");
   }

	@session_start();
   session_write_close();

	require_once dirname(__FILE__).'/../../configuration/linker.php';
	
	$returnData = array();
	
	// --------------------
	
	$controller = new XDController(array(STATUS_LOGGED_IN));
	
	// This controller accepts internal tokens so the STATUS_LOGGED_IN check qualifies
	// when this controller is accessed internally with a valid (recognizable) token
	// appended to the query
	
	$controller->acceptInternalTokens();
	
	$controller->registerOperation('get_tree');
	$controller->registerOperation('get_data');	
	$controller->registerOperation('get_dimension');			
	$controller->invoke('REQUEST');
		
?>