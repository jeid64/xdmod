<?php
// --------------------------------------------------------------------------------
// @author Steve Gallo
// @date 2011-Jan-07
//
// Singleton database abstraction layer to provide machinery to query the
// configuration file or use parameters to obtain database engine and connection
// information.
// --------------------------------------------------------------------------------

namespace CCR;

use xd_utilities;

class DB
{

  // An array (pool) of database connection handles
  
  private static $instancePool = array();

  // Ensure that this class is a singleton

  private function __construct() {}

  // ================================================================================
  // Cleanup
  // ================================================================================

  public function __destruct() {}

  // ================================================================================
  // Create an instance of the database singleton.  A single argument is
  // required, which is configuration file section identifier (e.g. [datawarehouse]).
  // The database connection parameters in that section will be used to create the
  // instance, which will be cached for re-use by subsequent requests targeting the
  // same section.
  //
  // @throws Exception if there is an invalid number of arguments
  //
  // @returns An instance of the database class
  // ================================================================================
  
  public static function factory( $section, $autoConnect = true)
  {
  
    $numArgs = func_num_args();

	if($numArgs < 1)
	{
		 throw new Exception("Invalid number of arguments");
	}
	
    $engine = NULL;
    $host = NULL;
    $database = NULL;
    $user = NULL;
    $passwd = NULL;
    $port = NULL;


      
	// If this section has been used before in creating a database instance (handle), then
	// it will have been cached.  In this case, the cached handle will be returned.
	
	if (array_key_exists($section, self::$instancePool)){
		return self::$instancePool[$section];
	}
	
	$engine =   xd_utilities\getConfiguration($section, 'db_engine');
	$host =     xd_utilities\getConfiguration($section, 'host');
	$database = xd_utilities\getConfiguration($section, 'database');
	$user =     xd_utilities\getConfiguration($section, 'user');
	$passwd =   xd_utilities\getConfiguration($section, 'pass');
	$port =     xd_utilities\getConfiguration($section, 'port');

    $engine = "CCR\\DB\\$engine";
	
	self::$instancePool[$section] = new $engine($host, $port, $database, $user, $passwd);
	if($autoConnect) self::$instancePool[$section]->connect();
    


    if ( ! class_exists($engine) )
    {
      $msg = "Unknown database engine: '$engine'";
      throw new Exception($msg);
    }
    
   return self::$instancePool[$section];
  
  }  // factory()

}  // class DB


