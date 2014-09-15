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

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
$application = new Application();

$application->registerRoutes($this, array('routes' => array(
	array('name' => 'sms#index', 'url' => '/', 'verb' => 'GET'),
	array('name' => 'sms#push', 'url' => '/push', 'verb' => 'POST'),
	array('name' => 'sms#retrieve_all_ids', 'url' => '/get/ids/all', 'verb' => 'GET'),
)));
