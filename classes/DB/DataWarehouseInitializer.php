<?php
require_once 'Log.php';
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */
 
use CCR\DB;

class DataWarehouseInitializer
{
    private $_mod_warehouse = null;
    private $_tgcdb = null;
    private $_pops = null;
    public static $_dest_schema = "modw_aggregates";

	public $_aggregationUnits = array
	(
		"day",
		"month",
		"quarter",
		"year"
		
	);
			
	public $_ingestors = array
	(
		"CountriesIngestor",
		"StatesIngestor",
		"OrgTypesIngestor",
		"OrganizationsIngestor",
		"ServiceProviderIngestor",
		"ResourceTypesIngestor",
		"NSFStatusCodesIngestor",
		"PeopleIngestor",
		"GatewayPeopleIngestor",
		"PIPeopleIngestor",
		"ResourcesIngestor",
		"FieldOfScienceIngestor",
		"FieldOfScienceHierarchyIngestor",
		"GrantTypesIngestor",
		"AccountsIngestor",
		"SystemAccountsIngestor",
		"TransactionTypesIngestor",
		"AllocationStatesIngestor",
		"RequestsIngestor",
		"PrincipalInvestigatorsIngestor",
		"AllocationsIngestor",
		"AllocationsOnResourcesIngestor",
		"BoardTypesIngestor",
		"AllocationBreakdownsIngestor",
		"AllocationAdjustmentsIngestor",
		"QueuesIngestor",
		"NodeCountIngestor",
		"PeopleOnAccountsIngestor",
		"PeopleUnderPIIngestor",
		"JobsIngestor"
	);

	//reduced set of ingestors for tgcdb prod, since mirror runs first we dont need it to get every thing again.
	public $_tgcdb_prod_ingest_set = array
	(
		"PeopleIngestor",
		"GatewayPeopleIngestor",
		"PIPeopleIngestor",
		"FieldOfScienceIngestor",
		"FieldOfScienceHierarchyIngestor",
		"GrantTypesIngestor",
		"AccountsIngestor",
		"SystemAccountsIngestor",
		"TransactionTypesIngestor",
		"AllocationStatesIngestor",
		"RequestsIngestor",
		"PrincipalInvestigatorsIngestor",
		"AllocationsIngestor",
		"AllocationsOnResourcesIngestor",
		"BoardTypesIngestor",
		"AllocationBreakdownsIngestor",
		"AllocationAdjustmentsIngestor",
		"QueuesIngestor",
		"PeopleOnAccountsIngestor",
		"PeopleUnderPIIngestor",
		"JobsIngestor"
	);
	
    public $_pops_ingestors = array
	(
		 'POPSProposalMeetingIngestor',
		 'POPSNPCSSentIngestor',
		 'POPSResGroupResIngestor',
		 'POPSResGroupDesIngestor',
		 'POPSPropNumResGroupGrantNumIngestor',
		 'POPSOrganizationViewIngestor',
		 'POPSFieldsOfScienceIngestor',
         'POPSFundingAgenciesIngestor',
         'POPSGrantsIngestor',
		 'POPSResourcesIngestor',
		 'POPSTGResourcesIngestor',
		 'POPSProposalIngestor',
		 'POPSProposalAllocationIngestor',
		 'POPSResourceAmountIngestor',
		 'POPSProposalAttachIngestor',
		 'POPSCoPIIngestor',
		 'POPSProposalStatusIngestor',
		 'POPSProposalKeywordIngestor',
		 'POPSProposalDurationIngestor',
		 'POPSProposalSFOSIngestor',
		 'POPSProposalTypeIngestor',
		 'POPSProposalTypeDESIngestor',
		 'POPSProposalExtensionIngestor',
		 'POPSProposalExtensionLengthIngestor',
		 'POPSMeetingIngestor',
		 'POPSMTGResourcesIngestor',
		 'POPSResourceAmountTypeIngestor',
		 'POPSProposalDocumentIngestor',
		 'POPSResourceTypeDescriptionIngestor',
		 'POPSFormatTypeDescriptionIngestor',
		 'POPSProposalMeetingNotificationIngestor',
		 'POPSDocumentVersionDescriptionIngestor',
		 'POPSMeetingTypeDescriptionIngestor',
		 'POPSPurposeDescriptionIngestor',
		 'POPSBoardTypeDescriptionIngestor',
		 'POPSClientAllIngestor',
		 'POPSClientOrganizationIngestor',
		 'POPSPersonIngestor',
		 'POPSInterimClientIngestor'
		);
 
 	public $_taccstats_ingestors = array
	(
		 //'TaccStatsRangerSummaryIngestor',
		 'TaccStatsLonestarSummaryIngestor',
		 'TaccStatsStampedeSummaryIngestor'
	);
		
	public $_aggregators = array
	(
		'JobTimeseriesAggregator', 
		'AllocationsTimeseriesAggregator', 
		'AccountsTimeseriesAggregator', 
		'POPSProposalsTimeseriesAggregator', 
		/*'POPSGrantsTimeseriesAggregator',*/ 
		'AppKernelsTimeseriesAggregator', 
		'TaccStatsTimeseriesAggregator'
		);
		
    protected $_logger = null;

	function __construct($tgcdb = 'tgcdbmirror', $pops = 'pops', $warehouse = 'datawarehouse')
    {
        $this->_mod_warehouse = DB::factory($warehouse, false);
        $this->_tgcdb         = DB::factory($tgcdb, false);
        $this->_pops          = DB::factory($pops, false);

        $this->_logger = Log::singleton('xdconsole', '', 'DWI');// default to console logger
    }
    //functionality for ingesting from tgcdb
    function ingestAll($start_date = "1997-01-01 00:00:00", $end_date = "2010-01-01 23:59:59")
    {
        $this->_logger->info(get_class($this) . ".ingestAll(start_date: $start_date, end_date: $end_date):start");

        foreach ($this->_ingestors as $ingestor_name) {
            $ingestor = new $ingestor_name($this->_mod_warehouse, $this->_tgcdb, $start_date, $end_date);
            $ingestor->setLogger($this->_logger);
            $ingestor->ingest();
        }
		
		$this->_logger->info(get_class($this) . ".ingestAll():end");
    }

    function ingest($ingestors = array(), $start_date = "1997-01-01 00:00:00", $end_date = "2010-01-01 23:59:59")
    {
        $this->_logger->info(get_class($this) . ".ingest(ingestors: (".implode(',',$ingestors)."), start_date: $start_date, end_date: $end_date):start");

        foreach ($ingestors as $ingestor_name) {
            $ingestor = new $ingestor_name($this->_mod_warehouse, $this->_tgcdb, $start_date, $end_date);
            $ingestor->setLogger($this->_logger);
            $ingestor->ingest();
        }
		$this->_logger->info(get_class($this) . ".ingest():end");
    }
    function ingestExcept($except_ingestors = array(), $start_date = "1997-01-01 00:00:00", $end_date = "2010-01-01 23:59:59")
    {
        $this->_logger->info(get_class($this) . ".ingest(except_ingestors: (".implode(',',$ingestors)."), start_date: $start_date, end_date: $end_date):start");

        foreach ($this->_ingestors as $ingestor_name) {
            if (in_array($ingestor_name, $except_ingestors) == TRUE)
                continue;
            $ingestor = new $ingestor_name($this->_mod_warehouse, $this->_tgcdb, $start_date, $end_date);
            $ingestor->setLogger($this->_logger);
            $ingestor->ingest();
        }
		
		 $this->_logger->info(get_class($this) . ".ingest():end");
    }
    //end - functionality for ingesting from tgcdb

    //functionality to ingest pops data.
    function ingestAllPops($start_date = "1997-01-01 00:00:00", $end_date = "2015-01-01 23:59:59")
    {
        $this->_logger->info(get_class($this) . ".ingestAllPops(start_date: $start_date, end_date: $end_date):start");

        foreach ($this->_pops_ingestors as $ingestor_name) {
            $ingestor = new $ingestor_name($this->_mod_warehouse, $this->_pops, $start_date, $end_date);
            $ingestor->setLogger($this->_logger);
            $ingestor->ingest();
        }

       // Refer to modw.fieldofscience instead
       $this->_mod_warehouse->handle()->prepare('TRUNCATE modw_pops.fos')->execute();
		
	   $this->_logger->info(get_class($this) . ".ingestAllPops():end");
    }
    //end - functionality to ingest pops data.

	//functionality to ingest taccstats data.
	function ingestAllTaccStats( $start_date = "1997-01-01 00:00:00",  $end_date = "2015-01-01 23:59:59", $replace = false)
	{
		$this->_logger->info(get_class($this) . ".ingestAllTaccStats(start_date: $start_date, end_date: $end_date):start");
		
        foreach ($this->_taccstats_ingestors as $ingestor_name) {
            $ingestor = new $ingestor_name($this->_mod_warehouse, $start_date, $end_date);
			$ingestor->setLogger($this->_logger);
			$ingestor->ingest($replace);
        }
		
		$this->_logger->info(get_class($this) . ".ingestAllTaccStats():end");
	}
	function ingestTaccStats(array $taccstats_ingestors = array(), $replace = true, $start_date = "1997-01-01 00:00:00", $end_date = "2015-01-01 23:59:59")
	{
		$this->_logger->info(get_class($this) . ".ingestTaccStats(taccstats_ingestors: (".implode(',',$taccstats_ingestors)."), start_date: $start_date, end_date: $end_date):start");
		
        foreach ($taccstats_ingestors as $ingestor_name) {
            $ingestor = new $ingestor_name($this->_mod_warehouse, $start_date, $end_date);
			$ingestor->setLogger($this->_logger);
			$ingestor->ingest($replace);
        }
		
		$this->_logger->info(get_class($this) . ".ingestTaccStats():end");
	}
	//end - functionality to ingest taccstats data.
    ////////////////////////////////////////////////////////////////////////////////////////////////////

	function regenerateAggregationUnitTables()
	{
		$this->_logger->info(get_class($this) . ".regenerateAggregationUnitTables():start");
		//re-generate the aggregation unit tables (day, month, quarter, year) in modw
		foreach($this->_aggregationUnits as $aggUnit)
		{
			$ts_gen = new TimeseriesGenerator($aggUnit);
        	$ts_gen->execute($this->_mod_warehouse, self::$_dest_schema);
		}
		$this->_logger->info(get_class($this) . ".regenerateAggregationUnitTables():end");
	}
    //this function should be called before all other aggregation functions.
    function initializeAggregation($start_date = null, $end_date = null, $append = true)
    {
		if ($start_date === null) {
			$start_date = date("Y-m-d", mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (86400 * 1));
		}

		if ($end_date === null) {
			$end_date = date('Y-m-d');
		}
        $this->_logger->info(get_class($this) . ".initializeAggregation(start_date: $start_date, end_date: $end_date):start");

		if(!$append)
		{
			//create extra dimension tables in modw
			$processor_buckets = new ProcessorBucketGenerator();
			$processor_buckets->execute($this->_mod_warehouse, self::$_dest_schema);
	
			$job_times = new JobTimeGenerator();
			$job_times->execute($this->_mod_warehouse, self::$_dest_schema);
			
			//create the modw_aggregates
			$this->_mod_warehouse->handle()->prepare("CREATE DATABASE if not exists " . self::$_dest_schema)->execute();
		}
		
		//check to see all resources with jobs have processor info
        $resources_without_info_result = $this->_mod_warehouse->query("
			select distinct(resource_id) as resource_id
			from jobfact
			where
				start_time_ts between unix_timestamp('$start_date') and unix_timestamp('$end_date') 
			  and resource_id not in (select distinct(id) from resourcefact where processors is not null)
  		");

        if (count($resources_without_info_result) > 0) {
            $resources = array();
            foreach ($resources_without_info_result as $resource) {
                $resources[] = $resource['resource_id'];
            }
            throw new Exception('New Resource(s) in resourcefact table does not have processor and node information: ' . implode(',', $resources));
        }
       
		//re-create the minmaxdate table, since this changes as new data comes in, append or not
        $this->_mod_warehouse->handle()->prepare("drop table if exists modw.minmaxdate")->execute();
        $min_max = $this->_mod_warehouse->handle()->prepare("
			create table modw.minmaxdate  engine=myisam as (select least(min(start_time),
				min(end_time),
				min(submit_time),
				(select min(initial_start_date) from modw.allocation)) as min_job_date,
				greatest(max(start_time),
				max(end_time),
				max(submit_time)) as max_job_date 
			from
			modw.jobfact) 
		")->execute();
		
		$this->regenerateAggregationUnitTables();
		
		//do any side off summarizaition here
	    $allocationsummary_aggregator = new AllocationSummaryAggregator();
        $allocationsummary_aggregator->execute($this->_mod_warehouse, self::$_dest_schema);
		
	    $this->_logger->info(get_class($this) . ".initializeAggregation():end");
    }
	function aggregate($aggregator, $start_date, $end_date, $append = true)
	{
		$this->_logger->info(get_class($this) . ".aggregate(aggregator: ".$aggregator.", start_date: $start_date, end_date: $end_date):start");
		
		if(in_array($aggregator,$this->_aggregators)) 
		{
			foreach($this->_aggregationUnits as $aggUnit)
			{
				$agg = new $aggregator($aggUnit);
				$agg->setLogger($this->_logger);
        		$agg->execute($this->_mod_warehouse, self::$_dest_schema, $start_date, $end_date, $append);
			}
		}
		else
		{
			$message = "$aggregator is not a valid class";
			$this->_logger->err($message);
		}
		$this->_logger->info(get_class($this) . ".aggregate():end");
	}
	
	function aggregateAll($start_date, $end_date, $append = true, $initializeAggregation = true)
	{
		$this->_logger->info(get_class($this) . ".aggregateAll(start_date: $start_date, end_date: $end_date, initializeAggregation: $initializeAggregation):start");
		
		if($initializeAggregation)
		{
			$this->initializeAggregation($start_date,$end_date, $append);
		}
		foreach($this->_aggregators as $aggregator)
		{
			try
			{
				$this->aggregate($aggregator,$start_date,$end_date,$append);
			}
			catch(\Exception $e)
			{
				 $this->_logger->err(array(
                        'message' => get_class($this).".aggregateAll(start_date: $start_date, end_date: $end_date, append: $append) Caught exception while executing $aggregator: " . $e->getMessage() ,
                        'stacktrace' => $e->getTraceAsString()
                    ));
			}
		}
		
		//run this after aggregation as it uses aggregated data.
		$this->runCustomQueries();
		
		$this->_logger->info(get_class($this) . ".aggregateAll():end");
	}
	
    function aggregateAllJobs($start_date, $end_date, $append = true, $infinidb = false)
    {
		$this->_logger->info(get_class($this) . ".aggregateAllJobs(start_date: $start_date, end_date: $end_date, append: $append):start");
		
        $this->aggregate('JobTimeseriesAggregator',$start_date,$end_date,$append);

		$this->_logger->info(get_class($this) . ".aggregateAllJobs() done");
    }

    //to be called only after aggregateAll
    function aggregateAllAllocations($start_date, $end_date, $append = true, $infinidb = false)
    {
		$this->_logger->info(get_class($this) . ".aggregateAllAllocations(start_date: $start_date, end_date: $end_date, append: $append):start");
		
		$this->aggregate('AllocationsTimeseriesAggregator',$start_date,$end_date,$append);
		
		$this->_logger->info(get_class($this) . ".aggregateAllAllocations():end");
    }

    //to be called only after aggregateAll
    function aggregateAllAccounts($start_date, $end_date, $append = true, $infinidb = false)
    {
		$this->_logger->info(get_class($this) . ".aggregateAllAccounts(start_date: $start_date, end_date: $end_date, append: $append):start");
		
		$this->aggregate('AccountsTimeseriesAggregator',$start_date,$end_date,$append);
		
		$this->_logger->info(get_class($this) . ".aggregateAllAccounts():end");
    }

    function aggregateAllPOPS($start_date, $end_date, $append = true, $infinidb = false)
    {
		$this->_logger->info(get_class($this) . ".aggregateAllPOPS(start_date: $start_date, end_date: $end_date, append: $append):start");
		
		$this->aggregate('POPSProposalsTimeseriesAggregator',$start_date,$end_date,$append);
		
		$this->_logger->info(get_class($this) . ".aggregateAllPOPS():end");

    }

    //to be called only after aggregateAllJobs
    function aggregateAllAppKernels($start_date, $end_date, $append = true, $infinidb = false)
    {
		$this->_logger->info(get_class($this) . ".aggregateAllAppKernels(start_date: $start_date, end_date: $end_date, append: $append):start");
		
		$this->aggregate('AppKernelsTimeseriesAggregator',$start_date,$end_date,$append);
		
		$this->_logger->info(get_class($this) . ".aggregateAllAppKernels():end");
    }

		
	function aggregateAllTaccStats($start_date, $end_date, $append = true, $infinidb = false)
	{
		$this->_logger->info(get_class($this) . ".aggregateAllTaccStats(start_date: $start_date, end_date: $end_date, append: $append):start");
		
		$this->aggregate('TaccStatsTimeseriesAggregator',$start_date,$end_date,$append);
		
		$this->_logger->info(get_class($this) . ".aggregateAllTaccStats():end");
	}
	
	function runCustomQueries()
	{
		$this->_logger->info(get_class($this) . ".runCustomQueries():start");
		$script = file_get_contents(dirname(__FILE__).'/../../assets/scripts/jtowns_queries.sql');
		
		$commands = explode('//',$script);
		array_shift($commands); //pull out the first command which is delimiter //
		foreach($commands as $command)
		{		
			$command = trim($command);
			if($command != '')//ignore empty tokens
			{
				//echo get_class($this) . ".runCustomQueries() - running: ".$command."\n";
				$statement = $this->_mod_warehouse->handle()->prepare($command);
				$statement->execute();
			}
		}
		$this->_logger->info(get_class($this) . ".runCustomQueries():end");
	}
	
    public function setLogger(Log $logger)
    {
        $this->_logger = $logger;
    }

    //deprecated  and not used
    /*private function _addGrantDates($mod_warehouse)
    {
        print "*** Bring in the start and end dates for proposals\n";

        try {
            $proposal_results = $mod_warehouse->query("SELECT * from modw_pops.proposal where proposal_num not like 'Unknown'");

            if (count($proposal_results) > 0) {
                foreach ($proposal_results as $proposal) {
                    echo $proposal['proposal_num'], ' ';
                    //get the dates from tgcdb allocation and request table
                    $allocation_dates = $mod_warehouse->query("select
						date_format(min(al.initial_start_date), '%Y-%m-%d') as start_date,
						date_format(max(al.initial_end_date), '%Y-%m-%d') as end_date
						from
						modw.allocation al
						join modw.request req on req.id = al.request_id
						where
						req.proposal_number = :proposal_num", array(
                        'proposal_num' => $proposal['proposal_num']
                    ));

                    echo 'tgcdb ', implode(',', $allocation_dates[0]), " --- ";

                    //try and get the dates from pops/aami packets
                    $pops_dates = $mod_warehouse->query("select
						date_format(min(npcs.effect_date), '%Y-%m-%d') as start_date,
						date_format(max(npcs.end_date), '%Y-%m-%d') as end_date

						from modw_pops.npcs_sent npcs
						where
						 npcs.prop_num = :proposal_num and  npcs.prop_id <> -1 and npcs.mtg_id <> -1", array(
                        'proposal_num' => $proposal['proposal_num']
                    ));

                    echo 'pops ', implode(',', $pops_dates[0]), " -- ";

                    //figure out start date
                    if (isset($pops_dates[0]['start_date']) && $pops_dates[0]['start_date'] != '') {
                        $start_date = $pops_dates[0]['start_date'];
                    } else {
                        $start_date = $allocation_dates[0]['start_date'];
                    }

                    //figure out end date
                    if (isset($pops_dates[0]['end_date']) && $pops_dates[0]['end_date'] != '') {
                        $end_date = $pops_dates[0]['end_date'];
                    } else {
                        $end_date = $allocation_dates[0]['end_date'];
                    }
                    echo $start_date, ' ', $end_date, "\n";

                    //update the proposal record
                    $mod_warehouse->handle()->prepare("update modw_pops.proposal set start_date = :start_date, end_date = :end_date where prop_id = :prop_id")->execute(array(
                        'prop_id' => $proposal['prop_id'],
                        'start_date' => $start_date,
                        'end_date' => $end_date
                    ));


                }
            }
        }
        catch (Exception $e) {
            print 'addGrantDates Exception: ' . $e->getMessage() . "\n";
            return 'select 1';
        }
        return 'select 1';
    }*/
}

?>
