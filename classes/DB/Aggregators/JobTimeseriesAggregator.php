<?php

/**
 * @author: Amin Ghadersohi 7/1/2010
 */
class JobTimeseriesAggregator extends Aggregator
{
    public $_fields;
    public $_tablename;

    private $_time_period;

    function __construct($time_period)
    {
        $this->_time_period = $time_period;

        if ($time_period != 'day' && $time_period != 'week' && $time_period != 'month' && $time_period != 'quarter' && $time_period != 'year') {
            throw new Exception("Time period {$this->_time_period} is invalid.");
        }

        $this->_tablename = "jobfact_by_{$this->_time_period}";

		$wallduration_case_statement =  $this->getDistributionSQLCaseStatement('wallduration', ':seconds', 'start_time_ts', 'end_time_ts', ":{$this->_time_period}_start_ts",":{$this->_time_period}_end_ts");
		
		$local_charge_case_statement =  $this->getDistributionSQLCaseStatement('local_charge', ':seconds', 'start_time_ts', 'end_time_ts', ":{$this->_time_period}_start_ts",":{$this->_time_period}_end_ts");
		

        $this->_fields = array(
            new TableColumn("{$this->_time_period}_id", 'int(11)', ":{$this->_time_period}_id", true, false, "The id related to modw.{$this->_time_period}s."),
            new TableColumn('year', 'int(4)', ':year', true, false, "The year of the {$this->_time_period}"),
            new TableColumn("{$this->_time_period}", 'int(3)', ":{$this->_time_period}", true, false, "The {$this->_time_period} of the year."),
            //new TableColumn('name', 'varchar(200)', 'name', true, true),
            new TableColumn('person_id', 'int(7)', '', true, true, "The id of the person that ran the jobs.", true),
            new TableColumn('organization_id', 'int(6)', '', true, true, "The organization of the resource that the jobs ran on.", true),
            new TableColumn('person_organization_id', 'int(6)', '', true, true, "The organization of the person that ran the jobs.", true),
            new TableColumn('person_nsfstatuscode_id', 'int(3)', '', true, true, "The NSF status code of the person that ran the jobs.", true),
            //new TableColumn('organizationtype_id', 'int(11)', '', true),
            //new TableColumn('country_id', 'int(11)', '', true),
            //new TableColumn('state_id', 'int(11)', '', true),
            new TableColumn('resource_id', 'int(5)', '', true, true, "The resource on which the jobs ran", true),
            new TableColumn('resourcetype_id', 'int(3)', '', true, true, "The type of the resource on which the jobs ran.", true),
            new TableColumn('queue_id', 'char(50)', '', true, true, "The queue of the resource on which the jobs ran.", true),
            new TableColumn('fos_id', 'int(4)', '', true, true, "The field of science of the project to which the jobs belong.", true),
            //new TableColumn('request_id', 'int(11)','',true,true, "The request from which came the allocation that these jobs belong to.", true),
            new TableColumn('account_id', 'int(6)', '', true, true, "The id of the account record from which one can get charge number", true),
            new TableColumn('systemaccount_id', 'int(8)', '', true, true, "The id of the system account record from which one can get username on the resource the job ran on.", true),
            new TableColumn('allocation_id', 'int(7)', '', true, true, "The id of allocation these jobs used to run", true),
            new TableColumn('principalinvestigator_person_id', 'int(7)', '', true, true, "The PI that owns the project that funds these jobs", true),
            new TableColumn('piperson_organization_id', 'int(7)', 'coalesce(piperson_organization_id, 0)', true, false, "The organization of the PI that owns the project that funds these jobs", true),
            new TableColumn('jobtime_id', 'int(3)', '(select id from job_times jt where wallduration >= jt.min_duration and wallduration <= jt.max_duration)', true, false, "Job time is bucketing of wall time based on prechosen intervals in the modw.job_times table.", true),
            new TableColumn('nodecount_id', 'int(8)', 'nodecount', true, false, "Number of nodes each of the jobs used."),
		    new TableColumn('processors', 'int(8)', '(case when resource_id = 2020 then 1 else processors end)', true, false, "Number of processors each of the jobs used.", true),
            new TableColumn('processorbucket_id', 'int(3)', '(select id from processor_buckets pb where case when resource_id = 2020 then 1 else processors end between pb.min_processors and pb.max_processors)', false, true, "Processor bucket or job size buckets are prechosen in the modw.processor_buckets table.", true),
            new TableColumn('submitted_job_count', 'int(11)', "sum(case when submit_time_ts
                                                                        between :{$this->_time_period}_start_ts
                                                                            and :{$this->_time_period}_end_ts
                                                                   then case when resource_id = 2020 then processors else 1 end
                                                                 else 0
                                                             end)", false, true, "The number of jobs that started during this {$this->_time_period}. "),
            new TableColumn('job_count', 'int(11)', "sum(case when end_time_ts
                                                                        between :{$this->_time_period}_start_ts
                                                                            and :{$this->_time_period}_end_ts
                                                                   then case when resource_id = 2020 then processors else 1 end
                                                                 else 0
                                                             end)", false, true, "The number of jobs that ended during this {$this->_time_period}. "),
            new TableColumn('started_job_count', 'int(11)', "sum(case when start_time_ts
                                                                        between :{$this->_time_period}_start_ts
                                                                            and :{$this->_time_period}_end_ts
                                                                   then case when resource_id = 2020 then processors else 1 end
                                                                 else 0
                                                             end)", false, true, "The number of jobs that started during this {$this->_time_period}. "),
            new TableColumn('running_job_count', 'int(11)', 'sum(case when resource_id = 2020 then processors else 1 end)', false, true, "The number of jobs that were running during this {$this->_time_period}."),

            new TableColumn('wallduration', 'decimal(18,0)', "coalesce(sum( $wallduration_case_statement),0)", false, true, "(seconds) The wallduration of the jobs that were running during this period. This will only count the walltime of the jobs that fell during this {$this->_time_period}. If a job started in the previous {$this->_time_period}(s) the wall time for that {$this->_time_period} will be added to that {$this->_time_period}. Same logic is true if a job ends not in this {$this->_time_period}, but upcoming {$this->_time_period}s. "),
            new TableColumn('sum_wallduration_squared', 'double', "coalesce(sum( pow($wallduration_case_statement,2)),0)", false, true, "(seconds) The sum of the square of wallduration of the jobs that were running during this period. This will only count the walltime of the jobs that fell during this {$this->_time_period}. If a job started in the previous {$this->_time_period}(s) the wall time for that {$this->_time_period} will be added to that {$this->_time_period}. Same logic is true if a job ends not in this {$this->_time_period}, but upcoming {$this->_time_period}s. "),
            new TableColumn('waitduration', 'decimal(18,0)', "sum(
                    case when (start_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts )
                                  then waitduration
                         else 0
                    end
                )", false, true, "(seconds)The amount of time jobs waited to execute during this {$this->_time_period}."),
            new TableColumn('sum_waitduration_squared', 'double', "sum(
                    case when  (start_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts )
                                  then pow(waitduration,2)
                         else 0
                    end
                )", false, true, "(seconds)The sum of the square of the amount of time jobs waited to execute during this {$this->_time_period}."),
            new TableColumn('local_charge', 'decimal(18,0)', "sum( $local_charge_case_statement)", false, true, "The amount of the local_charge charged to jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its local_charge is distributed linearly across the {$this->_time_period}s it used."),

            new TableColumn('sum_local_charge_squared', 'double', "sum( pow( $local_charge_case_statement, 2) )", false, true, "The sum of the square of local_charge of jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its local_charge is distributed linearly across the {$this->_time_period}s it used."),
 
            new TableColumn('cpu_time', 'decimal(18,0)', "coalesce(sum( processors*$wallduration_case_statement),0)", false, true, "(seconds) The amount of the cpu_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its cpu_time is distributed linearly across the {$this->_time_period}s it used."),
            new TableColumn('sum_cpu_time_squared', 'double', "coalesce(sum( pow(processors*$wallduration_case_statement,2)),0)", false, true, "(seconds) The sum of the square of the amount of the cpu_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its cpu_time is distributed linearly across the {$this->_time_period}s it used."),
			
			new TableColumn('node_time', 'decimal(18,0)', "coalesce(sum( nodecount*$wallduration_case_statement),0)", false, true, "(seconds) The amount of the node_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its node_time is distributed linearly across the {$this->_time_period}s it used."),
            new TableColumn('sum_node_time_squared', 'double', "coalesce(sum( pow(nodecount*$wallduration_case_statement,2)),0)", false, true, "(seconds) The sum of the square of the amount of the node_time of the jobs pertaining to this {$this->_time_period}. If a job took more than one {$this->_time_period}, its node_time is distributed linearly across the {$this->_time_period}s it used."),
			
            new TableColumn('sum_weighted_expansion_factor', 'decimal(18,0)',
                  "sum( ((wallduration + waitduration) / wallduration) * nodecount * coalesce($wallduration_case_statement,0))", false, true, " this is the sum of expansion factor per job multiplied by nodecount and the [adjusted] duration of jobs that ran in this {$this->_time_period}s. "),
            new TableColumn('sum_job_weights', 'decimal(18,0)',
                  "sum( nodecount * coalesce($wallduration_case_statement,0))", false, true, " this is the sum of (nodecount multipled by the [adjusted] duration) for jobs that ran in this {$this->_time_period}s. ")
            //new TableColumn('date', 'date', 'date(start_time)', false,false)
        );

        if ($time_period == 'year') {
            unset($this->_fields[2]);
        }
    }

    function execute($modwdb, $dest_schema, $start_date, $end_date, $append = true, $infinidb = false)
    {
        $this->_logger->info(  get_class($this) . ".execute(start_date: $start_date, end_date: $end_date, append: $append)" );
        $startDateResult = $modwdb->query("select min(id) as id from {$this->_time_period}s
                                where timestamp('$start_date')
                                        between
                                                 {$this->_time_period}_start
                                             and {$this->_time_period}_end");
        $start_date_id   = 1;
        if (count($startDateResult) > 0) {
            $start_date_id = $startDateResult[0]['id'];
            if (!isset($start_date_id)) {
                $startDateResult = $modwdb->query("select case when '$start_date' < min({$this->_time_period}_start) then 1 else 999999999 end as id  from {$this->_time_period}s");

                if (count($startDateResult) > 0) {
                    $start_date_id = $startDateResult[0]['id'];
                }
            }
        }

        $endDateResult = $modwdb->query("select max(id) as id from {$this->_time_period}s
                                where timestamp('$end_date')
                                        between
                                                 {$this->_time_period}_start
                                             and {$this->_time_period}_end");
        $end_date_id   = -1;
        if (count($endDateResult) > 0) {
            $end_date_id = $endDateResult[0]['id'];
            if (!isset($end_date_id)) {
                $endDateResult = $modwdb->query("select case when '$end_date' < min({$this->_time_period}_start) then 0 else 999999999 end as id  from {$this->_time_period}s");
                if (count($endDateResult) > 0) {
                    $end_date_id = $endDateResult[0]['id'];
                }
            }
        }
        $this->_logger->info(  "start_{$this->_time_period}_id: $start_date_id, end_{$this->_time_period}_id: $end_date_id" );

        if ($append == true) {
            /*$modwdb->handle()->prepare("delete from  {$dest_schema}.{$this->_tablename}
            where {$this->_time_period}_id >= $start_date_id
            and {$this->_time_period}_id <= $end_date_id
            ")->execute();*/

            $altertable_statement = "alter table {$dest_schema}.{$this->_tablename}";
            foreach ($this->_fields as $field) {
                $altertable_statement .= " change {$field->getName()} {$field->getName()} {$field->getType()} " . ($field->isInGroupBy() ? "NOT NULL" : "NULL") . " COMMENT '" . ($field->isInGroupBy() ? "DIMENSION" : "FACT") . ": {$field->getComment()}', ";
            }
            $altertable_statement = trim($altertable_statement, ", ");

            $modwdb->handle()->prepare($altertable_statement)->execute();

        } else {
            $createtable_statement = "create table if not exists {$dest_schema}." . $this->_tablename . " ( ";

            foreach ($this->_fields as $field) {
                $createtable_statement .= " {$field->getName()} {$field->getType()} " . ($field->isInGroupBy() ? "NOT NULL" : "NULL") . " COMMENT '" . ($field->isInGroupBy() ? "DIMENSION" : "FACT") . ": {$field->getComment()}', ";
            }
            $createtable_statement = trim($createtable_statement, ", ");

            $createtable_statement .= ") engine = " . ($infinidb ? 'infinidb' : 'myisam') . " COMMENT='Jobfacts aggregated by {$this->_time_period}.';";
            //echo $createtable_statement;

            $modwdb->handle()->prepare("drop table if exists {$dest_schema}." . $this->_tablename)->execute();
            $modwdb->handle()->prepare($createtable_statement)->execute();
            //$modwdb->handle()->prepare("ALTER TABLE {$dest_schema}.".$this->_tablename." ADD `date_id` INT( 11 ) NOT NULL ")->execute();

            if ($infinidb !== true) {
                $index_fieldnames = array();
                foreach ($this->_fields as $field) {
                    if ($field->isInGroupBy()) {
                        $index_fieldnames[] = $field->getName();
                        $modwdb->handle()->prepare("create index index_{$this->_tablename}_{$field->getName()} using
                                                        hash on {$dest_schema}.{$this->_tablename} (" . $field->getName() . ")")->execute();
                    }
                }
                if (count($index_fieldnames) > 0) {
                    //    $modwdb->handle()->prepare("create index index_".$this->_tablename."_pk_2 using
                    //                                    hash on {$dest_schema}.".$this->_tablename." (".implode(',',$index_fieldnames).")")->execute();
                }
                //$modwdb->handle()->prepare("create index index_{$this->_tablename}_pk using
                //                                    hash on {$dest_schema}.{$this->_tablename} ({$this->_time_period}_id)")->execute();
                //$modwdb->handle()->prepare("create index index_".$this->_tablename."_resourcetype using
                //                                    hash on {$dest_schema}.".$this->_tablename." (resourcetype_id)")->execute();

                //$modwdb->handle()->prepare("create index index_".$this->_tablename."_pk_2 using
                //                                hash on {$dest_schema}.".$this->_tablename." ({$this->_time_period}_id, allocation_id, local_charge)")->execute();
            }
        }
        if ($infinidb !== true) {
            $noncube_fields = array();
            $groupby_fields = array();
            $formula_fields = array();

            foreach ($this->_fields as $field) {
                if (!$field->isInCube()) {
                    $noncube_fields[] = $field;
                } else if ($field->isInGroupBy()) {
                    $groupby_fields[] = $field;
                } else {
                    $formula_fields[] = $field;
                }
            }

            $insert_statement = "insert into {$dest_schema}." . $this->_tablename . " ( ";

            foreach ($noncube_fields as $field) {
                $insert_statement .= "{$field->getName()}, ";
            }
            foreach ($groupby_fields as $field) {
                $insert_statement .= "{$field->getName()}, ";
            }
            foreach ($formula_fields as $field) {
                $insert_statement .= "{$field->getName()}, ";
            }
            $insert_statement = trim($insert_statement, ", ");
            $insert_statement .= "  ) values (";
            foreach ($noncube_fields as $field) {
                $insert_statement .= ":{$field->getName()}, ";
            }
            foreach ($groupby_fields as $field) {
                $insert_statement .= ":{$field->getName()}, ";
            }
            foreach ($formula_fields as $field) {
                $insert_statement .= ":{$field->getName()}, ";
            }
            $insert_statement = trim($insert_statement, ", ");
            $insert_statement .= "  )";
            $this->_logger->debug($insert_statement);

            $select_statement = "
                        select SQL_NO_CACHE distinct
                        ";
            foreach ($noncube_fields as $field) {
                $select_statement .= "{$field->getFormula()} as {$field->getName()}, ";
            }
            foreach ($groupby_fields as $field) {
                $select_statement .= "{$field->getName()}, ";
            }
            foreach ($formula_fields as $field) {
                $select_statement .= "{$field->getFormula()} as {$field->getName()}, ";
            }
            $select_statement = trim($select_statement, ", "); //use index (index_jobfact_time_ts)

            //(start_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts) or
            //(:{$this->_time_period}_start_ts between start_time_ts and end_time_ts) or
            $select_statement .= "
                        from jobfact
                        where
                              (end_time_ts between :{$this->_time_period}_start_ts and :{$this->_time_period}_end_ts) or
                              (:{$this->_time_period}_end_ts between start_time_ts and end_time_ts)
                        group by ";
            foreach ($noncube_fields as $field) {
                $select_statement .= "{$field->getName()}, ";
            }
            foreach ($groupby_fields as $field) {
                $select_statement .= "{$field->getName()}, ";
            }
            $select_statement = trim($select_statement, ", ");

            $this->_logger->debug($select_statement);

            $prepared_insert_statement = $modwdb->handle()->prepare($insert_statement);

            $dates_query = "select SQL_NO_CACHE distinct
                                    id,
                                    `year`,
                                    `{$this->_time_period}`,
                                    {$this->_time_period}_start,
                                    {$this->_time_period}_end,
                                    {$this->_time_period}_start_ts,
                                    {$this->_time_period}_end_ts,
                                    hours,
                                    seconds
                                 from {$this->_time_period}s
                                 where id >= $start_date_id
                                  and id <= $end_date_id
                                 order by `year` desc, `{$this->_time_period}` desc";

            $dates_results = $modwdb->query($dates_query);

            foreach ($dates_results as $date_result) {
                $period_id       = $date_result['id'];
                $period_start    = $date_result["{$this->_time_period}_start"];
                $period_end      = $date_result["{$this->_time_period}_end"];
                $period_start_ts = $date_result["{$this->_time_period}_start_ts"];
                $period_end_ts   = $date_result["{$this->_time_period}_end_ts"];
                $year            = $date_result['year'];
                $time_period     = $date_result["{$this->_time_period}"];
                $period_hours    = $date_result["hours"];
                $period_seconds  = $date_result["seconds"];
                $this->_logger->debug(json_encode($date_result));

                $statement = $modwdb->handle()->prepare($select_statement);
                $statement->execute(array(
                    "{$this->_time_period}_id" => $period_id,
                    //":{$this->_time_period}_hours" => $period_hours,
                    //"{$this->_time_period}_start" => $period_start,
                    //"{$this->_time_period}_end" => $period_end,
                    "{$this->_time_period}" => $time_period,
                    'year' => $year,
                    "{$this->_time_period}_start_ts" => $period_start_ts,
                    "{$this->_time_period}_end_ts" => $period_end_ts,
                    //'hours' => $period_hours,
                    'seconds' => $period_seconds
                ));
                if ($append) {
                    $modwdb->handle()->prepare("delete from  {$dest_schema}.{$this->_tablename}
                                                where {$this->_time_period}_id = $period_id
                                               ")->execute();
                }
                while ($row = $statement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
                    $prepared_insert_statement->execute($row);
                }

            }
        }
        $this->_logger->debug('Optimizing table');
        $modwdb->handle()->prepare("optimize table {$dest_schema}.{$this->_tablename}")->execute();
    }
}

