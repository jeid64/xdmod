<?php

namespace Xdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class UnionUserGroup extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT
                    user_name AS union_user_group_name
                FROM shredded_job
                WHERE user_name IS NOT NULL
                UNION
                SELECT DISTINCT
                    group_name AS union_user_group_name
                FROM shredded_job
                WHERE group_name IS NOT NULL
            ',
            'staging_union_user_group',
            'union_user_group_name',
            array(
                'union_user_group_name',
            )
        );
    }
}

