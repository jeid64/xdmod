<?php

	require_once dirname(__FILE__).'/../configuration/linker.php';

   use CCR\DB;

	/*
	 * @Class XDAdmin
	 * XDMoD Administrative User
	 */

   class XDAdmin {
	
      private $_pdo = null;
      
      function __construct() {

         $this->_pdo = DB::factory('database');
			
      }//__construct
	
		// ---------------------------
			
		public function getUserListing($group_filter = 0) {
		
         $filterSQL = '';
		   
		   if ($group_filter != 0) {
            $filterSQL = "WHERE u.user_type = $group_filter";
		   }
		   
		   $query  = "SELECT u.id, u.username, u.first_name, u.last_name, u.account_is_active, ";
		   $query .= "CASE ";
		   $query .= "WHEN (SELECT init_time FROM SessionManager WHERE user_id=u.id ORDER BY init_time DESC LIMIT 1) IS NULL ";
		   $query .= "THEN '0' ";
		   $query .= "ELSE (SELECT init_time FROM SessionManager WHERE user_id=u.id ORDER BY init_time DESC LIMIT 1) ";
		   $query .= "END AS last_logged_in FROM Users AS u $filterSQL ORDER BY last_logged_in DESC";
		   
         $userQuery = $this->_pdo->query($query);
        	
         return $userQuery;
        	
		}//getUserListing

		// ---------------------------
					
		public function updateAccountRequestStatus($id = -1, $creator = '') {

         $create_message = 'Created on '.date('Y-m-d \a\t h:i A');
         if (!empty($creator)){ $create_message .= " by $creator"; }

         $this->_pdo->execute(
                              "UPDATE AccountRequests SET status='$create_message' WHERE id=:id", 
                              array('id' => $id)
                      );
        	
		}//updateAccountRequestStatus
		
		// ---------------------------
					
		public function enumerateRoles() {

         $rolesResults = $this->_pdo->query("SELECT description, abbrev FROM Roles ORDER BY description");
        	
         return $rolesResults;
        	
		}//enumerateRoles
		
		// ---------------------------
					
		public function enumerateExceptionEmailAddresses() {

         $emailAddressResults = $this->_pdo->query("SELECT email_address FROM ExceptionEmailAddresses ORDER BY email_address");
        	
        	$results = array();
        	
         foreach($emailAddressResults as $address) {
            $results[] = $address['email_address'];
         }
      
         return $results;
        	
		}//enumerateExceptionEmailAddresses
				
		// ---------------------------

      // enumerateResourceProviders:
      // A listing of resource providers that ever ran jobs, along with their id
      
		public function enumerateResourceProviders() {
                      
         $pdo = DB::factory('datawarehouse');
                       
         $rpResults = $pdo->query("SELECT DISTINCT organization_id as id, o.abbrev as organization, o.name as name FROM modw_aggregates.jobfact_by_quarter, modw.organization AS o WHERE o.id = organization_id ORDER BY o.abbrev ASC");
        	
         return $rpResults;
        	
		}//enumerateResourceProviders

		// ---------------------------

      // enumerateInstitutions:
      // A listing of institutions along with their id
      
		public function enumerateInstitutions($start, $limit, $name_filter = NULL) {

         $pdo = DB::factory('datawarehouse');
      
         $filter = (!empty($name_filter)) ? "WHERE name LIKE '%$name_filter%'" : '';
      
         $institutionCountQuery = $this->_pdo->query("SELECT COUNT(*) AS total_records FROM modw.organization $filter");
               
         $institutionResults = $pdo->query("SELECT id, name FROM modw.organization $filter ORDER BY name ASC LIMIT $limit OFFSET $start");
        	
         return array($institutionCountQuery[0]['total_records'], $institutionResults);
        	
		}//enumerateInstitutions

		// ---------------------------

      // enumerateUserTypes:
      // A listing of user types
      
		public function enumerateUserTypes() {
                      
         $pdo = DB::factory('database');
                       
         $typeResults = $pdo->query("SELECT id, type FROM moddb.UserTypes ORDER BY type ASC");
        	
         return $typeResults;
        	
		}//enumerateUserTypes				

	}//XDAdmin
	
?>
