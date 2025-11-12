<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<UserMapping>
 */
class UserMappingMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'wecom_user_mapping', UserMapping::class);
	}

	/**
	 * 根据企业微信用户ID查找映射
	 *
	 * @param string $wecomUserId
	 * @return UserMapping
	 * @throws DoesNotExistException
	 */
	public function findByWecomUserId(string $wecomUserId): UserMapping {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('wecom_user_id', $qb->createNamedParameter($wecomUserId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	/**
	 * 根据 NextCloud 用户 ID 查找映射
	 *
	 * @param string $nextcloudUid
	 * @return UserMapping
	 * @throws DoesNotExistException
	 */
	public function findByNextcloudUid(string $nextcloudUid): UserMapping {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('nextcloud_uid', $qb->createNamedParameter($nextcloudUid, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	/**
	 * 根据邮箱查找映射
	 *
	 * @param string $email
	 * @return UserMapping
	 * @throws DoesNotExistException
	 */
	public function findByEmail(string $email): UserMapping {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('email', $qb->createNamedParameter($email, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	/**
	 * 根据手机号查找映射
	 *
	 * @param string $mobile
	 * @return UserMapping
	 * @throws DoesNotExistException
	 */
	public function findByMobile(string $mobile): UserMapping {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('mobile', $qb->createNamedParameter($mobile, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	/**
	 * 获取所有映射
	 *
	 * @return UserMapping[]
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->orderBy('updated_at', 'DESC');

		return $this->findEntities($qb);
	}

	/**
	 * 根据部门ID查找用户映射
	 *
	 * @param int $departmentId
	 * @return UserMapping[]
	 */
	public function findByDepartmentId(int $departmentId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->like('department_ids', $qb->createNamedParameter('%,' . $departmentId . ',%', IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * 更新最后登录时间
	 *
	 * @param string $wecomUserId
	 * @param int $timestamp
	 * @return void
	 */
	public function updateLastLoginAt(string $wecomUserId, int $timestamp): void {
		$qb = $this->db->getQueryBuilder();

		$qb->update($this->getTableName())
			->set('last_login_at', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT))
			->where(
				$qb->expr()->eq('wecom_user_id', $qb->createNamedParameter($wecomUserId, IQueryBuilder::PARAM_STR))
			);

		$qb->executeStatement();
	}

	/**
	 * 删除指定企业微信用户的映射
	 *
	 * @param string $wecomUserId
	 * @return void
	 */
	public function deleteByWecomUserId(string $wecomUserId): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('wecom_user_id', $qb->createNamedParameter($wecomUserId, IQueryBuilder::PARAM_STR))
			);

		$qb->executeStatement();
	}
}
