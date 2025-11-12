<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Service;

use OCA\OAuthWeCom\Db\AuditLog;
use OCA\OAuthWeCom\Db\AuditLogMapper;
use OCP\IRequest;

/**
 * 审计日志服务
 * 提供统一的日志记录和查询接口
 */
class AuditService {
	// 事件类型常量
	public const EVENT_LOGIN = 'login';
	public const EVENT_LOGOUT = 'logout';
	public const EVENT_OAUTH_AUTH = 'oauth_authorization';
	public const EVENT_OAUTH_CALLBACK = 'oauth_callback';
	public const EVENT_USER_SYNC = 'user_sync';
	public const EVENT_USER_CREATE = 'user_create';
	public const EVENT_USER_UPDATE = 'user_update';
	public const EVENT_CONFIG_CHANGE = 'config_change';

	// 状态常量
	public const STATUS_SUCCESS = 'success';
	public const STATUS_FAILED = 'failed';
	public const STATUS_WARNING = 'warning';

	public function __construct(
		private AuditLogMapper $auditLogMapper,
		private IRequest $request,
	) {
	}

	/**
	 * 创建审计日志
	 */
	private function createLog(
		string $action,
		string $status,
		?string $nextcloudUid = null,
		?string $wecomUserId = null,
		?string $message = null,
		?array $details = null
	): AuditLog {
		$log = new AuditLog();
		$log->setAction($action);
		$log->setStatus($status);
		$log->setNextcloudUid($nextcloudUid ?? '');
		$log->setWecomUserId($wecomUserId ?? '');
		$log->setIpAddress($this->request->getRemoteAddress());
		$log->setUserAgent($this->request->getHeader('User-Agent'));
		$log->setMessage($message ?? '');
		$log->setDetails($details ? json_encode($details) : '');
		$log->setCreatedAt(time());
		
		return $this->auditLogMapper->insert($log);
	}

	/**
	 * 记录登录事件
	 */
	public function logLogin(
		string $userId,
		string $wecomUserId,
		bool $success,
		?string $message = null
	): AuditLog {
		return $this->createLog(
			self::EVENT_LOGIN,
			$success ? self::STATUS_SUCCESS : self::STATUS_FAILED,
			$userId,
			$wecomUserId,
			$message
		);
	}

	/**
	 * 记录OAuth授权事件
	 */
	public function logOAuthAuthorization(
		?string $userId = null,
		?string $wecomUserId = null,
		bool $success = true,
		?string $message = null,
		?array $details = null
	): AuditLog {
		return $this->createLog(
			self::EVENT_OAUTH_AUTH,
			$success ? self::STATUS_SUCCESS : self::STATUS_FAILED,
			$userId,
			$wecomUserId,
			$message,
			$details
		);
	}

	/**
	 * 记录OAuth回调事件
	 */
	public function logOAuthCallback(
		?string $userId = null,
		?string $wecomUserId = null,
		bool $success = true,
		?string $message = null,
		?array $details = null
	): AuditLog {
		return $this->createLog(
			self::EVENT_OAUTH_CALLBACK,
			$success ? self::STATUS_SUCCESS : self::STATUS_FAILED,
			$userId,
			$wecomUserId,
			$message,
			$details
		);
	}

	/**
	 * 记录用户同步事件
	 */
	public function logUserSync(
		bool $success,
		string $message,
		?array $details = null
	): AuditLog {
		return $this->createLog(
			self::EVENT_USER_SYNC,
			$success ? self::STATUS_SUCCESS : self::STATUS_FAILED,
			null,
			null,
			$message,
			$details
		);
	}

	/**
	 * 记录用户创建事件
	 */
	public function logUserCreate(
		string $userId,
		string $wecomUserId,
		bool $success,
		?string $message = null
	): AuditLog {
		return $this->createLog(
			self::EVENT_USER_CREATE,
			$success ? self::STATUS_SUCCESS : self::STATUS_FAILED,
			$userId,
			$wecomUserId,
			$message
		);
	}

	/**
	 * 记录配置更改事件
	 */
	public function logConfigChange(
		string $userId,
		string $message,
		?array $details = null
	): AuditLog {
		return $this->createLog(
			self::EVENT_CONFIG_CHANGE,
			self::STATUS_SUCCESS,
			$userId,
			null,
			$message,
			$details
		);
	}

	/**
	 * 获取最近的日志
	 */
	public function getRecentLogs(int $limit = 100): array {
		$logs = $this->auditLogMapper->findRecent($limit);
		return $this->formatLogs($logs);
	}

	/**
	 * 根据用户ID获取日志
	 */
	public function getLogsByUser(string $userId, int $limit = 100): array {
		$logs = $this->auditLogMapper->findByNextcloudUid($userId, $limit);
		return $this->formatLogs($logs);
	}

	/**
	 * 根据事件类型获取日志
	 */
	public function getLogsByAction(string $action, int $limit = 100): array {
		$logs = $this->auditLogMapper->findByAction($action, $limit);
		return $this->formatLogs($logs);
	}

	/**
	 * 根据时间范围获取日志
	 */
	public function getLogsByTimeRange(int $startTime, int $endTime, int $limit = 100): array {
		$logs = $this->auditLogMapper->findByTimeRange($startTime, $endTime, $limit);
		return $this->formatLogs($logs);
	}

	/**
	 * 根据状态获取日志
	 */
	public function getLogsByStatus(string $status, int $limit = 100): array {
		$logs = $this->auditLogMapper->findByStatus($status, $limit);
		return $this->formatLogs($logs);
	}

	/**
	 * 清理旧日志
	 */
	public function cleanOldLogs(int $daysToKeep = 90): int {
		$timestamp = time() - ($daysToKeep * 24 * 3600);
		return $this->auditLogMapper->deleteOlderThan($timestamp);
	}

	/**
	 * 格式化日志数组
	 */
	private function formatLogs(array $logs): array {
		return array_map(function (AuditLog $log) {
			$data = [
				'id' => $log->getId(),
				'action' => $log->getAction(),
				'status' => $log->getStatus(),
				'nextcloud_uid' => $log->getNextcloudUid(),
				'wecom_user_id' => $log->getWecomUserId(),
				'ip_address' => $log->getIpAddress(),
				'user_agent' => $log->getUserAgent(),
				'message' => $log->getMessage(),
				'created_at' => $log->getCreatedAt(),
			];

			// 解析details
			$details = $log->getDetails();
			if (!empty($details)) {
				$data['details'] = json_decode($details, true);
			}

			return $data;
		}, $logs);
	}
}

