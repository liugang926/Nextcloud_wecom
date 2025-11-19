<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Service;

use Psr\Log\LoggerInterface;

class NotificationService {
    public function __construct(
        private WeComApiService $weComApiService,
        private LoggerInterface $logger
    ) {
    }

    public function sendMessage(string $userId, string $message): bool {
        try {
            return $this->weComApiService->sendMessage($userId, $message);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send message to user {$userId}: " . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
}
