<?php

use CCR\DB;

class DataWarehouse 
{
	private static $db = null;
	
	public static $_not_set = -9999999;

	public static function connect() 
	{
		if(!self::$db) 
		{		
			self::$db = DB::factory('datawarehouse');
		}
		return self::$db;

	}

	public function destroy() 
	{
		if(self::$db !== null) 
		{ 
			self::$db = null;
		}
	}

	function __destruct()
	{
		destroy();
   }	
 
	public static function getAllocations($config = array())
	{
	
	  $person_id = $config['person_id'];
	  $showActive = isset($config['show_active']) ? $config['show_active'] : true;
	  $allocation_id = isset($config['allocation_id']) ? $config['allocation_id'] : -1;
	  
	  $pi = "";
	  
	  if (isset($config['is_pi_of_allocation'])) {   
	     $pi = "and als.principalinvestigator_person_id ".($config['is_pi_of_allocation'] ? "" : "!")."= $person_id";
	  }
	  
	  self::connect();

		$query = "
		select distinct 
			als.allocation_id,
			als.principalinvestigator_person_id,
			als.person_id,
			als.charge_number,
			als.project_title,
			t.name as request_type, 
			als.resource_name,
			   als.base_allocation as base, 
			   format(als.base_allocation,2) as base_formatted, 
			   als.remaining_allocation as remaining, 
			   format(als.remaining_allocation,2) as remaining_formatted, 
			   als.pi_last_name, 
			   als.pi_first_name, 
			   als.project_title, 
			   als.status, 
			   als.initial_start_date as start, 
			   als.end_date as end 
		from modw_aggregates.allocation_summary als, resourcefact re, person pti, allocation a, transactiontype t, request req 
		where als.person_id = $person_id
		  and als.resource_id = re.id 
		  and als.allocation_id = a.id
		  and a.request_id = req.id
		  and t.id = req.request_type_id 
		  $pi 
		  and pti.id = als.person_id 
		 ".($allocation_id > -1 ? " " :" and als.status = '".($showActive?'active':'expired')."' ")."  
		  and als.allocation_id ".($allocation_id > 0?" = ".$allocation_id:" > -1 ")."
		 group by 
				als.allocation_id
		order by
			  end desc
		";
		
		$stmt = self::$db->handle()->prepare($query);
		$stmt->execute();
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);	 
		
		$results_b = array();
		
		$charge_allocation_map = array();
		
		// =================================================================================================
		
		$rct = 0;

		// Consolidation Phase (eliminating redundant records based on a common charge number)
		// The redundancies are also due to the same charge number across multiple resources (each of which has a unique allocation id)
		// Constructing a 1:M (M >= 1) map of charge number to allocation id(s) and/or resource(s)
	
		foreach ($results as $r) {
		
			$cn = $r['charge_number'];
			
			if (!isset($charge_allocation_map[$cn])) $charge_allocation_map[$cn] = array(
				'pi' => 0,
				'allocation_ids' => array(),
				'resources' => array(),
				'base' => 0,
				'remaining' => 0,
				'total_base_formatted' => 0,
				'total_remaining_formatted' => 0
			);
			
			$charge_allocation_map[$cn]['pi'] = $r['principalinvestigator_person_id'];
			$charge_allocation_map[$cn]['allocation_ids'][$r['allocation_id']] = array(
			   'name' => $r['resource_name'], 
			   'timeframe' => $r['start'].' to '.$r['end'],
			   'type' => $r['request_type']			
			);
			
			$charge_allocation_map[$cn]['resources'][] = array(
				'allocation_id' => $r['allocation_id'],
				'resource_name' => $r['resource_name'],
				'timeframe' => $r['start'].' to '.$r['end'],
				'type' => $r['request_type'],
				'base' => $r['base'],
				'remaining' => $r['remaining'],
				'base_formatted' => number_format($r['base'],2),
				'remaining_formatted' => number_format($r['remaining'],2)
			);
			
			// General project details =======================================
			
			$charge_allocation_map[$cn]['project_title'] = $r['project_title'];
			$charge_allocation_map[$cn]['charge_number'] = $r['charge_number'];
			//$charge_allocation_map[$cn]['description'] = $r['description'];
			$charge_allocation_map[$cn]['status'] = $r['status'];
			$charge_allocation_map[$cn]['start'] = $r['start'];
			$charge_allocation_map[$cn]['end'] = $r['end'];
			
			// Base & Remaining SU details ===================================
			
			$charge_allocation_map[$cn]['base'] += $r['base'];
			$charge_allocation_map[$cn]['remaining'] += $r['remaining'];
			
			$charge_allocation_map[$cn]['base_formatted'] = number_format($charge_allocation_map[$cn]['base'],2);
			$charge_allocation_map[$cn]['remaining_formatted'] = number_format($charge_allocation_map[$cn]['remaining'],2);
			
		}//foreach ($results as $r)
		
		// =================================================================================================
		
		foreach ($charge_allocation_map as &$c) {
			
			$allocation_ids = implode(',', array_keys($c['allocation_ids']));

			$query = "SELECT ab.allocation_id, ab.person_id, p.last_name, p.first_name, ab.used_allocation
		              FROM modw.allocationbreakdown AS ab, modw.person AS p
		              WHERE ab.person_id = p.id
		              AND ab.allocation_id
		              IN ($allocation_ids) 
		              ORDER BY ab.person_id";
					
			$stmt = self::$db->handle()->prepare($query);
			$stmt->execute();
		
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);	 
		
			$user_pool = array();

			// Construct an inverse map (allocation_id to user listing)
			$inverseMap = array();
						
			foreach ($results as $r) {
			
				$pid = $r['person_id'];
				
				// Force the (upcoming) sort procedure to place the PI at the top
				//$prefix = ($c['pi'] == $pid) ? "0" : "";
				
				if (!isset($user_pool[$pid])) {
					
					$user_pool[$pid] = array(
						'name' => $r['last_name'].", ".$r['first_name'],
						'is_pi' => ($c['pi'] === $pid),
						'total' => 0,
						'resources' => array()
					);
					
				}

				if (!isset($inverseMap[$r['allocation_id']])) {
					$inverseMap[$r['allocation_id']] = array(); 
				}
					
				if (number_format($r['used_allocation']) != 0) {
					
					// Append any utilized resources to the current user, each differentiated by allocation id
					
					$user_pool[$pid]['resources'][$r['allocation_id']] = array(
						//'allocation_id' => $r['allocation_id'],
						'name' => $c['allocation_ids'][$r['allocation_id']]['name'], 
						'timeframe' => $c['allocation_ids'][$r['allocation_id']]['timeframe'], 
						'type' => $c['allocation_ids'][$r['allocation_id']]['type'], 
						'used' => number_format($r['used_allocation'],2)
					);

					$inverseMap[$r['allocation_id']][] = array(
						'name' => $user_pool[$pid]['name'], 
						'is_pi' => ($c['pi'] === $pid),
						'consumption' => $r['used_allocation'],
						'used' => number_format($r['used_allocation'],2)
					);
					
				}
				
				$user_pool[$pid]['total'] += $r['used_allocation'];
				$user_pool[$pid]['total_formatted'] = number_format($user_pool[$pid]['total'],2);

			}//foreach ($results as $r)
						
			// ----------------------------------------------------
			
			foreach ($c['resources'] as &$cr) {
			
				$cr['users'] = $inverseMap[$cr['allocation_id']];
				
				// Sort users on each resource (by decreasing usage)
				usort($cr['users'], function($a, $b){
					return $b['consumption'] - $a['consumption'];
					//return strcmp($a['name'], $b['name']);  <-- former sort method was alphabetical, ascending
					
				});
				
				unset($cr['allocation_id']);
				
			}//foreach
			 
			unset($c['allocation_ids']);
			
			// Sort the resources (associated with a user) by (ascending) resource name
			foreach ($user_pool as $p => &$v) {
				
				$v['resources'] = array_values($v['resources']);
				
				usort($v['resources'], function($a, $b){
					return strcmp($a['name'], $b['name']);
				});

			}//foreach

			// Cache the PI user entry and remove it from $user_pool (prior to being sorted)
			$pi_slot = $user_pool[$c['pi']];
			unset($user_pool[$c['pi']]);
			unset($c['pi']);
			
			$c['users'] = array_values($user_pool);

			// Sort users by total usage (descending)		
			usort($c['users'], function($a, $b){
				
				return $b['total'] - $a['total'];
				// return strcmp($a['name'], $b['name']);  <-- former sort method was alphabetical, ascending
				
			});

			// PI will be @ top, regardless of sort outcome
			array_unshift($c['users'], $pi_slot);
			
			// Sort the resources alphabetically
			
			usort($c['resources'], function($a, $b){
				return strcmp($a['resource_name'], $b['resource_name']);
			});

		}//foreach ($charge_allocation_map as &$c)

		// =================================================================================================
		
		return array_values($charge_allocation_map);
					
	}//getAllocations
		
		
	public static function getAllocationsByChargeNumber($person_id, $start_date, $end_date, $is_pi = false)
	{
	self::connect();

		$query = "
		select 
			als.charge_number,
			als.allocation_id,
			group_concat(distinct(concat(trim(als.project_title),'|', coalesce(als.proposal_number,'NA'), '|', coalesce(als.grant_number,'NA'))) order by als.project_title separator ';') as project_titles,
			group_concat(distinct(concat(als.fos_name, ', ', als.fos_parent_name, ', ', als.fos_directorate_name)) order by als.fos_name separator ';') as fos_names,
			group_concat(distinct(concat(alr_rs.code, ' - ', alr_rs.name)) order by alr_rs.code  ) as resource_names,
			group_concat(distinct(concat(als.pi_last_name, ', ', als.pi_first_name)) order by als.pi_last_name separator ';') as pi_names,
			   als.status,
			   min(als.initial_start_date) as start,
			   max(als.end_date) as end,
			   sum(als.base_allocation) as base, 
			   format(sum(als.base_allocation),0) as base_formatted, 
			   sum(als.initial_allocation) as initial, 
			   format(sum(als.initial_allocation),0) as initial_formatted,
			   sum(als.remaining_allocation) as remaining, 
			   format(sum(als.remaining_allocation),1) as remaining_formatted, 
			   sum(als.total_used_allocation) as used,
			   format(sum(als.total_used_allocation),1) as used_formatted,
			   sum(als.total_used_allocation_by_user) as used_by_user,
			   format(sum(als.total_used_allocation_by_user),1) as used_by_user_formatted
			   
		from modw_aggregates.allocation_summary als, person pti, allocationonresource alr, resourcefact alr_rs
		where als.".($is_pi?'principalinvestigator_person_id':'person_id')." = $person_id
		  and pti.id = als.person_id 
		  and alr.allocation_id = als.allocation_id
		  and alr.allocation_state_id = 2
		  and alr_rs.id = alr.resource_id
		  and
		  (als.initial_start_date between :start_date and :end_date or
						  :start_date between als.initial_start_date and als.end_date) 
		group by als.charge_number,
				als.allocation_id,
		       als.status
		order by
			  status, als.allocation_id, end desc
		";

		$stmt = self::$db->handle()->prepare($query);
		$stmt->execute(array('start_date'=> $start_date, 'end_date' =>$end_date));
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);	 
		
		
		
		return $results;		
	}

	// -----------------------------------------------------------
		
	public static function getPrincipalInvestigator($allocation_id) {
	
	  self::connect();
	
      $stmt = self::$db->handle()->prepare("SELECT DISTINCT(principalinvestigator_person_id) AS principal_investigator
			                                   FROM modw_aggregates.allocation_summary WHERE allocation_id=:allocation_id");
			                                   
		$stmt->execute(array('allocation_id'=> $allocation_id));
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (count($results) == 0) {
         throw new Exception('Invalid allocation id specified');
		}	 
		
		return $results[0]['principal_investigator'];	
		
	}//getPrincipalInvestigator
	
	// -----------------------------------------------------------
	
	public static function getAllocationData($allocation_id)
	{
	
      self::connect();

      // alr.allocation_state_id = 2  <-- only account for active resources (?)

		$query = "
		select 
			als.charge_number,
			als.allocation_id,
			group_concat(distinct(concat(trim(als.project_title),'|', coalesce(als.proposal_number,'NA'), '|', coalesce(als.grant_number,'NA'))) order by als.project_title separator ';') as project_titles,
			group_concat(distinct(concat(als.fos_name, ', ', als.fos_parent_name, ', ', als.fos_directorate_name)) order by als.fos_name separator ';') as fos_names,
			group_concat(distinct(concat(alr_rs.code, ' - ', alr_rs.name)) order by alr_rs.code  ) as resource_names,
			group_concat(distinct(concat(als.pi_last_name, ', ', als.pi_first_name)) order by als.pi_last_name separator ';') as pi_names,
			   als.status,
			   min(als.initial_start_date) as start,
			   max(als.end_date) as end,
			   sum(als.base_allocation) as base, 
			   format(sum(als.base_allocation),0) as base_formatted, 
			   sum(als.initial_allocation) as initial, 
			   format(sum(als.initial_allocation),0) as initial_formatted,
			   sum(als.remaining_allocation) as remaining, 
			   format(sum(als.remaining_allocation),1) as remaining_formatted, 
			   sum(als.total_used_allocation) as used,
			   format(sum(als.total_used_allocation),1) as used_formatted,
			   sum(als.total_used_allocation_by_user) as used_by_user,
			   format(sum(als.total_used_allocation_by_user),1) as used_by_user_formatted
			   
		from modw_aggregates.allocation_summary als, person pti, allocationonresource alr, resourcefact alr_rs
		where 
		  pti.id = als.person_id 
		  and alr.allocation_id = als.allocation_id
		  and als.allocation_id = $allocation_id
		  and alr.allocation_state_id = 2
		  and alr_rs.id = alr.resource_id 
		group by als.charge_number,
				als.allocation_id,
		       als.status
		order by
			  status, als.allocation_id, end desc
		";

		$stmt = self::$db->handle()->prepare($query);
		$stmt->execute();
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);	 
		
		return $results;		
		
   }//getAllocationData
	
   // -----------------------------------------------------------
		
	public static function getPersonDetails($person_id)
	{
		self::connect();
		$people_query = "
			select trim(concat(p.last_name, ', ', p.first_name, ' ',coalesce(p.middle_name, ''))) as full_name,
					o.name as organization,
				    department,
					nsfstatus.name as status_code,
					case when (p.id in (select person_id from principalinvestigator)) then 'Yes' else 'No' end as Is_PI
			from person p, organization o, nsfstatuscode nsfstatus
			where p.id = :person_id 
			  and o.id = p.organization_id
			  and nsfstatus.id = p.nsfstatuscode_id
		";
		$stmt = self::$db->handle()->prepare($people_query);
		$stmt->execute(array('person_id' => $person_id));
		
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);	
	
		if(count($results) > 0)
		{
			return $results[0];
		}
		return array() ;
	}
	
	public static function daysInYear($year)
	{
		return DataWarehouse::isLeapYear($year)?366:365;
	}
		/**
	* This function gets a year as a parameter and returns a boolean,
	* true for leap years and false for normal years.
	*
	* @param int $year
	* @return boolean
	*/
	public static function isLeapYear($year)
	{
		# Check for valid parameters #
		if ($year < 0)
		{
			throw new \Exception('Wrong parameter for $year in function isLeapYear. It must be a positive integer.'.$year);
		}
		   
		# In the Gregorian calendar there is a leap year every year divisible by four
		# except for years which are both divisible by 100 and not divisible by 400.
		   
		if ($year % 4 != 0)
		{
			return false;
		}
		else
		{
			if ($year % 100 != 0)
			{
				return true;    # Leap year
			}
			else
			{
				if ($year % 400 != 0)
				{
					return false;
				}
				else
				{
					return true;    # Leap year
				}
			}
		}
	} 

}


?>
