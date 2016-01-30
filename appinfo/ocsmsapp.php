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

use \OCA\OcSms\Controller\ApiController;
use \OCA\OcSms\Controller\SmsController;

use \OCA\OcSms\Db\Sms;
use \OCA\OcSms\Db\SmsMapper;

use \OCA\OcSms\Db\ConfigMapper;

use \OCA\OcSms\Lib\PhoneNumberFormatter;

class OcSmsApp extends App {

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
		 * Managers
		 */
		$container->registerService('ContactsManager', function($c) {
			return $c->getServer()->getContactsManager();
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
				$c->query('ContactsManager'),
				$c->query('ServerContainer')->getURLGenerator(),
				$app
			);
		});

		$container->registerService('ApiController', function($c) use($app) {
			return new ApiController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('UserId'),
				$c->query('SmsMapper'),
				$app
			);
		});
	}
}
