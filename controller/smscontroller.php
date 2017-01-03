<?php
/**
 * ownCloud - ocsms
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2016
 */

namespace OCA\OcSms\Controller;


use \OCP\IRequest;
use \OCP\IURLGenerator;
use \OCP\Contacts\IManager as IContactsManager;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http;

use \OCA\OcSms\Db\ConfigMapper;
use \OCA\OcSms\Db\SmsMapper;
use \OCA\OcSms\Db\ConversationStateMapper;

use \OCA\OcSms\Lib\ContactCache;
use \OCA\OcSms\Lib\PhoneNumberFormatter;

class SmsController extends Controller {

	private $userId;
	private $configMapper;
	private $smsMapper;
	private $convStateMapper;
	private $urlGenerator;
	private $contactCache;

	/**
	 * SmsController constructor.
	 * @param string $appName
	 * @param IRequest $request
	 * @param $userId
	 * @param SmsMapper $mapper
	 * @param ConfigMapper $cfgMapper
	 * @param IContactsManager $contactsManager
	 * @param $urlGenerator
	 */
	public function __construct ($appName, IRequest $request, $userId,
			SmsMapper $mapper, ConversationStateMapper $cmapper,
			ConfigMapper $cfgMapper,
			IContactsManager $contactsManager, IURLGenerator $urlGenerator) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->smsMapper = $mapper;
		$this->convStateMapper = $cmapper;
		$this->configMapper = $cfgMapper;
		$this->urlGenerator = $urlGenerator;
		$this->contactCache = new ContactCache($cfgMapper, $contactsManager);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index () {
		$mboxes = array(
			'PNLConversations' => array(
				'label' => 'Conversations',
				'phoneNumbers' => $this->smsMapper->getAllPhoneNumbers($this->userId),
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
		$contactsSrc = $this->contactCache->getContacts();
		$contacts = array();
		$photos = $this->contactCache->getContactPhotos();
		$uids = $this->contactCache->getContactUids();

		// Cache country because of loops
		$configuredCountry = $this->configMapper->getCountry();

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
		$lastRead = $this->convStateMapper->getLast($this->userId);
		$ocversion = \OCP\Util::getVersion();
		$photoversion = 1;
		if (version_compare($ocversion[0].".".$ocversion[1].".".$ocversion[2], "9.0.0", ">=")) {
			$photoversion = 2;
		}

		return new JSONResponse(array("phonelist" => $phoneList, "contacts" => $contacts, "lastRead" => $lastRead, "photos" => $photos, "uids" => $uids, "photo_version" => $photoversion));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $phoneNumber
	 * @param int $lastDate
	 * @return JSONResponse
	 */
	public function getConversation ($phoneNumber, $lastDate = 0) {
		$contacts = $this->contactCache->getContacts();
		$iContacts = $this->contactCache->getInvertedContacts();
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
			$phoneNumbers[] = PhoneNumberFormatter::format($configuredCountry, $phoneNumber);
		}
		// Order by id (date)
		ksort($messages);
		$msgLimit = $this->configMapper->getMessageLimit();
		// Only load the last 500 messages
		$messages = array_slice($messages, -$msgLimit, $msgLimit, true);

		// Set the last read message for the conversation (all phone numbers)
		if (count($messages) > 0) {
			$maxDate = max(array_keys($messages));
			for ($i=0;$i<count($phoneNumbers);$i++) {
				$this->convStateMapper->setLast($this->userId, $phoneNumbers[$i], $maxDate);
			}
		}

		// @ TODO: filter correctly
		return new JSONResponse(array("conversation" => $messages, "contactName" => $contactName,
			"phoneNumbers" => $phoneNumbers, "msgCount" => $msgCount));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $contact
	 * @return JSONResponse
	 */
	public function deleteConversation ($contact) {
		$contacts = $this->contactCache->getContacts();
		$iContacts = $this->contactCache->getInvertedContacts();
		$contactName = "";

		// Cache country because of loops
		$configuredCountry = $this->configMapper->getCountry();

		$fmtPN = PhoneNumberFormatter::format($configuredCountry, $contact);
		if (isset($contacts[$fmtPN])) {
			$contactName = $contacts[$fmtPN];
		}

		// Contact resolved
		if ($contactName != "" && isset($iContacts[$contactName])) {
			// forall numbers in iContacts
			foreach($iContacts[$contactName] as $cnumber) {
				$this->smsMapper->removeMessagesForPhoneNumber($this->userId, $cnumber);
			}
		}
		else {
			$this->smsMapper->removeMessagesForPhoneNumber($this->userId, $contact);
		}
		return new JSONResponse(array());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $lastDate
	 * @return JSONResponse
	 */
	public function checkNewMessages($lastDate) {
		$phoneList = $this->smsMapper->getNewMessagesCountForAllPhonesNumbers($this->userId, $lastDate);
		$contactsSrc = $this->contactCache->getContacts();
		$photosSrc = $this->contactCache->getContactPhotos();
		$uidsSrc = $this->contactCache->getContactUids();
		$contacts = array();
		$photos = array();
		$uids = array();

		foreach ($phoneList as $number => $ts) {
			$fmtPN = preg_replace("#[ ]#","", $number);
			if (isset($contactsSrc[$fmtPN])) {
				$contacts[$fmtPN] = $contactsSrc[$fmtPN];
				$uids[$fmtPN] = $uidsSrc[$fmtPN];

				if (isset($photosSrc[$contacts[$fmtPN]])) {
					$photos[$contacts[$fmtPN]] = $photosSrc[$contacts[$fmtPN]];
				}
			}
		}

		return new JSONResponse(array("phonelist" => $phoneList, "contacts" => $contacts, "photos" => $photos, "uids" => $uids));
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @param $messageId
	 * @param $phoneNumber
	 * @return JSONResponse
	 */
	public function deleteMessage ($messageId, $phoneNumber) {
		if (!preg_match('#^[0-9]+$#',$messageId)) {
			return new JSONResponse(array(), Http::STATUS_BAD_REQUEST);
		}
		$this->smsMapper->removeMessage($this->userId, $phoneNumber, $messageId);
		return new JSONResponse(array());
	}
}
