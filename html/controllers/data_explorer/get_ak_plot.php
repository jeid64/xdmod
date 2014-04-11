<?php
require_once('common.php');

$returnData = array();

try
{

   $user = \xd_security\detectUser(array(XDUser::INTERNAL_USER));

   $ak_db = new \AppKernel\AppKernelDb();
	 
	$selectedResourceIds = getSelectedResourceIds();
	$selectedProcessingUnits = getSelectedPUCounts();
	$selectedMetrics = getSelectedMetrics();
	$expandedAppKernels = getExpandedAppKernels();
	$showChangeIndicator = getShowChangeIndicator();
	$format = \DataWarehouse\ExportBuilder::getFormat($_REQUEST, 'png', array('svg', 'png', 'png_inline', 'svg_inline', 'xml', 'csv', 'jsonstore', 'hc_jsonstore'));
	$inline = true;
	if(isset($_REQUEST['inline']))
	{
		$inline = $_REQUEST['inline'] == 'true' || $_REQUEST['inline'] === 'y';
	}	
	
	list($start_date, $end_date, $start_ts, $end_ts) = checkDateParameters();
	
	$limit = getLimit();
	$offset = getOffset();
	
	$show_title = getShowtitle();
	$title = getTitle();
	
	$width = getWidth();
    $height = getHeight();
    $scale = getScale();
	$swap_xy = getSwapXY();
	$legend_location = getLegendLocation();
	
	$show_guide_lines = getShowGuideLines();
	$font_size = getFontSize();
	
	$datasets = array();
	
	foreach($selectedMetrics as $metric)
	{
		foreach($selectedResourceIds as $resourceId)
		{
			
			if(preg_match('/ak_(?P<ak>\d+)_metric_(?P<metric>\d+)_(?P<pu>\d+)/', $metric, $matches))
			{
				$akId = $matches['ak'];
				$metricId = $matches['metric'];
				$puCount = $matches['pu'];
				
				if(count($selectedProcessingUnits) == 0 || in_array($puCount, $selectedProcessingUnits))
				{
					$datasetList = $ak_db->getDataset($akId,
							  $resourceId,
							  $metricId,
							  $puCount,
							  $start_ts, 
							  $end_ts, 
							  false,
							  false,
							  true, 
							  false);
				
					foreach($datasetList as $result)
					{				
						$datasets[] = $result;
					}
				}
				
			}else
			if(preg_match('/ak_(?P<ak>\d+)_metric_(?P<metric>\d+)/', $metric, $matches))
			{
				$akId = $matches['ak'];
			 	$metricId = $matches['metric'];	
				
				if(count($selectedProcessingUnits) == 0)
				{
					$pus = $ak_db->getProcessingUnits($start_date,$end_date,  
														$selectedResourceIds, $selectedMetrics);
					foreach($pus as $pu)
					{
						$selectedProcessingUnits[] = $pu->count;
					}
				}
				
				foreach($selectedProcessingUnits as $puCount)
				{
					$datasetList = $ak_db->getDataset($akId,
								  $resourceId,
								  $metricId,
								  $puCount,
								  $start_ts, 
								  $end_ts, 
								  false,
								  false,
								  true, 
								  false);
					
					foreach($datasetList as $result)
					{
						$datasets[] = $result;
					}
				}
			 
			}

		}
	}
	
	
	$filename_kernels = array();
	$filename_resources = array();
	$filename_processing_units = array();
	$filename_metrics = array();
	foreach($datasets as $result)
	{
		$filename_kernels[$result->akName] = $result->akName;
		$filename_resources[$result->resourceName] = $result->resourceName;
		$filename_metrics[$result->metric] = $result->metric;
	}
	$filename = 'data_explorer_'.$start_date.'_to_'.$end_date.'_'.implode('_',$filename_resources).'_'.implode('_',$filename_kernels).'_'.implode('_',$filename_metrics);
	
	$filename = substr($filename,0,100);
	if($format === 'hc_jsonstore' || $format === 'png' || $format === 'svg' || $format === 'png_inline' || $format === 'svg_inline')
	{
		$hc = new \DataWarehouse\Visualization\HighChartAppKernel($start_date, $end_date, $scale, $width, $height, $swap_xy);
		$hc->setTitle($show_title?($title?$title:implode(', ',$filename_resources).'; '.implode(', ',$filename_kernels).'; '.implode(', ',$filename_metrics)):NULL);
		$hc->setLegendLocation($legend_location);//called before and after
		$hc->configure($datasets,						
						$font_size,
						$limit,
						$offset,
						$format === 'svg',
						false,
						false,
						true,
						$showChangeIndicator
						);
		$hc->setLegendLocation($legend_location);
				
		$message = NULL;
		
		if(count($selectedMetrics) < 1)
		{
			$message = "<- Select a metric from the left";
		} else
		if(count($selectedResourceIds) < 1) 
		{
			$message = "<- Select a resource from the left";
		}
		 
		
		if($message !== NULL)
		{
			$hc->setTitle($message);
		}
		$returnData = $hc->exportJsonStore();
		
		
		$requestDescripter = new \User\Elements\RequestDescripter($_REQUEST);
		$chartIdentifier = $requestDescripter->__toString();
		$chartPool = new \XDChartPool($user);
		$includedInReport = $chartPool->chartExistsInQueue($chartIdentifier, $title);
		
		$returnData['data'][0]['reportGeneratorMeta'] = array(
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
	else
	if($format === 'csv' || $format === 'xml')
	{
		$exportedDatas = array();
		foreach($datasets as $result)
		{
			$exportedDatas[] = $result->export();
		}
						
		\DataWarehouse\ExportBuilder::export($exportedDatas,$format,$inline, $filename);
		exit;
	}

}
catch(Exception $ex)
{
	print_r($ex);
	$returnData = array(
			'totalCount' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
}

if(isset($format) && ($format == 'hc_jsonstore'))
{	
	\DataWarehouse\ExportBuilder::writeHeader($format);
	print json_encode($returnData);
	
}
?>