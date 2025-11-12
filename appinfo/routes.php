<?php

declare(strict_types=1);

return [
	'routes' => [
		// 页面路由
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		
		// OAuth 认证路由
		['name' => 'oauth#authorize', 'url' => '/oauth/authorize', 'verb' => 'GET'],
		['name' => 'oauth#callback', 'url' => '/oauth/callback', 'verb' => 'GET'],
		
		// 管理后台API路由
		['name' => 'admin#getConfig', 'url' => '/admin/config', 'verb' => 'GET'],
		['name' => 'admin#saveConfig', 'url' => '/admin/config', 'verb' => 'POST'],
		['name' => 'admin#testConnection', 'url' => '/admin/test-connection', 'verb' => 'POST'],
		['name' => 'admin#manualSync', 'url' => '/admin/sync', 'verb' => 'POST'],
		['name' => 'admin#getLogs', 'url' => '/admin/logs', 'verb' => 'GET'],
	],
	'ocs' => [
		// OCS API 路由
		['name' => 'api#index', 'url' => '/api', 'verb' => 'GET'],
	],
];

