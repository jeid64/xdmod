<?php

namespace Xdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class Group extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT group_name
                FROM shredded_job
                WHERE group_name IS NOT NULL
            ',
            'staging_group',
            'group_name',
            array(
                'group_name',
            )
        );
    }
}

