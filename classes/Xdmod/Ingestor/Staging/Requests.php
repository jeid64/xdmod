<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class Requests extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    group_id AS request_id,
                    1        AS primary_fos_id,
                    group_id AS account_id
                FROM staging_group g
            ',
            'hpcdb_requests',
            'request_id',
            array(
                'request_id',
                'primary_fos_id',
                'account_id',
            )
        );
    }
}

