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

use \OCP\AppFramework\Db\Mapper;

class SmsMapper extends Mapper {
	private $db;

    public function __construct(IDb $db) {
        $this->db = $db;
    }

	// @TODO
	public function saveAll($smsList) {
		foreach ($smsList as $sms) {
			$query = \OC_DB::prepare('INSERT INTO *PREFIX*ocsms_smsdatas ' .
			'(user_id, added, lastmodified, sms_read, sms_seen, sms_date,' .
			'sms_draft, sms_id, sms_address, sms_msg) VALUES ' .
			'(?,?,?,?,?,?,?,?,?,?)');
			$result = $query->execute(array(
				\OCP\User::getUser(),"NOW()","NOW()",
				$sms["read"] === "true", $sms["seen"] === "true",
				$sms["date"], $sms["draft"] === "true", $sms["id"],
				$sms["address"], $sms["body"]
			));
		}
	}

    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*ocsms_smsdatas` ' .
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
