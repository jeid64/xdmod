<?php

use CCR\DB;


function fugangHere($queryFields, $baseQuery)
{
    $user = \xd_security\getLoggedInUser(); 
	
	$limit = getLimit();
	$offset = getOffset();
	$sortInfo = getSortInfo();
	if(count($sortInfo) == 1)
	{ 
		$sortInfo = $sortInfo[0];
	}	
	
    $fields = array();
	$count = -1;
	$records = array();
	$columns = array();	
	$subnotes = array();
	
	foreach($queryFields as $queryFieldName => $queryField)
	{
		$fields[] = array('name' => $queryFieldName, 'type' => $queryField['type']);
		$columns[] = array_merge($queryField['column'], array('dataIndex' => $queryFieldName));
	}
	
	$query = str_replace('__fields__', implode(', ',array_keys($queryFields)),$baseQuery)
			.(count($sortInfo) == 2? " order by {$sortInfo['column_name']} {$sortInfo['direction']} ": '') 
			.' limit '.$limit.' offset '.$offset;

	$countQuery = str_replace('__fields__', 'count(*) as total' ,$baseQuery);
		
	$dbh = DB::factory('database');
	
	$records = $dbh->query($query);
	$countResults = $dbh->query($countQuery);

	$count = $countResults[0]['total'];

	$returnData = array
	(
		"metaData" => array("totalProperty" => "total", 
							'messageProperty' => 'message',
							"root" => "records",
							"id" => "id",
							"fields" => $fields,
							"sortInfo" => (count($sortInfo) == 2? array('field' => $sortInfo['column_name'],'direction' => $sortInfo['direction']): array())
							),
		"success" => true,
		"total" => $count,
		"records" => $records,
		"columns" => $columns//,
		//'message' => $query
	);
	
	return $returnData;
}