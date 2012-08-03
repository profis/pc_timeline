<?php
//register this plugin to use the "pc_timeline" tree renderer
$plugins->Register_renderer('tree', function($id, $plugin) {
	$plugin = 'pc_timeline';
	global $plugins;
	$renderer = $plugins->Get_renderer($plugin, 'tree');
	if ($renderer) return $renderer($id, $plugin);
});