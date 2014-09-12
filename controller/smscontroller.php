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
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCA\OcSms\Db\SmsMapper;

class SmsController extends Controller {

    private $userId;
    private $smsMapper;
    // TMP
    private $errorMsg;

    public function __construct($appName, IRequest $request, $userId, SmsMapper $mapper){
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->smsMapper = $mapper;
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
		if ($this->checkPushStructure($smsCount, $smsDatas) === false) {
			return new JSONResponse(array("status" => false, "msg" => $this->errorMsg));
		}

		$this->smsMapper->saveAll($this->userId, $smsDatas);
		return new JSONResponse(array("status" => true, "msg" => "OK"));
	}

	private function checkPushStructure($smsCount, $smsDatas) {
		if ($smsCount != count($smsDatas)) {
			$this->errorMsg = "Error: sms count invalid";
			return false;
		}

		foreach ($smsDatas as &$sms) {
			if (!array_key_exists("id", $sms) || !array_key_exists("read", $sms) ||
				!array_key_exists("draft", $sms) ||
				!array_key_exists("date", $sms) || !array_key_exists("seen", $sms) ||
				!array_key_exists("body", $sms) || !array_key_exists("address", $sms)) {
				$this->errorMsg = "Error: bad SMS entry";
				return false;
			}

			if (!is_numeric($sms["id"])) {
				$this->errorMsg = "Error: Invalid SMS ID";
				return false;
			}

			if ($sms["read"] !== "true" && $sms["read"] !== "false") {
				$this->errorMsg = "Error: Invalid SMS Read state";
				return false;
			}

			if ($sms["seen"] !== "true" && $sms["seen"] !== "false") {
				$this->errorMsg = "Error: Invalid SMS Seen state";
				return false;
			}

			if ($sms["draft"] !== "true" && $sms["draft"] !== "false") {
				$this->errorMsg = "Error: Invalid SMS Draft state";
				return false;
			}

			if (!is_numeric($sms["date"]) && $sms["date"] != 0 && $sms["date"] != 1) {
				$this->errorMsg = "Error: Invalid SMS date";
				return false;
			}

			// @ TODO: test address and body ?
		}
		return true;
	}


}
