<?php

	@session_start();
	@require_once dirname(__FILE__).'/../../../configuration/linker.php';
	
	$logged_in_user = \xd_security\getLoggedInUser();
	
   $person_id = isset($_REQUEST['user_ref']) ? $_REQUEST['user_ref'] : $logged_in_user->getPersonID();
	
	// =============================================================
	
	$baseParams = array('person_id' => $person_id);
	
	if (isset($_REQUEST['pi_mode'])) {
      $baseParams['is_pi_of_allocation'] = ($_REQUEST['pi_mode'] === 'true');
	}
	
	$active_config = array_merge($baseParams, array('show_active' => true));
	$expired_config = array_merge($baseParams, array('show_active' => false));
	
	// =============================================================
	
	if(isset($_REQUEST['operation'])) {
	
		switch($_REQUEST['operation']) {
		
			case 'summary':
			
            	$obj_warehouse = new XDWarehouse();
            	
            	$active_allocations = DataWarehouse::getAllocations($active_config);
            	$expired_allocations = DataWarehouse::getAllocations($expired_config);
            
            	$response = array(
               		'user' => $obj_warehouse->resolveName($person_id), 
               		'active_count' => count($active_allocations),
               		'expired_count' => count($expired_allocations),
               		'active_allocations' => $active_allocations,
               		'expired_allocations' => $expired_allocations
            	);

				echo json_encode($response);  

				break;
				
		}//switch
	
	}//if(isset($_REQUEST['operation']))

?>