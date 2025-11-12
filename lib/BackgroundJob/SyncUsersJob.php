<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\BackgroundJob;

use OCA\OAuthWeCom\Service\ConfigService;
use OCA\OAuthWeCom\Service\SyncService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * 定时同步用户后台任务
 * 定期从企业微信同步用户和组织架构
 */
class SyncUsersJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private ConfigService $configService,
		private SyncService $syncService,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		
		// 设置执行间隔（默认24小时）
		$interval = $this->configService->getSyncFrequency();
		$this->setInterval($interval * 3600); // 转换为秒
	}

	/**
	 * 执行任务
	 */
	protected function run($argument): void {
		try {
			// 检查是否启用了同步
			if (!$this->configService->isSyncEnabled()) {
				$this->logger->debug('User sync is disabled, skipping');
				return;
			}

			// 检查配置是否有效
			if (!$this->configService->isConfigured()) {
				$this->logger->warning('WeCom configuration is invalid, skipping sync');
				return;
			}

			$this->logger->info('Starting scheduled user sync');
			
			// 执行同步
			$result = $this->syncService->fullSync();
			
			if ($result['success']) {
				$this->logger->info('Scheduled user sync completed successfully', [
					'total_users' => $result['total_users'],
					'created_users' => $result['created_users'],
					'updated_users' => $result['updated_users'],
					'total_departments' => $result['total_departments'],
				]);
			} else {
				$this->logger->error('Scheduled user sync failed', [
					'message' => $result['message'],
					'errors' => $result['errors'],
				]);
			}
		} catch (\Exception $e) {
			$this->logger->error('Scheduled user sync exception', [
				'exception' => $e,
				'message' => $e->getMessage(),
			]);
		}
	}
}

