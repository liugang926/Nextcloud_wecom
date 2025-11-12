<template>
	<div class="sync-dashboard">
		<div class="dashboard-header">
			<h3>{{ t('oauthwecom', '同步状态') }}</h3>
			<button 
				:disabled="syncing" 
				class="button-primary"
				@click="triggerSync">
				<span v-if="syncing" class="icon-loading-small"></span>
				{{ syncing ? t('oauthwecom', '同步中...') : t('oauthwecom', '立即同步') }}
			</button>
		</div>

		<div v-if="lastSyncResult" class="sync-result">
			<div class="result-header" :class="lastSyncResult.success ? 'success' : 'error'">
				<span :class="lastSyncResult.success ? 'icon-checkmark' : 'icon-error'"></span>
				<span class="status-text">
					{{ lastSyncResult.success ? t('oauthwecom', '同步成功') : t('oauthwecom', '同步失败') }}
				</span>
				<span class="sync-time">
					{{ formatTime(lastSyncTime) }}
				</span>
			</div>

			<div v-if="lastSyncResult.success" class="sync-stats">
				<div class="stat-item">
					<span class="stat-label">{{ t('oauthwecom', '总用户数') }}</span>
					<span class="stat-value">{{ lastSyncResult.total_users }}</span>
				</div>
				<div class="stat-item">
					<span class="stat-label">{{ t('oauthwecom', '新建用户') }}</span>
					<span class="stat-value success">{{ lastSyncResult.created_users }}</span>
				</div>
				<div class="stat-item">
					<span class="stat-label">{{ t('oauthwecom', '更新用户') }}</span>
					<span class="stat-value">{{ lastSyncResult.updated_users }}</span>
				</div>
				<div class="stat-item">
					<span class="stat-label">{{ t('oauthwecom', '总部门数') }}</span>
					<span class="stat-value">{{ lastSyncResult.total_departments }}</span>
				</div>
			</div>

			<div v-if="lastSyncResult.errors && lastSyncResult.errors.length > 0" class="sync-errors">
				<h4>{{ t('oauthwecom', '错误信息') }}</h4>
				<ul>
					<li v-for="(error, index) in lastSyncResult.errors" :key="index">
						{{ error }}
					</li>
				</ul>
			</div>
		</div>

		<div v-else class="no-sync-data">
			<p>{{ t('oauthwecom', '暂无同步记录') }}</p>
		</div>
	</div>
</template>

<script>
export default {
	name: 'SyncDashboard',
	data() {
		return {
			syncing: false,
			lastSyncResult: null,
			lastSyncTime: null,
		}
	},
	methods: {
		async triggerSync() {
			this.syncing = true

			try {
				const response = await fetch(OC.generateUrl('/apps/oauthwecom/admin/sync'), {
					method: 'POST',
					headers: {
						'requesttoken': OC.requestToken,
					},
				})

				const result = await response.json()
				
				this.lastSyncResult = result.data || result
				this.lastSyncTime = Date.now()

				if (result.success) {
					OC.Notification.showTemporary(
						this.t('oauthwecom', '用户同步成功'),
						{ type: 'success' }
					)
				} else {
					OC.Notification.showTemporary(
						this.t('oauthwecom', '用户同步失败: ') + result.message,
						{ type: 'error' }
					)
				}
			} catch (error) {
				console.error('Sync failed:', error)
				OC.Notification.showTemporary(
					this.t('oauthwecom', '同步失败: ') + error.message,
					{ type: 'error' }
				)
			} finally {
				this.syncing = false
			}
		},

		formatTime(timestamp) {
			if (!timestamp) {
				return ''
			}
			const date = new Date(timestamp)
			return date.toLocaleString()
		},
	},
}
</script>

<style scoped>
.sync-dashboard {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 20px;
	margin-top: 20px;
}

.dashboard-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
}

.dashboard-header h3 {
	font-size: 18px;
	font-weight: 500;
	margin: 0;
}

.sync-result {
	margin-top: 15px;
}

.result-header {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 15px;
	border-radius: var(--border-radius);
	margin-bottom: 15px;
}

.result-header.success {
	background: rgba(70, 180, 70, 0.1);
	color: var(--color-success);
}

.result-header.error {
	background: rgba(224, 72, 72, 0.1);
	color: var(--color-error);
}

.result-header span {
	font-size: 20px;
}

.status-text {
	font-weight: 500;
	flex: 1;
}

.sync-time {
	font-size: 13px;
	opacity: 0.7;
}

.sync-stats {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin-bottom: 15px;
}

.stat-item {
	background: var(--color-background-dark);
	padding: 15px;
	border-radius: var(--border-radius);
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.stat-label {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.stat-value {
	font-size: 24px;
	font-weight: 600;
	color: var(--color-main-text);
}

.stat-value.success {
	color: var(--color-success);
}

.sync-errors {
	background: rgba(224, 72, 72, 0.1);
	padding: 15px;
	border-radius: var(--border-radius);
	border-left: 3px solid var(--color-error);
}

.sync-errors h4 {
	margin: 0 0 10px 0;
	font-size: 14px;
	font-weight: 500;
	color: var(--color-error);
}

.sync-errors ul {
	margin: 0;
	padding-left: 20px;
}

.sync-errors li {
	margin-bottom: 5px;
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.no-sync-data {
	text-align: center;
	padding: 40px 20px;
	color: var(--color-text-maxcontrast);
}

.button-primary {
	background: var(--color-primary-element);
	color: var(--color-primary-element-text);
	border: none;
	padding: 8px 16px;
	border-radius: var(--border-radius);
	cursor: pointer;
	font-weight: 500;
	display: flex;
	align-items: center;
	gap: 8px;
	transition: background 0.2s;
}

.button-primary:hover:not(:disabled) {
	background: var(--color-primary-element-hover);
}

.button-primary:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}

/* 响应式设计 */
@media (max-width: 768px) {
	.dashboard-header {
		flex-direction: column;
		align-items: flex-start;
		gap: 15px;
	}

	.sync-stats {
		grid-template-columns: 1fr;
	}
}
</style>

