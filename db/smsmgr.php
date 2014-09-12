<?php
/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014
 */

namespace OCA\OcSms\Db;

use \OCP\IDb;

class SmsMgr {
	private $db;

    public function __construct(IDb $db) {
        $this->db = $db;
    }

	// @TODO
	public function saveAll($smsList) {

	}

    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*myapp_authors` ' .
            'WHERE `id` = ?';
        $query = $db->prepareQuery($sql);
        $query->bindParam(1, $id, \PDO::PARAM_INT);
        $result = $query->execute();

        while($row = $result->fetchRow()) {
            return $row;
        }
    }
}

?>
