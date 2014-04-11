<?php

namespace Xdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;

class People extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            "
                SELECT
                    p.person_id AS id,
                    p.organization_id,
                    p.prefix,
                    COALESCE(TRIM(p.first_name), '') AS first_name,
                    COALESCE(TRIM(p.middle_name), '') AS middle_name,
                    COALESCE(TRIM(p.last_name), '') AS last_name,
                    p.department,
                    p.title,
                    em.email_address,
                    COALESCE(
                        CONCAT(
                            p.last_name,
                            ', ',
                            p.first_name,
                            COALESCE(
                                CONCAT(' ', p.middle_name),
                                ''
                            )
                        ),
                        p.last_name,
                        ''
                    ) AS long_name,
                    COALESCE(
                        CONCAT(
                            p.last_name,
                            ', ',
                            SUBSTR(p.first_name, 1, 1)
                        ),
                        p.last_name,
                        ''
                    ) AS short_name
                FROM hpcdb_people p
                JOIN hpcdb_organizations o
                    ON o.organization_id = p.organization_id
                LEFT OUTER JOIN hpcdb_email_addresses em
                    ON p.person_id = em.person_id
                ORDER BY long_name
            ",
            'person',
            array(
                'id',
                'organization_id',
                'prefix',
                'first_name',
                'middle_name',
                'last_name',
                'department',
                'title',
                'email_address',
                'long_name',
                'short_name',
                'order_id',
            )
        );
    }
}

