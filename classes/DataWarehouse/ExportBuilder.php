<?php
namespace DataWarehouse;

/* 
* @author Amin Ghadersohi
* @date 2011-Apr-07
*
* Singleton class for helping with data export 
* 
*/
class ExportBuilder
{
	private static $_self = NULL;

	public static function getInstance()
	{
		if(self::$_self == NULL)
		{
			self::$_self = new DataExport();
		}
		return self::$_self;
	}


	private function __construct()
	{
		
	}
	public static $supported_formats = array (
		'xls' => array('render_as' => 'application/vnd.ms-excel', 'destination' => 'attachment'),
		'xml' => array('render_as' => 'text/xml',                 'destination' => 'attachment'),
		'png' => array('render_as' => 'image/png',                 'destination' => 'attachment'),
		'png_inline' => array('render_as' => 'image/png',                 'destination' => 'inline'),
		'svg_inline' => array('render_as' => 'image/svg+xml',                 'destination' => 'inline'),
		'eps' => array('render_as' => 'image/eps',     'destination' => 'attachment'),
		'svg' => array('render_as' => 'image/svg+xml',                 'destination' => 'attachment'),
		'csv' => array('render_as' => 'application/xls', 				'destination' => 'attachment'),
		'jsonstore' => array('render_as' => 'text/plain', 				'destination' => 'inline'),
		'hc_jsonstore' => array('render_as' => 'text/plain', 				'destination' => 'inline'),
		'json' => array('render_as' => 'text/plain', 				'destination' => 'inline'),
		'session_variable' => array('render_as' => 'text/plain', 				'destination' => 'inline'),
		'params' => array('render_as' => 'text/plain', 				'destination' => 'inline'),
		'img_tag' => array('render_as' => 'text/html', 				'destination' => 'inline'),
		'html' => array('render_as' => 'text/html', 				'destination' => 'inline')
	);
	
	public static $dataset_action_formats = array('json','jsonstore','xml','xls','csv', 'html');
	
	public static function getDefault(array $arr = array())
	{
		$count = count($arr);
		if($count > 0) return $arr[0];
		throw new \Exception('No default format could be assigned');
	}

	public static function getHeader($format, $forceInline = false, $filename = 'data')
	{
		$filename = str_replace(array(' ', '/', '\\','?','%','*',':','|','"','<','>','.', '\n', '\t', '\r'),'_',$filename);
		$headers = array();
		
		if (isset(self::$supported_formats[$format]))
		{
			$headers['Content-type'] = self::$supported_formats[$format]['render_as'];
			if (self::$supported_formats[$format]['destination'] == 'attachment' && !$forceInline){
				$headers['Content-Disposition'] = 'attachment; filename="'.$filename.'.'.$format.'"';
			}
			
		}
		else{
			$headers['Content-type'] = 'text/plain';
			$headers['Content-Disposition'] = 'attachment; filename="'.$filename.'.'.$format.'"';
		}
		return $headers;
	}
		
	public static function writeHeader($format, $forceInline = false, $filename = 'data')
	{
		$headers = self::getHeader($format,$forceInline, $filename);
		
		foreach($headers as $k => $v)
		{
			header("$k: $v");
		}
	}
	
	public static function getFormat(&$request, $default = 'jsonstore', $formats_subset = array())
	{
		$format = $default;
		if(isset($request['format']) )
		{
			$f = strtolower($request['format']);
		
			if( isset(self::$supported_formats[$f]) && 
			    (
				(count($formats_subset) == 0 )
			    ||
				(count($formats_subset) > 0 && array_search($f,$formats_subset) !== false ))
				
				 )
			{
				
				$format = $f;
			}
		}
		return $format;
	}
	public static function buildExport(array &$queries = array(), array &$request,  \XDUser &$user, $format )
	{
		$returnData = array();
		$inline = false;
		if(isset($request['inline']))
		{
			$inline = $request['inline'] == 'true' || $request['inline'] === 'y';
		}
		$query_count = count($queries);
		if($query_count > 0)
		{
			$dataset = $queries[0]->getDataset();
			
			if($format == 'jsonstore' && count($queries) > 1 && $dataset instanceof \DataWarehouse\Data\TimeseriesDataset) 
			{
				$returnData = array
				(
					"metaData" => array("totalProperty" => "total", 
										"root" => "records",
										"id" => "id",
										"fields" => array(array("name" => 'Message', "type" => 'string'))
										),
					"success" => true,
					"message" => 'Datasheet view is not available for timeseries. Turn off timeseries or use the Export button to get the data.',
					"total" => 1,
					"records" => array(array('Message' => 'Datasheet view is not available for timeseries. Turn off timeseries or use the Export button to get the data.')),
					"columns" => array(array("header" => 'Error Message', "width" => 600, "dataIndex" => 'Message', 
								"sortable" => true, 'editable' => false, 'align' => 'left', 'renderer' => "CCR.xdmod.ui.stringRenderer"))
				); 
			}else
			{
				if($format == 'png' || $format == 'png_inline' || $format == 'svg' || $format == 'eps' )
				{

               $chart_title = str_replace('%','Percent', $dataset->getTitle($query_count>1, $queries[0] ))
			   									.'_'.$queries[0]->getStartDate().'_to_'.$queries[0]->getEndDate()
												.'_'.$request['dataset_type'];					


					if ($format == 'eps') {

                  $chart = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationFromQuery($queries[0], $request, $user, 'png');

                  $chart_title = str_replace(' ', '_', $chart_title);
                  \xd_charting\convertPNGStreamToEPSDownload($chart, $chart_title);

                  exit;					

					}
					else {

					$chart = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationFromQuery($queries[0], $request, $user, $format);
										
			    	\DataWarehouse\ExportBuilder::writeHeader($format, $inline, $chart_title);
					echo $chart; //todo: if multiple charts zip them into a file
					
					}
					
					
				}else
				if($format == 'img_tag')
				{
					$chart = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationFromQuery($queries[0], $request, $user, $format);
					echo $chart;
				}
				else
				if($format == 'jsonstore')
				{
					for($i = 1; $i < count($queries); $i++)
					{	
						$dataset->merge( $queries[$i]->getDataset());
					}
					
					$returnData = $dataset->exportJsonStore(\DataWarehouse\VisualizationBuilder::getLimit($request));	
					
				}else
				{
					$exportedDatas = array();

					if($dataset instanceof \DataWarehouse\Data\TimeseriesDataset)
					{
						for($i = 0; $i < $query_count ; $i++)
						{	
							$exportedDatas[] = $queries[$i]->getDataset()->export($queries[0] );
						}
					}
					else
					{
						for($i = 1; $i < $query_count ; $i++)
						{	
							$dataset->merge( $queries[$i]->getDataset());
						}
						$exportedDatas[] = $dataset->export($queries[0] );
					}

					$data_title = str_replace('%','Percent',$dataset->getTitle($query_count>1, $queries[0] ));

						
					$returnData = self::export($exportedDatas,$format, $inline, $data_title.' '.$queries[0]->getStartDate().' to '.$queries[0]->getEndDate().'_'.$request['dataset_type']	);
				}
			}
		}
		return $returnData;
	}
	public static function export(array $exportedDatas = array(), $format, $inline = true, $filename = 'data')
	{
		$returnData = array();
		$fp;
		$xml;
		if($format == 'csv' || $format == 'xls')
		{
			self::writeHeader($format, $inline, $filename);
			$fp = fopen('php://output', 'w');	
		}else if($format == 'xml')
		{
			self::writeHeader($format, $inline, $filename);
			$xml = new \XMLWriter();
			$xml->openURI('php://output');
			$xml->startDocument();
			$xml->startElement('xdmod-xml-dataset');
		} 
		
		foreach($exportedDatas as $exportedData)
		{
			$headers = $exportedData['headers'];
			$rows = $exportedData['rows'];
			$duration = $exportedData['duration'];
			$title = $exportedData['title'];
			
			$parameters = isset($exportedData['title2'])?$exportedData['title2']:array();
			
			if($format == 'csv' || $format == 'xls')
			{
				fputcsv($fp, array_keys($title));
				fputcsv($fp, $title);
				if(count($parameters) > 0)
				{
					fputcsv($fp, array_keys($parameters));
					foreach($parameters as $parameters_label => $params)
					{
					fputcsv($fp, $params);
					}
				}
				fputcsv($fp, array_keys($duration));
				fputcsv($fp, $duration);
				fputcsv($fp, array('---------'));
				fputcsv($fp, $headers);
				foreach($rows as $row)
				{
					fputcsv($fp, $row);
				}
				fputcsv($fp, array('---------'));		
				continue;
			}
			else if($format == 'xml')
			{						
				$xml->startElement('header');
					foreach($title as $title_label => $title_element)
					{
						$xml->startElement(self::formatElement($title_label));
						$xml->text($title_element);
						$xml->endElement();
					}
					foreach($parameters as $parameters_label => $params)
					{
						$xml->startElement(self::formatElement($parameters_label));
						foreach($params as $parameter)
						{
							$xml->startElement('parameter');
								$parameter_parts = explode('=',$parameter);
								$xml->startElement('name');
								$xml->text($parameter_parts[0]);
								$xml->endElement();
								$xml->startElement('value');
								$xml->text($parameter_parts[1]);
								$xml->endElement();
							$xml->endElement();
						}
						$xml->endElement();
					}
					foreach($duration as $duration_label => $duration_element)
					{
						$xml->startElement(self::formatElement($duration_label));
						$xml->text($duration_element);
						$xml->endElement();
					}
					$xml->startElement('columns');
					foreach($headers as $header)
					{
						$xml->startElement('column');
						$xml->text($header);
						$xml->endElement();
					}
					$xml->endElement();
				$xml->endElement();
				

				
				$xml->startElement('rows');	
				foreach($rows as $row)
				{
					$xml->startElement('row');
					
					foreach($row as $index => $cell)
					{
						$xml->startElement('cell');
						if(isset($headers[$index]))
						{	
							$xml->startElement('column');
							$xml->text($headers[$index]);
							$xml->endElement();	
						}
						$xml->startElement('value');
						$xml->text($cell);
						$xml->endElement();	
						$xml->endElement();	
					}
					$xml->endElement();	
				}
				$xml->endElement();	
				
				
				continue;
			}else if($format == 'json')
			{
				$returnData[] = array('title' => $title, 'parameters' => $parameters, 'duration' => json_encode($duration), 'headers' => json_encode($headers), 'rows' =>  json_encode($rows));
			}else if($format == 'html')
			{
				$returnData[] = '<table border="1">';
				
				foreach($exportedDatas as $exportedData)
				{
					$headers = $exportedData['headers'];
					$returnData[] = '<tr><th>'.implode('</th><th>',$headers).'</th></tr>';
					break;
				}
				foreach($exportedDatas as $exportedData)
				{
					$rows = $exportedData['rows'];
					foreach($rows as $row)
					{
						$returnData[] = '<tr><td>'.implode('</td><td>',$row).'</td></tr>';
					}
					break;
				}
				$returnData[] = '</table>';
				//$returnData[] = array('title' => $title, 'parameters' => $parameters, 'duration' => json_encode($duration), 'headers' => json_encode($headers), 'rows' =>  json_encode($rows));
			}
		}
		if($format == 'xml')
		{
			$xml->endElement();
			$xml->endDocument();
		}
		return $returnData;
	}
	
	public static function formatElement(&$title_label)
	{
		$title_label = str_replace(' ','_',$title_label);
		$title_label = str_replace(',','',$title_label);
		$title_label = str_replace(':','',$title_label);
		$title_label = str_replace('.','',$title_label);
		return $title_label;
	}

}
?>