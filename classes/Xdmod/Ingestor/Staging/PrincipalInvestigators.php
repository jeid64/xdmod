<?php

namespace Xdmod\Ingestor\Staging;

use PDODBSynchronizingIngestor;

class PrincipalInvestigators extends PDODBSynchronizingIngestor
{
    public function __construct($dest_db, $src_db)
    {
        parent::__construct(
            $dest_db,
            $src_db,
            '
                SELECT
                    union_user_group_id AS person_id,
                    group_id            AS request_id
                FROM staging_group g
                JOIN staging_union_user_group uug
                    ON g.group_name = uug.union_user_group_name
            ',
            'hpcdb_principal_investigators',
            array(
                'person_id',
                'request_id',
            ),
            array(
                'person_id',
                'request_id',
            )
        );
    }
}

