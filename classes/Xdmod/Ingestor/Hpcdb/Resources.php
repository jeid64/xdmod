<?php

namespace Xdmod\Ingestor\Hpcdb;

use PDODBMultiIngestor;
use Xdmod\Config;

class Resources extends PDODBMultiIngestor
{
    function __construct($dest_db, $src_db, $start_date = '1997-01-01', $end_date = '2010-01-01')
    {
        $config = Config::factory();
        $resourceConfig = $config['resources'];

        $updateStatements = array();

        $dbh = $dest_db->handle();

        foreach ($resourceConfig as $id => $resource) {
            if (!is_numeric($id)) { continue; }

            $updateStatements[] = sprintf(
                'UPDATE resourcefact SET processors = %s, q_nodes = %s, q_ppn = %s WHERE name = %s',
                $dbh->quote($resource['processors']),
                $dbh->quote($resource['nodes']),
                $dbh->quote($resource['ppn']),
                $dbh->quote($resource['name'])
            );
        }

        parent::__construct(
            $dest_db,
            $src_db,
            array(),
            '
                SELECT
                    r.resource_id AS id,
                    r.resource_type_id AS resourcetype_id,
                    r.organization_id,
                    r.resource_name AS name,
                    r.resource_code AS code,
                    r.resource_description AS description
                FROM hpcdb_resources r
                ORDER BY r.resource_id
            ',
            'resourcefact',
            array(
                'id',
                'resourcetype_id',
                'organization_id',
                'name',
                'code',
                'description',
            ),
            $updateStatements
        );
    }
}

