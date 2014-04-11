<?php
	
	try {
	
   	$activeUser = \xd_security\getLoggedInUser();

      $members = $activeUser->getActiveRole()->enumCenterStaffMembers();
    	
    	$returnData = array();
    	
    	$returnData['success'] = true;
    	$returnData['count'] = count($members);
   	$returnData['members'] = $members;
   	
   	echo json_encode($returnData);
	
	}
   catch (\Exception $e){
   
      \xd_response\presentError($e->getMessage());
      
   }	
	
?>