<?php
	require_once( dirname(__FILE__).'/../common_params.php');
	
	function getSelectedDimensionIds()
	{
		return isset($_REQUEST['selectedDimensionIds']) && $_REQUEST['selectedDimensionIds'] != '' ? explode(',',$_REQUEST['selectedDimensionIds']):array();
	}
	function getSelectedMetricIds()
	{
		return isset($_REQUEST['selectedMetricIds']) && $_REQUEST['selectedMetricIds'] != '' ? explode(',',$_REQUEST['selectedMetricIds']):array();
	}
	function getAggregationUnit()
	{
		return isset($_REQUEST['aggregation_unit'])?$_REQUEST['aggregation_unit']:'auto'; 
	}
	function getTimeseries()
	{
		return isset($_REQUEST['timeseries'])?$_REQUEST['timeseries'] == 'true' || $_REQUEST['timeseries'] === 'y': false; 
	}
	function getInline()
	{
		return isset($_REQUEST['inline'])?$_REQUEST['inline'] == 'true' || $_REQUEST['inline'] === 'y': true;
	}
	function getLimit()
	{
		if(!isset($_REQUEST['limit']) || empty($_REQUEST['limit'])) $limit = 20;
		else $limit = $_REQUEST['limit'];	
		return $limit;
	}
	function getOffset()
	{		
		if(!isset($_REQUEST['start']) || empty($_REQUEST['start']))  $offset = 0;
		else $offset = $_REQUEST['start'];	
		return $offset;
	}
	function getChartProperties()
	{		
		if(!isset($_REQUEST['chart_properties']) || empty($_REQUEST['chart_properties']))  $chart_config = 0;
		else $chart_config =  urldecode($_REQUEST['chart_properties']);	
		
		return json_decode($chart_config);
	}

	function getSwapXY()
	{
		return isset($_REQUEST['swap_xy'])?$_REQUEST['swap_xy'] == 'true' || $_REQUEST['swap_xy'] === 'y': false;
	}

	function getSelectedFilterIds()
	{
		return isset($_REQUEST['selectedFilterIds']) && $_REQUEST['selectedFilterIds'] != '' ? explode(',',$_REQUEST['selectedFilterIds']):array();
	}
		
?>