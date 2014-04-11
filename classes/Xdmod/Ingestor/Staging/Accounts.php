<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class Accounts extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            'SELECT group_id AS account_id FROM staging_group',
            'hpcdb_accounts',
            'account_id',
            array(
                'account_id',
            )
        );
    }
}

