<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\OAuthWeCom\AppInfo\Application::APP_ID, OCA\OAuthWeCom\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\OAuthWeCom\AppInfo\Application::APP_ID, OCA\OAuthWeCom\AppInfo\Application::APP_ID . '-main');

?>

<div id="oauthwecom"></div>
