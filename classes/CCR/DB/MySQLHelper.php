<?php

namespace CCR\DB;

use CCR\DB\MySQLDB;

class MySQLHelper
{

    /**
     * @var \Log
     */
    protected $logger;

    /**
     * @var MySQLDB
     */
    protected $db;

    /**
     * Factory method.
     *
     * @param MySQLDB $db
     *
     * @return MySQLHelper
     */
    public static function factory(MySQLDB $db)
    {
        return new static($db);
    }

    /**
     * Constructor.
     *
     * @param MySQLDB $db
     */
    protected function __construct(MySQLDB $db)
    {
        $this->db     = $db;
        $this->logger = \Log::singleton('null');
    }

    /**
     * Set the logger.
     *
     * @param \Log $logger
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Drop a table.
     *
     * @param string $tableName
     */
    public function dropTable($tableName)
    {
        $sql = 'DROP TABLE ' . $this->db->getHandle()->quote($tableName);

        $this->logger->debug("Drop statement: $sql");

        $this->db->execute($sql);
    }

    /**
     * Create a table.
     *
     * @param string $tableName
     * @param array $columnDefs Column definitions.
     * @param array $keyDefs Key definitions.
     */
    public function createTable(
        $tableName,
        array $columnDefs,
        array $keyDefs = array()
    ) {

        $columnSql = array();
        $keySql    = array();

        foreach ($columnDefs as $col) {
            $def = $col['name'] . ' ' . $col['type'];

            $nullable = isset($col['nullable']) && $col['nullable'] === true;
            $auto     = isset($col['auto'])     && $col['auto']     === true;
            $unique   = isset($col['unique'])   && $col['unique']   === true;

            if (!$nullable) {
                $def .= ' NOT NULL';
            }

            if ($auto) {
                $def .= ' AUTO_INCREMENT';
            }

            if ($unique) {
                $def .= ' UNIQUE';
            }

            if (isset($col['key'])) {
                if ($col['key'] === 'primary') {
                    $keySql[] = "PRIMARY KEY ({$col['name']})";
                } elseif ($col['key'] === true) {
                    $keySql[] = "KEY {$col['name']} ({$col['name']})";
                }
            }

            $columnSql[] = $def;
        }

        foreach ($keyDefs as $key) {
            $def = '';

            if (isset($key['type'])) {
                $def .= strtoupper($key['type']);
            }

            $def .= ' KEY';

            if (isset($key['name'])) {
                $def .=  ' ' . $key['name'];
            }

            $def .= ' (' . implode(', ', $key['columns']) . ')';

            $keySql[] = $def;
        }

        $sql = "CREATE TABLE $tableName (\n"
            . implode(",\n", array_merge($columnSql, $keySql))
            . "\n)";

        $this->logger->debug("Create statement:\n$sql");

        $this->db->execute($sql);
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function tableExists($tableName)
    {
        $sql = '
            SELECT COUNT(*) AS count
            FROM information_schema.tables
            WHERE table_schema = :schema_name
                AND table_name = :table_name
        ';
        $this->logger->debug("Query: $sql");

        list($row) = $this->db->query($sql, array(
            'schema_name' => $this->db->_db_name,
            'table_name'  => $tableName,
        ));

        return $row['count'] > 0;
    }
}

