<?php

	// Operation: user_auth->login_check
	
	$returnData['status'] = isset($_SESSION['xdUser']) ? 'logged_in' : 'not_logged_in';
	
	xd_controller\returnJSON($returnData);

?>