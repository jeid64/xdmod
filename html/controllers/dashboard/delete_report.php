<?php

$returnData = array();

try
{
	
	$user = \xd_security\getLoggedInUser();
	
	$userProfile = $user->getProfile();
	
	$reports = $userProfile->fetchValue('reports');
	
	if($reports == NULL) 
	{
		$reports =  array('data' => array(), 'total' => 0);
	}
	
	$deletedReports = array();
	$data = isset($_REQUEST['data'])?$_REQUEST['data']:'';
	$data = json_decode($data);
	
	foreach($data as $datum)
	{
		$deletedReports[] = $reports['data'][$datum];
		unset($reports['data'][$datum]);
	}
	
	$reports['total'] = count($reports['data']);
	$userProfile->setValue('reports',$reports);
	$userProfile->save();
	
   $returnData = array(
      'total' => count($deletedReports), 
      'message' => 'success', 
      'data' => array_values($deletedReports) ,
      'success' => true
   );
	
}
catch(Exception $ex)
{

   \xd_response\presentError($ex->getMessage());
   
}

\xd_controller\returnJSON($returnData);

?>