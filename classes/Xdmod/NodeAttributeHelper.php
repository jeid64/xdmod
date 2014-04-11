<?php

namespace Xdmod;

use DOMXPath;
use CCR\DB\MySQLDB;
use CCR\DB\MySQLHelper;
use Xdmod\Config;

class NodeAttributeHelper
{

    /**
     * The name of the node attribute table.
     *
     * @var string
     */
    protected $tableName;

    /**
     * The name of the node attribute table's primary key column.
     *
     * @var string
     */
    protected $pkName;

    /**
     * The node attribute table configuration data.
     *
     * @var array
     */
    protected $config;

    /**
     * Log object.
     *
     * @var \Log
     */
    protected $logger;

    /**
     * Database connection.
     *
     * @var MySQLDB
     */
    protected $db;

    /**
     * MySQL helper object.
     *
     * @var MySQLHelper
     */
    protected $helper;

    /**
     * The names of the node attributes.
     *
     * @var array
     */
    protected $attributeNames;

    /**
     * Factory method.
     *
     * @param MySQLDB $db
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
        $config = Config::factory();
        $this->config = $config['node_attributes'];

        $this->tableName = $this->config['name'];

        $pkColumns = array_values(
            array_filter(
                $this->config['columns'],
                function ($col) {
                    return isset($col['key']) && $col['key'] === 'primary';
                }
            )
        );

        $this->pkName = $pkColumns[0]['name'];

        $this->logger = \Log::singleton('null');

        $this->db = $db;

        $this->helper = MySQLHelper::factory($db);
    }

    /**
     * Set the logger.
     *
     * @param \Log $logger
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
        $this->helper->setLogger($logger);
    }

    /**
     * Create the node attribute table if it doesn't already exist.
     */
    public function createTable()
    {
        if (!$this->helper->tableExists($this->tableName)) {
            $this->logger->notice("Creating table {$this->tableName}");

            $this->helper->createTable(
                $this->tableName,
                $this->config['columns'],
                $this->config['keys']
            );
        }
    }

    /**
     * Get all of the node attribute names.
     */
    public function getAttributeNames()
    {
        if (!isset($this->attributeNames)) {
            $this->attributeNames = array_map(
                function ($col) { return $col['name']; },
                $this->config['columns']
            );
        }

        return $this->attributeNames;
    }

    /**
     * Get node attribute data from an XPath object.
     *
     * @param DOMXPath $xpath
     *
     * @return array
     */
    public function getNodeDataFromXPath(DOMXPath $xpath)
    {
        $this->logger->debug('Querying XPath for nodes');

        $nodes = $xpath->query('//Node');

        if ($nodes === false) {
            throw new Exception('XPath query failed');
        }

        $nodesData = array();

        $attrNames = $this->getAttributeNames();

        foreach ($nodes as $node) {
            $nodeData = array();
            foreach ($node->childNodes as $child) {
                $name  = $child->nodeName;
                $value = $child->nodeValue;

                if (in_array($name, $attrNames)) {
                    $nodeData[$name] = $value;
                }
            }
            $nodesData[] = $nodeData;
        }

        return $nodesData;
    }

    /**
     * Update node attribute data from an XPath object.
     *
     * @param DOMXPath $xpath
     * @param string $resource
     * @param string $startDate
     * @param string $endDate
     * @param bool $noAppend
     *
     * @return array
     *     - insert_count => The number of records inserted.
     *     - update_count => The number of records updated.
     */
    public function updateNodeAttrFromXPath(
        DOMXPath $xpath,
        $resource,
        $startDate,
        $endDate,
        $noAppend
    ) {
        $this->logger->info('Updating database');

        $insertCount = $updateCount = 0;

        $nodesData = $this->getNodeDataFromXPath($xpath);

        $this->db->execute('START TRANSACTION');

        try {
            foreach ($nodesData as $xmlData) {
                $dbData
                    = $this->getMostRecentNodeData($resource, $xmlData['name']);

                $sameAttrs
                    = $dbData === null
                    ? false
                    : $this->compareNodeData($xmlData, $dbData);

                if ($sameAttrs && !$noAppend) {
                    # TODO: Make sure new end date is after most recent
                    # end date.
                    $this->updateNodeEndDate(
                        $dbData[$this->pkName],
                        $endDate
                    );
                    $updateCount++;
                } else {
                    $xmlData['resource'] = $resource;
                    $xmlData['start_date'] = $startDate;
                    $xmlData['end_date'] = $endDate;
                    $this->insertNodeData($xmlData);
                    $insertCount++;
                }
            }
        } catch (Exception $e) {
            $this->db->execute('ROLLBACK');
            throw $e;
        }

        $this->db->execute('COMMIT');

        return array(
            'insert_count' => $insertCount,
            'update_count' => $updateCount,
        );
    }

    /**
     * Get the most recent attribute data for a given node.
     *
     * @param string $resource The name of the resource.
     * @param string $name The name of the node.
     */
    public function getMostRecentNodeData($resource, $name)
    {
        $sql = "
            SELECT *
            FROM {$this->tableName}
            WHERE resource = :resource
                AND name = :name
            ORDER BY end_date DESC
            LIMIT 1
        ";
        $this->logger->debug("Query: $sql");

        $rows = $this->db->query(
            $sql,
            array(
                'resource' => $resource,
                'name'     => $name,
            )
        );

        if (count($rows) === 0) {
            return null;
        }

        return $rows[0];
    }

    /**
     * Get the most recent date that node attributes have been recorded
     * for a given resource.
     *
     * @param string $resource The name of the resource.
     *
     * @return string The date in YYYY-MM-DD format.
     */
    public function getMostRecentDate($resource)
    {
        $sql = "
            SELECT MAX(end_date) AS max_end_date
            FROM {$this->tableName}
            WHERE resource = :resource
        ";
        $this->logger->debug("Query: $sql");

        list($row) = $this->db->query($sql, array('resource' => $resource));

        return $row['max_end_date'];
    }

    /**
     * Get all node attribute data for a given date.
     *
     * @param string $resource The name of the resource.
     * @param string $date The date in YYYY-MM-DD format.
     *
     * @return array
     */
    public function getNodesForDate($resource, $date)
    {
        $sql = "
            SELECT *
            FROM {$this->tableName}
            WHERE
                resource = :resource
                AND start_date <= :date1
                AND end_date >= :date2
        ";
        $this->logger->debug("Query: $sql");

        $nodes = $this->db->query(
            $sql,
            array(
                'resource'  => $resource,
                'date1'     => $date,
                'date2'     => $date,
            )
        );

        return $nodes;
    }

    /**
     * Insert node attribute data into the database.
     *
     * @param array $data An array with keys that match column names
     *     in the node attribute table.
     */
    public function insertNodeData(array $data)
    {
        $sql = 'INSERT INTO ' . $this->tableName . ' ('
            . implode(', ', array_keys($data))
            . ') VALUES ('
            . implode(', ', array_fill(0, count($data), '?'))
            . ')';

        $this->logger->debug('Insert statement: ' . $sql);

        return $this->db->insert($sql, array_values($data));
    }

    /**
     * Update the end date for a given node.
     *
     * @param int $id The node attribute primary key.
     * @param string $endDate The new end date in YYYY-MM-DD format.
     */
    public function updateNodeEndDate($id, $endDate)
    {
        $sql = "
            UPDATE {$this->tableName}
            SET end_date = :date
            WHERE {$this->pkName} = :id
        ";
        $this->logger->debug("Update statement: $sql");

        $this->db->execute(
            $sql,
            array(
                'id'   => $id,
                'date' => $endDate,
            )
        );
    }

    /**
     * Compare two arrays of node data, return true if the nodes are the
     * same.
     *
     * @param array $node1
     * @param array $node2
     *
     * @return bool
     */
    public function compareNodeData(array $node1, array $node2)
    {
        $ignoreColumns = array($this->pkName, 'start_date', 'end_date');

        foreach ($node1 as $key => $value) {
            if (in_array($key, $ignoreColumns)) {
                continue;
            }

            if ($node2[$key] != $value) {
                return false;
            }
        }

        return true;
    }
}

