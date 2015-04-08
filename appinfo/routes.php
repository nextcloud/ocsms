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
	array('name' => 'api#push', 'url' => '/push', 'verb' => 'POST'), // Android API
	array('name' => 'api#replace', 'url' => '/replace', 'verb' => 'POST'), // Android API
	array('name' => 'api#retrieve_all_ids', 'url' => '/get/smsidlist', 'verb' => 'GET'), // Android APIv1
	array('name' => 'api#retrieve_all_ids_with_status', 'url' => '/get/smsidstate', 'verb' => 'GET'), // Android APIv1
	array('name' => 'api#retrieve_last_timestamp', 'url' => '/get/lastmsgtime', 'verb' => 'GET'), // Android APIv1
	array('name' => 'sms#retrieve_all_peers', 'url' => '/get/peerlist', 'verb' => 'GET'),
	array('name' => 'sms#get_conversation', 'url' => '/get/conversation', 'verb' => 'GET'),
	array('name' => 'sms#check_new_messages', 'url' => '/get/new_messages', 'verb' => 'GET'),
	array('name' => 'api#get_api_version', 'url' => '/get/apiversion', 'verb' => 'GET'), // Android APIv1
	array('name' => 'sms#set_country', 'url'=> '/set/country', 'verb' => 'POST'),
	array('name' => 'sms#get_country', 'url'=> '/get/country', 'verb' => 'GET'),
	array('name' => 'api#get_phones_sms_number', 'url' => 'get/phones/smsnumber', 'verb' => 'GET'), // Android APIv2
)));
