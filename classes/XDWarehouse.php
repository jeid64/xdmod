<?php

   require_once dirname(__FILE__).'/../configuration/linker.php';

   use CCR\DB;

   /*
    * @Class XDWarehouse
    * XDMoD wrapper for accessing data from the data warehouse
    */

   class XDWarehouse {

      private $_pdo = null;

      function __construct() {

         $this->_pdo = DB::factory('datawarehouse');

      }//construct

      // ---------------------------

      public function totalGridUsers() {

         $recordCountQuery = $this->_pdo->query("SELECT COUNT(*) AS total_records FROM person");

         return $recordCountQuery[0]['total_records'];

      }//totalGridUsers

      // ---------------------------

      public function enumerateGridUsers($searchMode, $start, $limit, $nameFilter = NULL, $piFilter = false, $university_id = NULL) {

         if ($searchMode != FORMAL_NAME_SEARCH && $searchMode != USERNAME_SEARCH) {
            throw new \Exception('Invalid search mode specified');
         }
         
         if (!isset($start) || !isset($limit)){
            return array(0, array());
         }

         // Filter Logic ==================

         //$totalFilter = '';
         $filterElements = array();
                  
         if ($piFilter == true)   { 

            // Filter to account for principal investigators only

            $filterElements[] = 'p.id IN (SELECT DISTINCT(person_id) FROM principalinvestigator)'; 

         }

         if ($nameFilter != NULL) {

            // Filter according to a name specified 

            if ($searchMode == FORMAL_NAME_SEARCH) $filterElements[] = "CONCAT(p.last_name, ', ', p.first_name) LIKE '%$nameFilter%'";
            if ($searchMode == USERNAME_SEARCH) $filterElements[] = "CONCAT(s.username, '@', r.name) LIKE '$nameFilter%'";
            
         }
         
         if ($university_id != NULL) {
         
            $filterElements[] = "p.organization_id = $university_id";
         
         }

         if ($searchMode == FORMAL_NAME_SEARCH) $filterConcatClause = 'WHERE';
         if ($searchMode == USERNAME_SEARCH) $filterConcatClause = 'AND';
         
         $filter = (count($filterElements) > 0) ? $filterConcatClause.' '.implode(' AND ', $filterElements) : '';
         
         
         // ==============================                  
         
         switch($searchMode) {
         
            case FORMAL_NAME_SEARCH:    
                
               // For pagination, a total record count is needed ...
         
               $recordCountQuery = $this->_pdo->query("SELECT COUNT(*) AS total_records FROM person AS p $filter");
         
               $usersQuery = $this->_pdo->query("SELECT p.id, p.first_name, p.last_name FROM person AS p $filter ORDER BY p.last_name ASC, p.first_name ASC LIMIT $limit OFFSET $start");        
               
               break;
         
            case USERNAME_SEARCH:
            
               // For pagination, a total record count is needed ...
               
               $recordCountQuery = $this->_pdo->query("SELECT COUNT(*) AS total_records FROM systemaccount AS s, resourcefact AS r, person AS p  WHERE s.person_id = p.id AND r.id = s.resource_id $filter");
         
               $usersQuery = $this->_pdo->query("SELECT CONCAT(s.username, '@', r.name, ' (', p.last_name, ', ', p.first_name, ')') AS absusername, s.person_id AS id " .
                                                "FROM systemaccount AS s, resourcefact AS r, person AS p " .
                                                "WHERE s.person_id = p.id AND r.id = s.resource_id $filter ORDER BY absusername ASC, s.person_id LIMIT $limit OFFSET $start"); 
                                                
               break;             	
        	
        	}//switch($searchMode)
        	
         return array($recordCountQuery[0]['total_records'], $usersQuery);
        	
      }//enumerateGridUsers

      // ---------------------------

      public function resolveInstitutionName($institution_id) {

         $instQuery = $this->_pdo->query("SELECT name FROM organization WHERE id=$institution_id");

         if (count($instQuery) == 0){ return NO_MAPPING; }

         return $instQuery[0]['name'];

      }//resolveName
      
      // ---------------------------

      public function resolveName($person_id) {

         $nameQuery = $this->_pdo->query("SELECT CONCAT(last_name, ', ', first_name) AS formal_name FROM person WHERE id=$person_id"); 

         if (count($nameQuery) == 0){ return NO_MAPPING; }

         return $nameQuery[0]['formal_name'];

      }//resolveName

      // ---------------------------

      /*
       *
       * @function fetchMappedUserBucket
       * 
       * @param XDUser $user;
       *
       * @return int (a bucket id used to reference the 'page' in which the user is stored)
       *
       */

      public function fetchMappedUserBucket($user) {

         $formal_name = $user->getLastName().",".$user->getFirstName();

         $bucketQuery = $this->_pdo->query("SELECT offset FROM person_lut WHERE '$formal_name' >= CONCAT(starting_last_name, ',', starting_first_name) AND CONCAT(ending_last_name, ',', ending_first_name) >= '$formal_name'");

         return $bucketQuery[0]['offset'];

      }//fetchMappedUserBucket

      // ---------------------------

      /*
       *
       * @function enumerateFieldsOfScience
       *
       * @return array (a textual listing of all fields of science)
       *
       */

      public function enumerateFieldsOfScience() {

         $fos_entries = $this->_pdo->query("SELECT id, description FROM fieldofscience ORDER BY description");

         $fields = array();

         foreach ($fos_entries as $fos) {
            $fields[] = array('field_id' => $fos['id'], 'field_label' => $fos['description']);
         }
         
         return $fields;

      }//enumerateFieldsOfScience

   }//XDWarehouse

   // SELECT COUNT(*) as total_records FROM person WHERE id IN (SELECT DISTINCT(person_id) FROM principalinvestigator);

?>