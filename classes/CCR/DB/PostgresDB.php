<?php
/* 
* @author Amin Ghadersohi
* @date 2010-Jul-07
*
* The top interface for postgres dbs using pdo driver
* 
*/
namespace CCR\DB;

class PostgresDB extends PDODB
{
	function __construct($db_host,$db_port,$db_name,$db_username,$db_password)
	{
		parent::__construct("pgsql",$db_host,$db_port,$db_name,$db_username,$db_password);
	}
	function __destruct()
	{
		parent::__destruct();
    }
}
?>
