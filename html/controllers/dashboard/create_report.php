<?php

$returnData = array();

try {
	
	$user = \xd_security\getLoggedInUser();
		
	$userProfile = $user->getProfile();
	$reports = $userProfile->fetchValue('reports');
	
	if($reports== NULL) 
	{
		$reports =  array('data' => array(), 'total' => 0);
	}
	
	$data = isset($_REQUEST['data'])?$_REQUEST['data']:'';
	
	$data = json_decode($data,false);
	
	if(!is_array($data)) $data = array($data);

	$newReports = array();
	
	foreach($data as $datum)
	{
		$datum->ts = time();
		$reports['data'][$datum->id] =  get_object_vars($datum);
		$newReports[$datum->id] = $reports['data'][$datum->id];
	}
	
	$reports['total'] = count($reports['data']);
	
	$userProfile->setValue('reports',$reports);
	$userProfile->save(); 
	
	//$newReports = array_values($newReports);

   $returnData = array(
      'total' => count($newReports), 
      'message' => 'success', 
      'data' => array_values($newReports) ,
      'success' => true
   );
	
}
catch(Exception $ex) {

   \xd_response\presentError($ex->getMessage());
			
}

\xd_controller\returnJSON($returnData);

?>