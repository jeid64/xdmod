<?php

   class RDRClient {

      private $_kit_endpoint = 'http://info.teragrid.org/web-apps/xml/kit-rdr-v3/';
      
      //returns resource type RDR information which ALL XSEDE resources must have
      private $_kit_resource_endpoint = 'http://info.teragrid.org/web-apps/xml/kit-rdr-v3/resource';
      
      //returns compute type RDR information which only compute resource have to have
      private $_kit_compute_endpoint = 'http://info.teragrid.org/web-apps/xml/kit-rdr-v3/compute';
      
      //returns storage type RDR information which only storage resources have to have
      private $_kit_storage_endpoint = 'http://info.teragrid.org/web-apps/xml/kit-rdr-v3/storage';

      private $_resource_map = array();
      
      
      // ----------------------------------------      
      
      public function __construct ($resource_map = array()) {
      
         $this->_resource_map = $resource_map;
         
      }//__construct
      
      // ----------------------------------------      
      
      public function enumerateResources($statusFilter = NULL) {
      
         $xml_feed = file_get_contents($this->_kit_endpoint);
         
         $rdr = \xd_xml\xml2array($xml_feed);
         $rdr = $rdr['RDR_Resources']['RDR_Resource'];
         
         $response = array('resource_count' => count($rdr));
         
         foreach ($rdr as $r) {
            
            $timestamp = $this->_cleanTimestamp($r['attr']['Timestamp']);
            
            $resource_state = $this->_getResourceStatus($r);
            $resource_id = $this->_getResourceID($r['ResourceID']['value']);
            
            // Some resources report <ResourceType/> with no value
            $resource_type = isset($r['Resource']['ResourceType']['value']) ?  $r['Resource']['ResourceType']['value'] : 'Unknown';
            
            if (!isset($response[$resource_state])) $response[$resource_state] = array();
            if (!isset($response[$resource_state][$resource_type])) $response[$resource_state][$resource_type] = array();
            
            $response[$resource_state][$resource_type][] = array(
               'timestamp' => date('Y-m-d'),
               'rdr_timestamp' => $timestamp,
               'resource_name' => $r['ResourceID']['value'],
               'resource_id' => $resource_id,
               'site_id' => $r['SiteID']['value']
            );
            
         }//foreach

         if ($statusFilter == NULL) return $response;
         if ($statusFilter != NULL) return $response[$statusFilter];
         
      }//enumerateResources

      // ----------------------------------------
      
      public function enumerateComputeResources($desired_fields = array()) {
      
         $xml_feed = file_get_contents($this->_kit_compute_endpoint);
         
         $rdr = \xd_xml\xml2array($xml_feed);
         $rdr = $rdr['RDR_Resources']['RDR_Compute'];
      
         $response = array();
      
         $timestamp = date('Y-m-d');
         
         foreach ($rdr as $r) {
         
            $res = array(
            
               'timestamp' => $timestamp,
               'rdr_timestamp' => $this->_cleanTimestamp($r['attr']['Timestamp']),
               'resource_id' => $this->_getResourceID($r['ResourceID']['value']),
               'resource_name' => $r['ResourceID']['value']
                
            );
         
            $compute_resource = $r['ComputeResource'];
            
            $fields = array();
            
            foreach ($desired_fields as $df) {
            
               $res[$df] = NULL;
            
            }
            
            foreach ($compute_resource as $cr => $varr) {
            
               if (in_array($cr, $desired_fields)) {
               
                  $res[$cr] = (count($varr) == 1) ? $varr['value'] : NULL;   
               
               }
            
            }//foreach
            
            // Acquire TS conversion factor, should it exist
            
            $res['tg_conversion_factor'] = $this->_determineTGConversionFactor($compute_resource);
                        
            //$res['stats'] = $fields;
            
            $response[] = $res;
            
         }//foreach
         
         return $response;
   
      }//enumerateComputeResources

      // ----------------------------------------
      
      public function enumerateStorageResources($desired_fields = array()) {
      
         $xml_feed = file_get_contents($this->_kit_storage_endpoint);
         
         $rdr = \xd_xml\xml2array($xml_feed);
         $rdr = $rdr['RDR_Resources']['RDR_Storage'];
         
         $response = array();
      
         $timestamp = date('Y-m-d');
         
         foreach ($rdr as $r) {
         
            $res = array(
            
               'timestamp' => $timestamp,
               'rdr_timestamp' => $this->_cleanTimestamp($r['attr']['Timestamp']),
               'resource_id' => $this->_getResourceID($r['ResourceID']['value']),
               'resource_name' => $r['ResourceID']['value']
                
            );
         
            $storage_resource = $r['StorageResource'];
            
            $fields = array();
            
            foreach ($desired_fields as $df) {
            
               $res[$df] = NULL;
            
            }
            
            foreach ($storage_resource as $cr => $varr) {
            
               if (in_array($cr, $desired_fields)) {
               
                  $res[$cr] = (count($varr) == 1) ? $varr['value'] : NULL;   
               
               }
            
            }//foreach
            
            $response[] = $res;
            
         }//foreach
         
         return $response;
      
      }//enumerateStorageResources
      
      // ----------------------------------------

      private function _determineTGConversionFactor($compute_resource) {
      
         $conversion_factor_set = NULL;
         $curr_date = date('Y-m-d');
            
         if (isset($compute_resource['ConversionFactor'])) {
         
            if (isset($compute_resource['ConversionFactor']['ConvertTo'])) {
            
               $compute_resource['ConversionFactor'] = array($compute_resource['ConversionFactor']);

            }

            foreach ($compute_resource['ConversionFactor'] as $cf) {
               
               if (strtolower($cf['ConvertTo']['value']) == 'teragrid') {
                  
                  $start_date = isset($cf['StartDate']['value']) ? $cf['StartDate']['value'] : '';
                  $end_date = isset($cf['EndDate']['value']) ? $cf['EndDate']['value'] : '';  
         
                  if (empty($start_date) & empty($end_date)) {
                     $conversion_factor_set = $cf['Factor']['value'];
                  }
                  elseif (!empty($start_date) & empty($end_date)) {
                     $conversion_factor_set = $cf['Factor']['value'];
                  }
                  elseif (!empty($start_date) & !empty($end_date)) {
                  
                     if ($curr_date >= $start_date && $curr_date <= $end_date) {
                        $conversion_factor_set = $cf['Factor']['value'];
                     }
                     else {
                        $conversion_factor_set = NULL;
                     }
                     
                  }
                  else {
                     $conversion_factor_set = NULL;
                  }
                  
               }
                  
            }//foreach
 
         }//if (isset($compute_resource['ConversionFactor']))
   
         return $conversion_factor_set;
         
      }//_determineTGConversionFactor
      
      // ----------------------------------------
            
      private function _cleanTimestamp($timestamp) {

         $timestamp = str_replace('T', ' ', $timestamp);
         $timestamp = str_replace('Z', '', $timestamp);

         return $timestamp;
      
      }//_cleanTimestamp
            
      // ----------------------------------------

      private function _getResourceID($name) {

         // Alternative resource names (there is a definite mismatch between the names in acct.resources and the RDR)
         
         //           NAME_IN_RDR                             NAME_IN_XDCDB 
         //                                                   (namely acct.resources, in XDMoD --> modw.resourcefact)
         if ($name == 'gordonio.sdsc.teragrid.org')   $name = 'gordon-ion.sdsc.teragrid';
         //if ($name == 'keeneland.gatech.xsede.org') $name = 'keeneland.nics.teragrid';
         if ($name == 'avid32.iu.teragrid.org')       $name = 'avidd-ia32.iu.teragrid';
         if ($name == 'data.psc.xsede.org')           $name = 'supercell.psc.xsede';
         if ($name == 'xwfs.tacc.xsede.org')          $name = 'xwfs.xsede';
         if ($name == 'albedo.psc.xsede.org')         $name = 'albedo.psc.teragrid';
         if ($name == 'oasis-dm.sdsc.xsede.org')      $name = 'oasis.sdsc.xsede';
            
         $name = str_replace('teragrid.org', 'teragrid', $name);
         $name = str_replace('xsede.org', 'xsede', $name);
         
         if (isset($this->_resource_map[$name]))
            return $this->_resource_map[$name];
         else
            return -1;
      
      }//_getResourceID

      // ----------------------------------------
                     
      private function _getResourceStatus(&$kit) {
   
         $curr_date = date('Y-m-d');
         $statuses = isset($kit['Resource']['ResourceStatus']) ? $kit['Resource']['ResourceStatus'] : array();
         $status = "Unknown";
      
         foreach($statuses as $rstat) {
         
            $start_date = isset($rstat['StartDate']['value']) ? $rstat['StartDate']['value'] : '';
            $end_date = isset($rstat['EndDate']['value']) ? $rstat['EndDate']['value'] : '';   
            
            if (!empty($start_date)) {
         
               if (!empty($end_date)) {
         
                  // If values exist for both $start_date and $end_date, see if the current date
                  // falls between them.
                  
                  if ($curr_date >= $start_date && $curr_date <= $end_date) {
         
                     $status = $rstat['ResourceStatusType']['value'];
         
                  }
         
               }
               else {
            
                  // A $start_date has been specified, but an $end_date has not.
                  // Check if the current date is ahead of the $start_date
                  
                  if ($curr_date >= $start_date) {
         
                     $status = $rstat['ResourceStatusType']['value'];
         
                  }  
         
               }
         
            }
            
            // ------------------------------
            
            // Edge case:  If the current date exceeds the (existing) $end_date of 
            // the resource status of 'Decommissioned', then the resource is considered
            // Decommissioned.
            
            if ($rstat['ResourceStatusType']['value'] == 'Decommissioned') {
            
               if (!empty($end_date)) {
            
                  if ($curr_date >= $end_date) {
                  
                     $status = "Decommissioned";
                  
                  }
            
               }
            
            }
         
         }//foreach
                  
         return $status;
           
      }//_getResourceStatus
      
   }//RDRClient
      
?>