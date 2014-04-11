<?php

namespace Xdmod\Ingestor\Hpcdb;

use Exception;
use PDODBMultiIngestor;

class Jobs extends PDODBMultiIngestor
{
    function __construct(
        $dest_db,
        $src_db,
        $start_date = null,
        $end_date = null
    ) {

        $dest_table = "jobfact";

        $src_query = "
            SELECT
                j.job_id,
                j.local_jobid,
                p.person_id,
                p.organization_id AS person_organization_id,
                o.organization_id,
                r.resource_id,
                r.resource_type_id AS resourcetype_id,
                COALESCE(j.queue, 'NA') AS queue_id,
                req.primary_fos_id AS fos_id,
                j.account_id,
                j.allocation_id,
                pi.person_id AS principalinvestigator_person_id,
                pip.organization_id AS piperson_organization_id,
                sa.system_account_id AS systemaccount_id,
                FROM_UNIXTIME(start_time) AS start_time,
                FROM_UNIXTIME(end_time) AS end_time,
                FROM_UNIXTIME(submit_time) AS submit_time,
                wallduration,
                start_time - submit_time AS waitduration,
                jobname AS name,
                nodecount,
                j.processors,
                (end_time - start_time) * j.processors AS cpu_time,
                j.processors AS processors_original,
                start_time AS start_time_ts,
                end_time AS end_time_ts,
                submit_time AS submit_time_ts,
                req.request_id,
                ar.resource_id AS allocation_resource_id
            FROM hpcdb_jobs j
            JOIN hpcdb_accounts ac
                ON j.account_id = ac.account_id
            JOIN hpcdb_allocations alloc
                ON j.allocation_id = alloc.allocation_id
            JOIN hpcdb_allocation_breakdown alb
                ON j.allocation_breakdown_id = alb.allocation_breakdown_id
            JOIN hpcdb_requests req
                ON alloc.account_id = req.account_id
            JOIN hpcdb_system_accounts sa
                ON j.username = sa.username
                AND j.person_id = sa.person_id
                AND j.resource_id = sa.resource_id
            JOIN hpcdb_resources r
                ON j.resource_id = r.resource_id
            JOIN hpcdb_resources ar
                ON alloc.resource_id = ar.resource_id
            JOIN hpcdb_people p
                ON sa.person_id = p.person_id
            JOIN hpcdb_organizations o
                ON r.organization_id = o.organization_id
            JOIN hpcdb_principal_investigators pi
                ON req.request_id = pi.request_id
            JOIN hpcdb_people pip
                ON pi.person_id = pip.person_id
        ";

        // If start and end times are provided, re-ingest all the jobs
        // in that date range.  Otherwise, only ingest new jobs.
        if ($start_date !== null || $end_date !== null) {
            if ($start_date === null || $end_date === null) {
                throw new Exception('Both start and end date are needed.');
            }
            $src_query .= "
                WHERE end_time >= UNIX_TIMESTAMP('$start_date')
                   AND end_time <= UNIX_TIMESTAMP('$end_date')
            ";

            // Delete all the jobs in the supplied date range.
            $delete_stmt = "
                DELETE FROM $dest_table
                WHERE end_time_ts >= UNIX_TIMESTAMP('$start_date')
                   AND end_time_ts <= UNIX_TIMESTAMP('$end_date')
            ";
        } else {
            // Don't delete anything and only ingest new jobs.
            $sql = "SELECT MAX(job_id) AS max_id FROM $dest_table";
            list($row) = $dest_db->query($sql);
            if ($row['max_id'] != null) {
                $src_query .= ' WHERE j.job_id > ' . $row['max_id'];
            }
            $delete_stmt = 'nodelete';
        }

        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            $src_query,
            $dest_table,
            array(
                'job_id',
                'local_jobid',
                'person_id',
                'person_organization_id',
                'organization_id',
                'resource_id',
                'resourcetype_id',
                'queue_id',
                'fos_id',
                'account_id',
                'allocation_id',
                'principalinvestigator_person_id',
                'piperson_organization_id',
                'systemaccount_id',
                'start_time',
                'end_time',
                'submit_time',
                'wallduration',
                'waitduration',
                'name',
                'nodecount',
                'processors',
                'cpu_time',
                'processors_original',
                'start_time_ts',
                'end_time_ts',
                'submit_time_ts',
                'request_id',
                'allocation_resource_id',
            ),
            array(),
            $delete_stmt
        );
    }
}

