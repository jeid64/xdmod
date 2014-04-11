<?php

namespace Hpcdb;

use Exception;
use CCR\DB;
use aRestAction;
use RestElements;

class RestAction extends aRestAction
{

    /**
     * HPcDB database instance.
     *
     * @var Database
     */
    protected $db;

    /**
     * Factory method implementation.
     *
     * @param RestElements $request
     */
    public static function factory(RestElements $request)
    {
        return new static($request);
    }

    /**
     * Constructor.
     *
     * @param RestElements $request
     */
    protected function __construct(RestElements $request)
    {
        parent::__construct($request);
        $this->db = DB::factory('hpcdb');
    }

    /**
     * @inherit
     */
    public function __call($target, $arguments)
    {
        $this->_authenticateUser(ROLE_ID_MANAGER);

        $method = $target . ucfirst(strtolower($this->_operation));

        if (!method_exists($this, $method)) {
            throw new Exception("Method '$method' not found ...");
        }

        return $this->$method($arguments);
    }

    /**
     * Construct a SQL update statement and parameter array.
     *
     * @param string $table Name of the table to update.
     * @param array $updateColumns Associative array of column names as
     *   keys and the value to update those columns.
     * @param array $whereColumns Associative array of columns names as
     *   keys and the value of that column in the database for the row
     *   that should be updated.
     *
     * @return array First element is the SQL, second element is an
     *   array of parameters suitable for executing the query.
     */
    protected function buildUpdateQuery(
        $table,
        array $updateColumns,
        array $whereColumns
    ) {
        $sql = 'UPDATE ' . $table;

        $sql .= ' SET ' . implode(
            ', ',
            array_map(
                function ($column) { return "$column = :$column"; },
                array_keys($updateColumns)
            )
        );

        $sql .= ' WHERE ' . implode(
            ' AND ',
            array_map(
                function ($column) { return "$column = :$column"; },
                array_keys($whereColumns)
            )
        );

        $params = $updateColumns + $whereColumns;

        return array($sql, $params);
    }
}

