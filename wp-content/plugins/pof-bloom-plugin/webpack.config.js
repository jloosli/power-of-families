const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	entry: {
		admin: path.resolve(__dirname, 'assets/js/admin.js'),
		frontend: path.resolve(__dirname, 'assets/js/frontend.js'),
		settings: path.resolve(__dirname, 'assets/js/settings.js'),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'build'),
	},
};
