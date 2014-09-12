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

    public function push($json_datas) {
		var_dump($json_datas);
		return array("test" => "test2");
	}


}
