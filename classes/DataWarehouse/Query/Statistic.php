<?php

namespace DataWarehouse\Query;

/**
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * Abstract class for defining classes pertaining to a query field that calculates some statistic.
 *
 */
abstract class Statistic extends \DataWarehouse\Query\Model\FormulaField
{
    /**
     * This affects how the query will sort by a stat based on this group by.
     * valid values: SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING.
     * Alternatively a null value would mean no sorting.
     * Refer to: http://php.net/manual/en/function.array-multisort.php
     */
    private $_order_by_stat_option = NULL;

    private $_label = NULL;
    private $_unit = NULL;
    private $_decimals;

    public function __construct($formula, $aliasname, $label, $unit, $decimals = 1)
    {
        parent::__construct($formula, $aliasname);
        $this->setOrderByStat(SORT_DESC);
		$this->setLabel($label);
		$this->setUnit($unit);
		$this->setDecimals($decimals);
	}
	
	public function getWeightStatName()
	{
		return 'weight';
	}
	public function getLabel($units = true)
	{
		return $units && $this->_label!=$this->_unit && strpos($this->_label,$this->_unit) === false ?$this->_label.' ('.$this->_unit.')':$this->_label;
	}
	
	public function setLabel($label)
	{
		$this->_label = $label;
	}
	
	public function getUnit()
	{
		return $this->_unit;
	}
	
	public function setUnit($unit)
	{
		$this->_unit = $unit;
	}
	public function getDecimals($data_min = NULL, $data_max = NULL)
	{
		$decimals =  $this->_decimals;
		if($this->_decimals > 0 && $data_min !== NULL && $data_max !== NULL) 
		{
			if($data_min != 0 && $data_max != 0)
			{
				$min = $data_min;
			}else if($data_min == 0)
			{
				$min = $data_max;
			}else 
			{
				$min = $data_min;
			}
			if($min != 0 && $data_max/$min < 1000)
			{
				if($min < 0.000001 )
				{
					$decimals = 8;
				}else
				if($min < 0.00001)
				{
					$decimals = 7;
				}else
				if($min < 0.0001)
				{
					$decimals = 6;
				}else
				if($min < 0.001)
				{
					$decimals = 5;
				}else
				if($min < 0.01)
				{
					$decimals = 4;
				}else
				if($min < 0.1)
				{
					$decimals = 3;
				}
			}
		}

		return $decimals;
	}
	
	public function setDecimals($decimals)
	{
		$this->_decimals = $decimals;
	}
	
	/*
    * Sets the method by which the query would be sorted based on the stat, if any.
    * @sort_option: SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING, NULL (default: SORT_DESC)
    *
    * Refer to: http://php.net/manual/en/function.array-multisort.php
    */
    public function setOrderByStat($sort_option = SORT_DESC)
    {
        if(isset($sort_option) &&
            $sort_option != SORT_ASC &&
            $sort_option != SORT_DESC &&
            $sort_option != SORT_REGULAR &&
            $sort_option != SORT_NUMERIC &&
            $sort_option != SORT_STRING)
        {
            throw new Exception("GroupBy::setOrderByStat(sort_option = $sort_option): error - invalid sort_option");
        }
        $this->_order_by_stat_option = $sort_option;
    }

    /**
     * @returns the value of the _order_by_stat_option variable.
     */
    public function getOrderByStatOption()
    {
        return $this->_order_by_stat_option;
    }
	
	public function isVisible()
	{
		return true;
	}
	
	public function getInfo()
	{
		return $this->getLabel(false);
	}
	public function getDefinition()
	{
		return '';
	}
	public function getDescription(\DataWarehouse\Query\GroupBy &$group_by)
	{
		return '<b>'.$this->getLabel().'</b>: '.$this->getInfo();
	}
}

?>
