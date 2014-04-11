<?php

	namespace xd_security;

	const SESSION_EXPIRED = 'Session Expired';

	// --------------------------------
	
	function detectUser($failover_methods = array()) {
	
	   // - Attempt to get a logged in user
	   // - Should a logged in user not exist, inspect $failover_methods to determine the next kind of
	   //   user to fetch
	   
      try {
	     
         $user = getLoggedInUser();
         
      }
      catch (\Exception $e) {
	  
	      if (count($failover_methods) == 0)
            throw new \Exception(SESSION_EXPIRED, \UserException::NO_LOGGED_IN_USER);
	      
         switch($failover_methods[0]) {
	      
            case \XDUser::PUBLIC_USER:
	        
               if (isset($_REQUEST['public_user']) && $_REQUEST['public_user'] === 'true') {
                  return \XDUser::getPublicUser(); 
               }
               else {
                  throw new \Exception(SESSION_EXPIRED, \UserException::NO_PUBLIC_USER);
               }
	                  
               break;
	                	           
            case \XDUser::INTERNAL_USER:
	           
               try {
	              
                  return getInternalUser();
	              
               }
               catch(\Exception $e) {
	           
                  if (isset($failover_methods[1]) && $failover_methods[1] == \XDUser::PUBLIC_USER) {
	              
                     if (isset($_REQUEST['public_user']) && $_REQUEST['public_user'] === 'true') {
                        return \XDUser::getPublicUser(); 
                     }
                     else {
                        throw new \Exception(SESSION_EXPIRED, \UserException::NO_PUBLIC_USER);
                     }
	                 
                  }
                  else {
                     throw new \Exception(SESSION_EXPIRED, \UserException::NO_INTERNAL_USER);
                  }
	           
               }
	      
               break;
	           
            default: 
            
               throw new \Exception(SESSION_EXPIRED, \UserException::NO_LOGGED_IN_USER);

               break;
	           
         }//switch($failover_methods[0])
	     
      }//catch (\Exception $e)

      return $user;

   }//detectUser

	// --------------------------------	
	
	/*	
      @function assertDashboardUserLoggedIn
      This is merely to check if a dashboard user has logged in (and not make use of the respective XDUser object)
   */
   
   function assertDashboardUserLoggedIn() {
   
      try {
         return getDashboardUser();
      }
      catch(\Exception $e) {

         \xd_controller\returnJSON(array(
            'success' => false,
            'status' => $e->getMessage()
         ));
   
         exit;    

      }
   
   }//assertDashboardUserLoggedIn

	// --------------------------------	

	/*	
      @function getDashboardUser
      @returns an instance of XDUser pertaining to the dashboard user
      @throws an Exception if: 
         - the session variable pertaining to the dashboard user does not exist
         - the user_id stored in the session variable does not map to a valid XDUser
         - the user does not have manager privileges
   */	   

	function getDashboardUser() {

    	if (!isset($_SESSION['xdDashboardUser'])) {
    		throw new \Exception('Dashboard session expired');
    	}
    	
	   $user = \XDUser::getUserByID($_SESSION['xdDashboardUser']);
	   		
      if ($user == NULL) {
         throw new \Exception('User does not exist');
      }
	  
      if ($user->isManager() == false) {
         throw new \Exception('Permissions do not allow you to access the dashboard');
      }
      
      return $user;
      
	}//getDashboardUser
	
	// --------------------------------	
		
	function getLoggedInUser() {

    	if (!isset($_SESSION['xdUser'])) {
    		throw new \Exception('Session expired');
    	}
    	
	   $user = \XDUser::getUserByID($_SESSION['xdUser']);
	   		
      if ($user == NULL) {
         throw new \Exception('User does not exist');
      }
	  
      return $user;
      
	}//getLoggedInUser
	
	// --------------------------------	
		
	function getInternalUser() {

      if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == '127.0.0.1' && isset($_REQUEST['user_id'])) {
      
         $user = \XDUser::getUserByID($_REQUEST['user_id']);
         
         if ($user == NULL) {
            throw new \Exception('Internal user does not exist');
         }
      
      }
      else {
         throw new \Exception('Internal user not specified');
      }	     

      return $user;
      
	}//getInternalUser
		
	// --------------------------------	
		
	function enforceUserRequirements($requirements, $session_variable = 'xdUser') {
	
		$returnData = array();
		
		if (in_array(STATUS_LOGGED_IN, $requirements)) {

			if (!isset($_SESSION[$session_variable])) {
			
				$returnData['status'] = 'not_logged_in';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'Session Expired';
				$returnData['data'] = array();
				\xd_controller\returnJSON($returnData);	
				
			}//if (!isset($_SESSION[$session_variable]))
				
			$user = \XDUser::getUserByID($_SESSION[$session_variable]);
			
			if ($user == NULL){
			
				$returnData['status'] = 'user_does_not_exist';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'user_does_not_exist';
				$returnData['data'] = array();
				\xd_controller\returnJSON($returnData);	
				
			}//if ($user == NULL)
			
			/*
			NOTE: Need to add this back in when the UI can support this properly
			if ($user->getAccountStatus() == INACTIVE) {
			
				$returnData['status'] = 'account_disabled';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'account_disabled';
				$returnData['data'] = array();
				\xd_controller\returnJSON($returnData);
					
			}//INACTIVE
         */

			// -------------------------------------

			if ($user->isManager()){
				// Manager subsumes 'Science Advisory Board Member' role
				\xd_utilities\remove_element_by_value($requirements, SAB_MEMBER);
			}
		 
			// -------------------------------------
		
			if (in_array(SAB_MEMBER, $requirements)){
			
				// This user must be a member of the Science Advisory Board
				
				if (!in_array('sab', $user->getRoles())){
				
					$returnData['status'] = 'not_sab_member';
					$returnData['success'] = false;
					$returnData['totalCount'] = 0;
					$returnData['message'] = 'not_sab_member';
					$returnData['data'] = array();
					\xd_controller\returnJSON($returnData);	
					
				}
				
			}//SAB_MEMBER
			
			if (in_array(STATUS_MANAGER_ROLE, $requirements)){
			
				if (!($user->isManager())){
				
					$returnData['status'] = 'not_a_manager';
					$returnData['success'] = false;
					$returnData['totalCount'] = 0;
					$returnData['message'] = 'not_a_manager';
					$returnData['data'] = array();
					\xd_controller\returnJSON($returnData);	
					
				}
				
			}//STATUS_MANAGER_ROLE

			if (in_array(STATUS_CENTER_DIRECTOR_ROLE, $requirements)){
			
			   if ($user->getActiveRole()->getIdentifier() != ROLE_ID_CENTER_DIRECTOR) {
				   
					$returnData['status'] = 'not_a_center_director';
					$returnData['success'] = false;
					$returnData['totalCount'] = 0;
					$returnData['message'] = 'not_a_center_director';
					$returnData['data'] = array();
               \xd_controller\returnJSON($returnData);
						
            }
				
			}//STATUS_CENTER_DIRECTOR_ROLE
								
		}//if (in_array(STATUS_LOGGED_IN, $requirements))
		
	}//enforceUserRequirements
	
	// --------------------------------
		
	// secureCheck: ensures that all of the $_REQUEST[keys] in $required_params conform to their
	// respective patterns (e.g. $required_params = array('uid' => RESTRICTION_UID) : $_REQUEST['uid'] has 
	// to comply with the pattern in RESTRICTION_UID
	
	// If $enforce_all is set to 'false', then secureCheck will return an integer indicating how many of 
	// the params qualify (this is used for cases in which at least one parameter is required, but not all)
	
	function secureCheck(&$required_params, $m, $enforce_all = true) {

		// ${'_'.$m}['param'] <-- should be working, but doesn't inside this function

		$qualifyingParams = 0;
		
		if ($m == 'GET') $param_array = $_GET;
		if ($m == 'POST') $param_array = $_POST;
		if ($m == 'REQUEST') $param_array = $_REQUEST;
		
		foreach ($required_params as $param => $pattern) {
		
			if (!isset($param_array[$param])){
				if ($enforce_all){ return false; }
				if (!$enforce_all){ continue; }
			} 
			
			$param_array[$param] = preg_replace('/\s+/', ' ', $param_array[$param]);
			
			if (preg_match($pattern, $param_array[$param]) == 0) {
				if ($enforce_all){ return false; }
				if (!$enforce_all){ continue; }
			}
			
			$qualifyingParams++;
			
		}//foreach ($required_params...

		if ($enforce_all){ return true; }
		if (!$enforce_all){ return $qualifyingParams; }
		
	}//secureCheck
	
	// --------------------------------	
   
   function assertParametersSet($requiredParams = array()) {
      
      foreach ($requiredParams as $k => $v) {
   
         if (!is_int($k)) {
   		
            //$k represents the name of the param
            //$v represents the format of the value that param must conform to (a regex)
   		   
            $param_name = $k;
            $pattern = $v;

         }
         else {

            //$v represents the name of the param
   		      		
            $param_name = $v;
            $pattern = '/.*/';
   			
         }	
   
         assertParameterSet($param_name, $pattern);
			
   	}//foreach ($test as $k => $v)
	
	}//assertParametersSet
	
	// --------------------------------	   

   // assertParameterSet: Provides a checkstop when a required argument has not been 
   //                     supplied in a web request (using GET or POST).
   
   function assertParameterSet($param_name, $pattern = '/.*/') {
 
      if (!isset($_REQUEST[$param_name])) {
         
         \xd_response\presentError("$param_name not specified");
         
      }//if

      $_REQUEST[$param_name] = preg_replace('/\s+/', ' ', $_REQUEST[$param_name]);
			
      if (preg_match($pattern, $_REQUEST[$param_name]) == 0) {
         
         \xd_response\presentError("invalid value specified for $param_name");
         
      }//if
                 
      return $_REQUEST[$param_name];
      
   }//assertParameterSet
   	
?>
