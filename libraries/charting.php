<?php

   namespace xd_charting;
   
   // --------------------------------
   
   /*
   @function getChartFromURI
   @param $uri (e.g. chart_type=....&resource_id=....&fos=....)
   @param XDUser $user
   */
   
   function getChartFromURI($uri, $user) {

      // In cases where this function is called as the result of parsing an XML document (e.g. chart definition),
      // special characters in the URI are escaped ...

      $uri = str_replace('&amp;', '&', $uri);   // &amp; is used for overcoming validation problems in browsers
      $uri = str_replace('%20', ' ', $uri);     // Unicode character for space (MySQL ends up replacing spaces with %20 when 
                                                // the URI is inserted into the database.
      
      $request = \User\Elements\RequestDescripter::fromString($uri);      
            
      $queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromRequest($request, $user);
      
      $charts = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationsFromQueries($queries, $request, $user);
      
      // ==================================
      
      // Todo: The code below will need to be updated to not use a session-based mechanism.
      
      $url_elements = \User\Elements\RequestDescripter::fromString($charts[0]['chart_url']);
      
      return $_SESSION[$url_elements['img']];

   }//getChartFromURI
   
   // --------------------------------
   
   /*
   * @function convertPNGStreamToEPSDownload
   *
   * Takes a png-formatted image stream and transforms it into an EPS-formatted stream presented
   * as a downloadable attachment
   *
   * @param $png_stream (binary content which comprises a PNG-formatted image
   *
   */   

   function convertPNGStreamToEPSDownload($png_stream, $eps_file_name = 'xdmod_chart') {
      
      // EPS filenames have an inherit limit of 76 characters
      $eps_file_name = substr($eps_file_name, 0, 76).'.eps';

      $png_stream_file = tempnam("/tmp", "png_stream_saved");
      $eps_file = tempnam("/tmp", "generated_eps.eps");
   
      $handle = fopen($png_stream_file, "w");
      fwrite($handle, $png_stream);
      fclose($handle);
   
      $im = new \Imagick($png_stream_file);
      
      $im->setImageFormat("eps");
      $im->writeImage($eps_file);
   
      unlink($png_stream_file);
      
      // fix for IE catching or PHP bug issue
      header("Pragma: public");
      header("Expires: 0"); // set expiration time
      header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
      // browser must download file from server instead of cache
      
      // force download dialog
      header("Content-Type: application/force-download");
      header("Content-Type: application/octet-stream");
      header("Content-Type: application/download");
      
      header("Content-Disposition: attachment; filename=".$eps_file_name.";");
      
      header("Content-Transfer-Encoding: binary");
      header("Content-Length: ".filesize($eps_file));
      
      readfile($eps_file);
   
      unlink($eps_file);
      
   }//convertPNGStreamToEPS

   // --------------------------------------------------

   // @function processForReport
      
   function processForReport(&$highchart_config) {
   
      $data = json_encode($highchart_config);
      $highchart_config = json_decode($data, true);
      
      if ( isset($highchart_config['data']) && isset($highchart_config['data'][0]) ) {
         $highchart_config = $highchart_config['data'][0];
      }
   
      /*
      $highchart_config['chart']['width'] = 148;
      $highchart_config['chart']['height'] = 69;
      */
      
      //$highchart_config['legend']['enabled'] = false;
      
      $highchart_config['credits']['text'] = '';
      
      //$highchart_config['title']['text'] = '';
      //$highchart_config['subtitle']['text'] = '';
   
      //$highchart_config['xAxis']['title']['text'] = '';
      ///$highchart_config['xAxis']['labels']['enabled'] = false;
      //$highchart_config['xAxis']['gridLineColor'] = '#ffffff';
      //$highchart_config['xAxis']['tickColor'] = '#ffffff';
      //$highchart_config['xAxis']['lineColor'] = '#ffffff';
      
      //$highchart_config['yAxis']['title']['text'] = '';
      //$highchart_config['yAxis']['labels']['enabled'] = false;
      //$highchart_config['yAxis']['gridLineColor'] = '#ffffff';
      //$highchart_config['yAxis']['lineColor'] = '#ffffff';
      
      //$highchart_config['plotOptions']['series'] = array('marker' => array('enabled' => false));
      
   }//processForReport
      
   // --------------------------------------------------

   // @function processForThumbnail
      
   function processForThumbnail(&$highchart_config) {
   
      if ( isset($highchart_config['data']) && isset($highchart_config['data'][0]) ) {
         $highchart_config = $highchart_config['data'][0];
      }
   
      /*
      $highchart_config['chart']['width'] = 148;
      $highchart_config['chart']['height'] = 69;
      */
      
      $highchart_config['legend']['enabled'] = false;
      
      $highchart_config['credits']['text'] = '';
      
      $highchart_config['title']['text'] = '';
      $highchart_config['subtitle']['text'] = '';
   
      $highchart_config['xAxis']['title']['text'] = '';
      $highchart_config['xAxis']['labels']['enabled'] = false;
      //$highchart_config['xAxis']['gridLineColor'] = '#ffffff';
      //$highchart_config['xAxis']['tickColor'] = '#ffffff';
      //$highchart_config['xAxis']['lineColor'] = '#ffffff';
      
      $highchart_config['yAxis']['title']['text'] = '';
      $highchart_config['yAxis']['labels']['enabled'] = false;
      //$highchart_config['yAxis']['gridLineColor'] = '#ffffff';
      //$highchart_config['yAxis']['lineColor'] = '#ffffff';
      
      //$highchart_config['plotOptions']['series'] = array('marker' => array('enabled' => false));
      
   }//processForThumbnail

   // --------------------------------------------------

   // @function exportHighchart
         
   function exportHighchart($chartConfig, $width, $height, $scale, $format) {
      $effectiveWidth = (int)($width*$scale);
      $effectiveHeight = (int)($height*$scale);
   
      $base_filename = sys_get_temp_dir() . '/' . md5(rand() . microtime());

      // These files must have the proper extensions for PhantomJS.
      $output_image_filename = $base_filename . '.' . $format;
      $tmp_html_filename     = $base_filename . '.html';
   
      $html_dir = __DIR__ . "/../html";
      $template = file_get_contents($html_dir . "/highchart_template.html");
   
      $template = str_replace('_html_dir_', $html_dir, $template);
      $template = str_replace('_chartOptions_',json_encode($chartConfig), $template);
      $template = str_replace('_width_',$effectiveWidth, $template);
      $template = str_replace('_height_',$effectiveHeight, $template);
      file_put_contents($tmp_html_filename,$template);
   
   
         
      if($effectiveWidth <  \ChartFactory::$thumbnail_width)
      {
        //$effectiveHeight = (int)($effectiveHeight * 600/$effectiveWidth);
        //$effectiveWidth = 600;
      }
         
      if ($format == 'png') {
      
         \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js png $tmp_html_filename $output_image_filename $effectiveWidth $effectiveHeight");
      
         $data = file_get_contents($output_image_filename);
   
         @unlink($output_image_filename);
         @unlink($tmp_html_filename);
      
         return $data;
      
      }
      
      if ($format == 'svg') {
      
        // $effectiveWidth = 1660;
        // $effectiveHeight = 1245;
         
         $svgContent = \xd_phantomjs\phantomExecute(dirname(__FILE__)."/phantomjs/generate_highchart.js svg $tmp_html_filename null ".$effectiveWidth." ".$effectiveHeight);
      
         @unlink($tmp_html_filename);
      
         return $svgContent;
         
      }
      	      
   }//exportHighchart

