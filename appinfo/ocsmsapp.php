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


namespace OCA\OcSms\AppInfo;


use \OCP\AppFramework\App;
use \OCP\IContainer;

use \OCA\OcSms\Controller\ApiController;
use \OCA\OcSms\Controller\SettingsController;
use \OCA\OcSms\Controller\SmsController;

use \OCA\OcSms\Db\Sms;
use \OCA\OcSms\Db\SmsMapper;

use \OCA\OcSms\Db\ConfigMapper;

class OcSmsApp extends App {

	private $c;

	public function __construct (array $urlParams=array()) {
		parent::__construct('ocsms', $urlParams);

		$container = $this->getContainer();
		$this->c = $container;
		$app = $this;

		/**
        	 * Database Layer
        	 */
		$container->registerService('ConfigMapper', function (IContainer $c) use ($app) {
			return new ConfigMapper(
				$c->query('ServerContainer')->getDb(),
				$c->query('CurrentUID'),
				$c->query('ServerContainer')->getCrypto()
			);
		});

		$container->registerService('Sms', function(IContainer $c) {
			return new Sms($c->query('ServerContainer')->getDb());
		});

		$container->registerService('SmsMapper', function(IContainer $c) {
			return new SmsMapper($c->query('ServerContainer')->getDb());
		});

		/**
		 * Managers
		 */
		$container->registerService('ContactsManager', function(IContainer $c) {
			return $server = $c->query('ServerContainer')->getContactsManager();
		});

		/**
		 * Controllers
		 */
		$container->registerService('SettingsController', function(IContainer $c) use($app) {
			return new SettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('ConfigMapper'),
				$app
			);
		});

		$container->registerService('SmsController', function(IContainer $c) use($app) {
			$server = $c->query('ServerContainer');
			return new SmsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('CurrentUID'),
				$c->query('SmsMapper'),
				$c->query('ConfigMapper'),
				$server->getContactsManager(),
				$server->getURLGenerator(),
				$app
			);
		});

		$container->registerService('ApiController', function(IContainer $c) use($app) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('CurrentUID'),
				$c->query('SmsMapper'),
				$app
			);
		});
	}
}
