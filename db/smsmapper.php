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
	public function __construct (IDb $db) {
		parent::__construct($db, 'ocsms_smsdatas');
	}
	
	public function getAllIds ($userId) {
		$query = \OC_DB::prepare('SELECT sms_id, sms_mailbox FROM ' .
		'*PREFIX*ocsms_smsdatas WHERE user_id = ?');
		$result = $query->execute(array($userId));
		
		$smsList = array();
		while($row = $result->fetchRow()) {
			$mbox = $row["sms_mailbox"];
			if (!isset($smsList[$mbox])) {
				$smsList[$mbox] = array();
			}
			
			if (!in_array($row["sms_id"], $smsList[$mbox])) {
				array_push($smsList[$mbox], $row["sms_id"]);
			}
		}
		return $smsList;
	}

	public function writeToDB ($userId, $smsList, $purgeAllSmsBeforeInsert = false) {
		\OCP\DB::beginTransaction();
		
		if ($purgeAllSmsBeforeInsert === true) {
			$query = \OC_DB::prepare('DELETE FROM *PREFIX*ocsms_smsdatas ' .
			'WHERE user_id = ?');
			$result = $query->execute(array($userId));
		}
		
		foreach ($smsList as $sms) {
			$smsFlags = sprintf("%s%s",
				$sms["read"] === "true" ? "1" : "0",
				$sms["seen"] === "true" ? "1" : "0"
			);
			
			// Only delete if we haven't purged the DB
			if ($purgeAllSmsBeforeInsert === false) {
				// Remove previous record
				// @ TODO: only update the required fields, getAllIds can be useful
				$query = \OC_DB::prepare('DELETE FROM *PREFIX*ocsms_smsdatas ' .
				'WHERE user_id = ? AND sms_id = ?');
				$result = $query->execute(array(
					$userId, (int) $sms["_id"]
				));
			}
			
			$query = \OC_DB::prepare('INSERT INTO *PREFIX*ocsms_smsdatas ' .
			'(user_id, added, lastmodified, sms_flags, sms_date, sms_id,' .
			'sms_address, sms_msg, sms_mailbox, sms_type) VALUES ' .
			'(?,?,?,?,?,?,?,?,?,?)');
			$result = $query->execute(array(
				$userId, "NOW()", "NOW()", $smsFlags,
				(int) $sms["date"], (int) $sms["_id"],
				$sms["address"], $sms["body"], (int) $sms["mbox"],
				(int) $sms["type"]
			));
			
			
		}
		
		\OCP\DB::commit();
	}
}

?>
