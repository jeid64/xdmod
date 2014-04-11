<?php
/**
 * Shredder base class.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace Xdmod;

use Exception;
use CCR\DB\Database;
use PDODBMultiIngestor;
use Xdmod\NodeMap;

class Shredder
{

    /**
     * The format name of the shredder.
     *
     * @var string
     */
    protected $format;

    /**
     * Name of the database table used to store shredded data.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @var string
     */
    protected static $tableName = '';

    /**
     * Name of the primary key column of the database table used to
     * store shredded data.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @var string
     */
    protected static $tablePkName = '';

    /**
     * The maximum primary key value at the time the shredder is
     * created.
     *
     * This value is used to determine what data needs to be ingested.
     *
     * @var int
     */
    protected $maxPk = 0;

    /**
     * Mapping from generic job table to resource manager specific job
     * table.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @var array
     */
    protected static $columnMap = array();

    /**
     * Logger object.
     *
     * @var \Log;
     */
    protected $logger;

    /**
     * Database connection.
     *
     * @var Database
     */
    protected $db;

    /**
     * Resource name for the file being shredded.
     *
     * @var string
     */
    protected $resource;

    /**
     * Node map for the file being shredded.
     *
     * @var string
     */
    protected $nodeMap;

    /**
     * The date that corresponds to the data currently being shredded.
     *
     * This is needed to determine the correct date to use with the node
     * map.
     *
     * @var string A date in YYYY-MM-DD format.
     */
    protected $dataDate;

    /**
     * Mapping from generic job names to resource manager specific job
     * keys or functions.  Used to error check parsed data.
     *
     * Keys should be "job_id", "start_time", "end_time",
     * "submission_time", "walltime", "nodes" and "cpus".  The "job_id"
     * key is required.  The reset are optional, but if they are
     * missing, the checkJobData function will not check be able to
     * perform all the possible checks.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @see checkJobData
     *
     * @var array
     */
    protected static $dataMap = array();

    /**
     * Data from jobs that contain errors.
     *
     * @var array
     */
    protected $jobErrors = array();

    /**
     * Protected constructor to enforce factory pattern.
     *
     * @param Database $db The database connection.
     */
    protected function __construct(Database $db)
    {
        $this->db     = $db;
        $this->logger = \Log::singleton('null');

        $classPath = explode('\\', strtolower(get_class($this)));
        $this->format = $classPath[count($classPath) - 1];

        $tableName   = static::$tableName;
        $tablePkName = static::$tablePkName;
        $sql = "SELECT MAX($tablePkName) AS max_pk FROM $tableName";
        list($row) = $this->db->query($sql);

        if ($row['max_pk'] != null) {
            $this->maxPk = $row['max_pk'];
        }

        $this->logger->debug("MAX($tableName.$tablePkName) = {$this->maxPk}");
    }

    /**
     * Factory method.
     *
     * @param string $format File format name.
     * @param Database $db The database connection.
     *
     * @return Shredder
     */
    public static function factory($format, Database $db)
    {
        $class = "Xdmod\\Shredder\\" . ucfirst(strtolower($format));

        if (!class_exists($class)) {
            throw new Exception("Class not found '$class'");
        }

        return new $class($db);
    }

    /**
     * Set the logger.
     *
     * @param Logger $logger The logger instance.
     */
    public function setLogger(\Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the resource name for the files being shredded.
     *
     * @param string $resource The name of the resource.
     */
    public function setResource($resource)
    {
        $this->logger->debug("Setting resource to '$resource'");
        $this->resource = $resource;
    }

    /**
     * Return the name of the resource.
     *
     * @return string The name of the resource.
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Check if a resource name has been set.
     *
     * @return bool True if the resource name has been set.
     */
    public function hasResource()
    {
        return isset($this->resource);
    }

    /**
     * Set the node map.
     *
     * @param NodeMap $nodeMap
     */
    public function setNodeMap(NodeMap $nodeMap)
    {
        $this->nodeMap = $nodeMap;
    }

    /**
     * Get the node map.
     *
     * @return nodeMap
     */
    public function getNodeMap()
    {
        return $this->nodeMap;
    }

    /**
     * Check if the node map has been set.
     *
     * @return bool True if the node map has been set.
     */
    public function hasNodeMap()
    {
        return isset($this->nodeMap);
    }

    /**
     * Return the resource node name for a node name.
     *
     * @param string $node
     * @param string $date
     * @param string $fallback
     *
     * @return string
     */
    public function getResourceForNode($node, $date, $fallback = null)
    {
        $this->logger->debug("Getting resource name for node '$node'");

        if ($node !== null && $this->hasNodeMap()) {
            $nodeMap = $this->getNodeMap();

            if ($date != $this->dataDate) {
                $this->nodeMap->setDate($date);
                $this->dataDate = $date;
            }

            if ($nodeMap->hasResourceForNode($node)) {
                return $nodeMap->getResourceForNode($node);
            }
        }

        if ($this->hasResource()) {
            return $this->getResource();
        }

        if ($fallback === null) {
            throw new Exception("Failed to find resource for node '$node'");
        }

        return $fallback;
    }

    /**
     * Shred files in a directory.
     *
     * @param string $dir The directory path.
     *
     * @return int The number of records shredded.
     */
    public function shredDirectory($dir)
    {
        $this->logger->notice("Shredding directory '$dir'");

        if (!is_dir($dir)) {
            $this->logger->err("'$dir' is not a directory");
            return false;
        }

        $paths = $this->getDirectoryFilePaths($dir);

        $recordCount = 0;
        $fileCount   = 0;

        foreach ($paths as $path) {
            $recordCount += $this->shredFile($path);
            $fileCount++;
        }

        $this->logger->notice("Shredded $fileCount files");
        $this->logger->notice("Shredded $recordCount records total");

        return $recordCount;
    }

    /**
     * Returns an array of paths to all the files in a directory that
     * should be shredded.
     *
     * @param string $dir The directory path.
     *
     * @return array
     */
    protected function getDirectoryFilePaths($dir)
    {
        $files = scandir($dir);

        $paths = array();

        foreach ($files as $file) {
            if (strpos($file, '.') === 0) {
                $this->logger->debug("Skipping hidden file '$file'");
                continue;
            }

            $paths[] = $dir . '/' . $file;
        }

        return $paths;
    }

    /**
     * Shred a file.
     *
     * @param string $file The file path.
     *
     * @return int The number of records shredded.
     */
    public function shredFile($file)
    {
        $this->logger->notice("Shredding file '$file'");

        if (!is_file($file)) {
            $this->logger->err("'$file' is not a file");
            return false;
        }

        $lines = file($file);

        $recordCount = 0;

        $this->db->execute('START TRANSACTION');

        foreach ($lines as $lineNumber => $line) {

            // Remove trailing whitespace.
            $line = rtrim($line);

            try {
                $this->shredLine($line);
                $recordCount++;
            } catch (Exception $e) {
                $msg = sprintf(
                    'Failed to shred line %d of file %s: %s',
                    $lineNumber,
                    $file,
                    $e->getMessage()
                );
                $this->logger->err(array(
                    'message'    => $msg,
                    'stacktrace' => $e->getTraceAsString(),
                ));
            }
        }

        $this->db->execute('COMMIT');

        $this->logger->notice("Shredded $recordCount records");

        return $recordCount;
    }

    /**
     * Shred a line from a log file and insert the data into the
     * database.
     *
     * NOTE: This should be overriden by the subclass.
     *
     * @param string $line A single line from a log file.
     */
    public function shredLine($line)
    {
        throw new Exception('Shredder subclass must implement shredLine');
    }

    /**
     * Insert a single row into the database.
     *
     * @param array $values The values to insert where the array keys
     *   correspond to the column names.
     */
    protected function insertRow($values)
    {
        $sql = $this->createInsertStatement(static::$tableName, array_keys($values));

        $this->logger->debug("Insert statement: '$sql'");

        $this->db->insert($sql, array_values($values));
    }

    /**
     * Create a SQL statement with
     *
     * @param string $table The name of the table to insert into.
     * @param array $columns The table column names.
     *
     * @return string A SQL insert statement.
     */
    protected function createInsertStatement($table, array $columns)
    {
        $sql = "INSERT INTO $table ("
            . implode(', ', $columns)
            . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?'))
            . ')';

        return $sql;
    }

    /**
     * Truncate the shredder data table.
     */
    public function truncate()
    {
        $tableName = static::$tableName;
        $this->logger->info("Truncating table '$tableName'");
        $this->db->execute("TRUNCATE $tableName");
        $this->maxPk = 0;
    }

    /**
     * Creates a ingestor to populate the generic job table.
     *
     * @return Ingestor Description.
     */
    public function getJobIngestor($ingestAll = false)
    {
        $this->logger->debug('Creating ingestor');

        $sourceQuery     = $this->getIngestorQuery($ingestAll);
        $insertFields    = array_keys(static::$columnMap);
        $deleteStatement = $this->getIngestorDeleteStatement($ingestAll);

        $insertFields[] = 'source_format';

        $this->logger->debug("Ingestor source query: $sourceQuery");

        $this->logger->debug(
            'Ingestor insert fields: ' . implode(', ', $insertFields)
        );

        $this->logger->debug("Ingestor delete statement: $deleteStatement");

        $ingestor = new PDODBMultiIngestor(
            $this->db,
            $this->db,
            array(),
            $sourceQuery,
            'shredded_job',
            $insertFields,
            array(),
            $deleteStatement
        );

        $ingestor->setLogger($this->logger);

        return $ingestor;
    }

    /**
     * Creates a SQL query for use by the job ingestor.
     *
     * @param bool $ingestAll True if the query should select all data
     *   in the table and not just new data.
     *
     * @return string
     */
    protected function getIngestorQuery($ingestAll)
    {
        $columns = array();

        foreach (static::$columnMap as $key => $value) {
            if ($key === $value) {
                $columns[] = $value;
            } else {
                $columns[] = "$value AS $key";
            }
        }

        $columns[] = "'{$this->format}' AS source_format";

        $sql = 'SELECT ' . implode(', ', $columns)
            . ' FROM ' . static::$tableName;

        if ($ingestAll) {
            $sql .= ' WHERE 1 = 1';
        } else {
            $sql .= ' WHERE ' . static::$tablePkName . ' > ' . $this->maxPk;
        }

        return $sql;
    }

    /**
     * Creates a SQL delete statement for use by the job ingestor.
     *
     * @return string
     */
    protected function getIngestorDeleteStatement($ingestAll)
    {
        if ($ingestAll) {
            return 'TRUNCATE shredded_job';
        } else {
            return 'nodelete';
        }
    }

    /**
     * Find and return the maximum end date of all shredded job data.
     *
     * @return string A date formatted as YYYY-MM-DD.
     */
    public function getJobMaxDate()
    {
        $sql = "
            SELECT DATE_FORMAT(MAX(date_key), '%Y-%m-%d') AS max_date
            FROM shredded_job
        ";

        $params = array();

        if ($this->hasResource()) {
            $sql .= ' WHERE cluster_name = :resource';
            $params['resource'] = $this->getResource();
        }

        $this->logger->debug('Query: ' . $sql);

        list($row) = $this->db->query($sql, $params);

        return $row['max_date'];
    }

    /**
     * Check job data for consistency.
     *
     * @param string $input The input used to generate the job data.
     * @param array $data Job data.
     */
    protected function checkJobData($input, array &$data)
    {
        $keys = array(
            'job_id',
            'start_time',
            'end_time',
            'submission_time',
            'walltime',
            'nodes',
            'cpus',
        );

        // Create array with the generic keys used in $dataMap.
        $dataMap = static::$dataMap;
        $mappedData = array();
        foreach ($keys as $key) {
            if (isset($dataMap[$key])) {
                $mappedData[$key] = $data[$dataMap[$key]];
            }
        }

        $errorMessages = array();

        if (isset($dataMap['start_time']) && isset($dataMap['end_time'])) {
            list($valid, $messages) = $this->checkJobTimes(
                $mappedData['start_time'],
                $mappedData['end_time']
            );
            if (!$valid) {
                $errorMessages = array_merge($errorMessages, $messages);

                if (isset($dataMap['walltime'])) {
                    list(
                        $data[$dataMap['start_time']],
                        $data[$dataMap['end_time']]
                    ) = $this->fixJobTimes(
                        $mappedData['start_time'],
                        $mappedData['end_time'],
                        $mappedData['walltime']
                    );
                }
            }
        }

        if (isset($dataMap['nodes']) && isset($dataMap['cpus'])) {
            list($valid, $messages) = $this->checkNodesAndCpus(
                $mappedData['nodes'],
                $mappedData['cpus']
            );
            if (!$valid) {
                $errorMessages = array_merge($errorMessages, $messages);
            }
        }

        if (count($errorMessages) > 0) {
            $this->logJobError(array(
                'job_id'   => $mappedData['job_id'],
                'input'    => $input,
                'messages' => $errorMessages,
            ));
        }
    }

    /**
     * Check job times for validity.
     *
     * @param int $startTime Job start time in epoch format.
     * @param int $endTime Job start time in epoch format.
     *
     * @return array First value in the array is a bool indicating the
     *   data is valid or not.  The second value is an array or error
     *   messages if the data is not valid.
     */
    protected function checkJobTimes($startTime, $endTime)
    {
        $valid = true;
        $errorMessages = array();

        if ($startTime == 0) {
            $this->logger->debug('Found 0 start time.');
            $valid = false;
            $errorMessages[] = 'Job start time is 0.';
        }

        if ($endTime == 0) {
            $this->logger->debug('Found 0 end time.');
            $valid = false;
            $errorMessages[] = 'Job end time is 0.';
        }

        if ($startTime > $endTime) {
            $this->logger->debug('Found start time after end time.');
            $errorMessages[] = 'Job start time as after job end time.';
            $valid = false;
        }

        return array($valid, $errorMessages);
    }

    /**
     * Attempt to correct invalid job times.
     *
     * @param int $startTime Job start time in epoch format.
     * @param int $endTime Job start time in epoch format.
     * @param int $wall Job wall time in seconds.
     *
     * @return array The corrected start and end times.
     */
    protected function fixJobTimes($startTime, $endTime, $walltime)
    {
        $this->logger->debug('Attempting to fix job times.', array(
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'walltime'   => $walltime,
        ));

        if ($startTime == 0 && $endTime == 0) {
            $msg = 'Failed to correct invalid start and end times';
            throw new Exception($msg);
        }

        if ($startTime == 0) {
            $startTime = $endTime - $walltime;
            $this->logger->debug("Setting start time to $startTime");
        }

        if ($endTime == 0) {
            $endtime = $startTime + $walltime;
            $this->logger->debug("Setting end time to $endTime");
        }

        if ($startTime > $endTime) {

            // Assume the end time is correct.
            $startTime = $endTime - $walltime;
            $this->logger->debug("Setting start time to $startTime");
        }

        return array($startTime, $endTime);
    }

    /**
     * Check job node and cpu counts for validity.
     *
     * @param int $nodes Node count.
     * @param int $cpus Cpu count.
     *
     * @return array First value in the array is a bool indicating the
     *   data is valid or not.  The second value is an array or error
     *   messages if the data is not valid.
     */
    protected function checkNodesAndCpus($nodes, $cpus)
    {
        $valid = true;
        $errorMessages = array();

        if ($nodes == 0) {
            $this->logger->debug('Found 0 node count.');
            $valid = false;
            $errorMessages[] = 'Job node count is 0.';
        }

        if ($cpus == 0) {
            $this->logger->debug('Found 0 cpu count.');
            $valid = false;
            $errorMessages[] = 'Job cpu count is 0.';
        }

        if ($nodes > $cpus) {
            $this->logger->debug('Found node count greater than cpu count.');
            $errorMessages[]
                = "Job node count greater than cpu count ($nodes > $cpus).";
            $valid = false;
        }

        return array($valid, $errorMessages);
    }

    /**
     * Attempt to correct invalid node and cpu counts..
     *
     * @param int $nodes Node count.
     * @param int $cpus Cpu count.
     *
     * @return array The corrected node and cpu counts.
     */
    protected function fixNodesAndCpus($nodes, $cpus)
    {
        $this->logger->debug('Attempting to node and cpu counts.', array(
            'nodes' => $nodes,
            'cpus'  => $cpus,
        ));

        if ($nodes == 0 && $cpus == 0) {
            $nodes = 1;
            $cpus = 1;
            $this->logger->debug("Setting node count to $nodes");
            $this->logger->debug("Setting cpu count to $cpus");
        }

        if ($cpus < $nodes) {
            $cpus = $nodes;
            $this->logger->debug("Setting cpu count to $cpus");
        }

        return array($nodes, $cpus);
    }

    /**
     * Log a job data error.
     *
     * @param array $jobInfo
     */
    protected function logJobError(array $jobInfo)
    {
        $this->jobErrors[] = $jobInfo;
    }

    /**
     * Check if the shredder has logged any job data errors
     *
     * @return bool True if job data errors have been logged.
     */
    public function hasJobErrors()
    {
        return count($this->jobErrors) > 0;
    }

    /**
     * Write job data errors to a file.
     *
     * @param string $file The path of a file to write to.
     */
    public function writeJobErrors($file)
    {
        $this->logger->debug("Opening file '$file'");
        $fh = fopen($file, 'w+');

        if ($fh === false) {
            throw new Exception("Failed to open file '$file'");
        }

        $this->logger->debug('Writing to file');
        fwrite($fh, str_repeat('=', 72) . "\n");
        fwrite($fh, 'Shredder end time: ' . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, "Resource: {$this->resource}\n");
        fwrite($fh, "Format: {$this->format}\n");

        // TODO: Refactor to display input format for all resource
        //       managers.
        if ($this->format == 'slurm') {
            fwrite(
                $fh,
                'Input format: ' . implode('|', $this->getFieldNames()) . "\n"
            );
        }

        fwrite($fh, "\n");

        foreach ($this->jobErrors as $err) {
            fwrite($fh, str_repeat('-', 72) . "\n");

            fwrite($fh, "Input:\n{$err['input']}\n\n");

            foreach ($err['messages'] as $message) {
                fwrite($fh, "$message\n");
            }

            fwrite($fh, "\n");
        }
    }
}

