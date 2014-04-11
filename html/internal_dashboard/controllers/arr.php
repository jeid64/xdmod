<?php

@session_start();

require_once dirname(__FILE__) . '/../../../configuration/linker.php';

$controller = new XDController(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), dirname(__FILE__));
$controller->registerOperation('get_summary');
$controller->registerOperation('get_active_tasks');
$controller->registerOperation('get_errmsg');
$controller->invoke('REQUEST', 'xdDashboardUser');

