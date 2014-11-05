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

namespace OCA\OcSms\AppInfo;


use \OCP\AppFramework\App;

use \OCA\OcSms\Controller\SmsController;

use \OCA\OcSms\Db\Sms;
use \OCA\OcSms\Db\SmsMapper;


class OcSmsApp extends App {

	/**
	* @var array used to cache the parsed contacts for every request
	*/
	private static $contacts;
	
	private static $contactsInverted;
	
	private $c;

	public function __construct (array $urlParams=array()) {
		parent::__construct('ocsms', $urlParams);

		$container = $this->getContainer();
		$this->c = $container;
		$app = $this;
		
		/**
		 * Controllers
		 */

		$container->registerService('SmsController', function($c) use($app) {
			return new SmsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('SmsMapper'),
				$app
			);
		});

		/**
        	 * Database Layer
        	 */
	        $container->registerService('Sms', function($c) {
	            return new Sms($c->query('ServerContainer')->getDb());
	        });

	        $container->registerService('SmsMapper', function($c) {
	            return new SmsMapper($c->query('ServerContainer')->getDb());
	        });

		/**
		 * Core
		 */
		$container->registerService('UserId', function($c) {
			return \OCP\User::getUser();
		});

		/**
		 * Managers
		 */
		$container->registerService('ContactsManager', function($c){
			return $c->getServer()->getContactsManager();
		});
	}

	public function getContacts() {
		// Only load contacts if they aren't in the buffer
		if(count(self::$contacts) == 0) {
			$this->loadContacts();
		}

		return self::$contacts;
	}
	
	public function getInvertedContacts() {
		// Only load contacts if they aren't in the buffer
		if(count(self::$contactsInverted) == 0) {
			$this->loadContacts();
		}

		return self::$contactsInverted;
	}
	
	/**
	 * Partially importe this function from owncloud Chat app
	 * https://github.com/owncloud/chat/blob/master/app/chat.php
	 */
	private function loadContacts() {
		self::$contacts = array();
		self::$contactsInverted = array();
		
		$cm = $this->c['ContactsManager'];
		if ($cm == null) {
			return;
		}
		
		$result = array();
		try {
			$result = $cm->search('',array('FN'));
		} catch (Exception $e) {
			// If contact manager failed, avoid the issue
			return;
		}

		foreach ($result as $r) {
			if (isset ($r["TEL"])) {
				$phoneIds = $r["TEL"];
				if (is_array($phoneIds)) {
					$countPhone = count($phoneIds);
					for ($i=0; $i < $countPhone; $i++) {
						$this->pushPhoneNumberToCache($phoneIds[$i], $r["FN"]);
					}
				}
				else {
					$this->pushPhoneNumberToCache($phoneIds, $r["FN"]);
				}
			}
		}
	}

	private function pushPhoneNumberToCache($rawPhone, $contactName) {
		// We try to add many combinaisons
		$phoneNb = preg_replace("#[ ]#", "/", $rawPhone);

		/*
		* At this point, spaces are slashes.
		*/

		// Spaces removed
		$phoneNbNoSpaces = preg_replace("#[/]#", "", $phoneNb);
		// Parenthesis removed
		$phoneNbNoParenthesis = preg_replace("#[(]|[)]#", "", $phoneNb);
		// Dashes removed
		$phoneNbNoDashes = preg_replace("#[-]#", "", $phoneNb);

		// Spaces and parenthesis
		$phoneNbNoSpacesParenthesis = preg_replace("#[/]|[(]|[)]#", "", $phoneNb);
		// Spaces and dashes
		$phoneNbNoSpacesDashes = preg_replace("#[/]|[-]#", "", $phoneNb);
		// parenthesis and dashes
		$phoneNbNoDashesParenthesis = preg_replace("#[-]|[(]|[)]#", "", $phoneNb);

		// Nothing
		$phoneNbNothing = preg_replace("#[/]|[(]|[)]|[-]#", "", $phoneNb);
		
		// Contacts
		self::$contacts[$phoneNb] = $contactName;
		self::$contacts[$phoneNbNoSpaces] = $contactName;
		self::$contacts[$phoneNbNoParenthesis] = $contactName;
		self::$contacts[$phoneNbNoDashes] = $contactName;
		self::$contacts[$phoneNbNoSpacesParenthesis] = $contactName;
		self::$contacts[$phoneNbNoSpacesDashes] = $contactName;
		self::$contacts[$phoneNbNoDashesParenthesis] = $contactName;
		self::$contacts[$phoneNbNothing] = $contactName;

		// Inverted contacts
		if (!isset(self::$contactsInverted[$contactName])) {
			self::$contactsInverted[$contactName] = array();
		}

		array_push(self::$contactsInverted[$contactName], $phoneNb);

		if (!in_array($phoneNbNoSpaces, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNoSpaces);
		}

		if (!in_array($phoneNbNoParenthesis, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNoParenthesis);
		}

		if (!in_array($phoneNbNoDashes, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNoDashes);
		}

		if (!in_array($phoneNbNoSpacesParenthesis, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNoSpacesParenthesis);
		}

		if (!in_array($phoneNbNoSpacesDashes, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNoSpacesDashes);
		}

		if (!in_array($phoneNbNoDashesParenthesis, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNoDashesParenthesis);
		}

		if (!in_array($phoneNbNothing, self::$contactsInverted[$contactName])) {
			array_push(self::$contactsInverted[$contactName], $phoneNbNothing);
		}
	}
}
