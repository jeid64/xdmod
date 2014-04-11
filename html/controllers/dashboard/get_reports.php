<?php

$returnData = array();

try
{
	
	$user = \xd_security\getLoggedInUser();
	
	$userProfile = $user->getProfile();

	$reports = $userProfile->fetchValue('reports');

	if($reports!= NULL) 
	{
		 $reportsArray = array_values($reports['data']);
		
		 $timestamps = array();
		 $names = array();
		 foreach($reportsArray as $key => $report)
		 {
			 if(!isset($report['name']))
			 {
				 unset($reportsArray[$key]);
				 continue;
			 }
			$timestamps[$key] = isset($report['ts'])?$report['ts']:0;
			$names[$key] = $report['name'];
		 }
		 
		 array_multisort($timestamps, SORT_DESC, SORT_NUMERIC, $names, SORT_ASC, SORT_STRING, $reportsArray);
		 
		 
		 $returnData = array(
			'total' => $reports['total'], 
			'message' => 'success', 
			'data' => $reportsArray ,
			'success' => true);
	}
	else 
	{
		$returnData = array(
			'total' => 0, 
			'message' => 'success',
			'data' => array(),
			'success' => true);
	}
	
}
catch(Exception $ex)
{

   \xd_response\presentError($ex->getMessage());

}

\xd_controller\returnJSON($returnData);

?>