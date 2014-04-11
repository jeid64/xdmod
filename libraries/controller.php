<?php

	namespace xd_controller;

	// --------------------------------
	
	function returnJSON($data = array()){
	
		print json_encode($data);
		exit;
		
	}//returnJSON

?>