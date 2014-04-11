<?php
namespace DataWarehouse\Visualization;

class HighChart2
{
	protected $_swapXY;
	protected $_chart;
	protected $_width;
	protected $_height;
	protected $_scale;//deprecated
	
	protected $_aggregationUnit;
	protected $_startDate;
	protected $_endDate;
	
	protected $_total;
	
	protected $_dashStyles = array(
        'Solid',
        'ShortDash',
        'ShortDot',
        'ShortDashDot',
        'ShortDashDotDot',
        'Dot',
        'Dash',
        'LongDash',
        'DashDot',
        'LongDashDot',
        'LongDashDotDot'
    );
	
	protected $_dashStyleCount = 11;

	public function __construct($aggregation_unit, 
						$start_date, 
						$end_date, $scale,$width,$height, $swap_xy = false)
	{
		$this->_groupBys = array();
		$this->_stats = array();
		
		$this->_aggregationUnit = $aggregation_unit;
		$this->_startDate = $start_date;
		$this->_endDate = $end_date;
		
		$this->_width = $width *$scale;
		$this->_height = $height *$scale;
		$this->_scale = 1; //deprecated

		$this->_swapXY = $swap_xy;
		
		$this->_total = 0;
		
		$this->_chart = array(
				'chart' => array('inverted' => $this->_swapXY/*, 'zoomType' => 'x'*/),
				'credits' => array('text' => $this->_startDate.' to '. $this->_endDate.'. Powered by XDMoD/Highcharts', 'href' => ''),
				'title' => array('text' => '', 'x' => -20),
				'subtitle' => array('text' => '', 'x' => -20),
				'xAxis' => array('categories' => array()),
				'yAxis' => array(),
				'legend' => array(

								'backgroundColor' => '#FFFFFF',
								//'shadow' => true,
								// 'itemWidth' => $width/4,
								//'margin' => 30,
								'borderWidth' => 0,
								
								'y' => -5,   
								'labelFormatter' => 
								"var ret = ''; 
								var x = this.name; 
								var indexOfSQ = x.indexOf(']');
								var brAlready = false;
								if( indexOfSQ > 0)
								{
								 ret += x.substring(0,indexOfSQ+1)+'<br/>';
								 x = x.substring(indexOfSQ+1,x.length);
								 brAlready = true;
								}
								var indexOfBr = x.indexOf('{');
								if( indexOfBr > 0 && !brAlready)
								{
								 ret += x.substring(0,indexOfBr)+'<br/>';
								 x = x.substring(indexOfBr,x.length);
								}
								ret+=x.wordWrap(50,'<br/>');
								return ret;"
							),
				'series' => array(),
				'tooltip' => array('crosshairs' => true, 'shared' => true,  'xDateFormat' => '%Y-%m-%d',),
				'plotOptions' => array(  'series' => array( 'allowPointSelect' => false, 'connectNulls' => false/*,  'marker' => array('enabled' => true, 'hover' => array('enabled' => true))*/)),
	
				'dimensions' => array(),
				'metrics' => array(),
				'exporting' => array('enabled' => false)
			);
	}
	public function setDataSource(array $source)
	{
		$src = count($source) > 0? ' Src: '.implode(', ',$source).'.':'';
		$this->_chart['credits']['text'] = $this->_startDate.' to '. $this->_endDate.' '.$src.' Powered by XDMoD/Highcharts';
	}
	public function getTitle()
	{
		return $this->_chart['title']['text'];
	}
	public function setTitle($title)
	{
		$this->_chart['title']['text'] = $title;
	}
	public function setSubtitle($title)
	{
		$this->_chart['subtitle']['text'] = $title;
	}
	public function setLegendLocation($legend_location)
	{
		$this->_legend_location = $legend_location;
		switch($legend_location)
		{
			case 'top_center':
				$this->_chart['legend']['align'] = 'center';
				$this->_chart['legend']['verticalAlign'] = 'top';
				$pad = 0;
				if($this->_chart['title']['text'] != '') $pad += 30;
				if($this->_chart['subtitle']['text'] != '') $pad += 20;
				$this->_chart['legend']['y'] = $pad;
				
			break;
			//case 'bottom_right':
			//break;
			//case 'bottom_left':
			//break;
			case 'left_center':
				$this->_chart['legend']['align'] = 'left';
				$this->_chart['legend']['verticalAlign'] = 'middle';
				$this->_chart['legend']['layout'] = 'vertical';
				break;
			
			case 'left_top':
				$this->_chart['legend']['align'] = 'left';
				$this->_chart['legend']['verticalAlign'] = 'top';
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'left_bottom':
				$this->_chart['legend']['align'] = 'left';
				$this->_chart['legend']['verticalAlign'] = 'bottom';
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'right_center':
				$this->_chart['legend']['align'] = 'right';
				$this->_chart['legend']['verticalAlign'] = 'middle';
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'right_top':
				$this->_chart['legend']['align'] = 'right';
				$this->_chart['legend']['verticalAlign'] = 'top';
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'right_bottom':
				$this->_chart['legend']['align'] = 'right';
				$this->_chart['legend']['verticalAlign'] = 'bottom';
				$this->_chart['legend']['layout'] = 'vertical';
			break;	
			case 'floating_bottom_center':
				$this->_chart['legend']['align'] = 'center';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['y'] = -100;
			break;
			case 'floating_top_center':
				$this->_chart['legend']['align'] = 'center';
				$this->_chart['legend']['verticalAlign'] = 'top';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['y'] = 70;
			break;
			case 'floating_left_center':
				$this->_chart['legend']['align'] = 'left';
				$this->_chart['legend']['verticalAlign'] = 'middle';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['x'] = 80;
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'floating_left_top':
				$this->_chart['legend']['align'] = 'left';
				$this->_chart['legend']['verticalAlign'] = 'top';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['x'] = 80;
				$this->_chart['legend']['y'] = 70;
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'floating_left_bottom':
				$this->_chart['legend']['align'] = 'left';
				$this->_chart['legend']['verticalAlign'] = 'bottom';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['x'] = 80;
				$this->_chart['legend']['y'] = -100;
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'floating_right_center':
				$this->_chart['legend']['align'] = 'right';
				$this->_chart['legend']['verticalAlign'] = 'middle';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['x'] = -10;
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'floating_right_top':
				$this->_chart['legend']['align'] = 'right';
				$this->_chart['legend']['verticalAlign'] = 'top';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['x'] = -10;
				$this->_chart['legend']['y'] = 70;
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case 'floating_right_bottom':
				$this->_chart['legend']['align'] = 'right';
				$this->_chart['legend']['verticalAlign'] = 'bottom';
				$this->_chart['legend']['floating'] = true;
				$this->_chart['legend']['x'] = -10;
				$this->_chart['legend']['y'] = -100;
				$this->_chart['legend']['layout'] = 'vertical';
			break;
			case '':
			case 'none':
			case 'off':
				$this->_legend_location = 'off';
				$this->_chart['legend']['enabled'] = false;
			break;
			case 'bottom_center':
			default:
				$this->_legend_location = 'bottom_center';
				$this->_chart['legend']['align'] = 'center';
				$this->_chart['legend']['margin'] = 15;
				break;
		}
		if($legend_location != 'bottom_center')
		{
			$this->_chart['chart']['spacingBottom'] = 25;
		}
		if($legend_location != 'right_center' &&
		   $legend_location != 'right_top' &&
		   $legend_location != 'right_bottom')
		{
			$this->_chart['chart']['spacingRight'] = 20;
		}
		$this->_hasLegend = $this->_legend_location != 'off';
	}
	public function configure(
						&$data_series,
						&$request, 
						&$role_parameters,
						&$global_filters,
						$font_size,
						$limit = NULL, $offset = NULL
						)
	{ 
	
		$this->show_filters = isset($request['show_filters'])? $request['show_filters'] == 'y' || $request['show_filters'] == 'true' : true; 
		
		
		$this->_chart['title']['style'] = array('color'=> '#000000', 'fontSize' => (16 + $font_size).'px');
		$this->_chart['subtitle']['style'] = array('fontSize' => (12 + $font_size).'px');
		$this->_chart['subtitle']['y'] = 30+ $font_size;
		$this->_chart['legend']['itemStyle'] = array('fontSize' => (12  + $font_size).'px');
		//$this->_chart['legend']['itemMarginBottom'] = 16 + $font_size;
		
		$colors = \ChartFactory::getColors(33);
		$colors = array_reverse($colors);
		
		$dataSources = array();
		
		$complexDataset = new \DataWarehouse\Data\ComplexDataset();
		
		$roleParameterDescriptions = array();

		foreach($data_series as $data_description_index => $data_description)
		{	
			$query_classname = '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Aggregate';
			
			try
			{
				$stat = $query_classname::getStatistic($data_description->metric);
			}
			catch(\Exception $ex)
			{
				continue;
			}
			$this->_chart['metrics'][$stat->getLabel(false)] = $stat->getLabel();
			
			//$axisId = $data_description->realm.'_'.$data_description->metric.'_'.$data_description->log_scale.'_'.($data_description->combine_type == 'percent');
			$axisId = $stat->getUnit().'_'.$data_description->log_scale.'_'.($data_description->combine_type == 'percent');
			
			if(!isset($yAxisArray[$axisId])) $yAxisArray[$axisId] = array();
			
			$yAxisArray[$axisId][] = $data_description;
			
			$query = new $query_classname(
						$this->_aggregationUnit, 
						$this->_startDate, 
						$this->_endDate, 
						null,
						null,
					 	array(),
						'tg_usage',
						 array(),
						false);
			$dataSources[$query->getDataSource()] = 1;			
			$group_by = $query->addGroupBy($data_description->group_by);
			$this->_chart['dimensions'][$group_by->getLabel()] = $group_by->getInfo();
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
			}
			
			//print_r($global_filters);
			
			$groupedRoleParameters = array();
			foreach($role_parameters as $role_parameter_dimension => $role_parameter_value)
			{
				if(!isset($groupedRoleParameters[$role_parameter_dimension])) $groupedRoleParameters[$role_parameter_dimension] = array();
				$groupedRoleParameters[$role_parameter_dimension][] = $role_parameter_value;
			}
			
			foreach($global_filters->data as $global_filter)
			{
				//echo $global_filter->checked, '"';
				if(isset($global_filter->checked) && $global_filter->checked == 1)
				{
					if(!isset($groupedRoleParameters[$global_filter->dimension_id])) $groupedRoleParameters[$global_filter->dimension_id] = array();
					$groupedRoleParameters[$global_filter->dimension_id][] = $global_filter->value_id;
				}
			}
			
			$query->setRoleParameters($groupedRoleParameters);
			
			$roleParameterDescriptions = array_merge($roleParameterDescriptions,$query->roleParameterDescriptions);	
			
			$query->setFilters($data_description->filters  );
		
			$dataset = new \DataWarehouse\Data\SimpleDataset($query);
			
			$complexDataset->addDataset($data_description, $dataset);
			
		}
		
		if($this->show_filters)$this->_chart['subtitle']['text'] = wordwrap(implode(" -- ", array_unique($roleParameterDescriptions)),$this->_width/($font_size+6),"<br />\n");
		 
		$this->setDataSource(array_keys($dataSources));
		$xAxisDataObject = $complexDataset->getXAxis(false,$limit, $offset);
		
		
		
		
		$this->_chart['xAxis'] = array('title' => array('text' => $xAxisDataObject->getName()=='XSEDE'?'':$xAxisDataObject->getName(), 
														'margin' => 15 + $font_size, 
														'style' => array(
																	'color'=> '#000000',
													 				'fontSize' => (12 + $font_size).'px'
																	)
													),  
										'labels' => $this->_swapXY ?
												array(
														'enabled' => true,
													    
														 'step' => $xAxisDataObject->getCount()< 20?0:round($xAxisDataObject->getCount()/20), 
														 'style' => array('fontSize' => (11 + $font_size).'px' , 'line-height' =>1+$font_size/40), 
														 'formatter' => " 
														 var maxL = ".floor($this->_width*($limit<11?35:20)/580).";
														 var x  = (this.value.length>maxL-3)?this.value.substring(0,maxL-3)+'...':this.value;
																	 return x.wordWrap(".floor($this->_width*($limit<11?22:22)/580).",'<br/>');
																	 "
												
												):
												   array('enabled' => true,
													     'rotation' => $xAxisDataObject->getCount()<= 8?0: -90,
														 //'staggerLines' => $xAxisDataObject->getCount()>10 && $xAxisDataObject->getCount()<16?2:null,
														 'align' => $xAxisDataObject->getCount()<= 8?'center':'right', 
														 'step' => $xAxisDataObject->getCount()< 20?0:round($xAxisDataObject->getCount()/20), 
														 'style' => array('fontSize' => (11 + $font_size).'px' , 'line-height' =>1+$font_size/40), 
														 'formatter' => " 
														 var maxL = ".floor($this->_height*($limit<11?30:15)/400).";
														 var x  = (this.value.length>maxL-3)?this.value.substring(0,maxL-3)+'...':this.value;
																	 return x.wordWrap(".floor($this->_height*($limit<11?18:18)/400).",'<br/>');
																	 "
													), 
										'lineWidth' => 2 + $font_size/4,
										'categories' => $xAxisDataObject->values);
									
		$yAxisArray = $complexDataset->getYAxis($limit, $offset);
		$this->_total = $complexDataset->_total;
		$yAxisCount = count($yAxisArray);
		$color_index = 0;
		foreach($yAxisArray as $yAxisIndex => $yAxisObject)
		{
			$yAxisColorValue = /*count($yAxisDataDescriptions) > 1 ? 0x000000 :*/$colors[$color_index % 33];
			$yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);	
			//$yAxisGridColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($yAxisColorValue,70)),6,'0',STR_PAD_LEFT);	
			
			$yAxis = array('title' => array('text' => $yAxisObject->title, 'style' => array('color'=> $yAxisColor, 'fontSize' => (12 + $font_size).'px')), 
											 'labels' => array( 'style' => array('fontSize' => (11 + $font_size).'px')),
											 'opposite' => $yAxisIndex % 2 == 1,
											 'min' => $yAxisObject->log_scale?null:0,
											 'type' => $yAxisObject->log_scale? 'logarithmic' : 'linear',
											 'showLastLabel' => $this->_chart['title']['text'] != '',//$data_description->log_scale?true:false,
											 'gridLineWidth' => $yAxisCount > 1 ?0: 1,											 
											 'endOnTick' => true,// $data_description->log_scale?true:false,
											 'lineWidth' => 2 + $font_size/4,
											// 'lineColor'=> $yAxisColor,
											 'allowDecimals' => $yAxisObject->decimals > 0,
											 'tickInterval' => $yAxisObject->log_scale ?1:null,
											 'maxPadding' => min(0.6,0.30*($yAxisIndex) + ($yAxisObject->value_labels?0.5:0.1) + ($yAxisObject->std_err?.3:0))
											 );
											 
			$this->_chart['yAxis'][] = $yAxis; 
			
			foreach($yAxisObject->series as $yAxisDataObjectAndDescription)
			{
				$yAxisDataObject = $yAxisDataObjectAndDescription['yAxisDataObjet'];
				$data_description = $yAxisDataObjectAndDescription['data_description'];
				$decimals = $yAxisDataObjectAndDescription['decimals'];
				$semDecimals = $yAxisDataObjectAndDescription['semDecimals'];
				$filterParametersTitle =  $yAxisDataObjectAndDescription['filterParametersTitle'];
				
				$color_value = $colors[$color_index++ % 33];
				$color = '#'.str_pad(dechex($color_value),6,'0',STR_PAD_LEFT);	
				
				
			//	$this->_chart['chart'] = $data_description->display_type;
				$dataLabelsConfig = array(
											'enabled' => $data_description->value_labels, 'style' => array('fontSize' => (11 + $font_size).'px', 'color' => $color ));
				$tooltipConfig = array();
				$values = array();	
									
				if($data_description->display_type == 'pie')
				{
					foreach( $yAxisDataObject->values as $index => $value)
					{
						$values[] = array('name' => $xAxisDataObject->values[$index], 'y' => $value, 'color' => '#'.str_pad(dechex($colors[$index % 33]),6,'0',STR_PAD_LEFT));
					}
					$dataLabelsConfig  = array_merge($dataLabelsConfig, array( 'color' => '#000000', 'formatter' =>
														"var maxL = ".floor($this->_width*($limit<11?30:15)/580).";
														 var x  = (this.point.name.length>maxL+3)?this.point.name.substring(0,maxL-3)+'...':this.point.name;
																	 return '<b>'+x.wordWrap( ".floor($this->_width*($limit<11?15:15)/580).",'</b><br/><b>')+'</b><br/>'+Highcharts.numberFormat(this.y, $decimals);;
																	 "
					//"return '<b>'+this.point.name.wordWrap(15,'</b><br/><b>')+'<br/>'+Highcharts.numberFormat(this.y, $decimals);"
					 ));
					$tooltipConfig = array_merge($tooltipConfig, array('pointFormat' => "{series.name}: {point.y} <b>({point.percentage}%)</b> ", 'percentageDecimals' => 1, 'valueDecimals' => $decimals));
					
					$this->_chart['tooltip']['shared'] = false;
				}else {
					
					$dataLabelsConfig  = array_merge($dataLabelsConfig, array('formatter' => "return Highcharts.numberFormat(this.y, $decimals);"));
																				  						
					if(/*$data_description->display_type == 'bar'*/ $this->_swapXY) 
					{						
						$dataLabelsConfig  = array_merge($dataLabelsConfig, array( 'x' => 70));
						$this->_chart['xAxis']['labels']['rotation'] = 0;						
					}
					else
					{
						$dataLabelsConfig  = array_merge($dataLabelsConfig, array('rotation' => -90 , 
																				  'align' => 'center', 
																				  'y' => -70, 
																				  'formatter' => "return Highcharts.numberFormat(this.y, $decimals);"));	
					}
					$values = $yAxisDataObject->values;
					$tooltipConfig = array_merge($tooltipConfig, array('valueDecimals' => $decimals));
				}
				
				$values_count = count($values);
				
			
				$data_series_desc = array(
									'name' => str_replace('style=""','style="color:'.$yAxisColor.'"',$yAxisDataObject->getName()).$filterParametersTitle, 
									'zIndex' => $data_description->display_type == 'column' ? 1: $data_description->display_type == 'area'? 2:3, 
									'color'=> $data_description->display_type == 'pie'? NULL: $color,
									'type' => $data_description->display_type, 
									'shadow' => false,
									'groupPadding' => 0.05,
									'pointPadding' => 0,
									'borderWidth' => 0,
									'yAxis' => $yAxisIndex, 
									'lineWidth' => 2 + $font_size/4, 
									'marker' => array('lineWidth' => 1, 'radius' => $font_size/4 + 5 ),
									'tooltip' => $tooltipConfig,
									'showInLegend' => true,
									'dataLabels' => $dataLabelsConfig,
									'data' => $values);
					
				if(	$data_description->combine_type=='stack'  && count($data_series) > 1) $data_series_desc['stacking'] = 'normal';
				else if($data_description->combine_type=='percent') $data_series_desc['stacking'] = 'percent';
				$this->_chart['series'][] = $data_series_desc;
				
				/*if(isset($data_description->trend_line) && $data_description->trend_line == 1 && $data_description->display_type != 'pie' && $values_count > 1)
				{
					$newValues = array();
					foreach($values as $value)
					{
						if($value != NULL) $newValues[] = $value; 
					}
					$new_values_count = count($newValues);
					if($new_values_count > 1)
					{
						list($m,$b) = \xd_regression\linear_regression(array_keys($newValues),$newValues);
						$trend_formula = number_format($m,2).'x '.($b>0?'+':'').number_format($b,2);
						
						
						$data_series_desc = array(
								'name' => str_replace('style=""','style="color:'.$yAxisColor.'"',$yAxisDataObject->getName().' Trend Line ('.$trend_formula.')').$filterParametersTitle, 
								'zIndex' => 4, 
								'color'=> $color, 
								'type' => 'line', 
								'shadow' => true,
								'groupPadding' => 0.05,
								'pointPadding' => 0,
								'yAxis' => $yAxisIndex, 
								'lineWidth' => 1 + $font_size/6,
								'showInLegend' => true,
								'marker' => array ('enabled' => false),
								'dashStyle' => 'LongDash',
								//'tooltip' => array('pointFormat' => $trend_formula),
								'data' => array(array(0,$b), array($values_count-1,($values_count-1)*$m+$b)));
						$this->_chart['series'][] = $data_series_desc;
					}
				}*/
						
				if($data_description->std_err == 1 && $data_description->display_type != 'pie')
				{
					$error_color_value = \ChartFactory::alterBrightness($color_value,-70);
					$error_color = '#'.str_pad(dechex($error_color_value),6,'0',STR_PAD_LEFT);	
					$errorCount = $yAxisDataObject->getErrorCount();
					$error_series = array();
					
					for($i = 0 ; $i < $errorCount; $i++)
					{
						$has_value = isset($yAxisDataObject->values[$i]);
						$error_series[] = array('x' => $i, 'bottom' => $has_value ?$yAxisDataObject->values[$i]-$yAxisDataObject->errors[$i]:null, 'top' => $has_value ?$yAxisDataObject->values[$i]+$yAxisDataObject->errors[$i]:null);
					}
					$err_data_series_desc = array(
								'name' => '(Std Err) '.$yAxisDataObject->getName().$filterParametersTitle, 
								'zIndex' => 4,//$data_description->display_type == 'column' || $data_description->display_type == 'area'? 4:NULL, 
								'color'=> $error_color, 
								'type' => 'ErrorBar', 
								'shadow' => false,
								'groupPadding' => 0.05,	
								'pointPadding' => 0,
								 'lineWidth' => 2,
								'yAxis' => $yAxisIndex, 
								'tooltip' => array('valueDecimals' => $semDecimals, 'valuePrefix' => '+/-'),
								//'dataLabels' => array('enabled' => $data_description->value_labels, 'rotation' => -90 , 'align' => 'center', 'y' => -60, 'formatter' => "return Highcharts.numberFormat(this.y, $decimals);"),
								'data' => $error_series);
					if(! $data_description->log_scale)
					{
						$this->_chart['series'][] = $err_data_series_desc;
					}
				}
			}
		}
		
		if($this->_chart['title']['text'] == '' && $this->_chart['subtitle']['text'] != '') 
		{
			$this->_chart['title']['text'] = $this->_chart['subtitle']['text'];
			$this->_chart['subtitle']['text'] = '';
		}
		//$this->_chart['metrics'] = array_values($this->_chart['metrics']);
		//$this->_chart['dimensions'] = array_values($this->_chart['dimensions']);
		
	}
	
	public function exportJsonStore($limit = NULL, $offset = NULL)
	{		
		$returnData = array(
						'totalCount' => $this->_total,
						'success' => true, 
						'message' => 'success', 
						'data' => array($this->_chart)
						);
						
		return $returnData;
	
	}
}

?>