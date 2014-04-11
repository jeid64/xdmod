<?php

   require_once dirname(__FILE__).'/../../configuration/linker.php';
   
   @session_start();

   if (isset($_POST['xdmod_username']) && isset($_POST['xdmod_password'])) {
      
      $user = XDUser::authenticate($_POST['xdmod_username'], $_POST['xdmod_password']);
      
      if ($user == NULL) {
            
         denyWithMessage('Invalid login');
      
      }
      
      $_SESSION['xdDashboardUser'] = $user->getUserID();
      
   }
   
   // --------------------------------------
   
   if (!isset($_SESSION['xdDashboardUser'])){
      denyWithMessage('');
      exit;
   }

   // --------------------------------------
            
   try {
      $user = XDUser::getUserByID($_SESSION['xdDashboardUser']);
   }
   catch(Exception $e) {
      denyWithMessage('There was a problem initializing your account.');
      exit;
   }

   // --------------------------------------
      
   if (!isset($user)) {
      // There is an issue with the account (most likely deleted while the user was logged in, and the user refreshed the entire site)
      session_destroy();
      header("Location: splash.php");
      exit;
   }

   // --------------------------------------

   if ($user->isManager() == false) {
      denyWithMessage('You are not allowed access to this resource.');
      exit;
   }
   
   // --------------------------------------  
   
   function denyWithMessage($message) {
   
      $referer = isset($_POST['direct_to']) ? $_POST['direct_to'] : $_SERVER['SCRIPT_NAME'];
      $reject_response = $message;
   
      include 'splash.php';
      exit;
      
   }
   
?>