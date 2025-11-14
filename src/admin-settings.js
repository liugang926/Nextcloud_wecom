import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

// 使用 Nextcloud 原生通知 API
const showSuccess = (msg) => {
	if (window.OC && window.OC.Notification) {
		window.OC.Notification.showTemporary(msg, { type: 'success' })
	} else {
		console.log('Success:', msg)
	}
}

const showError = (msg) => {
	if (window.OC && window.OC.Notification) {
		window.OC.Notification.showTemporary(msg, { type: 'error' })
	} else {
		console.error('Error:', msg)
	}
}

document.addEventListener('DOMContentLoaded', function() {
	const saveButton = document.getElementById('save-settings')
	const testButton = document.getElementById('test-connection')
	const syncButton = document.getElementById('manual-sync')
	const copyButton = document.getElementById('copy-callback-url')

	// 保存设置
	if (saveButton) {
		saveButton.addEventListener('click', async function() {
			const corpId = document.getElementById('wecom-corp-id')?.value || ''
			const agentId = document.getElementById('wecom-agent-id')?.value || ''
			const appSecret = document.getElementById('wecom-app-secret')?.value || ''
			const enabled = document.getElementById('wecom-enabled')?.checked || false
			const forceLogin = document.getElementById('wecom-force-login')?.checked || false
			const autoCreateUser = document.getElementById('wecom-auto-create')?.checked || false
			const syncEnabled = document.getElementById('wecom-sync-enabled')?.checked || false
			const syncFrequency = parseInt(document.getElementById('wecom-sync-frequency')?.value) || 24
			const defaultQuota = document.getElementById('wecom-default-quota')?.value || 'default'

			// 获取用户匹配字段
			const userMatchFields = []
			if (document.querySelector('input[name="match_field_email"]')?.checked) {
				userMatchFields.push('email')
			}
			if (document.querySelector('input[name="match_field_phone"]')?.checked) {
				userMatchFields.push('phone')
			}
			if (document.querySelector('input[name="match_field_username"]')?.checked) {
				userMatchFields.push('username')
			}

			try {
				saveButton.disabled = true
				saveButton.textContent = '保存中...'

				const response = await axios.post(
					generateUrl('/apps/oauthwecom/admin/config'),
					{
						corpId,
						agentId,
						appSecret,
						enabled,
						forceLogin,
						autoCreateUser,
						syncEnabled,
						syncFrequency,
						userMatchFields,
						defaultQuota,
					}
				)

				if (response.data.status === 'success') {
					showSuccess(response.data.message || '配置保存成功')
				} else {
					showError(response.data.message || '保存配置失败')
				}
			} catch (error) {
				console.error('Save config error:', error)
				showError('保存配置失败: ' + (error.response?.data?.message || error.message))
			} finally {
				saveButton.disabled = false
				saveButton.textContent = '保存设置'
			}
		})
	}

	// 测试连接
	if (testButton) {
		testButton.addEventListener('click', async function() {
			try {
				testButton.disabled = true
				testButton.textContent = '测试中...'

				const response = await axios.post(
					generateUrl('/apps/oauthwecom/admin/test-connection')
				)

				if (response.data.status === 'success') {
					showSuccess(response.data.message || '连接测试成功')
				} else {
					showError(response.data.message || '连接测试失败')
				}
			} catch (error) {
				console.error('Test connection error:', error)
				showError('连接测试失败: ' + (error.response?.data?.message || error.message))
			} finally {
				testButton.disabled = false
				testButton.textContent = '测试连接'
			}
		})
	}

	// 手动同步
	if (syncButton) {
		syncButton.addEventListener('click', async function() {
			const syncStatus = document.getElementById('sync-status')
			
			try {
				syncButton.disabled = true
				syncButton.textContent = '同步中...'
				if (syncStatus) {
					syncStatus.textContent = '正在同步...'
					syncStatus.className = 'sync-status syncing'
				}

				const response = await axios.post(
					generateUrl('/apps/oauthwecom/admin/sync')
				)

				if (response.data.status === 'success') {
					showSuccess(response.data.message || '同步成功')
					if (syncStatus) {
						syncStatus.textContent = '同步完成'
						syncStatus.className = 'sync-status success'
					}
				} else {
					showError(response.data.message || '同步失败')
					if (syncStatus) {
						syncStatus.textContent = '同步失败'
						syncStatus.className = 'sync-status error'
					}
				}
			} catch (error) {
				console.error('Manual sync error:', error)
				showError('同步失败: ' + (error.response?.data?.message || error.message))
				if (syncStatus) {
					syncStatus.textContent = '同步失败'
					syncStatus.className = 'sync-status error'
				}
			} finally {
				syncButton.disabled = false
				syncButton.textContent = '立即同步'
			}
		})
	}

	// 复制回调URL
	if (copyButton) {
		copyButton.addEventListener('click', function() {
			const callbackUrl = document.getElementById('wecom-callback-url')
			if (callbackUrl) {
				callbackUrl.select()
				document.execCommand('copy')
				showSuccess('回调地址已复制到剪贴板')
			}
		})
	}

	// 启用/禁用同步相关控件
	const syncEnabledCheckbox = document.getElementById('wecom-sync-enabled')
	if (syncEnabledCheckbox) {
		const toggleSyncControls = function() {
			const isEnabled = syncEnabledCheckbox.checked
			const syncFrequencyInput = document.getElementById('wecom-sync-frequency')
			const manualSyncButton = document.getElementById('manual-sync')
			
			if (syncFrequencyInput) {
				syncFrequencyInput.disabled = !isEnabled
			}
			if (manualSyncButton) {
				// 手动同步始终可用
			}
		}

		syncEnabledCheckbox.addEventListener('change', toggleSyncControls)
		toggleSyncControls() // 初始化状态
	}
})
