<?php
/**
 * Nextcloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2017
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
			"notification_state" => $this->configMapper->getNotificationState(),
			"contact_order" => $this->configMapper->getContactOrder(),
			"contact_order_reverse" => $this->configMapper->getContactOrderReverse(),
		));
	}

	/**
	 * @NoAdminRequired
	 * @param $country
	 * @return JSONResponse
	 */
	function setCountry($country) {
		if (!array_key_exists($country, CountryCodes::$codes)) {
			return new JSONResponse(array("status" => false, "msg" => "Invalid country"), Http::STATUS_BAD_REQUEST);
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
			return new JSONResponse(array("status" => false, "msg" => "Invalid notification state"), Http::STATUS_BAD_REQUEST);
		}
		$this->configMapper->set("notification_state", $notification);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}

	/**
	 * @NoAdminRequired
	 * @param $attribute
	 * @param $reverse
	 * @return JSONResponse
	 */
	function setContactOrder($attribute, $reverse) {
		if (!in_array($reverse, ['true','false']) || !in_array($attribute, ['lastmsg','label'])) {
			return new JSONResponse(array("status" => false, "msg" => "Invalid contact ordering"), Http::STATUS_BAD_REQUEST);
		}
		$this->configMapper->set("contact_order", $attribute);
		$this->configMapper->set("contact_order_reverse", $reverse);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}
}
