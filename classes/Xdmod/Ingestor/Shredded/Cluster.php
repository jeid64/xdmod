<?php

namespace Xdmod\Ingestor\Shredded;

use PDODBSynchronizingIngestor;

class Cluster extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT DISTINCT cluster_name
                FROM shredded_job
                WHERE cluster_name IS NOT NULL
            ',
            'staging_cluster',
            'cluster_name',
            array(
                'cluster_name',
            )
        );
    }
}

