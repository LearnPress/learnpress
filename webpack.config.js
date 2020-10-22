const path = require( 'path' );
const webpack = require( 'webpack' );
const tools = require( './tools/webpack' );

module.exports = {
	entry: {
		'./assets/js/admin/admin': './assets/src/js/admin/admin.js',
		'./assets/js/admin/learnpress': './assets/src/js/admin/learnpress.js',
		'./assets/js/admin/utils': './assets/src/js/admin/utils/index.js',
		'./assets/js/admin/editor/course': './assets/src/js/admin/editor/course.js',
		'./assets/js/admin/editor/quiz': './assets/src/js/admin/editor/quiz.js',
		'./assets/js/admin/editor/question': './assets/src/js/admin/editor/question.js',
		'./assets/js/admin/partial/meta-box-order': './assets/src/js/admin/partial/meta-box-order.js',
		'./assets/js/admin/pages/statistic': './assets/src/js/admin/pages/statistic.js',
		'./assets/js/admin/pages/setup': './assets/src/js/admin/pages/setup.js',
		'./assets/js/frontend/learnpress': './assets/src/js/frontend/learnpress.js',
		'./assets/js/frontend/utils': './assets/src/js/frontend/utils/index.js',
		'./assets/js/global': './assets/src/js/global.js',
		'./assets/js/utils': './assets/src/js/utils/index.js',
		'./assets/js/frontend/courses': './assets/src/js/frontend/courses.js',
		//'./assets/js/frontend/single-course': './assets/src/js/frontend/single-course.js',

		'./assets/js/frontend/checkout': './assets/src/js/frontend/checkout.js',
		'./assets/js/frontend/become-teacher': './assets/src/js/frontend/become-teacher.js',
		'./assets/js/frontend/custom': './assets/src/js/frontend/custom.js',
		'./assets/js/frontend/profile': './assets/src/js/frontend/profile.js',
		//'./assets/js/frontend/question-types': './assets/src/js/frontend/question-types.js',
		//'./assets/js/frontend/single-course': './assets/src/js/frontend/single-course.js',
	},
	output: {
		path: path.resolve( __dirname ),
		filename: 'production' === process.env.NODE_ENV ? '[name].min.js' : '[name].js',
	},
	watch: false,
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
						plugins: [
						    '@babel/plugin-transform-async-to-generator',
							'@babel/plugin-proposal-object-rest-spread',
						],
					},
				},
			},
		],
	},
	plugins: [
		tools.mergeAndCompressJs,
	],
};
