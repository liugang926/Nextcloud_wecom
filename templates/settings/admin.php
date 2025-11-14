<?php
/**
 * @var array $_
 */

use OCP\Util;

// 生成建议的回调 URL
$urlGenerator = \OC::$server->getURLGenerator();
$suggestedCallbackUrl = $urlGenerator->linkToRouteAbsolute('oauthwecom.oauth.callback');

// 在页面底部加载脚本，确保DOM已准备好
script('oauthwecom', 'oauthwecom-adminSettings');
?>

<div id="oauthwecom-admin" class="section">
	<h2><?php p($l->t('企业微信OAuth认证')); ?></h2>
	
	<div class="wecom-settings-section">
		<h3><?php p($l->t('基本配置')); ?></h3>
		<p class="settings-hint">
			<?php p($l->t('在企业微信管理后台创建应用后，填写以下配置信息。')); ?>
			<a href="https://work.weixin.qq.com/api/doc" target="_blank" rel="noopener noreferrer">
				<?php p($l->t('查看企业微信开发文档')); ?>
			</a>
		</p>
		
		<div class="form-group">
			<label for="wecom-corp-id"><?php p($l->t('企业ID (CorpID)')); ?></label>
			<input type="text" 
				   id="wecom-corp-id" 
				   name="corp_id" 
				   value="<?php p($_['config']['corp_id']); ?>"
				   placeholder="<?php p($l->t('请输入企业ID')); ?>">
			<p class="hint"><?php p($l->t('在企业微信管理后台的"我的企业"中查看')); ?></p>
		</div>

		<div class="form-group">
			<label for="wecom-agent-id"><?php p($l->t('应用AgentID')); ?></label>
			<input type="text" 
				   id="wecom-agent-id" 
				   name="agent_id" 
				   value="<?php p($_['config']['agent_id']); ?>"
				   placeholder="<?php p($l->t('请输入应用AgentID')); ?>">
			<p class="hint"><?php p($l->t('在企业微信应用详情页面查看')); ?></p>
		</div>

		<div class="form-group">
			<label for="wecom-app-secret"><?php p($l->t('应用Secret')); ?></label>
			<input type="password" 
				   id="wecom-app-secret" 
				   name="app_secret" 
				   value="<?php p($_['config']['app_secret'] === '******' ? '' : $_['config']['app_secret']); ?>"
				   placeholder="<?php p($l->t('请输入应用Secret')); ?>">
			<p class="hint"><?php p($l->t('在企业微信应用详情页面查看（需保密）')); ?></p>
		</div>

		<div class="form-group">
			<label for="wecom-callback-url"><?php p($l->t('OAuth回调地址')); ?></label>
			<input type="text" 
				   id="wecom-callback-url" 
				   name="callback_url" 
				   value="<?php p($suggestedCallbackUrl); ?>"
				   readonly>
			<p class="hint">
				<?php p($l->t('将此地址配置到企业微信应用的"授权回调域"中')); ?>
			</p>
			<button id="copy-callback-url" class="button-secondary">
				<?php p($l->t('复制地址')); ?>
			</button>
		</div>
	</div>

	<div class="wecom-settings-section">
		<h3><?php p($l->t('登录设置')); ?></h3>
		
		<div class="form-group checkbox-group">
			<input type="checkbox" 
				   id="wecom-enabled" 
				   name="enabled" 
				   class="checkbox"
				   <?php if ($_['config']['enabled']): ?>checked<?php endif; ?>>
			<label for="wecom-enabled"><?php p($l->t('启用企业微信登录')); ?></label>
		</div>

		<div class="form-group checkbox-group">
			<input type="checkbox" 
				   id="wecom-force-login" 
				   name="force_login" 
				   class="checkbox"
				   <?php if ($_['config']['force_login']): ?>checked<?php endif; ?>>
			<label for="wecom-force-login"><?php p($l->t('强制使用企业微信登录')); ?></label>
			<p class="hint"><?php p($l->t('启用后，登录页面将默认显示企业微信登录')); ?></p>
		</div>

		<div class="form-group checkbox-group">
			<input type="checkbox" 
				   id="wecom-auto-create" 
				   name="auto_create_user" 
				   class="checkbox"
				   <?php if ($_['config']['auto_create_user']): ?>checked<?php endif; ?>>
			<label for="wecom-auto-create"><?php p($l->t('自动创建用户')); ?></label>
			<p class="hint"><?php p($l->t('首次登录时自动创建NextCloud账号')); ?></p>
		</div>
	</div>

	<div class="wecom-settings-section">
		<h3><?php p($l->t('用户同步设置')); ?></h3>
		
		<div class="form-group checkbox-group">
			<input type="checkbox" 
				   id="wecom-sync-enabled" 
				   name="sync_enabled" 
				   class="checkbox"
				   <?php if ($_['config']['sync_enabled']): ?>checked<?php endif; ?>>
			<label for="wecom-sync-enabled"><?php p($l->t('启用自动同步')); ?></label>
		</div>

		<div class="form-group">
			<label for="wecom-sync-frequency"><?php p($l->t('同步频率（小时）')); ?></label>
			<input type="number" 
				   id="wecom-sync-frequency" 
				   name="sync_frequency" 
				   value="<?php p($_['config']['sync_frequency']); ?>"
				   min="1"
				   max="168">
			<p class="hint"><?php p($l->t('设置自动同步的时间间隔')); ?></p>
		</div>

		<div class="form-group">
			<label for="wecom-user-match-fields"><?php p($l->t('用户匹配字段')); ?></label>
			<div class="checkbox-list">
				<label>
					<input type="checkbox" name="match_field_email" value="email" 
						   <?php if (in_array('email', $_['config']['user_match_fields'])): ?>checked<?php endif; ?>>
					<?php p($l->t('邮箱')); ?>
				</label>
				<label>
					<input type="checkbox" name="match_field_phone" value="phone"
						   <?php if (in_array('phone', $_['config']['user_match_fields'])): ?>checked<?php endif; ?>>
					<?php p($l->t('手机号')); ?>
				</label>
				<label>
					<input type="checkbox" name="match_field_username" value="username"
						   <?php if (in_array('username', $_['config']['user_match_fields'])): ?>checked<?php endif; ?>>
					<?php p($l->t('用户名')); ?>
				</label>
			</div>
			<p class="hint"><?php p($l->t('选择用于匹配现有用户的字段')); ?></p>
		</div>

		<div class="form-group">
			<label for="wecom-default-quota"><?php p($l->t('默认用户配额')); ?></label>
			<input type="text" 
				   id="wecom-default-quota" 
				   name="default_quota" 
				   value="<?php p($_['config']['default_quota']); ?>"
				   placeholder="<?php p($l->t('例如: 10 GB')); ?>">
			<p class="hint"><?php p($l->t('留空则使用系统默认配额')); ?></p>
		</div>

		<div class="form-group">
			<button id="manual-sync" class="button-secondary">
				<?php p($l->t('立即同步')); ?>
			</button>
			<span id="sync-status" class="sync-status"></span>
		</div>
	</div>

	<div class="wecom-settings-actions">
		<button id="test-connection" class="button-secondary">
			<?php p($l->t('测试连接')); ?>
		</button>
		<button id="save-settings" class="button-primary">
			<?php p($l->t('保存设置')); ?>
		</button>
	</div>

	<div id="wecom-message" class="message hidden"></div>
</div>

