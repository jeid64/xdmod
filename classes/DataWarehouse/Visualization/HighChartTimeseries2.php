<?php
namespace DataWarehouse\Visualization;

class HighChartTimeseries2 extends HighChart2
{
	public function __construct($aggregation_unit, 
						$start_date, 
						$end_date,$scale,$width,$height, $swap_xy = false, $showContextMenu = false, $share_y_axis = false)
	{
		parent::__construct($aggregation_unit, 
						$start_date, 
						$end_date,$scale,$width,$height,$swap_xy,$showContextMenu, $share_y_axis);
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
		//$this->_chart['plotOptions']['line']['connectNulls'] = true; //try to connect nulls with dashed lines
		
		$color_count = 33;
		$colors = \ChartFactory::getColors($color_count);
		$colors = array_reverse($colors);
		$dataSeriesCount  = count($data_series);
		$dataSources = array();
		$yAxisArray = array();
		
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
			
			if($this->_shareYAxis) 
				$axisId = 'sharedAxis';
			else
			if($this->_hasLegend && $dataSeriesCount > 1)
				$axisId = $stat->getUnit().'_'.$data_description->log_scale.'_'.($data_description->combine_type == 'percent');
			else
				$axisId = $data_description->realm.'_'.$data_description->metric.'_'.$data_description->log_scale.'_'.($data_description->combine_type == 'percent');
			
			
			if(!isset($yAxisArray[$axisId])) $yAxisArray[$axisId] = array();
			
			$yAxisArray[$axisId][] = $data_description;
		}	
		
		$yAxisCount = count($yAxisArray);
		
		$results = array();
		$color_index = 0;
	
		$roleParameterDescriptions = array();
		
		foreach(array_values($yAxisArray) as $yAxisIndex => $yAxisDataDescriptions)
		{
			$yAxis = null;
			
			foreach($yAxisDataDescriptions as $data_description_index => $data_description)
			{	
				$query_classname = '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Timeseries';

				$query = new 
					$query_classname(
							$this->_aggregationUnit, 
							$this->_startDate, 
							$this->_endDate,
							null,
							null,
							array(),
							'tg_usage',
							array(),
							false);
							
				$groupedRoleParameters = array();
				foreach($role_parameters as $role_parameter_dimension => $role_parameter_value)
				{
					if(!isset($groupedRoleParameters[$role_parameter_dimension])) $groupedRoleParameters[$role_parameter_dimension] = array();
					$groupedRoleParameters[$role_parameter_dimension][] = $role_parameter_value;
				}
				if(!$data_description->ignore_global)
				{
					foreach($global_filters->data as $global_filter)
					{
						if(isset($global_filter->checked) && $global_filter->checked == 1)
						{
							if(!isset($groupedRoleParameters[$global_filter->dimension_id])) $groupedRoleParameters[$global_filter->dimension_id] = array();
							$groupedRoleParameters[$global_filter->dimension_id][] = $global_filter->value_id;
						}
					}
				}
				$query->setRoleParameters($groupedRoleParameters);
				$roleParameterDescriptions = array_merge($roleParameterDescriptions,$query->roleParameterDescriptions);			
				$query->setFilters($data_description->filters);				
				
				if($data_description->filters->total === 1 && $data_description->group_by === 'none')
				{
					$data_description->group_by = $data_description->filters->data[0]->dimension_id;
				}
				
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
				
				
				//echo $query->getQueryString();
				$dataset = new \DataWarehouse\Data\SimpleTimeseriesDataset($query);
				
				$statisticObject = $query->_stats[$data_description->metric]; 
				$decimals = $statisticObject->getDecimals();
				
				$yAxisLabel = ($data_description->combine_type=='percent'? '% of ':'').
							(($this->_hasLegend && $dataSeriesCount > 1)?$dataset->getColumnUnit($data_description->metric,false):$dataset->getColumnLabel($data_description->metric,false));
				if($yAxis == null)
				{
					$yAxisColorValue = $data_description->color == 'auto'?$colors[$color_index % $color_count]:hexdec($data_description->color);
					$yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);	
					//$yAxisGridColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($yAxisColorValue,70)),6,'0',STR_PAD_LEFT);	
					
					$yAxis = array('title' => array('text' => $this->_shareYAxis?'':$yAxisLabel, 'style' => array('color'=> $yAxisColor, 'fontSize' => (12 + $font_size).'px')), 
									'labels' => 
									array('style' => array('fontSize' => (11 + $font_size).'px')),
								//	 'tickPixelInterval' => 100,
									'opposite' => $yAxisIndex % 2 == 1,
									'min' => $data_description->log_scale?null:0,
									'type' => $data_description->log_scale? 'logarithmic' : 'linear',
									'showLastLabel' =>  $this->_chart['title']['text'] != '',//$data_description->log_scale?true:false,
									'endOnTick' => true,//$data_description->log_scale?true:false,
									// 'gridLineDashStyle' => $this->_dashStyles[$yAxisIndex % $this->_dashStyleCount],
									'gridLineWidth' => $yAxisCount > 1 ?0: 1 + ($font_size/8),
									// 'gridLineColor' => $data_description->log_scale? $yAxisGridColor: "#C0C0C0",
									//'labels' => array('style' => array('color'=> $color)),
									 'lineWidth' => 1 + $font_size/4,
									 
									 //'lineColor'=> $yAxisColor,
									'allowDecimals' => $decimals > 0,
									'tickInterval' => $data_description->log_scale ?1:null,
									'maxPadding' =>  max(0.05, ($data_description->value_labels?0.25:0) + ($data_description->std_err?.25:0)));
					
					$this->_chart['yAxis'][] = $yAxis;
				}	
				$xAxisData = $dataset->getTimestamps();
				
				$start_ts_array = array();
				foreach($xAxisData->start_ts as $st)
				{
					$start_ts_array[] = $st*1000;
				}
				$pointInterval = 24 * 3600 * 1000 *($xAxisData->getName() == 'Day'? 1 : ($xAxisData->getName() == 'Month'? 30 : ($xAxisData->getName() == 'Quarter'? 90 : $xAxisData->getName() == 'Year'? 365 : 1)));
				if(!isset($xAxis))//TODO xAxis is never used.
				{
					$xAxis = 	
						array(
							'type' => 'datetime', 	
							'min' => strtotime($this->_startDate)*1000,			
													
							// 'title' => array('text' => $xAxisData->getName(), /*'margin' => 20 + $font_size,*/ 'style' => array('color'=> '#000000', 'fontSize' => (12 + $font_size).'px')),  
							'labels' => $this->_swapXY?
							array(
								'enabled' => true, /*'rotation' => -90, 'align' => 'right',*/
								'step' => round(($font_size<0?0:$font_size+5 ) / 11), 
								'style' => array('fontSize' => (11 + $font_size).'px', 
												 'marginTop' => $font_size*2)
							)
							:
							array(
								'enabled' => true, /*'rotation' => -90, 'align' => 'right',*/
								'step' => ceil(($font_size<0?0:$font_size+11  ) / 11), 
								'style' => array('fontSize' => (11 + $font_size).'px',
												 'marginTop' => $font_size*2)
							), 
							'minTickInterval' => $pointInterval,
							'lineWidth' => 1 + $font_size/4
						 );
     				$this->_chart['xAxis'] = $xAxis;
					
					if($xAxisData->getName() == 'Day')
					{
						$this->_chart['xAxis']['max'] = strtotime($this->_endDate)*1000;
					}
					if($xAxisData->getName() == 'Quarter')
					{
						//$this->_chart['xAxis']['labels']['formatter'] = 
						//"var v = this.value; return 'Q'+Math.floor(1+(parseInt(Highcharts.dateFormat('%m',v)))/3)+' '+Highcharts.dateFormat('%y',v);";
					}
					
				}

				$datagroupDataObject = $dataset->getColumnUniqueOrdered('dim_'.$data_description->group_by, $limit, $offset, $data_description->realm);
				$this->_total = max($this->_total,$datagroupDataObject->count);
				
				if($data_description->std_err == 1)
				{
					$semStatisticObject = $query->_stats['sem_'.$data_description->metric]; 
					$semDecimals = $semStatisticObject->getDecimals();	
				}
				
				$yAxisDataObjectsIterator = $dataset->getColumnIteratorBy('met_'.$data_description->metric,$datagroupDataObject);	
				
				$totalSeries = 0;
				foreach($yAxisDataObjectsIterator as $yAxisObject)
				{
					if( $yAxisObject != NULL) $totalSeries ++;
				}
				foreach($yAxisDataObjectsIterator as $index => $yAxisDataObject)
				{	
					if( $yAxisDataObject != NULL)
					{
						$yAxisDataObject->joinTo($xAxisData, null);
						if($this->_hasLegend ?$dataSeriesCount == 1:$yAxisCount==1) $yAxisDataObject->setName($yAxisDataObject->groupName);
												
						$color_value = $data_description->color == 'auto' ?$colors[$color_index++ % $color_count]:hexdec($data_description->color);
						$color = '#'.str_pad(dechex($color_value),6,'0',STR_PAD_LEFT);	
						$lineColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($color_value,-70)),6,'0',STR_PAD_LEFT);
						
						//high charts chokes on datasets that are all null so detect them and replace all with zero. this will give the user the right impression. (hopefully)
						$all_null = true;
						foreach($yAxisDataObject->values as $value)
						{
							if($value != null) 
							{
								$all_null = false;
								break;
							}
						}
						if($all_null) continue;
					//	if($totalSeries == 1 && $all_null == true)
					//	{
					//		$values = array_fill(0,$yAxisDataObject->getCount(),$data_description->log_scale?null:0);
					//	}
					//	else
						{
							$values = $yAxisDataObject->values;
						}
						
						$values_count = count($values);
						
						$filterParametersTitle = $data_description->long_legend == 1?$query->getFilterParametersTitle():'';
						if($filterParametersTitle != '')
						{
							$filterParametersTitle = ' {'.$filterParametersTitle.'}' ;
						}
						
						$dataLabelsConfig = array('enabled' => $data_description->value_labels, 
												  'style' => array('fontSize' => (10 + $font_size).'px', 
												  					'color' => $color )
													);
						$tooltipConfig = array();
						$seriesValues = array();					
						if($data_description->display_type == 'pie')
						{
							$this->_chart['chart']['inverted'] = false;
							foreach( $values as $index => $value)
							{
								$seriesValues[] = array('name' => $xAxisData->values[$index], 'y' => $value, 'color' => '#'.str_pad(dechex($colors[$index % $color_count]),6,'0',STR_PAD_LEFT));
							}
							$dataLabelsConfig  = array_merge($dataLabelsConfig, array( 'color' => '#000000', 'formatter' =>  "return '<b>'+this.point.name.wordWrap(15,'</b><br/><b>')+'<br/>'+Highcharts.numberFormat(this.y, $decimals);"));
							$tooltipConfig = array_merge($tooltipConfig, array('pointFormat' => "{series.name}: {point.y} <b>({point.percentage}%)</b> ", 'percentageDecimals' => 1, 'valueDecimals' => $decimals));
							
							$this->_chart['tooltip']['shared'] = false;
						}else
						{
							$dataLabelsConfig  = array_merge($dataLabelsConfig, array('formatter' => "return Highcharts.numberFormat(this.y, $decimals);"));
						
							if($this->_swapXY)
							{
								$dataLabelsConfig  = array_merge($dataLabelsConfig, array('x' => 70));
								$this->_chart['xAxis']['labels']['rotation'] = 0;
							}
							else
							{
								$dataLabelsConfig  = array_merge($dataLabelsConfig, array('rotation' => -90 , 
																						  'align' => 'center', 
																						  'y' => -70, 
																						  'formatter' => "return Highcharts.numberFormat(this.y, $decimals);"));	
							}
							$seriesValues = array();
							foreach($values as $i => $v)
							{
								$seriesValues[] = array($start_ts_array[$i], $v);
							}
							$tooltipConfig = array_merge($tooltipConfig, array('valueDecimals' => $decimals)); 
						}
						$zIndex = 0;
						if($data_description->display_type == 'column' )
						{
							$zIndex = 1;
						}else
						if($data_description->display_type == 'line' || $data_description->display_type == 'scatter' ||$data_description->display_type == 'spline')
						{
							$zIndex = 2;
						}
						$dataSeriesName = $yAxisDataObject->getName();
						$clickAction = $this->_showContextMenu?'this.ts = this.x;XDMoD.Module.UsageExplorer.seriesContextMenu(this,'.$data_description->id.',\''.$dataSeriesName.'\');':'';
						$data_series_desc = array(
									'name' => str_replace('style=""','style="color:'.$yAxisColor.'"',$dataSeriesName).$filterParametersTitle, 
									'zIndex' => $zIndex,
									'color'=> $data_description->display_type == 'pie'? NULL: $color,  
									'type' => $data_description->display_type, 
									'dashStyle' => $data_description->line_type,
									'shadow' => $data_description->shadow,//$data_description->display_type == 'line' || $data_description->display_type == 'spline',
									'groupPadding' => 0.1,
									'pointPadding' => 0,
									'borderWidth' => 0,
									'yAxis' => $yAxisIndex, 
									'lineWidth' =>  $data_description->display_type !== 'scatter' ? $data_description->line_width + $font_size/4:0,
									'showInLegend' => $data_description->display_type != 'pie',
									'innerSize' => min(100,(100.0/$totalSeries)*count($this->_chart['series'])).'%',
									'connectNulls' => $data_description->display_type == 'line' || $data_description->display_type == 'spline',
									'marker' => array('enabled' => $values_count < 21 && $this->_width > \ChartFactory::$thumbnail_width, 'lineWidth' => 1, 'lineColor' => $lineColor, 'radius' => $font_size/4 + 5 ),
									///'size' => floor(100/$totalSeries).'%',
									'tooltip' => $tooltipConfig,									
									'dataLabels' => $dataLabelsConfig,
									'data' => $seriesValues,
									'cursor' => 'pointer',
									'point' => array('events' => array('click' => $clickAction)));
						if($data_description->display_type === 'pie')
						{
							$data_series_desc['borderWidth'] = 1;
						}			
						if($data_description->display_type!=='line')
						{
							if($data_description->combine_type=='stack')
							{
								$data_series_desc['stacking'] = 'normal';
								//$data_series_desc['stack'] = 0;
							}
							else if($data_description->combine_type=='percent' && !$data_description->log_scale ) $data_series_desc['stacking'] = 'percent';
						}
						$this->_chart['series'][] = $data_series_desc;
						
						if(isset($data_description->trend_line) && $data_description->trend_line == 1 && $data_description->display_type != 'pie' )
						{
							$first = NULL;
							$last = NULL;
							$newValues = array();
							foreach($values as $i => $value)
							{
								if($value !== NULL) 
								{
									if($first === NULL && $value != 0)
									{
										 $first = $i;
									}
									if($first !== NULL)
									{
										$newValues[] = $value; 
										$last = $i;
									}
								}
							}
							
							$new_values_count = count($newValues);
							
							if($new_values_count > 1)
							{
								list($m,$b,$r, $r_squared) = \xd_regression\linear_regression(array_keys($newValues),$newValues);
								$trend_points = array();
								foreach($newValues as $ii => $value) //first first positive point on trend line since when logscale negative values make it barf
								{
									$y = ($m*$ii)+$b;
									if(!$data_description->log_scale || $y > 0) 
									{
										$trend_points[] = array($start_ts_array[$first+$ii], $y);
									}
								}
								
								$trend_formula = (number_format($m,2)==0?number_format($m,3):number_format($m,2)).'x '.($b>0?'+':'').number_format($b,2);
								$data_series_desc = array(
										'name' => str_replace('style=""','style="color:'.$yAxisColor.'"','Trend Line: '.$dataSeriesName.' ('.$trend_formula.'), R-Squared='.number_format($r_squared,2)).$filterParametersTitle, 
										'zIndex' => 3, 
										'color'=> $color, 
										'type' => $data_description->log_scale?'spline':'line', 
										'shadow' => $data_description->shadow,
										'groupPadding' => 0.05,
										'pointPadding' => 0,
										'borderWidth' => 0,
										'enableMouseTracking' => false,
										'yAxis' => $yAxisIndex, 
										'lineWidth' => 2 + $font_size/4.0,
										'showInLegend' => true,
										'marker' => array('enabled' => false, 'hover' => array('enabled' => false)),
										'dashStyle' => 'ShortDot',
										'm' => $m,
										'b' => $b,
										//'tooltip' => array('pointFormat' => $trend_formula),
										'data' => $trend_points);
								$this->_chart['series'][] = $data_series_desc;
							}
						}
						
						if($data_description->std_err == 1 && $data_description->display_type != 'pie' /*&& $totalSeries < 2*/)
						{
							$error_color_value = \ChartFactory::alterBrightness($color_value,-70);
							$error_color = '#'.str_pad(dechex($error_color_value),6,'0',STR_PAD_LEFT);	
							
							$errorCount = $yAxisDataObject->getErrorCount();
							$error_series = array();
							
							for($i = 0 ; $i < $errorCount; $i++)
							{
								$has_value = isset($yAxisDataObject->values[$i])  && $yAxisDataObject->values[$i] != 0;
								$error_series[] = array('x' => $start_ts_array[$i], 'bottom' => $has_value ?$yAxisDataObject->values[$i]-$yAxisDataObject->errors[$i]:null, 'top' => $has_value ?$yAxisDataObject->values[$i]+$yAxisDataObject->errors[$i]:null);
							}
							$err_data_series_desc = array(
										'name' => '(Std Err) '.$dataSeriesName.$filterParametersTitle, 
										'zIndex' => 4,//$data_description->display_type == 'column' || $data_description->display_type == 'area'? 4:NULL, 
										'color'=> $error_color, 
										'type' => 'ErrorBar', 
										'shadow' => $data_description->shadow,
										'groupPadding' => 0.05,	
										'lineWidth' =>2 + $font_size/4,
										'pointPadding' => 0,
										'yAxis' => $yAxisIndex, 
										'tooltip' => array('valueDecimals' => $semDecimals, 'valuePrefix' => '+/-'),
										//'dataLabels' => array('enabled' => $data_description->value_labels, 'rotation' => -90 , 'align' => 'center', 'y' => -60, 'formatter' => "return Highcharts.numberFormat(this.y, $decimals);"),
										'data' => $error_series,
									'cursor' => 'pointer',
									'point' => array('events' => array('click' => $clickAction)));
						
							if(! $data_description->log_scale)
							{
								$this->_chart['series'][] = $err_data_series_desc;
							}
						}
					}
				}				
			}
		}

		if($this->show_filters) $this->_chart['subtitle']['text'] = wordwrap(implode(" -- ", array_unique($roleParameterDescriptions)),$this->_width/($font_size+6),"<br />\n");
		
		$this->setDataSource(array_keys($dataSources));
		
		if($this->_chart['title']['text'] == '' && $this->_chart['subtitle']['text'] != '') 
		{
			$this->_chart['title']['text'] = $this->_chart['subtitle']['text'];
			$this->_chart['subtitle']['text'] = '';
		}
	}
	
	public function configure2(
					    &$query, \DataWarehouse\Data\TimeseriesDataset &$dataset, \XDUser &$user, 
						$combine_type, $display_type, $log_scale,  $show_aggregate_labels, $show_error_labels, $show_error_bars, $show_trend_line, $show_guide_lines,
						$font_size,
						$show_drill_down = false,
						$limit = NULL, $offset = NULL
						)
	{ 
		if($display_type == 'bar') $display_type = 'column';
		if($display_type == 'h_bar') 
		{
			$display_type = 'column';
		}
		$this->_chart['title']['style'] = array('color'=> '#000000', 'fontSize' => (16 + $font_size).'px');
		$this->_chart['subtitle']['style'] = array('fontSize' => (12 + $font_size).'px');
		$this->_chart['subtitle']['y'] = 30+ $font_size;
		$this->_chart['legend']['itemStyle'] = array('fontSize' => (12  + $font_size).'px');
		

		if($this->_width <= \ChartFactory::$thumbnail_width)
		{		
			$limit = floor($limit*$this->_width/(\ChartFactory::$thumbnail_width*3.0));
		}	
		$color_count = 33;//$dataset->countDataSeries();
		$colors = \ChartFactory::getColors($color_count);
		$colors = array_reverse($colors);
		 
		
		//$chart_colors = array();
		$long_name_short_name = array();
		{	
			$datalabels = array();
			$dataoveralls = array();
			$datalabels_to_overall =  $dataset->getDataSeriesNamesAndOverall();
			foreach($datalabels_to_overall as $key => $row)
			{
				$datalabels[$key] = $row['name'];
				$dataoveralls[$key] = $row['value'];
				$long_name_short_name[$this->_width <= \ChartFactory::$thumbnail_width?$row['short_name']:$row['name']] = $row['name'];
			}
			array_multisort( $datalabels, SORT_DESC, $dataoveralls, SORT_DESC, $datalabels_to_overall);
	
			//foreach($datalabels_to_overall as $key => $datalabel_to_overall)
			//{
			//	$chart_colors[$datalabel_to_overall['name']] = $colors[($key % $color_count)];
			//}		
		}
			
		$datalabels =  $dataset->getDataSeriesNames($this->_width <= \ChartFactory::$thumbnail_width);
		$groupByValues = $dataset->getDataSeriesNames(false);
		
		
		$data_scale_factor = 1;
		$data_scale_factor_label = '';
		$data_series_count = $dataset->countDataSeries();
		if($data_series_count - $limit > 1)
		{
			$dataset->truncate($limit);
			$datalabels =  $dataset->getDataSeriesNames($this->_width <= \ChartFactory::$thumbnail_width);
			$long_name_short_name[$datalabels[$limit]] = $datalabels[$limit];
			//$chart_colors[$long_name_short_name[$datalabels[$limit]]] = $colors[$limit];
			$data_series_count = $dataset->countDataSeries();
		}
		$data_series = $dataset->getDataSeries(false, $data_scale_factor,$data_scale_factor_label);
		$data_per_data_series_count = $dataset->countDataPerDataSeries();
		
		$data_max = 1;
		$data_min = 0;
		$dataset->getMinMax($data_min, $data_max);
			
		$aggregation_unit = $dataset->getAggregationUnit();
		$this->setDataSource(array($query->getDataSource()));
		
		$yAxis = null;
		$i = 0;		
		//$data_colors = array();
		foreach($data_series as $data_index => $data)
		{	
			$hasErrors = $data->hasErrors();
			$hasValues = $data->hasValues();
			//$data_colors[] = $chart_colors[$data->getName()];
			$values = $data->getValues();
			
			$errors = $data->getErrors();
			$errorCount = count($errors);
			
			$calc_decimals = $data->getStatistic()->getDecimals($data_min,$data_max);
			$decimals = $this->_width > \ChartFactory::$thumbnail_width?$calc_decimals:1;
		
			$color_value = $colors[$i % $color_count];
			$color = '#'.str_pad(dechex($color_value),6,'0',STR_PAD_LEFT);	
			$lineColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($color_value,-70)),6,'0',STR_PAD_LEFT);
			
			$all_null = true;
			foreach($values as $vi => $value)
			{
				if($value != null) 
				{
					$all_null = false;
					break;
				}
			}
			if($all_null) continue;
				
			$values_count = count($values);
			
			$xAxisData = $dataset->getTimestamps();
				
			$start_ts_array = array();
			foreach($xAxisData as $st)
			{
				$start_ts_array[] = $st*1000;
			}
			
			if($yAxis == null)
			{
				$yAxisColorValue = $colors[0];
				$yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);	
				$yAxisGridColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($yAxisColorValue,70)),6,'0',STR_PAD_LEFT);	
				$yAxisLabel = ($combine_type=='percentage'? '% of ':'').$data->getStatistic()->getUnit();
				$yAxis = array(
					'title' => array('text' => $yAxisLabel, 'style' => array(/*'color'=> $yAxisColor,*/ 'fontSize' => (12 + $font_size).'px')), 
					'labels' => array('style' => array('fontSize' => (11 + $font_size).'px')),
					//'tickPixelInterval' => 100,//$this->_height/5,
					'min' => $log_scale?null:0,
					'type' => $log_scale? 'logarithmic' : 'linear',
					'showLastLabel' =>  true,//$this->_chart['title']['text'] != '',//$data_description->log_scale?true:false,
					'endOnTick' => true,//$log_scale?true:false,
					'gridLineDashStyle' => $this->_dashStyles[0],
					'gridLineWidth' =>  $show_guide_lines? 1 + ($font_size/8):0,
					'gridLineColor' => "#C0C0C0",
					'lineWidth' => 1 + $font_size/4,
					'allowDecimals' => $decimals > 0,
					'tickInterval' => $log_scale ?1:null,
					'maxPadding' =>  max(0.05, ($show_aggregate_labels?0.25:0) + ($show_error_bars && $hasErrors && $combine_type!='percentage' && $data_series_count === 1?.25:0))
				);
				
				$this->_chart['yAxis'][] = $yAxis;
			}
			
			
			if(!isset($xAxis))
			{				
				$pointInterval = 24 * 3600 * 1000 *($aggregation_unit == 'day'? 1 : ($aggregation_unit == 'month'? 30 : ($aggregation_unit == 'quarter'? 90 : $aggregation_unit == 'year'? 365 : 1)));
			
				$xAxis = 	
					array(
						'type' => 'datetime', 	
						'min' => strtotime($this->_startDate)*1000,			
												
						// 'title' => array('text' => $xAxisData->getName(), /*'margin' => 20 + $font_size,*/ 'style' => array('color'=> '#000000', 'fontSize' => (12 + $font_size).'px')),  
						'labels' => $this->_swapXY?
						array(
							'enabled' => true, /*'rotation' => -90, 'align' => 'right',*/
							'step' => round(($font_size<0?0:$font_size+5 ) / 11), 
							'style' => array('fontSize' => (11 + $font_size).'px', 
											 'marginTop' => $font_size*2)
						)
						:
						array(
							'enabled' => true, /*'rotation' => -90, 'align' => 'right',*/
							'step' => ceil(($font_size<0?0:$font_size+11  ) / 11), 
							'style' => array('fontSize' => (11 + $font_size).'px',
											 'marginTop' => $font_size*2)
						), 
						'minTickInterval' => $pointInterval,
						'lineWidth' => 1 + $font_size/4
					 );
				$this->_chart['xAxis'] = $xAxis;
				if($aggregation_unit == 'day')
				{
					$this->_chart['xAxis']['max'] = strtotime($this->_endDate)*1000;
				}
				if($aggregation_unit == 'quarter')
				{
					//$this->_chart['xAxis']['labels']['formatter'] = 
					//"var v = this.value; return 'Q'+Math.floor(1+(parseInt(Highcharts.dateFormat('%m',v)))/3)+' '+Highcharts.dateFormat('%y',v);";
				}
				
			}
			
			
			$dataLabelsConfig = array('enabled' => $show_aggregate_labels || ($show_error_labels && $hasErrors && $combine_type!='percentage' && $data_series_count === 1), 
									  'style' => array('fontSize' => (10 + $font_size).'px', 
														'color' => $color )
									  
									  );
			if($show_aggregate_labels && $show_error_labels && $hasErrors && $combine_type!='percentage' && $data_series_count === 1)
			{
				$dataLabelsConfig['formatter'] = "return Highcharts.numberFormat(this.y, $decimals)+' [+/-'+Highcharts.numberFormat(this.percentage, $decimals)+']';";
			}else
			if($show_aggregate_labels)
			{
				$dataLabelsConfig['formatter'] = "return Highcharts.numberFormat(this.y, $decimals);";
			}
			else
			if($show_error_labels && $hasErrors && $combine_type!='percentage' && $data_series_count === 1)
			{
				$dataLabelsConfig['formatter'] = "return '+/-'+Highcharts.numberFormat(this.percentage, $decimals);";
			}
			$tooltipConfig = array();
			$seriesValues = array();					
		
			if($this->_swapXY)
			{
				$dataLabelsConfig  = array_merge($dataLabelsConfig, array('x' => 70));
				$this->_chart['xAxis']['labels']['rotation'] = 0;
			}
			else
			{
				$dataLabelsConfig  = array_merge($dataLabelsConfig, array('rotation' => -90 , 
																		  'align' => 'center', 
																		  'y' => -70));	
			}
			$seriesValues = array();
			foreach($values as $vi => $v)
			{
				$dv = (double) $v;
				$point = array('x' => $start_ts_array[$vi], 'y'=> $dv == 0 ?null:$dv); //if logscale dont pass 0
				if($hasErrors && isset($errors[$vi]))$point['percentage'] = (double)$errors[$vi];
				$seriesValues[] = $point;
			}
			$tooltipConfig = array_merge($tooltipConfig, array('valueDecimals' => $decimals));
			
			
			$drillDowns = implode(',',$user->getCachedActiveRole()->getQueryDescripters($dataset->getQueryGroupname(), 
																										$dataset->getRealmName(), 
																										$data->getGroupBy()->getName(), 
																										$data->getStatistic()->getAlias()->getName())->getDrillTargets($data->getStatistic()->getAlias()));
			$groupByNameAndUnit = $data->getGroupBy()->getName().'-'.$data->getGroupBy()->getUnit();
			$groupByIds = $data->getIds();
			
			$groupByValue = str_replace("'","\'", $groupByValues[$data_index]);
			$clickAction = $show_drill_down?"this.ts = this.x;XDMoD.Module.Usage.drillChart(this,'$drillDowns',
															   '$groupByNameAndUnit',
															   '{$groupByIds[0]}',
															   '$groupByValue',
															   'none',
															   '{$dataset->getQueryGroupname()}', 
															   '{$dataset->getRealmName()}');":'';
			$datasetName = ($data_series_count > 1? ($data->getGroupBy()->getOrderByStatOption() != NULL?($i+1).'. ':''): '')/*.(($data_series_count === 1)?$data->getStatistic()->getLabel(false).': ' :'')*/.(($this->_width <= \ChartFactory::$thumbnail_width)?$data->getShortName():$data->getName()).($hasValues?'':' - *Empty Dataset*');
			
			$zIndex = 0;
			if($display_type == 'column' )
			{
				$zIndex = 1;
			}else
			if($display_type == 'line' || $display_type == 'spline')
			{
				$zIndex = 2;
			}
			$data_series_desc = array(
						'name' => $datasetName, 
						'zIndex' => $zIndex, 
						'color'=> $display_type == 'pie'? NULL: $color,  
						'type' => $display_type, 
						'shadow' => false,//$data_description->display_type == 'line' || $data_description->display_type == 'spline',
						'groupPadding' => 0.1,
						'pointPadding' => 0,
						'borderWidth' => 0,
						'yAxis' => 0, 
						'lineWidth' => 2 + $font_size/4,
						'showInLegend' => $display_type != 'pie',
						'connectNulls' => $display_type == 'line' || $display_type == 'spline',
						'marker' => array('enabled' => $values_count < 31 && $this->_width > \ChartFactory::$thumbnail_width, 'lineWidth' => 1, 'lineColor' => $lineColor, 'radius' => $font_size/4 + 5 ),
						'tooltip' => $tooltipConfig,									
						'dataLabels' => $dataLabelsConfig,
						'data' => $seriesValues,
						'cursor' => 'pointer',
						'point' => array('events' => array('click' => $clickAction))
						);		
			if($display_type === 'pie')
			{
				$data_series_desc['borderWidth'] = 1;
			}			
			if($display_type!=='line')
			{
				if(	$combine_type=='stack' ) $data_series_desc['stacking'] = 'normal';
				else if($combine_type=='percentage' && !$log_scale) $data_series_desc['stacking'] = 'percent';
			}
			$this->_chart['series'][] = $data_series_desc;
			
			
	
			if($combine_type!='percentage' && $data_series_count === 1)
			{
				if(isset($show_trend_line) && $show_trend_line == 1 && $hasValues && $all_null != true)
				{
					$first = NULL;
					$last = NULL;
					$lastNonZero = NULL;
					$newValues = array();
					foreach($values as $i => $value)
					{
						if($value !== NULL) 
						{
							if($first === NULL && $value != 0)
							{
								 $first = $i;
							}
							if($first !== NULL)
							{
								$newValues[] = $value; 
								$last = $i;
							}
						}
						if($value !== 0)
						{
							$lastNonZero = $i;
						}
					}
					$last = min($last,$lastNonZero);
					//print_r($newValues);
					//echo $first, ' ', $last, "\n";
					$newValues = array_slice($newValues,0,$last-$first+1);
					//print_r($newValues);
					$new_values_count = count($newValues);
				
					if($new_values_count > 1)
					{
						list($m,$b,$r, $r_squared) = \xd_regression\linear_regression(array_keys($newValues),$newValues);
						$trend_formula = (number_format($m,2)==0?number_format($m,3):number_format($m,2)).'x '.($b>0?'+':'').number_format($b,2); 
						
						$trend_points = array();
						foreach($newValues as $ii => $value) 
						{
							$y = ($m*$ii)+$b;
							if(!$log_scale || $y > 0) //when log_scale find first positive point on trend line since when logscale negative values make it barf
							{
								$trend_points[] = array($start_ts_array[$first+$ii], $y);
								
							}
						}
						//print_r($trend_points);
						$data_series_desc = array(
								'name' => 'Trend Line: ('.$trend_formula.'), R-Squared='.number_format($r_squared,6), 
								'zIndex' => 3, 
								'color'=> $color, 
								'type' => $log_scale?'spline':'line', 
								'shadow' => false,
								'groupPadding' => 0.05,
								'pointPadding' => 0,
								'enableMouseTracking' => false,
								'yAxis' => 0, 
								'lineWidth' => 2 + $font_size/4.0,
								'showInLegend' => true,
								'marker' => array('enabled' => false, 'hover' => array('enabled' => false)),
								'dashStyle' => 'ShortDot',
								'm' => $m,
								'b' => $b,
								'data' => $trend_points);
						
						$this->_chart['series'][] = $data_series_desc;
					}
				}
				
				if(isset($show_error_bars) && $show_error_bars == 1 && $hasErrors)
				{
					$error_color_value = \ChartFactory::alterBrightness($color_value,-70);
					$error_color = '#'.str_pad(dechex($error_color_value),6,'0',STR_PAD_LEFT);	
					
					
					$error_series = array();
					
					for($i = 0 ; $i < $errorCount; $i++)
					{
						$has_value = isset($values[$i]) && $values[$i] != 0;
						$error_series[] = array('x' => $start_ts_array[$i], 'bottom' => $has_value ?$values[$i]-$errors[$i]:null, 'top' => $has_value ?$values[$i]+$errors[$i]:null);
					}
					
					$err_data_series_desc = array(
								'name' => '(Std Err)', 
								'zIndex' => 4,//$data_description->display_type == 'column' || $data_description->display_type == 'area'? 4:NULL, 
								'color'=> $error_color, 
								'type' => 'ErrorBar', 
								'shadow' => false,
								'groupPadding' => 0.05,	
								'lineWidth' =>2 + $font_size/4,
								'pointPadding' => 0,
								'yAxis' => 0, 
								'tooltip' => array('valueDecimals' => $decimals, 'valuePrefix' => '+/-'),
								//'dataLabels' => $errorLabelsConfig,
								'data' => $error_series,
								'cursor' => 'pointer',
								'point' => array('events' => array('click' => $clickAction)));
				
					
					//print_r($error_series);
					//echo $has_nulls;
					if(! $log_scale)
					{
						$this->_chart['series'][] = $err_data_series_desc;
					}
				}
				
				
			}
		
			$i++;
		}
		
	}
	public function exportJsonStore()
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