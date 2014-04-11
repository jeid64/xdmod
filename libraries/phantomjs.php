<?php

   namespace xd_phantomjs;

   function phantomExecute($command) {
   
      $phantomjs_path = \xd_utilities\getConfiguration('reporting', 'phantomjs_path');
      
      exec("DISPLAY=:99 $phantomjs_path $command 2>&1", $data, $ret_val);

      if ($ret_val == 0) {
      
         return implode('', $data);
         
      }
      else {
         $msg = "PhantomJS returned $ret_val, data: " . json_encode($data);
         throw new \Exception($msg);
      }

   }//phantomExecute
   
?>
