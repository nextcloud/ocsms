<?php
/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2015
 */

namespace OCA\OcSms\Controller;


use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;

use \OCA\OcSms\AppInfo\OcSmsApp;

use \OCA\OcSms\Db\ConfigMapper;
use \OCA\OcSms\Db\SmsMapper;

use \OCA\OcSms\Lib\CountryCodes;
use \OCA\OcSms\Lib\PhoneNumberFormatter;

class SmsController extends Controller {

	private $app;
	private $userId;
	private $configMapper;
	private $smsMapper;
	private $errorMsg;

	public function __construct ($appName, IRequest $request, $userId, SmsMapper $mapper, ConfigMapper $cfgMapper, OcSmsApp $app){
		parent::__construct($appName, $request);
		$this->app = $app;
		$this->userId = $userId;
		$this->smsMapper = $mapper;
		$this->configMapper = $cfgMapper;
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
	public function getApiVersion () {
		return new JSONResponse(array("version" => 1));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * This function is used by API v1
	 * Phone will compare its own message list with this
	 * message list and send the missing messages
	 * This call will remain as secure slow sync mode (1 per hour)
	 */
	public function retrieveAllIds () {
		$smsList = $this->smsMapper->getAllIds($this->userId);
		return new JSONResponse(array("smslist" => $smsList));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * This function is used by API v2
	 * Phone will get this ID to push recent messages
	 * This call will be used combined with retrieveAllIds
	 * but will be used more times
	 */
	public function retrieveLastTimestamp () {
		$ts = $this->smsMapper->getLastTimestamp($this->userId);
		return new JSONResponse(array("timestamp" => $ts));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function retrieveAllIdsWithStatus () {
		$smsList = $this->smsMapper->getAllIdsWithStatus($this->userId);
		return new JSONResponse(array("smslist" => $smsList));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function retrieveAllPeers () {
		$phoneList = $this->smsMapper->getLastMessageTimestampForAllPhonesNumbers($this->userId);
		$contactsSrc = $this->app->getContacts();
		$contacts = array();
		$photos = $this->app->getContactPhotos();

		// Cache country because of loops
		$configuredCountry = $this->configMapper->getCountry();

		$countPhone = count($phoneList);
		foreach ($phoneList as $number => $ts) {
			$fmtPN = PhoneNumberFormatter::format($configuredCountry, $number);
			if (isset($contactsSrc[$number])) {
				$contacts[$number] = $contactsSrc[$number];
			} elseif (isset($contactsSrc[$fmtPN])) {
				$contacts[$number] = $contactsSrc[$fmtPN];
			} elseif (isset($contacts[$fmtPN])) {
				$contacts[$number] = $fmtPN;
			} else {
				$contacts[$number] = $fmtPN;
			}
		}
		$lastRead = $this->smsMapper->getLastReadDate($this->userId);
		return new JSONResponse(array("phonelist" => $phoneList, "contacts" => $contacts, "lastRead" => $lastRead, "photos" => $photos));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getConversation ($phoneNumber, $lastDate = 0) {
		$contacts = $this->app->getContacts();
		$iContacts = $this->app->getInvertedContacts();
		$contactName = "";
	
		// Cache country because of loops
		$configuredCountry = $this->configMapper->getCountry();

		$fmtPN = PhoneNumberFormatter::format($configuredCountry, $phoneNumber);
		if (isset($contacts[$fmtPN])) {
			$contactName = $contacts[$fmtPN];
		}

		$messages = array();
		$phoneNumbers = array();
		$msgCount = 0;
		// Contact resolved
		if ($contactName != "" && isset($iContacts[$contactName])) {
			// forall numbers in iContacts
			foreach($iContacts[$contactName] as $cnumber) {
				$messages = $messages +	$this->smsMapper->getAllMessagesForPhoneNumber($this->userId, $cnumber, $configuredCountry, $lastDate);
				$msgCount += $this->smsMapper->countMessagesForPhoneNumber($this->userId, $cnumber, $configuredCountry);
				$phoneNumbers[] = PhoneNumberFormatter::format($configuredCountry, $cnumber);
			}
		}
		else {
			$messages = $this->smsMapper->getAllMessagesForPhoneNumber($this->userId, $phoneNumber, $configuredCountry, $lastDate);
			$msgCount = $this->smsMapper->countMessagesForPhoneNumber($this->userId, $phoneNumber, $configuredCountry);
			if(isset($peerNumber[$fmtPN])) {
				foreach($peerNumber[$fmtPN] as $cnumber) {
					$messages = $messages +	$this->smsMapper->getAllMessagesForPhoneNumber($this->userId, $cnumber, $configuredCountry, $lastDate);
					$msgCount += $this->smsMapper->countMessagesForPhoneNumber($this->userId, $cnumber, $configuredCountry);
				}
			}
			$phoneNumbers[] = PhoneNumberFormatter::format($configuredCountry, $phoneNumber);
		}
		// Order by id (date)
		ksort($messages);

		// Set the last read message for the conversation (all phone numbers)
		if (count($messages) > 0) {
			$maxDate = max(array_keys($messages));
			for ($i=0;$i<count($phoneNumbers);$i++) {
				$this->smsMapper->setLastReadDate($this->userId, $phoneNumbers[$i], $maxDate);
			}
		}

		// @ TODO: filter correctly
		return new JSONResponse(array("conversation" => $messages, "contactName" => $contactName,
			"phoneNumbers" => $phoneNumbers, "msgCount" => $msgCount));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function checkNewMessages($lastDate) {
		$phoneList = $this->smsMapper->getNewMessagesCountForAllPhonesNumbers($this->userId, $lastDate);
		$contactsSrc = $this->app->getContacts();
		$photosSrc = $this->app->getContactPhotos();
		$contacts = array();
		$photos = array();

		$countPhone = count($phoneList);
		foreach ($phoneList as $number => $ts) {
			$fmtPN = preg_replace("#[ ]#","/", $number);
			if (isset($contactsSrc[$fmtPN])) {
				$fmtPN2 = preg_replace("#\/#","", $fmtPN);
				$contacts[$fmtPN] = $contactsSrc[$fmtPN];
				$contacts[$fmtPN2] = $contactsSrc[$fmtPN];
	
				if (isset($photosSrc[$contacts[$fmtPN]])) {
					$photos[$contacts[$fmtPN]] = $photosSrc[$contacts[$fmtPN]];
				}

				if (isset($photosSrc[$contacts[$fmtPN2]])) {
					$photos[$contacts[$fmtPN2]] = $photosSrc[$contacts[$fmtPN2]];
				}
			}
		}

		return new JSONResponse(array("phonelist" => $phoneList, "contacts" => $contacts, "photos" => $photos));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
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

	/**
	 * @NoAdminRequired
	 */
	function setCountry($country) {
		if (!array_key_exists($country, CountryCodes::$codes)) {
			return new JSONResponse(array("status" => false, "msg" => "Invalid country"));
		}
		$this->configMapper->set("country", $country);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}
}
