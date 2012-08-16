<?php
function pc_timeline_renderer($params) {
	$date = null;
	$additional = v($params['additional']['pc_timeline'], null);
	$date = v($additional['date'], null);
	$id = v($params['id']);
	$site_id = v($_POST['site']);
	if (empty($date)) {
		$list = Get_tree_childs($id, $site_id, false, null, null);
	}
	else {
		$list = Get_tree_childs($id, $site_id, false, null, $date);
	}
	$params['data'] = (is_array($list)?$list:array());
	return true;
};
//$plugins->Register_renderer('tree', 'pc_timeline_renderer');
$core->Register_hook('core/tree/get-childs/pc_timeline', 'pc_timeline_renderer');