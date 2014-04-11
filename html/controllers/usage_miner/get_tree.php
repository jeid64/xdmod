<?php
@require_once('common.php');
$returnData = array();

try
{

	$user = \xd_security\getLoggedInUser();
	
	$query_group_name = 'tg_usage';
	$realm = getRealm();
	
	$selectedDimensionIds = getSelectedDimensionIds();
	$selectedMetricIds = getSelectedMetricIds();
   
    if(isset($_REQUEST['node']) && $_REQUEST['node'] === 'dimensions') 
	{
		$query_descripter_realms = $user->getActiveRole()->getQueryDescripters($query_group_name);

		foreach($query_descripter_realms as $query_descripter_realm => $query_descripter_groups)
		{
			if($query_descripter_realm != $realm) continue;
			foreach($query_descripter_groups as $query_descripter_group)
			{		
				foreach($query_descripter_group as $query_descripter)
				{
					$groupByInstance = $query_descripter->getGroupByInstance();
					$groupByName = $groupByInstance->getName();
					
					if($groupByName == 'none') continue;
					$groupByLabel = $groupByInstance->getLabel();
					$permittedStats = $query_descripter->getPermittedStatistics();
					if(isset($returnData[$groupByName]))
					{
						$returnData[$groupByName]['realms'] .= ','.$query_descripter_realm;
						$returnData[$groupByName]['stats'] .= ','.implode(',',$permittedStats);
					}
					else
					{
						$returnData[$groupByName] = 
							array(  
								'text' => $groupByLabel,
								'label' => $groupByLabel,
								'id' => $groupByName, 
								'realms' => $query_descripter_realm,
								'stats' => implode(',',$permittedStats),
								'info' => $groupByInstance->getInfo(),
								'type' => 'dimension',
								'iconCls' => 'menu', 
								'leaf' => true,
								'checked' => in_array($groupByName,$selectedDimensionIds)
							);
					}
				}
			}
		}
	}
	else if(isset($_REQUEST['node']) && $_REQUEST['node'] === 'metrics') 
	{
		$summary_query_descripter = $user->getActiveRole()->getQueryDescripters($query_group_name, 'Jobs','none');
		
		$permitted_summary_stats = $summary_query_descripter->getPermittedStatistics();
		
		$query_descripter_realms = $user->getActiveRole()->getQueryDescripters($query_group_name);

		foreach($query_descripter_realms as $query_descripter_realm => $query_descripter_groups)
		{
			if($query_descripter_realm != $realm) continue;
			foreach($query_descripter_groups as $query_descripter_group)
			{		
				foreach($query_descripter_group as $query_descripter)
				{
					if($query_descripter->getGroupByName() == 'none') continue;
									
					foreach($query_descripter->getPermittedStatistics() as $realm_group_by_statistic)
					{		
						$statistic_object = $query_descripter->getStatistic($realm_group_by_statistic);
						//if($statistic_object->isVisible())
						{				
							$disabled = !in_array($realm_group_by_statistic,$permitted_summary_stats);			
							$returnData[$statistic_object->getAlias()->getName()] = 
								array(
									'text' => $statistic_object->getLabel(),
									'label' => $statistic_object->getLabel(),
									'id' => $statistic_object->getAlias()->getName(), 
									'stat' => $statistic_object->getAlias()->getName(), 
									'realm' => $query_descripter_realm,
								//	'qtip' => $statistic_object->getLabel(),
									'info' => $statistic_object->getInfo(),
									'type' => 'metric',
									'iconCls' => 'chart', 
									'defaultDisabled' => $disabled,
									'disabled' => $disabled,
									'leaf' => true,
									'checked' => in_array($statistic_object->getAlias()->getName(),$selectedMetricIds)
								);
							
						}
					}
				}
			}
		}
	}
	
	$texts = array();
	foreach($returnData as $key => $row)
	{
		$texts[$key] = $row['text'];
	}
	array_multisort($texts, SORT_ASC,  $returnData);
	$returnData = array('totalCount'=> 1, 'data' => array(array('nodes' => json_encode(array_values($returnData)))));

}
catch(Exception $ex)
{
	print_r($ex);
	$returnData = array();
}

xd_controller\returnJSON($returnData);
?>