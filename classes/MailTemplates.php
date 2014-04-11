<?php

   /*
    * @Class MailTemplates
    *
    *
    */

   class MailTemplates {

      public static function passwordReset($user) {
      
         $site_address = xd_utilities\getConfiguration('general', 'site_address');
      
         // ====================
         
         $message  = "Dear ".$user->getFirstName().",\n\n";
      
         $message .= "Your username is: ".$user->getUsername()."\n\n";
      
         $message .= "To reset your password, please navigate to the following link:\n\n";
         $message .= "$site_address/password_reset.php?rid=".md5($user->getUsername().$user->getPasswordLastUpdatedTimestamp())."\n\n";
         $message .= "(Please note that once you update your password, the above link will no longer be valid)\n\n";
   
         $message .= "Sincerely,\n";
         
         $message .= "The XDMoD Team";
         
         return $message;
   
      }//passwordReset
      
      // ---------------------------------------------------
      
      // @function customReport:
      // Gets used by reports built and sent via the Report Generator, as well as those reports built and sent via
      // the report scheduler
      
      public static function customReport($recipient_name, $frequency = '') {
         
         $message  = "Dear $recipient_name,\n\n";
      
         $frequency = trim($frequency);
         $frequency = (!empty($frequency)) ? ' '.$frequency : $frequency;
         
         $message .= "Attached is the$frequency XDMoD TAS report you requested.\n\n";
   
         $message .= "Thank you,\n";
         $message .= "The TAS Project Team\n";
         $message .= "Center for Computational Research\n";
         $message .= "University at Buffalo, SUNY"; 
         
         return array(
            'subject' => 'XDMoD Report',
            'message' => $message
         );
   
      }//customReport

      // ---------------------------------------------------
      
      // @function complianceReport:
      // Gets used by reports built and sent via XDComplianceReport
      
      public static function complianceReport($recipient_name, $additional_information = '') {
         
         $message  = "Dear $recipient_name,\n\n";
         
         //$message .= "Attached is the XSEDE monthly compliance report you requested.\n\n";
   
         $message .= $additional_information;
         
         $message .= "Thank you,\n";
         $message .= "The TAS Project Team\n";
         $message .= "Center for Computational Research\n";
         $message .= "University at Buffalo, SUNY"; 

         return array(
            'subject' => 'XDMoD Compliance Report',
            'message' => $message
         );
   
      }//complianceReport

   }//MailTemplates

?>