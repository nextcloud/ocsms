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


namespace OCA\OcSms\AppInfo;


use \OCP\AppFramework\App;

use \OCA\OcSms\Controller\SmsController;

use \OCA\OcSms\Db\Sms;
use \OCA\OcSms\Db\SmsMapper;

use \OCA\OcSms\Db\ConfigMapper;

use \OCA\OcSms\Lib\PhoneNumberFormatter;

class OcSmsApp extends App {

	/**
	* @var array used to cache the parsed contacts for every request
	*/
	private static $contacts;
	private static $contactPhotos;

	private static $contactsInverted;

	private $c;

	public function __construct (array $urlParams=array()) {
		parent::__construct('ocsms', $urlParams);

		$container = $this->getContainer();
		$this->c = $container;
		$app = $this;

		/**
		 * Core
		 */
		$container->registerService('UserId', function($c) {
			return \OCP\User::getUser();
		});

		/**
        	 * Database Layer
        	 */
		$container->registerService('ConfigMapper', function ($c) use ($app) {
                        return new ConfigMapper(
                                $c->query('ServerContainer')->getDb(),
				$c->query('UserId'),
                                $c->query('ServerContainer')->getCrypto()
                        );
                });

	        $container->registerService('Sms', function($c) {
	            return new Sms($c->query('ServerContainer')->getDb());
	        });

	        $container->registerService('SmsMapper', function($c) {
	            return new SmsMapper($c->query('ServerContainer')->getDb());
	        });

		/**
		 * Controllers
		 */
		$container->registerService('SmsController', function($c) use($app) {
			return new SmsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('SmsMapper'),
				$c->query('ConfigMapper'),
				$app
			);
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

	public function getContactPhotos() {
		// Only load contacts if they aren't in the buffer
		if(count(self::$contactPhotos) == 0) {
			$this->loadContacts();
		}
		return self::$contactPhotos;
	}

	/**
	 * Partially importe this function from owncloud Chat app
	 * https://github.com/owncloud/chat/blob/master/app/chat.php
	 */
	private function loadContacts() {
		self::$contacts = array();
		self::$contactsInverted = array();

		// Cache country because of loops
                $configuredCountry = $this->c->query('ConfigMapper')->getCountry();

		$cm = $this->c['ContactsManager'];
		if ($cm == null) {
			return;
		}

		$result = $cm->search('',array('FN'));

		foreach ($result as $r) {
			if (isset ($r["TEL"])) {
				$phoneIds = $r["TEL"];
				if (is_array($phoneIds)) {
					$countPhone = count($phoneIds);
					for ($i=0; $i < $countPhone; $i++) {
						$this->pushPhoneNumberToCache($phoneIds[$i], $r["FN"], $configuredCountry);
					}
				}
				else {
					$this->pushPhoneNumberToCache($phoneIds, $r["FN"], $configuredCountry);
				}
				
				if (isset ($r["PHOTO"])) {
					// Remove useless prefix
					$photoURL = preg_replace("#VALUE=uri:#","",$r["PHOTO"]);
					self::$contactPhotos[$r["FN"]] = $photoURL;
				}
			}
		}
	}

	private function pushPhoneNumberToCache($rawPhone, $contactName, $country) {

		$phoneNb = PhoneNumberFormatter::format($country, $rawPhone);
		self::$contacts[$phoneNb] = $contactName;
		// Inverted contacts
		if (!isset(self::$contactsInverted[$contactName])) {
			self::$contactsInverted[$contactName] = array();
		}
		array_push(self::$contactsInverted[$contactName], $phoneNb);
	}
}
