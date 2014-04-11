<?php
namespace DataWarehouse\Data;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* This class represents one data column. 
* 
*/

class SimpleTimeseriesData extends SimpleData
{	
	public $start_ts = array(); //only available for timeseries data

	public function __construct($name)
	{
		parent::__construct($name);
	}
	
	public function getChartTimes()
	{
		$chartTimes = array();
		foreach($this->start_ts as $timestamp)
		{
			$chartTimes[] = chartTime2($timestamp);
		}
		return $chartTimes;
	}
	
	public function makeUnique()
	{
		$uniqueValues = array();
		foreach($this->values as $index => $value)
		{
			$id = $this->ids[$index];
			if(isset($uniqueValues[$id]))
			{
				unset($this->values[$index]);
				unset($this->errors[$index]);
				unset($this->start_ts[$index]);
				unset($this->ids[$index]);
				unset($this->order_ids[$index]);
			}
			else
			{
				$uniqueValues[$id] = $value;
			}
		}
		$this->values = array_values($this->values);
		$this->errors = array_values($this->errors);
		$this->ids = array_values($this->ids);
		$this->order_ids = array_values($this->order_ids);
		$this->start_ts = array_values($this->start_ts);
		return $uniqueValues;
	}
	
	public function joinTo(SimpleTimeseriesData $left, $no_value = NULL)
	{
		$t_values = $this->values;
		$t_errors = $this->errors;
		$t_order_ids = $this->order_ids;
		$t_ids = $this->ids;
		$t_start_ts = $this->start_ts;
		
		$ts_to_index = array();
		foreach($t_start_ts as $index => $ts)
		{
			$ts_to_index[$ts] = $index;
		}
		
		$this->values = array();
		$this->errors = array();
		$this->ids = array();
		$this->order_ids = array();
		$this->start_ts = array();
		
		foreach($left->start_ts as $index => $start_ts)
		{
			$this->start_ts[] = $start_ts;
			if(isset($ts_to_index[$start_ts]))
			{
				$i = $ts_to_index[$start_ts];
				$this->values[] = $t_values[$i];
				//$this->order_ids[] = $t_order_ids[$i];
				//$this->ids[] = $t_ids[$i];
				$this->errors[] = array_key_exists($i,$t_errors)?$t_errors[$i]:null;
			}
			else
			{
				$this->values[] = $no_value;
				$this->errors[] = $no_value;
				//$this->order_ids[] = $no_value;
				//$this->ids[] = $no_value;
			}
		}
	}

	
}

?>