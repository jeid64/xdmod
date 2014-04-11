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

	function getDataSeries()
	{		
	
		if(!isset($_REQUEST['data_series']) || empty($_REQUEST['data_series']))  return json_decode(0);
		
		if( is_array($_REQUEST['data_series']) && is_array($_REQUEST['data_series']['data']) )   
		{
			$v = $_REQUEST['data_series']['data'];
			
			$ret = array();
			foreach($v as $x)
			{
				$y = (object) $x;
				
				for($i = 0, $b = count($y->filters['data']); $i < $b; $i++)
				{
					$y->filters['data'][$i] = (object) $y->filters['data'][$i];
				}
				
				$y->filters = (object) $y->filters;

				$ret[] = $y;
			}
			return $ret;
		}
		$ret =  urldecode($_REQUEST['data_series']);	
		
		return json_decode($ret);
	}

	function getSelectedFilterIds()
	{
		return isset($_REQUEST['selectedFilterIds']) && $_REQUEST['selectedFilterIds'] != '' ? explode(',',$_REQUEST['selectedFilterIds']):array();
	}
	
	
	function getGlobalFilters()
	{
		if(!isset($_REQUEST['global_filters']) || empty($_REQUEST['global_filters']))  return (object)array('data' => array(), 'total' => 0);
		  
		if(is_array($_REQUEST['global_filters'])) 
		{
			$v = $_REQUEST['global_filters']['data'];
			
			$ret = (object)array('data' => array(), 'total' => 0);

			foreach($v as $x)
			{
				$ret->data[] = (object)$x;
				$ret->total++;
			}
			return $ret;
		}
		$ret =  urldecode($_REQUEST['global_filters']);	
		
		return json_decode($ret);
	}			
	

?>
