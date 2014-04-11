<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class AllocationsOnResources extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    group_cluster_id AS allocation_id,
                    cluster_id       AS resource_id
                FROM staging_group_cluster gc
                JOIN staging_cluster c
                    ON gc.cluster_name = c.cluster_name
            ',
            'hpcdb_allocations_on_resources',
            'allocation_id',
            array(
                'allocation_id',
                'resource_id',
            )
        );
    }
}

