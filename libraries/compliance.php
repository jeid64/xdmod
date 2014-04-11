<?php

	namespace xd_compliance;

   // -----------------------------------------------

   /*
      @function measureTextWidth
      
      Returns a numeric value appropriate for the width of an ExtJS GridPanel column.
      Used in conjunction with the metaData for the grid so the columns can be resized
      dynamically and automatically.
      
   */
   	
   function measureTextWidth($str) {
   
      $str = trim($str);
      
      $totLength = 0;
      
      for ($j = 0; $j < mb_strlen($str); $j++) {
         
         switch(mb_substr($str, $j, 1)) {
         
            case 'I': $totLength += 4; break;
            case '-': $totLength += 4; break;
            default:  $totLength += 9; break;
                     
         }//switch
         
      }//for
      
      return $totLength;
      
   }//measureTextWidth

   // -----------------------------------------------
   
   function createSectionBreak(&$comp, $text) {
   
      return array(
         'requirement' => $text,
         'metricType' => '',
         'data' => $comp->generateEmptySet(),
         'section_break' => true
      );
      
   }//createSectionBreak
   
   // -----------------------------------------------
   
   function generateShorthandValue($num) {
   
      $si_prefix = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z');
      $base = 1000;
      $class = min((int)log($num, $base), count($si_prefix) - 1);
   
      return sprintf('%1.2f', $num / pow($base, $class)) . $si_prefix[$class];
   
   }//generateShorthandValue
   
?>