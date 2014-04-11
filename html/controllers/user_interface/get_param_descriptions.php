<?php

	$returnData = array();
	try
	{
		$charts = array();
		
		$user = \xd_security\getLoggedInUser();
		
		$parameter_descriptions = \DataWarehouse\QueryBuilder::getInstance()->pullQueryParameterDescriptionsFromRequest($_REQUEST, $user);

		$key_value_param_descriptions = array();
		foreach($parameter_descriptions as $param_desc)
		{
			$kv = explode('=',$param_desc);
			$key_value_param_descriptions[] = array('key' => trim($kv[0],' '), 'value' => trim($kv[1],' '));
		}

		$returnData = array(
			'totalCount' => count($key_value_param_descriptions),
			'success' => true, 
			'message' => 'success', 
			'data' => $key_value_param_descriptions);
			
	}
	catch(Exception $ex)
	{
		$returnData = array(
			'totalCount' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
	}
	
	print json_encode($returnData);
?>