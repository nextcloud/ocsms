<?php
/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2016
 */


namespace OCA\OcSms\AppInfo;


use \OCP\AppFramework\App;
use \OCP\IContainer;

use \OCA\OcSms\Controller\ApiController;
use \OCA\OcSms\Controller\SettingsController;
use \OCA\OcSms\Controller\SmsController;

use \OCA\OcSms\Db\Sms;
use \OCA\OcSms\Db\SmsMapper;
use \OCA\OcSms\Db\ConversationStateMapper;

use \OCA\OcSms\Db\ConfigMapper;

use \OCA\OcSms\Migration\FixConversationReadStates;

class OcSmsApp extends App {

	/**
	 * OcSmsApp constructor.
	 * @param array $urlParams
	 */
	public function __construct (array $urlParams=array()) {
		parent::__construct('ocsms', $urlParams);
		$container = $this->getContainer();
		$server = $container->query('ServerContainer');

		$container->registerService('UserId', function($c) use ($server) {
			if ($server->getUserSession()->getUser()) {
				return $server->getUserSession()->getUser()->getUID();
			}
			else {
				return null;
			}
		});

		/**
	         * Database Layer
        	 */
		$container->registerService('ConfigMapper', function (IContainer $c) use ($server) {
			return new ConfigMapper(
				$server->getDatabaseConnection(),
				$c->query('UserId'),
				$server->getCrypto()
			);
		});

		$container->registerService('Sms', function(IContainer $c) use ($server) {
			return new Sms($server->getDb());
		});

		$container->registerService('ConversationStateMapper', function(IContainer $c) use ($server) {
			return new ConversationStateMapper($server->getDatabaseConnection());
		});

		$container->registerService('SmsMapper', function(IContainer $c) use ($server) {
			return new SmsMapper(
				$server->getDatabaseConnection(),
				$c->query('ConversationStateMapper')
			);
		});

		/**
		 * Managers
		 */
		$container->registerService('ContactsManager', function(IContainer $c) use ($server) {
			return $server->getContactsManager();
		});

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function(IContainer $c) {
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ConfigMapper')
			);
		});

		$container->registerService('SmsController', function(IContainer $c) use($server) {
			return new SmsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('SmsMapper'),
				$c->query('ConversationStateMapper'),
				$c->query('ConfigMapper'),
				$server->getContactsManager(),
				$server->getURLGenerator()
			);
		});

		$container->registerService('ApiController', function(IContainer $c) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('SmsMapper')
			);
		});

		/**
		 * Migration services
		 */
		$container->registerService('OCA\OcSms\Migration\FixConversationReadStates', function ($c) {
			return new FixConversationReadStates(
				$c->query('ConversationStateMapper'),
				$c->getServer()->getUserManager()
			);
		});
	}
}
