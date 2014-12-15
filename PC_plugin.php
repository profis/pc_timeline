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
		//echo $date;
		$list = Get_tree_childs($id, $site_id, false, null, $date);
		//echo '----------------';
		//print_r($list);
		//echo '================';
	}
	$params['data'] = (is_array($list)?$list:array());
	return true;
};
//$plugins->Register_renderer('tree', 'pc_timeline_renderer');
$core->Register_hook('core/tree/get-childs/pc_timeline', 'pc_timeline_renderer');

Register_class_autoloader('PC_controller_pc_timeline', dirname(__FILE__).'/PC_controller.php');

$this->core->Register_hook("before_page_save", "PC_controller_pc_timeline::onBeforePageSave");
$this->core->Register_hook("after_page_save", "PC_controller_pc_timeline::onAfterPageSave");
$this->core->Register_hook("move_page", "PC_controller_pc_timeline::onMovePage");


Register_class_autoloader('PC_controller_pc_timeline', dirname(__FILE__).'/PC_controller.php');
$this->core->Register_hook("rss.get_list", "PC_controller_pc_timeline::Get_rss_list");
