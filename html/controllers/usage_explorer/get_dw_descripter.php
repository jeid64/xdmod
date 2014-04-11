<?php
@require_once('common.php');
$returnData = array();

try
{	
	$user = \xd_security\getLoggedInUser();
	$active_role = getActiveRole();
	
	$role_data = explode(':', $active_role);
	$role_data = array_pad($role_data, 2, NULL);
	$activeRole = $user->assumeActiveRole($role_data[0], $role_data[1]);
	
	$shortRole = $role_data[0];
	$us_pos = strpos($shortRole, '_');
	if($us_pos > 0)
	{
		$shortRole = substr($shortRole, 0, $us_pos);
	}

	//try to lookup answer in cache first
	$db = \CCR\DB::factory('database');
	$db->execute('create table if not exists dw_desc_cache (role char(5), response mediumtext, index (role) ) ');
	$cachedResults = $db->query('select response from dw_desc_cache where role=:role',array('role' => $shortRole));
	if(count($cachedResults) > 0)
	{
		$returnData = unserialize($cachedResults[0]['response']);
		
	}else
	{ 
		$enabledRealms = explode(',',DATA_REALMS);
		
		$realms = array();
		$groupByObjects = array();
		
		$query_group_name = 'tg_usage'; 
			
		$query_descripter_realms = $activeRole->getQueryDescripters($query_group_name);
	 
		foreach($query_descripter_realms as $query_descripter_realm => $query_descripter_groups)
		{
			
			if(!in_array($query_descripter_realm,$enabledRealms))continue;
			$realms[$query_descripter_realm] = array('text' => $query_descripter_realm, 'dimensions' => array(), 'metrics' => array());
			foreach($query_descripter_groups as $query_descripter_group)
			{		
				foreach($query_descripter_group as $query_descripter)
				{		
					if($query_descripter->getDisableMenu()) continue;
					$groupByName = $query_descripter->getGroupByName();			
					if(!isset($realms[$query_descripter_realm]['dimensions'][$groupByName]))
					{		
						$group_by_object = $query_descripter->getGroupByInstance();
						$groupByObjects[$query_descripter_realm.'_'.$groupByName] = array(
							'object' => $group_by_object,
							'permittedStats' => $group_by_object->getPermittedStatistics());
						$realms[$query_descripter_realm]['dimensions'][$groupByName] = 
							array(
								'text' => $group_by_object->getLabel(),
								'info' => $group_by_object->getInfo()
							);
					}
					$permittedStatistics = $groupByObjects[$query_descripter_realm.'_'.$groupByName]['permittedStats'];
					
					foreach($permittedStatistics as $realm_group_by_statistic)
					{		
						if(!isset($realms[$query_descripter_realm]['metrics'][$realm_group_by_statistic]))
						{
							$statistic_object = $query_descripter->getStatistic($realm_group_by_statistic);
							if($statistic_object->isVisible())
							{
								$realms[$query_descripter_realm]['metrics'][$realm_group_by_statistic] = 
									array(
										'text' => $statistic_object->getLabel(),
										'info' => $statistic_object->getInfo(),
										'std_err' => in_array('sem_'.$realm_group_by_statistic, $permittedStatistics)
									);
							}
						}
					}
				}			
			}
			$texts = array();
			foreach($realms[$query_descripter_realm]['metrics'] as $key => $row)
			{
				$texts[$key] = $row['text'];
			}
			array_multisort($texts, SORT_ASC, $realms[$query_descripter_realm]['metrics']);
		}
		$texts = array();
		foreach($realms as $key => $row)
		{
			$texts[$key] = $row['text'];
		}
		array_multisort($texts, SORT_ASC, $realms);
	
		$returnData = array('totalCount'=> 1, 'data' => array(array( 'realms' => $realms)));
		
		//cache the results
		$db->execute('insert into dw_desc_cache (role, response) values (:role, :response)',array('role' =>$shortRole, 'response' => serialize($returnData)));
	}

}
catch(Exception $ex)
{
	$returnData = array(
			'totalCount' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
}

xd_controller\returnJSON($returnData);
?>