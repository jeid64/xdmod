<?php
/**
 * PBS/TORQUE shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace Xdmod\Shredder;

use Exception;
use DateTime;
use DateInterval;
use Xdmod\Shredder;

class Pbs extends Shredder
{

    /**
     * @inheritdoc
     */
    protected static $tableName = 'shredded_job_pbs';

    /**
     * @inheritdoc
     */
    protected static $tablePkName = 'shredded_job_pbs_id';

    /**
     * Regular expression for PBS accounting log lines.
     *
     * @var string
     */
    protected static $linePattern = '|
        ^
        ( \d{2}/\d{2}/\d{4} )    # Date
        \s
        ( \d{2}:\d{2}:\d{2} )    # Time
        ;
        ( \w )                   # Event type
        ;
        ( [^;]+ )                # Job ID
        ;
        ( .* )                   # Parameters
    |x';

    /**
     * All the columns in the job table, excluding the primary key.
     *
     * @var array
     */
    protected static $columnNames = array(
        'job_id',
        'job_array_index',
        'host',
        'queue',
        'user',
        'groupname',
        'ctime',
        'qtime',
        'start',
        'end',
        'etime',
        'exit_status',
        'session',
        'requestor',
        'jobname',
        'owner',
        'account',
        'session_id',
        'error_path',
        'output_path',
        'exec_host',
        'resources_used_vmem',
        'resources_used_mem',
        'resources_used_walltime',
        'resources_used_nodes',
        'resources_used_cpus',
        'resources_used_cput',
        'resource_list_nodes',
        'resource_list_procs',
        'resource_list_neednodes',
        'resource_list_pcput',
        'resource_list_cput',
        'resource_list_walltime',
        'resource_list_ncpus',
        'resource_list_nodect',
        'resource_list_mem',
        'resource_list_pmem',
    );

    /**
     * Columns that should be parsed and their expected format.
     *
     * @var array
     */
    protected static $columnFormats = array(
        'resources_used_vmem'     => 'memory',
        'resources_used_mem'      => 'memory',
        'resources_used_walltime' => 'time',
        'resources_used_cput'     => 'time',
        'resource_list_pcput'     => 'time',
        'resource_list_cput'      => 'time',
        'resource_list_walltime'  => 'time',
        'resource_list_mem'       => 'memory',
        'resource_list_pmem'      => 'memory',
    );

    /**
     * @inheritdoc
     */
    protected static $columnMap = array(
        'date_key'        => 'DATE(FROM_UNIXTIME(end))',
        'job_id'          => 'job_id',
        'job_array_index' => 'NULLIF(job_array_index, -1)',
        'job_name'        => 'jobname',
        'cluster_name'    => 'LOWER(host)',
        'queue_name'      => 'LOWER(queue)',
        'user_name'       => 'LOWER(user)',
        'group_name'      => 'LOWER(groupname)',
        'account_name'    => 'account',
        'start_time'      => 'start',
        'end_time'        => 'end',
        'submission_time' => 'ctime',
        'wallt'           => 'resources_used_walltime',
        'cput'            => 'resources_used_cput',
        'mem'             => 'resources_used_mem',
        'vmem'            => 'resources_used_vmem',
        'wait'            => 'GREATEST(start - ctime, 0)',
        'exect'           => 'GREATEST(end - start, 0)',
        'nodes'           => 'resources_used_nodes',
        'cpus'            => 'resources_used_cpus',
    );

    /**
     * @inheritdoc
     */
    protected static $dataMap = array(
        'job_id'          => 'job_id',
        'start_time'      => 'start',
        'end_time'        => 'end',
        'submission_time' => 'ctime',
        'walltime'        => 'resources_used_walltime',
        'nodes'           => 'resources_used_nodes',
        'cpus'            => 'resources_used_cpus',
    );

    /**
     * @inheritdoc
     */
    public function shredLine($line)
    {
        $this->logger->debug("Shredding line '$line'");

        $date = $node = $type = null;

        $job = array();

        if (preg_match(self::$linePattern, $line, $matches)) {
            $date = preg_replace(
                '#^(\d{2})/(\d{2})/(\d{4})$#',
                '$3-$1-$2',
                $matches[1]
            );

            $type   = $matches[3];
            $jobId  = $matches[4];
            $params = $matches[5];
        } else {
            throw new Exception("Malformed PBS accounting line: '$line'");
        }

        // Ignore all non-"end" events.
        if ($type != 'E') {
           return;
        }

        $jobIdData = $this->getJobIdAndHost($jobId);
        $job['job_id'] = $jobIdData['job_id'];
        if ($jobIdData['job_array_index'] !== null) {
            $job['job_array_index'] = $jobIdData['job_array_index'];
        }

        $paramList = preg_split('/\s+/', $params);

        foreach ($paramList as $param) {
            if (strpos($param, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $param, 2);

            $key = strtolower(str_replace('.', '_', $key));

            if ($key == 'exec_host') {
                $data = $this->parseExecHost($value);
                $node = $data['host_list'][0]['node'];
                $job['resources_used_nodes'] = $data['node_count'];
                $job['resources_used_cpus']  = $data['cpu_count'];
            } elseif (isset(self::$columnFormats[$key])) {
                $format = self::$columnFormats[$key];
                $parseMethod = 'parse' . ucfirst($format);
                $job[$key] = $this->$parseMethod($value);
            } elseif ($key === 'group') {
                $job['groupname'] = $value;
            } else {
                $job[$key] = $value;
            }
        }

        $job['host'] = $this->getResourceForNode(
            $node,
            $date,
            $jobIdData['host']
        );

        foreach (array_keys($job) as $key) {
            if (!in_array($key, self::$columnNames)) {
                $this->logger->debug("Ignoring unknown attribute '$key'");
                unset($job[$key]);
            }
        }

        $this->checkJobData($line, $job);

        $this->insertRow($job);
    }

    /**
     * Determine the job_id and hostname for a job.
     *
     * @param string $id The PBS id_string.
     *
     * @return array
     */
    public function getJobIdAndHost($id)
    {
        $this->logger->debug("Parsing id_string '$id'");

        // id_string is formatted as "sequence_number.hostname".
        list($sequence, $host) = explode('.', $id, 2);

        $jobId = $index = null;

        // If the job is part of a job array the sequence number may be
        // formatted as "job_id[array_index]" or "job_id-array_index".
        // If the sequence number represents the entire job array it may
        // be formatted as "job_id[]".
        if (preg_match('/ ^ (\d+) \[ (\d+)? \] $ /x', $sequence, $matches)) {
            $jobId = $matches[1];
            if (isset($matches[2])) {
                $index = $matches[2];
            }
        } elseif (preg_match('/^(\d+)-(\d+)$/', $sequence, $matches)) {
            $jobId = $matches[1];
            $index = $matches[2];
        } elseif (preg_match('/^\d+$/', $sequence, $matches)) {
            $jobId = $sequence;
        } else {
            $this->logger->warning("Unknown id_string format: '$id_string'");
            $jobId = $sequence;
        }

        return array(
            'host'            => $host,
            'job_id'          => $jobId,
            'job_array_index' => $index,
        );
    }

    /**
     * Determine the number of nodes and cpus used by a job.
     *
     * @param string $hosts A list of hostname formatted as
     *   HOST1/CPU1+HOST2/CPU2+...
     *
     * @return array
     */
    protected function parseExecHost($hosts)
    {
        $hostList = $this->parseHosts($hosts);

        // Key is the node name, value is the number of cpus.
        $nodeCpus = array();

        foreach ($hostList as $host) {
            $node = $host['node'];

            if (isset($nodeCpus[$node])) {
                $nodeCpus[$node]++;
            } else {
                $nodeCpus[$node] = 1;
            }
        }

        $nodeCount = 0;
        $cpuCount = 0;

        foreach ($nodeCpus as $node => $cpus) {
            $nodeCount++;
            $cpuCount += $cpus;
        }

        return array(
            'host_list'  => $hostList,
            'node_count' => $nodeCount,
            'cpu_count'  => $cpuCount,
        );
    }

    /**
     * Parse a time string.
     *
     * @param string $time The time in HH:MM:SS format.
     *
     * @return int The number of seconds past midnight.
     */
    protected function parseTime($time)
    {
        $this->logger->debug("Parsing time '$time'");

        list($h, $m, $s) = explode(':', $time);
        return $h * 60 * 60 + $m * 60 + $s;
    }

    /**
     * Parse a memory quantity string.
     *
     * @param string $memory The quantity of memory.
     *
     * @return int The quantity of memory in bytes.
     */
    protected function parseMemory($memory)
    {
        $this->logger->debug("Parsing memory '$memory'");

        if (preg_match('/^(\d*\.?\d+)(\D+)?$/', $memory, $matches)) {
            $quantity = $matches[1];

            // PBS uses kilobytes by default.
            $unit = isset($matches[2]) ? $matches[2] : 'kb';

            return $this->scaleMemory($quantity, $unit);
        } else {
            throw new Exception("Unknown memory format: '$memory'");
        }
    }

    /**
     * Scale the memory from the given unit to bytes.
     *
     * @param float $quantity The memory quantity.
     * @param string $unit The memory unit (b, kb, mb, gb).
     *
     * @return int
     */
    protected function scaleMemory($quantity, $unit)
    {
        $this->logger->debug("Scaling memory '$quantity', '$unit'");

        switch ($unit) {
            case 'b':
                return (int)floor($quantity);
                break;
            case 'kb':
                return (int)floor($quantity * 1024);
                break;
            case 'mb':
                return (int)floor($quantity * 1024 * 1024);
                break;
            case 'gb':
                return (int)floor($quantity * 1024 * 1024 * 1024);
                break;
            default:
                throw new Exception("Unknown memory unit: '$unit'");
                break;
        }
    }

    /**
     * Parse a hosts string.
     *
     * @param string $hosts A list of hostname formatted as
     *   HOST1/CPU1+HOST2/CPU2+...
     *
     * @return array An array of node name and cpu id pairs.
     */
    protected function parseHosts($hosts)
    {
        $this->logger->debug("Parsing hosts '$hosts'");

        $parts = explode('+', $hosts);

        $hostList = array();

        foreach ($parts as $part) {
            list($host, $cpu) = explode('/', $part);

            $hostList[] = array(
                'node' => $host,
                'cpu'  => $cpu,
            );
        }

        return $hostList;
    }

    /**
     * PBS accounting files are named in the YYYYMMDD format.  The file
     * for the current date is not included because it may be in use.
     *
     * @inheritdoc
     */
    protected function getDirectoryFilePaths($dir)
    {
        $maxDate = $this->getJobMaxDate();

        if ($maxDate == null) {
            $this->logger->debug('No maximum date found in job table.');
            return parent::getDirectoryFilePaths($dir);
        }

        $this->logger->debug('Max date: ' . $maxDate);

        $now  = new DateTime('now');
        $date = new DateTime($maxDate);

        $oneDay = new DateInterval('P1D');

        $date->add($oneDay);

        $paths = array();

        while ($date->diff($now)->days > 0) {
            $path = $dir . '/' . $date->format('Ymd');

            $date->add($oneDay);

            if (!is_file($path)) {
                $this->logger->debug("Skipping missing file '$dir'");
                continue;
            }

            $paths[] = $path;
        }

        return $paths;
    }
}

