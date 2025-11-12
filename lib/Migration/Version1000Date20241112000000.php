<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * 创建企业微信OAuth认证插件的数据库表
 */
class Version1000Date20241112000000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// 创建用户映射表
		if (!$schema->hasTable('wecom_user_mapping')) {
			$table = $schema->createTable('wecom_user_mapping');
			
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('wecom_user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('nextcloud_uid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('mobile', Types::STRING, [
				'notnull' => false,
				'length' => 32,
				'default' => '',
			]);
			$table->addColumn('department_ids', Types::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('created_at', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('updated_at', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);
			$table->addColumn('last_login_at', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['wecom_user_id'], 'wecom_um_wecom_uid');
			$table->addUniqueIndex(['nextcloud_uid'], 'wecom_um_nc_uid');
			$table->addIndex(['email'], 'wecom_um_email');
			$table->addIndex(['mobile'], 'wecom_um_mobile');
		}

		// 创建审计日志表
		if (!$schema->hasTable('wecom_audit_logs')) {
			$table = $schema->createTable('wecom_audit_logs');
			
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('wecom_user_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('nextcloud_uid', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('action', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('status', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('ip_address', Types::STRING, [
				'notnull' => false,
				'length' => 45,
				'default' => '',
			]);
			$table->addColumn('user_agent', Types::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('message', Types::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('details', Types::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
			$table->addColumn('created_at', Types::BIGINT, [
				'notnull' => true,
				'default' => 0,
			]);

			$table->setPrimaryKey(['id']);
			$table->addIndex(['action'], 'wecom_al_action');
			$table->addIndex(['nextcloud_uid'], 'wecom_al_nc_uid');
			$table->addIndex(['wecom_user_id'], 'wecom_al_wecom_uid');
			$table->addIndex(['created_at'], 'wecom_al_created');
			$table->addIndex(['status'], 'wecom_al_status');
		}

		return $schema;
	}
}

