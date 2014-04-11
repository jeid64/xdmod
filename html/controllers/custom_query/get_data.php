<?php
require_once('common.php');

require_once('HighRoller.php');
require_once('HighRollerSeriesData.php');
require_once('HighRollerPieChart.php');
require_once('XDMoDHighChart.php');

use CCR\DB;

/*
--
-- Supported by xsede
--

drop procedure if exists jtowns.custom_xsede_res_support;
delimiter //
create procedure jtowns.custom_xsede_res_support(
  in start_date date,
  in end_date date)
begin

-- Proposals active during the desired time period

create temporary table tmp_xdcdb_proposals as
select
distinct xdcdb_proposal_number
from jtowns.xdcdb_grant_activity
where xdcdb_proposal_number is not null
and (start_activity_date between start_date and end_date
     or end_activity_date between start_date and end_date);

select series, data/1000000 as data from
(
  SELECT agency as series, sum(calc_dollar) as data
  FROM jtowns.pops_supp_grant_adj
  join tmp_xdcdb_proposals on xdcdb_proposal_number = pops_grant_num
  WHERE supp_start_date <= end_date and supp_end_date >= start_date
  group by agency
) a order by data desc;

end
//
delimiter ;

--
-- Directly supported byt xsede (percentage award)
--

drop procedure if exists jtowns.custom_xsede_direct_res_support;
delimiter //
create procedure jtowns.custom_xsede_direct_res_support(
  in start_date date,
  in end_date date)
begin

-- Proposals active during the desired time period

create temporary table tmp_xdcdb_proposals as
select
distinct xdcdb_proposal_number
from jtowns.xdcdb_grant_activity
where xdcdb_proposal_number is not null
and (start_activity_date between start_date and end_date
     or end_activity_date between start_date and end_date);

select series, data/1000000 as data from
(
  SELECT agency as series, sum(calc_percentage_dollar) as data
  FROM jtowns.pops_supp_grant_adj
  join tmp_xdcdb_proposals on xdcdb_proposal_number = pops_grant_num
  WHERE supp_start_date <= end_date and supp_end_date >= start_date
  group by agency
) a order by data desc;

end
//
delimiter ;

--
-- SUs delivered based on funding agency
--

drop procedure if exists jtowns.custom_xsede_res_delivered;
delimiter //
create procedure jtowns.custom_xsede_res_delivered(
  in start_date date,
  in end_date date)
begin

-- Proposals active during the desired time period

create temporary table tmp_xdcdb_proposals as
select
xdcdb_proposal_number, sum(total_su) as total_su
from xdcdb_grant_activity
where xdcdb_proposal_number is not null
and (start_activity_date between start_date and end_date or end_activity_date between start_date and end_date)
group by xdcdb_proposal_number;

-- Calculate total funding dollars for all supporting grant that overlap the desired date range for each
-- pops grant number.  Then, calculate the fraction of the total associated with each funding agency
-- under that pops grant.

create temporary table tmp_supp_grants as
select pops_grant_num, agency, total_dollars, sum(calc_dollar) / a.total_dollars * 100 as percentage
from pops_supp_grant_adj
join (
  select pops_grant_num, sum(calc_dollar) as total_dollars
  from pops_supp_grant_adj
  where supp_start_date <= end_date and supp_end_date >= start_date
  and calc_dollar is not null
  group by pops_grant_num
) a using(pops_grant_num)
where supp_start_date <= end_date and supp_end_date >= start_date
group by pops_grant_num, agency;

-- Join the supplemental grants with projects that saw activity in xdcdb and calculate the percentage
-- SU attributed to each funding agency.

select * from
(
select agency as series, sum(total_su * percentage)/1000000 as data
from tmp_xdcdb_proposals
join tmp_supp_grants on xdcdb_proposal_number = pops_grant_num
group by agency
) a order by data desc;

end
//
delimiter ;
*/

// --------------------------------------------------------------------------------

$returnData = array();

try
{
  
  // The UI can provide a json object containing configuration info.  If a config option was
  // provided decode it and merge the optionsinto the request array.

	if (isset($_REQUEST['config']))
	{
		$config = json_decode($_REQUEST['config'],true);
		$_REQUEST = array_merge($config, $_REQUEST);
	}
   
	$format = \DataWarehouse\ExportBuilder::getFormat($_REQUEST,
                                                     'png',
                                                     array('svg', 'png', 'png_inline', 'svg_inline', 'xml', 'csv', 'jsonstore', 'hc_jsonstore'));
	

   $user = \xd_security\detectUser(array(XDUser::INTERNAL_USER, XDUser::PUBLIC_USER));
   
   
   // Verify the correct role

   if ( ! ( in_array('mgr', $user->getRoles()) || in_array('po', $user->getRoles()) ) )
   {
     throw new Exception("Access Denied");
   }

	list($start_date, $end_date, $start_ts, $end_ts) = checkDateParameters();
	if ( $start_ts > $end_ts ) throw new Exception('End date must be greater than or equal to start date');
	
	$inline = getInline();
	$aggregation_unit = getAggregationUnit();
	$timeseries = getTimeseries();
   $limit = getLimit();
	$offset = getOffset();
	$title = getTitle();
	$subtitle = ( isset($_REQUEST['subtitle']) ? $_REQUEST['subtitle'] : null );

	$filename = 'xdmod_'.($title != ''?$title:'untitled').'_'.$start_date.'_to_'.$end_date;
	$filename = substr($filename, 0, 100);
	
	if ( $format === 'hc_jsonstore' ||
        $format === 'png' ||
        $format === 'svg' ||
        $format === 'png_inline' ||
        $format === 'svg_inline' )
	{
		$width = getWidth();
		$height = getHeight();
		$scale = getScale();
		$swap_xy = getSwapXY();
		
		$legend_location = getLegendLocation();
		
		$font_size = getFontSize();
      $isDollars = true;
      $isMillions = true;
      $decimals = 2;
      $queryKey = $_REQUEST['query'];
      
      switch ( $queryKey )
      {
      case 'q1':
        $sql = "call mod_custom.custom_xsede_res_support(:start_date, :end_date);";
        break;
        
      case 'q2':
        $sql = "call mod_custom.custom_xsede_direct_res_support(:start_date, :end_date);";
        break;
        
      case 'q3':
        $sql = "call mod_custom.custom_xsede_res_delivered(:start_date, :end_date);";
        $isDollars = false;
        break;

      case 'q4':
        $sql = "call mod_custom.custom_xsede_nsf_support(:start_date, :end_date);";
        break;

      case 'q5':
        $sql = "call mod_custom.custom_xsede_nsf_support_mps(:start_date, :end_date);";
        break;

      case 'q6':
        $sql = "call mod_custom.custom_mps_award_count(:start_date, :end_date);";
        $isDollars = false;
        $isMillions = false;
        $decimals = 0;
        break;

      case 'q7':
        $sql = "call mod_custom.custom_xsede_nsf_support_awdsearch(:start_date, :end_date);";
        break;

      default:
        throw new Exception("Invalid query or query not selected");
      }  // switch ( $queryKey)

      try
      {
        $results = DB::factory('datawarehouse')->query($sql, array(':start_date' => $start_date, ':end_date' => $end_date));
      } catch ( \PDOException $e ) {
        print_r($e);
      }
      
      // Create an object with the axes that we want to plot. Use SimpleDataset for the series and name each one.
      
      $options = array(
        'title' => $title,
        'subtitle' => $subtitle,
        'decimals' => $decimals,
        'font_size' => $font_size,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'limit' => $limit,
        'width' => $width,
        'is_dollars' => $isDollars,
        'is_millions' => $isMillions,
        );
      
      $piechart = XDMoDHighChart::factory("pie", $options);
      XDMoDHighChart::setLegendLocation($piechart, $legend_location);
       
      $seriesData = array();
      $maxSlices = $limit;
      $count = 0;
      $otherTotal = 0;

      $colors = \ChartFactory::getColors(33);
      $colors = array_reverse($colors);
      
      foreach ($results as $key => $row) 
      {
        if ( ($count + 1) >= $maxSlices )
          $otherTotal += (float) $row['data'];
        else
          $seriesData[] = array('name' => $row['series'],
                                'y' => (float) $row['data'],
                                'color' => '#' . str_pad(dechex($colors[$count % 33]), 6, '0', STR_PAD_LEFT));
        $count++;
      }

      if ( 0 != $otherTotal )
        $seriesData[] = array('name' => "All Others",
                              'y' => $otherTotal,
                              'color' => '#' . str_pad(dechex($colors[$count % 33]), 6, '0', STR_PAD_LEFT));

      $hrseries = new HighRollerSeriesData();
      $hrseries->addData($seriesData);  // ->addName("Dollars");
      $hrseries->marker = array('lineWidth' => 1, 'radius' => $font_size/4 + 5 );
      $hrseries->tooltip->pointFormat = "{series.name}: {point.y} <b>({point.percentage:.1f}%)</b>";
      $piechart->addSeries($hrseries);

      $returnData = array(
        'totalCount' => count($piechart->series),
        'success' => true, 
        'message' => 'success', 
        'data' => array((array) json_decode($piechart->getChartOptionsObject()))
        );

		$requestDescripter = new \User\Elements\RequestDescripter($_REQUEST);
		$chartIdentifier = $requestDescripter->__toString();
		$chartPool = new \XDChartPool($user);
		$includedInReport = $chartPool->chartExistsInQueue($chartIdentifier, $title);
      
		$returnData['data'][0]['reportGeneratorMeta'] = array(
        'chart_args' => $chartIdentifier,
        'title' => $title,
        'params_title' =>  "", // $piechart->subtitle->text,
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

}
catch(Exception $ex)
{
	$returnData = array(
			'totalCount' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
}

if(isset($format) &&( $format == 'hc_jsonstore' ||$format == 'jsonstore' || $format ==  'session_variable'  ))
{	
	\DataWarehouse\ExportBuilder::writeHeader($format);
	print json_encode($returnData);
	
}
?>
