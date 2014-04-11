<?php
require_once('common.php');

$returnData = array();

try
{
	
   $user = \xd_security\getLoggedInUser();

	$userProfile = $user->getProfile();
	
	$queries = $userProfile->fetchValue('queries');
	$queriesArray = array();
	if($queries != null)
	{
		$queriesArray = json_decode($queries,true);
	}

	$data = isset($_REQUEST['data'])?json_decode($_REQUEST['data'],true):'';
	
	$name = trim($data['name']);
	 
	$config = $data['config'];
	if(trim($name) === '') throw new Exception('Query name cannot be empty');
	
	$queriesArray[$name] = array('ts' => time(), 'name' => $name, 'config' => $config); 
	
	$userProfile->setValue('queries',json_encode($queriesArray));
	$userProfile->save();
	
	$returnData = array(
			'total' => count($queriesArray), 
			'message' => 'success',
			'data' => array($queriesArray[$name]),
			'success' => true);
	
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