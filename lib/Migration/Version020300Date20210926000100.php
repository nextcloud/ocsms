<?php

/**
 * @copyright Copyright (c) 2021, Phone Sync for Nextcloud (github.com/nextcloud/ocsms)
 * 
 * @author Bogdan Popescu <github.com/floss4good>
 * 
 * @license GNU AGPL version 3 or any later version
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace OCA\Ocsms\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Phone Sync (ocsms) migration that will
 * migrate data for some renamed tables and rename some indexes.
 */
class Version020300Date20210926000100 extends SimpleMigrationStep {
	/** @var IDBConnection */
	private $connection;

	/** @var bool */
	private $conversationReadStatesMigrated = false;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @inheritdoc
	 */
	public function name(): string {
		return 'Migrate data for some renamed tables';
	}

	/**
	 * @inheritdoc
	 */
	public function description(): string {
		return 'Move data from `ocsms_conversation_read_states` and `ocsms_sendmessage_queue`'
		. ' to `ocsms_conv_r_states` and `ocsms_sendmess_queue`, respectively, and rename some indexes.';
	}

	/**
	 * Try to copy data from `ocsms_conversation_read_states` and `ocsms_sendmessage_queue` legacy tables
	 * to `ocsms_conv_r_states` and `ocsms_sendmess_queue`, respectively, new tables.
	 *
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($this->canMigrateConversationReadStates($output, $schema)) {
			$output->info('Copying data from `ocsms_conversation_read_states` to `ocsms_conv_r_states`.');
			$qbInsert = $this->connection->getQueryBuilder();
			$qbInsert->insert('ocsms_conv_r_states')
				->setValue('user_id', $qbInsert->createParameter('user_id'))
				->setValue('phone_number', $qbInsert->createParameter('phone_number'))
				->setValue('int_date', $qbInsert->createParameter('int_date'));


			$qbSelect = $this->connection->getQueryBuilder();
			$qbSelect->select('user_id', 'phone_number', 'int_date')
				->from('ocsms_conversation_read_states');

			$resultStmt = $qbSelect->execute();
			while ($row = $resultStmt->fetch()) {
				$qbInsert
					->setParameter('user_id', $row['user_id'], IQueryBuilder::PARAM_STR)
					->setParameter('phone_number', $row['phone_number'], IQueryBuilder::PARAM_STR)
					->setParameter('int_date', $row['int_date'], IQueryBuilder::PARAM_INT);
				$qbInsert->execute();
			}
			$resultStmt->closeCursor();

			$this->conversationReadStatesMigrated = true;
		} else {
			$output->info('Conversation read states were not migrated.');
		}

		if ($this->canMigrateSendMessageQueue($output, $schema)) {
			$output->info('Copying data from `ocsms_sendmessage_queue` to `ocsms_sendmess_queue`.');
			$qbInsert = $this->connection->getQueryBuilder();
			$qbInsert->insert('ocsms_sendmess_queue')
				->setValue('user_id', $qbInsert->createParameter('user_id'))
				->setValue('sms_address', $qbInsert->createParameter('sms_address'))
				->setValue('sms_msg', $qbInsert->createParameter('sms_msg'));

			$qbSelect = $this->connection->getQueryBuilder();
			$qbSelect->select('user_id', 'sms_address', 'sms_msg')
				->from('ocsms_sendmessage_queue');

			$resultStmt = $qbSelect->execute();
			while ($row = $resultStmt->fetch()) {
				$qbInsert
					->setParameter('user_id', $row['user_id'], IQueryBuilder::PARAM_STR)
					->setParameter('sms_address', $row['sms_address'], IQueryBuilder::PARAM_STR)
					->setParameter('sms_msg', $row['sms_msg'], IQueryBuilder::PARAM_STR);
				$qbInsert->execute();
			}
			$resultStmt->closeCursor();
		}
	}

	/**
	 * Perform the following DB schema changes:
	 * - Drop `ocsms_conversation_read_states` legacy table, if data successfully copied to new table.
	 * - Drop `ocsms_sendmessage_queue` legacy table, if found.
	 * - Rename (DROP + ADD) some indexes from `ocsms_smsdatas` table:
	 *   - `smsdata_user_mailbox` -> `smsdata_user_mbox`
	 *   - `smsdata_user_mailbox_date` -> `smsdata_user_mbox_date`
	 *   - `smsdata_user_mailbox_address` -> `smsdata_user_mbox_addr`
	 *   - `smsdata_user_mailbox_address_date` -> `smsdata_user_mbox_addr_date`
	 *
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($this->conversationReadStatesMigrated) {
			if ($schema->hasTable('ocsms_conversation_read_states')) {
				$output->info('Dropping `ocsms_conversation_read_states` legacy table.');
				$schema->dropTable('ocsms_conversation_read_states');
			}
		}

		if ($schema->hasTable('ocsms_sendmessage_queue')) {
			$output->info('Dropping `ocsms_sendmessage_queue` legacy table.');
			$schema->dropTable('ocsms_sendmessage_queue');
		}

		$table = $schema->getTable('ocsms_smsdatas');

		if ($table->hasIndex('smsdata_user_mailbox')) {
			$table->dropIndex('smsdata_user_mailbox');
		}
		if (!$table->hasIndex('smsdata_user_mbox')) {
			$table->addIndex(['user_id', 'sms_mailbox'], 'smsdata_user_mbox');
		}

		if ($table->hasIndex('smsdata_user_mailbox_date')) {
			$table->dropIndex('smsdata_user_mailbox_date');
		}
		if (!$table->hasIndex('smsdata_user_mbox_date')) {
			$table->addIndex(['user_id', 'sms_mailbox', 'sms_date'], 'smsdata_user_mbox_date');
		}

		if ($table->hasIndex('smsdata_user_mailbox_address')) {
			$table->dropIndex('smsdata_user_mailbox_address');
		}
		if (!$table->hasIndex('smsdata_user_mbox_addr')) {
			$table->addIndex(['user_id', 'sms_mailbox', 'sms_address'], 'smsdata_user_mbox_addr');
		}

		if ($table->hasIndex('smsdata_user_mailbox_address_date')) {
			$table->dropIndex('smsdata_user_mailbox_address_date');
		}
		if (!$table->hasIndex('smsdata_user_mbox_addr_date')) {
			$table->addIndex(['user_id', 'sms_mailbox', 'sms_address', 'sms_date'], 'smsdata_user_mbox_addr_date');
		}

		return $schema;
	}

	/**
	 * Checks if the rows from `ocsms_conversation_read_states` legacy table
	 * can be migrated to the new table (both tables should exist for this
	 * and new table should be empty).
	 *
	 * @param IOutput $output
	 * @param ISchemaWrapper $schema
	 * @return bool True if both legacy and new tables exist and new one is empty, false otherwise
	 */
	private function canMigrateConversationReadStates(IOutput $output, ISchemaWrapper $schema): bool {
		if (!$schema->hasTable('ocsms_conversation_read_states')) {
			$output->info('The `ocsms_conversation_read_states` legacy table was not found.');
			return false;
		}
		if (!$schema->hasTable('ocsms_conv_r_states')) {
			$output->warning('The `ocsms_conv_r_states` table does not exists!');
			return false;
		}

		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('user_id', 'count'))
			->from('ocsms_conv_r_states');
		$rowsCount = $qb->execute()->fetchColumn(0);
		
		if ($rowsCount > 0) {
			$output->info('The `ocsms_conv_r_states` table is not empty.');
			return false;
		}

		return true;
	}

	/**
	 * Checks if the rows from `ocsms_sendmessage_queue` legacy table
	 * can be migrated to the new table (both tables should exist for this).
	 *
	 * @param IOutput $output
	 * @param ISchemaWrapper $schema
	 * @return bool True if both legacy and new tables exist, false otherwise
	 */
	private function canMigrateSendMessageQueue(IOutput $output, ISchemaWrapper $schema): bool {
		if (!$schema->hasTable('ocsms_sendmessage_queue')) {
			$output->info('The `ocsms_sendmessage_queue` legacy table was not found.');
			return false;
		}
		if (!$schema->hasTable('ocsms_sendmess_queue')) {
			$output->warning('The `ocsms_sendmess_queue` table does not exists!');
			return false;
		}

		return true;
	}
}
