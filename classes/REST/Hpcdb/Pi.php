<?php

namespace Hpcdb;

class Pi extends RestAction
{

    /**
     * List all the principle investigators in the HPcDB.
     */
    protected function listAction()
    {
        $sql = '
            SELECT
                pi.person_id,
                pi.request_id,
                p.first_name,
                p.middle_name,
                p.last_name,
                r.primary_fos_id,
                (
                    SELECT username
                    FROM hpcdb_system_accounts sa
                    WHERE sa.person_id = pi.person_id
                    LIMIT 1
                ) AS username
            FROM hpcdb_principal_investigators pi
            JOIN hpcdb_people p ON pi.person_id = p.person_id
            JOIN hpcdb_requests r ON pi.request_id = r.request_id
        ';
        $pis = $this->db->query($sql);

        return array(
            'success' => true,
            'total'   => count($pis),
            'results' => $pis,
        );
    }

    /**
     * Update a principal investigator.
     */
    protected function updateAction()
    {
        $params = $this->_parseRestArguments('person_id/primary_fos_id');

        $dbParams = array(
            'person_id'      => $params['person_id'],
            'primary_fos_id' => $params['primary_fos_id'],
        );

        $sql = '
            UPDATE hpcdb_requests r
            JOIN hpcdb_principal_investigators pi
                ON r.request_id = pi.request_id
            SET primary_fos_id = :primary_fos_id
            WHERE person_id = :person_id
        ';
        $this->db->execute($sql, $dbParams);

        return array(
            'success' => true,
            'total'   => 1,
            'results' => array(
                array(
                    'person_id'      => $params['person_id'],
                    'primary_fos_id' => $params['primary_fos_id'],
                ),
            ),
        );
    }
}

