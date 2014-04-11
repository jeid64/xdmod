<?php
   
   try {

      $user = \xd_security\getLoggedInUser();

      $rm = new XDReportManager($user);

      $returnData = array();
      $returnData['dropped_entries'] = array();

      foreach ($_POST as $k => $v) {

         if (preg_match('/^selected_chart_/', $k) == 1) {
         
            $rm->removeChartFromChartPoolByID($v);
      
            preg_match('/controller_module=(.+?)&/', $v, $m);
            
            $module_id = $m[1];
            
            if (!isset($returnData['dropped_entries'][$module_id])) $returnData['dropped_entries'][$module_id] = array();
            $returnData['dropped_entries'][$module_id][] = $v;
              
         }

      }//foreach ($_POST as $k => $v)

      $returnData['success'] = true;
      $returnData['action'] = 'remove';

      \xd_controller\returnJSON($returnData);

	}
   catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
   }
