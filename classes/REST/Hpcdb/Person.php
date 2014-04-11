<?php

namespace Hpcdb;

use Exception;

class Person extends RestAction
{

    /**
     * List all the people.
     */
    protected function listAction()
    {
        $sql = '
            SELECT
                person_id,
                first_name,
                middle_name,
                last_name
            FROM hpcdb_people
        ';
        $people = $this->db->query($sql);

        return array(
            'success' => true,
            'total'   => count($people),
            'results' => $people,
        );
    }

    /**
     * Update a person in the HPcDB.
     */
    protected function updateAction()
    {
        $params = $this->_parseRestArguments('person_id');

        $columns = array('first_name', 'middle_name', 'last_name');

        $updateColumns = array();

        foreach ($columns as $column) {
            if (isset($params[$column])) {
                $updateColumns[$column] = $params[$column];
            }
        }

        if (count($updateColumns) == 0) {
            throw new Exception("No update data found in input");
        }

        list($sql, $dbParams) = $this->buildUpdateQuery(
            'hpcdb_people',
            $updateColumns,
            array('person_id' => $params['person_id'])
        );

        $count = $this->db->execute($sql, $dbParams);

        if ($count == 0) {
            return array(
                'success' => false,
                'total'   => 0,
                'message' => 'No person updated',
            );
        }

        return array(
            'success' => true,
            'total'   => 1,
            'results' => array(
                array('person_id' => $params['person_id']),
            ),
        );
    }
}

