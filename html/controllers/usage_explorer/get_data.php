<?php
require_once('common.php');

$returnData = array();

try
{
	if(isset($_REQUEST['config']))
	{
		$config = json_decode($_REQUEST['config'],true);
		$_REQUEST = array_merge($config,$_REQUEST);
	}
	
	$format = \DataWarehouse\ExportBuilder::getFormat($_REQUEST, 'png', array('svg', 'png', 'png_inline', 'svg_inline', 'xml', 'csv', 'jsonstore', 'hc_jsonstore'));

   $user = \xd_security\detectUser(array(XDUser::INTERNAL_USER, XDUser::PUBLIC_USER));
   
	$activeRoleParam = getActiveRole();
	
	$role_data = explode(':', $activeRoleParam);
	
	$role_data = array_pad($role_data, 2, NULL);
	$activeRole = $user->assumeActiveRole($role_data[0], $role_data[1]);
	$user->setCachedActiveRole($activeRole);
	
	$role_parameters = $activeRole->getParameters();

	$rp_regex = '/rp_(?P<rp_id>[0-9]+)/';
	
	if(preg_match($rp_regex,$activeRoleParam,$matches) > 0)
	{
		$role_parameters['provider'] =$matches['rp_id'];
	}
	
	$inline = getInline();
	
	list($start_date, $end_date, $start_ts, $end_ts) = checkDateParameters();
	if($start_ts > $end_ts) throw new Exception('End date must be greater than or equal to start date');
	
	
	$showContextMenu = getShowContextMenu();
	

	$aggregation_unit = getAggregationUnit();
	$timeseries = getTimeseries();
	
	$limit = getLimit();
	$offset = getOffset();

	$title = getTitle();
	$show_title = getShowTitle();
	
	$global_filters = getGlobalFilters();
	
	$share_y_axis = getShareYAxis();
	 
	$dataset_classname = $timeseries? '\DataWarehouse\Data\SimpleTimeseriesDataset' : '\DataWarehouse\Data\SimpleDataset';
	$chart_classname = $timeseries? '\DataWarehouse\Visualization\CustomTimeseriesXYChart' : '\DataWarehouse\Visualization\CustomXYChart';
	$highchart_classname = $timeseries? '\DataWarehouse\Visualization\HighChartTimeseries2' : '\DataWarehouse\Visualization\HighChart2';

	$filename = 'xdmod_'.($title != ''?$title:'untitled').'_'.$start_date.'_to_'.$end_date;
	$filename = substr($filename,0,100);
	
	$data_series = getDataSeries();
	//print_r($data_series);

	if($format === 'hc_jsonstore' || $format === 'png' || $format === 'svg' || $format === 'png_inline' || $format === 'svg_inline' )
	{		
		$width = getWidth();
		$height = getHeight();
		$scale = getScale();
		$swap_xy = getSwapXY();
		
		$legend_location = getLegendLocation();
	
		$font_size = getFontSize();
		$hc = new $highchart_classname($aggregation_unit, 
						$start_date, 
						$end_date, $scale, $width, $height, $swap_xy, $showContextMenu, $share_y_axis); 
						
		if($show_title)$hc->setTitle($title); 			
		$hc->setLegendLocation($legend_location);//called before and after
		$hc->configure($data_series,
						$_REQUEST,
						$role_parameters,
						$global_filters,
						$font_size,
						$limit,
						$offset
						);
		$hc->setLegendLocation($legend_location);
		
		$returnData = $hc->exportJsonStore($limit, $offset);
		
		$requestDescripter = new \User\Elements\RequestDescripter($_REQUEST);
		$chartIdentifier = $requestDescripter->__toString();
		$chartPool = new \XDChartPool($user);
		$includedInReport = $chartPool->chartExistsInQueue($chartIdentifier, $title);
		
		$returnData['data'][0]['reportGeneratorMeta'] = 
		array(
			'chart_args' => $chartIdentifier,
			'title' => $title,
			'params_title' =>  $returnData['data'][0]['subtitle']['text'],
			'start_date' => $start_date,
			'end_date' => $end_date,
			'included_in_report' => $includedInReport?'y':'n'
		);

		if (isset($_REQUEST['render_thumbnail']))
		{
			\xd_charting\processForThumbnail($returnData);
			
			header("Content-Type: image/png");
			print \xd_charting\exportHighchart($returnData, '148', '69', 2, 'png');
			
			exit;
		}
		
		if (isset($_REQUEST['render_for_report']))
		{
			\xd_charting\processForReport($returnData);
			
			header("Content-Type: image/png");
			print \xd_charting\exportHighchart($returnData, $width, $height, $scale, 'png');
			
			exit;
		}
            
      	if($format === 'png')
		{
			\DataWarehouse\ExportBuilder::writeHeader($format,false,$filename);
			print \xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'png');
		}
		else 
		if($format === 'png_inline')
		{
			 
			\DataWarehouse\ExportBuilder::writeHeader($format,false,$filename);
			print 'data:image/png;base64,'.base64_encode(\xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'png'));
			
		}
		else if($format === 'svg')
		{
		
			\DataWarehouse\ExportBuilder::writeHeader($format,false,$filename);
			print \xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'svg');
			
		}
		if($format === 'svg_inline')
		{
			 
			\DataWarehouse\ExportBuilder::writeHeader($format,false,$filename);
			print 'data:image/svg+xml;base64,'.base64_encode(\xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'svg'));
			
		}

	}
	else if( $format === 'jsonstore' || $format === 'csv' || $format === 'xml' )
	{	
		foreach($data_series as $data_description_index => $data_description)
		{
			$query_classname = $timeseries? '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Timeseries': '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Aggregate';
		
			$query = new $query_classname(
						$aggregation_unit, 
						$start_date, 
						$end_date, 
						null,
						null,
						array(),
						'tg_usage',
						 array(),
						false);
						
			$query->addGroupBy($data_description->group_by);
			$query->addStat($data_description->metric);
			
			if($data_description->std_err == 1)
			{
				try
				{
					 $query->addStat('sem_'.$data_description->metric);
				}
				catch(\Exception $ex)
				{
					$data_description->std_err = 0;
				}
			}
			/*
			switch ($data_description->sort_type)
			{
				case 'value_asc':
				$query->addOrderBy($data_description->metric,'asc');
				$query->sortInfo = array(array('column_name' => $data_description->metric, 'direction' => 'asc'));
				break;
				case 'value_desc':
				$query->addOrderBy($data_description->metric,'desc');
				$query->sortInfo = array(array('column_name' => $data_description->metric, 'direction' => 'desc'));
				break;
				case 'label_asc':
				$query->addOrderBy($data_description->group_by,'asc');
				$query->sortInfo = array(array('column_name' => $data_description->group_by, 'direction' => 'asc'));
				break;
				case 'label_desc':
				$query->addOrderBy($data_description->group_by,'desc');
				$query->sortInfo = array(array('column_name' => $data_description->group_by, 'direction' => 'desc'));
				break;		
			}*/
			
			$groupedRoleParameters = array();
			foreach($role_parameters as $role_parameter_dimension => $role_parameter_value)
			{
				if(!isset($groupedRoleParameters[$role_parameter_dimension])) $groupedRoleParameters[$role_parameter_dimension] = array();
				$groupedRoleParameters[$role_parameter_dimension][] = $role_parameter_value;
			}
			foreach($global_filters->data as $global_filter)
			{
				if($global_filter->checked == 1)
				{
					if(!isset($groupedRoleParameters[$global_filter->dimension_id])) $groupedRoleParameters[$global_filter->dimension_id] = array();
					$groupedRoleParameters[$global_filter->dimension_id][] = $global_filter->value_id;
				}
			}
			
			$query->setRoleParameters($groupedRoleParameters);
			
		//	$roleParameterDescriptions = array_merge($roleParameterDescriptions,$query->roleParameterDescriptions);	
			
			$query->setFilters($data_description->filters  );
			
			$dataset = new $dataset_classname($query);
			
			if( $format === 'csv' || $format === 'xml')
			{
				$exportedData = $dataset->export($filename);

				\DataWarehouse\ExportBuilder::export(array($exportedData),$format, $inline, $filename);
			}
			else if($format === 'jsonstore')
			{
				$returnData = $dataset->exportJsonStore();
			}
		}		
	}	
}
catch(Exception $ex)
{

   \xd_response\presentError($ex);
 
}

if(isset($format) &&( $format == 'hc_jsonstore' ||$format == 'jsonstore' || $format ==  'session_variable'  ))
{	
	\DataWarehouse\ExportBuilder::writeHeader($format);
	print json_encode($returnData);
	
}
?>