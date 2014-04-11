<?php
namespace DataWarehouse\Data;


class ComplexDataset
{
	public $_dataDescripters = array();
	public $_total;
	public function __construct()
	{
		$this->_total = 1;
	}
	
	public function addDataset($data_description, \DataWarehouse\Data\SimpleDataset &$dataset)
	{
		$this->_dataDescripters[] = (object)array('data_description' => $data_description, 'dataset' => $dataset);
	}
	
	public function getXAxis($force_reexec = false, $limit = NULL, $offset = NULL)
	{
		if(!isset($this->xAxisDataObject) || $force_reexec === true) 
		{
			$names = array();
			$this->xAxisDataObject = new \DataWarehouse\Data\SimpleData('');
			//$this->xAxisDataObject->setName($dataObjectName);
			//$this->xAxisDataObject->groupName = $value;
			$this->xAxisDataObject->unit = 'X Axis';
			$sort_type = 'none';
			foreach($this->_dataDescripters as $dataDescripterAndDataset)
			{
				//if($dataDescripterAndDataset->data_description->display_type == 'pie') continue;
				if($dataDescripterAndDataset->data_description->sort_type !== 'none') $sort_type = $dataDescripterAndDataset->data_description->sort_type;
				$subXAxisObject = $dataDescripterAndDataset->dataset->getColumn('dim_'.$dataDescripterAndDataset->data_description->group_by/*, $limit, $offset*/);
				$names[$subXAxisObject->getName()] = $subXAxisObject->getName();
				$yAxisDataObject = $dataDescripterAndDataset->dataset->getColumn('met_'.$dataDescripterAndDataset->data_description->metric/*,$limit, $offset*/);
				foreach ($subXAxisObject->values as $index => $label)
				{
					$order = $subXAxisObject->order_ids[$index];
					$value = $yAxisDataObject->values[$index];
					if(!isset($this->xAxisDataObject->values[$label])) $this->xAxisDataObject->values[$label] = array('label' => $label, 'order' => $order, 'value' => $value);
				}
				//print_r( $this->xAxisDataObject->values);
			}
			$values = array();
			$orders = array();
			foreach($this->xAxisDataObject->values as $key => $vArray)
			{
				$values[$key] = $vArray['value'];
				$orders[$key] = $vArray['order'];
			}
			
			//echo $sort_type;
			//print_r( $this->xAxisDataObject->values);
			switch ($sort_type)
			{
				case 'value_asc':
				array_multisort($values, SORT_ASC, $this->xAxisDataObject->values );
				break;
				case 'value_desc':
				array_multisort($values, SORT_DESC, $this->xAxisDataObject->values );
				break;
				case 'none':
				case 'label_asc':
				array_multisort($orders, SORT_ASC, $this->xAxisDataObject->values );
				break;
				case 'label_desc':
				array_multisort($orders, SORT_DESC, $this->xAxisDataObject->values );
				break;		
			}
			$labels = array();
			foreach($this->xAxisDataObject->values as $value )
			{
				$labels[] = $value['label'];
			}
			$this->xAxisDataObject->values =  $labels;
			$this->_total = count($this->xAxisDataObject->values);
		}
		$this->xAxisDataObject->setName(implode(' / ',array_unique($names)));
		$this->xAxisDataObject->values = array_slice($this->xAxisDataObject->values,$offset,$limit);
		return $this->xAxisDataObject;
	}
	
	public function getYAxis($limit = NULL, $offset = NULL, $shareYAxis = false)
	{
		$this->getXAxis(true,$limit,$offset);
		
		$yAxisArray = array();
		
		foreach($this->_dataDescripters as $data_description_index => $dataDescripterAndDataset)
		{	
			$data_description = $dataDescripterAndDataset->data_description;
			
			$query_classname = '\\DataWarehouse\\Query\\'.$data_description->realm.'\\Aggregate';
			$stat = $query_classname::getStatistic($data_description->metric);
			
			if($shareYAxis) 
				$axisId = 'sharedAxis';
			else
				$axisId = /*$data_description->realm.'_'.*/$stat->getUnit().'_'.$data_description->log_scale.'_'.($data_description->combine_type == 'percent');
				
			if(!isset($yAxisArray[$axisId])) $yAxisArray[$axisId] = array();
			
			$yAxisArray[$axisId][] = $dataDescripterAndDataset;
			
		}
		//print_r($this->xAxisDataObject);
		$returnYAxis = array();
		foreach(array_values($yAxisArray) as $yAxisIndex => $yAxisDataDescriptions)
		{
			$yAxisObject = new \stdClass;
			$yAxisObject->series = array();
			$yAxisObject->decimals = 0;		
			$yAxisObject->std_err = false;
			$yAxisObject->value_labels = false;
			$yAxisObject->log_scale = false;
			
			foreach($yAxisDataDescriptions as $dataDescripterAndDataset)
			{	
				$yAxisObject->title =   ($dataDescripterAndDataset->data_description->combine_type=='percent'? '% of ':'').$dataDescripterAndDataset->dataset->getColumnUnit($dataDescripterAndDataset->data_description->metric,false);
				
				$statisticObject = $dataDescripterAndDataset->dataset->_query->_stats[$dataDescripterAndDataset->data_description->metric];
				$subXAxisObject = $dataDescripterAndDataset->dataset->getColumn('dim_'.$dataDescripterAndDataset->data_description->group_by, $limit, $offset);
				//print_r($subXAxisObject);
						
				$yAxisDataObject = $dataDescripterAndDataset->dataset->getColumn('met_'.$dataDescripterAndDataset->data_description->metric,$limit, $offset);
				
				$filterParametersTitle = $data_description->long_legend == 1?$dataDescripterAndDataset->dataset->_query->getFilterParametersTitle():'';
				
				if($filterParametersTitle != '')
				{
					$filterParametersTitle = ' {'.$filterParametersTitle.'}' ;
				}
				
				//print_r($yAxisDataObject);
				//print_r( $this->xAxisDataObject->values);
				if($this->xAxisDataObject->getCount() <=0) continue;
				$newValues = array_fill(0,$this->xAxisDataObject->getCount(), NULL);
				if($dataDescripterAndDataset->data_description->std_err)$newErrors = array_fill(0,$this->xAxisDataObject->getCount(), NULL);
				foreach($subXAxisObject->values as $xIndex => $xValue)
				{
					$found = array_search($xValue,$this->xAxisDataObject->values, true);
				//	echo $found, ' ', $xValue, '\n'; 
					if($found !== FALSE)
					{
						$newValues[$found] = $yAxisDataObject->values[$xIndex];
						if($dataDescripterAndDataset->data_description->std_err)$newErrors[$found] = $yAxisDataObject->errors[$xIndex];
					}
				}
			
				//print_r($newValues);
				$yAxisObject->std_err = $dataDescripterAndDataset->data_description->std_err || $yAxisObject->std_err;
				$yAxisObject->value_labels = $dataDescripterAndDataset->data_description->value_labels || $yAxisObject->value_labels;
				$yAxisObject->log_scale = $dataDescripterAndDataset->data_description->log_scale || $yAxisObject->log_scale;
				
				$decimals = $statisticObject->getDecimals();
				$yAxisObject->decimals = max($yAxisObject->decimals,$decimals);
				$yAxisDataObject->values = $newValues;
				if($dataDescripterAndDataset->data_description->std_err)
				{
					$yAxisDataObject->errors = $newErrors;
					$yAxisDataObject->getErrorCount(true);
					$semStatisticObject = $dataDescripterAndDataset->dataset->_query->_stats['sem_'.$dataDescripterAndDataset->data_description->metric];
					$semDecimals = $semStatisticObject->getDecimals();	
					}
				
				$yAxisObject->series[] = array('yAxisDataObjet' => $yAxisDataObject, 'data_description' => $dataDescripterAndDataset->data_description, 'decimals' => $decimals, 
												'semDecimals' => isset($semDecimals)?$semDecimals:0,
												'filterParametersTitle' => $filterParametersTitle);
				
			}
			
			$returnYAxis[$yAxisIndex] = $yAxisObject;
		}
		
		return $returnYAxis;
	}
	public function getTitle2()
	{
		return 'Title';
	}
	public function getTotalPossibleCount()
	{
		return 1;
	}
	
	public function getResults($limit = NULL, $offset = NULL, $force_reexec = false)
	{
		return array();
	}
	
	public function extractColumnLabel($column_type_and_name)
	{
		return 'columnLabel';
	}
	
	public function getColumnLabel($column_name, $is_dimension)
	{
		return 'columnLabel';
	}
	public function getColumnUnit($column_name, $is_dimension)
	{
		return 'columnUnit';
	}
	public function getColumn($column_type_and_name,$limit = NULL,$offset= NULL)
	{
		
		$dataObject = new \DataWarehouse\Data\SimpleData($column_type_and_name);
	
		return $dataObject;
	}
	public function export($export_title = 'title')
	{		
		$headers = array();
		$rows = array();
		$duration_info = array('start' => $this->_query->getStartDate(), 'end' => $this->_query->getEndDate());
		$title = array('title' => 'None');
		$title2 = array('parameters' => array());
		
		$count = $this->getTotalPossibleCount();
			
		$results = $this->getResults();
		$result_count = count($results);
				
		if($result_count > 0)
		{
			$title['title'] = $export_title;
			$title2['parameters'] = $this->_query->parameterDescriptions;
			$group_bys = $this->_query->getGroupBys();
			
			$stats = $this->_query->getStats();
			$has_stats = count($stats) > 0;
						
			foreach($group_bys as $group_by)
			{
				$headers[] = $group_by->getName()==='none'?'Summary':$group_by->getLabel();							
			}
			
			foreach($stats as $stat)
			{
				$stat_unit = $stat->getUnit();
				$stat_alias = $stat->getAlias()->getName();
				
				$data_unit = '';
				if(substr( $stat_unit, -1 ) == '%')
				{
					$data_unit = '%';
				}
				$column_header = $stat->getLabel();//.'<br>'.$stat_unit;
				if($column_header != $stat_unit && strpos($column_header, $stat_unit) === false) $column_header .= ' ('.$stat_unit.')';
				$headers[] = $column_header;
			}
			foreach($results as $result)
			{
				$record = array();				
				foreach($group_bys as $group_by)
				{
					$record[$group_by->getName()] =  $result[$group_by->getShortNameColumnName(true)];						
				}
				$stats = $this->_query->getStats();
				foreach($stats as $stat)
				{
					$record[$stat->getAlias()->getName()] =  $result[$stat->getAlias()->getName()];						
				}
				$rows[] = $record;
			}		
		}
	
		return array('title' => $title,
					 'title2' => $title2,
					'duration' => $duration_info,
					'headers' => $headers,
					 'rows' => $rows);
	}

	public function exportJsonStore($limit = NULL, $offset = NULL)
	{
		$fields = array();
		$count = -1;
		$records = array();
		$columns = array();	
		$subnotes = array();
		$sortInfo = array();
		$message = '';
		$count = $this->_query->getCount();
			
		$results = $this->getResults($limit,$offset,false);
		$result_count = count($results);
		

		if($result_count > 0)
		{
			$group_bys = $this->_query->getGroupBys();
			$stats = $this->_query->getStats();
			$has_stats = count($stats) > 0;
			
			
			foreach($group_bys as $group_by)
			{
				$fields[] =  array("name" => $group_by->getName(), "type" => 'string', 'sortDir' => 'DESC');
				$columns[] = array("header" => $group_by->getName()==='none'?'Source':$group_by->getLabel(), "width" => 150, "dataIndex" => $group_by->getName(), 
								"sortable" => true, 'editable' => false, 'locked' => $has_stats);								
			}
			
			foreach($stats as $stat)
			{
				$stat_unit = $stat->getUnit();
				$stat_alias = $stat->getAlias()->getName();
				
				$data_unit = '';
				if(substr( $stat_unit, -1 ) == '%')
				{
					$data_unit = '%';
				}
				$column_header = $stat->getLabel();//.'<br>'.$stat_unit;
				if($column_header != $stat_unit && strpos($column_header, $stat_unit) === false) $column_header .= ' ('.$stat_unit.')';
				/*
				$data_max = 1;
				$data_min = 0;
				$dataseries->getMinMax($data_min, $data_max);*/
				$decimals = $stat->getDecimals(/*$data_min, $data_max*/);
				
				$fields[] =  array("name" => $stat_alias, "type" => 'float', 'sortDir' => 'DESC');
				$columns[] = array("header" => $column_header, "width" => 140, "dataIndex" => $stat_alias, 
								"sortable" => true, 'editable' => false, 'align' => 'right', 'xtype' => 'numbercolumn', 'format' => ($decimals>0?'0,000.'.str_repeat(0,$decimals):'0,000').$data_unit);								
			}
			foreach($results as $result)
			{
				$record = array();				
				foreach($group_bys as $group_by)
				{
					$record[$group_by->getName()] =  $result[$group_by->getLongNameColumnName(true)];						
				}
				$stats = $this->_query->getStats();
				foreach($stats as $stat)
				{
					$record[$stat->getAlias()->getName()] =  $result[$stat->getAlias()->getName()];						
				}
				$records[] = $record;
			}	
			
			$query_orders = $this->_query->getOrders();
			foreach($query_orders as $query_order)
			{
				$sortInfo = array('field' => $query_order->getColumnName(), 'direction' => $query_order->getOrder() );
			}
		}
		else
		{
			$message = 'Dataset is empty';
			$fields = array(array("name" => 'Message', "type" => 'string'));
			$records = array(array('Message' => $message));
			$columns = array(array("header" => 'Message', "width" => 600, "dataIndex" => 'Message', 
								"sortable" => $sortable, 'editable' => false, 'align' => 'left', 'renderer' => "CCR.xdmod.ui.stringRenderer"));
		}

		$returnData = array
		(
			"metaData" => array("totalProperty" => "total", 
								'messageProperty' => 'message',
								"root" => "records",
								"id" => "id",
								"fields" => $fields,
								"sortInfo" => $sortInfo
								),
			'message' => '<ul>'.$message.'</ul>',
			"success" => true,
			"total" => $count,
			"records" => $records,
			"columns" => $columns
		); 
		
		return $returnData;
	}
}
?>