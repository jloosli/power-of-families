const path = require('path');
const defaults = require('@wordpress/scripts/config/webpack.config.js');

// Constants
const THEME_DIR = 'pof-theme';

// Plugins.
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
// const CopyPlugin = require('copy-webpack-plugin');
const FileManagerPlugin = require('filemanager-webpack-plugin');

module.exports = {
	...defaults,
	entry: {
		...defaults.entry,
		'js/main': {
			import: path.resolve(
				process.cwd(),
				THEME_DIR,
				'assets',
				'ts',
				'main',
				'index.ts'
			),
		},
		'css/main': {
			import: path.resolve(
				process.cwd(),
				THEME_DIR,
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
						// {
						// 	source: './dist/fonts',
						// 	destination: `./${THEME_DIR}/build/fonts`,
						// },
						// {
						// 	source: './dist/images',
						// 	destination: `./${THEME_DIR}/build/images`,
						// },
						{
							source: './dist',
							destination: `./${THEME_DIR}/build`,
						},
					],
				},
			},
		}),
	],
};
