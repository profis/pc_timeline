<?php
# ProfisCMS - Opensource Content Management System Copyright (C) 2011 JSC "ProfIS"
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http:#www.gnu.org/licenses/>.
global $plugin_url;
$plugin_url = $this->core->Get_url('plugins', '', $this->currently_parsing);
function pc_site_icons_replace_cb($params) {
	global $plugin_url;
	switch ($params[2]) {
		case 'doc': case 'docx': $i = 'word.png'; break;
		case 'xls': case 'xlsx': $i = 'excel.png'; break;
		case 'pdf': $i = 'pdf.png'; break;
		case 'zip': case 'rar': case '7z': $i = 'archive.png'; break;
		default: return $params[1];
	}
	$icon =& $i;
	return '<img class="pc_icon" src="'.$plugin_url.'icons/'.$icon.'" alt="" />'.$params[1];
}
function pc_site_icons_hook($params) {
	$text =& $params['text'];
	$text = preg_replace_callback("#(<a href=\"[^\"]+?\.(docx?|xlsx?|pdf|zip|rar|7z)\")#", "pc_site_icons_replace_cb", $text);
}
$this->core->Register_hook("parse_html_output", "pc_site_icons_hook");
unset($plugin_url);