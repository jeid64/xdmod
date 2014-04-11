<?php

	$returnData = array();
	try
	{
	
		$charts = array();

      $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));
		
		$queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromRequest($_REQUEST, $user);
			
		/*if a center view is selected use public role*/	
		$disabledMenus = $user->getCachedActiveRole()->getDisabledMenus(explode(',',DATA_REALMS));
		
		foreach($queries as $key => $query)
		{	
		
			$disabled = false;
			foreach($disabledMenus as $disabledMenu)
			{
				if($query->groupBy()->getName() === $disabledMenu['group_by'] && $query->getRealmName() === $disabledMenu['realm']) $disabled = true;
			}
			if($disabled /*&& isset($_REQUEST['public_user']) && $_REQUEST['public_user'] == 'true'*/) unset($queries[$key]);
		}
		
		$texts = array();
		foreach($queries as $key => $query)
		{
			$texts[$key] = $query->getMainStatisticField()->getLabel();
		}
		array_multisort($texts, SORT_ASC, $queries );


		$charts = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationsFromQueries($queries, $_REQUEST, $user);

		
		$count = count($charts);
		$returnData = array(
			'totalCount' => $count,
			'success' => true, 
			'message' => 'success', 
			'data' => $charts);
			
	}
	catch(Exception $ex)
	{
		$returnData = array(
			'totalCount' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array($ex->getTraceAsString()),
			'success' => false);
	}
	
	print json_encode($returnData);
?>