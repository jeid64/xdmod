<?php
/* 
* @author Amin Ghadersohi
* @date 2010-Jul-07
*
* The top interface for mysql dbs using pdo driver
* 
*/
namespace CCR\DB;

class MySQLDB extends PDODB
{
	function __construct($db_host,$db_port,$db_name,$db_username,$db_password)
	{
		parent::__construct("mysql",$db_host,$db_port,$db_name,$db_username,$db_password);
	}
	function __destruct()
	{
		parent::__destruct();
    }

}
?>
