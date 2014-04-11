<?php

   namespace xd_versioning;

   function getPortalVersion($short = false) {
   
      // Acquire the version information if possible ...
      $revision = exec('git log -1 --pretty=format:"%h"');
      
      $version = \xd_utilities\getConfiguration('general', 'version');
       
      if ($short == true) {
      
         $ver = explode(' (', $version);
         
         return $ver[0];
         
      }
      
      if (!empty($revision)) {
         
         // This is a developmental version (since the git meta-data (in the .git directory) is intact)
         $version .= ".$revision (".date("Y.m.d").") Dev";
         
      }
      
      return $version;
      
   }//getPortalVersion   

         

?>
