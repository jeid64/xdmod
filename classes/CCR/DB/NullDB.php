<?php
/**
 * Null database implementation.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace CCR\DB;

class NullDB implements Database
{
    public function connect()
    {
    }

    public function destroy()
    {
    }

    public function insert($statement, $params = array())
    {
    }

    public function query(
        $query,
        array $params = array(),
        $returnStatement = false
    ) {
    }

    public function execute($query, array $params = array())
    {
    }
}

