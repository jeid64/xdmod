<?php
	
	// Operation: sab_user->assign_assumed_person
	
	$params = array('person_id' => RESTRICTION_ASSIGNMENT);
	
	$isValid = xd_security\secureCheck($params, 'POST');
	
	if (!$isValid) {
      $returnData['success'] =  false;
      $returnData['status'] = 'invalid_id_specified';
      $returnData['message'] = $returnData['status'];
      xd_controller\returnJSON($returnData);
	};
	
	// -----------------------------
	
	$xdw = new XDWarehouse();
			
	if ($xdw->resolveName($_POST['person_id']) == NO_MAPPING) {
      $returnData['success'] =  false;
      $returnData['status'] = 'no_person_mapping';
      $returnData['message'] = $returnData['status'];
		xd_controller\returnJSON($returnData);
	}
			
	$_SESSION['assumed_person_id'] = $_POST['person_id'];
	
	// -----------------------------

   $returnData['success'] =  true;
	$returnData['status'] = 'success';
   $returnData['message'] = $returnData['status'];
				
	xd_controller\returnJSON($returnData);
			
?>