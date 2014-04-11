<?php

require_once dirname(__FILE__).'/../configuration/linker.php';

use CCR\DB;

/*
 * @Class XDSessionManager
 *
 * Abstracts access to the following schema:
 *
 * TABLE SessionManager (
 *	   session_token VARCHAR(40) NOT NULL PRIMARY KEY,
 *	   session_id TEXT NOT NULL,
 *	   user_id INT( 11 ) UNSIGNED NOT NULL ,
 *	   ip_address VARCHAR( 40 ) NOT NULL ,
 *	   user_agent VARCHAR( 255 ) NOT NULL ,
 *	   init_time VARCHAR( 100 ) NOT NULL, 
 *	   last_active VARCHAR( 100 ) NOT NULL, 
 *	   used_logout TINYINT(1) UNSIGNED
 *	);
 *	
 */

class XDSessionManager {

   public static function recordLogin($user) {

      @session_start();

      $pdo = DB::factory('database');

      // Retrieve the exact time in which the login occurred.  This timestamp is then be assigned to a session variable
      // which will be consulted any time a token needs to be mapped back to an actual XDMoD Portal user (see resolveUserFromToken(...)).
      
      $init_time = self::getMicrotime();
         
      $session_id = session_id();
      $user_id = $user->getUserID();
         
      $session_token = md5($user_id.$session_id.$init_time);
         
      $ip_address = $_SERVER['REMOTE_ADDR'];
      $user_agent = $_SERVER['HTTP_USER_AGENT'];  
         
      $record_query = "INSERT INTO SessionManager (session_token, session_id, user_id, ip_address, user_agent, init_time, last_active, used_logout) " . 
                      "VALUES ('$session_token', '$session_id', '$user_id', '$ip_address', '$user_agent', '$init_time', '$init_time', 0)";
						
      $pdo->execute($record_query);
			
      $_SESSION['xdInit'] = $init_time;
		$_SESSION['xdUser'] = $user_id;
   
		$_SESSION['session_token'] = $session_token;
			
		return $session_token;
			
	}//recordLogin
		
   // -------------------------------------------------------

   // resolveUserFromToken:
   // If the second argument to this function ($user_id_only) is set to true, then 
   // resolveUserFromToken(...) will return the numerical XDUser ID as opposed
   // to an XDUser object instance.
   
   public static function resolveUserFromToken($restRequest, $user_id_only = false) {
				
      $token = $restRequest->getToken();
      $ip_address = $restRequest->getIPAddress();

      // ============================================
      
      // Use of the test token requires that the caller reside on the UB network (128.205....)
      
      if ($token == TEST_TOKEN && \xd_network\addressBelongsToNetwork($ip_address, '128.205.0.0/16')) {
            
         if ($user_id_only) {
            return TEST_TOKEN_MAPPED_USER;
         }
         else {
            return XDUser::getUserByID(TEST_TOKEN_MAPPED_USER);
         }
         
      }//if ($token == TEST_TOKEN)
      
      // ============================================
      
      $resolution_mechanism = EXCLUSIVE;
      
      switch($ip_address) {
      
         case "127.0.0.1":
         
            // Requests to authenticated REST calls are originating from localhost.  
            // RULE:  If the originator of the call is internal (e.g. coming from localhost), the expected token will be 
            //        defined in moddb.Users (and NOT one issued by XDSessionManager)
            
            $user = XDUser::getUserByToken($token);
            
            if ($resolution_mechanism == EXCLUSIVE) {
            
               if ($user == NULL) {
                  throw new \Exception('Invalid token specified');
               }
            
               return $user;
            
               break;
            
            }
            
            if ($resolution_mechanism == FAILOVER) {
            
               if ($user != NULL) {
                  return $user;
               }
               
               // If execution reaches this point, internal token resolution has failed, and the 'default' case
               // (below) will execute
               
            }
            
         default:
         
            @session_start();
                            
            if (!isset($_SESSION['xdInit'])){
               // Session died (token no longer valid);
               throw new \Exception('Token invalid or expired.  You must authenticate before using this call.');
            }
            
            $session_id = session_id();
            
            /*
            $resolver_query = "SELECT user_id FROM SessionManager " . 
                              "WHERE session_token='$token' AND session_id='$session_id' AND ip_address='$ip_address' AND init_time='{$_SESSION['xdInit']}'";
            */

            // Without IP restriction ... relaxed, especially for very mobile users (in which network hopping is frequent)
            
            $resolver_query = "SELECT user_id FROM SessionManager " . 
                              "WHERE session_token='$token' AND session_id='$session_id' AND init_time='{$_SESSION['xdInit']}'";
                               
            // ----------------------------------------------
                 							
            $access_logfile = LOG_DIR . '/session_manager.log';
  
            $logConf = array('mode' => 0644);
            $sessionManagerLogger = Log::factory('file', $access_logfile, 'SESSION_MANAGER', $logConf);

            $sessionManagerLogger->log($_SERVER['REMOTE_ADDR'].' QUERY '.$resolver_query);

            // ----------------------------------------------

            $pdo = DB::factory('database');
             
            $user_check = $pdo->query($resolver_query);
                 
            if (count($user_check) > 0) {	
      	
               $last_active_time = self::getMicrotime();
               
               $last_active_query = "UPDATE SessionManager SET last_active = '$last_active_time' " . 
                                    "WHERE session_token='$token' AND session_id='$session_id' AND ip_address='$ip_address' AND init_time='{$_SESSION['xdInit']}'";
      							
               $pdo->execute($last_active_query);
               
               if ($user_id_only)
                  return $user_check[0]['user_id'];
               
               $user = XDUser::getUserByID($user_check[0]['user_id']);
               
               if ($user == NULL) {
                  throw new \Exception('Invalid token specified');
               }
               
               return $user;
      
            }
            else {
               // An error occurred (session is intact, yet a corresponding record pertaining to that session does not exist in the DB)
               throw new \Exception('Invalid token specified');
            }
            
            break;
            
      }//switch($ip_address)     
               
   }//resolveUserFromToken
   
   // -------------------------------------------------------
		
   public static function logoutUser($token = "") {
		
      @session_start();
      
      // If a session is still active and a token has been specified, attempt to record the logout in the SessionManager table 
      // (provided the supplied token is still 'valid' and a corresponding record in SessionManager can be found)
      
      if (isset($_SESSION['xdInit']) && !empty($token)){

         $session_id = session_id();
         $ip_address = $_SERVER['REMOTE_ADDR'];
      
         $logout_query =   "UPDATE SessionManager SET used_logout = 1 " . 
                           "WHERE session_token='$token' AND session_id='$session_id' AND ip_address='$ip_address' AND init_time='{$_SESSION['xdInit']}'";
							
         $pdo = DB::factory('database'); 
         $pdo->execute($logout_query);

      }//if
      
      // Drop the session so that any REST calls requiring authentication (via tokens) trip the first Exception as the result of invoking
      // resolveUserFromToken($token)
      session_destroy();
      
   }//logoutUser  

   // -------------------------------------------------------
      
   private static function getMicrotime() {

      list($usec, $sec) = explode(' ', microtime());
      return $usec + $sec;
    
   }//getMicrotime
   
}//XDSessionManager
   
?>
