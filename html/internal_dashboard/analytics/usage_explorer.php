<html>

   <head>

      <style type="text/css">
      
         body, table {
            font-family: arial;
            font-size: 14px;
         }
         
         .user_section {
            font-size: 18px;
            color: #888;
         }
      
      </style>
         
   </head>
   
   <body>
                  
<?php

   use CCR\DB;
      
   require_once dirname(__FILE__).'/../../../configuration/linker.php';
   require_once 'common.php';

   print '<span style="float: right; text-align: right; color: #888">Viewing data for <b>'.$_SERVER['SERVER_NAME']."</b><br />[<a href=\"usage_explorer.php\">Reload</a>]".'</span>';
   
   createHeader("Usage Explorer Analytics", "");
   
   $pdo = DB::factory('database');
   
   $u_results = $pdo->query('SELECT COUNT(u.id) as ut_count, u.user_type, ut.type FROM moddb.Users AS u, moddb.UserTypes AS ut WHERE u.user_type = ut.id OR u.user_type = 700 GROUP BY u.user_type');
   
   $stats_by_user_type = array();
   
   foreach ($u_results as $ur) {

      $stats_by_user_type[$ur['user_type']] = array(
         'user_type' => ($ur['user_type'] == 700) ? 'XSEDE' : $ur['type'],
         'num_users' => $ur['ut_count'],
         'have_profiles' => 0
      );

   }//foreach

   $results = $pdo->query('SELECT u.id, u.username, u.user_type FROM moddb.Users AS u WHERE u.id IN (SELECT user_id FROM moddb.UserProfiles)');

   $global_metrics = array();

   $final_metrics = array();

   $common_metrics = array();

   $ue_dates = array();

   $agg = array();
   $metric_counts = array();
        
print <<<EOF

<style type="text/css">

   body {
      margin: 10px !important;
   }
   
</style>

<script language="JavaScript">

   function displayCommonMetrics() {
   
      document.getElementById('ue_comparison_tracking').style.display = 'none';
      document.getElementById('ue_common_metrics').style.display = '';
      
   }//displayCommonMetrics
   
   function displayComparisonTracking() {
   
      document.getElementById('ue_common_metrics').style.display = 'none';
      document.getElementById('ue_comparison_tracking').style.display = '';
      
   }//displayComparisonTracking

</script>

<table border=0 width=565 bgcolor='#eeeeff'><tr>
   <td width=60>Display:</td>
   <td><a href='javascript:void(0)' onClick='displayCommonMetrics()'>Common Metrics</a> | <a href='javascript:void(0)' onClick='displayComparisonTracking()'>Comparison Tracking</a></td>
</tr></table>

<br />

EOF;

   print "<div id='ue_comparison_tracking' style='display: none'>";

   createHeader("Comparison Tracking", "Tracks comparisons users are making using the Usage Explorer");
   
   foreach ($results as $r) {

      $user = XDUser::getUserByID($r['id']);
      $prof = $user->getProfile();

      $queries = $prof->fetchValue('queries');
      $queries = json_decode($queries, true);

      $metrics = array();
      $g = array();
      $query_count = 0;

      $user_info = ($user->isXSEDEUser() === true) ? 'XSEDE' : $r['username'];
      $formal_name = $user->getFormalName()." ($user_info)";
      
      print '<b class="user_section">'.$formal_name."</b><br /><br />\n";
          
      foreach ($queries as $q => $d) {

         $p_data = json_decode($d['config'], true);

         $compared_metrics = array();
         
         foreach ($p_data['data_series']['data'] as $data_set) {

            $abs_metric = $data_set['realm'].' --> '.$data_set['metric'];
            $compared_metrics[] = $abs_metric;
            
            if (!isset($agg[$abs_metric])) $agg[$abs_metric] = array();
            if (!isset($agg[$abs_metric][$formal_name])) $agg[$abs_metric][$formal_name] = 1;
            
            //agg[$abs_metric]['__count__'] = (isset($agg[$abs_metric]['__count__'])) ? count($agg[$abs_metric]) - 1 : 1;
            $metric_counts[$abs_metric] = count($agg[$abs_metric]);

         }//foreach (data set in query...)

         if (count($compared_metrics) > 0) {

            $query_count++;

            $g[$d['ts']] = array(
               'timestamp' => date('Y-m-d, g:i:s A', $d['ts']),
               'compared_metrics' => implode('<br />', $compared_metrics)
            );
         }

      }//foreach ($queries...)
            
      krsort($g);
      renderAsTable(array_values($g), array(
         'timestamp' => array('width' => 200),
         'compared_metrics' => array('label' => 'compared metrics')
      ));
      
      print "<br />";
      
   }//foreach ($results as $r)
     
   print "</div><div id='ue_common_metrics'>";
   
   createHeader("Common Metrics", "Displays how common metrics are among distinct users (sorted by descending frequency)");
   
   array_multisort($metric_counts, SORT_DESC);
   
   $final = array();
   
   foreach ($metric_counts as $m => $c) {
      $final[] = array('metric' => $m, 'frequency' => $c);
   }//foreach
   
   renderAsTable($final, array(
      'metric' => array('width' => 500),
      'frequency' => array('label' => 'users with this metric<br />in a saved query')
   ));
   
   //\xd_debug\dumpArray($metric_counts);

   print "</div>";

?>

   </body>
   
</html>