<?php
function pc_timeline_renderer($id, $plugin) {
	$date = null;
	$additional = v($_POST['additional'], null);
	if (!empty($additional)) {
		$additional = json_decode($additional, true);
		$date = v($additional['pc_timeline']['date'], null);
	}
	$id = v($_POST['node']);
	$site_id = v($_POST['site']);
	if (empty($date)) {
		$r = Get_tree_childs($id, $site_id, null, null, null);
	}
	else {
		$r = Get_tree_childs($id, $site_id, null, null, $date);
	}
	$out = (is_array($r)?$r:array());
	return $out;
};
$plugins->Register_renderer('tree', 'pc_timeline_renderer');