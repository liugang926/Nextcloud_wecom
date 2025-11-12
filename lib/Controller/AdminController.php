<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Controller;

use OCA\OAuthWeCom\Service\ConfigService;
use OCA\OAuthWeCom\Service\SyncService;
use OCA\OAuthWeCom\Service\WeComApiService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class AdminController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private ConfigService $configService,
		private WeComApiService $weComApiService,
		private SyncService $syncService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * 获取配置
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function getConfig(): DataResponse {
		try {
			return new DataResponse([
				'status' => 'success',
				'data' => $this->configService->getAllConfig(),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to get config: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse([
				'status' => 'error',
				'message' => $e->getMessage(),
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * 保存配置
	 *
	 * @param string $corpId
	 * @param string $agentId
	 * @param string $appSecret
	 * @param bool $enabled
	 * @param bool $forceLogin
	 * @param bool $autoCreateUser
	 * @param bool $syncEnabled
	 * @param int $syncFrequency
	 * @param array $userMatchFields
	 * @param string $defaultQuota
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function saveConfig(
		string $corpId = '',
		string $agentId = '',
		string $appSecret = '',
		bool $enabled = false,
		bool $forceLogin = false,
		bool $autoCreateUser = true,
		bool $syncEnabled = false,
		int $syncFrequency = 24,
		array $userMatchFields = ['email'],
		string $defaultQuota = 'default',
	): DataResponse {
		try {
			// 基本配置
			if (!empty($corpId)) {
				$this->configService->setCorpId($corpId);
			}
			if (!empty($agentId)) {
				$this->configService->setAgentId($agentId);
			}
			if (!empty($appSecret) && $appSecret !== '********') {
				$this->configService->setAppSecret($appSecret);
			}

			// 登录设置
			$this->configService->setEnabled($enabled);
			$this->configService->setForceLogin($forceLogin);
			$this->configService->setAutoCreateUser($autoCreateUser);

			// 同步设置
			$this->configService->setSyncEnabled($syncEnabled);
			$this->configService->setSyncFrequency($syncFrequency);
			$this->configService->setUserMatchFields($userMatchFields);
			$this->configService->setDefaultQuota($defaultQuota);

			$this->logger->info('WeComConfig saved successfully');

			return new DataResponse([
				'status' => 'success',
				'message' => '配置保存成功',
				'data' => $this->configService->getAllConfig(),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Failed to save config: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse([
				'status' => 'error',
				'message' => '保存配置失败: ' . $e->getMessage(),
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * 测试企业微信连接
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function testConnection(): DataResponse {
		try {
			if (!$this->configService->isConfigured()) {
				return new DataResponse([
					'status' => 'error',
					'message' => '请先完整配置企业微信参数',
				], Http::STATUS_BAD_REQUEST);
			}

			// 测试企业微信 API 连接
			$this->weComApiService->testConnection();
			
			return new DataResponse([
				'status' => 'success',
				'message' => '连接测试成功',
			]);
		} catch (\Exception $e) {
			$this->logger->error('Connection test failed: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse([
				'status' => 'error',
				'message' => '连接测试失败: ' . $e->getMessage(),
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * 手动触发用户同步
	 *
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	public function manualSync(): DataResponse {
		try {
			if (!$this->configService->isConfigured()) {
				return new DataResponse([
					'status' => 'error',
					'message' => '请先完整配置企业微信参数',
				], Http::STATUS_BAD_REQUEST);
			}

			$this->logger->info('Starting manual user sync');
			
			// 执行同步
			$result = $this->syncService->fullSync();
			
			if ($result['success']) {
				return new DataResponse([
					'status' => 'success',
					'message' => $result['message'],
					'data' => [
						'total_users' => $result['total_users'],
						'created_users' => $result['created_users'],
						'updated_users' => $result['updated_users'],
						'total_departments' => $result['total_departments'],
						'created_groups' => $result['created_groups'],
					],
				]);
			} else {
				return new DataResponse([
					'status' => 'error',
					'message' => $result['message'],
					'errors' => $result['errors'],
				], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
		} catch (\Exception $e) {
			$this->logger->error('Manual sync failed: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse([
				'status' => 'error',
				'message' => '同步失败: ' . $e->getMessage(),
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
