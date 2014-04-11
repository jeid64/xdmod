<?php

   require_once dirname(__FILE__).'/../configuration/linker.php';

   use CCR\DB;
   
   class PrincipalInvestigator {
      
      private $_pdo;
      
      // --------------------------------------------
            
      function __construct() {

         $this->_pdo = DB::factory('datawarehouse');

      }//__construct

      // --------------------------------------------
      
      public function enumerate() {
      
          $result_data = $this->_pdo->query("SELECT p.id, CONCAT (
                                               p.last_name, ', ', p.first_name, ' ', 
                                               CASE WHEN p.middle_name IS NULL then '' ELSE p.middle_name END
                                            ) AS full_name, o.name AS organization, p.department
                                            FROM person AS p, organization AS o 
                                            WHERE p.id IN (SELECT DISTINCT(person_id) FROM principalinvestigator) 
                                            AND p.organization_id = o.id 
                                            ORDER BY p.last_name ASC, p.first_name ASC");
                           
         return $result_data;
                                
      }//enumerate		
      
      // --------------------------------------------      
      
      public function getDetails($id) {

         $pi_details = array();
         
         $pi_details['id'] = $id;
         
         $person_details = DataWarehouse::getPersonDetails($id);
         
         if (count($person_details) == 0) {
            throw new \Exception('The User ID you have specified is invalid');
         }

         //var_dump($person_details);
         
         // --------------
                  
         list($first_name, $middle_name, $last_name) = $this->_parseName($person_details['full_name']);
         
         $pi_details['name']['full'] = $person_details['full_name'];
         $pi_details['name']['first'] = $first_name;
         $pi_details['name']['middle'] = $middle_name;
         $pi_details['name']['last'] = $last_name;
         
         $pi_details['organization'] = $person_details['organization'];
         $pi_details['department'] = $person_details['department'];
         $pi_details['status'] = $person_details['status_code'];
         
         $allocations_information = array();
         
         // 4th argument -- if true, display allocations governed by the PI as opposed to the PI at least being a member of ?
         
         try {
         
            $allocations = DataWarehouse::getAllocationsByChargeNumber($id, '1900-01-01', date('Y-m-d'), $this->_userIsPrincipalInvestigator($id));
            
            $pi_details['governed_allocations'] = $this->_getAllocationOverview($id);
            $pi_details['other_allocations'] = $this->_getAllocationOverview($id, false);
                              
            $allocation_ids = array();
                     
            $allocation_ids['active'] = array();
            $allocation_ids['expired'] = array();
            
            $active_allocation_resources = array();
            
            foreach($allocations as $allocation) {
               
               $resources_of_active_allocations = $this->_getResourcesOfAllocationById($allocation['allocation_id']);
               
               foreach($resources_of_active_allocations as $resource) {
                                 
                  $allocationStats = $this->_getAllocationStatsByChargeAndResource($allocation['charge_number'], $resource['resource_id']);
                  
                  if (count($allocationStats) == 1) {
                     $active_allocation_resources[] = $allocationStats[0];
                  }
                  
               }//foreach
            
            }//foreach
            
            $pi_details['resources_of_active_governed_allocations'] = $active_allocation_resources;
            
            $result_data = $pi_details;
            
            return $result_data;
         
         }
         catch(\Exception $e) {
         
            throw new \Exception('The User ID you have specified is invalid');
            
         }
      
      }//getDetails
      
      // --------------------------------------------     
            
      public function getJobsRan($id, $start_date, $end_date) {
               
         $result_data['id'] = $id;
         $result_data['start_date'] = $start_date;
         $result_data['end_date'] = $end_date;         
            
         $parameters = array();
         $parameters[] = new \DataWarehouse\Query\Model\Parameter('principalinvestigator_person_id', '=', $id);
         $query = new \DataWarehouse\Query\Jobs\Aggregate('auto', $start_date, $end_date, 'none', 'all', $parameters);
   		
         $e = $query->execute();
         $job_count_pi_only = $e['job_count'][0];
         
         $result_data['jobs_aggregated_by_pi_group'] = $job_count_pi_only;
      
         $parameters = array();
         $parameters[] = new \DataWarehouse\Query\Model\Parameter('person_id', '=', $id);
         $query = new \DataWarehouse\Query\Jobs\Aggregate('auto', $start_date, $end_date, 'none', 'all', $parameters);
      
         $e = $query->execute();
         $job_count_group = $e['job_count'][0];
         
         $result_data['jobs_pi_only'] = $job_count_group;  
         
         return $result_data;
         
      }//getJobsRan
      
      // --------------------------------------------  
            
      public function getAllocationDetails($allocation_id) {
                         
         try {
            
            $allocation_data = DataWarehouse::getAllocationData($allocation_id);
            
            if (count($allocation_data) == 0) {
            
               throw new \Exception('No allocation is mapped to this id');
            
            }
            
            $allocation_data = $allocation_data[0];
         
         }
         catch(Exception $e) {
            
            if (strpos($e->getMessage(), 'Column not found') !== false) {
               throw new \Exception('Invalid value specified for allocation id');
            }
            else {
               throw new \Exception($e->getMessage());
            }
            
         }
         
         //$allocations = DataWarehouse::getAllocationsByChargeNumber(2990, '1900-01-01', date('Y-m-d'), true);      

         $result_data['allocation_id'] = $allocation_id;      
               
         $result_data['jobs_under_allocation'] = $this->_getJobCount($allocation_id);
         
         $result_data['charge_number'] = $allocation_data['charge_number'];
         $result_data['status'] = $allocation_data['status'];
         
         // SU Details -----------------------
         
         $result_data['su_details'] = array(
         
            'base' => $allocation_data['base'],
            //'initial' => $allocation_data['initial'],
            'consumed' => $allocation_data['used'],
            'remaining' => $allocation_data['remaining']
         
         );
         
         // Allocation lifetime --------------
         
         $result_data['lifetime'] = array(
         
            'creation' => $allocation_data['start'],
            'expiration' => $allocation_data['end']
         
         );

         // Projects -------------------------
         
         $projects = explode(';', $allocation_data['project_titles']);
         $project_titles = array();
         
         foreach($projects as $project) {
         
            $project_details = explode('|', $project);
            
            $result_data['associated_projects'][] = array('name' => $project_details[0], 'grant' => $project_details[1]);
            
            //print $project.'<br>';
         
         }//foreach

         // Resources, Membership -------------------------         

         $result_data['resources'] = $this->_getAllocationStatsById($allocation_id); 
                        
         $pi_user = DataWarehouse::getPrincipalInvestigator($allocation_id);         
         
         $result_data['members'] = $this->_getUsersOfAllocation($allocation_id, $pi_user);
         
         return $result_data;
         
      }//getAllocationDetails
      
      // ===========================================================================       
      
      private function _getAllocationOverview($user_id, $governed_by_user = true) {
        
         $allocationDetails = array();
         
         $allocationQuery = $this->_pdo->query("SELECT allocation_id, status 
                                         FROM modw_aggregates.allocation_summary 
                                         WHERE person_id=$user_id AND principalinvestigator_person_id ".($governed_by_user ? '=' : '!=')." person_id 
                                         GROUP BY allocation_id");
                                                          
         foreach($allocationQuery as $allocation) {
         
            $allocationDetails[$allocation['status']][] = $allocation['allocation_id'];
         
         }//foreach
              
         return $allocationDetails;                          
   
      }//_getAllocationOverview
         
      // ------------------------------------------------------
      
      private function _parseName($name) {
      
         //Name Format:  last, first [middle]
            
         $name_details = explode(' ', $name);
            
         $last_name = str_replace(',', '', $name_details[0]);
         $first_name = $name_details[1];
         $middle_name = (count($name_details) == 3) ? $name_details[2] : '';  
         
         return array($first_name, $middle_name, $last_name);
      
      }//_parseName
      
      // ------------------------------------------------------
         
      private function _userIsPrincipalInvestigator($id) {
               
         $piQuery = $this->_pdo->query("SELECT id FROM person 
                                 WHERE id IN (SELECT DISTINCT(person_id) FROM principalinvestigator) AND id='$id'");
                                  
         return (count($piQuery) != 0);
      
      }//_userIsPrincipalInvestigator
      
      // ------------------------------------------------------
         
      private function _getResourcesByAllocation($allocation_id) {
   
         $resourceQuery = $this->_pdo->query("SELECT r.name AS hostname, r.code, r.id
                                       CASE 
                                          WHEN r.description IS NULL 
                                             THEN 'No description available' 
                                          ELSE
                                             r.description
                                       END AS description 
                                       FROM resourcefact AS r, allocationonresource AS a 
                                       WHERE a.resource_id = r.id AND a.allocation_id=$allocation_id");
      
      
         return $resourceQuery;
      
      }//_getResourcesByAllocation
      
      // ------------------------------------------------------
      
      private function _getActiveChargesFromUser($user_id) {
            
         $chargeQuery = $this->_pdo->query("SELECT charge_number 
                                     FROM modw_aggregates.allocation_summary 
                                     WHERE person_id=$user_id 
                                     AND status='active' 
                                     GROUP BY charge_number");
      
         return $chargeQuery;
         
      }//_getActiveChargesFromUser
      
      // ------------------------------------------------------ 
      
      private function _getResourcesOfAllocationById($allocation_id) {
            
         /*
         $resourceQuery = $this->_pdo->query("SELECT a.resource_id, r.code AS resource_code  
                                       FROM modw_aggregates.allocation_summary AS a, modw.resourcefact AS r 
                                       WHERE a.charge_number='$charge_number' 
                                       AND a.status = 'active' 
                                       AND a.resource_id = r.id 
                                       GROUP BY a.resource_id ORDER BY r.code");
         */
         
         $resourceQuery = $this->_pdo->query("SELECT a.resource_id, r.code AS resource_code  
                                 FROM modw_aggregates.allocation_summary AS a, modw.resourcefact AS r 
                                 WHERE a.allocation_id='$allocation_id' 
                                 AND a.status = 'active' 
                                 AND a.resource_id = r.id 
                                 GROUP BY a.resource_id ORDER BY r.code");
           
         return $resourceQuery;
           
      }//_getResourcesOfAllocationById
      
      // ------------------------------------------------------
      
      private function _getAllocationStatsByChargeAndResource($charge_number, $resource_id) {
      
         $allocationQuery = $this->_pdo->query("SELECT r.id, 
                                         REPLACE (r.name, '*', '') AS name,  
                                         REPLACE (r.code, '*', '') AS code, 
                                         a.base_allocation, 
                                         a.remaining_allocation, a.initial_start_date, a.initial_end_date,
                                         CASE WHEN INSTR(r.name, '*') = 0 THEN '' ELSE 'insufficient_data' END AS additional_information 
                                         FROM modw.allocation AS a, modw.resourcefact AS r, modw.account AS ac 
                                         WHERE r.id = a.resource_id 
                                         AND ac.id = a.account_id 
                                         AND r.id=$resource_id 
                                         AND ac.charge_number='$charge_number' 
                                         ORDER BY a.initial_start_date DESC LIMIT 1");
      
         return $allocationQuery;
         
      }//_getAllocationStatsByChargeAndResource
      
      // ------------------------------------------------------
   
      private function _getAllocationStatsById($allocation_id) {
         
         $allocationQuery = $this->_pdo->query("SELECT r.id, r.name, r.code, a.base_allocation, 
                                         a.remaining_allocation, a.initial_start_date, a.initial_end_date 
                                         FROM modw.allocation AS a, modw.resourcefact AS r, 
                                         modw.account AS ac WHERE r.id = a.resource_id 
                                         AND ac.id = a.account_id  AND a.id=$allocation_id 
                                         ORDER BY a.initial_start_date DESC");
   
         return $allocationQuery;
         
      }//_getAllocationStatsById
      
      // ------------------------------------------------------
      
      private function _getJobCount($allocation_id, $member_id = '') {
      
         $query = "SELECT SUM(job_count) AS job_count FROM modw_aggregates.jobfact_by_quarter WHERE allocation_id=$allocation_id";
      
         //$query = "SELECT COUNT(*) as job_count FROM jobfact WHERE allocation_id=$allocation_id";
         
         if (!empty($member_id)){ $query .= " AND person_id=$member_id"; }
         
         $jobCountQuery = $this->_pdo->query($query);
         
         return $jobCountQuery[0]['job_count'];
      
      }//_getJobCount
      
      // ------------------------------------------------------ 
         
      private function _getUsersOfAllocation($allocation_id, $pi_user_id) {
   
         $allocationUsersQuery = $this->_pdo->query("SELECT a.person_id AS id, 
                                              CONCAT(p.last_name, ', ', p.first_name, ' ', 
                                                     CASE WHEN p.middle_name IS NULL then '' ELSE p.middle_name END
                                              ) AS full_name,
                                              a.percentage as su_percent_quota, a.used_allocation as su_consumption
                                              FROM allocationbreakdown AS a, person AS p 
                                              WHERE a.person_id = p.id AND a.allocation_id=$allocation_id");
         
         $users = array();
         
         foreach($allocationUsersQuery as &$user) {
         
            list($first_name, $middle_name, $last_name) = $this->_parseName($user['full_name']);
            
            $user['is_pi'] = ($user['id'] == $pi_user_id);
            $user['job_contribution'] = $this->_getJobCount($allocation_id, $user['id']);
            $user['name']['full'] = $user['full_name'];
            $user['name']['first'] = $first_name;
            $user['name']['middle'] = $middle_name;
            $user['name']['last'] = $last_name;
            
            unset($user['full_name']);
                  
         }//foreach
         
         return $allocationUsersQuery;
         
      }//_getUsersOfAllocation          
      
   }//class PrincipalInvestigator
      
?>
