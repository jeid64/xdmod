<?php
namespace DataWarehouse\Data;

use CCR\DB;

class SimpleTimeseriesDataset extends SimpleDataset
{
	public function __construct(&$query)
	{
		parent::__construct($query);
	}

	public function getColumn($column_type_and_name,$limit = NULL,$offset= NULL, $wherecolumn_name = NULL, $where_value = NULL)
	{
		$column_type = substr($column_type_and_name,0,3);
		$column_name = substr($column_type_and_name,4);	
		
		$is_dimension = $column_type=='dim';
		
		if($column_type_and_name =='time')
		{
			$is_dimension = true;
			$column_name = $this->_query->getAggregationUnit()->getUnitName();
		}
		
		$values_column_name = NULL;
		$sem_column_name = NULL;
		$ids_column_name = NULL;
		$order_ids_column_name = NULL;
		$start_ts_column_name = NULL;
		
		$dataObject = new \DataWarehouse\Data\SimpleTimeseriesData($this->getColumnLabel($column_name,$is_dimension));
		
		if($is_dimension)
		{
			$dataObject->groupByObject = $this->_query->_group_bys[$column_name];
			$values_column_name = $column_name.'_name';
			$ids_column_name = $column_name.'_id';		
			$order_ids_column_name = $column_name.'_order_id';	
			$dataObject->unit = $this->getColumnLabel($column_name,$is_dimension); //$dataObject->groupByObject->getLabel();
		}
		else
		{
			$dataObject->statisticObject = $this->_query->_stats[$column_name];
			$values_column_name = $column_name;
			$dataObject->unit = $this->getColumnLabel($column_name,$is_dimension); //$dataObject->statisticObject->getLabel();
		}

		if(isset($this->_query->_stats['sem_'.$column_name]))
		{
			$sem_column_name= 'sem_'.$column_name;
		}
		$hasWhere = $wherecolumn_name != NULL && $where_value != NULL;
		
		$this->getResults($limit,$offset,false,true);//inits $this->_results
		$columnTypes = $this->_columnTypes;
		
		$start_ts_column_name = $this->_query->getAggregationUnit()->getUnitName().'_start_ts';
		
		//echo count($results), ' ';
		//return $dataObject;
		foreach($this->_results as $row)
		{
			if($hasWhere && $row[$wherecolumn_name] != $where_value) continue;
			
			if($start_ts_column_name != NULL && !isset($row[$start_ts_column_name])) throw new \Exception ("SimpleTimeseriesDataset:getColumn() start_ts_column_name=$start_ts_column_name does not exist in the dataset.");	
			
			$start_ts  = $row[$start_ts_column_name];
			$dataObject->start_ts[] = $start_ts;
			
			if($values_column_name != NULL)
			{
				if(!array_key_exists($values_column_name,$row)) throw new \Exception ("SimpleTimeseriesDataset:getColumn() values_column_name=$values_column_name does not exist in the dataset.");	
				else $dataObject->values[] = $this->convertSQLtoPHP($row[$values_column_name],$columnTypes[$values_column_name]['native_type'],$columnTypes[$values_column_name]['precision']);
			}
			if($sem_column_name != NULL)
			{
				if(!array_key_exists($sem_column_name,$row))$dataObject->errors[] = 0;
				else $dataObject->errors[] = $this->convertSQLtoPHP($row[$sem_column_name],$columnTypes[$sem_column_name]['native_type'],$columnTypes[$sem_column_name]['precision']);
			}
			if($ids_column_name != NULL)
			{
				if(!array_key_exists($ids_column_name, $row)) throw new \Exception ("SimpleTimeseriesDataset:getColumn() ids_column_name=$ids_column_name does not exist in the dataset.");	
				else $dataObject->ids[] = $this->convertSQLtoPHP($row[$ids_column_name],$columnTypes[$ids_column_name]['native_type'],$columnTypes[$ids_column_name]['precision']);
			}
			if($order_ids_column_name != NULL)
			{
				if(!array_key_exists($order_ids_column_name,$row)) throw new \Exception ("SimpleTimeseriesDataset:getColumn() order_ids_column_name=$order_ids_column_name does not exist in the dataset.");	
				else $dataObject->order_ids[] = $this->convertSQLtoPHP($row[$order_ids_column_name],$columnTypes[$order_ids_column_name]['native_type'],$columnTypes[$order_ids_column_name]['precision']);
			}					
		}
		
		return $dataObject;
	}	
	public function getColumnUniqueOrdered($column_type_and_name,$limit = NULL,$offset= NULL,$realm = 'Jobs')
	{
		$column_type = substr($column_type_and_name,0,3);
		$column_name = substr($column_type_and_name,4);	
		
		$is_dimension = $column_type=='dim';
		
		if( $column_type_and_name=='time' )
		{
			$is_dimension = true;
			$column_name = $this->_query->getAggregationUnit()->getUnitName();
		}	
		$query_classname = '\\DataWarehouse\\Query\\'.$realm .'\\Aggregate';
		$agg_query = new $query_classname(
							$this->_query->getAggregationUnit()->getUnitName(), 
							$this->_query->getStartDate(), 
							$this->_query->getEndDate(), 
							null,
							null,
							array(),
							'tg_usage',
							 array(),
							false);
							
		$agg_query->addGroupBy($column_name);
		
		foreach($this->_query->_stats as $stat_name => $stat)
		{
			$agg_query->addStat($stat_name);
		}
		$agg_query->clearOrders();
		if(isset($this->_query->sortInfo))
		{
			
			foreach($this->_query->sortInfo as $sort)
			{
				$agg_query->addOrderBy($sort['column_name'], $sort['direction']);
			}
		}
		$agg_query->setParameters($this->_query->parameters);
		
		$dataObject = new \DataWarehouse\Data\SimpleTimeseriesData($column_name);
		if($is_dimension)
		{
			$dataObject->groupByObject = $agg_query->_group_bys[$column_name];
			$values_column_name = $column_name.'_name';
			$ids_column_name = $column_name.'_id';		
			$order_ids_column_name = $column_name.'_order_id';	
			$dataObject->unit = $dataObject->groupByObject->getLabel();
		}
		else
		{
			$dataObject->statisticObject = $agg_query->_stats[$column_name];
			$values_column_name = $column_name;
			$dataObject->unit = $dataObject->statisticObject->getUnit();
		}

		$dataObject->count = $agg_query->getCount();
		
		$query_string = $agg_query->getQueryString($limit,  $offset);
		
		//echo $agg_query->getCountQueryString();
		$statement =  DB::factory($agg_query->_db_profile)->query($query_string,array(),true);
		$statement->execute();
		$columnTypes = array();

		for($end = $statement->columnCount(), $i = 0; $i < $end; $i++)
		{
			$raw_meta = $statement->getColumnMeta($i);
			$columnTypes[$raw_meta['name']] = $raw_meta;
		}
		
		while($row = $statement->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT))
		{
			if($values_column_name != NULL)
			{
				if(!array_key_exists($values_column_name,$row)) throw new \Exception ("SimpleTimeseriesDataset:getColumnUniqueOrdered() values_column_name=$values_column_name does not exist in the dataset.");	
				else $dataObject->values[] = $this->convertSQLtoPHP($row[$values_column_name],$columnTypes[$values_column_name]['native_type'],$columnTypes[$values_column_name]['precision']);
				
				$sem_column_name = 'sem_'.$values_column_name;
				if(!array_key_exists($sem_column_name, $row)) $dataObject->errors[] = 0;
				else $dataObject->errors[] = $this->convertSQLtoPHP($row[$sem_column_name],$columnTypes[$sem_column_name]['native_type'],$columnTypes[$sem_column_name]['precision']);
			}
			if($ids_column_name != NULL)
			{
				if(!array_key_exists($ids_column_name,$row)) throw new \Exception ("SimpleTimeseriesDataset:getColumnUniqueOrdered() ids_column_name=$ids_column_name does not exist in the dataset.");	
				else $dataObject->ids[] = $this->convertSQLtoPHP($row[$ids_column_name],$columnTypes[$ids_column_name]['native_type'],$columnTypes[$ids_column_name]['precision']);
			}
			if($order_ids_column_name != NULL)
			{
				if(!array_key_exists($order_ids_column_name,$row)) throw new \Exception ("SimpleTimeseriesDataset:getColumnUniqueOrdered() order_ids_column_name=$order_ids_column_name does not exist in the dataset.");	
				else $dataObject->order_ids[] = $this->convertSQLtoPHP($row[$order_ids_column_name],$columnTypes[$order_ids_column_name]['native_type'],$columnTypes[$order_ids_column_name]['precision']);
			}					
		}
		//print_r($dataObject);
		return $dataObject;
		
	}
	public function getTimestamps()
	{
		$raw_timetamps = $this->_query->getTimestamps();
		$column_name = $this->_query->getAggregationUnit()->getUnitName();
		$timestampsDataObject = new \DataWarehouse\Data\SimpleTimeseriesData($this->getColumnLabel($column_name,true));
		
		$values_column_name = 'short_name';
		$ids_column_name = 'id';	
		$order_ids_column_name = 'id';
		$start_ts_column_name = 'start_ts';
		
		foreach($raw_timetamps as $raw_timetamp)
		{
			if(!array_key_exists($start_ts_column_name, $raw_timetamp)) throw new \Exception ("SimpleTimeseriesDataset:getTimestamps() start_ts_column_name=$start_ts_column_name does not exist in the dataset.");	
			
			$start_ts =  $raw_timetamp[$start_ts_column_name];
			 $timestampsDataObject->start_ts[] = $start_ts;
			
			if(!array_key_exists($values_column_name, $raw_timetamp)) throw new \Exception ("SimpleTimeseriesDataset:getTimestamps() values_column_name=$values_column_name does not exist in the dataset.");	
			else $timestampsDataObject->values[] = $raw_timetamp[$values_column_name];
		
			$timestampsDataObject->errors[] = 0;
			
			if(!array_key_exists($ids_column_name, $raw_timetamp)) throw new \Exception ("SimpleTimeseriesDataset:getTimestamps() ids_column_name=$ids_column_name does not exist in the dataset.");	
			else $timestampsDataObject->ids[] = $raw_timetamp[$ids_column_name];
			
			if(!array_key_exists($order_ids_column_name, $raw_timetamp)) throw new \Exception ("SimpleTimeseriesDataset:getTimestamps() order_ids_column_name=$order_ids_column_name does not exist in the dataset.");	
			else $timestampsDataObject->order_ids[] = $raw_timetamp[$order_ids_column_name];
		}
		return $timestampsDataObject;
	}
/*
	//returns the column indicated by first param grouped by datagroup
	public function getColumnBy($column_type_and_name,$datagroup_type_and_name, $limit = NULL,$offset= NULL)
	{
		$results = $this->getResults($limit,$offset,false);
		
		$column_type = substr($column_type_and_name,0,3);
		$column_name = substr($column_type_and_name,4);	
		
		$is_dimension = $column_type=='dim';
		
		if($column_type_and_name =='time')
		{
			$is_dimension = true;
			$column_name = $this->_query->getAggregationUnit()->getUnitName();
		}
		//print_r($results);
		//get enumeration of datagroup column
		$groupColumn = $this->getColumn($datagroup_type_and_name,$limit,$offset);
		$groupColumn->makeUnique();
		//print_r($groupColumn);
		$where_column_type = substr($datagroup_type_and_name,0,3);
		$where_column_name = substr($datagroup_type_and_name,4);	
		
		$where_is_dimension = $where_column_type=='dim';
		
		if($column_type_and_name =='time')
		{
			$is_dimension = true;
			$where_column_name = $this->_query->getAggregationUnit()->getUnitName();
		}
		
		if($where_is_dimension)
		{
			$where_column_name = $where_column_name.'_name';
		}
		
		$dataObjects = array();
		foreach($groupColumn->ids as $index => $id)
		{
			$value = $groupColumn->values[$index];
			$dataObjectName = $value.' ['.$this->getColumnLabel($column_name,$is_dimension).']';
			$dataObject = $this->getColumn($column_type_and_name,$limit,$offset,$where_column_name,$value);
			$dataObject->setName($dataObjectName);
			$dataObject->unit = $this->getColumnLabel($column_name,$is_dimension);
			//print_r($dataObject);
			$dataObjects[] = $dataObject;
		}
		return $dataObjects;
	}*/
	
	public function getColumnIteratorBy($column_type_and_name,$datagroup_type_and_name)
	{
		return new SimpleTimeseriesDataIterator($this, $column_type_and_name,$datagroup_type_and_name);
	}
}
?>
