<?php
namespace DataWarehouse;

class VisualizationBuilder
{
	private static $_self = NULL;

	public static function getInstance()
	{
		if(self::$_self == NULL)
		{ 
			self::$_self = new VisualizationBuilder();
		}
		return self::$_self;
	}
	
	public static $plot_action_formats = array('session_variable', /*'params',*/ 'png', 'png_inline', 'img_tag', 'svg');
	public static $display_types = array("bar", "h_bar", "line", "pie", 'area'	);
	public static $combine_types = array("side", "percentage", "stack", "overlay"	);
	
	private function __construct()
	{
		
	}
	

	
	public static function getLimit(array &$request)
	{
		$limit = 20;
		if(isset($request['limit']))
		{
			$limit = intval($request['limit']);
			if($limit == 0 && $request['limit'] != '0') $limit = 20; //use default auto value of the request is not a number, this includes 'auto'
		}
		return $limit;
	}
	
	public function buildVisualizationFromQuery(\DataWarehouse\Query\Query $query, &$request, \XDUser &$user, $format = 'session_variable')
	{
		$debug_level = 0;
		if(isset($request['debug_level']))
		{
			$debug_level = abs(intval($request['debug_level']));
		}
		
		$thumbnail = false;
		if(isset($request['thumbnail']))
		{
			 $thumbnail = $request['thumbnail'] === true || $request['thumbnail'] === 'y';
		}

		$show_title = false;
		if(isset($request['show_title']))
		{
			$show_title = $request['show_title'] == 'true' || $request['show_title'] === 'y';
		}
		$show_guide_lines = true;
		if(isset($request['show_guide_lines']))
		{
			$show_guide_lines = $request['show_guide_lines'] == 'true' || $request['show_guide_lines'] === 'y';
		}
		
		//deprecated
		$show_legend = true;
		if(isset($request['show_legend']))
		{
			 $show_legend = $request['show_legend'] == 'true' || $request['show_legend'] === 'y';
		}
		//replaces show_legend
		$legend_location = isset($request['legend_type']) && $request['legend_type'] != '' ? $request['legend_type']: 'bottom_center';
		
		$swap_xy = isset($request['swap_xy'])?$request['swap_xy'] == 'true' || $request['swap_xy'] === 'y': false;
		
		$font_size = isset($request['font_size']) && $request['font_size'] != '' ? $request['font_size']: 'default';
		
		$show_gradient = true;
		if(isset($request['show_gradient']))
		{
			 $show_gradient = $request['show_gradient'] == 'true' || $request['show_gradient'] === 'y';
		}
		
		$log_scale = false;
		if(isset($request['log_scale']))
		{
			 $log_scale = $request['log_scale'] == 'true' || $request['log_scale'] === 'y';
		}
		
		$show_error_bars = true;
		if(isset($request['show_error_bars']))
		{
			$show_error_bars = $request['show_error_bars'] == 'true' || $request['show_error_bars'] === 'y';
		}
		
		$show_trend_line = true;
		if(isset($request['show_trend_line']))
		{
			$show_trend_line = $request['show_trend_line'] == 'true' || $request['show_trend_line'] === 'y';
		}
		$show_aggregate_labels = false;
		if(isset($request['show_aggregate_labels']))
		{
			 $show_aggregate_labels = $request['show_aggregate_labels'] == 'true' || $request['show_aggregate_labels'] === 'y';
		}	
		$show_error_labels = false;
		if(isset($request['show_error_labels']))
		{
			 $show_error_labels = $request['show_error_labels'] == 'true' || $request['show_error_labels'] === 'y';
		}	
		$interactive_elements = false;
		if(isset($request['interactive_elements']))
		{
			 $interactive_elements = $request['interactive_elements'] == 'true' || $request['interactive_elements'] === 'y';
		}
		
		$scale = 1.0;
		if(isset($request['scale'])) $scale = $request['scale'];				
		$width = 740;
		if(isset($request['width'])) $width = intval($request['width']);
		$height = 345;
		if(isset($request['height'])) $height = intval($request['height']);		
		
		$display_type = 'auto';
		if(isset($request['display_type']))
		{
			$display_type = $request['display_type'];
		}
		$combine_type = 'auto';
		if(isset($request['combine_type']))
		{
			$combine_type = $request['combine_type'];
		}
		
		$offset = 0;
		if(isset($request['offset']))
		{
			$offset = intval($request['offset']);
		}
		
		$limit = self::getLimit($request);
		
		
		
		$dataset_type = $query->getQueryType();
		
		$subnotes = array(
		'* Resources marked with asterisk do not provide processor counts per job when submitting to the '.ORGANIZATION_NAME.' Central Database. This affects the accuracy of the following (and related) statistics: Job Size and CPU Consumption'
		);
		
		
		srand(\DataWarehouse\VisualizationBuilder::make_seed());


		$dataset = $query->getDataset();
		
		$data = $dataset->getDataSeries();
		
		if($dataset_type == 'aggregate')
		{
			if(/*$display_type == 'line' || */$display_type == 'area')
			{
				$display_type = $dataset->isEmpty()?'h_bar':$data[0]->getGroupBy()->getDefaultDisplayType($dataset_type);
				$combine_type = 'stack';
			}
			else
			if($display_type == 'auto')
			{
				$display_type = $dataset->isEmpty()?'h_bar':$data[0]->getGroupBy()->getDefaultDisplayType($dataset_type);
			}
			if($combine_type == 'auto')
			{
				$combine_type = 'stack';
			}
		}else
		{
			if($display_type == 'h_bar' || $display_type == 'pie')
			{
				$display_type = $dataset->isEmpty()?'line':$data[0]->getGroupBy()->getDefaultDisplayType($dataset_type);
			}else
			if($display_type == 'auto')
			{
				$display_type = $dataset->isEmpty()?'line':$data[0]->getGroupBy()->getDefaultDisplayType($dataset_type);
			}
			if($combine_type == 'auto')
			{
				$combine_type = 'stack';
			}
		}
		
		$first_data_group = $dataset->isEmpty()?'':$data[0]->getGroupBy()->getName();
		$first_data_name = $dataset->isEmpty()?'':$data[0]->getStatistic()->getAlias();
		
		
		$highchart_classname = $dataset instanceof \DataWarehouse\Data\TimeseriesDataset? '\DataWarehouse\Visualization\HighChartTimeseries2' : '\DataWarehouse\Visualization\HighChart2';		
				
		if($display_type == 'h_bar') 
		{
			$swap_xy = true;
		}
		$hc = new $highchart_classname($query->getAggregationUnit()->getUnitName(), $query->getStartDate(), $query->getEndDate(), $scale, $width, $height, $swap_xy);
		
		$filter_options = json_encode($dataset->getFilterOptions());
		
			
		
		$hc->setTitle($show_title?(isset($request['title'])?$request['title']:$query->getTitle(false)):null); 	
		
		$hc->setLegendLocation(/*$width < \ChartFactory::$thumbnail_width?'off': */$legend_location);//called before and after
		$hc->configure2($query, $dataset, $user, $combine_type, $display_type, $log_scale, $show_aggregate_labels, $show_error_labels, $show_error_bars, $show_trend_line, $show_guide_lines,
						$font_size,
						!$thumbnail,
						$limit,
						$offset
						);
		$hc->setLegendLocation(/*$width < \ChartFactory::$thumbnail_width?'off': */$legend_location);
		$hc->setSubtitle($show_title?wordwrap($query->getTitle2(),($scale*$width)/($font_size+6),"<br />\n"):null);
		$returnData  = $hc->exportJsonStore($limit, $offset);

		$requestDescripter = new \User\Elements\RequestDescripter($request);
		$chartIdentifier = $requestDescripter->__toString();
		
		$subnotes = array();
		if(!$dataset->isEmpty() && $first_data_group == 'resource' || isset($request['resource']))
		{
			$subnotes[] = '* Resources marked with asterisk do not provide processor counts per job when submitting to the '.ORGANIZATION_NAME.' Central Database. This affects the accuracy of the following (and related) statistics: Job Size and CPU Consumption';
		}

		$chartPool = new \XDChartPool($user);

		if($format == 'session_variable')
		{
			$vis = array(
				'hc_jsonstore' => $returnData['data'][0],
				'query_time' => $debug_level>0?$query->query_time:'',
				'query_string' => $debug_level>0?$query->query_string:'',
				
				'title' => $query->getTitle(),
				'params_title' => $query->getTitle2(),
				'comments' => 'comments',//$chart->getComments(),
				'chart_args' => $chartIdentifier,
				
				'reportGeneratorMeta' => array(
				  'included_in_report' => ($chartPool->chartExistsInQueue($chartIdentifier)) ? 'y' : 'n'
				),
				
				'short_title' => $query->getShortTitle(),//$chart->getShortTitle(),
				'random_id' => 'chart_'.rand(),

				'subnotes' => $subnotes,
				'single_stat' => $query->_single_stat,
				
				'filter_options' => $filter_options,
				'group_description' => $query->groupBy()->getDescription(),
				'description' => $query->getMainStatisticField()->getDescription($query->groupBy()).
							($query->getMainStatisticField() != null && $dataset->hasErrors() && ($show_error_labels || $show_error_bars)
								?('<br/>'.$query->getStatistic('sem_'.$query->getMainStatisticField()->getAlias())->getDescription($query->groupBy())):'' ),
				
				'id' => 'statistic_'.$query->getRealmName().'_'.$query->groupBy()->getName().'_'.$query->getMainStatisticField()->getAlias(),
				'menu_id' => 'group_by_'.$query->getRealmName().'_'.$query->groupBy()->getName(),
				'realm' => $query->getRealmName(),
				
				'start_date' => $query->getStartDate(),
				'end_date' => $query->getEndDate(),
				'aggregation_unit' => $query->getAggregationUnit()->getUnitName(),
				
				'chart_settings' => str_replace('"','`',json_encode(
					array(
						'dataset_type' => $dataset_type,
						'display_type' => $display_type,
						'combine_type' => $combine_type,
						'show_legend' => false,//$show_legend,
						'show_guide_lines' => $show_guide_lines,
						'log_scale' => $log_scale,
						'limit' => $limit, 
						'offset' => $offset,
						'show_trend_line' => $show_trend_line,
						'show_error_bars' => $show_error_bars,
						'show_aggregate_labels' => $show_aggregate_labels,
						'show_error_labels' => $show_error_labels,
						'enable_errors' => $dataset->countDataSeries() == 1 && $data[0]->hasErrors() && !$log_scale,
						'enable_trend_line' => $dataset->countDataSeries() == 1 && $dataset_type == 'timeseries'
						)
				)),	
				
				'show_gradient' => $show_gradient,
				'final_width' => $scale * $width,
				'final_height' => $scale * $height
			);
			
			return $vis;
		}else if($format == 'svg')
		{
			return \xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'svg');
		}else
		{
			return \xd_charting\exportHighchart($returnData['data'][0], $width, $height, $scale, 'png');
		}
	}
	
	public function buildVisualizationsFromQueries(array &$queries, &$request, \XDUser &$user, $format = NULL)
	{		
	
		if($format == NULL)
		{
			$format = \DataWarehouse\ExportBuilder::getFormat($request, \DataWarehouse\ExportBuilder::getDefault(self::$plot_action_formats), self::$plot_action_formats);
		}
		$visualizations = array();		
		foreach($queries as $query)
		{
			if( $query->getMainStatisticField()->isVisible()) // so the std err charts dont appear by themselves in the thumbnail view.
			{
				$visualizations[] = $this->buildVisualizationFromQuery($query,$request,$user, $format);
			}
		} 
		
		return $visualizations;
	}
	
	public static function make_seed()
	{
	  list($usec, $sec) = explode(' ', microtime());
	  return (float) $sec + ((float) $usec * 100000);
	}
}
?>