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


namespace OCA\OcSms\Migration;

use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

use \OCA\OcSms\Db\ConversationStateMapper;

class FixConversationReadStates implements IRepairStep {

	private $userManager;
	private $convStateMapper;

	/**
	 * FixConversationReadStates constructor.
	 *
	 * @param IUserManager $userManager
	 */
	public function __construct(ConversationStateMapper $mapper, IUserManager $userManager) {
		$this->userManager = $userManager;
		$this->convStateMapper = $mapper;
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Migrate legacy conversation reading states';
	}

	/**
	 * @inheritdoc
	 */
	public function run(IOutput $output) {

		$output->startProgress();
		$output->advance(1, "Migrate states");
		$this->convStateMapper->migrate();
		$output->finishProgress();
	}
}
