<?php

$returnData = array();

try
{

   $user = \xd_security\getLoggedInUser(); 
   
	if(!isset($_REQUEST['start_date']))  throw new \Exception('Parameter start_date (yyyy-mm-dd) is not set');
	$start_date = $_REQUEST['start_date'];	

	if(!isset($_REQUEST['end_date']))  throw new \Exception('Parameter end_date (yyyy-mm-dd) is not set');
	$end_date = $_REQUEST['end_date'];	
	
	if(!isset($_REQUEST['limit']) || empty($_REQUEST['limit'])) $limit = 30;
	else $limit = $_REQUEST['limit'];	
	
	if(!isset($_REQUEST['start']) || empty($_REQUEST['start']))  $offset = 0;
	else $offset = $_REQUEST['start'];	

	$realm = 'Jobs';
	
	$query_group = \DataWarehouse\QueryBuilder::getQueryGroupFromRequest($_REQUEST);
	if($query_group === 'my_usage' || $query_group === 'my_summary') 
	{
		$role_parameters = array();
		$role_parameters = $user->getActiveRole()->getParameters();
		$_REQUEST = array_merge($_REQUEST, $role_parameters);
	}
	
	$classname =  '\\DataWarehouse\\Query\\'.$realm.'\\'.'Aggregate';
	$classname::registerGroupBys();
	$registeredGroupBys = $classname::getRegisteredGroupBys();
		
	$parameters = array();
		
	foreach($registeredGroupBys as $registeredGroupByName => $registeredGroupByClassname)
	{
		$group_by_instance = new $registeredGroupByClassname();
		$parameters = array_merge($parameters, $group_by_instance->pullQueryParameters($_REQUEST));	
	}

	$query = new \DataWarehouse\Query\Jobs\Raw($start_date, $end_date, 'datawarehouse_amin', 'jobfact2', $parameters);
	$returnData = $query->exportJsonStore($limit,$offset);

}
catch(Exception $ex)
{
	print_r($ex);
	$returnData = array();
}

xd_controller\returnJSON($returnData);
?>