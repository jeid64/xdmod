<?php
	@session_start();
	@set_time_limit(0);

	@require_once dirname(__FILE__).'/../../../configuration/linker.php';

	try
	{
		
		$logged_in_user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));
		
      $debug_level = 0;
		if(isset($_REQUEST['debug_level']))
		{
			$debug_level = abs(intval($_REQUEST['debug_level']));
		}
   		
		$query_group = 'tg_summary';
		if(isset($_REQUEST['query_group'])) $query_group = $_REQUEST['query_group'];
		
		$activeRole = $logged_in_user->getMostPrivilegedRole();

		if(!isset($_REQUEST['start_date']))
		{
			throw new Exception("start_date parameter is not set");
		}
		if(!isset($_REQUEST['end_date']))
		{
			throw new Exception("end_date parameter is not set");
		}
		
		$start_date = $_REQUEST['start_date'];
		$end_date = $_REQUEST['end_date'];
		
		$aggregation_unit = 'auto';
		
		if(isset($_REQUEST['aggregation_unit']))
		{
			$aggregation_unit = lcfirst($_REQUEST['aggregation_unit']);
		}
		
		$rp_summary_regex = '/rp_(?P<rp_id>[0-9]+)_summary/';
	
	    $charts_query_group = 'tg_summary';
	  
		$raw_parameters = array();	
		if($query_group === 'my_usage' || $query_group === 'my_summary' || 
		   $query_group === 'tg_usage' || $query_group === 'tg_summary')
		{
			
		}
		else
		if(preg_match($rp_summary_regex,$query_group,$matches) > 0)
		{
			$raw_parameters['provider'] = $matches['rp_id'];
		}
		else
		{	
			//$charts_query_group = 'my_summary';
			
			$role_data = explode(':', substr($query_group,0,strpos($query_group,'_summary')));
			$role_data = array_pad($role_data, 2, NULL);
			
			$activeRole = $logged_in_user->assumeActiveRole($role_data[0], $role_data[1]);
			
			$raw_parameters = $activeRole->getParameters();

		}
		
		$query_descripter = new \User\Elements\QueryDescripter($charts_query_group,'Jobs', 'none');
		
		$query = new \DataWarehouse\Query\Jobs\Aggregate($aggregation_unit, $start_date,$end_date,'none', 'all', $query_descripter->pullQueryParameters($raw_parameters));
		
		$result = $query ->execute();	
		
		if($debug_level < 1) // if debug level is less than 1 (most likely 0), clear out the debug variables.
		{
			$result['query_string'] = '';
			$result['query_time'] = '';
		}
	
		$retCharts = array();

		$request = array(
			'start_date' => $start_date,
			'end_date' => $end_date,
			'thumbnail' => 'y',
			'scale' => .50,
			'width' => 840,
			'height' => 340,
			'aggregation_unit' => $aggregation_unit,
			//'log_scale' => 'y',
			'query_group' => $query_group,
			'single_stat' => 'y'
			);
			
		$request = array_merge($request, $raw_parameters);
		
		$query_descripters = $activeRole->getQueryDescripters($charts_query_group,NULL,NULL,NULL,true);
      	
		$queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromDescripters($query_descripters, $request, $logged_in_user);
		
		$retCharts = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationsFromQueries($queries, $request, $logged_in_user, 'params');
	   
		$result['charts'] = json_encode($retCharts);

		echo  json_encode(array('totalCount' => 1, 'success' => true, 'message' => '', 'data' => array($result) ));
		
	}catch(Exception $ex)
	{
	
		echo  json_encode(
		array('totalCount' => 0, 
			  'message' => $ex->getMessage()."<hr>".$ex->getTraceAsString(), 
			  'data' => array(),
			  'success' => false));
	}
	
?>