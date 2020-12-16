<?php

declare(strict_types=1);

namespace OCA\Ocsms\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version020109Date20201216203338 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ocsms_user_datas')) {
			$table = $schema->createTable('ocsms_user_datas');
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('datakey', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('datavalue', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addIndex(['user_id', 'datakey'], 'user_datas_user_datakey');
		}

		if (!$schema->hasTable('ocsms_smsdatas')) {
			$table = $schema->createTable('ocsms_smsdatas');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 10,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('added', 'datetime', [
				'notnull' => true,
				'default' => '1970-01-01 00:00:00',
			]);
			$table->addColumn('lastmodified', 'datetime', [
				'notnull' => true,
				'default' => '1970-01-01 00:00:00',
			]);
			$table->addColumn('sms_id', 'bigint', [
				'notnull' => true,
				'length' => 5,
			]);
			$table->addColumn('sms_address', 'string', [
				'notnull' => true,
				'length' => 512,
			]);
			$table->addColumn('sms_msg', 'string', [
				'notnull' => true,
				'length' => 10240,
			]);
			$table->addColumn('sms_date', 'bigint', [
				'notnull' => true,
				'length' => 10,
			]);
			$table->addColumn('sms_flags', 'string', [
				'notnull' => true,
				'length' => 2,
				'default' => '00',
			]);
			$table->addColumn('sms_mailbox', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->addColumn('sms_type', 'smallint', [
				'notnull' => true,
				'length' => 1,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id', 'sms_mailbox'], 'smsdata_user_mailbox');
			$table->addIndex(['user_id', 'sms_id'], 'smsdata_user_smsid');
			$table->addIndex(['user_id', 'sms_mailbox', 'sms_date'], 'smsdata_user_mailbox_date');
			$table->addIndex(['user_id', 'sms_mailbox', 'sms_address'], 'smsdata_user_mailbox_address');
			$table->addIndex(['user_id', 'sms_mailbox', 'sms_address', 'sms_date'], 'smsdata_user_mailbox_address_date');
		}

		if (!$schema->hasTable('ocsms_sendmessage_queue')) {
			$table = $schema->createTable('ocsms_sendmessage_queue');
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 10,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('sms_address', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('sms_msg', 'string', [
				'notnull' => true,
				'length' => 2048,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('ocsms_conversation_read_states')) {
			$table = $schema->createTable('ocsms_conversation_read_states');
			$table->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('phone_number', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('int_date', 'bigint', [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addIndex(['user_id', 'phone_number'], 'sms_conversation_rs_pkey');
		}

		if (!$schema->hasTable('ocsms_config')) {
			$table = $schema->createTable('ocsms_config');
			$table->addColumn('user', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('key', 'string', [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('value', 'string', [
				'notnull' => false,
				'length' => 10240,
			]);
			$table->addIndex(['user', 'key'], 'config_user_key');
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
