<?php
require_once 'Log.php';
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */

class PDODBMultiIngestor implements Ingestor
{
    protected $_destination_db = null;
    protected $_source_db = null;
    protected $_source_query = null;
    protected $_insert_table = null;
    protected $_insert_fields = null;
    protected $_pre_ingest_update_statements;
    protected $_post_ingest_update_statements;
    protected $_delete_statement = null;
    protected $_count_statement = null;
    protected $_logger = null;

    function __construct($dest_db, $source_db, $pre_ingest_update_statements = array() , $source_query, $insert_table, $insert_fields = array() , $post_ingest_update_statements = array() , $delete_statement = null, $count_statement = null)
    {
        $this->_destination_db = $dest_db;
        $this->_source_db = $source_db;
        $this->_source_query = $source_query;
        $this->_insert_fields = $insert_fields;
        $this->_insert_table = $insert_table;
        $this->_pre_ingest_update_statements = $pre_ingest_update_statements;
        $this->_post_ingest_update_statements = $post_ingest_update_statements;
        $this->_delete_statement = $delete_statement;
        $this->_count_statement = $count_statement;
        $this->_logger = Log::singleton('null');
    }

    function __destruct()
    {
    }

    public function ingest()
    {
        $this->_logger->info('Started ingestion for class: ' . get_class($this));
        $time_start = microtime(true);
        $sourceRows = 0;
        $countRowsAffected = 0;
        foreach($this->_pre_ingest_update_statements as $updateStatement)
        {
            try
            {
                $this->_logger->debug("Pre ingest update statement: $updateStatement");
                $this->_source_db->handle()->prepare($updateStatement)->execute();
            }
            catch(PDOException $e)
            {
                $this->_logger->warning(array(
                    'message' => 'Caught exception: (update statement) ' . $e->getMessage() ,
                    'stacktrace' => $e->getTraceAsString()
                ));
            }
        }

        // The count query must be before the source query for
        // unbuffered queries.
        if ($this->_count_statement != null) {
            $this->_logger->debug('Count query: ' . $this->_count_statement);
            $results   = $this->_source_db->query($this->_count_statement);
            $rowsTotal = $results[0]['row_count'];
        }

        $this->_logger->debug('Source query: ' . $this->_source_query);
        $message        = get_class($this) . ': Querying...';
        $message_length = strlen($message);
        print ($message);
        $srcStatement = $this->_source_db->handle()->prepare($this->_source_query);
        $srcStatement->execute();

        print(str_repeat(chr(8), $message_length));
        print(str_repeat(' ', $message_length));
        print(str_repeat(chr(8), $message_length));

        if ($this->_count_statement == null) {
            $rowsTotal = $srcStatement->rowCount();
        }

        $this->_logger->debug("Row count: $rowsTotal");

        $field_sep = chr(30);
        $line_sep = chr(29);
        $string_enc = chr(31);
        //$escape_chr = chr(27);
        $this->_destination_db->handle()->beginTransaction();
        $this->_destination_db->handle()->prepare("SET FOREIGN_KEY_CHECKS = 0")->execute();

        if ($this->_delete_statement == null ) {
            $this->_logger->debug("Truncating table '{$this->_insert_table}'");
            $this->_destination_db->handle()->prepare("TRUNCATE TABLE {$this->_insert_table}")->execute();
        } else if ($this->_delete_statement !== 'nodelete' )  {
            $this->_logger->debug('Delete statement: ' . $this->_delete_statement);
            $this->_destination_db->handle()->prepare($this->_delete_statement)->execute();
        }

        $infile_name = "/tmp/{$this->_insert_table}.data" . $this->_destination_db->_db_port;

        $f = fopen($infile_name, 'w');
		
		if($f === FALSE)
		{
			$infile_name = "/tmp/{$this->_insert_table}.data" . $this->_destination_db->_db_port.rand();
			 $f = fopen($infile_name, 'w');
			 if($f === FALSE)
			 {
				 throw new Exception(get_class($this) . ': tmp file error: could not open file: '.$infile_name );
			 }
		}
    	$this->_logger->debug("Using temporary file '$infile_name'");
        $exec_output = array();
        $warnings = array();
        while ($srcRow = $srcStatement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT))
        {
            $tmp_values = array();
            foreach ($this->_insert_fields as $insert_field) {
                $tmp_values[$insert_field] = $insert_field == 'order_id' ? $sourceRows : (!isset($srcRow[$insert_field]) ? '\N' :
                    (empty($srcRow[$insert_field]) ?  $string_enc . '' . $string_enc : $srcRow[$insert_field])  );
            }
            /*foreach($this->_insert_fields as $insert_field)
            {
               $value = $string_enc . '' . $string_enc;
                if (isset($srcRow[$insert_field]))
                {
                    $escaped_value = $srcRow[$insert_field];
                    $escaped_value = str_replace($escape_chr, $escape_chr . $escape_chr, $escaped_value);
                    $escaped_value = str_replace($string_enc, $escape_chr . $string_enc, $escaped_value);
                    $value = $string_enc . $escaped_value . $string_enc;
                }

                $tmp_values[$insert_field] = $insert_field == 'order_id' ? $string_enc . $sourceRows . $string_enc : $value;
            }*/
            fwrite($f, implode($field_sep, $tmp_values) . $line_sep);
            $sourceRows++;
            if ($sourceRows !== 0  && $sourceRows % 100000 == 0)
            {
                $message = get_class($this) . ': Rows Written to File: ' . $sourceRows . ' of ' . $rowsTotal;
                $message_length = strlen($message);
                print ($message);
                print (str_repeat(chr(8) , $message_length));
            }
            if ($sourceRows !== 0  && $sourceRows % 250000 == 0 || $rowsTotal == $sourceRows)
            {
                $load_statement = 'load data local infile \'' . $infile_name . '\' into table ' . $this->_insert_table . ' fields terminated by 0x1e optionally enclosed by 0x1f  lines terminated by 0x1d (' . implode(",", $this->_insert_fields) . ')'; //escaped by 0x1b
                try
                {
                    $output = array();
                    if ($this->_destination_db->_db_engine !== 'mysql')
                    {
                        throw new Exception(get_class($this) . ': Unsupported operation: currently only mysql is supported as destination db. ' . $this->_destination_db->_db_engine . ' was passed.');
                    }

                    $command = "mysql --local-infile -h {$this->_destination_db->_db_host} -P {$this->_destination_db->_db_port} -u {$this->_destination_db->_db_username} -p{$this->_destination_db->_db_password} {$this->_destination_db->_db_name} -e \"$load_statement\" 2>&1";
                    exec($command, $output, $return_var);

                    if (count($output) > 0)
                    {
                        throw new Exception(get_class($this) . ': load error ' . $return_var . ' ' . implode("\n", $output));
                    }
                    // $destStatementPrepared = $this->_destination_db->handle()->prepare($load_statement);
                    // $destStatementPrepared->execute();
                    /* $getWarningsStatement = $this->_destination_db->handle()->prepare('show warnings');
                    $getWarningsStatement->execute();
                    while ($warningRow = $getWarningsStatement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
                        //$this->_logger->debug('Warning: ' . json_encode($warningRow));
                        //$warnings[]  = "\t\t".implode(' | ', $warningRow) ;
                    }
                    */

                    fclose($f);
                    $f = fopen($infile_name, 'w');
                }
                catch(Exception $e)
                {
                    $this->_logger->err(array(
                        'message' => 'Caught exception: (load statement) ' . $e->getMessage() ,
                        'stacktrace' => $e->getTraceAsString()
                    ));
                    $this->_destination_db->handle()->rollback();
                    return;
                }
            }
        }
        fclose($f);
        unlink($infile_name);
        foreach($this->_post_ingest_update_statements as $updateStatement)
        {
            try
            {
                $this->_logger->debug("Post ingest update statement: $updateStatement");
                $this->_destination_db->handle()->prepare($updateStatement)->execute();
            }
            catch(PDOException $e)
            {
                $this->_logger->err(array(
                    'message' => 'Caught exception (post ingest update: ' . $updateStatement . '): ' . $e->getMessage() ,
                    'stacktrace' => $e->getTraceAsString()
                ));
                $this->_destination_db->handle()->rollback();
                return;
            }
        }

        $this->_destination_db->handle()->prepare("SET FOREIGN_KEY_CHECKS = 1")->execute();
        $this->_destination_db->handle()->commit();

        if ($rowsTotal > 0) {
            $this->_logger->debug('Optimizing table');
            $this->_destination_db->handle()->prepare("OPTIMIZE TABLE {$this->_insert_table}")->execute();
        }

        $time_end = microtime(true);
        $time = $time_end - $time_start;
        $message = get_class($this) . "\n" /*.$load_statement."\n"*/ . ' Rows Processed: ' . $sourceRows . ' of ' . $rowsTotal . " (Time Taken: " . number_format($time, 2) . " s)\n";
        if (count($warnings) > 0)
        {
            $message.= "\tWarnings: " . count($warnings) . "\n";
            $message.= implode("\n", $warnings) . "\n";
        }
        $message_length = strlen($message);
        print ($message);

        // NOTE: This is needed for the log summary.
        $this->_logger->notice(array(
            'message' => 'Finished ingestion',
            'class' => get_class($this) ,
            'start_time' => $time_start,
            'end_time' => $time_end,
            'records_examined' => $rowsTotal,
            'records_loaded' => $sourceRows,
        ));
    }

    public function setLogger(Log $logger)
    {
        $this->_logger = $logger;
    }
}

?>
