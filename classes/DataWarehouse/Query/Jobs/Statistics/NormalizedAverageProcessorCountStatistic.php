<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/* 
* @author Amin Ghadersohi
* @date 2011-Feb-07
*
* class for calculating the normalized average processor count 
*/

class NormalizedAverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
	public function __construct($query_instance = NULL)
	{
		$job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
		parent::__construct('100.0*coalesce(ceil(sum(jf.processors*jf.'.$job_count_formula.')/sum(jf.'.$job_count_formula.'))/(select sum(rrf.processors) from modw.resourcefact rrf where find_in_set(rrf.id,group_concat(distinct jf.resource_id)) <> 0 ),0)', 'normalized_avg_processors', 'Job Size: Normalized', '% of Total Cores',1);
	}

	public function getInfo()
	{
		return 	"The percentage average size ".ORGANIZATION_NAME." job over total machine cores.<br>
		<i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.";
	}
}
 
?>