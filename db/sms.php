<?php
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
