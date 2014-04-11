<?php

   try {
      
   	$user = \xd_security\getLoggedInUser();
   		
   	$rm = new XDReportManager($user);
      $cp = new XDChartPool($user);
   
   	$returnData['status'] = 'success';
   	
   	$charts_in_other_roles = $cp->enumChartsUnderOtherRoles();
   	
   	$returnData['has_charts_in_other_roles'] = (count($charts_in_other_roles) > 0);
   	$returnData['charts_in_other_roles'] = $charts_in_other_roles;
   		
   	$returnData['queue'] = $rm->fetchChartPool();
   
   	\xd_controller\returnJSON($returnData);
	
	}
   catch (Exception $e) {

	    \xd_response\presentError($e->getMessage());
	    
	}

?>