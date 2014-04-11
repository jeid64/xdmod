<?php
  // namespace DataWarehouse\Visualization;

class CustomHighChart extends DataWarehouse\Visualization\HighChart2
{
  
  public function __construct($aggregation_unit, 
                              $start_date, 
                              $end_date, $scale, $width, $height, $swap_xy = false)
  {
    parent::__construct($aggregation_unit, 
                        $start_date, 
                        $end_date, $scale,$width,$height, $swap_xy);
  }  // __construct()
  
  // ----------------------------------------------------------------------------------------------------

  public function configure(&$data_series,
                            &$request, 
                            $font_size,
                            $inMillions,
                            $limit = NULL, $offset = NULL
    )
  {
    // Maximum number of pie slices
    $maxSlices = $limit; 8;
    $title = $data_series->getName();
    $axisTitle = "Research Funding";

    $this->show_filters = isset($request['show_filters'])? $request['show_filters'] == 'y' || $request['show_filters'] == 'true' : true; 

    $this->_chart['credits'] = array('text' => $this->_startDate . " to " . $this->_endDate);
    $this->_chart['title']['text'] = $title;
    $this->_chart['title']['style'] = array('color'=> '#000000', 'fontSize' => (16 + $font_size).'px');
    $this->_chart['subtitle']['style'] = array('fontSize' => (12 + $font_size).'px');
    $this->_chart['subtitle']['y'] = 30+ $font_size;
    $this->_chart['legend']['itemStyle'] = array('fontSize' => (12  + $font_size).'px');
    $this->_chart['tooltip']['shared'] = false;

    //$this->_chart['legend']['itemMarginBottom'] = 16 + $font_size;
		
    $colors = \ChartFactory::getColors(33);
    $colors = array_reverse($colors);

    $count = 1;
    $yAxisCount = 1; // count($yAxisArray);
    $yAxisIndex = 0;
    $color_index = 0;

    $yAxisColorValue = $colors[$color_index % 33];
    $yAxisColor = '#'.str_pad(dechex($yAxisColorValue),6,'0',STR_PAD_LEFT);

    $yAxis = array('title' => array('text' => $axisTitle, 'style' => array('color'=> $yAxisColor, 'fontSize' => (12 + $font_size).'px')), 
                   'labels' => array( 'style' => array('fontSize' => (11 + $font_size).'px')),
                   'opposite' => $yAxisIndex % 2 == 1,
                   'min' => 0,
                   'type' => 'linear',
                   'showLastLabel' => $this->_chart['title']['text'] != '',//$data_description->log_scale?true:false,
                   'gridLineWidth' => $yAxisCount > 1 ?0: 1,											 
                   'endOnTick' => true,// $data_description->log_scale?true:false,
                   'lineWidth' => 2 + $font_size/4,
                   'allowDecimals' => true,
                   'tickInterval' => null,
                   'maxPadding' => min(0.6,0.30*($yAxisIndex) + (0.1) + (0))
      );

    $this->_chart['yAxis'][] = $yAxis; 

    $decimals = ($inMillions ? 2 : 0 );

    $color_value = $colors[$color_index++ % 33];
    $color = '#'.str_pad(dechex($color_value),6,'0',STR_PAD_LEFT);	

    $dataLabelsConfig = array(
      'enabled' => true,
      'style' => array('fontSize' => (11 + $font_size).'px',
                       'color' => $color )
      );
    $tooltipConfig = array('pointFormat' => "{series.name}: {point.y} <b>({point.percentage}%)</b> ",
                           'percentageDecimals' => 1,
                           'valueDecimals' => $decimals
      );
     
    $count = 0;
    $total = 0;
    $otherTotal = 0;
    $values = array();
    
    foreach( $data_series as $index => $series)
    {
      $seriesValue = (float) $series->sumValues();
      $total += $seriesValue;

      if ( ($count + 1) >= $maxSlices )
      {
        $otherTotal += $seriesValue;
      }
      else
      {
        $values[] = array('name' => $series->getName(),
                          'y' => $seriesValue,
                          'color' => '#'.str_pad(dechex($colors[$count % 33]),6,'0',STR_PAD_LEFT));
      }
      $count++;
    }

    // Only add the "All Others" category if there is data that requires it

    if ( 0 != $otherTotal )
    {
      $values[] = array('name' => "All Others",
                        'y' => $otherTotal,
                        'color' => '#' . str_pad(dechex($colors[($count+1) % 33]), 6, '0', STR_PAD_LEFT));
    }
    
    $values_count = count($values);
    
    $dataLabelsConfig = array_merge($dataLabelsConfig,
                                    array('color' => '#000000',
                                          'formatter' => "var maxL = " . floor($this->_width*($limit<11 ? 30 : 15)/580) . ";
														 var x = ((this.point.name.length>maxL+3) ? this.point.name.substring(0,maxL-3)+'...' : this.point.name);
														 return '<b>'+x.wordWrap( " . floor($this->_width*($limit<11 ? 15 : 15)/580) . ",'</b><br/><b>')+'</b><br/>" . ($inMillions ? "$" : "" ) . "' + Highcharts.numberFormat(this.y, $decimals) + ' " . ($inMillions ? "M" : "" ) . "<br/>' + Highcharts.numberFormat(100*this.y/$total, $decimals) + '%';"));

    $categories = $data_series->getSeriesNames();

    if ( $otherTotal > 0 && count($categories) >= $maxSlices )
    {
      $categories = array_slice($categories, 0, $maxSlices - 1);
      $categories[] = "All Others";
    }

    $this->_total = count($categories);

    $this->_chart['xAxis'] = array('title' => array('text' => "Agency", 
                                                    'margin' => 15 + $font_size, 
                                                    'style' => array('color'=> '#000000',
                                                                     'fontSize' => (12 + $font_size).'px')
                                     ),
                                   'labels' => array('enabled' => true,
                                                     'rotation' => -90,
                                                     'align' => 'center', 
                                                     'step' => 0,
                                                     'style' => array('fontSize' => (11 + $font_size).'px' , 'line-height' =>1+$font_size/40), 
                                                     'formatter' => " 
														 var maxL = ".floor($this->_height*($limit<11?30:15)/400).";
														 var x  = (this.value.length>maxL-3)?this.value.substring(0,maxL-3)+'...':this.value;
																	 return x.wordWrap(".floor($this->_height*($limit<11?18:18)/400).",'<br/>');
																	 "
                                     ),
                                   'lineWidth' => 2 + $font_size/4,
                                   'categories' => $categories
      );

    $data_series_desc = array(
      'name' => str_replace('style=""','style="color:'.$yAxisColor.'"', "Total Research"), 
      'zIndex' => 3, 
      'color'=> NULL,
      'type' => "pie",
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

    $this->_chart['series'][] = $data_series_desc;

    if($this->_chart['title']['text'] == '' && $this->_chart['subtitle']['text'] != '') 
    {
      $this->_chart['title']['text'] = $this->_chart['subtitle']['text'];
      $this->_chart['subtitle']['text'] = '';
    }
		
  }  // configure()
	
}

?>