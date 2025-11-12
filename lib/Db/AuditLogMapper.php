<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<AuditLog>
 */
class AuditLogMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'wecom_audit_logs', AuditLog::class);
	}

	/**
	 * 获取最近的日志
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return AuditLog[]
	 */
	public function findRecent(int $limit = 50, int $offset = 0): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		return $this->findEntities($qb);
	}

	/**
	 * 根据企业微信用户ID查找日志
	 *
	 * @param string $wecomUserId
	 * @param int $limit
	 * @return AuditLog[]
	 */
	public function findByWecomUserId(string $wecomUserId, int $limit = 50): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('wecom_user_id', $qb->createNamedParameter($wecomUserId, IQueryBuilder::PARAM_STR))
			)
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * 根据 NextCloud 用户 ID 查找日志
	 *
	 * @param string $nextcloudUid
	 * @param int $limit
	 * @return AuditLog[]
	 */
	public function findByNextcloudUid(string $nextcloudUid, int $limit = 50): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('nextcloud_uid', $qb->createNamedParameter($nextcloudUid, IQueryBuilder::PARAM_STR))
			)
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * 根据操作类型查找日志
	 *
	 * @param string $action
	 * @param int $limit
	 * @return AuditLog[]
	 */
	public function findByAction(string $action, int $limit = 50): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('action', $qb->createNamedParameter($action, IQueryBuilder::PARAM_STR))
			)
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * 根据状态查找日志
	 *
	 * @param string $status
	 * @param int $limit
	 * @return AuditLog[]
	 */
	public function findByStatus(string $status, int $limit = 50): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('status', $qb->createNamedParameter($status, IQueryBuilder::PARAM_STR))
			)
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * 根据时间范围查找日志
	 *
	 * @param int $startTime
	 * @param int $endTime
	 * @param int $limit
	 * @return AuditLog[]
	 */
	public function findByTimeRange(int $startTime, int $endTime, int $limit = 100): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->gte('created_at', $qb->createNamedParameter($startTime, IQueryBuilder::PARAM_INT))
			)
			->andWhere(
				$qb->expr()->lte('created_at', $qb->createNamedParameter($endTime, IQueryBuilder::PARAM_INT))
			)
			->orderBy('created_at', 'DESC')
			->setMaxResults($limit);

		return $this->findEntities($qb);
	}

	/**
	 * 获取日志总数
	 *
	 * @return int
	 */
	public function count(): int {
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->count('*', 'count'))
			->from($this->getTableName());

		$result = $qb->executeQuery();
		$count = $result->fetchOne();
		$result->closeCursor();

		return (int)$count;
	}

	/**
	 * 删除指定时间之前的日志
	 *
	 * @param int $timestamp
	 * @return int 删除的行数
	 */
	public function deleteOlderThan(int $timestamp): int {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->lt('created_at', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT))
			);

		return $qb->executeStatement();
	}
}
