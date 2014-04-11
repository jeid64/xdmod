<?php

/*
 * @class Aggregator
 * The parent class for all aggregator classes
 *
 * @author: Amin Ghadersohi 8/1/2013
 *
 */
 
class Aggregator extends Loggable
{
	public function __construct()
	{
		$this->targetTable = "modw_taccstats.taccstatsjobs";
	}
	
	/*
	* Replaces all the occurrences of :<key> in $statement with key/value pairs in $params = array('<expression>' => 'value',...);
	*/
	protected function bindParams($statement, $params)
	{
		foreach($params as $param_key => $param_value)
		{
			$statement = str_replace(':'.$param_key,$param_value,$statement);//todo: use regex such that prefixes of param names dont get replaced.
		}
		return $statement;
	}
	
	/*
	* Writes the query, after binding the value of all bound params, to outfile.
	*/	
	protected function dumpQuery($outfile, $select_statement, $select_params)
	{
		file_put_contents($outfile, $this->bindParams($select_statement,$select_params));
	}
	
	/*
	* Returns a sql case statement distributes a stat that was recorded between $s1 and $e1 
	* linearly between $s2 and $e2 where duration between $s2 and $e2 is $max because $max is not always $e2 - $s2
	*/
	protected function getDistributionSQLCaseStatement($stat, $max, $s1, $e1, $s2, $e2)
	{
		return "case when ($s1 between $s2 and $e2 and
					   $e1 between $s2 and $e2 ) 
						  then $stat
				 when ($s1 < $s2 and
					   $e1 between $s2 and $e2 )
					   then $stat*($e1 - $s2 ) / ($e1 - $s1)
				 when ($s1 between $s2 and $e2 and
					   $e1 > $e2 )
					   then	 $stat*( $e2 - $s1) / ($e1 - $s1)
				 when ($s1 < $s2 and
					   $e1 > $e2 )
					   then	$stat*( $max ) / ($e1 - $s1)
				 else $stat
				end";
	}
	
	/*
	* Returns a SQL case statement given condition/then/else as strings
	*/
	protected function getIf($condition, $then, $else)
	{
		return "case when $condition 
				   then $then
				 else $else 
			 	end";
	}
} //Aggregator

?>
