<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the percent utilization of a resource 
*/

class UtilizationStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance = NULL)
	{
		$duration_formula = 1;
		if($query_instance != NULL)
		{
			$duration_formula = $query_instance->getDurationFormula();
		}
		if($query_instance->getQueryType() == 'aggregate')
		{
			$date_table_start_ts = $query_instance->_start_date_ts;
			$date_table_end_ts = $query_instance->_end_date_ts;
		}
		else
		{
			$date_table_start_ts =new \DataWarehouse\Query\Model\TableField($query_instance->getDateTable(),$query_instance->getAggregationUnit()->getUnitName().'_start_ts');
			$date_table_end_ts = new \DataWarehouse\Query\Model\TableField($query_instance->getDateTable(),$query_instance->getAggregationUnit()->getUnitName().'_end_ts');		
		}
		
		parent::__construct("100.0*(coalesce(sum(jf.cpu_time/3600.0)/
					(select case
						when rrf.end_date_ts is null then
							case
								when rrf.start_date_ts < {$date_table_start_ts} 
								then $duration_formula
								when rrf.start_date_ts < {$date_table_end_ts}
								then ({$date_table_end_ts} - rrf.start_date_ts)/3600.0
								else $duration_formula
							end				
					
						when rrf.start_date_ts <= {$date_table_start_ts} and
						      rrf.end_date_ts >=  {$date_table_end_ts}
							 then $duration_formula
						when rrf.start_date_ts < {$date_table_start_ts} and
						     rrf.end_date_ts between {$date_table_start_ts} and {$date_table_end_ts}
							 then $duration_formula
						when rrf.start_date_ts between {$date_table_start_ts} and {$date_table_end_ts}
							 and rrf.end_date_ts > {$date_table_end_ts} 
							 then ({$date_table_end_ts} - rrf.start_date_ts ) / 3600.0
						when rrf.start_date_ts between {$date_table_start_ts} and {$date_table_end_ts}
						     and rrf.end_date_ts between {$date_table_start_ts} and {$date_table_end_ts}
							 then (rrf.end_date_ts - rrf.start_date_ts)/3600.0
						else $duration_formula 
					end
					*sum(rrf.processors) from modw.resourcefact rrf where find_in_set(rrf.id,group_concat(distinct jf.resource_id)) <> 0 ),0))", 'utilization', ORGANIZATION_NAME.' Utilization', '%',2);
	}

	public function getInfo()
	{
		return 	"The percentage of resources utilized by ".ORGANIZATION_NAME." jobs.<br/>
		<i>".ORGANIZATION_NAME." Utilization:</i> the ratio of the total CPU hours consumed by ".ORGANIZATION_NAME." jobs over a given time period divided by the total CPU hours that the system could have potentially provided during that period. It does not include non-".ORGANIZATION_NAME." jobs.<br/> 
		It is worth noting that this value is a rough estimate in certain cases where the resource providers don't provide accurate records of their system specifications, over time.";
	}
}
 
?>