<?php

namespace ReportTemplates;

class MonthlyComplianceReport extends \ReportTemplates\aReportTemplate
{
   
   public function buildReportFromTemplate(array &$additional_params = array()) {
            
      $rm = new \XDReportManager($this->_user);

      $report_name = $rm->generateUniqueName('Monthly Compliance Report');

      $report_id = $this->_user->getUserID()."-".time();
      
      $rm->configureSelectedReport(
   
         $report_id,
         $report_name,
         'Monthly Compliance Report',
         '',  //header
         '',  //footer
         $this->_report_skeleton['general']['font'],
         $this->_report_skeleton['general']['format'],
         $this->_report_skeleton['general']['charts_per_page'],
         $this->_report_skeleton['general']['schedule'],
         $this->_report_skeleton['general']['delivery']
      
      );
           
      $rm->insertThisReport("Monthly Compliance Report");
      
   }//buildReportFromTemplate
      
}//MonthlyComplianceReport

?>