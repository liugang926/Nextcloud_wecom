<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Tests\Unit\Service;

use OCA\OAuthWeCom\Service\NotificationService;
use OCA\OAuthWeCom\Service\WeComApiService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotificationServiceTest extends TestCase {
    private $weComApiService;
    private $logger;
    private $notificationService;

    protected function setUp(): void {
        parent::setUp();
        $this->weComApiService = $this->createMock(WeComApiService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->notificationService = new NotificationService(
            $this->weComApiService,
            $this->logger
        );
    }

    public function testSendMessageSuccess() {
        $this->weComApiService->expects($this->once())
            ->method('sendMessage')
            ->with('test_user_id', 'test_message')
            ->willReturn(true);
        $result = $this->notificationService->sendMessage('test_user_id', 'test_message');
        $this->assertTrue($result);
    }

    public function testSendMessageFailure() {
        $this->weComApiService->expects($this->once())
            ->method('sendMessage')
            ->with('test_user_id', 'test_message')
            ->will($this->throwException(new \Exception('Test error')));
        $result = $this->notificationService->sendMessage('test_user_id', 'test_message');
        $this->assertFalse($result);
    }
}
