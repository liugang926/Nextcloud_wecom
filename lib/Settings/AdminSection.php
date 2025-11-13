<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private IL10N $l,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * 返回区域ID
	 */
	public function getID(): string {
		return 'oauthwecom';
	}

	/**
	 * 返回区域名称
	 */
	public function getName(): string {
		return $this->l->t('企业微信认证');
	}

	/**
	 * 返回优先级
	 */
	public function getPriority(): int {
		return 75;
	}

	/**
	 * 返回图标URL
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('oauthwecom', 'app.svg');
	}
}

