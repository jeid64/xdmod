<?php
namespace DataWarehouse\Data;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class represents one data series over time. 
* 
*/

class TimeseriesData extends \DataWarehouse\Data\AggregateData
{
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
								array $sems = array(),
								$query_string = null,
								$query_time = 0
								)
	{
		$overall = 0;
		parent::__construct($name,$short_name,$stat,$group_by,$aggregation_unit,$ids,$labels,$short_labels,$values,$weights,$overall,$sems,$query_string,$query_time);
	}
	public function getId()
	{
		$ids = $this->getIds();
		return count($ids>0) ? $ids[0] : -1;
	}
	public function getIdName()
	{
		return $this->getGroupBy()->getName().'-'.$this->getGroupBy()->getUnit();
	}

	public function getTimestamps()
	{
		return $this->getLabels();
	}
	public function getChartTimes()
	{
		$chartTimes = array();
		foreach($this->getTimestamps() as $timestamp)
		{
			$chartTimes[] = chartTime2($timestamp);
		}
		return $chartTimes;
	}
	public function getTimeLabels()
	{
		$timeLabels = array();
		$aggregation_unit = $this->getAggregationUnit();
		foreach($this->getTimestamps() as $timestamp)
		{
			$timeLabels[] = $aggregation_unit->getTimeLabel($timestamp);
			
		}
		return $timeLabels;
	}	
	public function add(TimeseriesData $d)
	{
		$values = $this->getValues();
		$weights = $this->getWeights();
		$sems = $this->getErrors();
		$stat = $this->getStatistic()->getAlias();
		$useWeights = strpos($stat, 'avg_') !== false ||
					  strpos($stat, 'count') !== false ||
					  strpos($stat, 'utilization') !== false ||
					  strpos($stat, 'rate') !== false;
		$isMin =   strpos($stat, 'min_') !== false ;
		$isMax =   strpos($stat, 'max_') !== false ;
		
		$in_values = $d->getValues();
		$in_weights = $d->getWeights();
		$in_sems = $d->getErrors();
		
		foreach($in_values as $key => $value)
		{
			if($isMin)
			{
				if($values[$key] == 0 && $value != 0) $values[$key] = $value;
				$values[$key] = min($values[$key],$value);
			}
			else 
			if($isMax)
			{
				 $values[$key] = max($values[$key],$value);
			}
			else
			if($useWeights)
			{
				$values[$key] = ($values[$key] * $weights[$key]) + ($value * $in_weights[$key]);
				$values[$key] = $values[$key] / ($weights[$key] + $in_weights[$key]);
			}
			else
			{
				$values[$key] += $value;
			}
			$weights[$key] += $in_weights[$key];
			$sems[$key] = 0;
		}
		$this->setValues($values);
		$this->setWeights($weights);
		$this->setOverall(NULL);
		$this->setErrors($sems);
	}
}

?>