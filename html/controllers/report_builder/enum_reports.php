<?php

   try {
      
   	$user = \xd_security\getLoggedInUser();
   
   	$rm = new XDReportManager($user);
   
   	$returnData['status'] = 'success';
   	
   	$reports_in_other_roles = $rm->enumReportsUnderOtherRoles();
   	
   	$returnData['has_reports_in_other_roles'] = (count($reports_in_other_roles) > 0);
   	$returnData['reports_in_other_roles'] = $reports_in_other_roles;
   	
   	$returnData['queue'] = $rm->fetchReportTable();
   
   	\xd_controller\returnJSON($returnData);

   }
	catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
	}
	
?>