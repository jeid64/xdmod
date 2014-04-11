<?php

   try {
   
      $activeUser = \xd_security\getLoggedInUser();
   	
   	$role_id = \xd_security\assertParameterSet('role_id');
   	
   	$role_data = explode(':', $role_id);
   	
   	if (count($role_data) == 1) {
   	  $role_data[] = NULL;
   	}
   	
      $activeUser->setActiveRole($role_data[0], $role_data[1]);
      $activeUser->saveUser(true);
   	
   	$returnData = array();
   	
   	$returnData['success'] = true;
   	$returnData['is_center_role'] = ($role_data[0] == ROLE_ID_CENTER_DIRECTOR || $role_data[0] == ROLE_ID_CENTER_STAFF);
   	
   	echo json_encode($returnData);
	
	}
	catch (\Exception $e) {

	    \xd_response\presentError($e->getMessage());
	    
	}
	
?>