<?php

   try {
   
   	$user = \xd_security\getLoggedInUser();
   	
   	$rm = new XDReportManager($user);
   	
   	$response = array();
   	
   	$response['success'] = true;
   	$response['report_name']  = $rm->generateUniqueName();
   		
   	\xd_controller\returnJSON($response);
	
	}
	catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
	}

?>