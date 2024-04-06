const path = require('path');
const defaults = require('@wordpress/scripts/config/webpack.config.js');

// Plugins.
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
// const CopyPlugin = require('copy-webpack-plugin');
const FileManagerPlugin = require('filemanager-webpack-plugin');

module.exports = {
	...defaults,
	entry: {
		...defaults.entry,
		'pof-theme/js/main': {
			import: path.resolve(
				process.cwd(),
				'power-of-families-theme',
				'src',
				'main.ts'
			),
		},
		'pof-theme/css/main': {
			import: path.resolve(
				process.cwd(),
				'power-of-families-theme',
				'assets',
				'scss',
				'main.scss'
			),
		},
	},
	output: {
		filename: '[name].js',
		path: path.resolve(process.cwd(), 'dist'),
	},
	module: {
		...defaults.module,
		rules: [
			...defaults.module.rules,
			{
				test: /\.tsx?$/,
				use: [
					{
						loader: 'ts-loader',
						options: {
							configFile: 'tsconfig.json',
							transpileOnly: true,
						},
					},
				],
			},
		],
	},
	resolve: {
		extensions: [
			'.ts',
			'.tsx',
			...(defaults.resolve
				? defaults.resolve.extensions || ['.js', '.jsx']
				: []),
		],
	},
	plugins: [
		...defaults.plugins,
		new RemoveEmptyScriptsPlugin({
			stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS,
		}),
		new FileManagerPlugin({
			events: {
				onEnd: {
					copy: [
						{
							source: './dist/fonts',
							destination: './power-of-families-theme/build/fonts',
						},
						{
							source: './dist/images',
							destination: './power-of-families-theme/build/images',
						},
						{
							source: './dist/pof-theme',
							destination: './power-of-families-theme/build/theme',
						},
					],
				},
			},
		}),
	],
};
