<?php

namespace Hpcdb;

class User extends RestAction
{

    /**
     * List all the system users in the HPcDB.
     */
    protected function listAction()
    {
        $sql = '
            SELECT
                resource_id,
                person_id,
                username
            FROM hpcdb_system_accounts
        ';
        $users = $this->db->query($sql);

        return array(
            'success' => true,
            'results' => $users,
        );
    }
}

