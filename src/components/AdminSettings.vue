<template>
	<div id="oauthwecom-admin-settings">
		<!-- 设置页面将由模板渲染 -->
	</div>
</template>

<script>
export default {
	name: 'AdminSettings',
	mounted() {
		this.initEventListeners()
	},
	methods: {
		initEventListeners() {
			// 保存设置按钮
			const saveButton = document.getElementById('save-settings')
			if (saveButton) {
				saveButton.addEventListener('click', this.saveSettings)
			}

			// 测试连接按钮
			const testButton = document.getElementById('test-connection')
			if (testButton) {
				testButton.addEventListener('click', this.testConnection)
			}

			// 复制回调URL按钮
			const copyButton = document.getElementById('copy-callback-url')
			if (copyButton) {
				copyButton.addEventListener('click', this.copyCallbackUrl)
			}

			// 立即同步按钮
			const syncButton = document.getElementById('manual-sync')
			if (syncButton) {
				syncButton.addEventListener('click', this.manualSync)
			}
		},

		async saveSettings() {
			const button = document.getElementById('save-settings')
			button.disabled = true
			button.textContent = '保存中...'

			try {
				const config = this.getFormData()
				const response = await fetch(OC.generateUrl('/apps/oauthwecom/admin/config'), {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'requesttoken': OC.requestToken,
					},
					body: JSON.stringify(config),
				})

				const result = await response.json()
				
				if (result.success) {
					this.showMessage('success', result.message || '配置保存成功')
				} else {
					this.showMessage('error', result.message || '配置保存失败')
				}
			} catch (error) {
				console.error('Failed to save settings:', error)
				this.showMessage('error', '保存失败: ' + error.message)
			} finally {
				button.disabled = false
				button.textContent = '保存设置'
			}
		},

		async testConnection() {
			const button = document.getElementById('test-connection')
			button.disabled = true
			button.textContent = '测试中...'

			try {
				const response = await fetch(OC.generateUrl('/apps/oauthwecom/admin/test-connection'), {
					method: 'POST',
					headers: {
						'requesttoken': OC.requestToken,
					},
				})

				const result = await response.json()
				
				if (result.success) {
					this.showMessage('success', result.message || '连接测试成功')
				} else {
					this.showMessage('error', result.message || '连接测试失败')
				}
			} catch (error) {
				console.error('Connection test failed:', error)
				this.showMessage('error', '测试失败: ' + error.message)
			} finally {
				button.disabled = false
				button.textContent = '测试连接'
			}
		},

		copyCallbackUrl() {
			const input = document.getElementById('wecom-callback-url')
			if (input) {
				input.select()
				document.execCommand('copy')
				this.showMessage('success', '回调地址已复制到剪贴板')
			}
		},

		async manualSync() {
			const button = document.getElementById('manual-sync')
			const statusEl = document.getElementById('sync-status')
			
			button.disabled = true
			button.textContent = '同步中...'
			statusEl.textContent = '正在同步用户数据...'
			statusEl.className = 'sync-status syncing'

			try {
				const response = await fetch(OC.generateUrl('/apps/oauthwecom/admin/sync'), {
					method: 'POST',
					headers: {
						'requesttoken': OC.requestToken,
					},
				})

				const result = await response.json()
				
				if (result.success) {
					statusEl.textContent = result.message || '同步完成'
					statusEl.className = 'sync-status success'
					this.showMessage('success', result.message || '用户同步成功')
				} else {
					statusEl.textContent = '同步失败'
					statusEl.className = 'sync-status error'
					this.showMessage('error', result.message || '用户同步失败')
				}
			} catch (error) {
				console.error('Sync failed:', error)
				statusEl.textContent = '同步失败'
				statusEl.className = 'sync-status error'
				this.showMessage('error', '同步失败: ' + error.message)
			} finally {
				button.disabled = false
				button.textContent = '立即同步'
			}
		},

		getFormData() {
			return {
				corp_id: document.getElementById('wecom-corp-id')?.value || '',
				agent_id: document.getElementById('wecom-agent-id')?.value || '',
				app_secret: document.getElementById('wecom-app-secret')?.value || '',
				callback_url: document.getElementById('wecom-callback-url')?.value || '',
				enabled: document.getElementById('wecom-enabled')?.checked || false,
				force_login: document.getElementById('wecom-force-login')?.checked || false,
				disable_password_login: document.getElementById('wecom-disable-password')?.checked || false,
				auto_create_user: document.getElementById('wecom-auto-create')?.checked || false,
				sync_enabled: document.getElementById('wecom-sync-enabled')?.checked || false,
				sync_interval: parseInt(document.getElementById('wecom-sync-interval')?.value) || 60,
				sync_mode: document.getElementById('wecom-sync-mode')?.value || 'full',
				map_departments_to_groups: document.getElementById('wecom-map-departments')?.checked || false,
				default_quota: document.getElementById('wecom-default-quota')?.value || '',
			}
		},

		showMessage(type, message) {
			const messageEl = document.getElementById('wecom-message')
			if (messageEl) {
				messageEl.textContent = message
				messageEl.className = `message ${type}`
				messageEl.classList.remove('hidden')

				setTimeout(() => {
					messageEl.classList.add('hidden')
				}, 5000)
			}
		},
	},
}
</script>

<style scoped>
/* 样式将在单独的CSS文件中定义 */
</style>

