<?php
@require_once('common.php');
$returnData = array();

try
{

	$user = \xd_security\getLoggedInUser();
	
	$active_role = getActiveRole();
	
	$role_data = explode(':', $active_role);
	$role_data = array_pad($role_data, 2, NULL);
	$activeRole = $user->assumeActiveRole($role_data[0], $role_data[1]);
	$user->setCachedActiveRole($activeRole);
	
	$role_parameters = $activeRole->getParameters();
	
	$dimension_id = $_REQUEST['dimension_id'];
	
	$realm = getRealm();
	
	$query_classname =  '\\DataWarehouse\\Query\\'.$realm.'\\Aggregate';
	
	$group_by = $query_classname::getGroupBy($dimension_id);
	
	$limit = getLimit();
	$offset = getOffset();
	
	$searchText = getSearchText();
	$selectedFilterIds = getSelectedFilterIds();
	
	$totalCount = count($group_by->getPossibleValues($searchText,NULL,NULL,$role_parameters));
	$values = $group_by->getPossibleValues($searchText, $limit, $offset,$role_parameters);
	foreach($values as $value)
	{
		$returnData[] = array(  
								'name' =>  $value['long_name'],
								'id' => $value['id'],
								'checked' => in_array($value['id'],$selectedFilterIds)
							);
	}
	$returnData = array('totalCount'=> $totalCount, 'data' => $returnData);

}
catch(Exception $ex)
{
	print_r($ex);
	$returnData = array();
}

xd_controller\returnJSON($returnData);
?>