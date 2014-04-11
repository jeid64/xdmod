<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class SystemAccounts extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    cluster_id                AS resource_id,
                    union_user_group_id       AS person_id,
                    uug.union_user_group_name AS username,
                    UNIX_TIMESTAMP()          AS ts
                FROM staging_union_user_group_cluster uugc
                LEFT JOIN staging_union_user_group uug
                    ON uugc.union_user_group_name = uug.union_user_group_name
                LEFT JOIN staging_cluster c
                    ON uugc.cluster_name = c.cluster_name
            ',
            'hpcdb_system_accounts',
            array(
                'resource_id',
                'person_id',
            ),
            array(
                'resource_id',
                'person_id',
                'username',
                'ts',
            )
        );
    }
}

