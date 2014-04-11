<?php

	@session_start();

	@require_once dirname(__FILE__).'/../../../configuration/linker.php';
	
	try
	{
		
		$logged_in_user = \xd_security\getLoggedInUser();
	
		function checkPostParameter($parameterName, &$returnValue, $defaultValue = '')
		{
			if(isset($_POST[$parameterName])) 
			{
				$returnValue = $_POST[$parameterName];
			}
			else
			if(isset($defaultValue))
			{
				$returnValue = $defaultValue;
				
			}else 
			throw new Exception($parameterName.' parameter must be specified');	
		}
		$person_id = -1;
		$is_pi = 'n';
		$start_date = "";
		$end_date = "";
		$aggregation_unit = 'auto';
		
		checkPostParameter('person_id',$person_id);
		checkPostParameter('is_pi',$is_pi);
		checkPostParameter('start_date',$start_date);
		checkPostParameter('end_date',$end_date);
		checkPostParameter('aggregation_unit',$aggregation_unit);
		
		if(!is_numeric($person_id))
		{
			throw new Exception('The person specified is invalid.');		
		}
		$request = array(
			'start_date' => $start_date,
			'end_date' => $end_date,
			'thumbnail' => 'n',
			'scale' => .54,
			'query_group' => 'search',
			'single_stat' => 'y'
			);
		
		$parameters = array();
		if($person_id>-1)
		{
			if($is_pi == 'y')
			{
				$parameters[] = new \DataWarehouse\Query\Model\Parameter('principalinvestigator_person_id', '=', $person_id);
				$request['pi'] = $person_id;
			}
			else
			{
				$parameters[] = new \DataWarehouse\Query\Model\Parameter('person_id', '=', $person_id);
				$request['person'] = $person_id;
			}
		}

		$query = new \DataWarehouse\Query\Jobs\Aggregate($aggregation_unit, $start_date,$end_date,'none', 'all', $parameters );
			
		$result = $query ->execute();
		
		if(count($result) <= 0)
		{
			throw new Exception('No records found for '.($is_pi == 'y'?'principalinvestigator_person_id':'person_id').'='.$person_id);		
		}

		$retCharts = array();
		
		
			
		$query_descripters = $logged_in_user->getActiveRole()->getQueryDescripters('search',NULL,NULL,NULL,true);

		$queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromDescripters($query_descripters, $request, $logged_in_user);
		$retCharts = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationsFromQueries($queries, $request, $logged_in_user);
		
		$result['charts'] = json_encode($retCharts);

		$details = array();
		
		$basic_information = array();
		
		if($person_id > -1)
		{
			$person_search_results = DataWarehouse::getPersonDetails($person_id);
			foreach($person_search_results as $key => $value)
			{
				$keyName = ucwords(str_replace('_', ' ',$key));

				//$details[] = array('key' => $keyName, 'value' => $value);
				$details[] = array('id' => $keyName, 'text' => '<span class="details_key">'.$keyName.':</span> <span class="details_value">'.$value.'</span>', 'leaf' => true, 'iconCls' => 'info');
			}
			
		}else
		{
			
			$details[] = array('id' => 'Name', 'text' => '<span class="details_key">Name: </span> <span class="details_value">XSEDE</span>', 'leaf' => true, 'iconCls' => 'info');
		}
		
		//$details[] = array('id' => 'Basic Information', 'text' => 'Basic Information', 'leaf' => false, 'expanded' => true, 'children' => $basic_information);
		
		$allocations_information = array();
		$allocations = DataWarehouse::getAllocationsByChargeNumber($person_id,  $start_date, $end_date, $is_pi == 'y');
		
		$allocationFields = 	array(
				//'allocation_id' => 'allocation_id',
				'charge_number' => 'charge_number' ,
			//	'project_titles' => 'projects',
				//'status' => 'status',
				//'allocation_count' => 'allocations',
				'base_formatted' => 'base_SUs',
			//	'initial_formatted' => 'initial_SUs',
				'used_formatted' => 'used_SUs',
				'remaining_formatted' => 'remaining_SUs',
				
				
	//			'used_by_user_formatted' => 'used_by_user', 
			//	'start' => 'start_date', 
			//	'end' =>  'end_date'
				);
		$index = 1;
		foreach($allocations as $allocation)
		{
			$allocation_information = array();
			//$details[] = array('key' => 'Allocation '.$index, 'value' => '');
			
			$usage = round(($allocation['remaining']<0||$allocation['base']<=0)?0:$allocation['remaining']/$allocation['base'] *100.0,1);
			
			foreach($allocationFields as $allocationFieldKey => $allocationFieldValue)
			{
				$keyName = ucwords(str_replace('_', ' ',$allocationFieldValue));;
	
				$allocation_information[] = array('text' => '<span class="details_key">'.$keyName.': </span>'
														   .' <span class="details_value">'.$allocation[$allocationFieldKey].'</span>',
												  'leaf' => true, 
												  'iconCls' => 'info');
			}
			
						$allocation_information[] = array('text' => '<span class="details_key">Remaining: </span>'
														   .' <span class="details_value">'.$usage.'%</span>',
												  'leaf' => true, 
												  'iconCls' => 'info');
			$allocation_information[] = array('text' => '<span class="details_key">'.($allocation['status'] == 'expired'?'Duration':'Valid').': </span>'
														   .' <span class="details_value">'.$allocation['start'].' to '.$allocation['end'].'</span>',
												  'leaf' => true, 
												  'iconCls' => 'info');	
				$resource_information = array();
			
			$resource_names = explode(',', $allocation['resource_names']);
			foreach($resource_names as $resource_name)
			{
				$resource_information[] = array('text' => ' <span class="details_key">'.$resource_name.'</span>',
												  'leaf' => true, 
												  'iconCls' => 'info');
			}
	
			$allocation_information[] = array('text' => '<span class="details_key">Resource(s): </span>',
												  'leaf' => false, 
												  'expanded' => false,
												  'iconCls' => 'info',
												  'children' => $resource_information);	
			
			$projects_information = array();
			
			$project_titles = explode(';', $allocation['project_titles']);
			foreach($project_titles as $project_title)
			{
				$project_title_parts = explode('|', $project_title);
				
				$projects_information[] = array('text' => ' <span class="details_value">'.$project_title_parts[0].'</span>',
												  'leaf' => false, 
												  'iconCls' => 'info',
												  'children' => 
												  	array 
														(
														array('text' => '<span class="details_key">Proposal #: </span><span class="details_value">'.$project_title_parts[1].'</span>',
														  'leaf' => true, 
														  'iconCls' => 'info'
															
														),
														array('text' => '<span class="details_key">Grant #: </span><span class="details_value">'.$project_title_parts[2].'</span>',
														  'leaf' => true, 
														  'iconCls' => 'info'
															
														)
														)
											);
			}
			
			$allocation_information[] = array('text' => '<span class="details_key">Project(s): </span>',
												  'leaf' => false, 
												  'expanded' => false,
												  'iconCls' => 'info',
												  'children' => $projects_information);
							$pi_information = array();
			
			$pi_names = explode(';', $allocation['pi_names']);
			foreach($pi_names as $pi_name)
			{
				$pi_information[] = array('text' => ' <span class="details_value">'.$pi_name.'</span>',
												  'leaf' => true, 
												  'iconCls' => 'info');
			}
			
			$allocation_information[] = array('text' => '<span class="details_key">Principal Investigator(s): </span>',
												  'leaf' => false, 
												  'expanded' => false,
												  'iconCls' => 'info',
												  'children' => $pi_information);		
			
				$fos_information = array();
			
			$fos_names = explode(';', $allocation['fos_names']);
			foreach($fos_names as $fos_name)
			{	
				$fos_name_parts = explode(',',$fos_name);
				$fos_information[] = array('text' => '<span class="details_key">NSF Directorate:</span><span class="details_value">'.$fos_name_parts[2].'</span>',
												  'leaf' => false, 
												  'iconCls' => 'info',
												  'expanded' => false,
												  'children' => 											  
													  array(
														  array('text' => '<span class="details_key">Parent Science:</span><span class="details_value">'.$fos_name_parts[1].'</span>',
														  'leaf' => false, 
														  'iconCls' => 'info',
														  'expanded' => true,
														  'children' =>   
															  array(
																  array('text' => '<span class="details_key">Field of Science:</span><span class="details_value">'.$fos_name_parts[0].'</span>',
																  'leaf' => true, 
																  'expanded' => true,
																  'iconCls' => 'info'
																  )
															  )
														  
														  )
													  )
												  
												  );
			}
			
			$allocation_information[] = array('text' => '<span class="details_key">Field(s) of Science: </span>',
												  'leaf' => false, 
												  'expanded' => false,
												  'iconCls' => 'info',
												  'children' => $fos_information);									  
						  			
			
			$iconCls = 'allocation_100percent';
		
			if($usage < 1)
			{
				$iconCls = 'allocation_0percent';
			}else
			if($usage < 26)
			{
				$iconCls = 'allocation_25percent';
			}else
			if($usage < 51)
			{
				$iconCls = 'allocation_50percent';
			}else
			if($usage < 76)
			{
				$iconCls = 'allocation_75percent';
			}else
			{
				$iconCls = 'allocation_100percent';
			}
			$expanded = false;
			$keyClass = "details_key";
			if($allocation['status'] == 'expired')
			{
				$keyClass = 'details_key_expired';
				$expanded = false;
			}
			$details[] = array('text' => '<span class="'.$keyClass.'">'.($allocation['status'] == 'expired'?'(Expired) ' :'').'Allocation Id: '.$allocation['allocation_id'].'</span>', 
				 			   'leaf' => false, 
							   'expanded' => $expanded, 
							   'iconCls' => $iconCls,
							   'children' => $allocation_information);
			$index++;
			
		}
		
		//$details[] = array('id' => 'Allocation Information', 'text' => 'Allocation Information', 'leaf' => false, 'expanded' => true, 'children' => $allocations_information);
		
		$result['details'] = json_encode($details);
		
		echo  json_encode(array('totalCount' => 1, 
							    'data' => array($result),
								'success' => true, 
								'message' => '' ));
	
	}
	catch(Exception $ex)
	{
		echo  json_encode(array('totalCount' => 0, 
								'success' => false, 
			  					'message' => $ex->getMessage(), 
			  					'data' => array()));
	}
	

?>