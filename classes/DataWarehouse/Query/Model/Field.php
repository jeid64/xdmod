<?php

namespace DataWarehouse\Query\Model;


/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for a query field.
* 
*/

class Field
{
	protected $_def;
	protected $_alias;
		
	public function __construct($field_def, $aliasname = '')
	{
		$this->_def = $field_def;
		$this->_alias = new \DataWarehouse\Query\Model\Alias($aliasname);
	}
	public function getDefinition()
	{
		return $this->_def;
	}	
	public function getAlias()
	{
		return $this->_alias;
	}
	public function getQualifiedName($show_alias = false)
	{
		$ret = $this->_def;

		if($show_alias == true && $this->getAlias() != '')
		{
			$ret .= ' as '.$this->getAlias();
		}
		return $ret;
	}
	public function __toString()
	{
		return $this->_def;
	}
}

?>