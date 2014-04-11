<?php

    namespace Common;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This is the parent class for anyone who has a name attribute.
* 
*/

class Identity
{
    private $_name;	
	//no default value for name
    public function __construct($name)
    {
        $this->setName($name);
    }
    public function getName()
    {
        return $this->_name;
    }
    public function setName($name)
    {
        $this->_name = $name;
    }
    public function __toString()
    { 
        return $this->getName();
    }

}

?>