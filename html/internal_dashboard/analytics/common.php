<?php
  
   function printHeader($username, $prefix = '') {
   
      list($group, $disp_username) = explode(';', $username);
               
      $suffix = ''; $color = '#999';
         
      if ($group == 1) { $color = '#709dc9'; }
      if ($group == 2) { $suffix = '(XSEDE)'; $color = '#709dc9'; }

      if (!empty($prefix))   
         print "<b style='font-size: 18px'>$prefix <span style='color: $color'>$disp_username $suffix</span></b>";
      else
         print "<b style='font-size: 18px; color: $color'>$disp_username $suffix</b>";
         
   }//printHeader

   // -------------------------------------------------------
      
   function createHeader($title, $details) {
      
      print "<b style='font-size: 20px'>$title</b><br />$details<br /><br />";
      
   }//createHeader
   
   // -------------------------------------------------------
   
   function renderAsTable(&$records, $header_opts = array()) {
   
      if (count($records) == 0) {
      
         print "Table is empty (no records)";
         return;
         
      }
   
      $column_headers = array();
      $column_widths = array();
      
      foreach ($records[0] as $k => $v) {
      
         $column_headers[] = (isset($header_opts[$k]) && isset($header_opts[$k]['label'])) ? $header_opts[$k]['label'] : $k;
         $column_widths[] = (isset($header_opts[$k]) && isset($header_opts[$k]['width'])) ? 'width='.$header_opts[$k]['width'] : '';
         
      }//foreach
      
      print '<table border=0 width=100% cellspacing=0>';
      
      print '<tr bgcolor="eeeeff">';
      
         for ($c = 0; $c < count($records[0]); $c++) {
            print "<td {$column_widths[$c]}><b>{$column_headers[$c]}</b></td>";
         }
      
      print '</tr>';
      
      $recordIndex = 0;
      
      foreach ($records as $r) {

         $bgColor = ($recordIndex++ % 2) ? 'eeffee' : 'ffffff';

         $k = array_keys($r);
         $r = array_values($r);

         print "<tr bgcolor='#$bgColor'><td valign=top>";
         print implode("</td><td valign=top>", $r);
         print '</td></tr>';

      }//foreach
       
      print '</td></tr>';
      print '</table>';
      
   }//renderAsTable

?>