<?php

namespace Xdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class UserGroupCluster extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    user_name,
                    group_name,
                    cluster_name
                FROM shredded_job
                WHERE user_name IS NOT NULL
                    AND group_name IS NOT NULL
                    AND cluster_name IS NOT NULL
            ',
            'staging_user_group_cluster',
            array(
                'user_name',
                'group_name',
                'cluster_name',
            ),
            array(
                'user_name',
                'group_name',
                'cluster_name',
            )
        );
    }
}

