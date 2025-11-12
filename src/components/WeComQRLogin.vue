<template>
	<div class="wecom-qr-login">
		<div class="qr-container">
			<h3>{{ t('oauthwecom', '企业微信扫码登录') }}</h3>
			<div v-if="loading" class="loading">
				<span class="icon-loading"></span>
				<p>{{ t('oauthwecom', '正在加载...') }}</p>
			</div>
			<div v-else-if="error" class="error">
				<span class="icon-error"></span>
				<p>{{ errorMessage }}</p>
				<button @click="reload" class="button-primary">
					{{ t('oauthwecom', '重新加载') }}
				</button>
			</div>
			<div v-else class="qr-code">
				<div id="wecom-qr-code" class="qr-image"></div>
				<p class="hint">{{ t('oauthwecom', '请使用企业微信扫描二维码登录') }}</p>
			</div>
		</div>
		<div class="alternative-login">
			<a :href="manualLoginUrl" class="manual-login-link">
				{{ t('oauthwecom', '或使用密码登录') }}
			</a>
		</div>
	</div>
</template>

<script>
export default {
	name: 'WeComQRLogin',
	data() {
		return {
			loading: true,
			error: false,
			errorMessage: '',
			manualLoginUrl: OC.generateUrl('/login'),
			authUrl: '',
		}
	},
	mounted() {
		this.initQRCode()
	},
	methods: {
		async initQRCode() {
			this.loading = true
			this.error = false

			try {
				// 获取OAuth授权URL
				const authUrl = OC.generateUrl('/apps/oauthwecom/oauth/authorize')
				
				// 对于PC端，直接重定向到授权页面
				// 企业微信会在PC浏览器上显示二维码
				this.authUrl = authUrl
				
				// 可以选择：
				// 1. 直接重定向（推荐）
				window.location.href = authUrl
				
				// 2. 或者在页面中嵌入二维码（需要企业微信JS-SDK支持）
				// this.embedQRCode()
				
			} catch (error) {
				console.error('Failed to initialize QR code:', error)
				this.error = true
				this.errorMessage = this.t('oauthwecom', '加载二维码失败，请重试')
			} finally {
				this.loading = false
			}
		},
		
		embedQRCode() {
			// 如果需要在页面中嵌入二维码，可以使用企业微信提供的JS-SDK
			// 或者使用第三方二维码库生成二维码
			// 这里提供一个简单的实现示例
			
			const container = document.getElementById('wecom-qr-code')
			if (container) {
				// 使用qrcode.js或类似库生成二维码
				// 或者直接显示一个提示让用户点击链接
				container.innerHTML = `
					<a href="${this.authUrl}" class="qr-link">
						<div class="qr-placeholder">
							<span class="icon-link"></span>
							<p>${this.t('oauthwecom', '点击进入企业微信登录')}</p>
						</div>
					</a>
				`
			}
			this.loading = false
		},
		
		reload() {
			this.initQRCode()
		},
	},
}
</script>

<style scoped>
.wecom-qr-login {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 40px 20px;
	max-width: 500px;
	margin: 0 auto;
}

.qr-container {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 30px;
	text-align: center;
	width: 100%;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.qr-container h3 {
	font-size: 20px;
	font-weight: 600;
	margin-bottom: 20px;
	color: var(--color-main-text);
}

.loading,
.error {
	padding: 40px 20px;
}

.loading span,
.error span {
	font-size: 48px;
	display: block;
	margin-bottom: 15px;
}

.loading p,
.error p {
	color: var(--color-text-maxcontrast);
	margin-bottom: 15px;
}

.qr-code {
	padding: 20px 0;
}

.qr-image {
	width: 280px;
	height: 280px;
	margin: 0 auto 20px;
	display: flex;
	align-items: center;
	justify-content: center;
	background: var(--color-background-dark);
	border-radius: var(--border-radius);
	border: 2px dashed var(--color-border);
}

.qr-placeholder {
	text-align: center;
	padding: 40px;
}

.qr-placeholder .icon-link {
	font-size: 64px;
	color: var(--color-primary-element);
	display: block;
	margin-bottom: 15px;
}

.qr-placeholder p {
	color: var(--color-main-text);
	font-weight: 500;
}

.qr-link {
	text-decoration: none;
	display: block;
	transition: transform 0.2s;
}

.qr-link:hover {
	transform: scale(1.05);
}

.hint {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	margin-top: 10px;
}

.alternative-login {
	margin-top: 30px;
	text-align: center;
}

.manual-login-link {
	color: var(--color-primary-element);
	text-decoration: none;
	font-size: 14px;
	transition: opacity 0.2s;
}

.manual-login-link:hover {
	opacity: 0.8;
	text-decoration: underline;
}

.button-primary {
	background: var(--color-primary-element);
	color: var(--color-primary-element-text);
	border: none;
	padding: 10px 20px;
	border-radius: var(--border-radius);
	cursor: pointer;
	font-weight: 500;
	transition: background 0.2s;
}

.button-primary:hover {
	background: var(--color-primary-element-hover);
}

/* 响应式设计 */
@media (max-width: 768px) {
	.wecom-qr-login {
		padding: 20px 10px;
	}

	.qr-container {
		padding: 20px;
	}

	.qr-image {
		width: 240px;
		height: 240px;
	}
}
</style>

