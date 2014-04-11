<?php
	@session_start();

	@require_once dirname(__FILE__).'/../../../configuration/linker.php';
	
	try
	{
	
		$logged_in_user = \xd_security\getLoggedInUser();
		
		$person_id = $logged_in_user->getPersonID();
		
		$description = 'description';
		
		if(isset($_GET['show_date']) && $_GET['show_date'] == 'y')
		{
				$description = "concat(description, ' (', start_date, ' to ', end_date, ')') as description";
		}
	
		$daterangesQuery = "
			SELECT id, $description FROM  daterange l where id <> -1
		";
		
		$dateranges = DataWarehouse::connect()->query($daterangesQuery);
		
	
		
		echo json_encode(array('totalCount' => count($dateranges), 'success' => true, 
							  'message' => 'success', 'data' => $dateranges));

	}
	catch(Exception $ex)
	{
		echo  json_encode(array(
         'totalCount' => 0, 
          'success' => false, 
          'message' => $ex->getMessage(), 
          'data' => array()));
	}
	
?>