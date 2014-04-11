<?php

   /*
      XDMoD Portal Front Controller
      The Center For Computational Research, University At Buffalo
   */
 
   @session_start();
   
   // Fix to the 'trailing slash' issue -------------------------------
   
   // Get URL ------------
   
   $port = ($_SERVER['SERVER_PORT'] >= 9000) ? ':'.$_SERVER['SERVER_PORT'] : '';
   $proto = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
   
   $url = $proto . '://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];

   // --------------------   
   
   if (preg_match('/index.php(\/+)/i', $url)) {
    
      $properURI = preg_replace('/index.php(\/+)/i', 'index.php', $url);
      
      header("Location: $properURI");
      exit;
      
   }
   
   // -----------------------------------------------------------------

   $include_file = (isset($_SESSION['xdUser'])) ? 'entry_point.php' : 'entry_point_splash.php';

   include $include_file;

?>