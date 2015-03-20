<?php
/** ProfisCMS - Opensource Content Management System Copyright (C) 2011 JSC "ProfIS"
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache');
//
$out = array();
switch (v($routes->Get(1))) {
	case 'get-calendar-enabled-dates':
		$ctrl = v($_POST['controller']);
		$pid = v($_POST['pid']);
		$query = "SELECT * FROM {$cfg['db']['prefix']}plugin_timeline_index WHERE controller=? and cpid=?";
		$r = $db->prepare($query);
		$query_params = array($ctrl, $pid);
		$s = $r->execute($query_params);
		if ($s) $out = array(
			'success'=> true,
			'data'=> $r->fetchAll()
		);
		break;
		
	case 'calendar_rebuild':
		PC_controller_pc_timeline::fullRebuildIndex();
		break;
		
	default: $out['error'] = 'Invalid action';
}
echo json_encode($out);