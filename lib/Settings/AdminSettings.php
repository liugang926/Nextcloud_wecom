<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Settings;

use OCA\OAuthWeCom\AppInfo\Application;
use OCA\OAuthWeCom\Service\ConfigService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

class AdminSettings implements ISettings {
	public function __construct(
		private ConfigService $configService,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$parameters = [
			'config' => $this->configService->getAllConfig(),
		];

		return new TemplateResponse(Application::APP_ID, 'settings/admin', $parameters);
	}

	/**
	 * @return string
	 */
	public function getSection(): string {
		return 'security';
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return 50;
	}
}
