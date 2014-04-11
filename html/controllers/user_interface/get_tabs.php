<?php

	// Operation: user_interface->get_tabs
	
	$returnData = array();
	try
	{

      $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));
		
		$tabs = array();

		$roles = $user->getRoles();

		foreach($roles as $role_abbrev)
		{				
		
		   if ($role_abbrev == 'dev') continue;
		   
			$role = \User\aRole::factory($user->_getFormalRoleName($role_abbrev));
			$modules = $role->getPermittedModules();
			
			
			foreach($modules as $module)
			{
				if(!isset($tabs[$module->getName()]))
				{
					if($module->getName() === 'dashboard')
					{
						if($user->getUsername() == 'aming') $tabs[$module->getName()] = array('tab' => $module->getName(), 'isDefault' => $module->isDefault(), 'title' => $module->getTitle());
					}
					else
					{
						$tabs[$module->getName()] = array('tab' => $module->getName(), 'isDefault' => $module->isDefault(), 'title' => $module->getTitle());
					}
				}
			}
		}
		$returnData = 
			array('totalCount' => 1, 
				  'message' =>'', 
				  'data' => array(array('tabs' => json_encode(array_values($tabs)))),
				  'success' => true);
				  
		
	}catch(Exception $ex)
	{
		$returnData = 
			array('totalCount' => 0, 
				  'message' => $ex->getMessage(), 
				  'data' => array(),
				  'success' => false);
	}
	
	xd_controller\returnJSON($returnData);

?>