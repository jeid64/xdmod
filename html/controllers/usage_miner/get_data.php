<?php
require_once('common.php');

$returnData = array();

try
{
	
	$user = \xd_security\getLoggedInUser();

	$role_data = explode(':', getActiveRole());
	$role_data = array_pad($role_data, 2, NULL);
	$activeRole = $user->assumeActiveRole($role_data[0], $role_data[1]);
	$user->setCachedActiveRole($activeRole);
	
	//print $active_role->getIdentifier(true);
	
	$role_parameters = $activeRole->getParameters();
	
	$selectedDimensionIds = getSelectedDimensionIds();
	$selectedMetricIds = getSelectedMetricIds();
	
	$selectedDimensionIdsCount = count($selectedDimensionIds);
	$selectedMetricIdsCount = count($selectedMetricIds);
	
	$realm = getRealm();
	
	$format = \DataWarehouse\ExportBuilder::getFormat($_REQUEST, 'png', array('png', 'xml', 'csv', 'jsonstore', 'hc_jsonstore'));
	$inline = getInline();
	
	list($start_date, $end_date, $start_ts, $end_ts) = checkDateParameters();
	
	$aggregation_unit = getAggregationUnit();
	$timeseries = getTimeseries();
	
	$limit = getLimit();
	$offset = getOffset();

	$sortInfo = getSortInfo();
	
	$query_classname = $timeseries? '\\DataWarehouse\\Query\\'.$realm.'\\Timeseries' : '\\DataWarehouse\\Query\\'.$realm.'\\Aggregate';
	$dataset_classname = $timeseries? '\DataWarehouse\Data\SimpleTimeseriesDataset' : '\DataWarehouse\Data\SimpleDataset';
	$chart_classname = $timeseries? '\DataWarehouse\Visualization\CustomTimeseriesXYChart' : '\DataWarehouse\Visualization\CustomXYChart';
	$highchart_classname = $timeseries? '\DataWarehouse\Visualization\HighChartTimeseries' : '\DataWarehouse\Visualization\HighChart';
	
	$query = new $query_classname($aggregation_unit, 
							$start_date, 
							$end_date, 
							null,
							null,
							array(),
							'tg_usage',
							 array(),
							false);

	$filename = 'data_explorer_'.$start_date.'_to_'.$end_date.'_'.implode('_',$selectedDimensionIds).'_'.implode('_',$selectedMetricIds);
	$filename = substr($filename,0,100);
	
	if($format === 'hc_jsonstore' || $format === 'png' )
	{	
		$swap_xy = getSwapXY();
		$chartProperties = (array)getChartProperties();
	
		$query->configureForChart($chartProperties,$selectedDimensionIds, $sortInfo);
		$query->setParametersFromRequest($_REQUEST,$role_parameters);
		
		$dataset = new $dataset_classname($query);
	
		$show_title = getShowtitle();
		$width = getWidth();
		$height = getHeight();
		$scale = getScale();
		
		$show_guide_lines = getShowGuideLines();
		
		
		$requestDescripter = new \User\Elements\RequestDescripter($_REQUEST);
		$chartIdentifier = $requestDescripter->__toString();
		$chartPool = new \XDChartPool($user);
		$includedInReport = $chartPool->chartExistsInQueue($chartIdentifier);
		
		$hc = new $highchart_classname($chartProperties, $dataset, $scale, $width, $height, $swap_xy, $chartIdentifier, $includedInReport);
			
		if($format === 'hc_jsonstore')
		{
			$returnData = $hc->exportJsonStore($limit, $offset);
		}
		else if($format === 'png' )
		{
         // This section is deprecated
		}
	}
	else if( $format === 'jsonstore' || $format === 'csv' || $format === 'xml' )
	{		
		$query->configureForDatasheet($selectedDimensionIds,$selectedMetricIds,$sortInfo);
		$query->setParametersFromRequest($_REQUEST,$role_parameters);

		$dataset = new $dataset_classname($query);
		
		if($format === 'jsonstore')
		{
			$returnData = $dataset->exportJsonStore($limit, $offset);
		}
		else  if( $format === 'csv' || $format === 'xml')
		{
			$exportedData = $dataset->export($filename);
			
			$returnData = \DataWarehouse\ExportBuilder::export(array($exportedData),$format, $inline, $filename);
		}
	}
	
}
catch(Exception $ex)
{
	print_r($ex);
	$returnData = array(
			'totalCount' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
}

if( $format === 'hc_jsonstore' ||$format == 'jsonstore' || $format ==  'session_variable' )
{	
	\DataWarehouse\ExportBuilder::writeHeader($format);
	print json_encode($returnData);
}
?>