<?php
namespace Profis\Timeline;

use \Profis\Db\DbException;

class Timeline {
	protected $parentId;
	protected $textField;
	protected $count = null;
	protected $orderBy;

	/**
	 * @param int $parentId Id (pid) of the page that contains timeline handled pages.
	 * @param string $textField Name of the field that contains text.
	 */
	public function __construct($parentId, $textField = 'info', $orderBy = 'p.`date` DESC, p.id DESC') {
		$this->parentId = $parentId;
		$this->textField = $textField;
		$this->orderBy = $orderBy;
	}

	/**
	 * Counts number of sub pages that have non-empty name and text.
	 *
	 * @return int Number of pages found.
	 * @throws DbException
	 */
	public function getCount() {
		global $core;
		if( $this->count === null ) {
			$s = $core->db->prepare($q = "SELECT COUNT(*) FROM `{$core->db_prefix}pages` p INNER JOIN `{$core->db_prefix}content` c ON c.pid=p.id AND c.ln=:ln AND c.name!='' AND c.`{$this->textField}`!='' WHERE p.idp=:idp AND p.published=1 AND p.deleted=0");
			if( !$s->execute($p = array('idp' => $this->parentId, 'ln' => $core->site->ln)) )
				throw new DbException($s->errorInfo(), $q, $p);
			$total = $s->fetchColumn();
			$this->count = $total ? $total : 0;
		}
		return $this->count;
	}

	/**
	 * Gets sub pages that have non-empty name and text sorted by date in reverse order.
	 *
	 * @param int $limit Maximum number of pages to return.
	 * @param int $offset Index of the first page to return.
	 * @return array An array of pages.
	 * @throws DbException
	 */
	public function get($limit = 0, $offset = 0) {
		global $core;
		if( $limit ) {
			$limit = 'LIMIT ' . intval($limit);
			if( $offset > 0 )
				$limit .= ' OFFSET ' . intval($offset);
		}
		else
			$limit = '';

		$s = $core->db->prepare($q = "SELECT p.id FROM `{$core->db_prefix}pages` p INNER JOIN `{$core->db_prefix}content` c ON c.pid=p.id AND c.ln=:ln AND c.name!='' AND c.`{$this->textField}`!='' WHERE p.idp=:idp AND p.published=1 AND p.deleted=0 ORDER BY {$this->orderBy} {$limit}");
		if( !$s->execute($p = array('idp' => $this->parentId, 'ln' => $core->site->ln)) )
			throw new DbException($s->errorInfo(), $q, $p);

		$idList = array();
		while( $f = $s->fetch() )
			$idList[] = $f['id'];

		$list = empty($idList) ? array() : $core->page->Get_page($idList, true, false, false, array(), '', null, 'FIELD(p.id, ' . implode(',', $idList) . ')');
		return $list;
	}
}