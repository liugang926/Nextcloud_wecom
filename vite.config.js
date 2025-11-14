import { createAppConfig } from '@nextcloud/vite-config'

export default createAppConfig({
	main: 'src/main.js',
	adminSettings: 'src/admin-settings.js',
}, {
	inlineCSS: true,
	config: {
		css: {
			modules: {
				localsConvention: 'camelCase',
			},
		},
	},
})
