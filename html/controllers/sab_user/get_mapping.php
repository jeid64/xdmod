<?php
	
	// Operation: sab_user->get_mapping
	
	$params = array(
		'use_default' => RESTRICTION_YES_NO
	);
	
	$isValid = xd_security\secureCheck($params, 'POST');
	
	if (!$isValid) {
      $returnData['success'] =  false;
      $returnData['status'] = 'invalid_params_specified';
      $returnData['message'] = $returnData['status'];
      xd_controller\returnJSON($returnData);
	};
	
	// -----------------------------
	
	$logged_in_user = \xd_security\getLoggedInUser();

	$mapped_person_id = $logged_in_user->getPersonID($_POST['use_default'] == 'y');
	
	$xdw = new XDWarehouse();
	$mapped_person_name = $xdw->resolveName($mapped_person_id);
		
	// -----------------------------

   $returnData['success'] =  true;
	$returnData['status'] = 'success';
   $returnData['message'] = $returnData['status'];
	$returnData['mapped_person_id'] = $mapped_person_id;
	$returnData['mapped_person_name'] = $mapped_person_name;
				
	//unset($_SESSION['assumed_person_id']);
	
	xd_controller\returnJSON($returnData);
			
?>