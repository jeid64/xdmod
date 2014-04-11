<?php

   require_once dirname(__FILE__).'/../../../configuration/linker.php';

   use CCR\DB;
   
   @session_start();
   xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), 'xdDashboardUser');
   
   // =====================================================

   $pdo = DB::factory('database');
         
   $operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '';   
   
   $response = array();

   // =====================================================

   switch($operation) {
   
      case 'enum_presets': 

         $response['presets'] = array();
         
         if ($handle = opendir(dirname(__FILE__).'/../mail_templates')) {

            while (false !== ($entry = readdir($handle)))
            {
               if (substr($entry, -4) == '.txt')
                  $response['presets'][] = substr($entry, 0, -4);
            }
      
            closedir($handle);
            
            $response['success'] = true;
            $response['count'] = count($response['presets']);
            
         }
         else {
         
            $response['success'] = false;
            $response['message'] = 'Unable to get a listing of preset messages';
            
         }

         break;

      case 'fetch_preset_message': 
 
         $preset = \xd_security\assertParameterSet('preset');
         
         $targetFile = dirname(__FILE__).'/../mail_templates/' . $preset . '.txt';
         
         if (file_exists($targetFile)) {

            $response['success'] = true;
            $response['content'] = file_get_contents($targetFile);
            
         }
         else {
         
            $response['success'] = false;
            $response['message'] = "Unable to load preset '$preset'";
            
         }

         break;
                  
      case 'enum_target_addresses':

         $group_filter = \xd_security\assertParameterSet('group_filter');
         $role_filter = \xd_security\assertParameterSet('role_filter');
         
         $query = \xd_dashboard\deriveUserEnumerationQuery($group_filter, $role_filter, '', true);  
         
         $results = $pdo->query($query);
         
         $addresses = array();
         
         foreach ($results as $r) {
            $addresses[] = $r['email_address'];
         }
         
         $addresses = array_unique($addresses);

         sort($addresses);
         
         $response['success'] = true;
         $response['count'] = count($addresses);
         $response['response'] = $addresses;
         
         break;
      
      case 'send_plain_mail':

         $target_addresses = \xd_security\assertParameterSet('target_addresses');    
         $message = \xd_security\assertParameterSet('message');
         $subject = \xd_security\assertParameterSet('subject');  
         
         $response['success'] = true;

         $mail = ZendMailWrapper::init();
	
      	$mail->setSubject("[XDMoD] ".$subject);
      	
      	$mail->addTo('ccr-xdmod-help@buffalo.edu', 'Undisclosed Recipients');
      	$mail->setFrom('ccr-xdmod-help@buffalo.edu', 'XDMoD');
      	//$mail->setReplyTo('ccr-xdmod-help@buffalo.edu', 'XDMoD');
      	
      	$bcc_emails = explode(',', $target_addresses);
      	
         foreach ($bcc_emails as $b) {
            $mail->addBcc($b);
         } 
    	
      	$mail->setBodyText($message);
      	
         $response['status'] = $mail->send();

         break;
                            
      default:
         
         $response['success'] = false;
         $response['message'] = 'operation not recognized';
         
         break;      

   }//switch
   
   // =====================================================

   print json_encode($response);
   
?>
