<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Service;

use OCP\IConfig;

class ConfigService {
	private const APP_ID = 'oauthwecom';

	// 配置项键名
	private const CORP_ID = 'corp_id';
	private const AGENT_ID = 'agent_id';
	private const APP_SECRET = 'app_secret';
	private const CALLBACK_URL = 'callback_url';
	private const ENABLED = 'enabled';
	private const FORCE_LOGIN = 'force_login';
	private const AUTO_CREATE_USER = 'auto_create_user';
	private const SYNC_ENABLED = 'sync_enabled';
	private const SYNC_FREQUENCY = 'sync_frequency';
	private const USER_MATCH_FIELDS = 'user_match_fields';
	private const DEFAULT_QUOTA = 'default_quota';
	private const SYNC_DEPARTMENTS = 'sync_departments';
	private const NOTIFICATIONS_ENABLED = 'notifications_enabled';

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * 获取企业 ID
	 */
	public function getCorpId(): string {
		return $this->config->getAppValue(self::APP_ID, self::CORP_ID, '');
	}

	/**
	 * 设置企业 ID
	 */
	public function setCorpId(string $corpId): void {
		$this->config->setAppValue(self::APP_ID, self::CORP_ID, $corpId);
	}

	/**
	 * 获取应用 AgentID
	 */
	public function getAgentId(): string {
		return $this->config->getAppValue(self::APP_ID, self::AGENT_ID, '');
	}

	/**
	 * 设置应用 AgentID
	 */
	public function setAgentId(string $agentId): void {
		$this->config->setAppValue(self::APP_ID, self::AGENT_ID, $agentId);
	}

	/**
	 * 获取应用 Secret
	 */
	public function getAppSecret(): string {
		return $this->config->getAppValue(self::APP_ID, self::APP_SECRET, '');
	}

	/**
	 * 设置应用 Secret
	 */
	public function setAppSecret(string $appSecret): void {
		$this->config->setAppValue(self::APP_ID, self::APP_SECRET, $appSecret);
	}

	/**
	 * 获取回调 URL
	 */
	public function getCallbackUrl(): string {
		return $this->config->getAppValue(self::APP_ID, self::CALLBACK_URL, '');
	}

	/**
	 * 设置回调 URL
	 */
	public function setCallbackUrl(string $callbackUrl): void {
		$this->config->setAppValue(self::APP_ID, self::CALLBACK_URL, $callbackUrl);
	}

	/**
	 * 是否启用企业微信登录
	 */
	public function isEnabled(): bool {
		return $this->config->getAppValue(self::APP_ID, self::ENABLED, 'no') === 'yes';
	}

	/**
	 * 设置是否启用企业微信登录
	 */
	public function setEnabled(bool $enabled): void {
		$this->config->setAppValue(self::APP_ID, self::ENABLED, $enabled ? 'yes' : 'no');
	}

	/**
	 * 是否强制企业微信登录
	 */
	public function isForceLogin(): bool {
		return $this->config->getAppValue(self::APP_ID, self::FORCE_LOGIN, 'no') === 'yes';
	}

	/**
	 * 设置是否强制企业微信登录
	 */
	public function setForceLogin(bool $forceLogin): void {
		$this->config->setAppValue(self::APP_ID, self::FORCE_LOGIN, $forceLogin ? 'yes' : 'no');
	}

	/**
	 * 是否自动创建用户
	 */
	public function isAutoCreateUser(): bool {
		return $this->config->getAppValue(self::APP_ID, self::AUTO_CREATE_USER, 'yes') === 'yes';
	}

	/**
	 * 设置是否自动创建用户
	 */
	public function setAutoCreateUser(bool $autoCreate): void {
		$this->config->setAppValue(self::APP_ID, self::AUTO_CREATE_USER, $autoCreate ? 'yes' : 'no');
	}

	/**
	 * 是否启用用户同步
	 */
	public function isSyncEnabled(): bool {
		return $this->config->getAppValue(self::APP_ID, self::SYNC_ENABLED, 'no') === 'yes';
	}

	/**
	 * 设置是否启用用户同步
	 */
	public function setSyncEnabled(bool $enabled): void {
		$this->config->setAppValue(self::APP_ID, self::SYNC_ENABLED, $enabled ? 'yes' : 'no');
	}

	/**
	 * 获取同步频率（小时）
	 */
	public function getSyncFrequency(): int {
		return (int)$this->config->getAppValue(self::APP_ID, self::SYNC_FREQUENCY, '24');
	}

	/**
	 * 设置同步频率（小时）
	 */
	public function setSyncFrequency(int $hours): void {
		$this->config->setAppValue(self::APP_ID, self::SYNC_FREQUENCY, (string)$hours);
	}

	/**
	 * 获取用户匹配字段（email, phone, username）
	 */
	public function getUserMatchFields(): array {
		$fields = $this->config->getAppValue(self::APP_ID, self::USER_MATCH_FIELDS, 'email,phone');
		return array_filter(explode(',', $fields));
	}

	/**
	 * 设置用户匹配字段
	 */
	public function setUserMatchFields(array $fields): void {
		$this->config->setAppValue(self::APP_ID, self::USER_MATCH_FIELDS, implode(',', $fields));
	}

	/**
	 * 获取默认配额（字节）
	 */
	public function getDefaultQuota(): string {
		return $this->config->getAppValue(self::APP_ID, self::DEFAULT_QUOTA, 'default');
	}

	/**
	 * 设置默认配额
	 */
	public function setDefaultQuota(string $quota): void {
		$this->config->setAppValue(self::APP_ID, self::DEFAULT_QUOTA, $quota);
	}

	/**
	 * 获取同步的部门 ID 列表
	 */
	public function getSyncDepartments(): array {
		$deps = $this->config->getAppValue(self::APP_ID, self::SYNC_DEPARTMENTS, '');
		if (empty($deps)) {
			return [];
		}
		return array_filter(explode(',', $deps));
	}

	/**
	 * 设置同步的部门 ID 列表
	 */
	public function setSyncDepartments(array $departmentIds): void {
		$this->config->setAppValue(self::APP_ID, self::SYNC_DEPARTMENTS, implode(',', $departmentIds));
	}

	/**
	 * Whether notifications are enabled
	 */
	public function isNotificationsEnabled(): bool {
		return $this->config->getAppValue(self::APP_ID, self::NOTIFICATIONS_ENABLED, 'no') === 'yes';
	}

	/**
	 * Set whether notifications are enabled
	 */
	public function setNotificationsEnabled(bool $enabled): void {
		$this->config->setAppValue(self::APP_ID, self::NOTIFICATIONS_ENABLED, $enabled ? 'yes' : 'no');
	}

	/**
	 * Check if the configuration is complete
	 */
	public function isConfigured(): bool {
		return !empty($this->getCorpId())
			&& !empty($this->getAgentId())
			&& !empty($this->getAppSecret());
	}

	/**
	 * 获取所有配置
	 */
	public function getAllConfig(): array {
		return [
			'corp_id' => $this->getCorpId(),
			'agent_id' => $this->getAgentId(),
			'app_secret' => $this->getAppSecret() ? '********' : '', // 隐藏敏感信息
			'callback_url' => $this->getCallbackUrl(),
			'enabled' => $this->isEnabled(),
			'force_login' => $this->isForceLogin(),
			'auto_create_user' => $this->isAutoCreateUser(),
			'sync_enabled' => $this->isSyncEnabled(),
			'sync_frequency' => $this->getSyncFrequency(),
			'user_match_fields' => $this->getUserMatchFields(),
			'default_quota' => $this->getDefaultQuota(),
			'sync_departments' => $this->getSyncDepartments(),
			'is_configured' => $this->isConfigured(),
			'notifications_enabled' => $this->isNotificationsEnabled(),
		];
	}
}
