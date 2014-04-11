<?php

require_once('common.php');

$returnData = array();

try
{
	
	$user = \xd_security\getLoggedInUser();
	
	$userProfile = $user->getProfile();
	$userProfile->setValue('filters',$_REQUEST['filters']);
	$userProfile->save();
	
}
catch(Exception $ex)
{
	print_r($ex);
}

//xd_controller\returnJSON($returnData);

?>