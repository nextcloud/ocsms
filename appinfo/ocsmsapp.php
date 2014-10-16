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
		
		/**
		 * Controllers
		 */

		$container->registerService('SmsController', function($c) {
			return new SmsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('SmsMapper'),
				$this
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
		
		$result = $cm->search('',array('FN'));
		foreach ($result as $r) {
			if (isset ($r["TEL"])) {
				$phoneIds = $r["TEL"];
				if (is_array($phoneIds)) {
					$countPhone = count($phoneIds);
					for ($i=0; $i < $countPhone; $i++) {
						$phoneNb = preg_replace("#[ ]#", "", $phoneIds[$i]);
						
						self::$contacts[$phoneNb] = $r["FN"];
						if (!isset(self::$contactsInverted[$r["FN"]])) {
							self::$contactsInverted[$r["FN"]] = array();
						}
						array_push(self::$contactsInverted[$r["FN"]], $phoneNb);
					}
				}
				else {
					self::$contacts[$phoneIds] = $r["FN"];
					if (!isset(self::$contactsInverted[$r["FN"]])) {
						self::$contactsInverted[$r["FN"]] = array();
					}
					array_push(self::$contactsInverted[$r["FN"]], $phoneIds);
				}
			}
		}
	}
}
