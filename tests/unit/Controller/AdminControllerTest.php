<?php

declare(strict_types=1);

namespace OCA\OAuthWeCom\Tests\Unit\Controller;

use OCA\OAuthWeCom\Controller\AdminController;
use OCA\OAuthWeCom\Service\ConfigService;
use OCA\OAuthWeCom\Service\SyncService;
use OCA\OAuthWeCom\Service\WeComApiService;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AdminControllerTest extends TestCase {
    private $request;
    private $configService;
    private $weComApiService;
    private $syncService;
    private $logger;
    private $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->request = $this->createMock(IRequest::class);
        $this->configService = $this->createMock(ConfigService::class);
        $this->weComApiService = $this->createMock(WeComApiService::class);
        $this->syncService = $this->createMock(SyncService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->controller = new AdminController(
            'oauthwecom',
            $this->request,
            $this->configService,
            $this->weComApiService,
            $this->syncService,
            $this->logger
        );
    }

    public function testGetConfigSuccess() {
        $expectedConfig = ['corpId' => 'test_corp_id'];
        $this->configService->method('getAllConfig')->willReturn($expectedConfig);
        $response = $this->controller->getConfig();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals(['status' => 'success', 'data' => $expectedConfig], $response->getData());
    }

    public function testGetConfigError() {
        $this->configService->method('getAllConfig')->will($this->throwException(new \Exception('Test error')));
        $response = $this->controller->getConfig();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals(['status' => 'error', 'message' => 'Test error'], $response->getData());
        $this->assertEquals(500, $response->getStatus());
    }

    public function testSaveConfigSuccess() {
        $this->configService->expects($this->once())->method('setCorpId');
        $this->configService->expects($this->once())->method('setAgentId');
        $this->configService->expects($this->once())->method('setAppSecret');
        $this->configService->expects($this->once())->method('setNotificationsEnabled')->with(true);
        $response = $this->controller->saveConfig(
            'test_corp_id',
            'test_agent_id',
            'test_secret',
            true,
            true,
            true,
            true,
            48,
            ['email', 'phone'],
            '10GB',
            true
        );
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('success', $response->getData()['status']);
    }

    public function testSaveConfigError() {
        $this->configService->method('setCorpId')->will($this->throwException(new \Exception('Test error')));
        $response = $this->controller->saveConfig('test_corp_id', 'test_agent_id', 'test_secret');
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('error', $response->getData()['status']);
        $this->assertStringContainsString('Test error', $response->getData()['message']);
        $this->assertEquals(500, $response->getStatus());
    }

    public function testTestConnectionNotConfigured() {
        $this->configService->method('isConfigured')->willReturn(false);
        $response = $this->controller->testConnection();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('error', $response->getData()['status']);
        $this->assertEquals(400, $response->getStatus());
    }

    public function testTestConnectionSuccess() {
        $this->configService->method('isConfigured')->willReturn(true);
        $this->weComApiService->expects($this->once())->method('testConnection');
        $response = $this->controller->testConnection();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('success', $response->getData()['status']);
    }

    public function testTestConnectionError() {
        $this->configService->method('isConfigured')->willReturn(true);
        $this->weComApiService->method('testConnection')->will($this->throwException(new \Exception('Test error')));
        $response = $this->controller->testConnection();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('error', $response->getData()['status']);
        $this->assertStringContainsString('Test error', $response->getData()['message']);
        $this->assertEquals(500, $response->getStatus());
    }

    public function testManualSyncNotConfigured() {
        $this->configService->method('isConfigured')->willReturn(false);
        $response = $this->controller->manualSync();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('error', $response->getData()['status']);
        $this->assertEquals(400, $response->getStatus());
    }

    public function testManualSyncSuccess() {
        $this->configService->method('isConfigured')->willReturn(true);
        $syncResult = ['success' => true, 'message' => 'Sync successful'];
        $this->syncService->method('fullSync')->willReturn($syncResult);
        $response = $this->controller->manualSync();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('success', $response->getData()['status']);
    }

    public function testManualSyncFailure() {
        $this->configService->method('isConfigured')->willReturn(true);
        $syncResult = ['success' => false, 'message' => 'Sync failed', 'errors' => []];
        $this->syncService->method('fullSync')->willReturn($syncResult);
        $response = $this->controller->manualSync();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('error', $response->getData()['status']);
        $this->assertEquals(500, $response->getStatus());
    }

    public function testManualSyncError() {
        $this->configService->method('isConfigured')->willReturn(true);
        $this->syncService->method('fullSync')->will($this->throwException(new \Exception('Test error')));
        $response = $this->controller->manualSync();
        $this->assertInstanceOf(DataResponse::class, $response);
        $this->assertEquals('error', $response->getData()['status']);
        $this->assertStringContainsString('Test error', $response->getData()['message']);
        $this->assertEquals(500, $response->getStatus());
    }
}
