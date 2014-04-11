<?php
namespace DataWarehouse\Data;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class holds a set of @\DataWarehouse\Data\AggregateData objects.
*/

class AggregateDataset
{
	protected $_start_date;
	protected $_end_date;
	
	public function getShortTitle()
	{
		if($this->isEmpty()) return 'Empty Dataset';
		$data = $this->getDataSeries(false);
		return $data[0]->getStatistic()->getLabel();
	}
	public function getTitle($group_info_only = false, $query = NULL)
	{
		if($this->isEmpty()) return 'Empty Dataset';
		$data = $this->getDataSeries(false);
		$group_label = $data[0]->getGroupBy()->getLabel();
		return $group_info_only? 
				$group_label.' stats'.($data[0]->getGroupBy()->getName()==='none'?
						' Summary':
						': by '.$data[0]->getGroupBy()->getLabel()): 
			 /*'['.$group_label.'] '.*/$data[0]->getStatistic()->getLabel().($data[0]->getGroupBy()->getName()=='none'?'':': by '.$data[0]->getGroupBy()->getLabel());
	}
	public function getTitle2($query = NULL)
	{
		if($query != NULL)
		{
			return implode(" -- ",array_unique($query->parameterDescriptions));
		}
		return '';
	}
	public function getGroupBy()
	{
		if($this->isEmpty()) return NULL;
		return $this->_data[0]->getGroupBy();
	}
	public function getFilterOptions()
	{
		$filter_options = array();
		if($this->isEmpty()) return $filter_options;
		
		
		$ids = $this->_data[0]->getIds();
		$labels = $this->_data[0]->getLabels();
		foreach($ids as $index => $id)
		{
			$filter_options[] = array($id, $labels[$index]);	
		}
		return $filter_options;
	}
	
	public function getStartDate()
	{
		return $this->_start_date;
	}
	public function getEndDate()
	{
		return $this->_end_date;
	}
	public function setStartDate($start_date)
	{
		$this->_start_date = $start_date;
	}
	public function setEndDate($end_date)
	{
		$this->_end_date = $end_date;
	}	
	public function getAggregationUnit()
	{
		if($this->isEmpty()) return;
		
		$data = $this->getDataSeries(false);
		return $data[0]->getAggregationUnit();
	}
	public function durationInDays()
	{
		$dateDifferenceResult = \DataWarehouse::connect()->query("select (unix_timestamp( :end_date) - 
                unix_timestamp(:start_date))/3600.00 
                as date_difference_hours", array('start_date' => $this->_start_date,
                'end_date' => $this->_end_date));

            $date_difference_hours = $dateDifferenceResult[0]['date_difference_hours'];
          return $date_difference_hours/24.0;
	}
	protected $_data = array();
	
	public function __construct( $query_groupname, $realm_name)
	{
		$this->setRealmName($realm_name);
		$this->setQueryGroupname($query_groupname);
	}
	
	public function addSeries(\DataWarehouse\Data\AggregateData $series)
	{
		$this->_data[] = $series;
	}
	
	public function __toString()
	{
		return get_class($this)."{ \n\t"
				. implode("\n\t",$this->_data)
				. "\n"
				. "}\n";
	}
	public function getMinMax(&$data_min, &$data_max)
	{
		$data_maxes = array();
		$data_mins = array();
		foreach($this->_data as $data)
		{
			$data_maxes[] = max($data->getValues());
			$data_mins[] = min($data->getValues());
		}
		
		$data_max = count($data_maxes)>0?max($data_maxes):0;
		$data_min = count($data_mins)>0?min($data_mins):0;
	}
	
	public function getDataSeries($scaled = false, &$scale_factor = 1, &$data_scale_factor_label = '')
	{
		if($scaled == false)
		{
			return $this->_data;
		}
		
		$data_maxes = array();

		foreach($this->_data as $data)
		{
			$data_maxes[] = max($data->getValues());

		}
		$data_max = count($data_maxes)>0?max($data_maxes):0;
				
		$scale_method = NULL;
		
		if($data_max >= 1000 && $data_max < 1000000)
		{
			$scale_method = '\ChartFactory::value_to_kilo_value';
			$scale_factor = 1000;
			$data_scale_factor_label = 'thousands';
		}
		else
		if($data_max >= 1000000  && $data_max < 500000000)
		{
			$scale_method = '\ChartFactory::value_to_mega_value';
			$scale_factor = 1000000;
			$data_scale_factor_label = 'millions';
		}
		else
		if($data_max >= 500000000  )
		{
			$scale_method = '\ChartFactory::value_to_giga_value';
			$scale_factor = 1000000000;
			$data_scale_factor_label = 'billions';
		}
		
		foreach($this->_data as &$data_object)
		{
			$values = $data_object->getValues();
			if($scale_method != NULL)
			{
				array_walk($values, $scale_method);
			}
			$data_object->setValues($values);
		}
		return $data_series;
	}
	
	public function isEmpty()
	{
		return count($this->_data) <= 0;
	}
	public function getIds()
	{
		if(isEmpty()) return array();
		return $this->_data[0]->getIds();
	}
	public function countDataSeries()
	{
		return count($this->_data);
	}
	public function countDataPerDataSeries()
	{
		if(!$this->isEmpty())
		{
			return count($this->_data[0]->getValues());
		}
		return 0;
	}
	public function hasErrors()
	{
		foreach($this->_data as $data)
		{
			if($data->hasErrors()) return true;

		}
		return false;
	}	
	private $_query_groupname = 'query_groupname';
	private $_realm_name = 'query_realm';
		
	public function getQueryGroupname()
	{
		return $this->_query_groupname;
	}
	public function setQueryGroupname($query_groupname)
	{
		$this->_query_groupname = $query_groupname;
	}
	
	public function getRealmName()
	{
		return $this->_realm_name;
	}
	public function setRealmName($realm_name)
	{
		$this->_realm_name = $realm_name;
	}
	
	public function merge(AggregateDataset $dataset)
	{
		if($this->isEmpty() ) 
		{
			$this->_data = $dataset->getDataSeries(false);
			return;
		}else
		{
			$data0 = $this->_data[0];

			$ids0 = $data0->getIds();
			$labels0 = $data0->getLabels();
			$shortlabels0 = $data0->getLabels();
			
			if($dataset->isEmpty()) return;
			$dataset1 = $dataset->getDataSeries(false);

			$data1 = $dataset1[0];
			
			$newValues = array();
			$newSems = array();
			$newWeights = array();
			
			foreach($ids0 as $key => $id)
			{
				$label = $labels0[$key];
				$newValues[] = $data1->getValue($id);
				$newWeights[] = $data1->getWeight($id);
				$newSems[] = $data1->getError($id);
			}
			$series = new \DataWarehouse\Data\AggregateData($data1->getName(),
															$data1->getShortName(),
															$data1->getStatistic(),
															$data1->getGroupBy(),
															$data1->getAggregationUnit(),
															$ids0,
															$labels0,
															$shortlabels0,
															$newValues,
															$newWeights,
															NULL,
															$newSems);
		
			$this->addSeries($series);
		}
		
	}
	public function export($query = NULL)
	{		
		$headers = array();
		$rows = array();
		$duration_info = array('start' => $this->getStartDate(), 'end' => $this->getEndDate());
		$title = array('title' => 'title');
		$title2 = array('parameters' => '');
		
		if(!$this->isEmpty()) 
		{
			$title['title'] = $this->getTitle(false,$query);
			$title2['parameters'] = $query->parameterDescriptions;
			$headers[] = $this->_data[0]->getGroupBy()->getLabel();
			foreach($this->_data as $dataseries)
			{
				$dataseries_stat_name = $dataseries->getStatistic()->getAlias()->getName();
				$dataseries_stat_unit = $dataseries->getStatistic()->getUnit();
				$dataseries_stat_label = $dataseries->getStatistic()->getLabel();
				$dataseries_group_name = $dataseries->getGroupBy()->getName();
				
				if(substr($dataseries_stat_name,0,4) == 'sem_')continue;
				
				$data_unit = '';
				if(substr( $dataseries_stat_unit, -1 ) == '%')
				{
					$data_unit = '%';
				}
				$data_max = 1;
				$data_min = 0;
				$dataseries->getMinMax($data_min, $data_max);
				$decimals = $dataseries->getStatistic()->getDecimals($data_min, $data_max);
				
				
				$headers[] = $dataseries_stat_label;
				$labels = $dataseries->getLabels();
				$values = $dataseries->getValues();
				
				foreach($labels as $index => $label)
				{
					$value = $values[$index];
					if(!isset($rows[$label]))
					{
						$rows[$label] = array();
						$rows[$label][] = $label;
					}
					$rows[$label][] = $value.$data_unit;
				}
				if($dataseries->hasErrors())
				{
					$err_name = 'sem_'.$dataseries_stat_name;
					$headers[] = 'Std Err of '.$dataseries_stat_label;
					
					$errors = $dataseries->getErrors();
					
					
					foreach($labels as $index => $label)
					{
						$error = $errors[$index];
						$rows[$label][] = $error;
					}
				}
			}
			
		}

		return array('title' => $title,
					 'title2' => $title2,
					'duration' => $duration_info,
					'headers' => $headers,
					 'rows' => $rows);
	}
	public function exportJsonStore()
	{
		$fields = array();
		$count = -1;
		$records = array();
		$columns = array();	
		$subnotes = array();
		$message = '';	
		if(!$this->isEmpty()) 
		{
			
			$message .=  '<li>'.$this->_data[0]->getGroupBy()->getDescription().'</li>';
			if($this->_data[0]->getGroupBy()->getName() == 'resource')
			{
				$subnotes[] = '* Resources marked with asterisk are not providing per job processor counts, hence affecting the accuracy of the following statistics: Job Size and CPU Consumption';
			}
			
			$fields[] =  array("name" => $this->_data[0]->getGroupBy()->getName(), "type" => 'string');
			$columns[] = array("header" => $this->_data[0]->getGroupBy()->getLabel(), "width" => 250, "dataIndex" => $this->_data[0]->getGroupBy()->getName(), 
								"sortable" => true, 'editable' => false, 'align' => 'left', 'xtype' => 'gridcolumn', 'locked' => true);
								
			$data = $this->_data;
			
			$texts = array();
			foreach($data as $key => $dataseries)
			{
				$texts[$key] = $dataseries->getStatistic()->getLabel();
			}
			array_multisort($texts, SORT_ASC, $data);		
			
					
		
			foreach($data as $dataseries)
			{	
				$statistic = $dataseries->getStatistic();
				if(!$statistic->isVisible()) continue;
				$groupBy = $dataseries->getGroupBy();
				$dataseries_stat_name = $statistic->getAlias()->getName();
				$dataseries_stat_unit = $statistic->getUnit();
				$dataseries_stat_label = $statistic->getLabel();
				$dataseries_group_name = $groupBy->getName();
				
				if(substr($dataseries_stat_name,0,4) == 'sem_')continue;
				
				$data_unit = '';
				if(substr( $dataseries_stat_unit, -1 ) == '%')
				{
					$data_unit = '%';
				}
				$data_max = 1;
				$data_min = 0;
				$dataseries->getMinMax($data_min, $data_max);
				$decimals = $statistic->getDecimals($data_min, $data_max);
				$message .= '<li>'.$statistic->getDescription($groupBy).'</li>';
				
				$fields[] =  array("name" => $dataseries_stat_name, "type" => 'float');
				$columns[] = array("header" => $dataseries_stat_label, "width" => 185, "dataIndex" => $dataseries_stat_name, 
								"sortable" => true, 'editable' => false, 'align' => 'right', 'xtype' => 'numbercolumn', 'format' => ($decimals>0?'0,000.'.str_repeat(0,$decimals):'0,000').$data_unit);
								
				$values = $dataseries->getValues();
				$labels = $dataseries->getLabels();
				
				foreach($values as $key => $value)
				{
					if(!isset($records[$key]))
					{
						$records[$key] = array();
					}
					
					$records[$key][$dataseries_group_name] = $labels[$key]; 
					$records[$key][$dataseries_stat_name] = $value;	
					
				}
				if($dataseries->hasErrors())
				{
					$err_name = 'sem_'.$dataseries_stat_name;
					$fields[] =  array("name" => $err_name , "type" => 'float');
					$columns[] = array("header" => 'Std Err of '.$dataseries_stat_label, "width" => 215, "dataIndex" => $err_name , 
									"sortable" => true, 'editable' => false, 'align' => 'right', 'xtype' => 'numbercolumn', 'format' => $decimals>0?'0,000.'.str_repeat(0,$decimals+2):'0,000');
					$errors = $dataseries->getErrors();
					
					foreach($errors as $key => $error)
					{
						if(!isset($records[$key]))
						{
							$records[$key] = array();
						}
						$records[$key][$dataseries_group_name] = $labels[$key]; 
						$records[$key][$err_name] = $error;	
					}
				}
			}
		}else
		{
			$message = 'Dataset is empty';
			$fields = array(array("name" => 'Message', "type" => 'string'));
			$records = array(array('Message' => $message));
			$columns = array(array("header" => 'Message', "width" => 600, "dataIndex" => 'Message', 
								"sortable" => true, 'editable' => false, 'align' => 'left', 'renderer' => "CCR.xdmod.ui.stringRenderer"));
			
		}
		$count = count($records);
		$returnData = array
		(
			"metaData" => array("totalProperty" => "total", 
								'messageProperty' => 'message',
								"root" => "records",
								"id" => "id",
								"fields" => $fields
								),
			'message' => '<ul>'.$message.'</ul>',
			"success" => true,
			"total" => $count,
			"records" => $records,
			"columns" => $columns,
			'filter_options' => json_encode($this->getFilterOptions()),
			'subnotes' => $subnotes
		); 
		
		return $returnData;
	}
}
?>