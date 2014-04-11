<?php

	namespace User\Elements;
	

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class represents the information needed to describe a module in the portal for 
* presentation to the user.
* 
*/	
	
class Module extends \Common\Identity
{
	private $_is_default = false;
	
	private $_title;
	
	public function __construct($name, $is_default, $title = '')
	{
		parent::__construct($name);
		$this->_is_default = $is_default;
		$this->_title = $title;
	}
	
	public function isDefault()
	{
		return $this->_is_default;
	}
	public function getTitle()
	{
		return $this->_title;
	}
}

?>