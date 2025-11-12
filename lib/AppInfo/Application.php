<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\AppInfo;

use OCA\OAuthWeCom\BackgroundJob\SyncUsersJob;
use OCA\OAuthWeCom\Login\WeComLoginProvider;
use OCA\OAuthWeCom\Service\ConfigService;
use OCA\OAuthWeCom\Settings\AdminSettings;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'oauthwecom';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// 注册管理员设置页面
		$context->registerSettings(AdminSettings::class);
		
		// 注册登录提供者
		$context->registerAlternativeLogin(WeComLoginProvider::class);
		
		// 注册后台任务
		$context->registerBackgroundJob(SyncUsersJob::class);
	}

	public function boot(IBootContext $context): void {
		$container = $context->getServerContainer();
		
		// 检查是否启用了企业微信登录
		/** @var ConfigService $configService */
		$configService = $container->get(ConfigService::class);
		
		// 如果启用了强制登录，可以在这里添加重定向逻辑
		if ($configService->isEnabled() && $configService->isForceLogin()) {
			// TODO: 实现强制重定向到企业微信登录
		}
	}
}
