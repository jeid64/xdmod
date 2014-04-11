<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class AllocationBreakdown extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    user_group_cluster_id AS allocation_breakdown_id,
                    union_user_group_id   AS person_id,
                    group_cluster_id      AS allocation_id,
                    100                   AS percentage
                FROM staging_user_group_cluster ugc
                LEFT JOIN staging_group_cluster gc
                    ON ugc.group_name = gc.group_name
                    AND ugc.cluster_name = gc.cluster_name
                LEFT JOIN staging_union_user_group uug
                    ON ugc.user_name = uug.union_user_group_name
            ',
            'hpcdb_allocation_breakdown',
            'allocation_breakdown_id',
            array(
                'allocation_breakdown_id',
                'person_id',
                'allocation_id',
                'percentage',
            )
        );
    }
}

