<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Service;

use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;

/**
 * 企业微信API服务
 * 封装所有企业微信API调用
 */
class WeComApiService {
	private const API_BASE_URL = 'https://qyapi.weixin.qq.com/cgi-bin';
	private const CACHE_TTL = 7000; // access_token缓存时间（秒）
	private const CACHE_KEY_PREFIX = 'oauthwecom_token_';

	private ICache $cache;

	public function __construct(
		private ConfigService $configService,
		private IClientService $clientService,
		private LoggerInterface $logger,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createDistributed('oauthwecom');
	}

	/**
	 * 获取访问令牌
	 * 
	 * @throws \Exception
	 */
	public function getAccessToken(): string {
		$corpId = $this->configService->getCorpId();
		$appSecret = $this->configService->getAppSecret();

		if (empty($corpId) || empty($appSecret)) {
			throw new \Exception('Corp ID or App Secret not configured');
		}

		// 尝试从缓存获取
		$cacheKey = self::CACHE_KEY_PREFIX . md5($corpId . $appSecret);
		$cachedToken = $this->cache->get($cacheKey);
		
		if ($cachedToken !== null) {
			return $cachedToken;
		}

		// 调用API获取新token
		$url = self::API_BASE_URL . '/gettoken';
		$params = [
			'corpid' => $corpId,
			'corpsecret' => $appSecret,
		];

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, ['query' => $params]);
			$data = json_decode($response->getBody(), true);

			if (!isset($data['access_token'])) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to get access token: ' . $errorMsg);
			}

			$accessToken = $data['access_token'];
			
			// 缓存token
			$this->cache->set($cacheKey, $accessToken, self::CACHE_TTL);
			
			return $accessToken;
		} catch (\Exception $e) {
			$this->logger->error('Failed to get WeCom access token', ['exception' => $e]);
			throw $e;
		}
	}

	/**
	 * 生成OAuth授权URL
	 */
	public function getAuthUrl(string $redirectUri, string $state = '', string $scope = 'snsapi_base'): string {
		$corpId = $this->configService->getCorpId();
		$agentId = $this->configService->getAgentId();

		if (empty($corpId) || empty($agentId)) {
			throw new \Exception('Corp ID or Agent ID not configured');
		}

		$params = [
			'appid' => $corpId,
			'redirect_uri' => urlencode($redirectUri),
			'response_type' => 'code',
			'scope' => $scope,
			'agentid' => $agentId,
			'state' => $state ?: 'STATE',
		];

		return 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($params) . '#wechat_redirect';
	}

	/**
	 * 通过授权码获取用户信息
	 * 
	 * @throws \Exception
	 */
	public function getUserInfoByCode(string $code): array {
		$accessToken = $this->getAccessToken();
		$url = self::API_BASE_URL . '/auth/getuserinfo';
		
		$params = [
			'access_token' => $accessToken,
			'code' => $code,
		];

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, ['query' => $params]);
			$data = json_decode($response->getBody(), true);

			if (isset($data['errcode']) && $data['errcode'] !== 0) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to get user info: ' . $errorMsg);
			}

			return $data;
		} catch (\Exception $e) {
			$this->logger->error('Failed to get user info by code', ['exception' => $e, 'code' => $code]);
			throw $e;
		}
	}

	/**
	 * 获取用户详细信息
	 * 
	 * @throws \Exception
	 */
	public function getUserDetail(string $userId): array {
		$accessToken = $this->getAccessToken();
		$url = self::API_BASE_URL . '/user/get';
		
		$params = [
			'access_token' => $accessToken,
			'userid' => $userId,
		];

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, ['query' => $params]);
			$data = json_decode($response->getBody(), true);

			if (isset($data['errcode']) && $data['errcode'] !== 0) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to get user detail: ' . $errorMsg);
			}

			return $data;
		} catch (\Exception $e) {
			$this->logger->error('Failed to get user detail', ['exception' => $e, 'userId' => $userId]);
			throw $e;
		}
	}

	/**
	 * 获取部门列表
	 * 
	 * @throws \Exception
	 */
	public function getDepartmentList(?int $departmentId = null): array {
		$accessToken = $this->getAccessToken();
		$url = self::API_BASE_URL . '/department/list';
		
		$params = [
			'access_token' => $accessToken,
		];
		
		if ($departmentId !== null) {
			$params['id'] = $departmentId;
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, ['query' => $params]);
			$data = json_decode($response->getBody(), true);

			if (isset($data['errcode']) && $data['errcode'] !== 0) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to get department list: ' . $errorMsg);
			}

			return $data['department'] ?? [];
		} catch (\Exception $e) {
			$this->logger->error('Failed to get department list', ['exception' => $e]);
			throw $e;
		}
	}

	/**
	 * 获取部门成员列表
	 * 
	 * @throws \Exception
	 */
	public function getDepartmentUsers(int $departmentId, bool $fetchChild = false): array {
		$accessToken = $this->getAccessToken();
		$url = self::API_BASE_URL . '/user/simplelist';
		
		$params = [
			'access_token' => $accessToken,
			'department_id' => $departmentId,
			'fetch_child' => $fetchChild ? 1 : 0,
		];

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, ['query' => $params]);
			$data = json_decode($response->getBody(), true);

			if (isset($data['errcode']) && $data['errcode'] !== 0) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to get department users: ' . $errorMsg);
			}

			return $data['userlist'] ?? [];
		} catch (\Exception $e) {
			$this->logger->error('Failed to get department users', ['exception' => $e, 'departmentId' => $departmentId]);
			throw $e;
		}
	}

	/**
	 * 获取部门成员详情列表
	 * 
	 * @throws \Exception
	 */
	public function getDepartmentUsersDetail(int $departmentId, bool $fetchChild = false): array {
		$accessToken = $this->getAccessToken();
		$url = self::API_BASE_URL . '/user/list';
		
		$params = [
			'access_token' => $accessToken,
			'department_id' => $departmentId,
			'fetch_child' => $fetchChild ? 1 : 0,
		];

		try {
			$client = $this->clientService->newClient();
			$response = $client->get($url, ['query' => $params]);
			$data = json_decode($response->getBody(), true);

			if (isset($data['errcode']) && $data['errcode'] !== 0) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to get department users detail: ' . $errorMsg);
			}

			return $data['userlist'] ?? [];
		} catch (\Exception $e) {
			$this->logger->error('Failed to get department users detail', ['exception' => $e, 'departmentId' => $departmentId]);
			throw $e;
		}
	}

	/**
	 * 测试API连接
	 * 
	 * @throws \Exception
	 */
	public function testConnection(): bool {
		try {
			// 尝试获取access_token
			$accessToken = $this->getAccessToken();
			
			if (empty($accessToken)) {
				return false;
			}

			// 尝试获取部门列表验证token有效性
			$this->getDepartmentList();
			
			return true;
		} catch (\Exception $e) {
			$this->logger->error('WeCom API connection test failed', ['exception' => $e]);
			throw $e;
		}
	}

	/**
	 * Send a message
	 *
	 * @throws \Exception
	 */
	public function sendMessage(string $userId, string $content): bool {
		$accessToken = $this->getAccessToken();
		$url = self::API_BASE_URL . '/message/send';

		$params = [
			'access_token' => $accessToken,
		];

		$body = [
			'touser' => $userId,
			'msgtype' => 'text',
			'agentid' => $this->configService->getAgentId(),
			'text' => [
				'content' => $content,
			],
		];

		try {
			$client = $this->clientService->newClient();
			$response = $client->post($url, [
				'query' => $params,
				'json' => $body,
			]);
			$data = json_decode($response->getBody(), true);

			if (isset($data['errcode']) && $data['errcode'] !== 0) {
				$errorMsg = $data['errmsg'] ?? 'Unknown error';
				throw new \Exception('Failed to send message: ' . $errorMsg);
			}

			return true;
		} catch (\Exception $e) {
			$this->logger->error('Failed to send message', ['exception' => $e, 'userId' => $userId]);
			throw $e;
		}
	}
}

