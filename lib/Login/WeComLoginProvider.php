<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Login;

use OCA\OAuthWeCom\Service\ConfigService;
use OCP\Authentication\IAlternativeLogin;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * 企业微信登录提供者
 * 在登录页面添加企业微信登录选项
 */
class WeComLoginProvider implements IAlternativeLogin {
	public function __construct(
		private ConfigService $configService,
		private IURLGenerator $urlGenerator,
		private IL10N $l10n,
	) {
	}

	/**
	 * 获取登录选项的CSS类名
	 */
	public function getClass(): string {
		return 'wecom-oauth-login';
	}

	/**
	 * 获取登录选项的标签
	 */
	public function getLabel(): string {
		return $this->l10n->t('企业微信登录');
	}

	/**
	 * 获取登录链接
	 */
	public function getLink(): string {
		return $this->urlGenerator->linkToRoute('oauthwecom.oauth.authorize');
	}

	/**
	 * 加载登录选项（用于显示自定义HTML）
	 */
	public function load(): void {
		// 如果需要自定义HTML，可以在这里添加
		// 目前使用默认的按钮样式
	}
}

