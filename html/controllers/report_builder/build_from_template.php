<?php

   \xd_security\assertParametersSet(array(
      'template_id',
      'resource_provider'
   ));
   
   try {
   
      $user = \xd_security\getLoggedInUser();

      $template = XDReportManager::retrieveReportTemplate($user, $_POST['template_id']);
            
      $template->buildReportFromTemplate($_REQUEST);
            
      $returnData['success'] = true;

   }
	catch (Exception $e) {

	    \xd_response\presentError($e->getMessage());
	    
	}

	\xd_controller\returnJSON($returnData);

?>
