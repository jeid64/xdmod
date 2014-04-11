<html>
   
   <head>
   
      <style type="text/css">
                  
         body {
         
            margin: 4px;
            font-family: arial;
            font-size: 11px;
            background-color: #e8e8e8;
            
         }
      
      </style>
      
   </head>
   
   <body>

<?php

   if (isset($_REQUEST['first_load'])) {
      print "</body></html>";
      exit;
   }
   
   if (!isset($_REQUEST['username'])) {
      print "A username is required";
      exit;
   }
   
   @require_once dirname(__FILE__).'/../../configuration/linker.php';


   $username = trim($_REQUEST['username']);
   
   if (empty($username)) {
      print "<span style='color: #f00'>A username is required</span>";
      exit;   
   }
   
   $status = getXSEDEUserStatusByUsername($username);
   
   if ($status['valid_user'] == false) {
   
      //print "<span style='color: #f00'><b>$username</b> is not a valid XSEDE user</span>";
      print "<span style='color: #f00'><b>$username</b> is not associated with any active allocations</span>";
      exit;
      
   }
   
   if ($status['has_active_allocations'] == false) {
   
      //print "<span style='color: #f00'><b>$username</b> is a valid XSEDE user, yet <u>is not</u> associated with any active allocations</span>";
      print "<span style='color: #f00'><b>$username</b> is not associated with any active allocations</span>";
      exit;
      
   }
   
   //print "<span style='color: #080'><b>$username</b> is a valid XSEDE user and is associated with an active allocation</span>";
   print "<span style='color: #080'><b>$username</b> is associated with an active allocation</span>";   
   
   //\xd_debug\dumpArray($status);
   
   // ===============================================================
   
   function getXSEDEUserStatusByUsername($username) {
   
      $results = array('username' => $username);
   
      try {
         
         $person_id = XDUser::resolvePersonIDFromXSEDEUsername($username);
         $results['valid_user'] = true;
         
      }
      catch(Exception $e) {

         $results['valid_user'] = false;
         return $results;
                     
      }
      
      $allocations = DataWarehouse::getAllocationsByChargeNumber($person_id, date('Y-m-d'), date('Y-m-d'), false);
      
      $results['has_active_allocations'] = false;
      
      foreach ($allocations as $a) {
      
         if ($a['status'] == 'active') {
            $results['has_active_allocations'] = true;
            break;
         }
      
      }

      return $results;

   }//getXSEDEUserStatusByUsername

?>

   </body>
   
</html>