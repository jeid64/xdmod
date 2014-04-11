<?php
namespace DataWarehouse\Data;


class SimpleDataset
{
	public $_query;
	public $_columnTypes = array();

	
	public function __construct(&$query)
	{
		$this->_query = $query; 
	} 
	public function getTitle2()
	{
		return $this->_query->getTitle2();
	}
	public function getTotalPossibleCount()
	{
		return $this->_query->getCount();
	}
	
	public function getResults($limit = NULL, $offset = NULL, $force_reexec = false, $get_meta = true)
	{
		if($force_reexec === true || !isset($this->_results))	
		{
			$stmt = $this->_query->getRawStatement($limit, $offset);
			$this->_results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			if($get_meta)
			{
				$this->_columnTypes = array();

				for($end = $stmt->columnCount(), $i = 0; $i < $end; $i++)
				{
					$raw_meta = $stmt->getColumnMeta($i);
					$this->_columnTypes[$raw_meta['name']] = $raw_meta;
				}
			}
		}
		
		return $this->_results;
	}
	
	public function extractColumnLabel($column_type_and_name)
	{
		$column_type = substr($column_type_and_name,0,3);
		$column_name = substr($column_type_and_name,4);	
		
		$is_dimension = $column_type=='dim';
		if($column_type_and_name =='time')
		{
			$is_dimension = true;
			$column_name = $this->_query->getAggregationUnit()->getUnitName();
		}
		return $this->getColumnLabel($column_name,$is_dimension);
	}
	
	public function getColumnLabel($column_name, $is_dimension)
	{
		if($is_dimension === true)
		{
			$group_by = $this->_query->_group_bys[$column_name];
			return $group_by->getLabel();
		}else
		{
			$statistic = $this->_query->_stats[$column_name];
			return $statistic->getLabel();//!=$statistic->getUnit() && strpos($statistic->getLabel(),$statistic->getUnit()) === false?$statistic->getLabel().' ('.$statistic->getUnit().')':$statistic->getLabel();
		}
	}
	public function getColumnUnit($column_name, $is_dimension)
	{
		if($is_dimension === true)
		{
			$group_by = $this->_query->_group_bys[$column_name];
			return $group_by->getLabel();
		}else
		{
			$statistic = $this->_query->_stats[$column_name];
			return $statistic->getUnit();
		}
	}
	static function convertSQLtoPHP($value, $native_type, $precision)
	{
		switch($native_type)
		{
			case 'LONGLONG':
			case 'LONG':
				if($value == 0) return null;
				return (int)$value;
				
			case 'DOUBLE':	
			case 'NEWDECIMAL':
				if($value == 0) return null;
				if ($precision == 0)

					return (int)$value;
				else
					return (float)$value;
					
				default:
			return  $value;
		}
		return $value;
	}
	public function getColumn($column_type_and_name,$limit = NULL,$offset= NULL)
	{
		$results = $this->getResults($limit,$offset,false,true);
		
		$column_type = substr($column_type_and_name,0,3);
		$column_name = substr($column_type_and_name,4);	
		
		$is_dimension = $column_type=='dim';
		
		$values_column_name = NULL;
		$sem_column_name = NULL;
		$ids_column_name = NULL;
		$order_ids_column_name = NULL;
		$start_ts_column_name = NULL;
		
		$dataObject = new \DataWarehouse\Data\SimpleData($this->getColumnLabel($column_name,$is_dimension));
		if($is_dimension)
		{
			$dataObject->groupByObject = $this->_query->_group_bys[$column_name];
			$values_column_name = $column_name.'_name';
			$ids_column_name = $column_name.'_id';
			$order_ids_column_name = $column_name.'_order_id';
			$dataObject->unit = $this->getColumnLabel($column_name,$is_dimension); //$this->_query->_group_bys[$column_name]->getLabel();
		}
		else
		{
			$dataObject->statisticObject = $this->_query->_stats[$column_name];
			$values_column_name = $column_name;
			$dataObject->unit = $this->getColumnLabel($column_name,$is_dimension); //$this->_query->_stats[$column_name]->getLabel();
		}
		
		if(isset($this->_query->_stats['sem_'.$column_name]))
		{
			$sem_column_name= 'sem_'.$column_name;
		}
		$columnTypes = $this->_columnTypes;
		
		foreach($results as $row)
		{
			if($values_column_name != NULL)
			{
				if(!array_key_exists($values_column_name, $row)) throw new \Exception ("SimpleDataset:getColumn() values_column_name=$values_column_name does not exist in the dataset.");	
				else $dataObject->values[] = $this->convertSQLtoPHP($row[$values_column_name],$columnTypes[$values_column_name]['native_type'],$columnTypes[$values_column_name]['precision']);	
			}
			if($sem_column_name != NULL)
			{
				if(!array_key_exists($sem_column_name, $row))$dataObject->errors[] = 0;
				else $dataObject->errors[] = $this->convertSQLtoPHP($row[$sem_column_name],$columnTypes[$sem_column_name]['native_type'],$columnTypes[$sem_column_name]['precision']);
			}
			if($ids_column_name != NULL)
			{
				if(!array_key_exists($ids_column_name, $row)) throw new \Exception ("SimpleDataset:getColumn() ids_column_name=$ids_column_name does not exist in the dataset.");	
				else $dataObject->ids[] = $this->convertSQLtoPHP($row[$ids_column_name],$columnTypes[$ids_column_name]['native_type'],$columnTypes[$ids_column_name]['precision']);
			}
			if($order_ids_column_name != NULL)
			{
				if(!array_key_exists($order_ids_column_name, $row)) throw new \Exception ("SimpleDataset:getColumn() order_ids_column_name=$order_ids_column_name does not exist in the dataset. ");	
				else $dataObject->order_ids[] = $this->convertSQLtoPHP($row[$order_ids_column_name],$columnTypes[$order_ids_column_name]['native_type'],$columnTypes[$order_ids_column_name]['precision']);
			}						
		}
		return $dataObject;
	}
	public function export($export_title = 'title')
	{		
		$headers = array();
		$rows = array();
		$duration_info = array('start' => $this->_query->getStartDate(), 'end' => $this->_query->getEndDate());
		$title = array('title' => 'None');
		$title2 = array('parameters' => array());
		
		$count = $this->getTotalPossibleCount();
			
		$results = $this->getResults();
		$result_count = count($results);
				
		if($result_count > 0)
		{
			$title['title'] = $export_title;
			$title2['parameters'] = $this->_query->roleParameterDescriptions;
			$group_bys = $this->_query->getGroupBys();
			
			$stats = $this->_query->getStats();
			$has_stats = count($stats) > 0;
						
			foreach($group_bys as $group_by)
			{
				$headers[] = ($group_by->getName()==='none'?'Summary':$group_by->getLabel());							
			}
			
			foreach($stats as $stat)
			{
				$stat_unit = $stat->getUnit();
				$stat_alias = $stat->getAlias()->getName();
				
				$data_unit = '';
				if(substr( $stat_unit, -1 ) == '%')
				{
					$data_unit = '%'; 
				}
				$column_header = $stat->getLabel();//.'<br>'.$stat_unit;
				if($column_header != $stat_unit && strpos($column_header, $stat_unit) === false) $column_header .= ' ('.$stat_unit.')';
				$headers[] = $column_header. (count($this->_query->filterParameterDescriptions) > 0? ' {'.implode(', ',$this->_query->filterParameterDescriptions).'}':'');
			}
			foreach($results as $result)
			{
				$record = array();				
				foreach($group_bys as $group_by)
				{
					$record[$group_by->getName()] =  $result[$group_by->getLongNameColumnName(true)];
				}
				$stats = $this->_query->getStats();
				foreach($stats as $stat)
				{
					$record[$stat->getAlias()->getName()] =  $result[$stat->getAlias()->getName()];						
				}
				$rows[] = $record;
			}		
		}
	
		return array('title' => $title,
					 'title2' => $title2,
					'duration' => $duration_info,
					'headers' => $headers,
					 'rows' => $rows);
	}

	public function exportJsonStore($limit = NULL, $offset = NULL)
	{
		$fields = array();
		$count = -1;
		$records = array();
		$columns = array();	
		$subnotes = array();
		$sortInfo = array();
		$message = '';
		$count = $this->_query->getCount();
			
		$results = $this->getResults($limit,$offset,false);
		$result_count = count($results);
		

		if($result_count > 0)
		{
			$group_bys = $this->_query->getGroupBys();
			$stats = $this->_query->getStats();
			$has_stats = count($stats) > 0;
			
			
			foreach($group_bys as $group_by)
			{
				$fields[] =  array("name" => $group_by->getName(), "type" => 'string', 'sortDir' => 'DESC');
				$columns[] = array("header" => $group_by->getName()==='none'?'Source':$group_by->getLabel(), "width" => 150, "dataIndex" => $group_by->getName(), 
								"sortable" => true, 'editable' => false, 'locked' => $has_stats);								
			}
			
			foreach($stats as $stat)
			{
				$stat_unit = $stat->getUnit();
				$stat_alias = $stat->getAlias()->getName();
				
				$data_unit = '';
				if(substr( $stat_unit, -1 ) == '%')
				{
					$data_unit = '%';
				}
				$column_header = $stat->getLabel();//.'<br>'.$stat_unit;
				if($column_header != $stat_unit && strpos($column_header, $stat_unit) === false) $column_header .= ' ('.$stat_unit.')';
				/*
				$data_max = 1;
				$data_min = 0;
				$dataseries->getMinMax($data_min, $data_max);*/
				$decimals = $stat->getDecimals(/*$data_min, $data_max*/);
				
				$fields[] =  array("name" => $stat_alias, "type" => 'float', 'sortDir' => 'DESC');
				$columns[] = array("header" => $column_header, "width" => 140, "dataIndex" => $stat_alias, 
								"sortable" => true, 'editable' => false, 'align' => 'right', 'xtype' => 'numbercolumn', 'format' => ($decimals>0?'0,000.'.str_repeat(0,$decimals):'0,000').$data_unit);								
			}
			foreach($results as $result)
			{
				$record = array();				
				foreach($group_bys as $group_by)
				{
					$record[$group_by->getName()] =  $result[$group_by->getLongNameColumnName(true)];						
				}
				$stats = $this->_query->getStats();
				foreach($stats as $stat)
				{
					$record[$stat->getAlias()->getName()] =  $result[$stat->getAlias()->getName()];						
				}
				$records[] = $record;
			}	
			
			$query_orders = $this->_query->getOrders();
			foreach($query_orders as $query_order)
			{
				$sortInfo = array('field' => $query_order->getColumnName(), 'direction' => $query_order->getOrder() );
			}
		}
		else
		{
			$message = 'Dataset is empty';
			$fields = array(array("name" => 'Message', "type" => 'string'));
			$records = array(array('Message' => $message));
			$columns = array(array("header" => 'Message', "width" => 600, "dataIndex" => 'Message', 
								"sortable" => $sortable, 'editable' => false, 'align' => 'left', 'renderer' => "CCR.xdmod.ui.stringRenderer"));
		}

		$returnData = array
		(
			"metaData" => array("totalProperty" => "total", 
								'messageProperty' => 'message',
								"root" => "records",
								"id" => "id",
								"fields" => $fields,
								"sortInfo" => $sortInfo
								),
			'message' => '<ul>'.$message.'</ul>',
			"success" => true,
			"total" => $count,
			"records" => $records,
			"columns" => $columns
		); 
		
		return $returnData;
	}
}
?>