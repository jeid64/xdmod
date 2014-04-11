<?php
	
	// Operation: user_admin->enum_user_types
	
	$xda = new XDAdmin();
			
	$user_types = $xda->enumerateUserTypes();
			
   $userTypeEntries = array();
   
	foreach($user_types as $type) {

		$userTypeEntries[] = array(
		                    'id' => $type['id'], 
		                    'type' => $type['type']
		                 );
		           
	}//foreach

	// -----------------------------

	$returnData['status'] = 'success';
	$returnData['user_types'] = $userTypeEntries;
			
	\xd_controller\returnJSON($returnData);
			
?>