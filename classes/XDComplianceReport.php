<?php

require_once ('Zend/Pdf.php');

class XDComplianceReport {

   private $_verticalIndex = 670;
   
   private $_pdf;
   private $_activePage;
   
   private $_COLOR_BLACK, $_COLOR_RED, $_COLOR_GREEN, $_COLOR_BLUE;
   private $_COLOR_RESOURCE_SPECS, $_COLOR_TABLE_HEADER_BKGR, $_COLOR_TABLE_HEADER_OUTLINE;
   private $_COLOR_HRULE, $_PROPOSED_REQUIREMENTS_STRIPING_COLOR;
   
   private $_fillColor;
   private $_strokeColor;
   
   // --------------------------------------------  
   
   public function __construct() {
   
      $this->_COLOR_BLACK = new Zend_Pdf_Color_Rgb(0, 0, 0);
      $this->_COLOR_RED = new Zend_Pdf_Color_Rgb(1, 0, 0);
      $this->_COLOR_GREEN = new Zend_Pdf_Color_Rgb(0, 0.5, 0);
      $this->_COLOR_BLUE = new Zend_Pdf_Color_Rgb(0, 0, 1);
      
      $this->_COLOR_RESOURCE_SPECS = new Zend_Pdf_Color_Rgb(0.6, 0.6, 0.6);
      
      $this->_COLOR_TABLE_HEADER_BKGR = new Zend_Pdf_Color_GrayScale(0.95);  
      $this->_COLOR_TABLE_HEADER_OUTLINE = new Zend_Pdf_Color_GrayScale(1);
      
      // Color used for horizontal rules (header, footer)
      $this->_COLOR_HRULE = new Zend_Pdf_Color_GrayScale(0.8);

      $this->_PROPOSED_REQUIREMENTS_STRIPING_COLOR = new Zend_Pdf_Color_GrayScale(0.95);

      $this->_fillColor = new Zend_Pdf_Color_Rgb(0.6, 0.6, 0.6);
      $this->_strokeColor = new Zend_Pdf_Color_Rgb(0.6, 0.6, 0.6);
               
   }//__construct

   // --------------------------------------------   

   // Accessor / Mutator functions (intended to augment the capabilities of ZendPDF)
   
   private function _setFillColor($color) {
   
      $this->_fillColor = $color;
      $this->_activePage->setFillColor($color);
      
   }//_setFillColor
   
   private function _getFillColor() {
   
      return $this->_fillColor;
      
   }//_getFillColor

   private function _setStrokeColor($color) {
   
      $this->_strokeColor = $color;
      $this->_activePage->setLineColor($color); 
      
   }//_setStrokeColor
   
   private function _getStrokeColor() {
   
      return $this->_strokeColor;
      
   }//_getStrokeColor
   
   private function _setFontSize($size) {
   
      $this->_activePage->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $size); 
   
   }//_setFontSize
         
   // --------------------------------------------      

   /*   
      @function generate
      Constructs the report in PDF format, based on the availability of data in the array to be
      supplied as the second parameter.
   */
      
   public function generate($timeframe, &$data = array()) {
   
      $content = $data['content'];
      $proposed = $data['proposed'];
      
      //$content = array();
      //$proposed = array();
      
      $this->_pdf = new Zend_Pdf();
      
      $this->_activePage = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_LETTER);
      $this->_pdf->pages[] = $this->_activePage;

      $this->_createHeader($timeframe);
    
      $message = 'The following active resources are not fully compliant with respect to the timeframe noted above.';
      
      $messageColor = $this->_COLOR_RED;
      
      $mailMessage = "With respect to the previous month, $timeframe,\n".
                     count($content)." active XSEDE resources WERE NOT fully compliant.\n\n".
                     "Please review the attached report for more information.\n\n";
      
      $posX = 50;
      
      if (count($content) == 0) {
      
         $message = 'All active resources are fully compliant with respect to the timeframe noted above.';
         
         $messageColor = $this->_COLOR_GREEN;
         
         $mailMessage = "With respect to the previous month, $timeframe,\n".
                        "all active XSEDE resources WERE fully compliant.\n\n";
                        
         if (count($proposed) > 0) {

            $mailMessage .= "Please review the attached report for proposed requirements.\n\n";         

         }     
                        
         $posX = 88;
         
      }
      
      $this->_activePage->setFillColor($messageColor);
      
      $this->_activePage->drawText($message, $posX, 720);         
      
      $this->_activePage->setFillColor($this->_COLOR_BLACK);


      if (count($content) == 0 && count($proposed) > 0) {
         $this->_activePage->drawText("Refer to the next page for proposed requirements.", 172, 680);   
      }
         
               
      foreach ($content as $resource => $data) {
      
         $this->_generateTable($resource, $data);
         
      }//foreach

      $this->_createProposedRequirementsSection($proposed);

      $this->_setStrokeColor($this->_COLOR_HRULE); 
      $this->_setFillColor($this->_COLOR_BLACK);
      $this->_setFontSize(12);
         
      // Page numbering ==============================
                       
      $totalPages = count($this->_pdf->pages);
      
      for($i = 0; $i < $totalPages; $i++) {
      
         $p = $this->_pdf->pages[$i];
                  
         $p->drawLine(50, 35, 560, 35); 
      
         $p->drawText('Page '.($i + 1).' of '.$totalPages, 500, 20); 
         
      }//for

      // Source Definitions ==========================
      
      $first_page = $this->_pdf->pages[0];
      $first_page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 8); 
      $first_page->drawText('RDR: Resource Description Repository', 50, 25);  
      $first_page->drawText('XDcDB: XSEDE Central Database', 50, 14);
 
      // =============================================
      
      $template_path = tempnam('/tmp', 'monthly_compliance_report-');
      
      exec("rm $template_path");
      exec("mkdir $template_path");
      exec("chmod 777 $template_path");
      
      $output_file = basename($template_path).'.pdf';
      
      $this->_pdf->save("$template_path/$output_file");

      return array(
         'template_path' => $template_path, 
         'report_file' => "$template_path/$output_file", 
         'custom_message' => $mailMessage,
         'failed_compliance' => count($content),
         'proposed_requirements' => count($proposed)
      );
   
   }//generate
      
   // --------------------------------------------      

   /*   
      @function prepareComplianceData
      Prepares a filtered view of the compliance data, aggregated by resource, which
      only accounts for requirements that are not being fully supplied for the previous month
   */
   
   public function prepareComplianceData() {

      $data = Compliance::generateSnapshot();
            
      $start_date = $data['timeframes']['month']['start_date'];
      $end_date = $data['timeframes']['month']['end_date'];
      
      $content = array();
      $order = array();
      
      $proposedData = array();
      
      foreach ($data['data'] as $d) {
      
         if (!empty($d['requirement']) && $d['section_break'] === false) {
         
            $requirement = $d['requirement'];
            
            $source = 'XDcDB';
            
            if ($d['metric_type'] == 'resource') $source = 'RDR';

            if ($d['is_requested'] == true) {
            
               $proposedData[] = array(
                  'requirement' => $d['requirement'],
                  'details' => $d['tooltip']
               );

            }
                        
            foreach ($d as $k => $v) {
            
               if (!in_array($k, array('requirement', 'tooltip', 'section_break', 'is_requested'))) {
               
                  if (is_numeric($v['m_value']) && $v['m_value'] < 100) {
                     
                        if (!isset($content[$k])){
                        
                           $content[$k] = array();
                           $content[$k]['__details__'] = $this->_getMetaParamValue($data, $k, 'status');
                        
                           $order[$this->_getMetaParamValue($data, $k, 'numeric_rank')] = $k;
                        
                        }
                     
                        $content[$k][$requirement] = array (
                        
                           'source' => $source,
                           'value' => $v['m_value']
                        
                        );
                        
                  }//if
               
               }//if
           
            }//foreach
   
         }
      
      }//foreach ($data as $d)
   
      ksort($order);
      
      $sortedContent = array();
      
      foreach ($order as $res) {
         $sortedContent[$res] = $content[$res];
      }
      
      return array('start_date' => $start_date, 'end_date' => $end_date, 'content' => $sortedContent, 'proposed' => $proposedData);
      
   }//prepareComplianceData      

   // --------------------------------------------
   
   /*   
   
      @function _getMetaParamValue
      Consults the metaData section of the $data array, and returns a value pertaining to the corresponding
      resource.
      
      e.g.   
      
         ['metaData'] => array(
         
            0 => array(
               'name' => 'resource_x',
               'param_1' => 'value_1',
               'param_2' => 'value_2'
            ),
            
            1 => array(
               'name' => 'resource_y',
               'param_3' => 'value_3',
               'param_4' => 'value_4'
            )
         
         )
         
         $result = getMetaParamValue($data, 'resource_y', 'param_3');
         
         The value of $result will be 'value_3'
      
   */
      
   private function _getMetaParamValue(&$data, $resource, $param) {
   
      foreach ($data['metaData']['fields'] as $d) {
      
         if ($d['name'] == $resource) {
            return (isset($d[$param])) ? $d[$param] : 'nope';
         }
         
      }//foreach
   
   }//_getMetaParamValue
            
   // --------------------------------------------   
   
   private function _createHeader($timeframe) {
   
      $this->_setFontSize(20);
      $this->_activePage->drawText('Compliance Report', 50, 750); 
      
      $this->_setFontSize(12);
      $this->_activePage->drawText($timeframe, 420, 750); 
      
      $this->_activePage->setLineColor($this->_COLOR_HRULE); 
      $this->_activePage->drawLine(50, 742, 560, 742); 
      
   }//_createHeader

   // --------------------------------------------
   
   private function _determinePageBreak($cy, $y, $bound, $top = 750) {
      
      if ($cy < $bound) {

         // Reset the styling for the page so footer looks consistent when
         // pagination occurs.
         
         $this->_setStrokeColor($this->_COLOR_HRULE); 
         $this->_setFillColor($this->_COLOR_BLACK);
         $this->_setFontSize(12);
            
         // The content to follow is likely to be clipped if kept on the same (current) page.
         // Create a new page and reset the vertical index to the top of the new page (defined via $top).
         
         $this->_activePage = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_LETTER);
         $this->_pdf->pages[] = $this->_activePage;
              
         $this->_setFontSize(12);
              
         return $top;
         
      }//if ($cy <= 100)
      
      return $y;
      
   }//_determinePageBreak

   // --------------------------------------------

   private function _createResourceHeader($label, $subheader, $columns, $y) {
      
      $this->_setFontSize(15);
      $this->_activePage->setFillColor($this->_COLOR_BLUE);
      $this->_activePage->drawText($label, $columns[0], $y); 

      $this->_setFontSize(10);
      $this->_activePage->setFillColor($this->_COLOR_RESOURCE_SPECS);
      $this->_activePage->drawText($subheader, $columns[0], $y - 14); 
      
      $this->_activePage->setFillColor($this->_COLOR_BLACK);
      
   }//_createResourceHeader
         
   // --------------------------------------------
      
   private function _createRectangle($config = array()) {
      
      $cachedFillColor = $this->_getFillColor();
      $cachedStrokeColor = $this->_getStrokeColor();
      
      $this->_setFillColor($config['fill']);
      $this->_setStrokeColor($config['stroke']);
      
      $this->_activePage->drawRectangle($config['coord_a'][0], $config['coord_a'][1], $config['coord_b'][0], $config['coord_b'][1]);    
      
      $this->_setFillColor($cachedFillColor);
      $this->_setStrokeColor($cachedStrokeColor);
            
   }//_createRectangle
    
   // --------------------------------------------
       
   private function _createTableHeader($columns, $y) {

      $this->_setFontSize(12);
      $this->_setFillColor($this->_COLOR_BLACK);
      
      $this->_createRectangle(array(
         'fill' => $this->_COLOR_TABLE_HEADER_BKGR,
         'stroke' => $this->_COLOR_TABLE_HEADER_OUTLINE,
         'coord_a' => array(50, $y + 12),
         'coord_b' => array(560, $y - 3)
      ));
      
      // Table Block Header
      $this->_activePage->drawText('Metric', $columns[0], $y); 
      $this->_activePage->drawText('% Compliancy', $columns[1], $y); 
      $this->_activePage->drawText('Source', $columns[2], $y); 
      
   }//_createTableHeader

   // --------------------------------------------

   private function _createProposedRequirementsSection(&$proposed = array()) {
      
      if (count($proposed) > 0) {
      
         $leftMargin = 45;
         
         $this->_activePage = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_LETTER);
         $this->_setFontSize(12);
         $this->_pdf->pages[] = $this->_activePage;
         
         // Header (top 2) lines =============================
         
         $startY = 750;
         
         $this->_activePage->drawText('The following requirements have been proposed by the XSEDE Technology Audit Services team:', 45, $startY);  
 
         $startY -= 15;
 
         $this->_activePage->setFillColor($this->_COLOR_RESOURCE_SPECS);
         $this->_setFontSize(9);
         $this->_activePage->drawText('No resources are currently providing this information, yet we believe having these specifications at our disposal will be helpful.', 52, $startY);  
                  
         // Start proposed requirements listing ==============         
                  
         $this->_activePage->setFillColor($this->_COLOR_BLACK);
                  
         $startY -= 40;
         
         $rowIndex = 0;
         
         /*
         // Testing the page overflow logic
            
         for ($i = 1; $i <= 70; $i++) {
         
            $proposed[] = array(
            
               'requirement' => 'Dummy proposed requirement '.$i,
               'details' => 'Dummy details for requirement '.$i
               
            );
            
         }//for
         */
         
         foreach ($proposed as $data) {
         
            $startY = $this->_determinePageBreak($startY, $startY, 80, 740);
         
            // Row striping for proposed requirements listing
            
            if ($rowIndex++ % 2 == 0) {
 
               $this->_createRectangle(array(
                 'fill' => $this->_PROPOSED_REQUIREMENTS_STRIPING_COLOR,
                 'stroke' => $this->_PROPOSED_REQUIREMENTS_STRIPING_COLOR,
                 'coord_a' => array(40, $startY + 15),
                 'coord_b' => array(560, $startY - 20)
               ));
               
            }
               
            $this->_setFillColor($this->_COLOR_BLACK);
            $this->_setFontSize(14);
            $this->_activePage->drawText($data['requirement'], $leftMargin, $startY);  
          
            $startY -= 16;
          
            if (!empty($data['details'])) {

               $this->_setFillColor($this->_COLOR_RESOURCE_SPECS);
               $this->_setFontSize(10);
               $this->_activePage->drawText($data['details'], $leftMargin, $startY);  
            
            }
            
            $startY -= 20;
                   
         }//foreach
         
      }//if (count($proposed) > 0)
      
   }//_createProposedRequirementsSection
      
   // --------------------------------------------
               
   private function _generateTable($header, $data = array()) {

      $startY = $this->_verticalIndex;

      $columns = array(50, 310, 460);
      
      $startY = $this->_determinePageBreak($startY, $startY, 80);
      $this->_createResourceHeader($header, $data['__details__'], $columns, $startY);
      unset($data['__details__']);

      $startY -= 35; //header to table spacing

      $startY = $this->_determinePageBreak($startY, $startY, 60);
      $this->_createTableHeader($columns, $startY);
               
      $startY -= 10; //table header to content spacing
      
      $i = 1;
      
      foreach($data as $requirement => $details) {
      
         $source = $details['source'];
         $ncValue = $details['value'];
      
         $adjustedY = $this->_determinePageBreak($startY - ($i * 15), $startY, 50);

         if ($adjustedY != $startY) {
            $i = 0;
            $startY = $adjustedY;
         }
         
         $this->_activePage->drawText($requirement, $columns[0], $startY - ($i * 15)); 
         
         $textColor = ($ncValue == 0) ? $this->_COLOR_RED : $this->_COLOR_BLACK;
         $this->_activePage->setFillColor($textColor);
         
         $this->_activePage->drawText($ncValue, $columns[1], $startY - ($i * 15)); 
         
         // Reset color to black
         $this->_activePage->setFillColor($this->_COLOR_BLACK);
         $this->_activePage->drawText($source, $columns[2], $startY - ($i * 15)); 
      
         $this->_verticalIndex = $startY - ($i * 15);
         
         $i++;
         
      }//foreach($data ...)
      
      $this->_verticalIndex -= 40; //Table post-gap 
      
   }//_generateTable
   
}//XDComplianceReport
   
?>