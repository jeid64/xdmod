<?php

   try {
   
      $user = \xd_security\getLoggedInUser();
   	
      $rm = new XDReportManager($user);
      
      header('content-type: image/png');
      
      $idElements = array();
      
      foreach ($_GET as $k => $v) {
      
         $idElements[] = "$k=$v";
         
      }
      
      $data = $rm->getChartFromID(implode('&', $idElements));
      
      echo $data;
   
   }
	catch (Exception $e) {

      header('content-type: text/html');
      print $e->getMessage();
	    
	}
   
?>