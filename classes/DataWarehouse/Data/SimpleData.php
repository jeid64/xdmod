<?php
namespace DataWarehouse\Data;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class represents one data column. 
* 
*/

class SimpleData extends \Common\Identity
{	
	public $values = array();
	public $errors = array();
	public $order_ids = array(); //only available in case the data is a dimension and not a stat
	public $ids = array(); //only available in case the data is a dimension and not a stat
	public function __construct($name)
	{
		parent::__construct($name);
	}
	
	public function getCount($force_recount = false)
	{
		if(!isset($this->valuesCount) || $force_recount===true)
		{
			$this->valuesCount = count($this->values);
		}
		
		return $this->valuesCount;
	}	
	public function getErrorCount($force_recount = false)
	{
		if(!isset($this->errorsCount) || $force_recount===true)
		{
			$this->errorsCount = count($this->errors);
		}
		
		return $this->errorsCount;
	}	
	public function makeUnique()
	{
		//not implemented, duh. 
	}
	
	public function getMinMax()
	{
		if($this->getCount() > 0)
		{
			$max = 0;
			$min = 100000;
		}
		else
		{
			$max = 100000;
			$min = 0;
		}
		foreach($this->values as $value)
		{
			if($value != NoValue)
			{
				$min = min($min,$value);
				$max = max($max,$value);
			}
		}
		return array($min, $max);
	}
}

?>