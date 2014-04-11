<?php

	require_once dirname(__FILE__).'/../configuration/linker.php';

	/*
	 * @Class XDController
	 * XDMoD Controller Class
	 */

	class XDController {
	
		private $_requirements;
		private $_registered_operations;
		private $_operation_handler_directory;
		private $_accept_internal_tokens;         // Whether the controller accepts internal tokens in lieu of a (stateful) session
		
		// ---------------------------
		
		function __construct($requirements = array(), $basePath = OPERATION_DEF_BASE_PATH) {
		
			$this->_requirements = $requirements;
			$this->_registered_operations = array();
			$this->_accept_internal_tokens = false;
			
			$this->_operation_handler_directory = $basePath.'/'.substr(basename($_SERVER["SCRIPT_NAME"]), 0, -4);
			
		}//construct
		
		// ---------------------------
		
		public function registerOperation($operation) {
		
			$this->_registered_operations[] = $operation;	
		
		}//registerOperation
		
		// ---------------------------
		
		public function acceptInternalTokens() {
		
			$this->_accept_internal_tokens = true;	
		
		}//registerOperation
		
		// ---------------------------

		public function invoke($method, $session_variable = 'xdUser') {
		
		   
		   if ($this->_accept_internal_tokens == true) {
		   
		    if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')) {
		    
		       if (preg_match('/&token=(.+)$/', $_SERVER['QUERY_STRING'], $m) == 1) {
		       
		          $internal_token = $m[1];
		          
		          $user = XDUser::getUserByToken($internal_token);
		          
		          if ($user != NULL) {
		          
		             @session_start();
		             $_SESSION['xdUser'] = $user->getUserID();
		             
		          }//if ($user != NULL)
		       
		       }//if

          }//if

		   }//if ($this->_accept_internal_tokens == true)
		   
		   
			xd_security\enforceUserRequirements($this->_requirements, $session_variable);
	
			// --------------------
		
			$params = array('operation' => RESTRICTION_OPERATION);

			$isValid = xd_security\secureCheck($params, $method);

			if (!$isValid) {
				$returnData['status'] = 'operation_not_specified';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'operation_not_specified';
				$returnData['data'] = array();
				xd_controller\returnJSON($returnData);
			};
			
			// --------------------
			
			if(!in_array($_REQUEST['operation'], $this->_registered_operations)){
				$returnData['status'] = 'invalid_operation_specified';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'invalid_operation_specified';
				$returnData['data'] = array();
				xd_controller\returnJSON($returnData);
			}
			
			$operation_handler = $this->_operation_handler_directory.'/'.$_REQUEST['operation'].'.php';
			
			if (file_exists($operation_handler)){
				include $operation_handler;
			}
			else{
				$returnData['status'] = 'operation_not_defined';
				$returnData['success'] = false;
				$returnData['totalCount'] = 0;
				$returnData['message'] = 'operation_not_defined';
				$returnData['data'] = array();
				xd_controller\returnJSON($returnData);
			}
	
		}//invoke
		
	}//XDController
	
?>
