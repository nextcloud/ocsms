<?php
/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2017
 */

namespace OCA\OcSms\Db;

use \OCP\IDBConnection;

use \OCP\AppFramework\Db\Mapper;

use \OCA\OcSms\AppInfo\OcSmsApp;
use \OCA\OcSms\Lib\PhoneNumberFormatter;

class ConversationStateMapper extends Mapper {
	public function __construct (IDBConnection $db) {
		parent::__construct($db, 'ocsms_smsdatas');
	}

	public function getLast ($userId) {
		$sql = 'SELECT MAX(int_date) as mx FROM ' .
		'*PREFIX*ocsms_conversation_read_states WHERE user_id = ?';

		$query = \OCP\DB::prepare($sql);
		$result = $query->execute(array($userId));

		if ($row = $result->fetchRow()) {
			return $row["mx"];
		}

		return 0;
	}

	public function getLastForPhoneNumber ($userId, $phoneNumber) {
		$sql = 'SELECT MAX(int_date) as mx FROM ' .
			'*PREFIX*ocsms_conversation_read_states WHERE user_id = ? AND phone_number = ?';

		$query = \OCP\DB::prepare($sql);
		$result = $query->execute(array($userId, $phoneNumber));

		if ($row = $result->fetchRow()) {
			return $row["mx"];
		}

		return 0;
	}

	public function setLast ($userId, $phoneNumber, $lastDate) {
		\OCP\DB::beginTransaction();
		$query = \OCP\DB::prepare('DELETE FROM *PREFIX*ocsms_conversation_read_states ' .
			'WHERE user_id = ? AND phone_number = ?');
		$query->execute(array($userId, $phoneNumber));

		$query = \OCP\DB::prepare('INSERT INTO *PREFIX*ocsms_conversation_read_states' .
			'(user_id, phone_number, int_date) VALUES ' .
			'(?,?,?)');
		$query->execute(array($userId, $phoneNumber, $lastDate));
		\OCP\DB::commit();
	}

	/*
	 * Migration steps
	 */

	public function migrate () {
		$sql = 'SELECT user_id, datakey, datavalue FROM ' .
			'*PREFIX*ocsms_user_datas WHERE datakey LIKE \'lastReadDate-%\'';

		$query = \OCP\DB::prepare($sql);
		$result = $query->execute(array());

		while ($row = $result->fetchRow()) {
			$pn = preg_replace("#lastReadDate[-]#", "", $row["datakey"]);
			$this->setLast($row["user_id"], $pn, $row["datavalue"]);
		};

		$query = \OCP\DB::prepare("DELETE FROM *PREFIX*ocsms_user_datas WHERE datakey LIKE 'lastReadDate-%'");
		$query->execute(array());
	}
}

?>
