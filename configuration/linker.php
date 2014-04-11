<?php

$dir = dirname(__FILE__);
$baseDir = dirname($dir);

require_once($dir . '/constants.php');

// ---------------------------

$include_path  = ini_get('include_path');
$include_path .= ":" . $baseDir . '/classes';
$include_path .= ":" . $baseDir . '/classes/DB';
$include_path .= ":" . $baseDir . '/classes/DB/TACCStatsIngestors';
$include_path .= ":" . $baseDir . '/classes/DB/TGcDBIngestors';
$include_path .= ":" . $baseDir . '/classes/DB/POPSIngestors';
$include_path .= ":" . $baseDir . '/classes/DB/Aggregators';
$include_path .= ":" . $baseDir . '/classes/DB/DBModel';
$include_path .= ":" . $baseDir . '/classes/ExtJS';
$include_path .= ":" . $baseDir . '/classes/REST';
$include_path .= ":" . $baseDir . '/classes/User';
$include_path .= ":" . $baseDir . '/classes/ReportTemplates';
$include_path .= ":" . $baseDir . '/classes/AppKernel';
$include_path .= ":" . $baseDir . '/external_libraries';
$include_path .= ":" . $baseDir . '/libraries/HighRoller_1.0.5';

ini_alter('include_path', $include_path);

function __autoload($className)
{
   $pathList = explode(":", ini_get('include_path'));

   // if class does not have a namespace
   if(strpos($className,'\\') === FALSE) {
      $includeFile = $className.".php";
      foreach ($pathList as $path) {
         if (is_readable("$path/$includeFile")) {
            require_once("$path/$includeFile");
            break;
         }
      }
   } else {
      // convert namespace to full file path
      $class = dirname(__FILE__) . '/../classes/'
         . str_replace('\\', '/', $className) . '.php';
      if (is_readable("$class")) {
         require_once($class);
      }
   }
} //__autoload

// Libraries ---------------------------

require_once($baseDir . '/libraries/utilities.php');

$libraries = scandir($baseDir . '/libraries');

foreach ($libraries as $library) {
   $file = "$baseDir/libraries/$library";
   if (is_dir($file)) {
      continue;
   }
   require_once($file);
}

// Global Exception Handler (Uncaught exceptions will be logged) ------------------------------

function global_uncaught_exception_handler($exception)
{
   $logfile = LOG_DIR . "/" . xd_utilities\getConfiguration('general', 'exceptions_logfile');

   $logConf = array('mode' => 0644);
   $logger = Log::factory('file', $logfile, 'exception', $logConf);

   $logger->log('Exception Code: '.$exception->getCode(), PEAR_LOG_ERR);
   $logger->log('Message: '.$exception->getMessage(), PEAR_LOG_ERR);
   $logger->log('Origin: '.$exception->getFile().' (line '.$exception->getLine().')', PEAR_LOG_INFO);

   $stringTrace = (get_class($exception) == 'UniqueException') ? $exception->getVerboseTrace() : $exception->getTraceAsString();

   $logger->log("Trace:\n".$stringTrace."\n-------------------------------------------------------", PEAR_LOG_INFO);

   print "\nUnhandled exception.  Check logs for more details.";

   exit;
} //global_uncaught_exception_handler

set_exception_handler('global_uncaught_exception_handler');

// Configurable constants ---------------------------

$config = Xdmod\Config::factory();

$org = $config['organization'];
define('ORGANIZATION_NAME',        $org['name']);
define('ORGANIZATION_NAME_ABBREV', $org['name']);

$hierarchy = $config['hierarchy'];
define('HIERARCHY_TOP_LEVEL_LABEL',    $hierarchy['top_level_label']);
define('HIERARCHY_TOP_LEVEL_INFO',     $hierarchy['top_level_info']);
define('HIERARCHY_MIDDLE_LEVEL_LABEL', $hierarchy['middle_level_label']);
define('HIERARCHY_MIDDLE_LEVEL_INFO',  $hierarchy['middle_level_info']);
define('HIERARCHY_BOTTOM_LEVEL_LABEL', $hierarchy['bottom_level_label']);
define('HIERARCHY_BOTTOM_LEVEL_INFO',  $hierarchy['bottom_level_info']);

