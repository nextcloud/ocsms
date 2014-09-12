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
	public function push($smsCount, $smsDatas) {
		if ($smsCount != count($smsDatas)) {
			return "Error: sms count invalid";
		}
		return array("test" => "test2");
	}


}
