<?php
namespace DataWarehouse\Data;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class holds a set of @\DataWarehouse\Data\TimeseriesData objects.
*/

class TimeseriesDataset extends \DataWarehouse\Data\AggregateDataset
{
	public function __construct( $query_groupname, $realm_name)
	{
		parent::__construct( $query_groupname, $realm_name);
	}
	public function addSeries(\DataWarehouse\Data\TimeseriesData $series)
	{
		parent::addSeries($series);
	}
	
	public function getFilterOptions()
	{
		$filter_options = array();
		$ids = $this->getIds();
		$labels = $this->getDataSeriesNames();
		foreach($ids as $index => $id)
		{
			$filter_options[] = array($id, $labels[$index]);	
		}
		return $filter_options;
	}
	public function getIds()
	{
		if($this->isEmpty()) return array();
		$ids = array();
		
		foreach($this->_data as $data)
		{
			//$data_ids = $data->getIds();
			$ids[] = $data->getId();
		}
		return $ids;
	}
	
	public function truncate($limit)
	{
		if($this->isEmpty()) return;
		$stat = $this->_data[0]->getStatistic()->getAlias();
		$useWeights = strpos($stat, 'avg_') !== false ||
					  strpos($stat, 'count') !== false ||
					  strpos($stat, 'utilization') !== false ||
					  strpos($stat, 'rate') !== false ||
					  strpos($stat, 'expansion_factor') !== false;
		$isMin =   strpos($stat, 'min_') !== false ;
		$isMax =   strpos($stat, 'max_') !== false ;
		
		$dataPerDatasetCount = count($this->_data[0]->getValues());
		$datasetCount = count($this->_data);
		if($datasetCount-1 > $limit)
		{
			$dataname = ($useWeights?'Avg of ':'All ').($datasetCount-$limit).' Others';
			if($isMin)
			{
				$dataname= 'Minimum over all '.($datasetCount-$limit).' others';
			}
			else
			if($isMax)
			{
				$dataname = 'Maximum over all '.($datasetCount-$limit).' others';
			}
			
			$otherSum = new \DataWarehouse\Data\TimeseriesData($dataname,
								$dataname,
								$this->_data[$limit]->getStatistic(), 
								$this->_data[$limit]->getGroupBy(), 
								$this->_data[$limit]->getAggregationUnit(), 
								array(-9999 +(-1*$limit)), 
								$this->_data[$limit]->getLabels(),
								$this->_data[$limit]->getShortLabels(),
								$this->_data[$limit]->getValues(),
								$this->_data[$limit]->getWeights(),
								$this->_data[$limit]->getErrors());
			
			for($i = $limit+1; $i < $datasetCount; $i++)
			{
				$otherSum->add($this->_data[$i]);
				unset($this->_data[$i]);
			}
			
			$this->_data[$limit] = $otherSum;
		}
	}
	
	/*
	public function getDrillTargets()
	{
		if($this->isEmpty()) return array();
		$drill_targets = array();
		
		foreach($this->_data as $data)
		{
			$targets = $data->getGroupBy()->getDrillTargets();
			$drill_targets[] = implode(',',$targets);
		}
		return $drill_targets;
	}*/
	public function getIdNames()
	{
		if($this->isEmpty()) return array();
		$id_names = array();
		
		foreach($this->_data as $data)
		{
			$id_names[] = $data->getIdName();
		}
		return $id_names;
	}
	public function getTimestamps()
	{
		return $this->isEmpty()?array():$this->_data[0]->getTimestamps();
	}
	public function getChartTimes()
	{
		return $this->isEmpty()?array():$this->_data[0]->getChartTimes();
	}
	public function getTimeLabels()
	{
		return $this->isEmpty()?array():$this->_data[0]->getTimeLabels();
	}
	public function getDataSeriesNames($short_names = false)
	{
		$data_names = array();
		foreach($this->_data as $data)
		{
			$data_names[] = ($short_names?$data->getShortName():$data->getName());
		}
		return $data_names;
	}
	public function getDataSeriesNamesAndOverall()
	{
		$ret = array();
		foreach($this->_data as $data)
		{
			$ret[] = array('name' => $data->getName(), 
							'short_name' => $data->getShortName(),
							'value' => $data->getOverall());
		}
		return $ret;
	}
	public function merge(AggregateDataset $dataset)
	{
		if($this->isEmpty() ) 
		{
			$this->_data = $dataset->getDataSeries(false);
			return;
		}else
		{
			/*$data0 = $this->_data[0];

			$ids0 = $data0->getIds();
			$labels0 = $data0->getLabels();
			
			if($dataset->isEmpty()) return;
			$dataset1 = $dataset->getDataSeries(false);

			$data1 = $dataset1[0];
			
			$newValues = array();
			$newWeights = array();
			
			foreach($ids0 as $key => $id)
			{
				$label = $labels0[$key];
				$newValues[] = $data1->getValue($id);
				$newWeights[] = $data1->getWeight($id);
			}
			$series = new \DataWarehouse\Data\AggregateData($data1->getName(),
															$data1->getStatistic(),
															$data1->getGroupBy(),
															$data1->getAggregationUnit(),
															$ids0,
															$labels0,
															$newValues,
															$newWeights);
		
			$this->addSeries($series);*/
		}
		
	}
	public function export($query = NULL)
	{
		$headers = array();
		$headers[] = 'Date';
		$rows = array();
		$duration_info = array('start' => $this->getStartDate(), 'end' => $this->getEndDate());
		$title = array('title' => 'Title');
		$title2 = array('parameters' => '');
		
		foreach($this->_data as $dataseries)
		{
			
			$dataseries_stat_unit = $dataseries->getStatistic()->getUnit();
			$dataseries_stat_name = $dataseries->getStatistic()->getAlias()->getName();
			$dataseries_stat_label = $dataseries->getStatistic()->getLabel();
			$dataseries_name = $dataseries->getName();
			$dataseries_group_name = $dataseries->getGroupBy()->getName();
			
			$data_unit = '';
			if(substr($dataseries_stat_unit , -1 ) == '%')
			{
				$data_unit = '%';
			}
			$data_max = 1;
			$data_min = 0;
			$dataseries->getMinMax($data_min, $data_max);
			$decimals = $dataseries->getStatistic()->getDecimals($data_min, $data_max);
				
			$id = $dataseries->getIds();
			$id = $id[0];
			$headers[] =  '['.$dataseries_name.'] '.$dataseries_stat_label;
									
			$title['title'] = $this->getTitle(false,$query);
			$title2['parameters'] = $query->parameterDescriptions;
			$values = $dataseries->getValues();
			$labels = $dataseries->getTimeLabels();
			
			foreach($labels as $index => $label)
			{
				$value = $values[$index];
				$date_label =  $label;
				if(!isset($rows[$label]))
				{
					$rows[$label] = array();
					$rows[$label][] = $date_label;
				}
				$rows[$label][] = $value.$data_unit;
			}
			if($dataseries->hasErrors())
			{
				$err_name = 'sem_'.$dataseries_stat_name;
				$headers[] = '['.$dataseries_name.'] Std Err of '.$dataseries_stat_label;
				$errors = $dataseries->getErrors();
				
				foreach($labels as $index => $label)
				{
					$error = $errors[$index];
					$rows[$label][] = $error;
				}
			}
		}
		
		
		
		return array( 'duration' => $duration_info,
					'headers' => $headers,
					 'rows' => $rows,
					 'title' => $title,
					 'title2' => $title2);
	}
	public function exportJsonStore($limit = 20)
	{
		//build the filter list before the export truncates the dataset
		$filter_options = json_encode($this->getFilterOptions());
		
		$this->truncate($limit);
		$fields = array();
		$count = -1;
		$records = array();
		$columns = array();	
		$subnotes = array();
		$message = '';	
		if(!$this->isEmpty()) 
		{
			
			if($this->_data[0]->getGroupBy()->getName() == 'resource')
			{
				$subnotes[] = '* Resources marked with asterisk are not providing per job processor counts, hence affecting the accuracy of the following statistics: Job Size and CPU Consumption';
			}
			
			$fields[] =  array("name" => 'date', "type" => 'string');
			$columns[] = array("header" => 'Date', "width" => 155, "dataIndex" => 'date', 
								"sortable" => true, 'editable' => false, 'align' => 'left','xtype' => 'gridcolumn', 'locked' => true);
			
			$stats_noted = array();
			foreach($this->_data as $dataseries)
			{
				$dataseries_stat_unit = $dataseries->getStatistic()->getUnit();
				$dataseries_stat_name = $dataseries->getStatistic()->getAlias()->getName();
				$dataseries_stat_label = $dataseries->getStatistic()->getLabel();
				$dataseries_name = $dataseries->getName();
				$dataseries_group_name = $dataseries->getGroupBy()->getName();
				
				$data_unit = '';
				if(substr($dataseries_stat_unit , -1 ) == '%')
				{
					$data_unit = '%';
				}
				$data_max = 1;
				$data_min = 0;
				$dataseries->getMinMax($data_min, $data_max);
				$decimals = $dataseries->getStatistic()->getDecimals($data_min, $data_max);
				if(!isset($stats_noted[$dataseries->getStatistic()->getLabel()]))
				{
					$message .= '<li>'.$dataseries->getStatistic()->getDescription($dataseries->getGroupBy()).'</li>';
					$stats_noted[$dataseries->getStatistic()->getLabel()] = true;
				}
				$id = $dataseries->getIds();
				$id = $id[0];
				$fields[] =  array("name" => $id, "type" => 'float');
				$columns[] = array("header" => '['.$dataseries_name.'] '.$dataseries_stat_label, "width" => 250, "dataIndex" => $id, 
								"sortable" => true, 'editable' => false, 'align' => 'right', 'xtype' => 'numbercolumn', 'format' => ($decimals>0?'0,000.'.str_repeat(0,$decimals):'0,000').$data_unit);
							
				$values = $dataseries->getValues();
				$labels = $dataseries->getTimeLabels();
				
				foreach($values as $key => $value)
				{
					if(!isset($records[$key]))
					{
						$records[$key] = array();
					}
					$records[$key]['date'] =  $labels[$key]; 
					$records[$key][$id] = $value;	
				}
				$count = count($values);
				if($dataseries->hasErrors())
				{
					$err_name = 'sem_'.$dataseries_stat_name;
					$fields[] =  array("name" => $err_name.$id , "type" => 'float');
					$columns[] = array("header" => '['.$dataseries_name.'] Std Err of '.$dataseries_stat_label, "width" => 280, "dataIndex" => $err_name.$id , 
									"sortable" => true, 'editable' => false, 'align' => 'right', 'xtype' => 'numbercolumn', 'format' => $decimals>0?'0,000.'.str_repeat(0,$decimals+2):'0,000');
					$errors = $dataseries->getErrors();
					
					foreach($errors as $key => $error)
					{
						if(!isset($records[$key]))
						{
							$records[$key] = array();
						}
						$records[$key][$dataseries_group_name] = $labels[$key]; 
						$records[$key][$err_name.$id] = $error;	
					}
				}
			}
			$message =  '<li>'.$this->_data[0]->getGroupBy()->getDescription().'</li>'.$message;
			
		}
		$returnData = array
		(
			"metaData" => array("totalProperty" => "total", 
								'messageProperty' => 'message',
								"root" => "records",
								"id" => "id",
								"fields" => $fields
								),
			"success" => true,
			'message' =>$message,
			"total" => $count,
			"records" => $records,
			"columns" => $columns,
			'filter_options' => $filter_options,
			'subnotes' => $subnotes
		); 
		
		return $returnData;
	}
	
}
?>