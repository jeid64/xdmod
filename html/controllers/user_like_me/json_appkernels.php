<?php

	require_once dirname(__FILE__).'/../../../configuration/linker.php';

	$profile = isset($_POST['profile']) ? $_POST['profile'] : '';
	
	generateJSON($profile);
	
	function generateJSON($profile) {
	
		$e['profile'] = $profile;
		
		$db_host =     xd_utilities\getConfiguration('database', 'host');
		$db_user =     xd_utilities\getConfiguration('database', 'user');
		$db_pass =     xd_utilities\getConfiguration('database', 'pass');
			
		mysql_connect($db_host, $db_user, $db_pass);

		$res = mysql_query('SELECT DISTINCT name FROM mod_warehouse.app_kernel ORDER BY name ASC');
		
		$selectedAppKernels = array(
			'compchem' => array('NWChem', 'GAMESS', 'QuantumESPRESSO', 'CPMD'),
			'biochem'  => array('Amber', 'NAMD', 'CHARMM'),
			'astro'    => array('HPCC', 'NPB', 'BLAS', 'OMB', 'IMB', 'STREAM'),
			'' => array()
		);
		
		while (list($ak_name) = mysql_fetch_array($res)) {
			$e['appkernels'][] = array('text' => $ak_name, 'include' => in_array($ak_name, $selectedAppKernels[$profile]), 'data_link' => 'Data');		
		}
		
		echo json_encode($e);

	}//generateJSON
	
?>