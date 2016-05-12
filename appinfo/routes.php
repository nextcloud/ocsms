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

$application = new OcSmsApp();

$application->registerRoutes($this, array('routes' => array(
	array('name' => 'sms#index', 'url' => '/', 'verb' => 'GET'),
	array('name' => 'sms#retrieve_all_peers', 'url' => '/get/peerlist', 'verb' => 'GET'),
	array('name' => 'sms#get_conversation', 'url' => '/get/conversation', 'verb' => 'GET'),
	array('name' => 'sms#delete_conversation', 'url' => '/delete/conversation', 'verb' => 'POST'),
	array('name' => 'sms#check_new_messages', 'url' => '/get/new_messages', 'verb' => 'GET'),
	array('name' => 'sms#delete_message', 'url' => '/delete/message', 'verb' => 'POST'),

	array('name' => 'settings#set_country', 'url'=> '/set/country', 'verb' => 'POST'),
	array('name' => 'settings#get_settings', 'url'=> '/get/settings', 'verb' => 'GET'),
	array('name' => 'settings#set_messagelimit', 'url'=> '/set/msglimit', 'verb' => 'POST'),
	array('name' => 'settings#set_notification_state', 'url'=> '/set/notification_state', 'verb' => 'POST'),

	// Android API v1 doesn't have a version in the URL, be careful
	array('name' => 'api#get_api_version', 'url' => '/get/apiversion', 'verb' => 'GET'), // Android APIv1
	array('name' => 'api#push', 'url' => '/push', 'verb' => 'POST'), // Android API
	array('name' => 'api#replace', 'url' => '/replace', 'verb' => 'POST'), // Android API
	array('name' => 'api#retrieve_all_ids', 'url' => '/get/smsidlist', 'verb' => 'GET'), // Android APIv1
	array('name' => 'api#retrieve_all_ids_with_status', 'url' => '/get/smsidstate', 'verb' => 'GET'), // Android APIv1
	array('name' => 'api#retrieve_last_timestamp', 'url' => '/get/lastmsgtime', 'verb' => 'GET'), // Android APIv1

	// API v2
	array('name' => 'api#get_all_stored_phone_numbers', 'url' => '/api/v2/get/phones/numberlist', 'verb' => 'GET'), // Android APIv2
	array('name' => 'api#fetch_messages', 'url' => '/api/v2/messages/{start}/{limit}', 'verb' => 'GET'), // Android APIv2
	array('name' => 'api#fetch_messages_for_number', 'url' => '/api/v2/messages/{phonenumber}/{start}/{limit}', 'verb' => 'GET'), // Android APIv2
)));
