<?php

   use CCR\DB;

   /*
    * @Class XDReportManager
    * Used for keeping track of charts a user wishes to add to his / her report
    */

   class XDReportManager extends PDOClient{

      private $_user_id = null;
      private $_person_id = null;
      private $_user_full_name = null;
      private $_user_first_name = null;
      private $_user_last_name = null;
      private $_user_email = null;
      private $_charts_per_page = 1;
      private $_active_role_id = null;
      private $_site_host = '';
      private $_report_name = null;
      private $_report_title = null;
      private $_report_header = null;
      private $_report_footer = null;
      private $_report_schedule = null;
      private $_report_delivery = null;
      private $_report_format = null;
      private $_report_id = null;
      private $_pdo = null;
      private $_user_token = null;

      // --------------------------------------------
      
      // Below is a list of acceptable output formats and their respective content types
      
      private static $_header_map = array(
      
         'doc' => 'application/vnd.ms-word', 
         'pdf' => 'application/pdf'
      
      );
      
      const DEFAULT_FORMAT = 'pdf';
            
      // --------------------------------------------

      public function __construct($user) {
      
         $this->_site_host = xd_utilities\getConfiguration('general', 'site_address');
         $this->_site_host = $this->_renderAsInternalURL($this->_site_host);
         $this->_user_id = $user->getUserID();
         $this->_person_id = $user->getPersonID();
         $this->_user_full_name = $user->getFormalName();
         $this->_user_first_name = $user->getFirstName();
         $this->_user_last_name = $user->getLastName();
         $this->_user_email = $user->getEmailAddress();
         $this->_active_role_id = $user->getActiveRole()->getIdentifier(true);
         $this->_pdo = DB::factory('database');
         $this->_user_token = $user->getToken();
         
      }//__construct

		// --------------------------------------------
				
      public function emptyCache() {
         
         $this->_pdo->execute(
            'UPDATE ReportCharts SET image_data=NULL WHERE user_id=:user_id',
            array(
               'user_id' => $this->_user_id
            )
         );
               
      }//emptyCache
      
      // --------------------------------------------
      
      public static function isValidFormat($format) {
      
         return array_key_exists($format, self::$_header_map);

      }//isValidFormat

      // --------------------------------------------
      
      public static function resolveContentType($format) {
      
         return self::$_header_map[$format];
         
      }//resolveContentType      

      // --------------------------------------------
            
      private function _renderAsInternalURL($url) {
         
         if (substr_count($url, '/') < 3){
            $url .= '/';
         }
         
         list ($proto, $dummy, $host, $remainder) = explode('/', $url, 4);
   
         $host_components = explode(':', $host);
         $port = (count($host_components) == 2) ? ':'.$host_components[1] : '';
   
         if (!empty($port)) {
            $port = \xd_rest\resolveSecurePort($port);
         }

         $remainder = !empty($remainder) ? '/'.$remainder : '';

         return "http://127.0.0.1$port$remainder";
   
      }//_renderAsInternalURL

      // --------------------------------------------
      
      public static function enumScheduledReports($schedule_frequency) {
      
         $pdo = DB::factory('database');
      
         $results = $pdo->query("SELECT user_id, report_id FROM moddb.Reports WHERE schedule='$schedule_frequency'");
      
         $scheduled_reports = array();
               
         foreach($results as $report_data) {
            $scheduled_reports[] = array('user_id' =>  $report_data['user_id'], 'report_id' => $report_data['report_id']);
         }
         
         return $scheduled_reports;
      
      }//enumScheduledReports
      
      // --------------------------------------------

      public function configureSelectedReport(
      
         $report_id, 
         $report_name, $report_title,
         $report_header, $report_footer,
         $report_font, $report_format, $charts_per_page,
         $report_schedule, $report_delivery) 
         
      {

         $this->_report_id = $report_id;
         $this->_report_name = $report_name;
         $this->_report_title = $report_title;
         $this->_report_header = $report_header;
         $this->_report_footer = $report_footer;
         $this->_report_font = $report_font;
         $this->_report_format = $report_format;
         $this->_charts_per_page = $charts_per_page;
         $this->_report_schedule = $report_schedule;
         $this->_report_delivery = $report_delivery;

      }//configureSelectedReport

      // --------------------------------------------
      
      public function saveThisReport(){
               
         if (!isset($this->_report_id)) {
            throw new \Exception("configureSelectedReport() must be called first");
         }
         
         $this->_pdo->execute("UPDATE Reports SET 
            name = :report_name, 
            title = :report_title, 
            header =  :report_header, 
            footer = :report_footer, 
            font = '{$this->_report_font}', 
            charts_per_page = {$this->_charts_per_page},
            format = '{$this->_report_format}', 
            schedule =  '{$this->_report_schedule}', 
            delivery = '{$this->_report_delivery}'
            WHERE report_id = '{$this->_report_id}' ", 
               array(
                  'report_name' => $this->_report_name,
                  'report_title' => $this->_report_title,
                  'report_header' => $this->_report_header,
                  'report_footer' => $this->_report_footer                  
               )
            ); 
            
      }//saveThisReport

      // --------------------------------------------
      
      private function _fontWrapper($text, $font_name, $font_size = 12) {
      
         return '<span style="font-family: ' . strtolower($font_name) . '; font-size: ' . $font_size . 'px">' . $text . '</span>';
      
      }//_fontWrapper
 
      // --------------------------------------------

      public static function sanitizeFilename($filename) {
      
         $filename = preg_replace('/[^a-zA-Z0-9-_\. ]/','', $filename);
         $filename = strtolower(str_replace(" ", "_", $filename));   
         
         return (empty($filename) == true) ? 'xdmod_report' : $filename;
         
      }//sanitizeFilename
      
      // --------------------------------------------
                  
      public function getPreviewData($report_id, $token, $charts_per_page) {
      
         $report_data = $this->loadReportData($report_id, false);
      
         $report_font = $report_data['general']['font'];
         
         $rData = array();
         $chartSlot = array();
         
         $chartCount = 0;
         
         foreach($report_data['queue'] as $report_chart) {
                                         
            $suffix = ($chartCount++ % $charts_per_page);
            
            if (strtolower($report_chart['timeframe_type']) == 'user defined') {
            
               list($start_date, $end_date) = explode(' to ', $report_chart['chart_date_description']);
               
            }
            else {
            
               $e = \xd_date\getEndpoints($report_chart['timeframe_type']);
               
               $start_date = $e['start_date'];
               $end_date = $e['end_date'];
               
            }
            
            // Update comments and hyperlink so reporting engine can work with the correct chart (image)
            $report_chart['chart_date_description'] = $start_date.' to '.$end_date;
            
            $report_chart['chart_id'] = preg_replace('/start_date=(\d){4}-(\d){2}-(\d){2}/', "start_date=$start_date", $report_chart['chart_id']);
            $report_chart['chart_id'] = preg_replace('/end_date=(\d){4}-(\d){2}-(\d){2}/', "end_date=$end_date", $report_chart['chart_id']);
              
            // Titles are handled by the report template itself and do not need to be repeated in the chart image
            $report_chart['chart_id'] = preg_replace('/&title=(.+)/', "&title=", $report_chart['chart_id']);

            // ====================================
                       
            if (empty($report_chart['chart_drill_details'])) {
            
               $report_chart['chart_drill_details'] = ORGANIZATION_NAME_ABBREV;
            
            }
            
            $chartSlot[$suffix] = array(
               'report_title' => (count($rData) == 0 && !empty($report_data['general']['title'])) ? $this->_fontWrapper($report_data['general']['title'], $report_font, 22) . '<br />' : '',
               'header_text' => $this->_fontWrapper($report_data['general']['header'], $report_font, 12),
               'footer_text' => $this->_fontWrapper($report_data['general']['footer'], $report_font, 12),
               'chart_title_'.$suffix => $this->_fontWrapper($report_chart['chart_title'], $report_font, 16),
               'chart_drill_details_'.$suffix => $this->_fontWrapper($report_chart['chart_drill_details'], $report_font, 12),
               'chart_timeframe_'.$suffix => $this->_fontWrapper($report_chart['chart_date_description'], $report_font, 14),
               //'chart_id_'.$suffix => str_replace('render_thumbnail=y', '', $this->_processChartIdentifier($report_chart['chart_id'], false, $token, array('scale' => 1)))
               'chart_id_'.$suffix =>  '/report_image_renderer.php?type=report&ref='.$report_id.';'.$report_chart['ordering']            
            );
            
            if (count($chartSlot) == $charts_per_page) {
            
               $combinedSlots = array();
               
               foreach ($chartSlot as $e) {
               
                  $combinedSlots += $e;
               
               }
               
               $rData[] = $combinedSlots;
            
               $chartSlot = array();
               
            }
                     
         }//foreach
         
         // ==================================
         
         if (count($chartSlot) > 0) {
         
            // Handle remainder of charts...
            
            $combinedSlots = array();

            foreach ($chartSlot as $e) {
            
               $combinedSlots += $e;
            
            }
                        
            for ($i = count($chartSlot); $i < $charts_per_page; $i++) {
            
               $combinedSlots += array(
                  'chart_title_'.$i => '',
                  'chart_drill_details_'.$i => '',
                  'chart_timeframe_'.$i => '',
                  'chart_id_'.$i => 'img_placeholder.php?'
               );
            
            }
            
            $rData[] = $combinedSlots;
         
         }
         
         return $rData;
         
      }//getPreviewData

      // --------------------------------------------      

      public function getChartFromID($uri) {
 
         return \xd_charting\getChartFromURI($uri, XDUser::getUserByID($this->_user_id));
 
      }//getChartFromID

      // --------------------------------------------      

      public function insertThisReport($report_derivation_method = 'Manual'){
      
         if (!isset($this->_report_id)) {
            throw new \Exception("configureSelectedReport() must be called first");
         }
         
         $this->_pdo->execute("INSERT INTO Reports 
            (report_id, 
            user_id, 
            name, 
            derived_from,
            title, 
            header, 
            footer, 
            font, 
            format, 
            schedule, 
            delivery, 
            selected,
            charts_per_page,
            active_role ) 
            VALUES ( 
            '{$this->_report_id}', 
            '{$this->_user_id}', 
            :report_name, 
            :derived_from,
            :report_title, 
            :report_header, 
            :report_footer, 
            '{$this->_report_font}', 
            '{$this->_report_format}', 
            '{$this->_report_schedule}', 
            '{$this->_report_delivery}',
            '0',
            '{$this->_charts_per_page}',
            '{$this->_active_role_id}'
            )
         ", array(
         
            'report_name' => $this->_report_name, 
            'derived_from' => $report_derivation_method,
            'report_title' => $this->_report_title,
            'report_header' => $this->_report_header,
            'report_footer' => $this->_report_footer 
            
            )
         
         );
         
      }//insertThisReport

      // --------------------------------------------

      public function generateUniqueName($base_name = 'TAS Report') {

         $pdo = DB::factory('database');

         $values = array();

         // If the existing $base_name has a numerical suffix, consider that value
         // when generating the new suffix.
         
         $name_frags = explode(' ', $base_name);
         $name_suffix = array_pop($name_frags);
         
         if (is_numeric($name_suffix)){
            $base_name = implode(' ', $name_frags).' ';
            $values[] = $name_suffix;
         }

         $results = $pdo->query("SELECT name FROM Reports WHERE user_id='{$this->_user_id}' AND name LIKE '$base_name%'");
         
         foreach($results as $report_data) {

            $name = substr($report_data['name'], strlen($base_name));

            if (is_numeric($name)){
               $values[] = $name;
            }
            
         }//foreach
         
         $id = (count($values) > 0) ? (max($values) + 1) : 1;
         
         $base_name = trim($base_name);
         
         return "$base_name ".$id;
         
      }//generateUniqueName
 
      // --------------------------------------------
         
      public function isUniqueName($report_name, $report_id) {
         
         $results = $this->_pdo->query("SELECT name FROM Reports WHERE user_id='{$this->_user_id}' AND report_id != '$report_id' AND name LIKE :report_name", array('report_name' => $report_name));
      
         return (count($results) == 0);
   
      }//isUniqueName

      // --------------------------------------------

      public function emptyQueue() {
      
         $this->_pdo->execute("DELETE FROM ChartPool WHERE user_id='{$this->_user_id}'");
      
      }//emptyQueue

      // --------------------------------------------
            
      private function _paramExists($param, $haystack) {
      
         $num_matches = preg_match("/$param=(.+)/", $haystack);
            
         return ($num_matches > 0);

      }//_paramExists
            
      // --------------------------------------------
            
      private function _getParameterIn($param, $haystack) {
      
         $num_matches = preg_match("/$param=(.+)/", $haystack, $matches);
            
         $param_value = '';
            
         if ($num_matches > 0) {    
            $frags = explode('&', str_replace('/', '&', $matches[1]));       
            $param_value = $frags[0];
         }
            
         return $param_value;
         
      }//getParameterIn

      // --------------------------------------------
				
		public function enumReportsUnderOtherRoles() {

         $results = $this->_pdo->query(
            'SELECT active_role, COUNT(*) AS num_reports FROM moddb.Reports WHERE user_id=:user_id AND active_role != :active_role GROUP BY active_role',
            array(
               'user_id' => $this->_user_id,
               'active_role' => $this->_active_role_id
            )
         );
			
			$reportBreakdown = array();
			
			foreach ($results as $r) {
			
            $role_id = explode(';', $r['active_role']);
			      
			   $reportBreakdown[] = array(
                                 'role' => \xd_roles\getFormalRoleNameFromIdentifier($r['active_role']),
                                 'num_reports' => $r['num_reports']
			                       );
			
			}//foreach

			return $reportBreakdown;
		
		}//enumReportsUnderOtherRoles
		            
      // --------------------------------------------
      
      public function fetchChartPool() {
      
         //$query = "SELECT chart_id, user_id, insertion_rank, chart_title, chart_drill_details, chart_date_description, type FROM ChartPool WHERE user_id='{$this->_user_id}' AND active_role='{$this->_active_role_id}' ORDER BY insertion_rank ASC";
         
         $query = "SELECT chart_id, user_id, insertion_rank, chart_title, chart_drill_details, chart_date_description, type FROM ChartPool WHERE user_id='{$this->_user_id}' ORDER BY insertion_rank ASC";
                  
         $chartEntries = array();
         $results = $this->_pdo->query($query);
         
         foreach ($results as $entry) {
                  
            $timeframe_type = $this->_getParameterIn('timeframe_label', $entry['chart_id']);
                 
            $chartEntries[] = array(
                              'chart_id' => $entry['chart_id'], 
                              //'thumbnail_link' => $this->_processChartIdentifier($entry['chart_id'], false, '', array('scale' => 0.2)), 
                              'thumbnail_link' => '/report_image_renderer.php?type=chart_pool&ref='.$entry['user_id'].';'.$entry['insertion_rank'].'&token=', 
                              'chart_title' => $entry['chart_title'],
                              'chart_drill_details' => $entry['chart_drill_details'],
                              'chart_date_description' => $entry['chart_date_description'], 
                              //'timeframe_details' => $timeframe_details, 
                              'type' => $entry['type'],
                              'timeframe_type' => $timeframe_type
                           ); 
         
         }//foreach
         
         return $chartEntries;
   
      }//fetchChartPool

      // --------------------------------------------

      public function fetchReportTable() {

         //$query = "SELECT report_id, name, title, format, schedule, delivery, selected FROM Reports WHERE user_id='{$this->_user_id}' ";

         $query = "SELECT DISTINCT r.report_id, r.name, r.derived_from, r.title, r.charts_per_page, r.format, r.schedule, r.delivery, " . 
                  "(SELECT DISTINCT(SUM(1)) FROM ReportCharts AS rc WHERE rc.report_id = r.report_id) AS chart_count " .
                  "FROM Reports AS r, ReportCharts AS rc " .
                  "WHERE r.user_id='{$this->_user_id}'"; // AND r.active_role='{$this->_active_role_id}'";

         $Entries = array();

         $results = $this->_pdo->query($query);

         foreach ($results as $entry) {
            $Entries[] = array(
               'report_id' 		 => $entry['report_id'], 
               'report_name' 		 => $entry['name'],
               'creation_method'  => $entry['derived_from'],
               'report_title' 	 => $entry['title'],
               'charts_per_page'  => $entry['charts_per_page'],
               'report_format' 	 => $entry['format'],
               'report_schedule'  => $entry['schedule'],
               'report_delivery'  => $entry['delivery'],
               'chart_count' 	    => $entry['chart_count']
            );
         }
         
         return $Entries;

      }//fetchReportTable
      
      // --------------------------------------------
      
      private function _generateUID() {
      
          list($usec, $sec) = explode(" ", microtime());
          return ((float)$usec + (float)$sec);
    
      }//_generateUID

      // --------------------------------------------
            
      public function flushReportImageCache() {
      
         $cache_dir = '/tmp/';
         
         if ($dh = opendir($cache_dir)) {
         
            while (($file = readdir($dh)) !== false) {
            
               if (
                  (preg_match('/^xd_report_volatile_'.$this->_user_id.'_(.+).[png|xrc]/', $file) == 1) ||
                  (preg_match('/^'.$this->_user_id.'-(.+).png/', $file) == 1)
               ) {
                  unlink($cache_dir.$file);
               }
               
            }
            
            closedir($dh);
            
         }
         
      }//flushReportImageCache
      
      // --------------------------------------------
            
      public function loadReportData($report_id) {
      
         $return_data = array();
         
         // ==============================      
         
         $query = "SELECT name, title, header, footer, format, charts_per_page, font, schedule, delivery FROM Reports WHERE user_id='{$this->_user_id}' AND report_id ='{$report_id}'";
         
         $return_data['general'] = array();
         
         $results = $this->_pdo->query($query);
         
         if (count($results) == 0) {
         
            $return_data['success'] = false;
            $return_data['message'] = "Report with id $report_id could not be found.";      
            return $return_data;    
              
         }
         
         $return_data['general']['name'] = $results[0]['name'];
         $return_data['general']['title'] = $results[0]['title'];
         $return_data['general']['header'] = $results[0]['header'];
         $return_data['general']['footer'] = $results[0]['footer'];
         $return_data['general']['format'] = $results[0]['format'];
         $return_data['general']['charts_per_page'] = $results[0]['charts_per_page'];
         $return_data['general']['font'] = $results[0]['font'];
         $return_data['general']['schedule'] = $results[0]['schedule'];
         $return_data['general']['delivery'] = $results[0]['delivery'];
         
         // ==============================
         
         $query = "SELECT chart_id, report_id, ordering, chart_title, chart_drill_details, chart_date_description, chart_type, type, timeframe_type FROM ReportCharts WHERE user_id='{$this->_user_id}' AND report_id='{$report_id}' ORDER BY ordering ASC";
      
         $return_data['queue'] = array();
      
         $results = $this->_pdo->query($query); 
      
         foreach($results as $entry) {
                  
            //$timeframe_details = $this->_getParameterIn('timeframe_label', $entry['chart_id']);
                          
            $chart_data = array();
            
            $chart_data['chart_id'] = $entry['chart_id']; 
            //$chart_data['thumbnail_link'] = $this->_processChartIdentifier($entry['chart_id'], false, '', array('scale' => 0.2));
            
            
            $chart_data['thumbnail_link'] = '/report_image_renderer.php?type=report&ref='.$entry['report_id'].';'.$entry['ordering'].'&dc='.$this->_generateUID().'&token='; 
            
            $chart_data['ordering'] = $entry['ordering'];
            
            $chart_data['chart_title'] = $entry['chart_title'];
            $chart_data['chart_drill_details'] = $entry['chart_drill_details'];
            $chart_data['chart_date_description'] = $entry['chart_date_description'];
            //$chart_data['timeframe_details'] = $timeframe_details;
            $chart_data['type'] = $entry['type'];
            $chart_data['timeframe_type'] = $entry['timeframe_type'];
                  
            $return_data['queue'][] = $chart_data;
                  
         }//foreach
           
         // ==============================
               
         $return_data['success'] = true;
         
         return $return_data;
         
      }//loadReportData

      // ------------------------------------------------------
      
      // Transform the chart_id into a full (absolute) REST call
      
      private function _processChartIdentifier($chart_id, $use_internal_url = false, $token = '', $overrides = array()) {

         // The default 'scale' setting of 3 (along with <image scaleImage="FillFrame" in the jasper .jrxml files) dictates the pixel
         // density of the chart image(s) in the report      

         $scale = (isset($overrides['scale'])) ? $overrides['scale'] : 3;
         
         $guide_lines = (isset($overrides['guide_lines'])) ? $overrides['guide_lines'] : 'y';         
         
         if (strstr($chart_id, 'format=hc_jsonstore') !== false) {
         //if ($this->_paramExists('global_filters', $chart_id) == true) {
         
            // Dealing with a chart that has been introduced via the 'Usage Explorer' tab
            
            // NOTE: 'render_thumbnail=y' is an indicator to usage_explorer/get_data controller to take the generated HighCharts configuration
            // and render a thumbnail on the spot
            
            $abs_call = $use_internal_url ? $this->_site_host : '';

            $abs_call .= "/controllers/usage_explorer.php?";
            $abs_call .= $chart_id;
            $abs_call .= "&scale=$scale&format=png&show_guide_lines=$guide_lines&show_title=n&show_gradient=n&render_thumbnail=y";

            $abs_call .= "&token=".$this->_user_token;

            return $abs_call;
         
         }
         else {
         
            // Dealing with a chart that has been introduced via the 'Usage' tab
            
            $rest_call = $use_internal_url ? $this->_site_host : '';
            
            $rest_call .= "/rest/datawarehouse/explorer/plot/dimension=group_by/";
            $rest_call .= str_replace('&', '/', $chart_id);
               
            $rest_call .= '/scale='.$scale.'/show_guide_lines='.$guide_lines.'/show_title=n/show_gradient=n/format=png_inline';
            $rest_call .= "?token=";
            $rest_call .= $use_internal_url ? $this->_user_token : $token;
            
            return $rest_call;
         
         }
      
      }//processChartIdentifier
      
      // ------------------------------------------------------
            
      // Retrieve report data for use by the XML definition
      
      public function fetchReportData($report_id) {
   
         $query = "SELECT ordering,  chart_title, chart_id, chart_date_description, chart_drill_details, timeframe_type FROM ReportCharts 
                   WHERE report_id='{$report_id}' AND user_id='{$this->_user_id}' ORDER BY ordering ASC";

         $results = $this->_pdo->query($query);
   
         $report_data = array();
         
         foreach($results as $entry) {
         
            $chart_data = array();
            
            $chart_data['order'] = $entry['ordering'];
            $chart_data['title'] = $entry['chart_title'];
            $chart_data['comments'] = $entry['chart_date_description'];
            $chart_data['drill_details'] = $entry['chart_drill_details'];
            $chart_data['timeframe_type'] = $entry['timeframe_type'];
            
            $chart_data['image_url'] = $this->_processChartIdentifier($entry['chart_id'], true);
            
            $report_data[] = $chart_data;
            
         }//foreach
         
         return $report_data;
         
      }//fetchReportData

      // --------------------------------------------

      private function createElement(&$dom, &$node, $elementText, $text) {
      
         $elementNode = $dom->createElement($elementText);
         $node->appendChild($elementNode);
         
         $textNode = $dom->createTextNode(empty($text) ? ' ' : $text);
         $elementNode->appendChild($textNode);
         
      }//createElement

      // --------------------------------------------

      public function removeReportbyID($report_id){
   
         $this->_pdo->execute( "DELETE FROM Reports WHERE user_id='{$this->_user_id}' AND report_id = '$report_id'");
      
      }//removeReportbyID

      // --------------------------------------------

      public function buildBlobMap($report_id, &$map) {

         $query = "SELECT chart_id, image_data FROM ReportCharts WHERE report_id='{$report_id}' AND user_id='{$this->_user_id}'";
                         
         $map = $this->_pdo->query($query);
      
      }//buildBlobMap

      // --------------------------------------------
      
      public function resolveBlobFromChartId(&$map, $chart_id) {
               
         foreach ($map as $e) {
         
            if ($chart_id == $e['chart_id']) {
               return $e['image_data'];
            }
            
         }
         
         return NULL;
         
      }//resolveBlobFromChartId
      
      // --------------------------------------------
            
      public function removeReportCharts($report_id){
   
         $this->_pdo->execute( "DELETE FROM ReportCharts WHERE user_id='{$this->_user_id}' AND report_id = '$report_id' ");
      
      }//removeReportCharts
   
      // --------------------------------------------
      
      public function syncDatesBetweenIDAndBlobs($report_id) {
      
         $query = "SELECT chart_id, ordering, substr(image_data, 1, 21) AS blob_timestamp FROM ReportCharts WHERE report_id='{$report_id}'";
         
         $result = $this->_pdo->query($query);
         
         foreach($result as $r) {
            
            if (is_null($r['blob_timestamp'])) continue;

            list($blob_start, $blob_end) = explode(',', $r['blob_timestamp']);
            
            print "order: ".$r['ordering']."\n";
            print "blob start: $blob_start\n";
            print "blob end: $blob_end\n";
            print "chart_id: ".$r['chart_id']."\n\n";
            
            $rep = preg_replace('/start_date=(\d{4}-\d\{2}-\d{2})/', "start_date=$blob_start", $r['chart_id'], 1);
            
            print "updated_cid: $rep\n";
            
            print "\n";
            
         }

      }//syncDatesBetweenIDAndBlobs
      
      // --------------------------------------------
            
      public function saveCharttoReport($report_id, $chart_id, $chart_title, $chart_drill_details, $chart_date_description, $position_in_report, $timeframe_type, $entry_type, &$map = array()) {
         
         $this->_pdo->execute("INSERT INTO ReportCharts ( 
            chart_id, 
            report_id, 
            user_id, 
            chart_title,
            chart_drill_details,
            chart_type, 
            chart_date_description, 
            ordering,
            timeframe_type,
            image_data,
            type,
            selected) 
            VALUES (
               :chart_id,     
               '$report_id', 
               '{$this->_user_id}', 
               :chart_title,
               :chart_drill_details,
               '', 
               :chart_date_description, 
               '$position_in_report',
               '$timeframe_type',
               :image_data,
               '$entry_type',
               '0') 
         ",
         
         array(
         
            'chart_id' => $chart_id,
            'chart_title' => $chart_title, 
            'chart_drill_details' => $chart_drill_details, 
            'chart_date_description' => $chart_date_description, 
            'image_data' => $this->resolveBlobFromChartId($map, $chart_id)
            
            )
            
         );
         
      }//saveCharttoReport

      // --------------------------------------------

      public function removeChartFromChartPoolByID($chart_id) {
   
         $this->_pdo->execute("DELETE FROM ChartPool WHERE chart_id=:chart_id AND user_id='{$this->_user_id}'", array('chart_id' => $chart_id));
      
      }//removeChartFromChartPoolByID

      // --------------------------------------------
      
      public function getReportUserName($report_id) {
      
         $results = $this->_pdo->query("SELECT u.first_name, u.last_name FROM Users AS u, Reports AS r WHERE r.user_id='{$this->_user_id}' AND r.report_id= '$report_id' AND r.user_id=u.id");

         return $results[0]['first_name']." ".$results[0]['last_name'];
         
      }//getReportUserName

      // --------------------------------------------
      
      public function getReportUserFirstName($report_id) {
      
         $results = $this->_pdo->query("SELECT u.first_name FROM Users AS u, Reports AS r WHERE r.user_id='{$this->_user_id}' AND r.report_id='$report_id' AND r.user_id=u.id");
         
         return $results[0]['first_name'];
         
      }//getReportUserFirstName

      // --------------------------------------------
      
      public function getReportUserLastName($report_id) {
      
         $results = $this->_pdo->query("SELECT u.last_name FROM Users AS u, Reports AS r WHERE r.user_id='{$this->_user_id}' AND r.report_id='$report_id' AND r.user_id=u.id");

         return $results[0]['last_name']; 
              
      }//getReportUserLastName

      // --------------------------------------------
      
      public function getReportUserEmailAddress($report_id) {
      
         $results = $this->_pdo->query("SELECT u.email_address FROM Users AS u, Reports AS r WHERE r.user_id='{$this->_user_id}' AND r.report_id='$report_id' AND r.user_id=u.id");
         
         return $results[0]['email_address']; 
         
      }//getReportUserEmailAddress

      // --------------------------------------------
      
      public function getReportFormat($report_id) {
      
         $results = $this->_pdo->query("SELECT format FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");

         return $results[0]['format']; 
              
      }//getReportFormat

      // --------------------------------------------
      
      public function getReportFont($report_id) {
      
         $results = $this->_pdo->query("SELECT font FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");
         
         return $results[0]['font']; 
              
      }//getReportFont

      // --------------------------------------------
      
      public function getReportName($report_id, $sanitize = false) {
      
         $results = $this->_pdo->query("SELECT name FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");

         return ($sanitize == false) ? $results[0]['name'] : self::sanitizeFilename($results[0]['name']);         
              
      }//getReportName

      // --------------------------------------------
      
      public function getReportHeader($report_id) {
      
         $results = $this->_pdo->query("SELECT header FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");
         
         return $results[0]['header'];
              
      }//getReportHeader

      // --------------------------------------------
      
      public function getReportFooter($report_id) {
      
         $results = $this->_pdo->query("SELECT footer FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");

         return $results[0]['footer'];
              
      }//getReportFooter

      // --------------------------------------------
      
      public function getReportTitle($report_id) {
      
         $results = $this->_pdo->query("SELECT title FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");
         
         return $results[0]['title'];

      }//getReportTitle

      // --------------------------------------------
      
      public function getReportDerivation($report_id) {
      
         $results = $this->_pdo->query("SELECT derived_from FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");
         
         return $results[0]['derived_from'];

      }//getReportDerivation
      
      // --------------------------------------------
      
      public function getReportChartsPerPage($report_id) {
      
         $results = $this->_pdo->query("SELECT charts_per_page FROM Reports WHERE user_id='{$this->_user_id}' AND report_id='$report_id'");

         return $results[0]['charts_per_page']; 
              
      }//getReportChartsPerPage
      
      // --------------------------------------------
      
      private function _generateCachedFilename($insertion_rank, $volatile = false, $base_name_only = false) {
      
         if ($volatile == true) {
         
            $duplication_id = (is_array($insertion_rank) && isset($insertion_rank['did'])) ? $insertion_rank['did'] : '';

            $this->_ripTransform($insertion_rank, 'did');
               
            if (is_array($insertion_rank) && isset($insertion_rank['rank']) && isset($insertion_rank['start_date']) && isset($insertion_rank['end_date'])) {
               
               if ($base_name_only == true) {
                  return '/tmp/xd_report_volatile_'.$this->_user_id.'_'.$insertion_rank['rank'].$duplication_id.'.png';
               }
               
               return '/tmp/xd_report_volatile_'.$this->_user_id.'_'.$insertion_rank['rank'].$duplication_id.'_'.$insertion_rank['start_date'].'_'.$insertion_rank['end_date'].'.png';
               
            }
            else {
               return '/tmp/xd_report_volatile_'.$this->_user_id.'_'.$insertion_rank.$duplication_id.'.png';
            }
            
         }
         else {
            return '/tmp/'.$insertion_rank['report_id'].'_'.$insertion_rank['ordering'].'_'.$insertion_rank['start_date'].'_'.$insertion_rank['end_date'].'.png';
         }
      
      }//_generateCachedFilename

      // --------------------------------------------
      
      private function _ripTransform (&$arr, $item) {

         if (is_array($arr) && isset($arr[$item])) {
               
            unset($arr[$item]);
            if (count($arr) == 1) $arr = array_pop($arr);
               
         }
                     
      }//_ripTransform

      // --------------------------------------------
                  
      public function fetchChartBlob($type, $insertion_rank, $chart_id_cache_file = NULL) {

         $pdo = DB::factory('database');
         $trace = "";
               
         switch($type) {
            
            case 'volatile':      

               $temp_file = $this->_generateCachedFilename($insertion_rank, true);
                             
               if (file_exists($temp_file)) {

                  print file_get_contents($temp_file);
                  
               }
               else {
               
                  if (is_array($insertion_rank) && isset($insertion_rank['rank']) && isset($insertion_rank['start_date']) && isset($insertion_rank['end_date'])) {

                     $blob = $this->generateChartBlob($type, $insertion_rank, $insertion_rank['start_date'], $insertion_rank['end_date']);
                     
                  }
                  else {
            
                     // If no start or end dates are supplied, then, grab directly from chart pool

                     $chart_config_file = str_replace('.png', '.xrc', $temp_file);
                     $blob = $this->fetchChartBlob('chart_pool', $insertion_rank['rank'], $chart_config_file);
                     
                     // The following 3 lines are in place as a performance enhancement.  Should the user change the timeframe
                     // of a 'volatile' chart, then reset the timeframe back to the default, the logic below ensures that
                     // the default cached data is presented.
                     
                     $chart_id_config = file($chart_config_file);
                     
                     file_put_contents($temp_file, $blob);
                     $temp_file = str_replace('.png', '_'.$chart_id_config[1].'.png', $temp_file);

                  }                  

                  file_put_contents($temp_file, $blob);
                  
                  print $blob;
                  
               }
               
               exit;
               
               break;
               
                                     
            case 'chart_pool':
            
               $this->_ripTransform($insertion_rank, 'did');
               
               $iq = $pdo->query(
                  "SELECT chart_id, image_data FROM ChartPool WHERE user_id=:user_id AND insertion_rank=:insertion_rank", 
                  array('user_id' => $this->_user_id, 'insertion_rank' => $insertion_rank)
               ); 
               
               $trace = "user_id = {$this->_user_id}, insertion_rank = $insertion_rank";
               
               break;
               
            
            case 'cached':
            
               $temp_file = $this->_generateCachedFilename($insertion_rank);
               
               if (file_exists($temp_file)) {
               
                  print file_get_contents($temp_file);
                  
               }
               else {
               
                  $blob = $this->generateChartBlob($type, $insertion_rank, $insertion_rank['start_date'], $insertion_rank['end_date']);
                  file_put_contents($temp_file, $blob);
                  
                  print $blob;
                  
               }
               
               exit;
               
               break;
 
               
            case 'report':
         
               $iq = $pdo->query(
                  "SELECT chart_id, timeframe_type, image_data, chart_date_description FROM ReportCharts WHERE report_id=:report_id AND ordering=:ordering", 
                  array('report_id' => $insertion_rank['report_id'], 'ordering' => $insertion_rank['ordering'])
               ); 
               
               $trace = "report_id = {$insertion_rank['report_id']}, ordering = {$insertion_rank['ordering']}";
                          
               
               break;
                
         }//switch($type)
         
         if (count($iq) == 0) {
            throw new \Exception("No ($type) chart entry could be located ($trace)");
         }
 
         $image_data = $iq[0]['image_data'];
         $chart_id = $iq[0]['chart_id'];
                 
         $active_start = $this->_getParameterIn('start_date', $chart_id);
         $active_end = $this->_getParameterIn('end_date', $chart_id);
            
         if (isset($iq[0]['chart_date_description'])) {
            
            list($active_start, $active_end) = explode(' to ', $iq[0]['chart_date_description']);
            
         }
     
         // Timeframe determination ----------------------------
         
         if ($type == 'chart_pool' || $type == 'volatile') {
            $timeframe_type = $this->_getParameterIn('timeframe_label', $chart_id);
         }
         
         if ($type == 'report') {
            $timeframe_type = $iq[0]['timeframe_type'];
         }         
         
         if (strtolower($timeframe_type) == 'user defined') {
            
            $start_date = $active_start;
            $end_date = $active_end;
                         
         }
         else {

            $e = \xd_date\getEndpoints($timeframe_type);

            $start_date = $e['start_date'];
            $end_date = $e['end_date'];
            
         }
         
         // ----------------------------------------------------

         if (!empty($chart_id_cache_file)) {
            file_put_contents($chart_id_cache_file, $chart_id."\n".$start_date.'_'.$end_date);
         }

         
         if (empty($image_data)) {
         
            // No BLOB to begin with
            return $this->generateChartBlob($type, $insertion_rank, $start_date, $end_date);
            
         }
         else {
            
            // BLOB exists. Parse out the date information prepended to the actual image data 
            // then compare against $start_date and $end_date to see if the image data needs
            // to be refreshed.
            
            $blob_elements = explode(';', $image_data, 2);
            list($blob_start, $blob_end) = explode(',', $blob_elements[0]);
            
            if (($blob_start == $start_date) && ($blob_end == $end_date)) {
            
               $image_data_header = substr($blob_elements[1], 0, 8);
            
               if ($image_data_header == "\x89PNG\x0d\x0a\x1a\x0a") {
            
                  //Cached blob is still usable (contains raw png data)
                  return $blob_elements[1];
               
               }
               else {
               
                  //Cached data is not considered 'valid'. Re-generate blob
                  return $this->generateChartBlob($type, $insertion_rank, $start_date, $end_date);
                  
               }
               
            }
            else {
            
               //Cached data has gone stale. Re-generate blob
               return $this->generateChartBlob($type, $insertion_rank, $start_date, $end_date);
               
            }
            
         }   

      }//fetchChartBlob
      
      // --------------------------------------------
      
      public function generateChartBlob($type, $insertion_rank, $start_date, $end_date) {
      
         $pdo = DB::factory('database');

         switch($type) {
         
            case 'volatile':
               
               $temp_file = $this->_generateCachedFilename($insertion_rank, true, true);
               $temp_file = str_replace('.png', '.xrc', $temp_file);
               
               $iq = array();
               
               if (file_exists($temp_file) == true) {
               
                  $chart_id_config = file($temp_file);
                  
                  $iq[] = array('chart_id' => $chart_id_config[0]);
        
               }     
               else {

                  return $this->generateChartBlob('chart_pool', $insertion_rank['rank'], $start_date, $end_date);
               
               }
                              
               break;
            
            case 'chart_pool':
         
               $iq = $pdo->query(
                  "SELECT chart_id FROM ChartPool WHERE user_id=:user_id AND insertion_rank=:insertion_rank", 
                  array('user_id' => $this->_user_id, 'insertion_rank' => $insertion_rank)
               );
               
               break;
               
            case 'cached':
            case 'report':
         
                $iq = $pdo->query(
                  "SELECT chart_id FROM ReportCharts WHERE report_id=:report_id AND ordering=:ordering", 
                  array('report_id' => $insertion_rank['report_id'], 'ordering' => $insertion_rank['ordering'])
               ); 
                          
               break;
                
         }//switch($type)

         if (count($iq) == 0) {
            
            throw new \Exception("Unable to target chart entry");
            
         }
         
         $chart_id = $iq[0]['chart_id'];
         
         $r_chart_id = $this->_processChartIdentifier($chart_id, true);
         
         $usesHighCharts = strstr($r_chart_id, 'usage_explorer.php');      
         
         if ($usesHighCharts == true) {

            $module = $this->_getParameterIn('controller_module', $r_chart_id);
         
            if (empty($module)) { $module = 'usage_explorer'; }
            
            $r_chart_id = preg_replace('/usage_explorer.php/', $module.'.php', $r_chart_id);
         
         }
         
         // ====================================================================

         $r_chart_id = str_replace('&render_thumbnail=y', '&render_for_report=y', $r_chart_id);
         $r_chart_id = preg_replace('/start_date=(\d){4}-(\d){2}-(\d){2}/', "start_date=$start_date", $r_chart_id);
         $r_chart_id = preg_replace('/end_date=(\d){4}-(\d){2}-(\d){2}/', "end_date=$end_date", $r_chart_id);
         
         // ====================================================================
         
         $r_chart_id = str_replace(' ', '%20', $r_chart_id);

         // Create a POST request (advised since we may be dealing with a lot of chart config options) 
         
         if ($usesHighCharts == true) {
            
            list($endpoint, $arg_list) = explode('?', $r_chart_id, 2);
            $arg_set = explode('&', $arg_list);
         
         }
         else {

            $site_host = str_replace('/', '\/', $this->_site_host);
            
            $g = explode('?token=', $r_chart_id);
            $r_chart_id = $g[0];
            
            preg_match("/($site_host((.+?)\/){4})(.+)/", $r_chart_id, $m);
            
            $endpoint = $m[1].'?token='.$this->_user_token;
            $arg_set = explode('/', $m[4]);

         }
         
         $query_params = array();
         
         foreach ($arg_set as $a) {
         
            list($arg_name, $arg_value) = explode('=', $a, 2);
            
            $query_params[$arg_name] = $arg_value;
         
         }//foreach
         
         $query_params['user_id'] = $this->_user_id;
                  
         // Thumbnail specific settings -------------------
         
         $query_params['format'] = 'png_inline';

         $query_params['scale'] = 1;
         $query_params['width'] = 800;
         $query_params['height'] = 600;
         
         $query_params['show_title'] = 'n';
         $query_params['title'] = '';
         $query_params['subtitle'] = '';
         
         $query_params['show_filters'] = false;
         
         $query_params['font_size'] = 3;
         
         // -----------------------------------------------
         
         $opts = array('http' =>
            array(
               'user_agent' => 'xdmod',
               'method'  => 'POST',
               'header'  => 'Content-type: application/x-www-form-urlencoded',
               'content' => http_build_query($query_params)
            )
         );
         
         $raw_png_data = @file_get_contents($endpoint, false, stream_context_create($opts));
         
         if ($raw_png_data === false) {
            $os = implode(' %% ', $opts['http']);
            throw new \Exception("Unable to retrieve image data via URL: $endpoint -- ".$os);
         }
         
         if (preg_match('/Notice: /', $raw_png_data) > 0) {

            throw new \Exception("Textual data is present along with the image data");

         }

         switch($type) {
         
            case 'chart_pool':
         
               $pdo->execute(
                  "UPDATE moddb.ChartPool SET image_data=:image_data WHERE user_id=:user_id AND insertion_rank=:insertion_rank", 
                  array(
                     'user_id' => $this->_user_id,
                     'insertion_rank' => $insertion_rank,
                     'image_data' => "$start_date,$end_date;".$raw_png_data
                  )
               );
               
               break;
               
            case 'volatile':
            case 'cached':
            
               return $raw_png_data;
               break;
               
            case 'report':

               $pdo->execute(
                  "UPDATE moddb.ReportCharts SET image_data=:image_data WHERE report_id=:report_id AND ordering=:ordering", 
                  array(
                     'report_id' => $insertion_rank['report_id'],
                     'ordering' => $insertion_rank['ordering'],
                     'image_data' => "$start_date,$end_date;".$raw_png_data
                  )
               );
                          
               break;
                
         }//switch($type)
                  
         return $raw_png_data;
          
      }//generateChartBlob
      
      // --------------------------------------------
                                   
      public function generateXMLConfiguration($display = true, $report_id, $export_format = NULL) {

         $dom = new DOMDocument("1.0");
         
         if ($display == true)
            header("Content-Type: text/xml");
         
         $nodeRoot = $dom->createElement("Report");
         $dom->appendChild($nodeRoot);
         
            //  -------------------<User>  -------------------
            $nodeUser = $dom->createElement("User");
            $nodeRoot->appendChild($nodeUser);
            
            $this->createElement($dom, $nodeUser, "LastName",       $this->getReportUserLastName($report_id)       );
            $this->createElement($dom, $nodeUser, "FirstName",      $this->getReportUserFirstName($report_id)      );
            $this->createElement($dom, $nodeUser, "Email",          $this->getReportUserEmailAddress($report_id)   );
            
            //  -------------------<Format>  -------------------
            $this->createElement($dom, $nodeRoot, "Format",         ($export_format != NULL) ? $export_format : $this->getReportFormat($report_id));
            
            //  -------------------<Title>  -------------------
            $this->createElement($dom, $nodeRoot, "Title",          $this->getReportTitle($report_id) );
            
            //  -------------------<pageHeader>  -------------------
            $this->createElement($dom, $nodeRoot, "PageHeader",     $this->getReportHeader($report_id) );
            
            //  -------------------<Section>  -------------------
            $results = $this->fetchReportData($report_id);
            
            //$a = array();
            
            $charts_per_page = $this->getReportChartsPerPage($report_id);
            
            $chart_aspect_ratio = array(
               1 => 'width=600/height=450',
               2 => 'width=472/height=354'
            );
            
            $chartCount = 0;
            
            foreach($results as $entry) {
             
               $chartSlot = $chartCount++ % $charts_per_page;
               
               if ($chartSlot == 0) {
               
                  $nodeSection = $dom->createElement("Section");
                  $nodeRoot->appendChild($nodeSection);
               
               }
                   
               $this->createElement($dom, $nodeSection, 'SectionTitle_'.$chartSlot,          $entry['title']);
                
               // ====================================
                             
               if (strtolower($entry['timeframe_type']) == 'user defined') {
               
                  list($start_date, $end_date) = explode(' to ', $entry['comments']);
                  
               }
               else {
               
                  $e = \xd_date\getEndpoints($entry['timeframe_type']);
                  
                  $start_date = $e['start_date'];
                  $end_date = $e['end_date'];
                  
               }
               
               // Update comments and hyperlink so reporting engine can work with the correct chart (image)
               $entry['comments'] = $start_date.' to '.$end_date;
               
               /*
               $entry['image_url'] = str_replace('&render_thumbnail=y', '&render_for_report=y', $entry['image_url']);
               $entry['image_url'] = preg_replace('/start_date=(\d){4}-(\d){2}-(\d){2}/', "start_date=$start_date", $entry['image_url']);
               $entry['image_url'] = preg_replace('/end_date=(\d){4}-(\d){2}-(\d){2}/', "end_date=$end_date", $entry['image_url']);
               
               $entry['image_url'] = preg_replace('/scale=(\d+)/', 'scale=$1/'.$chart_aspect_ratio[$charts_per_page], $entry['image_url']);
               
               if (strstr($entry['image_url'], 'usage_explorer.php') !== false) {
               
                  // Reformat the image URL for HighChart support
                  $entry['image_url'] = preg_replace('/scale=(\d+)\/width=(\d+)\/height=(\d+)/', 'scale=2&width=$2&height=$3', $entry['image_url']);

                  // The report templates take care of inserting titles into the report, so titles embedded into the chart images themselves
                  // are not necessary.
                              
                  $entry['image_url'] = preg_replace('/title=(.*)&scale/', 'title=&scale', $entry['image_url']);
               
               }
               */
               
               $entry['image_url'] = $this->_site_host.'/report_image_renderer.php?type=report&ref='.$report_id.';'.$entry['order'];
                      
               // ====================================
               
               $entry['image_url'] = str_replace(' ', '%20', $entry['image_url']);
               
               if (empty($entry['drill_details'])) {
                  $entry['drill_details'] = ORGANIZATION_NAME_ABBREV;
               }
               
               $this->createElement($dom, $nodeSection, 'SectionDrillParameters_'.$chartSlot,    $entry['drill_details']);
               $this->createElement($dom, $nodeSection, 'SectionDescription_'.$chartSlot,        $entry['comments']);
                  
               $this->createElement($dom, $nodeSection, 'SectionImage_'.$chartSlot,              $entry['image_url']);
               
               //$a[] = $entry['image_url'];
                 
            }//foreach
            
            $remainingSlots = $chartCount % $charts_per_page;
            
            if ($remainingSlots > 0) {
            
               // Handle remainder of charts
               
               for ($r = $remainingSlots; $r < $charts_per_page; $r++) {
               
                  $this->createElement($dom, $nodeSection, 'SectionTitle_'.$r,              '');
                  $this->createElement($dom, $nodeSection, 'SectionDrillParameters_'.$r,    '');
                  $this->createElement($dom, $nodeSection, 'SectionDescription_'.$r,        '');
                  $this->createElement($dom, $nodeSection, 'SectionImage_'.$r,              'dummy_image');                 
               
               }
            
            }//if ($remainingSlots > 0)
            
            //  -------------------<pageFooter>  -------------------
            
            $this->createElement($dom, $nodeRoot, "PageFooter", $this->getReportFooter($report_id));
         
         if ($display == true)
            echo $dom->saveXML();
         else
            return $dom->saveXML();

      }//generateXMLConfiguration

      // --------------------------------------------      

      private function _getFormatFromXML(&$xml_definition) {
      
         $c = preg_match('/<Format>(.+)<\/Format>/', $xml_definition, $matches);
   
         $format_via_xml = ($c == 1) ? $matches[1] : 'pdf';
   
         return strtolower($format_via_xml);
      
      }//_getFormatFromXML
      
      // --------------------------------------------     
            
      public function buildReport($report_id, $xml_definition) {
         
         if ($this->getReportDerivation($report_id) == 'Monthly Compliance Report') {
         
            $compliance_report = new XDComplianceReport();    
   
            $data = $compliance_report->prepareComplianceData();
          
            //$data['content'] = array();   // <--- comment/uncomment to toggle the 2 compliance report variations which exist.
            
            $response = $compliance_report->generate($data['start_date'].' to '.$data['end_date'], $data);
         
            return $response;
         
         }//if (report_id maps to a monthly compliance report)
         
         // ======================
         
         $base_path = xd_utilities\getConfiguration('reporting', 'base_path');
		
         // The report output format is determined from the <Format> tag in the XML definition
         $report_format = $this->_getFormatFromXML($xml_definition);
         
         $report_font = $this->getReportFont($report_id);

         // Initialize a temporary working directory for the report generation
         
         $template_path = tempnam('/tmp', $report_id.'-');
      
         exec("rm $template_path");
         exec("mkdir $template_path");
         exec("chmod 777 $template_path");
         
         // Copy all report templates into this working directory
         // (All templates?  Probably want to copy just the template of interest -- dictated by the font)
         
         exec("cp $base_path/*.jrxml $template_path");
         
         // Generate a report definition (XML) to be used as the input to the Jasper Report Builder application
         
         $report_file_name = $report_id;
         $report_xml_file = $template_path.'/'.$report_file_name.'.xml';
         $report_output_file = $template_path.'/'.$report_file_name.'.'.$report_format;
               
         $fh = fopen($report_xml_file, 'w') or die("Can't open report XML definition file");
         
            fwrite($fh, "$xml_definition"."\n");
            
         fclose($fh);
         
         // Run Jasper Report Builder application (XML --> PDF) ======================
         
         $report_builder_script = $base_path."/ReportBuilder.sh";
         
         $exec_return = 0;
         
         $charts_per_page = $this->getReportChartsPerPage($report_id);
         
         $report_template = 'template_'.$charts_per_page.'up';
         
         /*
         print "$report_builder_script -E -W $template_path -B $report_file_name -T $report_template -F $report_font 2>$template_path/build.log";
         exit;
         */
                   
         exec ("$report_builder_script -E -W $template_path -B $report_file_name -T $report_template -F $report_font 2>$template_path/build.log", $exec_result, $exec_return);         

         if (!file_exists($report_output_file) || $exec_return != 0) {
            //exec("rm -rf $template_path");
            throw new \Exception("There was a problem building the report.");
         }
         
         return array(
            'template_path' => $template_path, 
            'report_file' => $report_output_file
         );
         
      }//buildReport

      // --------------------------------------------   
            
      public function mailReport($report_id, $report_file, $frequency = '', $additional_config = array()) {
         
         $mail = ZendMailWrapper::init();

			$mailer_sender = xd_utilities\getConfiguration('mailer', 'sender_email');
			
         $mail->setFrom($mailer_sender, 'XDMoD');
  
         $frequency = (!empty($frequency)) ? ' '.$frequency : $frequency;
  
         $subject_suffix = (APPLICATION_ENV == 'dev') ? '[Dev]' : '';
         
         // -------------------
            
         $destination_email_address = $this->getReportUserEmailAddress($report_id);
         
         $mail->addTo($destination_email_address);
         
         // -------------------
         
         $report_owner = $this->getReportUserName($report_id);
         
         switch($this->getReportDerivation($report_id)) {
         
            case 'Monthly Compliance Report':
            
               $include_attachment = ($additional_config['failed_compliance'] > 0 || $additional_config['proposed_requirements'] > 0);
               $templateConfig = MailTemplates::complianceReport($report_owner, $additional_config['custom_message']);
            
               break;
               
            default:
            
               $include_attachment = true;
               $templateConfig = MailTemplates::customReport($report_owner, $frequency);
      
               break;
               
         }//switch
      
         $mail->setSubject("Your$frequency ".$templateConfig['subject']." $subject_suffix");
         $mail->setBodyText($templateConfig['message']);
         
         // -------------------

         if ($include_attachment) {
         
            $report_format = pathinfo($report_file, PATHINFO_EXTENSION);
         
            $attachment_file_name = $this->getReportName($report_id, true).'.'.$report_format;

            $at = $mail->createAttachment(file_get_contents($report_file));
            $at->type        = self::$_header_map[$report_format];
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;

            $at->filename    = $attachment_file_name;

         }//if ($include_attachment) 
         
         // -------------------
            
         try {
            $status = $mail->send();
         }
         catch(Exception $e){
            return false;
         }
         
         return true;

      }//mailReport
      
      // --------------------------------------------   
      
      public static function enumerateReportTemplates($roleCollection = array()) {
      
         $pdo = DB::factory('database');
      
         $roleSet = "'".implode("','", $roleCollection)."'";
         
         $results = $pdo->query("SELECT DISTINCT rt.id, rt.name, rt.description, rt.use_submenu  
                                 FROM ReportTemplates AS rt, Roles AS r, ReportTemplateACL AS acl 
                                 WHERE rt.id = acl.template_id AND acl.role_id = r.role_id AND abbrev IN ($roleSet)");
         
         return $results;

      }//enumerateReportTemplates

      // --------------------------------------------   
      
      // @function retrieveReportTemplate
      // Loads the report template from the persistence model (database)
      
      public static function retrieveReportTemplate($user, $template_id) {
      
         $pdo = DB::factory('database');
      
         $results = $pdo->query('SELECT template, name, title, header, footer, format, font, schedule, delivery, charts_per_page 
                                 FROM ReportTemplates WHERE id=:id', array('id' => $template_id));
         
         if (count($results) == 0)
         {
            throw new \Exception('No report template could be found having the id you specified');  
         }

         $templateClass = '\\ReportTemplates\\'.$results[0]['template'];
      
         $template_definition_file = dirname(__FILE__).'/ReportTemplates/'.$results[0]['template'].'.php';  
                  
         if (!file_exists($template_definition_file))
         {
            throw new \Exception("Report template definition could not be located");      
         }
                   
         $r = array('general' => $results[0]);
         
         $r['charts'] = $pdo->query('SELECT chart_id, ordering, chart_date_description, chart_title, chart_drill_details, timeframe_type 
                                     FROM ReportTemplateCharts WHERE template_id=:id ORDER BY ordering ASC', array('id' => $template_id));
         
         return new $templateClass($user, $r);

      }//retrieveReportTemplate
            
   }//XDReportManager

?>
