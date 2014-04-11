<?php

	$app_kernels = explode(';', strtolower($_POST['app_kernels']));

	$profile = $_POST['profile'];
	
	$details = array(
		'This resource has a high performance disk system, thanks to solid state technology.',
		'This resource provides high throughput due to the high clock speed in each of its 8 cores.',
		'This resource has high memory bandwidth, and is tolerable of running extensive codes.'
	);
	
	$i = 0;
	
	foreach($app_kernels as $kernel) {
	
		$i++;
		
		$e['resources'][] = array(
							'site_title' => 'Resource '.$i, 
							'site_host' => "resource-$i.xdmod.com", 
							'site_details' => $details[$i % count($details)]
						);
	}
		
	echo json_encode($e);
	
?>