<?php

   require_once __DIR__.'/../configuration/linker.php';
   
   // With the '-m' argument passed into this script, reports only associated with the username
   // set in $maint_user will be built and sent

   $maint_mode = (isset($argv[1]) && $argv[1] == '-m');
   $maint_user = 'rgentner';

   /* The report schedule manager will run every night, at midnight (12:00 AM) */
   
   // Upon invocation of this script, the following logic will be exercised:
   
   // Daily -- always will be invoked
   
   // Weekly -- determine if the current day of the week is Sunday
   
   // Monthly -- determine if it is the first of the month
   
   // Quarterly -- determine if it is the first of the month AND if the month is
   //              one of the following: January, April, July, October

   
   // Semi-annually -- determine if it is the first of the month AND the month is:
   //       January or July    
   
   // Annually -- determine if it is the first of January

   // Ultimately, the logic above will be used to determine which types of reports are to be generated upon
   // invocation of this script.
 
   // =========================================================================================================   

   // We are using Pear::Log http://pear.php.net/package/Log
   require_once("Log.php");

   define('CONFIG_LOGGER', 'logger');

   $dbhost = xd_utilities\getConfiguration(CONFIG_LOGGER, 'host');
   $dbport = xd_utilities\getConfiguration(CONFIG_LOGGER, 'port');
   $dbuser = xd_utilities\getConfiguration(CONFIG_LOGGER, 'user');
   $dbpasswd = xd_utilities\getConfiguration(CONFIG_LOGGER, 'pass');
   $dbname = xd_utilities\getConfiguration(CONFIG_LOGGER, 'database');

   $conf = array('dsn' => "mysql://$dbuser:$dbpasswd@$dbhost:$dbport/$dbname");
   $dbLogger = Log::factory('xdmdb2', 'log_table', 'ReportScheduler', $conf);

   $conf = array('lineFormat' => "%{timestamp} -- %{message}");
   $consoleLogger = Log::factory('xdconsole', '', 'ReportScheduler', $conf);

   $logger = Log::factory('composite');
   $logger->addChild($dbLogger);
   $logger->addChild($consoleLogger);
   
   // =========================================================================================================   

   // NOTE: "process_start_time" is needed for log summary.
   $logger->notice(array('message' => 'Report scheduler start', 'process_start_time' => date('Y-m-d H:i:s')));

   $active_frequencies = getActiveFrequencies(true);
   
   foreach ($active_frequencies as $frequency) {
   
      $report_details = XDReportManager::enumScheduledReports($frequency);
      
      $suffix = (count($report_details) == 0) ? 'None' : count($report_details);
      
      $logger->log("Reports Scheduled for $frequency Delivery: ".$suffix);
      
      foreach ($report_details as $details) {
      
         $user = XDUser::getUserByID($details['user_id']);
      
         if ($maint_mode == true && $user->getUsername() == $maint_user || $maint_mode == false) {
         
            $logger->log("Preparing report ".$details['report_id']." ({$user->getUsername()})\n");
         
            $rm = new XDReportManager($user);
            
            $xml_definition = $rm->generateXMLConfiguration(false, $details['report_id']);
            
            try {
            
               $build_response = $rm->buildReport($details['report_id'], $xml_definition);
         
               $working_dir = $build_response['template_path'];
               $report_filename = $build_response['report_file'];
                  
               //print "Working Dir: $working_dir\n";
               //print "Report Filename: $report_filename\n";    
            
               $mailStatus = $rm->mailReport($details['report_id'], $report_filename, $frequency, $build_response);
         
            }
            catch(\Exception $e) {
            
               $logger->err(array('message' => "Error Preparing report ".$details['report_id'].": ".$e->getMessage(),
           		       'stacktrace' => $e->getTraceAsString()));
           		 
            }
            
            if (isset($working_dir) == true) {
               exec("rm -rf $working_dir");
            }

         }//if ($maint_mode...)
            
      }//foreach ($report_details as $details)
   
   }//foreach ($active_frequencies as $frequency)
   
   // NOTE: "process_end_time" is needed for log summary.
   $logger->notice(array('message' => 'Report scheduler end', 'process_end_time' => date('Y-m-d H:i:s')));

   // =========================================================================================================   
    
   function getActiveFrequencies ($verbose = false) {
   
      $activeFrequencies = array();

      // date (l) -- A full textual representation of the day of the week ('Sunday' through 'Saturday')      
      // date (w) -- Numeric representation of the day of the week (0 (for Sunday) through 6 (for Saturday))
      // date (n) -- Numeric representation of a month, without leading zeros (1 through 12)
      // date (j) -- Day of the month without leading zeros (1 to 31)            
      // date (Y) -- A full numeric representation of a year, 4 digits (Examples: 1999 or 2003)
      
      $time = date('l w n j Y');
      
      list($formal_day_of_week, $day_of_week, $month_index, $day_of_month, $year) = explode(' ', $time);
      
      if ($verbose == true) {
      
         print "Current Time ---------------------------- \n";
         print "Year:         $year\n";
         print "Month:        $month_index\n";
         print "Day Of Month: $day_of_month\n";
         print "Day Of Week:  $day_of_week ($formal_day_of_week)\n";
         
         print "\n\n";
      
      }//if ($verbose == true)
      
      // =================================================================
      
      // Daily (always active)
      
      $activeFrequencies[] = 'Daily';
       
      // =================================================================
      
      // Weekly
         
      if ($day_of_week == 0) {    // 0 = Sunday
      
         $activeFrequencies[] = 'Weekly';
         
      }//if ($day_of_week == 0)
   
      // =================================================================
      
      // Monthly
   
      if ($day_of_month == 1) {   // First of the month
      
         $activeFrequencies[] = 'Monthly';
         
      }//if ($day_of_month == 1)
         
      // =================================================================
      
      // Quarterly
   
      $quarter_start_months = array(1,4,7,10);   // (1 = January, 4 = April, 7 = July, 10 = October)
      
      if ($day_of_month == 1 && in_array($month_index, $quarter_start_months)) {  // First of the month and the month denotes the start of a quarter

         $activeFrequencies[] = 'Quarterly';
            
      }//if ($day_of_month == 1 && in_array($month, $quarter_start_months))

      // =================================================================

      // Semi-annually
   
      $semi_annual_start_months = array(1,7);   // (1 = January, 7 = July)
      
      if ($day_of_month == 1 && in_array($month_index, $semi_annual_start_months)) {  // First of the month and the month denotes the start of a new 6-month block

         $activeFrequencies[] = 'Semi-annual';
         
      }//if ($day_of_month == 1 && in_array($month_index, $semi_annual_start_months))

      // =================================================================

      // Annually
   
      if ($month_index == 1 && $day_of_month == 1) {  // January 1st

         $activeFrequencies[] = 'Annual';
         
      }//if ($month_index == 1 && $day_of_month == 1)

      // =================================================================
                        
      return $activeFrequencies;
   
   }//getActiveFrequencies
       
?>
