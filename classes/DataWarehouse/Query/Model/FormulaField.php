<?php

namespace DataWarehouse\Query\Model;


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for a query field that requires calculation via an expression or formula
* 
*/

class FormulaField extends Field
{
	public function __construct($formula, $aliasname)
	{
		parent::__construct($formula, $aliasname);
	}
}

?>
