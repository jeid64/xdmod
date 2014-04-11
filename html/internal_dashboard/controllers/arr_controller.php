<?php

   require_once dirname(__FILE__).'/../../../configuration/linker.php';

   use CCR\DB;

   @session_start();

   $response = array();

   $operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : '';

   if ($operation == 'logout') {

         unset($_SESSION['xdDashboardUser']);
         $response['success'] = true;

         if (isset($_REQUEST['splash_redirect'])) {
            print "<html><head><script language='JavaScript'>top.location.href='../index.php';</script></head></html>";
         }
         else {
            echo json_encode($response);
         }

         exit;
   }


   xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), 'xdDashboardUser');

   // =====================================================

   $pdo = DB::factory('database');
   $arr_db = DB::factory('arrdb');
   // =====================================================
   function getNodeIDbyName($nodename)
   {
      $arr_db = DB::factory('inca');
      $sql = "SELECT node_id FROM mod_arr.nodes WHERE name='$nodename'";
      $sqlres=$arr_db->query($sql);
      if(count($sqlres)==0)throw new Exception('No node found with such name');
      return (int)($sqlres[0]["node_id"]);
   }
   switch($operation) {
      case 'get_resources':
         $sql = 'SELECT resource_id as id,nickname as resource FROM mod_arr.resource
ORDER BY resource_id ASC';
         $sqlres=$arr_db->query($sql);
         $response['success'] = true;
         $response['response'] = $sqlres;
         $response['count'] = count($response['response']);
         print json_encode($response);
         break;
      case 'get_ak_success_rates':
         //get request
         $start_date = $_REQUEST['start_date'];
         $end_date = $_REQUEST['end_date'];
         
         $resources = explode(';', strtolower($_REQUEST['resources']));
         $resourceSelected='';
         if(count($resources)==1)$resourceSelected="AND resource='$resources[0]'";
         $appKers = explode(';', strtolower($_REQUEST['appKers']));
         $problemSizes = explode(';', strtolower($_REQUEST['problemSizes']));
         foreach ($problemSizes as $key => $var) {
            $problemSizes[$key] = (int)$var;
         }
         
         $showAppKer=($_REQUEST['showAppKer']==='true')?true:false;
         $showAppKerTotal=($_REQUEST['showAppKerTotal']==='true')?true:false;
         $showResourceTotal=($_REQUEST['showResourceTotal']==='true')?true:false;
         $showUnsuccessfulTasksDetails=($_REQUEST['showUnsuccessfulTasksDetails']==='true')?true:false;
         $showSuccessfulTasksDetails=($_REQUEST['showSuccessfulTasksDetails']==='true')?true:false;
         $internalFailureTasksFilter=($_REQUEST['showInternalFailureTasks']==='true')?'':'AND internal_failure=0';
         
         try{
         
         $nodeSelected='';
         if(array_key_exists('node',$_REQUEST))
         {
            $nodeName=$_REQUEST['node'];
            $nodeID=getNodeIDbyName($_REQUEST['node']);
            $nodeSelected="AND nodes LIKE '%;$nodeName;%'";
         };
         
         //Init Temporary Array
         $results=array();
         foreach($resources as $resource){
            $results[$resource]=array();
            foreach($appKers as $appKer){
               $results[$resource][$appKer]=array();
            }
         }
         $extraFilters="$resourceSelected $internalFailureTasksFilter $nodeSelected";
         //Count successfull Tasks
         $sql = "
         SELECT resource,reporter,reporternickname,COUNT(*) as total_tasks,AVG(status) as success_rate
         FROM mod_arr.arr_xdmod_instanceinfo
         WHERE '$start_date' <=collected AND  collected < '$end_date' AND status=1
         $extraFilters
         GROUP BY resource,reporternickname ORDER BY resource,reporternickname ASC;";
         $sqlres=$arr_db->query($sql);
         
         foreach($sqlres as $rownum =>$row)
         {
            $resource=$row['resource'];
            $appKer=$row['reporter'];
            
            $problemSize=explode('.', $row['reporternickname']);
            $problemSize=(int)$problemSize[count($problemSize)-1];
            
            if(!array_key_exists($resource,$results))continue;
            if(!array_key_exists($appKer,$results[$resource]))continue;
            
            if(!array_key_exists($problemSize,$results[$resource][$appKer]))
               $results[$resource][$appKer][$problemSize]=array(
                        "succ"=>0,
                        "unsucc"=>0,
                        );
            $results[$resource][$appKer][$problemSize]["succ"]=(int)$row['total_tasks'];
            
         }
         //Count unsuccessfull Tasks
         $sql = "
         SELECT resource,reporter,reporternickname,COUNT(*) as total_tasks,AVG(status) as success_rate
         FROM mod_arr.arr_xdmod_instanceinfo
         WHERE '$start_date' <=collected AND  collected < '$end_date' AND status=0 
         $extraFilters
         GROUP BY resource,reporternickname ORDER BY resource,reporternickname ASC;";
         $sqlres=$arr_db->query($sql);
         
         foreach($sqlres as $rownum =>$row){
            $resource=$row['resource'];
            $appKer=$row['reporter'];
            
            $problemSize=explode('.', $row['reporternickname']);
            $problemSize=(int)$problemSize[count($problemSize)-1];
            
            if(!array_key_exists($resource,$results))continue;
            if(!array_key_exists($appKer,$results[$resource]))continue;
            
            if(!array_key_exists($problemSize,$results[$resource][$appKer]))
               $results[$resource][$appKer][$problemSize]=array(
                     "succ"=>0,
                     "unsucc"=>0,
               );
            $results[$resource][$appKer][$problemSize]["unsucc"]=(int)$row['total_tasks'];
            //print "\tproblemSize:".$problemSize."\n";
            
         }
         //Merge results to respond
         $results2=array();
         foreach($results as $resource =>$row1)
         {
            $unsuccRes=0;
            $succRes=0;
            foreach($row1 as $appKer =>$row2)
            {
               $unsucc=0;
               $succ=0;
               $resultsTMP=array();
               foreach($row2 as $problemSize =>$row)
               {
                  if(!in_array($problemSize,$problemSizes))continue;
                  
                  if($showAppKer)
                  {
                     
                     $unsuccessfull_tasks='';
                     if(!($showUnsuccessfulTasksDetails||$showSuccessfulTasksDetails))
                        $unsuccessfull_tasks='Select "Show Details of Unsuccessful Tasks"
or "Show Details of Successful Tasks" options to see details on tasks';
                     
                     if($showUnsuccessfulTasksDetails){
                        if((int)$row["unsucc"]>0)
                        {
                           $sql = "
                           SELECT instance_id
                           FROM mod_arr.arr_xdmod_instanceinfo
                           WHERE '$start_date' <=collected AND  collected < '$end_date'
                           AND status=0 AND resource='$resource'
                           AND reporternickname='$appKer.$problemSize' $extraFilters
                           ORDER BY collected DESC;";
                           $sqlres=$arr_db->query($sql);
                           $unsuccessfull_tasks=$unsuccessfull_tasks.'Tasks finished unsuccessfully:<br/>';
                           $icount=1;
                           foreach($sqlres as $row2){
                              $task_id=$row2['instance_id'];
                              $unsuccessfull_tasks=$unsuccessfull_tasks.
                              "<a href=\"#\" onclick=\"javascript:new XDMoD.AppKernel.InstanceWindow({instanceId:$task_id});\">#$task_id</a> ";
                              if($icount%10==0)$unsuccessfull_tasks=$unsuccessfull_tasks.'<br/>';
                              $icount+=1;
                           }
                           $unsuccessfull_tasks=$unsuccessfull_tasks.'<br/>';
                           //var_dump($sqlres);
                        }
                        else
                           $unsuccessfull_tasks=$unsuccessfull_tasks.'There is no unsuccessful runs.<br/>';
                     }
                     if($showSuccessfulTasksDetails){
                        if((int)$row["succ"]>0)
                        {
                           $sql = "
                           SELECT instance_id
                           FROM mod_arr.arr_xdmod_instanceinfo
                           WHERE '$start_date' <=collected AND  collected < '$end_date'
                           AND status=1 AND resource='$resource'
                           AND reporternickname='$appKer.$problemSize' $extraFilters
                           ORDER BY collected DESC;";
                           $sqlres=$arr_db->query($sql);
                           $unsuccessfull_tasks=$unsuccessfull_tasks.'Tasks finished successfully:<br/>';
                           $icount=1;
                           foreach($sqlres as $row2){
                              $task_id=$row2['instance_id'];
                              $unsuccessfull_tasks=$unsuccessfull_tasks.
                              "<a href=\"#\" onclick=\"javascript:new XDMoD.AppKernel.InstanceWindow({instanceId:$task_id});\">#$task_id</a> ";
                              if($icount%10==0)$unsuccessfull_tasks=$unsuccessfull_tasks.'<br/>';
                              $icount+=1;
                           }
                        }
                        else
                           $unsuccessfull_tasks=$unsuccessfull_tasks.'There is no successful runs.<br/>';
                     }
                     $resultsTMP[$problemSize]=array(
                           "resource" => $resource,
                           "appKer" => $appKer,
                           "problemSize" => (string)$problemSize,
                           "successfull"=>(int)$row["succ"],
                           "unsuccessfull"=>(int)$row["unsucc"],
                           "total"=>(int)$row["succ"]+(int)$row["unsucc"],
                           "successfull_percent"=>100.0*(float)$row["succ"]/(float)($row["succ"]+$row["unsucc"]),
                           "unsuccessfull_tasks"=>$unsuccessfull_tasks
                     );
                  }
                  $unsucc+=$row["unsucc"];
                  $succ+=$row["succ"];
               }

               //var_dump($problemSizes);

               //var_dump($resultsTMP);
               
               foreach($problemSizes as $problemSize){
                  if(array_key_exists($problemSize,$resultsTMP))
                     $results2[]=$resultsTMP[$problemSize];
               }
               
               if($succ+$unsucc>0){
                  if($showAppKerTotal){
                  $successfull_percent=100.0*(float)$succ/(float)($succ+$unsucc);
                  $unsuccessfull_tasks='Tasks details are showed only for individual problem sizes';
                  $results2[]=array(
                        "resource" => $resource,
                        "appKer" => $appKer,
                        "problemSize" => "Total",
                        "successfull"=> $succ,
                        "unsuccessfull"=>$unsucc,
                        "total"=>$succ+$unsucc,
                        "successfull_percent"=>$successfull_percent,
                        "unsuccessfull_tasks"=>$unsuccessfull_tasks
                  );
               }}
               $unsuccRes+=$unsucc;
               $succRes+=$succ;
               
            }
            if($succRes+$unsuccRes>0){
               if($showResourceTotal){
               $successfull_percent=100.0*(float)$succRes/(float)($succRes+$unsuccRes);
               $unsuccessfull_tasks='Tasks details are showed only for individual problem sizes';
               $results2[]=array(
                        "resource" => $resource,
                        "appKer" => "Total",
                        "problemSize" => "Total",
                        "successfull"=> $succRes,
                        "unsuccessfull"=>$unsuccRes,
                        "total"=>$succRes+$unsuccRes,
                        "successfull_percent"=>$successfull_percent,
                        "unsuccessfull_tasks"=>$unsuccessfull_tasks
               );
            }}
         }
         
         $response['success'] = true;
         $response['response'] = $results2;
         $response['count'] = count($response['response']);
         }catch (Exception $e) {
            $response['success'] = true;
            $response['response'] = array();
            $response['count'] = count($response['response']);
         }
         
         if($_REQUEST['format']==='csv'){
            $filename='data.csv';
            $inline = false;
            $format='csv';
            
            $exportData=array();
            $exportData['title']=array('title'=>'App Kernels Success Rates');
            $exportData['duration']=array('from:'=>$start_date,'to'=>$end_date);
            $exportData['headers']=array_keys($results2[0]);
            
            #$tmpdata=array();
            #foreach($results2 as $raw => $params)
            
            $exportData['rows']=$results2;
            \DataWarehouse\ExportBuilder::export(array($exportData),$format, $inline, $filename);
         }
         else{
            print json_encode($response);
         }
      
         break;
      case 'get_ak_stats_over_nodes':
         $start_date = $_REQUEST['start_date'];
         $end_date = $_REQUEST['end_date'];
         $resource_id = $_REQUEST['resource'];
         
         $results=array();
         try{
            if($resource_id==='')throw new Exception('No resource selected');
            
            $resource_id=(int)$resource_id;
            
            
            $sql = "SELECT n.name as node,ns.successful as successful,ns.total as total
FROM (SELECT node_id,count(status) as successful,COUNT(*) as total
    FROM mod_arr.ak_on_nodes
    WHERE '$start_date' <=collected AND  collected < '$end_date'
          AND resource_id=$resource_id
    GROUP BY node_id) AS ns,
    mod_arr.nodes AS n
WHERE n.node_id=ns.node_id
ORDER BY n.name ASC";
            $sqlres=$arr_db->query($sql);
            foreach($sqlres as $row){
                $succRes=(int)$row['successful'];
                $unsuccRes=(int)$row['total']-(int)$row['successful'];
                $successful_percent=100.0*(float)$succRes/(float)($succRes+$unsuccRes);
                $results[]=array(
                        "node" => $row['node'],
                        "unsuccessful"=>$unsuccRes,
                        "successful"=> $succRes,
                        "total"=>$succRes+$unsuccRes,
                        "successful_percent"=>$successful_percent);
            }
         }
         catch (Exception $e) {
            $results=array();
         }
         //Format the responce
         $response['success'] = true;
         $response['response'] = $results;
         $response['count'] = count($response['response']);
         
         if($_REQUEST['format']==='csv'){
            $filename='StatsOverNodes.csv';
            $inline = false;
            $format='csv';
            
            $exportData=array();
            $exportData['title']=array('title'=>'Statistics over Nodes');
            $exportData['duration']=array('from:'=>$start_date,'to'=>$end_date);
            $exportData['headers']=array_keys($results[0]);
            
            $exportData['rows']=$results;
            \DataWarehouse\ExportBuilder::export(array($exportData),$format, $inline, $filename);
         }
         else{
            print json_encode($response);
         }
         break;
      default:

         $response['success'] = false;
         $response['message'] = 'operation not recognized';
         print json_encode($response);
         break;

   }//switch

   // =====================================================

   

?>
