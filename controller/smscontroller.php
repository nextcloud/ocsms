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

namespace OCA\OcSms\Controller;


use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use OCA\OcSms\Db\SmsMapper;

class SmsController extends Controller {

    private $userId;

    public function __construct($appName, IRequest $request, $userId){
        parent::__construct($appName, $request);
        $this->userId = $userId;
    }

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
    public function index() {
        $params = array('user' => $this->userId);
        return new TemplateResponse($this->appName, 'main', $params);
    }

	/**
	 * @NoAdminRequired
	 */
	public function push ($smsCount, $smsDatas) {
		if ($smsCount != count($smsDatas)) {
			return "Error: sms count invalid";
		}

		foreach ($smsDatas as $sms) {
			if (!array_key_exists("id", $sms) || !array_key_exists("read", $sms) ||
				!array_key_exists("draft", $sms) ||
				!array_key_exists("date", $sms) || !array_key_exists("seen", $sms) ||
				!array_key_exists("body", $sms) || !array_key_exists("address", $sms)) {
				return "Error: bad SMS entry";
			}

			if (!is_numeric($sms["id"])) {
				return "Error: Invalid SMS ID";
			}

			if ($sms["read"] !== "true" && $sms["read"] !== "false") {
				return "Error: Invalid SMS Read state";
			}

			if ($sms["seen"] !== "true" && $sms["seen"] !== "false") {
				return "Error: Invalid SMS Seen state";
			}

			if ($sms["draft"] !== "true" && $sms["draft"] !== "false") {
				return "Error: Invalid SMS Draft state";
			}

			if (!is_numeric($sms["date"]) && $sms["date"] != 0 && $sms["date"] != 1) {
				return "Error: Invalid SMS date";
			}

			// @ TODO: test address and body ?
		}

		$smsMgr = new SmsMgr();
		$smsMgr->saveAll($smsDAtas);
		return "OK";
	}


}
