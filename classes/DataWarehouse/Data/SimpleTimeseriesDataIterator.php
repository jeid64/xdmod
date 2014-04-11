<?php
namespace DataWarehouse\Data;

class SimpleTimeseriesDataIterator implements \Iterator
{
	private $groupColumn = array();
	private $dataset;
	private $index = 0;
	public function __construct(\DataWarehouse\Data\SimpleTimeseriesDataset &$dataset, $column_type_and_name, &$groupColumn)
	{
		$this->dataset = $dataset;
		$this->index = 0;
		$this->column_type_and_name = $column_type_and_name;
		$this->results = $this->dataset->getResults(NULL,NULL,false);
		$this->column_type = substr($column_type_and_name,0,3);
		$this->column_name = substr($column_type_and_name,4);	
		
		$this->is_dimension = $this->column_type=='dim';
		
		if($column_type_and_name =='time')
		{
			$this->is_dimension = true;
			$this->column_name = $dataset->getAggregationUnit()->getUnitName();
		}

		$this->groupColumn = $groupColumn;
		$this->where_column_name = $groupColumn->getName();
		$this->where_is_dimension = isset($groupColumn->groupByObject);
		
		if($this->column_type_and_name =='time')
		{
			$this->is_dimension = true;
			$wthis->where_column_name = $dataset->_query->getAggregationUnit()->getUnitName();
		}
		
		if($this->where_is_dimension)
		{
			$this->where_column_name = $this->where_column_name.'_name';
		}
	}
	function rewind() {
        $this->index = 0;//$this->offset;
    }

    function current() {
		if(!$this->valid()) return NULL;
		$value = $this->groupColumn->values[$this->index];
		$dataObjectName = $value.' [<span style="">'.$this->dataset->getColumnLabel($this->column_name,$this->is_dimension).'</span>]';
		$dataObject = $this->dataset->getColumn($this->column_type_and_name,NULL,NULL,$this->where_column_name,$value);
		$dataObject->setName($dataObjectName);
		$dataObject->groupName = $value;
		$dataObject->unit = $this->dataset->getColumnLabel($this->column_name,$this->is_dimension);

		return $dataObject;
    }

    function key() {
        return $this->index;
    }

    function next() {
        ++$this->index;
		return $this->current();
    }

    function valid() {
        return isset($this->groupColumn->values[$this->index]);
    }
};

?>