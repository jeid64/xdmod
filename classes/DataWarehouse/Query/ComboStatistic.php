<?php
    namespace DataWarehouse\Query;

/* 
* @author Amin Ghadersohi
* @date 2011-Aug-17
*
* 
*/
class ComboStatistic
{
	private $_label = NULL;
	private $_statistics = array();
	
	public function __construct($label)
	{
		$this->_label = $label;
	}
	public function getLabel()
	{
		return $this->_label;
	}
	
	public function setLabel($label)
	{
		$this->_label = $label;
	}
	protected function addStatistic($stat)
	{
		$this->_statistics[] = $stat;
	}
	
}

?>