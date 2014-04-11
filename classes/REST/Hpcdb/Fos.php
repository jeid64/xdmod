<?php

namespace Hpcdb;

use Exception;

class Fos extends RestAction
{

    /**
     * Find a field of science with the specified abbreviation or
     * description.
     */
    protected function findAction()
    {
        $params = $this->_parseRestArguments();

        $sql = '
            SELECT
                field_of_science_id,
                parent_id,
                description,
                abbrev
            FROM hpcdb_fields_of_science
        ';

        $columns = array(
            'description',
            'abbrev',
        );

        $dbParams = array();

        foreach ($columns as $column) {
            if (isset($params[$column])) {
                $dbParams[$column] = urldecode($params[$column]);
            }
        }

        if (count($dbParams) == 0) {
            $msg = 'No abbreviation or description found.';
            throw new Exception($msg);
        }

        $sql .= ' WHERE ' . implode(
            ' AND ',
            array_map(
                function ($column) { return "$column = :$column"; },
                array_keys($dbParams)
            )
        );

        $fos = $this->db->query($sql, $dbParams);

        return array(
            'success' => true,
            'total'   => count($fos),
            'results' => $fos,
        );
    }

    /**
     * List all fields of science in the HPcDB.
     */
    protected function listAction()
    {
        $sql = '
            SELECT
                field_of_science_id,
                parent_id,
                description,
                abbrev
            FROM hpcdb_fields_of_science
        ';
        $fos = $this->db->query($sql);

        return array(
            'success' => true,
            'total'   => count($fos),
            'results' => $fos,
        );
    }

    /**
     * Create a field of science.
     */
    protected function createAction()
    {
        $params = $this->_parseRestArguments('description');

        $sql = '
            INSERT INTO hpcdb_fields_of_science (
                parent_id,
                description,
                abbrev
            ) VALUES (
                :parent_id,
                :description,
                :abbrev
            )
        ';

        $dbParams = array(
            'description' => $params['description'],
        );

        $optionalParams = array('parent_id', 'abbrev');

        foreach ($optionalParams as $param) {
            $dbParams[$param]
                = isset($params[$param]) ? $params[$param] : null;
        }

        $fosId = (int)$this->db->insert($sql, $dbParams);

        return array(
            'success' => true,
            'total'   => 1,
            'results' => array(
                array('field_of_science_id' => $fosId),
            ),
        );
    }
}

