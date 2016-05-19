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

namespace OCA\OcSms\Controller;


use \OCP\IRequest;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http;

use \OCA\OcSms\Db\ConfigMapper;

use \OCA\OcSms\Lib\CountryCodes;

class SettingsController extends Controller {

	private $configMapper;

	public function __construct ($appName, IRequest $request, ConfigMapper $cfgMapper){
		parent::__construct($appName, $request);
		$this->configMapper = $cfgMapper;
	}

	/**
	* @NoAdminRequired
	*/
	function getSettings() {
		$country = $this->configMapper->getKey("country");
		if ($country === false) {
			return new JSONResponse(array("status" => false));
		}

		return new JSONResponse(array("status" => true,
			"country" => $country,
			"message_limit" => $this->configMapper->getMessageLimit(),
			"notification_state" => $this->configMapper->getNotificationState()
		));
	}

	/**
	 * @NoAdminRequired
	 * @param $country
	 * @return JSONResponse
	 */
	function setCountry($country) {
		if (!array_key_exists($country, CountryCodes::$codes)) {
			return new JSONResponse(array("status" => false, "msg" => "Invalid country"));
		}
		$this->configMapper->set("country", $country);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}

	/**
	 * @NoAdminRequired
	 * @param $limit
	 * @return JSONResponse
	 */
	function setMessageLimit($limit) {
		$this->configMapper->set("message_limit", $limit);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}

	/**
	 * @NoAdminRequired
	 * @param $notification
	 * @return JSONResponse
	 */
	function setNotificationState($notification) {
		if (!is_numeric($notification) || $notification < 0 || $notification > 2) {
			return new JSONResponse(array("status" => false, "msg" => "Invalid notification state"));
		}
		$this->configMapper->set("notification_state", $notification);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}
}
