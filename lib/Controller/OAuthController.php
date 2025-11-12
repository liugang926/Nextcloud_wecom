<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Controller;

use OCA\OAuthWeCom\Db\UserMapping;
use OCA\OAuthWeCom\Db\UserMappingMapper;
use OCA\OAuthWeCom\Service\AuditService;
use OCA\OAuthWeCom\Service\ConfigService;
use OCA\OAuthWeCom\Service\DeviceDetectService;
use OCA\OAuthWeCom\Service\WeComApiService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * OAuth认证控制器
 * 处理企业微信OAuth 2.0认证流程
 */
class OAuthController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private ConfigService $configService,
		private WeComApiService $weComApiService,
		private DeviceDetectService $deviceDetectService,
		private UserMappingMapper $userMappingMapper,
		private AuditService $auditService,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private IURLGenerator $urlGenerator,
		private ISession $session,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * 发起OAuth授权
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[PublicPage]
	public function authorize(): RedirectResponse {
		try {
			// 检查插件是否启用
			if (!$this->configService->isEnabled()) {
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			}

			// 生成state用于防止CSRF攻击
			$state = bin2hex(random_bytes(16));
			$this->session->set('oauth_state', $state);

			// 保存设备类型
			$deviceType = $this->deviceDetectService->getDeviceType();
			$this->session->set('oauth_device_type', $deviceType);

			// 获取回调URL
			$redirectUri = $this->urlGenerator->linkToRouteAbsolute('oauthwecom.oauth.callback');
			
			// 根据设备类型选择合适的授权范围
			// 对于企业微信APP内，使用静默授权
			// 对于其他情况，使用标准授权
			$scope = 'snsapi_base';
			if ($this->deviceDetectService->isWeComApp()) {
				// 在企业微信APP内，可以使用静默授权获取更多信息
				$scope = 'snsapi_privateinfo';
			}
			
			// 生成授权URL并重定向
			$authUrl = $this->weComApiService->getAuthUrl($redirectUri, $state, $scope);
			
			// 记录日志
			$this->logger->info('OAuth authorization initiated', [
				'device_type' => $deviceType,
				'scope' => $scope,
			]);
			
			return new RedirectResponse($authUrl);
		} catch (\Exception $e) {
			$this->logger->error('OAuth authorization failed', ['exception' => $e]);
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		}
	}

	/**
	 * OAuth回调处理
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[PublicPage]
	public function callback(string $code = '', string $state = ''): RedirectResponse {
		try {
			// 验证state
			$savedState = $this->session->get('oauth_state');
			if (empty($savedState) || $savedState !== $state) {
				$this->logger->warning('OAuth state mismatch', ['saved' => $savedState, 'received' => $state]);
				$this->auditService->logOAuthCallback(
					null,
					null,
					false,
					'State verification failed'
				);
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			}

			// 清除state
			$this->session->remove('oauth_state');

			// 验证code
			if (empty($code)) {
				$this->logger->warning('OAuth callback without code');
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			}

			// 通过code获取用户信息
			$wecomUserInfo = $this->weComApiService->getUserInfoByCode($code);
			
			if (empty($wecomUserInfo['userid'])) {
				$this->logger->error('Failed to get WeCom user ID from callback', ['info' => $wecomUserInfo]);
				$this->auditService->logOAuthCallback(
					null,
					null,
					false,
					'No user ID in response'
				);
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			}

			$wecomUserId = $wecomUserInfo['userid'];

			// 获取用户详细信息
			$wecomUserDetail = $this->weComApiService->getUserDetail($wecomUserId);

			// 查找或创建用户映射
			$user = $this->findOrCreateUser($wecomUserId, $wecomUserDetail);

			if ($user === null) {
				$this->logger->error('Failed to find or create user', ['wecomUserId' => $wecomUserId]);
				$this->auditService->logLogin(
					'',
					$wecomUserId,
					false,
					'Failed to find or create user'
				);
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
			}

			// 登录用户
			$this->userSession->setUser($user);
			$this->userSession->createSessionToken($this->request, $user->getUID(), $user->getUID());

			// 更新最后登录时间
			try {
				$this->userMappingMapper->updateLastLoginAt($wecomUserId, time());
			} catch (\Exception $e) {
				$this->logger->warning('Failed to update last login time', ['exception' => $e]);
			}

			// 记录审计日志
			$this->auditService->logLogin(
				$user->getUID(),
				$wecomUserId,
				true,
				'User logged in successfully'
			);

			// 重定向到主页
			return new RedirectResponse($this->urlGenerator->linkToRoute('files.view.index'));
		} catch (\Exception $e) {
			$this->logger->error('OAuth callback failed', ['exception' => $e]);
			$this->auditService->logOAuthCallback(
				null,
				null,
				false,
				'Exception: ' . $e->getMessage()
			);
			return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm'));
		}
	}

	/**
	 * 查找或创建用户
	 */
	private function findOrCreateUser(string $wecomUserId, array $wecomUserDetail): ?IUser {
		try {
			// 首先尝试通过映射表查找
			$mapping = $this->userMappingMapper->findByWecomUserId($wecomUserId);
			$user = $this->userManager->get($mapping->getNextcloudUid());
			
			if ($user !== null) {
				// 更新映射信息
				$this->updateUserMapping($mapping, $wecomUserDetail);
				return $user;
			}
		} catch (\Exception $e) {
			// 映射不存在，继续尝试匹配或创建
		}

		// 尝试根据配置的匹配字段查找现有用户
		$user = $this->matchExistingUser($wecomUserDetail);
		
		if ($user !== null) {
			// 创建映射
			$this->createUserMapping($wecomUserId, $user->getUID(), $wecomUserDetail);
			return $user;
		}

		// 如果启用了自动创建用户，则创建新用户
		if ($this->configService->isAutoCreateUser()) {
			$user = $this->createNewUser($wecomUserId, $wecomUserDetail);
			
			if ($user !== null) {
				$this->createUserMapping($wecomUserId, $user->getUID(), $wecomUserDetail);
			}
			
			return $user;
		}

		return null;
	}

	/**
	 * 匹配现有用户
	 */
	private function matchExistingUser(array $wecomUserDetail): ?IUser {
		$matchFields = $this->configService->getUserMatchFields();
		
		foreach ($matchFields as $matchField) {
			try {
				switch ($matchField) {
					case 'email':
						if (!empty($wecomUserDetail['email'])) {
							$users = $this->userManager->getByEmail($wecomUserDetail['email']);
							if (count($users) > 0) {
								return reset($users);
							}
						}
						break;
						
					case 'phone':
						if (!empty($wecomUserDetail['mobile'])) {
							// NextCloud没有直接通过手机号查询的API，尝试通过映射表查找
							try {
								$mapping = $this->userMappingMapper->findByMobile($wecomUserDetail['mobile']);
								return $this->userManager->get($mapping->getNextcloudUid());
							} catch (\Exception $e) {
								// 没找到
							}
						}
						break;
						
					case 'username':
						if (!empty($wecomUserDetail['userid'])) {
							$user = $this->userManager->get($wecomUserDetail['userid']);
							if ($user !== null) {
								return $user;
							}
						}
						break;
				}
			} catch (\Exception $e) {
				$this->logger->warning('Error matching existing user', ['exception' => $e, 'field' => $matchField]);
			}
		}

		return null;
	}

	/**
	 * 创建新用户
	 */
	private function createNewUser(string $wecomUserId, array $wecomUserDetail): ?IUser {
		try {
			// 使用企业微信用户ID作为用户名
			$username = $wecomUserId;
			
			// 如果用户名已存在，添加后缀
			if ($this->userManager->get($username) !== null) {
				$username = $wecomUserId . '_' . time();
			}

			// 生成随机密码
			$password = bin2hex(random_bytes(32));
			
			// 创建用户
			$user = $this->userManager->createUser($username, $password);
			
			if ($user === null) {
				$this->logger->error('Failed to create user', ['username' => $username]);
				return null;
			}

			// 设置用户显示名称
			if (!empty($wecomUserDetail['name'])) {
				$user->setDisplayName($wecomUserDetail['name']);
			}

			// 设置用户邮箱
			if (!empty($wecomUserDetail['email'])) {
				$user->setEMailAddress($wecomUserDetail['email']);
			}

			// 设置用户配额
			$quota = $this->configService->getDefaultQuota();
			if (!empty($quota)) {
				$user->setQuota($quota);
			}

			$this->logger->info('Created new user from WeCom', ['username' => $username, 'wecomUserId' => $wecomUserId]);
			
			return $user;
		} catch (\Exception $e) {
			$this->logger->error('Failed to create new user', ['exception' => $e, 'wecomUserId' => $wecomUserId]);
			return null;
		}
	}

	/**
	 * 创建用户映射
	 */
	private function createUserMapping(string $wecomUserId, string $nextcloudUid, array $wecomUserDetail): void {
		try {
			$mapping = new UserMapping();
			$mapping->setWecomUserId($wecomUserId);
			$mapping->setNextcloudUid($nextcloudUid);
			$mapping->setDisplayName($wecomUserDetail['name'] ?? '');
			$mapping->setEmail($wecomUserDetail['email'] ?? '');
			$mapping->setMobile($wecomUserDetail['mobile'] ?? '');
			$mapping->setDepartmentIds(json_encode($wecomUserDetail['department'] ?? []));
			$mapping->setCreatedAt(time());
			$mapping->setUpdatedAt(time());
			$mapping->setLastLoginAt(time());
			
			$this->userMappingMapper->insert($mapping);
		} catch (\Exception $e) {
			$this->logger->error('Failed to create user mapping', ['exception' => $e]);
		}
	}

	/**
	 * 更新用户映射
	 */
	private function updateUserMapping(UserMapping $mapping, array $wecomUserDetail): void {
		try {
			$mapping->setDisplayName($wecomUserDetail['name'] ?? '');
			$mapping->setEmail($wecomUserDetail['email'] ?? '');
			$mapping->setMobile($wecomUserDetail['mobile'] ?? '');
			$mapping->setDepartmentIds(json_encode($wecomUserDetail['department'] ?? []));
			$mapping->setUpdatedAt(time());
			
			$this->userMappingMapper->update($mapping);
		} catch (\Exception $e) {
			$this->logger->error('Failed to update user mapping', ['exception' => $e]);
		}
	}
}

