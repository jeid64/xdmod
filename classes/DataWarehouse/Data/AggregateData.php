<?php
namespace DataWarehouse\Data;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class represents one data series. 
* 
*/

class AggregateData extends \Common\Identity
{
	private $_statistic;
	private $_group_by;
	private $_aggregation_unit;
	private $_ids;
	private $_labels;
	private $_short_labels;
	private $_values;
	private $_weights;
	private $_overall;//sum of values
	private $_sems;
	
	private $_short_name;
	private $_query_string;
	private $_query_time;
	
	
	public function __construct($name,
								$short_name,
								\DataWarehouse\Query\Statistic $stat, 
								\DataWarehouse\Query\GroupBy $group_by, 
								\DataWarehouse\Query\TimeAggregationUnit $aggregation_unit, 
								array $ids = array(), 
								array $labels = array(),
								array $short_labels = array(),
								array $values = array(),
								array $weights = array(),
								$overall = NULL,
								array $sems = array(),
								$query_string = null,
								$query_time = 0
								)
	{
		parent::__construct($name);
		$this->_short_name = $short_name;
		$this->_statistic = $stat;
		$this->_group_by = $group_by;
		$this->_aggregation_unit = $aggregation_unit;
		$this->_ids = $ids;
		$this->_labels = $labels;
		$this->_short_labels = $short_labels;
		$this->_values = $values;
		$this->_weights = $weights;
		$this->_sems = $sems;
		$this->_query_string = $query_string;
		$this->_query_time = $query_time;
		$this->setOverall($overall);
		
	}
	
	public function getMinMax(&$data_min, &$data_max)
	{
		
		$data_max = count($this->_values)>0?max($this->_values):1;
		$data_min = count($this->_values)>0?min($this->_values):0;
	}
	public function getShortName()
	{
		return $this->_short_name;
	}
	protected function setOverall($overall = NULL)
	{
		if( $overall == NULL )
		{
			if( strpos($this->getStatistic()->getAlias(), 'max_') !== false)
			{
				$values = array();
				foreach($this->_values as $value)
				{
					if($value > 0) $values[] = $value;
				}
				
				$this->_overall =  count($values) > 0? max($values):0;
			}
			else
			if(strpos($this->getStatistic()->getAlias(), 'min_') !== false)
			{
				$values = array();
				foreach($this->_values as $value)
				{
					if($value > 0) $values[] = $value;
				}
				$this->_overall = count($values) > 0? min($values):0;
			}
			else
			{
				$useWeights = strpos($this->getStatistic()->getAlias(), 'avg_') !== false ||
							  strpos($this->getStatistic()->getAlias(), 'utilization') !== false ||
							  strpos($this->getStatistic()->getAlias(), 'burn_rate') !== false;
							  
				$valuesSum = 0;
				$weightsSum = 0;
				foreach($this->_values as $i => $value)
				{
					$weight = $this->_weights[$i];
					if($useWeights)
					{
						$valuesSum += ($value * $weight);
					}
					else
					{
						$valuesSum += $value;
					}
					$weightsSum += $weight;
	
				}
				if($weightsSum == 0 || !$useWeights) $weightsSum = 1;
				
				
				$this->_overall = ($valuesSum / $weightsSum);	
			}
		}
		else
		{
			$this->_overall = $overall;
		}
	}
	public function getOverall()
	{
		return $this->_overall;
	}	
	public function getStatistic()
	{
		return $this->_statistic;
	}
	public function getGroupBy()
	{
		return $this->_group_by;
	}
	public function getAggregationUnit()
	{
		return $this->_aggregation_unit;
	}
	public function getIds()
	{
		return $this->_ids;
	}
	public function getLabels($short_labels = false)
	{
		if($short_labels)
			return $this->_short_labels;
		else
			return $this->_labels;
	}
	public function getShortLabels()
	{
		return $this->_short_labels;
	}
	public function getValuesCount()
	{
		return count($this->_values);
	}
	public function getValues()
	{
		return $this->_values;
	}
	public function setValues($values)
	{
		$this->_values = $values;
	}
	public function getValue($id)
	{
		$index = array_search($id,$this->_ids);
		
		if($index !== false)
		{
			return $this->_values[$index];
		}
		throw new Exception (get_class($this).":getValue( id = $id ) not found");
	}
	public function getWeights()
	{
		return $this->_weights;
	}
	public function setWeights($weights)
	{
		$this->_weights = $weights;
	}
	public function getWeight($id)
	{
		$index = array_search($id,$this->_ids);
		
		if($index !== false)
		{
			return $this->_weights[$index];
		}
		throw new Exception (get_class($this).":getWeight( id = $id ) not found");
	}
	public function hasValues()
	{
		return max($this->_values)>0;
	}
	public function hasErrors()
	{
		return max($this->_sems)>0;
	}
	public function getErrors()
	{
		return $this->_sems;
	}
	public function setErrors($errors)
	{
		$this->_sems = $errors;
	}
	public function getError($id)
	{
		$index = array_search($id,$this->_ids);
		
		if($index !== false)
		{
			return $this->_sems[$index];
		}
		throw new Exception (get_class($this).":getWeight( id = $id ) not found");
	}
	public function getDataSeriesNamesAndOverall()
	{
		$ret = array();
		foreach($this->_labels as $key => $label)
		{
			$ret[] = array('name' => $label, 
						  'short_name' => $this->_short_labels[$key], 
						  'value' => $this->_values[$key]);
		}
		return $ret;
	}
	public function truncate($limit, $showAverageOfOthers = false)
	{
		$stat = $this->getStatistic()->getAlias();
		$useWeights = strpos($stat, 'avg_') !== false ||
					  strpos($stat, 'utilization') !== false ||
					  strpos($stat, 'burn_rate') !== false ||
					  strpos($stat, 'expansion_factor') !== false;
		$isMin =   strpos($stat, 'min_') !== false ;
		$isMax =   strpos($stat, 'max_') !== false ;
					 
		$valuesCount = count($this->_values);
		
		if($valuesCount > $limit)
		{
			$otherSum = 0;
			$weightsSum = 0;
			for($i = $limit; $i < $valuesCount; $i++)
			{
				if($isMin)
				{
					if($otherSum == 0)
					{
						$otherSum = $this->_values[$i];
					}
					else
					{
						$otherSum = min($otherSum,  $this->_values[$i]);
					}
				}
				else 
				if($isMax)
				{
					if($otherSum == 0)
					{
						$otherSum = $this->_values[$i];
					}
					else
					{
						$otherSum = max($otherSum,  $this->_values[$i]);
					}
				}
				else
				if($useWeights)
				{
					$otherSum += ($this->_values[$i] * $this->_weights[$i]);
				}
				else
				{
					$otherSum += $this->_values[$i];
				}
				$weightsSum += $this->_weights[$i];
				unset($this->_values[$i]);
				unset($this->_labels[$i]);
				unset($this->_short_labels[$i]);
				unset($this->_ids[$i]);
				unset($this->_weights[$i]);
				unset($this->_sems[$i]);
			}
			if($weightsSum == 0 || !$useWeights) $weightsSum = 1;
			if($isMin)
			{
				$this->_values[$limit] = $otherSum ;	
				$this->_labels[$limit] = 'Min of '.($valuesCount-$limit).' others';
			}
			else
			if($isMax)
			{
				$this->_values[$limit] = $otherSum;	
				$this->_labels[$limit] = 'Max of '.($valuesCount-$limit).' others';
			}else
			if($showAverageOfOthers === true && $useWeights!==true)
			{
				
				$this->_values[$limit] = ($otherSum / $weightsSum)/($valuesCount-$limit);	
				$this->_labels[$limit] = 'Avg of '.($valuesCount-$limit).' others';
			}
			else
			{
				$this->_values[$limit] = $otherSum / $weightsSum;	
				$this->_labels[$limit] = 'All '.($valuesCount-$limit).' others';				
			}
			$this->_short_labels[$limit] = $this->_labels[$limit];
			$this->_weights[$limit] = $weightsSum;
			$this->_ids[$limit] = -9999 + (-1*$limit);
			$this->_sems[$limit] = 0;
		}
	}
	
	public function __toString()
	{
		return "Data Name: {$this->getName()}\n"
				."Statistic: {$this->getStatistic()->getAlias()}\n"
				."Group By: {$this->getGroupBy()}\n"
				."Ids: ".implode(',',$this->getIds())."\n"
				."Labels: ".implode(',',$this->getLabels())."\n"
				."Short Labels: ".implode(',',$this->getShortLabels())."\n"
				."Values: ".implode(',',$this->getValues())."\n"
				."Std Err: ".implode(',',$this->getErrors())."\n"
				."Weights: ".implode(',',$this->getWeights())."\n"
				."Overall: ".$this->getOverall()."\n";
	}
}

?>