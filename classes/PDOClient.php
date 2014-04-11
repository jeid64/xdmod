<?php

	/*
	 * @Class PDOClient
	 *
	 * NOTE: This class can only be extended (and not used directly)
	 *
	 */
	 
	class PDOClient {
			
		private $_dbHandle = null;
			
		// --------------------------------------------
		
		/*
		 *
		 * @function _connect  (establishes a persistent database handle, should one not already exist)
		 * @access private
		 *
		 */
		 			
		private function _connect($config_block) {

			if(!$this->_dbHandle) {
			
				$db_host =     xd_utilities\getConfiguration($config_block, 'host');
				$db_database = xd_utilities\getConfiguration($config_block, 'database');
				$db_user =     xd_utilities\getConfiguration($config_block, 'user');
				$db_pass =     xd_utilities\getConfiguration($config_block, 'pass');

				$dsn = 'mysql:host='.$db_host.';dbname='.$db_database;
				$this->_dbHandle = new PDO($dsn, $db_user, $db_pass);

				$this->_dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		
		}//_connect

		// --------------------------------------------
		
		protected function _insertQuery($query, $params=array()) {
		
			$stmt = $this->_dbHandle->prepare($query);
			$stmt->execute($params);
				
			return $this->_dbHandle->lastInsertID();
			
		}//_insertQuery
			
		// --------------------------------------------
		
		protected function _query($query, $params=array()) {
		
			$stmt = $this->_dbHandle->prepare($query);
			$stmt->execute($params);
			
			try {
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $results;
			}
			catch (Exception $e) {
				// NON-SELECT based queries fall here
			}
			
		}//_query
		
		// --------------------------------------------
		
		public function __construct($config_block = 'database') {
		
			$this->_connect($config_block);
		
		}//__construct
	
	}//PDOClient

?>