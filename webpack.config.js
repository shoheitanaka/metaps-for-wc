const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

// Export configuration.
module.exports = {
	...defaultConfig,
	entry: {
		'admin/settings': '/src/metaps/admin/index.js',
		'frontend/payments/creditcardtoken': '/src/metaps/frontend/payments/creditcardtoken/index.js',
		'frontend/payments/creditcard': '/src/metaps/frontend/payments/creditcard/index.js',
		'frontend/payments/conveniencestore': '/src/metaps/frontend/payments/conveniencestore/index.js',
		'frontend/payments/payeasy': '/src/metaps/frontend/payments/payeasy/index.js',
	},
	output: {
		path: path.resolve( __dirname, 'includes/gateways/metaps/asset' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	]
};
