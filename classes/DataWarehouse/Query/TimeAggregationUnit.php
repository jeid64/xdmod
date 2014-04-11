<?php
    namespace DataWarehouse\Query;
/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* Abstract class for defining classes pertaining to grouping data over time.
* 
*/
abstract class TimeAggregationUnit
{
    //The unit name is passed to this class via the constructor by extending subclasses
    private $_unit_name;

    //protected constructor can only be called from extending classes.
    protected function __construct($unit_name)
    {
        $this->_unit_name = $unit_name;
    } //__construct

    /* 
    * Get this name of this unit.
    * 
    * @returns the unit name of this aggregation unit
    * 
    */
    public function getUnitName()
    {
        return $this->_unit_name;
    } //getUnitName

    /* 
    * 
    * @returns minimum index of the time aggregation unit. example quarter min = 1 
    * 
    */		
    abstract public function getMinPeriodPerYear();

    /* 
    * 
    * @returns maximum index of the time aggregation unit. example quarter  max = 4
    * 
    */			
    abstract public function getMaxPeriodPerYear();


	abstract public function getTimeLabel($timestamp);
    /* 
    * getMinDateId
    *
    * @returns the minimum id from the corresponding time period table based on the 
    *          value of start_date. Returns 1, if start_date is less than all possible 
    *		   values in the table.
    * 
    */	
    public function getMinDateId($start_date)
    {
		$q = "select min(id) as id from ".$this->getUnitName()."s 
        where '$start_date' 
            between 
            ".$this->getUnitName()."_start 
            and ".$this->getUnitName()."_end";
		
        $startDateResult = \DataWarehouse::connect()->query($q);
			
        $start_date_id = 1;
        if(count($startDateResult) > 0)
        {
            $start_date_id = $startDateResult[0]['id'];
            if(!isset($start_date_id) || $start_date_id == '' )
            {
				$q = "select case when '$start_date' < min(".$this->getUnitName()."_start) then 1 else ".self::$_max_date_id." end as id  from ".$this->getUnitName()."s";
                $startDateResult = \DataWarehouse::connect()->query($q);
                if(count($startDateResult) > 0)
                {
                    $start_date_id = $startDateResult[0]['id'];
                }
            }
        }


        return $start_date_id;
    } //getMinDateId

    /* 
    * 
    * @returns the maximum id from the corresponding time period table based on the 
    *          value of end_date. Returns -1 if end_date is greater than all possible 
    *		   values in the table
    * 
    */	
    public function getMaxDateId($end_date)
    {
        $endDateResult = \DataWarehouse::connect()->query(
            "select max(id) as id from ".$this->getUnitName()."s 
        where '$end_date' 
            between 
            ".$this->getUnitName()."_start 
            and ".$this->getUnitName()."_end");

            $end_date_id = self::$_max_date_id;
        if(count($endDateResult) > 0)
        {
            $end_date_id = $endDateResult[0]['id'];
            if(!isset($end_date_id))
            {
                $endDateResult = \DataWarehouse::connect()->query(
                    "select case when '$end_date' < min(".$this->getUnitName()."_start) then 0 else ".self::$_max_date_id." end as id  from ".$this->getUnitName()."s");
                if(count($endDateResult) > 0)
                {
                    $end_date_id = $endDateResult[0]['id'];
                }
            }
        }
        return $end_date_id;
    } //getMaxDateId

    /* 
    * @returns this object as a string
    * 
    */
    public function __toString()
    {
        return $this->getUnitName();
    } //__toString

    //////////////Static Members////////////////////////
    /*
    * This variable keeps track of all the time units being registered or not. See @registerUnit
    */
    private static $_initialized = false;

    /*
    * This is the max date id that will be used if end_date is out of range and greater than the last
    * date id avaiable in the data table.
    */
    public static $_max_date_id = 999999999999999;
    /*
    *  This array keeps track of the TimeAggregationUnit subclasses that have registed
    *   using RegisterUnit
    */
    public static $_unit_name_to_class_name = array();

    /* 
    * Registers an TimeAggregationUnit subclass
    *
    * @param $unit_name for example 'week', 'day', 'month', 'quarter'
    * @param $unit_class_name for example 'DayAggregationUnit'
    * 
    */
    public static function registerUnit($unit_name, $unit_class_name)
    {
        self::$_unit_name_to_class_name[$unit_name] = $unit_class_name; 
    } //registerUnit

    /* 
    * Registers all TimeAggregationUnit subclasses.
    * 
    */
    public static function registerAggregationUnits()
    {
        if(!self::$_initialized)
        {
            //TODO: automate this by search directory
            self::registerUnit('day', '\\DataWarehouse\\Query\\TimeAggregationUnits\\DayAggregationUnit');
            // self::registerUnit('week', '\\DataWarehouse\\Query\\TimeAggregationUnits\\WeekAggregationUnit');
            self::registerUnit('month', '\\DataWarehouse\\Query\\TimeAggregationUnits\\MonthAggregationUnit');
            self::registerUnit('quarter', '\\DataWarehouse\\Query\\TimeAggregationUnits\\QuarterAggregationUnit');
			self::registerUnit('year', '\\DataWarehouse\\Query\\TimeAggregationUnits\\YearAggregationUnit');
			
            self::$_initialized = true;
        }
    } //registerAggregationUnits

    /* 
    * @param $time_period: the name of the time aggregation unit. ie: day, week, month, quarter.
    * @param $start_date: if time_period is auto this is used to figure out aggregation unit
    * @param $end_date: if time_period is auto this is used to figure out aggregation unit
    * @returns a subclass of TimeAggregationUnit based on $time_period requested.
    * @throws Exception if $time_period is not registered
    *
    * TimeAggregationUnit subclasses must be registed using TimeAggregationUnit::registerUnit first
    * 
    */		
    public static function factory($time_period, $start_date, $end_date)
    {
        self::registerAggregationUnits();

        $time_period = self::deriveAggregationUnitName($time_period,$start_date,$end_date);

        if(isset(self::$_unit_name_to_class_name[$time_period]))
        {
            $class_name = self::$_unit_name_to_class_name[$time_period];

            return new $class_name;	
        }
        else
        {
            throw new Exception("TimeAggregationUnit: Time period {$time_period} is invalid.");
        }
    } //factory	

	/*
	* This function returns a copy of the array that maps the aggregation unit  names to class names
	*/
	public static function getRegsiteredAggregationUnits()
	{
		self::registerAggregationUnits();
		return self::$_unit_name_to_class_name;
	}//getRegsiteredAggregationUnits

    /*
    * if @param time_period is equal to 'auto' this function will use start and end date to figure out
    * a the name of the aggregation unit to use. 
    *
    */
    public static function deriveAggregationUnitName($time_period, $start_date, $end_date)
    {
        $time_period = strtolower($time_period);
		
		 $dateDifferenceResult = \DataWarehouse::connect()->query("select (unix_timestamp( :end_date) - 
			unix_timestamp(:start_date))/3600.00 
			as date_difference_hours", array('start_date' => $start_date,
			'end_date' => $end_date));

		$date_difference_hours = $dateDifferenceResult[0]['date_difference_hours'];
		
        if($time_period == 'auto')
        {
            if($date_difference_hours > (366*24*5))//2 years
            {
                $time_period = 'quarter';
            }else if($date_difference_hours > (100*24))
			{
				$time_period = 'month';
			}else
			   // if($date_difference_hours > (30*24))
				{
				//    $time_period = 'week';
			   // }else
			   // {
					$time_period = 'day';
				}
        }
		/*if($time_period == 'day' )
		{
			if($date_difference_hours >= (365*24*2))
			{
				$time_period = 'quarter';
			}
			else if($date_difference_hours >= (180*24))
			{
				$time_period = 'month';
			}
		}
		if($time_period == 'month' )
		{
			if($date_difference_hours >= (365*24*3))
			{
				$time_period = 'quarter';
			}
			
		}*/
        return $time_period;
    }

}

?>