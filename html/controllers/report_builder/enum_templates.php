<?php

   try {
   
      $user = \xd_security\getLoggedInUser();
   
      $templates = XDReportManager::enumerateReportTemplates($user->getRoles());
      
      $returnData['status'] = 'success';
      $returnData['success'] = true;
      $returnData['templates'] = $templates;
      $returnData['count'] = count($templates);
   
   	\xd_controller\returnJSON($returnData);

   }
	catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
	}
	
?>