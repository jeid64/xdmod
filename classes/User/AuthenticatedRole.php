<?php

namespace User;


class AuthenticatedRole extends \User\aRole 
{
 
   // ------------------------------------

   // All classes which extend aRole will make a call to this constructor, passing in an identifier
   // (all of which are defined in configuration/constants.php)

   protected function __construct($identifier)
   {
		parent::__construct($identifier);
	
		
   }//__construct

 

}//AuthenticatedRole

?>