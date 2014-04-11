<?php
/**
 * Maps nodes to sub-resources.
 *
 * If a resource has a "sub_resources" property in resources.json, the
 * node attributes are used to define sub-resources.  Each node with the
 * specified attributes are then mapped to that sub-resource.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace Xdmod;

use Exception;
use CCR\DB\Database;
use Xdmod\NodeAttributeHelper;

class NodeMap
{

    /**
     * @var \Log
     */
    protected $logger;

    /**
     * @var Database
     */
    protected $db;

    /**
     * @var NodeAttributeHelper
     */
    protected $helper;

    /**
     * The name of the resource.
     *
     * @var string
     */
    protected $resource;

    /**
     * Array with sub-resource name and the corresponding node
     * attributes.
     *
     * @var array
     */
    protected $subResources;

    /**
     * Map from node names to resource names.
     *
     * @var array
     */
    protected $resourceForNode;

    /**
     * Factory pattern.
     *
     * @param Database $db
     * @param string $resource Name of the resource.
     * @param array $subResources
     * @param string $date
     *
     * @return NodeMap
     */
    public static function factory(
        Database $db,
        $resource,
        array $subResources,
        $date = null
    ) {
        $map = new static($db, $resource, $subResources);

        if ($date !== null) {
            $map->setDate($date);
        }

        return $map;
    }

    /**
     * Constructor.
     *
     * @param Database $db
     * @param string $resource
     * @param array $subResources
     */
    protected function __construct(
        Database $db,
        $resource,
        array $subResources
    ) {
        $this->db           = $db;
        $this->resource     = $resource;
        $this->subResources = $subResources;
        $this->logger       = \Log::singleton('null');
        $this->helper       = NodeAttributeHelper::factory($db);
    }

    /**
     * @param \Log $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        $this->helper->setLogger($logger);
    }

    /**
     * Set the date to use when checking node attributes.
     *
     * @param string $date A date in YYYY-MM-DD format.
     */
    public function setDate($date)
    {
        $this->logger->debug("Loading data for '$date'");

        $nodes = $this->helper->getNodesForDate($this->resource, $date);

        $this->resourceForNode = array();

        foreach ($nodes as $node) {
            $resource = $this->getResourceForNodeInfo($node);
            $this->setResourceForNode($node['name'], $resource);
        }
    }

    /**
     * Given an array of node attributes, determine the sub-resource
     * for that node.
     *
     * @param array $info
     *
     * @return string
     */
    protected function getResourceForNodeInfo(array $info)
    {
        foreach ($this->subResources as $subResource) {
            foreach ($subResource['node_attributes'] as $attrs) {

                // Check for any attributes that are different.
                foreach ($attrs as $key => $value) {
                    if ($info[$key] != $value) {

                        // Continue to next group of attributes.
                        continue 2;
                    }
                }

                // All attributes match.
                return $subResource['resource'];
            }
        }

        return $this->resource;
    }

    /**
     * Set the resource name for a given node name.
     *
     * @param string $node
     * @param string $resource
     */
    protected function setResourceForNode($node, $resource)
    {
        $this->logger->debug("Setting resource for '$node' to '$resource'");

        if (isset($this->resourceForNode[$node])) {
            throw new Exception("Resource for node '$node' is already set");
        }

        $this->resourceForNode[$node] = $resource;
    }

    /**
     * Check if a node has been mapped to a resource.
     *
     * @param string $node
     *
     * @return bool
     */
    public function hasResourceForNode($node)
    {
        return isset($this->resourceForNode[$node]);
    }

    /**
     * Returns the resource name for a given node name.
     *
     * @param string $node
     *
     * @return string
     */
    public function getResourceForNode($node)
    {
        if (!isset($this->resourceForNode[$node])) {
            throw new Exception("Unknown node '$node'");
        }

        return $this->resourceForNode[$node];
    }
}

