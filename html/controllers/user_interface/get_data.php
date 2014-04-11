<?php


	$format = \DataWarehouse\ExportBuilder::getFormat($_REQUEST);

	$returnData = array();
	try
	{
		$data = array();
		
		$user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));
		
		$queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromRequest($_REQUEST, $user);
		
		$disabledMenus = $user->getActiveRole()->getDisabledMenus(explode(',',DATA_REALMS));
		
		foreach($queries as $key => $query)
		{	
			$disabled = false;
			foreach($disabledMenus as $disabledMenu)
			{
				if($query->groupBy()->getName() === $disabledMenu['group_by'] && $query->getRealmName() === $disabledMenu['realm']) $disabled = true;
			}
			if($disabled /*&& isset($_REQUEST['public_user']) && $_REQUEST['public_user'] == 'true'*/) unset($queries[$key]);
		}
		$returnData = \DataWarehouse\ExportBuilder::buildExport($queries, $_REQUEST, $user, $format);
	
	}
	catch(Exception $ex)
	{
		$returnData = array
		(
			"metaData" => array("totalProperty" => "total", 
								"root" => "records",
								"id" => "id",
								"fields" => array()
								),
			"success" => false,
			"message" => $ex->getMessage(),
			"total" => 0,
			"records" => array(),
			"columns" => array()
		); 
	}
	if($format == 'jsonstore' || $format == 'json')
	{	
		\DataWarehouse\ExportBuilder::writeHeader($format);
		print json_encode($returnData);
	}
?>