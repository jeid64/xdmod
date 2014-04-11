<?php

namespace Hpcdb;

class Request extends RestAction
{

    /**
     * Get a list of all requests in the HPcDB.
     */
    protected function listAction()
    {
        $params = $this->_parseRestArguments();

        $sql = '
            SELECT
                r.request_id,
                r.primary_fos_id,
                r.account_id,
                p.person_id
            FROM hpcdb_requests r
            JOIN hpcdb_principal_investigators pi
                ON r.request_id = pi.request_id
            JOIN hpcdb_people p
                ON pi.person_id = p.person_id
        ';
        $requests = $this->db->query($sql);

        return array(
            'success' => true,
            'total'   => count($requests),
            'results' => $requests,
        );
    }

    /**
     * Update a request.
     */
    protected function updateAction()
    {
        $params = $this->_parseRestArguments('request_id');

        $sql = '
            UPDATE hpcdb_requests SET
                primary_fos_id = :primary_fos_id
            WHERE request_id = :request_id
        ';
        //$count = $this->db->execute($sql, $dbParams);

        return array(
            'success' => true,
            'total'   => 1,
            'results' => array(
                array('request_id' => $params['request_id']),
            ),
        );
    }
}

