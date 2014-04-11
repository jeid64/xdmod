<?php
class XDMoDHighChart
{
  public $chart = null;

  public static function factory($type, array $options)
  {
    $classname = null;
    switch($type)
    {
      case 'pie':
        $classname = "HighRollerPieChart";
      break;
    default:
      throw new Exception("Invalid chart type: '$type'");
      break;
    }

    $chart = new $classname;
    self::initialize($chart, $options);
    return $chart;
  }  // factory()

  // ================================================================================

  public static function initialize(HighRoller &$chart,
                                    array $options)
  {
    $fontSize = $options['font_size'];
    $decimals = $options['decimals'];
    $startDate = $options['start_date'];
    $endDate = $options['end_date'];
    $limit = $options['limit'];
    $width = $options['width'];
    $isMillions = $options['is_millions'];
    $isDollars = $options['is_dollars'];

    $chart->title->text =  $options['title'];
    $chart->title->text =  $options['title'];
    $chart->title->style->fontSize = (16 + $fontSize) . "px";
    $chart->title->style->color = "#000000";
    
    if ( array_key_exists("subtitle", $options) )
    {
      $chart->subtitle->text =  $options['subtitle'];
      $chart->subtitle->style->fontSize = (12 + $fontSize) . "px";
      $chart->subtitle->style->color = "#000000";
      $chart->subtitle->y = 30 + $fontSize;
    }
    
    $chart->credits =  array('text' => "$startDate to $endDate. Powered by XDMoD/Highcharts", 'href' => '');
    $chart->xAxis->categories = array();


    $chart->tooltip = array(
      'crosshairs' => true,
      'shared' => false,
      'xDateFormat' => '%Y-%m-%d',
      'pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b>',
	  'valueDecimals' => $decimals,
      'percentageDecimals' => 1);
    $chart->dimensions = array();
    $chart->metrics = array();
    $chart->exporting = array('enabled' => false);
    $chart->plotOptions->series = array(
      'allowPointSelect' => false,
      'connectNulls' => false);
    $chart->plotOptions->pie->dataLabels = array(
      'enabled' => true,
      'color' => "#000000",
      'formatter' => "
var maxL = " . floor($width * ($limit < 11 ? 30 : 15) / 580) . ";
var x = ((this.point.name.length>maxL+3) ? this.point.name.substring(0,maxL-3)+'...' : this.point.name);
return '<b>'+x.wordWrap( " . floor($width * ($limit < 11 ? 15 : 15) / 580) . ",'</b><br/><b>')+'</b><br/>" . ($isDollars ? "$" : "" ) . "' + Highcharts.numberFormat(this.y, $decimals) + ' " . ($isMillions ? "M" : "" ) . "<br/>' + Highcharts.numberFormat(this.percentage, $decimals) + '%';"
      );
    $chart->plotOptions->pie->showInLegend = true;
  
    $chart->legend->itemStyle = array('fontSize' => (12  + $fontSize).'px');
    $chart->legend->backgroundColor = '#FFFFFF';
    $chart->legend->borderWidth = 0;
    $chart->legend->y = -5;
    $chart->legend->enabled = true;
    $chart->legend->labelFormatter ="
var ret = ''; 
var x = this.name; 
var indexOfSQ = x.indexOf(']');
var brAlready = false;
if ( indexOfSQ > 0)
{
  ret += x.substring(0,indexOfSQ+1)+'<br/>';
  x = x.substring(indexOfSQ+1,x.length);
  brAlready = true;
}
var indexOfBr = x.indexOf('{');
if ( indexOfBr > 0 && !brAlready)
{
  ret += x.substring(0,indexOfBr)+'<br/>';
  x = x.substring(indexOfBr,x.length);
}
ret += x.wordWrap(50,'<br/>');
return ret;";
  
  }  // initialize()
  
  // ================================================================================
  // Set up the legend configuration based on the string pass in.  The string must be in the form
  // <floating>_<alignment>_<vertical alignment> where <floating> is optional.  For example
  // "bottom_center", "right_bottom", "floating_bottom_center"
  // ================================================================================
  
  public static function setLegendLocation(HighRoller &$chart, $location)
  {
     $alignment = null;
     $vAlignment = null;
     $float = null;
     $layout = "vertical";
     $xAdjust = null;
     $yAdjust = null;
     $margin = null;

     $split = explode("_", $location);
     switch ( count($split) )
     {
     case 1:
       $chart->legend->enabled = false;
       break;
     case 2:
       list($alignment, $vAlignment) = $split;
       break;
     case 3:
       list($float, $alignment, $vAlignment) = $split;
       break;
     }

     switch($vAlignment)
     {
       case 'center':
         $vAlignment = "middle";
         break;
         default:
           break;
     }

     if ( "bottom" == $alignment )
     {
       $alignment = "center";
       $vAlignment = null;
       $layout = null;
       $margin = 15;
     }

     if ( null !== $float )
     {
       if ( $alignment == "left" ) $xAdjust = 80;
       else if ( $alignment == "right" ) $xAdjust = -10;

       if ( ("right" == $alignment && "bottom" == $valignment) ||
            ("left" == $alignment && "bottom" == $valignment) ||
            ("bottom" == $alignment && "middle" == $valignment) )
         $yAdjust = -100;
       else if ( ("top" == $alignment && "middle" == $valignment) ||
                 ("top" == $alignment && "left" == $valignment) ||
                 ("right" == $alignment && "top" == $valignment) )
         $yAdjust = 70;
     }
     
     $chart->legend->align = $alignment;
     $chart->legend->floating = ( "floating" == $float);

     if ( ! ("bottom" == $alignment && "middle" == $vAlignment) )
       $chart->spacingBottom = 25;
     if ( ! ("right" == $alignment &&
             ("middle" == $vAlignment || "top" == $vAlignment || "bottom" == $vAlignment) ) )
       $chart->spacingRight = 20;

     if ( null !== $xAdjust )
       $chart->legend->x = $xAdjust;
     if ( null !== $yAdjust )
       $chart->legend->y = $yAdjust;
     if ( null !== $margin )
       $chart->legend->margin = $margin;
     if ( null !== $layout )
       $chart->legend->layout = $layout;
     if ( null !== $vAlignment )
       $chart->legend->verticalAlign = $vAlignment;

  }  // setLegendLocation()
  
}  // class XDMoDHighChart
