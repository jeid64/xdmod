<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class Allocations extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            "
                SELECT
                    group_cluster_id AS allocation_id,
                    cluster_id       AS resource_id,
                    group_id         AS account_id
                FROM staging_group_cluster gc
                JOIN staging_cluster c
                    ON gc.cluster_name = c.cluster_name
                JOIN staging_group g
                    ON gc.group_name = g.group_name
            ",
            'hpcdb_allocations',
            'allocation_id',
            array(
                'allocation_id',
                'resource_id',
                'account_id',
            )
        );
    }
}

