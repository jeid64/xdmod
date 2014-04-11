<?php

namespace ReportTemplates;

use CCR\DB;

class SPQuarterlyReport extends \ReportTemplates\aReportTemplate
{
   
   public function buildReportFromTemplate(array &$additional_params = array()) {

      // - Read in Service Provider ID ($additional_params['resource_provider'])
      // - Enumerate all resources belonging to that SP
      // - For each resource, construct a report...
            
      $allowed_organizations = array_merge(
         $this->_user->getOrganizationCollection(ROLE_ID_CENTER_STAFF),
         $this->_user->getOrganizationCollection(ROLE_ID_CENTER_DIRECTOR)
      );
      
      $service_provider_id = isset($additional_params['resource_provider']) ? $additional_params['resource_provider'] : -1;
      
      if (!in_array($service_provider_id, $allowed_organizations)) {
         throw new \Exception("This report template 'SP Quarterly Report' requires that the user is affiliated with the service provider in question.");  
      }
      
      $resourceEnumQuery = "select id, code, 
                            case when end_date is null or date(now()) between start_date and end_date then 1
                            else 0
                            end as in_service
                            from modw.resourcefact where organization_id=$service_provider_id";
      
      $pdo = DB::factory('database');
      
      $sp_resources = $pdo->query($resourceEnumQuery);      

      // =========================
      
      $sp_result = $pdo->query("SELECT short_name FROM modw.serviceprovider WHERE organization_id=$service_provider_id");   
      
      $service_provider_abbrev = (count($sp_result) == 1) ? $sp_result[0]['short_name'] : 'Unknown';
      
      // =========================
      
      foreach ($sp_resources as $resource) {
      
         $map = array(
            'ABS_ROLE_ID' => ROLE_ID_CENTER_DIRECTOR.':'.$service_provider_id,
            'SP' => $service_provider_abbrev,
            'SP_ID' => $service_provider_id,
            'RESOURCE' => $resource['code'],
            'RES_ID' => $resource['id']
         );
         
         $report_instance = $this->_createReportFromMap($map);
      
      }//foreach
      
   }//buildReportFromTemplate
   
   // ------------------------------------------------------
   
   private function _generateID() {

      usleep(1000);
      
      list($usec, $sec) = explode(" ", microtime());
      return ((float)$usec + (float)$sec);
      
   }//_generateID

   // ------------------------------------------------------
   
   private function _createReportFromMap($map) {
   
      $patterns = array();
      $replacements = array();
      
      foreach ($map as $k => $v) {
      
         $patterns[] = "/\[:$k:\]/";
         $replacements[] = $v;
      
      }//foreach

      $report_name = $this->_report_skeleton['general']['name'].' ('.$map['RESOURCE'].')';
      
          
      $report_title = preg_replace($patterns, $replacements, $this->_report_skeleton['general']['title']);
      $report_header = preg_replace($patterns, $replacements, $this->_report_skeleton['general']['header']);
      $report_footer = preg_replace($patterns, $replacements, $this->_report_skeleton['general']['footer']);

      // ==================
      
      $rm = new \XDReportManager($this->_user);

      $report_name = $rm->generateUniqueName($report_name);

      $report_id = $this->_user->getUserID()."-".$this->_generateID();
      
      $rm->configureSelectedReport(
   
         $report_id,
         $report_name,
         $report_title,
         $report_header,
         $report_footer,
         $this->_report_skeleton['general']['font'],
         $this->_report_skeleton['general']['format'],
         $this->_report_skeleton['general']['charts_per_page'],
         $this->_report_skeleton['general']['schedule'],
         $this->_report_skeleton['general']['delivery']
      
      );
           
      $rm->insertThisReport("SP Quarterly Report");

      foreach ($this->_report_skeleton['charts'] as $chartEntry) {
         
         $rm->saveCharttoReport(
         
            $report_id, 
            preg_replace($patterns, $replacements, $chartEntry['chart_id']), 
            preg_replace($patterns, $replacements, $chartEntry['chart_title']),  
            preg_replace($patterns, $replacements, $chartEntry['chart_drill_details']),  
            $chartEntry['chart_date_description'], 
            $chartEntry['ordering'], 
            $chartEntry['timeframe_type'], 
            'image'
            
         ); 
      
      }//foreach
      
   }//_createReportFromMap
      
}//SPQuarterlyReport

?>
