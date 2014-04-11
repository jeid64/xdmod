<?php
/**
 * Slurm shredder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

namespace Xdmod\Shredder;

use Exception;
use DateTime;
use CCR\DB\Database;
use Xdmod\Shredder;

class Slurm extends Shredder
{

   /**
    * @inheritdoc
    */
   protected static $tableName = 'shredded_job_slurm';

   /**
    * @inheritdoc
    */
   protected static $tablePkName = 'shredded_job_slurm_id';

   /**
    * The field names needed from Slurm as named by sacct.
    *
    * @var array
    */
   protected static $fieldNames = array(
      'jobid',
      'cluster',
      'partition',
      'account',
      'group',
      'user',
      'submit',
      'eligible',
      'start',
      'end',
      'elapsed',
      'exitcode',
      'nnodes',
      'ncpus',
      'nodelist',
      'jobname',
   );

   /**
    * The field names needed from Slurm as named in the database.
    *
    * @var array
    */
   protected static $columnNames = array(
      'job_id',
      'cluster_name',
      'partition_name',
      'account_name',
      'group_name',
      'user_name',
      'submit_time',
      'eligible_time',
      'start_time',
      'end_time',
      'elapsed',
      'exit_code',
      'nnodes',
      'ncpus',
      'node_list',
      'job_name',
   );

   /**
    * The number of columns in input lines.
    *
    * @var int
    */
   protected static $columnCount;

   /**
    * @inheritdoc
    */
   protected static $columnMap = array(
      'date_key'        => 'DATE(FROM_UNIXTIME(end_time))',
      'job_id'          => 'job_id',
      'job_name'        => 'job_name',
      'cluster_name'    => 'cluster_name',
      'queue_name'      => 'partition_name',
      'user_name'       => 'user_name',
      'group_name'      => 'group_name',
      'account_name'    => 'account_name',
      'start_time'      => 'start_time',
      'end_time'        => 'end_time',
      'submission_time' => 'submit_time',
      'wallt'           => 'end_time - start_time',
      'wait'            => 'start_time - submit_time',
      'exect'           => 'end_time - start_time',
      'nodes'           => 'nnodes',
      'cpus'            => 'ncpus',
   );

   /**
    * @inheritdoc
    */
   protected static $dataMap = array(
      'job_id'          => 'job_id',
      'start_time'      => 'start_time',
      'end_time'        => 'end_time',
      'submission_time' => 'submit_time',
      'walltime'        => 'elapsed',
      'nodes'           => 'nnodes',
      'cpus'            => 'ncpus',
   );

   /**
    * The Slurm job states corresponding to jobs that are no longer
    * running.
    *
    * @var array
    */
   protected static $states = array(
      'CANCELLED',
      'COMPLETED',
      'FAILED',
      'NODE_FAIL',
      'PREEMPTED',
      'TIMEOUT',
   );

   /**
    * @inheritdoc
    */
   public function __construct(Database $db)
   {
      parent::__construct($db);

      self::$columnCount = count(self::$columnNames);
   }

   /**
    * @inheritdoc
    */
   public function shredLine($line)
   {
      $this->logger->debug("Shredding line '$line'");

      $fields = explode('|', $line, self::$columnCount);

      if (count($fields) != self::$columnCount) {
         throw new Exception("Malformed Slurm sacct line: '$line'");
      }

      $job = array();

      // Map numeric $fields array into a associative array.
      foreach (self::$columnNames as $index => $name) {
         $job[$name] = $fields[$index];
      }

      // Skip job steps.
      if (strpos($job['job_id'], '.') !== false) {
         return;
      }

      # Skip jobs that haven't ended.
      if ($job['end_time'] == 'Unknown') {
         return;
      }

      $node = $this->getFirstNode($job['node_list']);
      $date = substr($job['end_time'], 0, 10);

      // Convert datetime strings into unix timestamps.
      $dateKeys = array(
         'submit_time',
         'eligible_time',
         'start_time',
         'end_time',
      );
      foreach ($dateKeys as $key) {
         $job[$key] = $this->parseDatetime($job[$key]);
      }

      $job['cluster_name'] = $this->getResourceForNode(
         $node,
         $date,
         $job['cluster_name']
      );

      $job['elapsed'] = $this->parseTimeField($job['elapsed']);

      $this->checkJobData($line, $job);

      $this->insertRow($job);
   }

   /**
    * Returns the field names needed from Slurm as named by sacct.
    *
    * @return array
    */
   public function getFieldNames()
   {
      return self::$fieldNames;
   }

   /**
    * Returns the states for completed jobs as named by sacct.
    *
    * @return array
    */
   public function getStates()
   {
      return self::$states;
   }

   /**
    * Return the first node from a nodeset.
    *
    * Parses string like this:
    *   node[0-4]
    *   node[1,3,8]
    *   node5,other6
    *
    * @param string $nodeList
    *
    * @return string The name of the first node in the list.
    */
   private function getFirstNode($nodeList)
   {
      $bracketPos = strpos($nodeList, '[');
      $commaPos   = strpos($nodeList, ',');

      // If the nodeset doesn't contain a bracket "[" or if a comma is
      // preset in the nodeset before the bracket, return everything
      // before the first comma. (e.g. "node2,node1" returns "node1",
      // "node6,node[10-20]" returns "node6".)
      //
      // If the nodeset contains a bracket before any commas then take
      // everything before the bracket and apend the first number inside
      // the brackets. (e.g. "node[10-20],node30" return "node10",
      // "node[3,5]" return "node3".)

      if (
            $bracketPos === false
         || ($commaPos !== false && $bracketPos < $commaPos)
      ) {
         $nodes = explode(',', $nodeList);
         return $nodes[0];
      } else {
         $parts = explode('[', $nodeList);
         list($range) = explode(']', $parts[1]);
         list($number) = preg_split('/[^0-9]/', $range);
         return $parts[0] . $number;
      }
   }

   /**
    * Parse a datetime from Slurm.
    *
    * @param string $datetimeStr Datetime formatted as YYYY-MM-DDTHH-MM-SS.
    *
    * @return int Unix timestamp representation of the datetime.
    */
   private function parseDatetime($datetimeStr)
   {
      $datetimeObj = DateTime::createFromFormat('Y-m-d?H:i:s', $datetimeStr);

      if ($datetimeObj === false) {
         throw new Exception("Failed to parse datetime '$datetimeStr'");
      }

      return (int)$datetimeObj->format('U');
   }

   /**
    * Parse a time field from Slurm.
    *
    * Time fields are presented as [[days-]hours:]minutes:seconds.hundredths
    *
    * @param string $time
    *
    * @return int Time formatted in seconds.
    */
   private function parseTimeField($time)
   {
       $pattern = '
          /
          ^
          (?:
             (?:
                (?<days> \d+ )
                -
             )?
             (?<hours> \d+ )
             :
          )?
          (?<minutes> \d+ )
          :
          (?<seconds> \d+ )
          (?: \. \d+ )?
          $
          /x
       ';

       if (!preg_match($pattern, $time, $matches)) {
          throw new Exception("Failed to parse time field '$time'");
       }

       $days = $hours = 0;

       if (!empty($matches['days'])) {
          $days = $matches['days'];
       }

       if (!empty($matches['hours'])) {
          $hours = $matches['hours'];
       }

       $minutes = $matches['minutes'];
       $seconds = $matches['seconds'];

       return $days * 24 * 60 * 60
          + $hours * 60 * 60
          + $minutes * 60
          + $seconds;
   }
}

