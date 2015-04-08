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
	private $urlGenerator;

	public function __construct ($appName, IRequest $request, $userId, SmsMapper $mapper, ConfigMapper $cfgMapper, $urlGenerator, OcSmsApp $app){
		parent::__construct($appName, $request);
		$this->app = $app;
		$this->userId = $userId;
		$this->smsMapper = $mapper;
		$this->configMapper = $cfgMapper;
		$this->urlGenerator = $urlGenerator;
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
				'url' => $this->urlGenerator->linkToRoute('ocsms.sms.index', ['feed' => 'conversations'])
			),
			'PNLDrafts' => array(
				'label' => 'Drafts',
				'phoneNumbers' => array(),
				'url' => $this->urlGenerator->linkToRoute('ocsms.sms.index', ['feed' => 'drafts'])
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
	 */
	function setCountry($country) {
		if (!array_key_exists($country, CountryCodes::$codes)) {
			return new JSONResponse(array("status" => false, "msg" => "Invalid country"));
		}
		$this->configMapper->set("country", $country);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}

	/**
	* @NoAdminRequired
	*/
	function getCountry() {
		$country = $this->configMapper->getKey("country");
		if ($country === false) {
			return new JSONResponse(array("status" => false));
		}
		return new JSONResponse(array("status" => true, "country" => $country));
	}
}
