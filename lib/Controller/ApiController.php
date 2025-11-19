<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCA\OAuthWeCom\Service\NotificationService;
use OCA\OAuthWeCom\Service\ConfigService;
use OCP\IRequest;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private NotificationService $notificationService,
		private ConfigService $configService
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api')]
	public function index(): DataResponse {
		return new DataResponse(
			['message' => 'Hello world!']
		);
	}

	/**
	 * Send a message to a user
	 *
	 * @param string $userId
	 * @param string $message
	 * @return DataResponse
	 */
	#[ApiRoute(verb: 'POST', url: '/api/send_message')]
	public function sendMessage(string $userId, string $message): DataResponse {
		if (!$this->configService->isNotificationsEnabled()) {
			return new DataResponse([
				'status' => 'error',
				'message' => 'Notifications are disabled',
			], Http::STATUS_FORBIDDEN);
		}

		$success = $this->notificationService->sendMessage($userId, $message);

		if ($success) {
			return new DataResponse([
				'status' => 'success',
				'message' => 'Message sent successfully',
			]);
		} else {
			return new DataResponse([
				'status' => 'error',
				'message' => 'Failed to send message',
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
