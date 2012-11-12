<?php

function pc_timeline_install($controller) {
	global $core;
	//create database table
	$sql_files = array(
		'mysql'=> 'setup/mysql.sql',
	);
	$driver = $core->sql_parser->Get_default_driver();
	if (isset($sql_files[$driver])) {
		$sql = file_get_contents($core->plugins->Get_plugin_path('pc_timeline').$sql_files[$driver]);
		if ($sql) {
			$core->sql_parser->Replace_variables($sql);
			$queries = explode(';', $sql);
			foreach ($queries as $query) {
				if (!empty($query)) {
					$core->db->query($query);
				}
			}
		}
	}
	return true;
}