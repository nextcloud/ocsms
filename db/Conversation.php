<?php
/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2018
 */

namespace OCA\OcSms\Db;


class Conversation {
    public $id;
	public $userId;
	public $phoneNumber;

	public function __construct($userId, $phoneNumber) {
		$this->userId = $userId;
		$this->phoneNumber = $phoneNumber;
		$id = null;
	}
}