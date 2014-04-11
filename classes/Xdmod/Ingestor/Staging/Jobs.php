<?php

namespace Xdmod\Ingestor\Staging;

use PDODBMultiIngestor;

class Jobs extends PDODBMultiIngestor
{
    public function __construct($dest_db, $src_db)
    {
        $src_query = '
            SELECT
                id                    AS job_id,
                union_user_group_id   AS person_id,
                cluster_id            AS resource_id,
                user_group_cluster_id AS allocation_breakdown_id,
                group_cluster_id      AS allocation_id,
                group_id              AS account_id,
                job_id                AS local_jobid,
                start_time            AS start_time,
                end_time              AS end_time,
                submission_time       AS submit_time,
                wallt                 AS wallduration,
                job_name              AS jobname,
                nodes                 AS nodecount,
                cpus                  AS processors,
                queue_name            AS queue,
                UNIX_TIMESTAMP()      AS ts,
                mem                   AS memory,
                j.user_name           AS username
            FROM staging_job j
            LEFT JOIN staging_union_user_group uug
                ON j.user_name = uug.union_user_group_name
            LEFT JOIN staging_group g
                ON j.group_name = g.group_name
            LEFT JOIN staging_cluster c
                ON j.cluster_name = c.cluster_name
            LEFT JOIN staging_group_cluster gc
                ON j.group_name = gc.group_name
                AND j.cluster_name = gc.cluster_name
            LEFT JOIN staging_user_group_cluster ugc
                ON j.user_name = ugc.user_name
                AND j.group_name = ugc.group_name
                AND j.cluster_name = ugc.cluster_name
        ';

        $sql = 'SELECT MAX(job_id) AS max_id FROM hpcdb_jobs';
        list($row) = $dest_db->query($sql);
        if ($row['max_id'] != null) {
            $src_query .= 'WHERE j.id > ' . $row['max_id'];
        }

        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            $src_query,
            'hpcdb_jobs',
            array(
                'job_id',
                'person_id',
                'resource_id',
                'allocation_breakdown_id',
                'allocation_id',
                'account_id',
                'local_jobid',
                'start_time',
                'end_time',
                'submit_time',
                'wallduration',
                'jobname',
                'nodecount',
                'processors',
                'queue',
                'ts',
                'memory',
                'username',
            ),
            array(),
            'nodelete'
        );
    }
}

