<?php
final class PC_controller_pc_timeline extends PC_controller {
	static $previousParentController = null;
	static $previousParentId = null;
	static $previousDate = null;
	static $previousPath = null;
	static $previousName = null;
	static $previouslyVisible = false;
	static $indexControllers = "pc_timeline";

	public function Process($data) {
		$this->Render();
	}
	
	static function rebuildIndex(&$index, $idp = 0, $base_controller = null, $base_id = null, $level = 0) {
		global $core;
		if( is_string(self::$indexControllers) )
			self::$indexControllers = array_flip(array_map("trim", explode(",", self::$indexControllers)));

		$r = $core->db->prepare("SELECT id, site, controller, date FROM {$core->db_prefix}pages WHERE idp=? AND deleted=0 AND front=0 AND (published=1 OR controller='menu')");
		if( !$r->execute(Array($idp)) )
			return;
		while($p = $r->fetch()) {
			if( $base_controller != null && $p["date"] ) {
				if( is_numeric($p["date"]) ) $p["date"] = date("Y-m-d", $p["date"]);
				if( !isset($index[$base_controller]) ) $index[$base_controller] = Array();
				if( !isset($index[$base_controller][$base_id]) ) $index[$base_controller][$base_id] = Array();
				if( !isset($index[$base_controller][$base_id][$p["date"]]) ) $index[$base_controller][$base_id][$p["date"]] = 0;
				$index[$base_controller][$base_id][$p["date"]]++;
				
				if( $base_controller == "gov39_photo" || $base_controller == "gov39_video" ) {
					$bc = $base_controller . "_all";
					if( !isset($index[$bc]) ) $index[$bc] = Array();
					if( !isset($index[$bc][$p["site"]-1]) ) $index[$bc][$p["site"]-1] = Array();
					if( !isset($index[$bc][$p["site"]-1][$p["date"]]) ) $index[$bc][$p["site"]-1][$p["date"]] = 0;
					$index[$bc][$p["site"]-1][$p["date"]]++;
				}
				if( isset($_REQUEST["calendar_rebuild"]) ) {
					echo "<li><strong>" . str_repeat("-", $level) . " $p[site]: $p[id], $p[controller], $p[date] -> $base_controller:$base_id</strong></li>";
				}
			}
			else {
				if( isset($_REQUEST["calendar_rebuild"]) ) {
					echo "<li>" . str_repeat("-", $level) . " $p[site]: $p[id], $p[controller], $p[date]</li>";
				}
			}
			if( isset(self::$indexControllers[$p["controller"]]) )
				self::rebuildIndex($index, $p["id"], $p["controller"], $p["id"], $level + 1);
			else
				self::rebuildIndex($index, $p["id"], $base_controller, $base_id, $level + 1);
		}
	}
	
	static function fullRebuildIndex() {
		global $core;
		
		$core->db->query("DELETE FROM {$core->db_prefix}plugin_timeline_index");
		$core->db->query("DELETE FROM {$core->db_prefix}plugin_timeline_days");
			
		$index = Array();
		self::rebuildIndex($index);
		print_pre($index);
		foreach($index as $ctrl => $cindex) {
			foreach($cindex as $id => $iindex) {
				$arr = Array();
				foreach($iindex as $date => $count) {
					$year = substr($date, 0, 4);
					$month = intval(ltrim(substr($date, 5, 2), "0")) - 1;
					$day = intval(ltrim(substr($date, 8, 2), "0")) - 1;
					if( !isset($arr[$year]) )
						$arr[$year] = Array(0,0,0,0,0,0,0,0,0,0,0,0); // bit representation of all enabled days in year
					$arr[$year][$month] |= 1 << $day; // enable bit for the day corresponding to the fetched date
					$ir = $core->db->prepare("INSERT INTO {$core->db_prefix}plugin_timeline_index (controller, cpid, date, counter) VALUES (?,?,?,?)");
					$ir->execute(Array($ctrl, intval($id), $date, $count));
				}
				echo "<li>$ctrl:$id</li>";
				print_pre($arr);
				$ir = $core->db->prepare("INSERT INTO {$core->db_prefix}plugin_timeline_days (controller, cpid, days) VALUES (?,?,?)");
				$ir->execute(Array($ctrl, intval($id), json_encode($arr)));
				print_pre($core->db->errorInfo());
			}
		}
	}
	
	static function getParentInfo($p) {
		global $core;
		if( is_string(self::$indexControllers) )
			self::$indexControllers = array_flip(array_map("trim", explode(",", self::$indexControllers)));
		$r = $core->db->prepare("SELECT id, idp, controller FROM {$core->db_prefix}pages WHERE id=? AND published=1");
		$path = Array();
		while( !empty($p) && $p["idp"]>0 ) {
			if( !$r->execute(Array($p["idp"])) )
				break;
			$p = $r->fetch();
			if( isset(self::$indexControllers[$p["controller"]]) )
				return Array($p, array_reverse($path));
			$path[] = $p;
		}
		return null;
	}
	
	static function countDay($controller, $parentId, $date, $count ) {
		global $core;
		$r = $core->db->prepare("INSERT INTO {$core->db_prefix}plugin_timeline_index (controller, cpid, date, counter) VALUES (?,?,?,0)");
		$core->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
		$r->execute(Array($controller, $parentId, $date));
		$core->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		
		$r = $core->db->prepare("UPDATE {$core->db_prefix}plugin_timeline_index SET counter=counter+? WHERE controller=? AND cpid=? AND date=?");
		$r->execute(Array($count, $controller, $parentId, $date));
	}
	
	static function recacheIndex($controller, $parentId) {
		global $core;
		$core->db->query("DELETE FROM {$core->db_prefix}plugin_timeline_index WHERE counter<=0");
		$r = $core->db->prepare("SELECT date FROM {$core->db_prefix}plugin_timeline_index WHERE controller=? AND cpid=?");
		if( !$r->execute(Array($controller, $parentId)) )
			return;
		$arr = Array();
		while( $f = $r->fetch() ) {
			$year = substr($f["date"], 0, 4);
			$month = intval(ltrim(substr($f["date"], 5, 2), "0")) - 1;
			$day = intval(ltrim(substr($f["date"], 8, 2), "0")) - 1;
			if( !isset($arr[$year]) )
				$arr[$year] = Array(0,0,0,0,0,0,0,0,0,0,0,0); // bit representation of all enabled days in year
			$arr[$year][$month] |= 1 << $day; // enable bit for the day corresponding to the fetched date
		}
		
		$r = $core->db->prepare("INSERT INTO {$core->db_prefix}plugin_timeline_days (controller, cpid, days) VALUES (?,?,'{}')");
		$core->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
		$r->execute(Array($controller, $parentId));
		$core->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

		$r = $core->db->prepare("UPDATE {$core->db_prefix}plugin_timeline_days SET days=? WHERE controller=? AND cpid=?");
		$r->execute(Array(json_encode($arr), $controller, $parentId));
	}
	
	static function onBeforePageSave($params) {
		global $core;
		$r = $core->db->prepare("SELECT idp, date, published FROM {$core->db_prefix}pages WHERE id=?");
		if( !$r->execute(Array($params["changes"]["id"])) ) return;
		$p = $r->fetch();
		if( empty($p) ) return;
		self::$previouslyVisible = ($p["published"]);

		$parent = self::getParentInfo($p);
		if( $parent == null ) return; // no parent controllers that should be indexed
		if( is_numeric($p["date"]) ) $p["date"] = date("Y-m-d", $p["date"]);
		
		self::$previousDate = $p["date"];
		self::$previousParentController = $parent[0]["controller"];
		self::$previousParentId = $parent[0]["id"];
		self::$previousPath = $parent[1];
	}
	
	static function onAfterPageSave($params) {
		global $core;
		if( self::$previousParentController == null ) return; // page is not in some indexable controller
		
		$r = $core->db->prepare("SELECT idp, date, published FROM {$core->db_prefix}pages WHERE id=?");
		if( !$r->execute(Array($params["changes"]["id"])) ) return;
		$p = $r->fetch();
		if( empty($p) ) return;
		$visible = ($p["published"]);
		if( !$visible && !self::$previouslyVisible ) return; // no need to do any changes with invisible pages
		if( is_numeric($p["date"]) ) $p["date"] = date("Y-m-d", $p["date"]);

		$inc = $dec = null;
		if( self::$previouslyVisible && !$visible ) {
			// article becomes invisible
			$dec = self::$previousDate;
		}
		else if( !self::$previouslyVisible && $visible ) {
			// article becomes visible
			$inc = $p["date"];
		}
		else if( self::$previousDate != $p["date"] ) {
			// article changes it's date
			$dec = self::$previousDate;
			$inc = $p["date"];
		}
		if( $inc != $dec ) {
			if( $dec ) {
				self::countDay(self::$previousParentController, self::$previousParentId, $dec, -1);
				if( self::$previousParentController == "gov39_photo" || self::$previousParentController == "gov39_video" )
					self::countDay(self::$previousParentController . "_all", 0, $dec, -1);
			}
			if( $inc ) {
				self::countDay(self::$previousParentController, self::$previousParentId, $inc, 1);
				if( self::$previousParentController == "gov39_photo" || self::$previousParentController == "gov39_video" )
					self::countDay(self::$previousParentController . "_all", 0, $inc, 1);
			}
			self::recacheIndex(self::$previousParentController, self::$previousParentId);
			if( self::$previousParentController == "gov39_photo" || self::$previousParentController == "gov39_video" )
				self::recacheIndex(self::$previousParentController . "_all", 0);
		}
	}
	
	static function onMovePage($params) {
		global $core;
		if( $params["from_idp"] == $params["to_idp"] ) return; // not moved to other parent
		
		$r = $core->db->prepare("SELECT date, published FROM {$core->db_prefix}pages WHERE id=?");
		if( !$r->execute(Array($params["id"])) ) return;
		$p = $r->fetch();
		if( empty($p) ) return;
		if( !$p["published"] ) return; // invisible not indexed, so ignore it
		if( !$p["date"] ) return; // no date - no index
		if( is_numeric($p["date"]) ) $p["date"] = date("Y-m-d", $p["date"]);

		$parent_from = self::getParentInfo(Array("idp" => $params["from_idp"]));
		$parent_to = self::getParentInfo(Array("idp" => $params["to_idp"]));

		$p1 = $parent_from ? v($parent_from[0]["id"], null) : null;
		$p2 = $parent_to ? v($parent_to[0]["id"], null) : null;
		if( $p1 == $p2 ) // moved, but base parent having controller left the same
			return;
		if( $p1 ) {
			self::countDay($parent_from[0]["controller"], $parent_from[0]["id"], $p["date"], -1);
			self::recacheIndex($parent_from[0]["controller"], $parent_from[0]["id"]);
			if( $parent_from[0]["controller"] != $parent_to[0]["controller"] ) {
				if( $parent_from[0]["controller"] == "gov39_video" || $parent_from[0]["controller"] == "gov39_photo" ) {
					self::countDay($parent_from[0]["controller"] . "_all", 0, $p["date"], -1);
					self::recacheIndex($parent_from[0]["controller"] . "_all", 0);
				}
			}
		}
		if( $p2 ) {
			self::countDay($parent_to[0]["controller"], $parent_to[0]["id"], $p["date"], 1);
			self::recacheIndex($parent_to[0]["controller"], $parent_to[0]["id"]);
			if( $parent_from[0]["controller"] != $parent_to[0]["controller"] ) {
				if( $parent_to[0]["controller"] == "gov39_video" || $parent_to[0]["controller"] == "gov39_photo" ) {
					self::countDay($parent_to[0]["controller"] . "_all", 0, $p["date"], 1);
					self::recacheIndex($parent_to[0]["controller"] . "_all", 0);
				}
			}
		}
	}
}
