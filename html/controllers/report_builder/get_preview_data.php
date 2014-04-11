<?php

   try {
   
      $user = \xd_security\getLoggedInUser();
      
      $rm = new XDReportManager($user);
   		
      \xd_security\assertParametersSet(array(
         'report_id',
         'token',
         'charts_per_page'
      ));

      $data = $rm->getPreviewData($_POST['report_id'], $_POST['token'], $_POST['charts_per_page']);
      
      $returnData = array();
      
      $returnData['report_id'] = $_POST['report_id'];
      $returnData['success'] = true;
      $returnData['charts'] = $data;
      
      \xd_controller\returnJSON($returnData);
   
   }
	catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
	}

?>