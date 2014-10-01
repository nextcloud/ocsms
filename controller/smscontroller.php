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

namespace OCA\OcSms\Controller;


use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCA\OcSms\Db\SmsMapper;

class SmsController extends Controller {

	private $app;
	private $userId;
	private $smsMapper;
	private $errorMsg;

	public function __construct ($appName, IRequest $request, $userId, SmsMapper $mapper, OcSmsApp $app){
		parent::__construct($appName, $request);
		$this->app = $app;
		$this->userId = $userId;
		$this->smsMapper = $mapper;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index () {
		$mboxes = array(
			'PNLConversations' => array(
				'label' => 'Conversations',
				'phoneNumbers' => $this->smsMapper->getAllPeersPhoneNumbers($this->userId),
				'url' => \OCP\Util::linkToAbsolute('index.php', 'apps/ocsms/', array('feed' => 'conversations'))
			),
			'PNLDrafts' => array(
				'label' => 'Drafts',
				'phoneNumbers' => array(),
				'url' => \OCP\Util::linkToAbsolute('index.php', 'apps/ocsms/', array('feed' => 'drafts'))
			)
		);

		$params = array('user' => $this->userId,
			'mailboxes' => $mboxes
		);
		return new TemplateResponse($this->appName, 'main', $params);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function retrieveAllIds () {
		$smsList = $this->smsMapper->getAllIds($this->userId);
		return new JSONResponse(array("smslist" => $smsList));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function retrieveAllPeers () {
		$phoneList = $this->smsMapper->getAllPeersPhoneNumbers($this->userId);
		// @ TODO: filter correctly
		return new JSONResponse(array("phonelist" => $phoneList));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getConversation ($phoneNumber, $lastDate = 0) {
		$messages = $this->smsMapper->getAllMessagesForPhoneNumber($this->userId, $phoneNumber, $lastDate);
		// @ TODO: filter correctly
		return new JSONResponse(array("conversation" => $messages, $contacts => $this->app->getContacts()));
	}

	/**
	 * @NoAdminRequired
	 */
	public function push ($smsCount, $smsDatas) {
		if ($this->checkPushStructure($smsCount, $smsDatas, true) === false) {
			return new JSONResponse(array("status" => false, "msg" => $this->errorMsg));
		}

		$this->smsMapper->writeToDB($this->userId, $smsDatas);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}

	/**
	 * @NoAdminRequired
	 */
	 public function replace($smsCount, $smsDatas) {
		 if ($this->checkPushStructure($smsCount, $smsDatas, true) === false) {
			return new JSONResponse(array("status" => false, "msg" => $this->errorMsg));
		}

		$this->smsMapper->writeToDB($this->userId, $smsDatas, true);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	 }

	private function checkPushStructure ($smsCount, $smsDatas) {
		if ($smsCount != count($smsDatas)) {
			$this->errorMsg = "Error: sms count invalid";
			return false;
		}

		foreach ($smsDatas as &$sms) {
			if (!array_key_exists("_id", $sms) || !array_key_exists("read", $sms) ||
				!array_key_exists("date", $sms) || !array_key_exists("seen", $sms) ||
				!array_key_exists("mbox", $sms) || !array_key_exists("type", $sms) ||
				!array_key_exists("body", $sms) || !array_key_exists("address", $sms)) {
				$this->errorMsg = "Error: bad SMS entry";
				return false;
			}

			if (!is_numeric($sms["_id"])) {
				$this->errorMsg = sprintf("Error: Invalid SMS ID '%s'", $sms["_id"]);
				return false;
			}

			if (!is_numeric($sms["type"])) {
				$this->errorMsg = sprintf("Error: Invalid SMS type '%s'", $sms["type"]);
				return false;
			}

			if (!is_numeric($sms["mbox"]) && $sms["mbox"] != 0 && $sms["mbox"] != 1 &&
				$sms["mbox"] != 2) {
				$this->errorMsg = sprintf("Error: Invalid Mailbox ID '%s'", $sms["mbox"]);
				return false;
			}

			if ($sms["read"] !== "true" && $sms["read"] !== "false") {
				$this->errorMsg = sprintf("Error: Invalid SMS Read state '%s'", $sms["read"]);
				return false;
			}

			if ($sms["seen"] !== "true" && $sms["seen"] !== "false") {
				$this->errorMsg = "Error: Invalid SMS Seen state";
				return false;
			}

			if (!is_numeric($sms["date"]) && $sms["date"] != 0 && $sms["date"] != 1) {
				$this->errorMsg = "Error: Invalid SMS date";
				return false;
			}

			// @ TODO: test address and body ?
		}
		return true;
	}
}
