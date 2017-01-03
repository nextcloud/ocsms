<?php
/**
 * NextCloud - Phone Sync
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Loic Blot <loic.blot@unix-experience.fr>
 * @copyright Loic Blot 2014-2017
 */

namespace OCA\OcSms\Db;

use \OCP\AppFramework\Db\Entity;

class Sms extends Entity {

    protected $userId;
    protected $added;
    protected $lastmodified;
    protected $smsRead;
    protected $smsSeen;
    protected $smsDate;
    protected $smsDraft;
    protected $smsId;
    protected $smsAddress;
    protected $smsMsg;

    public function __construct () {
        $this->addType('smsId', 'integer');
        $this->addType('smsDraft', 'boolean');
        $this->addType('smsRead', 'boolean');
        $this->addType('smsSeen', 'boolean');
    }
}
?>
