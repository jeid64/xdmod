<?php

require_once('common.php');

$returnData = array();

try
{
	
	$user = \xd_security\getLoggedInUser();
	
	$userProfile = $user->getProfile();

	$queries = $userProfile->fetchValue('queries');
	
	$searchText = getSearchText();
	//$userProfile->setValue('queries','');
	//$userProfile->save();
	
	if($queries!= NULL) 
	{
		 $queriesArray = array_values(json_decode($queries,true));
		 
		 if(isset($searchText))
		 {
			array_walk($queriesArray, 
			function($query,$key) use(&$queriesArray, $searchText)
			{
				$re = '/^'.$searchText.'/';
				if(preg_match($re,$query['name'],$matches) < 1 ) unset($queriesArray[$key]);				
			});
		 }

		 $timestamps = array();
		 $names = array();
		 foreach($queriesArray as $key => $query)
		 {
			$timestamps[$key] = isset($query['ts'])?$query['ts']:0;
			$names[$key] = $query['name'];
		 }
		 
		 array_multisort($timestamps, SORT_DESC, SORT_NUMERIC, $names, SORT_ASC, SORT_STRING, $queriesArray);

		 $returnData = array(
			'total' => count($queriesArray), 
			'message' => 'success', 
			'data' => $queriesArray ,
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
	$returnData = array(
			'total' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
}
xd_controller\returnJSON($returnData);

?>