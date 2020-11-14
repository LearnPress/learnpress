const path = require( 'path' );
const webpack = require( 'webpack' );
// const ExtractTextPlugin = require('extract-text-webpack-plugin');
// // Set different CSS extraction for editor only and common block styles
// const blocksCSSPlugin = new ExtractTextPlugin({
//     filename: './assets/css/main.css',
// });

const tools = require( './tools/webpack' );

// Configuration for the ExtractTextPlugin.
const extractConfig = {
	use: [
		{ loader: 'raw-loader' },
		{
			loader: 'postcss-loader',
			options: {
				plugins: [ require( 'autoprefixer' ) ],
			},
		},
		{
			loader: 'sass-loader',
			query: {
				outputStyle: 'production' === process.env.NODE_ENV ? 'compressed' : 'nested',
			},
		},
	],
};

module.exports = {
	entry: {
		'./assets/js/dist/admin/editor/course': './assets/src/apps/js/admin/editor/course.js',
		'./assets/js/dist/admin/editor/quiz': './assets/src/apps/js/admin/editor/quiz.js',
		'./assets/js/dist/admin/editor/question': './assets/src/apps/js/admin/editor/question.js',
		'./assets/js/dist/admin/tools/course': './assets/src/apps/components/admin/tools/course.js',
		'./assets/js/dist/utils': './assets/src/js/utils/index.js',
	},
	output: {
		path: path.resolve( __dirname ),
		filename: 'production' === process.env.NODE_ENV ? '[name].min.js' : '[name].js',
	},
	watch: 'production' !== process.env.NODE_ENV,
	devtool: process.env.NODE_ENV === 'production' ? '' : 'source-map',
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@babel/preset-env',
						],
					},
				},
			},
		],
	},
	plugins: [
		//blocksCSSPlugin,
		tools.mergeAndCompressJs,
	],
};
