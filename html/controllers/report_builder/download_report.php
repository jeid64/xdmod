<?php
  
   \xd_security\assertParametersSet(array(
      'report_loc',
      'format'
   ));
   
   try { 
   
      if (XDReportManager::isValidFormat($_GET['format']) == false) {
         print "Invalid format specified";
         exit;
      }
      
      $output_format = $_GET['format'];
      
      $user = \xd_security\getLoggedInUser();
      
      $rm = new XDReportManager($user);
      
      // --------------------------------------------
      
      // Resolve absolute path to report document on backend
      
      $report_id = preg_replace('/(.+)-(.+)-(.+)/', '$1-$2', $_GET['report_loc']);
      
      $working_directory = '/tmp/'.$_GET['report_loc'];
      
      $report_file = $working_directory.'/'.$report_id.'.'.$output_format;
           
      // --------------------------------------------   
         
      if (!file_exists($report_file)) {
         print "The report you are referring to does not exist.";
         exit;
      }
      
      // --------------------------------------------
   
      // Build filename for attachment
      
      $report_name = $rm->getReportName($report_id, true).'.'.$output_format;
      
      // --------------------------------------------
            
      header("Content-type: ".XDReportManager::resolveContentType($output_format));
      
      header("Content-Disposition:inline;filename=\"$report_name\"");
   
      readfile($report_file);
      
      // Cleanup old temp working directories (over a day old) ========
      exec("find /tmp -type d -mtime +1", $o);
    
      foreach ($o as $e) {
   
         if (
               (preg_match('/^\/tmp\/\d{2}-\d{10}/', $e) == 1) || 
               (preg_match('/^\/tmp\/monthly_compliance_report(.+)$/', $e) == 1) 
            ) {
            exec("rm -rf $e");
         }
   
      }//foreach
   
   }
	catch (Exception $e) {

	    \xd_response\presentError($e->getMessage());
	    
	}
   
?>