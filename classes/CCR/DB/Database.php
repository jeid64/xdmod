<?php
/* 
* @author Amin Ghadersohi
* @date 2010-Jul-07
*
* The top interface for all db classes 
* 
*/

namespace CCR\DB;

  /*
   * @Interface Database
   * The interface for database classes.
   */
interface Database
{

  /*
	* @function connect  (Establishes the connection to the database)
	* @access public
	*/
	
  public function connect();
  /*
	* @function destroy  (Releases the connection to the database)
	* @access public
	*/
	
  public function destroy();

  /*
   * @function insert
   * @access public
   *
   * @param string $statement (The SQL INSERT statement)
   * @param array $params (The optional parameters to the database, when needed)
   *
   * @return int (Returns the index of the recently inserted record)
   */
  public function insert($statement, $params=array());

  // --------------------------------------------------------------------------------
  // Perform a query and return an associative array of results.  This is the
  // recommended method for executing SELECT statements.
  //
  // @param $query The query string
  // @param $params An array of values with as many elements as there are bound
  //   parameters in the SQL statement being executed.
  //
  // @throws Exception if the query string is empty
  // @throws Exception if there was an error executing the query
  //
  // @returns An array containing the query results
  // --------------------------------------------------------------------------------

  public function query($query, array $params = array(), $returnStatement = false);

  // --------------------------------------------------------------------------------
  // Execute an SQL statement and return the number of rows affected.  This is
  // the recommended method for executing non-SELECT statements.
  //
  // @param $query The query string
  // @param $params An array of values with as many elements as there are bound
  //   parameters in the SQL statement being executed.
  //
  // @throws Exception if the query string is empty
  // @throws Exception if there was an error executing the query
  //
  // @returns The number of rows affected by the statement
  // --------------------------------------------------------------------------------

  public function execute($query, array $params = array());

}  // DBInterface

?>
