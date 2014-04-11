<?php

   class ZendMailWrapper {
   
      public static function init() {
      
         //ini_set('include_path', dirname(__FILE__).'/../external_libraries');
      
         require_once ('Zend/Mail/Transport/Sendmail.php');
         require_once ('Zend/Mail.php');
      
         $tr = new Zend_Mail_Transport_Sendmail('-fxdmod-bounces@ccr.buffalo.edu');
         Zend_Mail::setDefaultTransport($tr);

         return new Zend_Mail();
      
      }//init
   
   }//ZendMailWrapper
   
?>