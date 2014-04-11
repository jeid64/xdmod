<?php

require_once( dirname(__FILE__).'/../common_params.php');
require_once( dirname(__FILE__).'/common.php');


$returnData = array();

try
{
	$queryFields = 
	array(
		'org_name'  => array('type' => 'string', 'column' => array('header' => 'Organization', 'sortable' => true, 'editable' => false, 'align' => 'left', 'xtype' => 'gridcolumn', 'width' => 200)),
		//'npubs_xd' => array('type' => 'int', 'column' => array('header' => '# of Pubs', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		'npubs5_xd' => array('type' => 'int', 'column' => array('header' => '# of Pubs', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		//'ncites_xd' => array('type' => 'int', 'column' => array('header' => 'Cited by', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		'ncites5_xd' => array('type' => 'int', 'column' => array('header' => 'Cited by', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		//'hindex_xd' => array('type' => 'float', 'column' => array('header' => 'H-index', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 85)),
		'hindex5_xd' => array('type' => 'float', 'column' => array('header' => 'H-index', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		//'hindexm_xd' => array('type' => 'float', 'column' => array('header' => 'H-index (m)', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		//'gindex_xd' => array('type' => 'float', 'column' => array('header' => 'G-index', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		'gindex5_xd'  => array('type' => 'float', 'column' => array('header' => 'G-index', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		//'i10index_xd' => array('type' => 'float', 'column' => array('header' => 'i10-index', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75)),
		'i10index5_xd' => array('type' => 'float', 'column' => array('header' => 'i10-index', 'sortable' => true, 'editable' => false, 'align' => 'right', 'xtype' => 'gridcolumn', 'width' => 75))
	);
	$searchText = getSearchText();
	if($searchText !== NULL)
	{
		$firstKey = array_keys($queryFields);
		$firstKey = $firstKey[0];
		$searchClause = " and $firstKey like '%$searchText%'";
	}
	$baseQuery = 
	'select 
		__fields__
	from
		xdmodpub2.xdmoddw_si_facts
			left join
		xdmodpub2.xdorgs ON xdmodpub2.xdmoddw_si_facts.org_id = xdorgs.org_id
	where
		person_id is NULL and project_number is NULL and tas_pub_id is NULL '.(isset($searchClause)? $searchClause  : '');
			
	$returnData = fugangHere($queryFields, $baseQuery);

}
catch(Exception $ex)
{
	$returnData = array(
			'total' => 0, 
			'message' => $ex->getMessage(), 
			'data' => array(),
			'success' => false);
}

xd_controller\returnJSON($returnData);
?>