<?php

	// Operation: user_interface->get_my_reports
	
	$returnData[] = array('text' => '2009 Q3', 'id' => "report_1", "iconCls" => 'report');
	$returnData[] = array('text' => '2009 Q4', 'id' => "report_2", "iconCls" => 'report');

	xd_controller\returnJSON($returnData);

?>