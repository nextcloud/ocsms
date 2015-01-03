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

$application = new OcSmsApp();

$application->registerRoutes($this, array('routes' => array(
	array('name' => 'sms#index', 'url' => '/', 'verb' => 'GET'),
	array('name' => 'sms#push', 'url' => '/push', 'verb' => 'POST'),
	array('name' => 'sms#replace', 'url' => '/replace', 'verb' => 'POST'),
	array('name' => 'sms#retrieve_all_ids', 'url' => '/get/smsidlist', 'verb' => 'GET'),
	array('name' => 'sms#retrieve_all_ids_with_status', 'url' => '/get/smsidstate', 'verb' => 'GET'),
	array('name' => 'sms#retrieve_last_timestamp', 'url' => '/get/lastmsgtime', 'verb' => 'GET'),
	array('name' => 'sms#retrieve_all_peers', 'url' => '/get/peerlist', 'verb' => 'GET'),
	array('name' => 'sms#get_conversation', 'url' => '/get/conversation', 'verb' => 'GET'),
	array('name' => 'sms#check_new_messages', 'url' => '/get/new_messages', 'verb' => 'GET'),
	array('name' => 'sms#get_api_version', 'url' => '/get/apiversion', 'verb' => 'GET'),
)));
