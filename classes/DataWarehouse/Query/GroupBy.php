<?php
    namespace DataWarehouse\Query;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* Abstract class for defining classes pertaining to grouping query data.
* 
*/
abstract class GroupBy extends \Common\Identity
{
    /*
    * This affects how the query will sort by a stat based on this group by.
    * valid values: SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING. 
    * Alternatively a null value would mean no sorting.
    * Refer to: http://php.net/manual/en/function.array-multisort.php
    */
    private $_order_by_stat_option = NULL;
	
	/*
	* Assigns the statistics that are possible in this grouping
	*/
	private $_statistics = array();
	
	/*
	* This query is required to return fields: id, short_name, name, description
	*/
	protected $_possible_values_query = NULL;
	
	private $_available_on_drilldown = true;
	
	protected $_id_field_name;
	protected $_short_name_field_name;
	protected $_long_name_field_name;
    /*
    * Constructor
    */
    public function __construct($name, array $statistics = array(), $possible_values_query = NULL)
    { 
        $this->setOrderByStat(SORT_DESC);
		$this->_statistics = $statistics;
		$this->_possible_values_query = $possible_values_query;
		$this->_id_field_name = 'id';
		$this->_short_name_field_name = 'short_name';
		$this->_long_name_field_name = 'long_name';
        parent::__construct($name);
    } //__construct
	public function getDescription()
	{
		return '<b>'.$this->getLabel().': </b>'.$this->getInfo();
	}
	public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
	{
	}
	public function filterByGroup(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table)
	{
		return '';
	}
	
	public function getAvailableOnDrilldown()
	{
		return $this->_available_on_drilldown;
	}
	public function setAvailableOnDrilldown($b)
	{
		$this->_available_on_drilldown = $b;
	}	
    /*
    * This function applys this group to the passed query.
    *
    * @param $query - the query to apply the group to
    * @param $data_table - the main data table of the query. Needed for adding proper where conditions
    */
    public abstract function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table);

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
            $sort_option !=  SORT_REGULAR && 
            $sort_option !=  SORT_NUMERIC &&
            $sort_option !=  SORT_STRING)
        {
            throw new Exception("GroupBy::setOrderByStat(sort_option = $sort_option): error - invalid sort_option");
        }
        $this->_order_by_stat_option = $sort_option;
    }

    /*
    * @returns the value of the _order_by_stat_option variable.
    */
    public function getOrderByStatOption()
    {
        return $this->_order_by_stat_option;
    }
	
	public function getPermittedStatistics()
	{
		return $this->_statistics;
	}
	
	public function getPossibleValuesQuery()
	{
		return $this->_possible_values_query;
	}
	/*
	* returns an associative array from index => {id=> ..., short_name=> ..., long_name=>...)
	*/
	public function getPossibleValues($hint = NULL, $limit = NULL, $offset = NULL, array $parameters = array(), $base_query = NULL)
	{
		if($this->_possible_values_query == NULL && $base_query  == NULL)
		{
			return array();
		}
		
		$possible_values_query = $base_query == NULL?$this->_possible_values_query:$base_query ;
		
		if($hint != NULL)
		{
			$possible_values_query = str_ireplace('where ', "where (gt.$this->_short_name_field_name like '%$hint%' or gt.$this->_long_name_field_name like '%$hint%' ) and ",$possible_values_query);
		}
		
		if($limit !== NULL && $offset !== NULL)
		{
			$possible_values_query .= " limit $limit offset $offset";
		}
//echo $possible_values_query;
		$results = \DataWarehouse::connect()->query($possible_values_query);
		
		return $results ;	
	}
	public function pullQueryParameters(&$request)
	{
		return array();
	}
	public function pullQueryParameterDescriptions(&$request)
	{
		return array();
	}
	public function pullQueryParameters2(&$request, $filter_query, $id_column)
	{
		$parameters = array();
		$filterItems = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];
			$filterItems = array_merge($filterItems,explode(',',$filterString));	
		}
		
		if(isset($request[$this->getName()])) 
		{
			$filterItems[] = $request[$this->getName()];
		}
		$filterCount = count($filterItems);
		if($filterCount > 0)
		{
			$fieldIdQuery = str_replace('_filter_', "'".implode("','",$filterItems)."'",$filter_query);

			$parameters[] = new \DataWarehouse\Query\Model\Parameter($id_column, 'in', "($fieldIdQuery)" );
		}
		
		return $parameters;
	}
	public function pullQueryParameterDescriptions2(&$request, $filter_query)
	{
		
		$parameters = array();
		$filterItems = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];
			$filterItems = array_merge($filterItems,explode(',',$filterString));	
		}
		
		if(isset($request[$this->getName()])) 
		{
			$filterItems[] = $request[$this->getName()];
		}
		$filterCount = count($filterItems);
	
		if($filterCount > 0)
		{
			$fieldLabelQuery = str_replace('_filter_', "'".implode("','",$filterItems)."'",$filter_query);
			$fieldLabelResults = \DataWarehouse::connect()->query($fieldLabelQuery);

			$label = $this->getLabel();
			/*if($filterCount > 1) //pluralize label 
			{
				$label .= substr($label,-1,1) == 's'?'es':'s';				
			}*/
			$parameter = $label.' = '.($filterCount>1?'(':'');
			foreach($fieldLabelResults as $fieldLabelResult)
			{
				$parameter .= ' '.$fieldLabelResult['field_label'].', '; 
			} 
			$parameter = substr($parameter,0,-2);
			$parameters[] = $parameter.($filterCount>1?' )':'');
		}
		//print_r($parameters);
		return $parameters;
	}
	
	public function getIdColumnName($multi = false)
	{
		if($multi !== true) return 'id';
		else return $this->getName().'_id';
	}

	public function getLongNameColumnName($multi = false)
	{
		if($multi !== true) return 'name';
		else return $this->getName().'_name';
	}
	
	public function getShortNameColumnName($multi = false)
	{
		if($multi !== true) return 'short_name';
		else return $this->getName().'_short_name';
	}
	public function getOrderIdColumnName($multi = false)
	{
		if($multi !== true) return 'order_id';
		else return $this->getName().'_order_id';
	}
	public static function getLabel()
	{
		return 'group_by';
	}

	public static function getUnit()
	{
		 return static::getLabel();
	}	

	public function getChartSettings()
	{
		return json_encode(array('dataset_type' => $this->getDefaultDatasetType(),
								 'display_type' => $this->getDefaultDisplayType($this->getDefaultDatasetType()),
								 'combine_type' => $this->getDefaultCombineMethod(),								
								 'limit' => $this->getDefaultLimit(),
								 'offset' => $this->getDefaultOffset(),
								 'log_scale' => $this->getDefaultLogScale(),
								 'show_legend' => $this->getDefaultShowLegend(),
								 'show_trend_line' => $this->getDefaultShowTrendLine(),
								 'show_error_bars' => $this->getDefaultShowErrorBars(),
								 'show_guide_lines' => $this->getDefaultShowGuideLines(),
								 'show_aggregate_labels' => $this->getDefaultShowAggregateLabels(),
								 'show_error_labels' => $this->getDefaultShowErrorLabels(),
								 'enable_errors' => $this->getDefaultEnableErrors(),
								 'enable_trend_line' => $this->getDefaultEnableTrendLine()
								 ));
	}
	public function getDefaultDatasetType()
	{
		return 'aggregate';
	}
	public function getDefaultDisplayType($dataset_type = NULL)
	{
		if($dataset_type == 'aggregate')
		{
			return 'h_bar';
		}
		else
		{
			return 'line';
		}
	}
	public function getDefaultCombineMethod()
	{
		return 'stack';
	}
	public function getDefaultShowLegend()
	{
		return 'y';
	}
	public function getDefaultLimit()
	{
		return 10;
	}
	public function getDefaultOffset()
	{
		return 0;
	}
	public function getDefaultLogScale()
	{
		return 'n';
	}
	public function getDefaultShowTrendLine()
	{
		return 'n';
	}
	public function getDefaultShowErrorBars()
	{
		return 'y';
	}
	public function getDefaultShowGuideLines()
	{
		return 'y';
	}
	public function getDefaultShowAggregateLabels()
	{
		return 'n';
	}
	public function getDefaultShowErrorLabels()
	{
		return 'n';
	}
	public function getDefaultEnableErrors()
	{
		return 'y';
	}
	public function getDefaultEnableTrendLine()
	{
		return 'y';
	}
	
	public function getInfo()
	{
		return $this->getLabel();
	}
	

	
}

?>