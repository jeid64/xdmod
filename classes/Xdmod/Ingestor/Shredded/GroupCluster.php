<?php

namespace Xdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class GroupCluster extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    group_name,
                    cluster_name
                FROM shredded_job
                WHERE
                    group_name IS NOT NULL
                    AND cluster_name IS NOT NULL
            ',
            'staging_group_cluster',
            array(
                'group_name',
                'cluster_name',
            ),
            array(
                'group_name',
                'cluster_name',
            )
        );
    }
}

