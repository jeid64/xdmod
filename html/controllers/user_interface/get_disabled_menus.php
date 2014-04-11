<?php

require_once( dirname(__FILE__).'/../common_params.php');
	
$returnData = array();

try
{	
   $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));   
   
	$role_data = explode(':', getActiveRole());
	$role_data = array_pad($role_data, 2, NULL);
	$activeRole = $user->assumeActiveRole($role_data[0], $role_data[1]);
	$user->setCachedActiveRole($activeRole);
	
	$returnData = $activeRole->getDisabledMenus(explode(',',DATA_REALMS));

}
catch(Exception $ex)
{
	print_r($ex);
	$returnData = array();
}

xd_controller\returnJSON($returnData);
?>