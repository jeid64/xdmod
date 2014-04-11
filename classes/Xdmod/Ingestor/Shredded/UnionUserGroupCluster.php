<?php

namespace Xdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class UnionUserGroupCluster extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    user_name AS union_user_group_name,
                    cluster_name
                FROM shredded_job
                WHERE user_name IS NOT NULL
                    AND cluster_name IS NOT NULL
                UNION
                SELECT DISTINCT
                    group_name   AS union_user_group_name,
                    cluster_name
                FROM shredded_job
                WHERE group_name IS NOT NULL
                    AND cluster_name IS NOT NULL
            ',
            'staging_union_user_group_cluster',
            array(
                'union_user_group_name',
                'cluster_name',
            ),
            array(
                'union_user_group_name',
                'cluster_name',
            )
        );
    }
}

