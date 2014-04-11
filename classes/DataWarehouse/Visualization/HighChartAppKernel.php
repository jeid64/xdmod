<?php
namespace DataWarehouse\Visualization;

class HighChartAppKernel extends HighChart2
{
	public function __construct($start_date, 
						$end_date,$scale,$width,$height, $swap_xy = false)
	{
		parent::__construct('auto', 
						$start_date, 
						$end_date,
						$scale,
						$width,$height,
						$swap_xy);
		$this->_axis = array();
		
		$this->_datasetCount = 0;
		$this->_axisCount = 0;

	}

	public function configure(
						&$datasets,
						$font_size = 0,
						$limit = NULL, $offset = NULL,
						$isSVG = false, 
						$drillDown = false,
						$colorsPerCore = false,						
						$longLegend = false,
						$showChangeIndicator = true ,
						$showControls = false, 
						$discreteControls = false, 
						$showControlZones = false, 
						$showRunningAverages = false, 
						$showControlInterval = false
						
						)
	{
		//$this->show_filters = isset($request['show_filters'])? $request['show_filters'] == 'y' || $request['show_filters'] == 'true' : true; 
		
		$this->_chart['title']['style'] = array('color'=> '#000000', 'fontSize' => (16 + $font_size).'px');
		$this->_chart['subtitle']['style'] = array('fontSize' => (12 + $font_size).'px');
		$this->_chart['subtitle']['y'] = 30+ $font_size;
		$this->_chart['legend']['itemStyle'] = array('fontSize' => (12  + $font_size).'px');
		$this->_chart['tooltip']['xDateFormat'] = '%Y/%m/%d %H:%M:%S';
		
		unset($this->_chart['legend']['labelFormatter' ]);
		
		$this->_chart['xAxis'] = 	
			array(
				'type' => 'datetime', 	
				'min' => strtotime($this->_startDate)*1000,			
				'max' => strtotime($this->_endDate)*1000,				
				// 'title' => array('text' => $xAxisData->getName(), /*'margin' => 20 + $font_size,*/ 'style' => array('color'=> '#000000', 'fontSize' => (12 + $font_size).'px')),  
				'labels' => 
					$this->_swapXY?
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
				'minTickInterval' =>  24 * 3600 * 1000,
				'lineWidth' => 1 + $font_size/4,
				'plotBands' => array()
			);

		$colors = \ChartFactory::getColors(33);
		$colors = array_reverse($colors);
		//$yAxisIndex = 0;
		foreach($datasets as $index => $dataset)
		{
			if(!isset($this->_axis[$dataset->metricUnit]))
			{
				$yAxisColorValue = $colors[$this->_axisCount % 33];
				$yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);	
				$yAxis = 
					array(
						 'title' => array('text' => $dataset->metricUnit, 'style' => array('color'=> $yAxisColor, 'fontSize' => (12 + $font_size).'px')), 
						 'labels' => array( 'style' => array('fontSize' => (11 + $font_size).'px'),
						 									 'formatter' => "return this.value< 0.01?this.value:Highcharts.numberFormat(this.value); "),
						 'opposite' => $this->_axisCount % 2 == 1,
						 'min' => false?null:0,
						 'type' => false? 'logarithmic' : 'linear',
						 'showLastLabel' => true,//$this->_chart['title']['text'] != '',//$data_description->log_scale?true:false,
						 //'gridLineWidth' => $yAxisCount > 1 ?0: 1,
						 
						 'endOnTick' => true,// $data_description->log_scale?true:false,
						 'index' => $this->_axisCount,
						 'lineWidth' => 1 + $font_size/4,
						// 'lineColor'=> $yAxisColor,
						 'allowDecimals' => true,
						 'tickInterval' => false ?1:null//,
						// 'maxPadding' => 0.5// min(0.6,0.30*($yAxisIndex))
					 );
				$this->_axis[$dataset->metricUnit] = $yAxis;
				$this->_chart['yAxis'][] = $yAxis;
				$this->_axisCount++;
			}
		}
		$controlPivot = -0.5;
		foreach($datasets as $index => $dataset)
		{
			$dataCount = count($dataset->valueVector);
			$yAxis = $this->_axis[$dataset->metricUnit];
			$yAxisColorValue = $colors[$yAxis['index'] % 33];
			$yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);	
			
			$color_value =  $colorsPerCore?self::getAppKernelColor($dataset->rawNumProcUnits):  $colors[$this->_datasetCount % 33];
			$color = '#'.str_pad(dechex($color_value),6,'0',STR_PAD_LEFT);	
			$lineColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($color_value,-70)),6,'0',STR_PAD_LEFT);
			
			if($longLegend)
			{
				$datasetName = '['.$dataset->numProcUnits.': '.$dataset->resourceName.'] <br/>'.$dataset->akName.' '.$dataset->metric.' [<span style="color:'.$yAxisColor.'">'.$dataset->metricUnit.'</span>]';
			}
			else
			{
				$datasetName = $dataset->numProcUnits;
			}
			
			$enableMarkers = $dataCount < 31 && $this->_width > \ChartFactory::$thumbnail_width;
			$seriesValues = array();
			foreach($dataset->valueVector as $i => $v)
			{
				$sv = array('x' =>  $dataset->timeVector[$i]*1000.0, 'y' => (double)$v);
				$sv['marker'] = array();
				if($showChangeIndicator && $dataset->versionVector[$i] > 0)
				{ 
					$sv['marker']['symbol'] = 'url(http://byrds.ccr.buffalo.edu/xdmod/exclamation_ak.png )'; 
				}else
				{
					$sv['marker']['enabled'] = $enableMarkers;
				}
				
				$seriesValues[] = $sv; 
			}
			
			$data_series_desc = 
				array(
					'name' => $datasetName, 
					'zIndex' => 1,
					'color'=>  $color,  
					'type' => 'line', 
					'shadow' => false,
					'groupPadding' => 0.1,
					'pointPadding' => 0,
					'borderWidth' => 0,
					'yAxis' => $yAxis['index'], 
					'lineWidth' => 2 + $font_size/4,
					'showInLegend' => true,
					'connectNulls' => true,
					'marker' => array('lineWidth' => 1, 'lineColor' => $lineColor, 'radius' => $font_size/4 + 5, 'symbol' => self::getAppKernelSymbol($dataset->rawNumProcUnits) ),
					'data' => $seriesValues
				);
				
			if($drillDown)
			{
				$data_series_desc['cursor'] = 'pointer';
				$data_series_desc['point'] = array('events' => array('click' => "XDMoD.Module.AppKernels.selectChildUnitsChart(".$dataset->rawNumProcUnits.");"));
			}
			$this->_chart['series'][] = $data_series_desc;
			
			$versionSum = array_sum($dataset->versionVector);
			if($showChangeIndicator && $versionSum > 0 && !isset($this->changeIndicatorInLegend) )
			{
				$versionValues = array();
				foreach($dataset->versionVector as $i => $v)
				{
					$versionValues[] = array('x' => $dataset->timeVector[$i]*1000.0, 'y' => null/* $v > 0 ?(double)$dataset->valueVector[$i]: null*/) ;
				}
				
				$version_series_desc = array(
									'name' => ($isSVG?'[<b>!</b>] ':'').'Change Indicator', 
									'yAxis' => $yAxis['index'], 
									'zIndex' => 10,
									'type' => 'scatter',
									'tooltip' => array('enabled' => false),
								    'marker' => array('enabled' => !$isSVG, 'symbol' => 'url(http://byrds.ccr.buffalo.edu/xdmod/exclamation_ak.png)') ,
									//'lineWidth' => 2 + $font_size/4,
									'showInLegend' => !isset($this->changeIndicatorInLegend),
									'legendIndex' => 100000,
									'data' => $versionValues 
									);
				$this->changeIndicatorInLegend = true;					
				$this->_chart['series'][] = $version_series_desc;
			}
			
			if($showRunningAverages)
			{
				$averageValues = array();
				foreach($dataset->runningAverageVector as $i => $v)
				{
					$sv = array('x' =>  $dataset->timeVector[$i]*1000.0, 'y' => $v?(double)$v:NULL);
					
					$averageValues[] = $sv; 
				}
				
				$aColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness($color,-200)),6,'0',STR_PAD_LEFT);	
				$data_series_desc = 
					array(
						'name' => 'Running Average', 
						'zIndex' => 1,
						'color'=>  $aColor,  
						'type' => 'line', 
						'shadow' => false,
						'dashStyle' => 'Dash',
						'groupPadding' => 0.1,
						'pointPadding' => 0,
						'borderWidth' => 0,
						'yAxis' => $yAxis['index'], 
						'lineWidth' => 1 + $font_size/4,
						'showInLegend' => true,
						'connectNulls' => false,
						'marker' => array('enabled' => false ),
						'data' => $averageValues
						);
				$this->_chart['series'][] = $data_series_desc;
			}
			
			if($showControls)
			{
				if(!isset($this->_axis['control']))
				{
					//$yAxisColorValue = $colors[$this->_axisCount % 33];
					//$yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);	
					$yAxisControl = 
						array(
							 'title' => array('text' => 'Control', 'style' => array(/*'color'=> $yAxisColor,*/ 'fontSize' => (12 + $font_size).'px')), 
							 'labels' => array( 'style' => array('fontSize' => (11 + $font_size).'px'),
																 'formatter' => "return this.value< 0.01?this.value:Highcharts.numberFormat(this.value); "),
							 'opposite' => $this->_axisCount % 2 == 1,
							 'type' => 'linear',
							 'showLastLabel' => $this->_chart['title']['text'] != '',							 
							 'endOnTick' => true,
							 'index' => $this->_axisCount,
							 'lineWidth' => 2 + $font_size/4,
							 'allowDecimals' => true,
							 'tickInterval' => false ?1:null
						 );
					$this->_axis['control'] = $yAxisControl;
					$this->_chart['yAxis'][] = $yAxisControl;
					$this->_axisCount++;
				}
					
				$controlVector = array();
				foreach($dataset->controlVector as $i => $control)
				{
					if($discreteControls)
					{
						if($control > 0) $control = 1;
						else if($control < $controlPivot) $control = -1;
						else $control = 0;
					}
					$sv = array('x' =>  $dataset->timeVector[$i]*1000.0, 'y' => (double)$control);
					$controlVector[] = $sv; 
				}
				
				//$cColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness(,-200)),6,'0',STR_PAD_LEFT);	
				$data_series_desc = 
					array(
						'name' => 'Control', 
						'zIndex' => 1,
						//'color'=>  $cColor,  
						'type' => 'line', 
						'shadow' => false,
						'dashStyle' => 'ShortDot',
						'groupPadding' => 0.1,
						'pointPadding' => 0,
						'borderWidth' => 0,
						'yAxis' => $this->_axis['control']['index'], 
						'lineWidth' => 1 + $font_size/4,
						'showInLegend' => true,
						'connectNulls' => false,
						'marker' => array('enabled' => false ),
						'data' => $controlVector
						);
				$this->_chart['series'][] = $data_series_desc;

			}
			if($showControlInterval)
			{	
				$rangeValues = array();
				foreach($dataset->controlStartVector as $i => $v)
				{
					$v2 = $dataset->controlEndVector[$i];
					$sv = array($dataset->timeVector[$i]*1000.0, $v2?(double)$v2:NULL, $v?(double)$v:NULL);
					
					$rangeValues[] = $sv; 
				}
				
				$aColor = '#'.str_pad(dechex(\ChartFactory::alterBrightness(0xB0E0E6,00)),6,'0',STR_PAD_LEFT);	
				$data_series_desc = 
					array(
						'name' => 'Control Band', 
						'zIndex' => 0,
						'color'=>  $aColor,  
						'type' => 'areasplinerange', 
						'shadow' => false,
						'yAxis' => $yAxis['index'], 
						'lineWidth' => 0,
						'showInLegend' => true,
						'connectNulls' => false,
						'marker' => array('enabled' => false ),
						'data' => $rangeValues
						);
				$this->_chart['series'][] = $data_series_desc;	
			}
			
			if($showControlZones)
			{
				$controlCount = count($dataset->controlVector);
				$outOfControlWindowStartIndex = NULL;
				$betterThanControlWindowStartIndex = NULL;
				$inControlWindowStartIndex = NULL;
				$lastControl = NULL;
				$times = $dataset->timeVector;
				foreach($dataset->controlVector as $ci => $control)
				{
					
					// The out of control regions
					if($control < $controlPivot) 
					{ 
						if($lastControl >= $controlPivot || $lastControl == NULL) $outOfControlWindowStartIndex = $ci;
					}
					
					if($control >= $controlPivot) 
					{
						if($lastControl < $controlPivot && $outOfControlWindowStartIndex != NULL)
						{ 
							$this->_chart['xAxis']['plotBands'][] = array('from' => $times[$outOfControlWindowStartIndex]*1000, 'to' => $times[$ci]*1000, 'color' =>'#ffaaaa');
						}
						$outOfControlWindowStartIndex = NULL;
					}
					if($outOfControlWindowStartIndex != NULL && $ci ==$controlCount-1)
					{
						$this->_chart['xAxis']['plotBands'][] = array('from' => $times[$outOfControlWindowStartIndex]*1000, 'to' => $times[$ci]*1000, 'color' =>'#ffaaaa');
					}
					
					// the better than control regions
					if($control > 0) 
					{ 
						if($lastControl <= 0 || $lastControl == NULL) $betterThanControlWindowStartIndex = $ci;
					}
					
					if($control <= 0) 
					{
						if($lastControl > 0)
						{ 
							$this->_chart['xAxis']['plotBands'][] = array('from' => $times[$betterThanControlWindowStartIndex]*1000, 'to' => $times[$ci]*1000, 'color' =>'#aaffaa');
						}
						$betterThanControlWindowStartIndex = NULL;
					}
					if($betterThanControlWindowStartIndex != NULL && $ci ==$controlCount-1)
					{
						$this->_chart['xAxis']['plotBands'][] = array('from' => $times[$betterThanControlWindowStartIndex]*1000, 'to' => $times[$ci]*1000, 'color' =>'#aaffaa');
					}
					
					$lastControl = $control;
				}		
				
				if(!isset($this->outOfControlInLegend) )
				{
					$versionValues = array();
					foreach($dataset->versionVector as $i => $v)
					{
						$versionValues[] = array('x' => $dataset->timeVector[$i]*1000.0, 'y' => NULL) ;
					}
					
					$ooc_series_desc = array(
										'name' => 'Out of Control', 
										'yAxis' => $yAxis['index'], 
										'type' => 'area',
										'color' => '#ffaaaa',
										'showInLegend' => !isset($this->outOfControlInLegend),
										'legendIndex' => 100000,
										'data' => $versionValues 
										);
					$this->outOfControlInLegend = true;					
					$this->_chart['series'][] = $ooc_series_desc;
				}
				if(!isset($this->betterThanControlInLegend) )
				{
					$versionValues = array();
					foreach($dataset->versionVector as $i => $v)
					{
						$versionValues[] = array('x' => $dataset->timeVector[$i]*1000.0, 'y' => NULL) ;
					}
					
					$inc_series_desc = array(
										'name' => 'Better than Control', 
										'yAxis' => $yAxis['index'], 
										'type' => 'area',
										'color' => '#aaffaa',
										'showInLegend' => !isset($this->betterThanControlInLegend),
										'legendIndex' => 100001,
										'data' => $versionValues 
										);
					$this->betterThanControlInLegend = true;					
					$this->_chart['series'][] = $inc_series_desc;
				}
				
			}
			$this->_datasetCount++;
		}	
		$this->setDataSource(array('XDMoD App Kernels'));
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
	
	public static function getAppKernelColor($cores)
	{
		$colors = array(1 => 0xf400ff, 
						2 => 0x000fff, 
						4 => 0xaaa222, 
						8 => 0xff0000, 
						16 => 0x11aa11, 
						32 => 0x0070ff, 
						64 => 0xC000FF, 
						128 =>0x0f0f0f);
		
		
		if(isset($colors[$cores]) )
		{
			return $colors[$cores];
		}
		else
		{
			return 0x000000;
		}
	}
	
	public static function getAppKernelSymbol($cores)
	{
		$colors = array(1 => 'circle', 
						2 => 'square', 
						4 => 'diamond', 
						8 => 'triangle', 
						16 => 'triangle-down', 
						32 => 'circle', 
						64 => 'square', 
						128 =>'diamond');
		
		
		if(isset($colors[$cores]) )
		{
			return $colors[$cores];
		}
		else
		{
			return null;
		}
	}
	
	public function getRawImage($format = 'png', $params = array(), $user = NULL)
	{
		$returnData = $this->exportJsonStore();
		
		if( $format == 'img_tag')
		{
			return '<img class="xd-img" alt="'.$this->getTitle().'" width="'.$this->_width*$this->_scale.'" height="'.$this->_height*$this->_scale.'"  class="chart_thumb-img" src="data:image/png;base64,'.base64_encode(\xd_charting\exportHighchart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, 'png')).'" />';
		}
		else
		if ($format == 'svg')
		{
			return \xd_charting\exportHighchart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, 'svg');
		}

		return \xd_charting\exportHighchart($returnData['data'][0], $this->_width, $this->_height, $this->_scale, 'png');
	}
	
	
}

?>