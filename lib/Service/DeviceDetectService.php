<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Service;

use OCP\IRequest;

/**
 * 设备识别服务
 * 识别访问终端类型（PC、移动端、企业微信APP等）
 */
class DeviceDetectService {
	public function __construct(
		private IRequest $request,
	) {
	}

	/**
	 * 检测是否来自企业微信APP
	 */
	public function isWeComApp(): bool {
		$userAgent = $this->request->getHeader('User-Agent');
		
		if (empty($userAgent)) {
			return false;
		}

		// 企业微信APP的User-Agent包含特定标识
		// 例如：Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wxwork/3.1.16
		return str_contains(strtolower($userAgent), 'wxwork') || 
			   str_contains(strtolower($userAgent), 'wechatwork');
	}

	/**
	 * 检测是否为移动设备
	 */
	public function isMobileDevice(): bool {
		$userAgent = $this->request->getHeader('User-Agent');
		
		if (empty($userAgent)) {
			return false;
		}

		$mobileKeywords = [
			'mobile',
			'android',
			'iphone',
			'ipad',
			'ipod',
			'blackberry',
			'windows phone',
			'opera mini',
			'iemobile',
		];

		$userAgentLower = strtolower($userAgent);
		
		foreach ($mobileKeywords as $keyword) {
			if (str_contains($userAgentLower, $keyword)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 检测是否为iOS设备
	 */
	public function isIOSDevice(): bool {
		$userAgent = $this->request->getHeader('User-Agent');
		
		if (empty($userAgent)) {
			return false;
		}

		$userAgentLower = strtolower($userAgent);
		
		return str_contains($userAgentLower, 'iphone') ||
			   str_contains($userAgentLower, 'ipad') ||
			   str_contains($userAgentLower, 'ipod');
	}

	/**
	 * 检测是否为Android设备
	 */
	public function isAndroidDevice(): bool {
		$userAgent = $this->request->getHeader('User-Agent');
		
		if (empty($userAgent)) {
			return false;
		}

		return str_contains(strtolower($userAgent), 'android');
	}

	/**
	 * 检测是否为微信内置浏览器
	 */
	public function isWeChatBrowser(): bool {
		$userAgent = $this->request->getHeader('User-Agent');
		
		if (empty($userAgent)) {
			return false;
		}

		$userAgentLower = strtolower($userAgent);
		
		return str_contains($userAgentLower, 'micromessenger');
	}

	/**
	 * 获取设备类型
	 */
	public function getDeviceType(): string {
		if ($this->isWeComApp()) {
			return 'wecom_app';
		}
		
		if ($this->isWeChatBrowser()) {
			return 'wechat_browser';
		}
		
		if ($this->isMobileDevice()) {
			if ($this->isIOSDevice()) {
				return 'mobile_ios';
			}
			if ($this->isAndroidDevice()) {
				return 'mobile_android';
			}
			return 'mobile_other';
		}
		
		return 'desktop';
	}

	/**
	 * 获取User-Agent
	 */
	public function getUserAgent(): string {
		return $this->request->getHeader('User-Agent') ?? '';
	}

	/**
	 * 是否需要特殊处理（APP内或移动端）
	 */
	public function needsSpecialHandling(): bool {
		return $this->isWeComApp() || $this->isMobileDevice();
	}
}

