<?php
 
   try {
   
      $user = \xd_security\getLoggedInUser();
   
      $rm = new XDReportManager($user);
   
      $report_id_array = explode (";", $_POST['selected_report']);
      
      foreach ( $report_id_array as $report_id ) {
         $rm->removeReportCharts($report_id);
         $rm->removeReportbyID($report_id);
      }
   
      $returnData['action'] = 'remove_report_by_id';
      $returnData['success'] = true;
   
      \xd_controller\returnJSON($returnData);

   }
	catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
	}

?>