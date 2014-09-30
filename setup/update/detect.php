<?php
/**
 * This script tries to detect current version of the plugin's database part.
 * 
 * @var array $cfg
 * @var PC_updater $this
 * @var PC_database $db
 * @var PC_core $core
 */

if( $db->getTableInfo('plugin_timeline_index') )
	return '1.3.0';

if( $core->plugins->Is_active('pc_timeline') )
	return '1.0.0'; // plugin is active, but database does not contain the table of the new version.

return null;