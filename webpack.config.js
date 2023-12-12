const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
const MergeIntoSingleFilePlugin = require( 'webpack-merge-and-include-globally' );
const LearnPressDependencyExtractionWebpackPlugin = require( './packages/dependecy-extraction-webpack-plugin' );

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
	...defaultConfig,
	entry: {
		'./assets/js/dist/admin/admin-notices': './assets/src/apps/js/admin/admin-notices.js',
		'./assets/js/dist/admin/admin-order': './assets/src/js/admin/admin-order.js',
		'./assets/js/dist/admin/admin-tools': './assets/src/js/admin/admin-tools.js',
		'./assets/js/dist/admin/editor/course': './assets/src/apps/js/admin/editor/course.js',
		'./assets/js/dist/admin/editor/quiz': './assets/src/apps/js/admin/editor/quiz.js',
		'./assets/js/dist/admin/editor/question': './assets/src/apps/js/admin/editor/question.js',
		'./assets/js/dist/admin/pages/tools': './assets/src/apps/js/admin/pages/tools.js',
		'./assets/js/dist/admin/pages/setup': './assets/src/apps/js/admin/pages/setup.js',
		'./assets/js/dist/admin/pages/statistic': './assets/src/apps/js/admin/pages/statistic.js',
		'./assets/js/dist/admin/admin-statistic': './assets/src/js/admin/admin-statistic.js',
		'./assets/js/dist/admin/pages/sync-data': './assets/src/apps/js/admin/pages/sync-data.js',
		'./assets/js/dist/admin/pages/themes-addons': './assets/src/apps/js/admin/pages/themes-addons.js',
		'./assets/js/dist/admin/addons': './assets/src/js/admin/addons.js',
		'./assets/js/dist/admin/pages/dashboard': './assets/src/apps/js/admin/pages/dashboard.js',
		'./assets/js/dist/admin/pages/widgets': './assets/src/apps/js/admin/pages/widgets.js',
		'./assets/js/dist/admin/course-material': './assets/src/apps/js/admin/course-material.js',
		'./assets/js/dist/loadAJAX': './assets/src/js/loadAJAX.js',
		'./assets/js/dist/utils': './assets/src/js/utils/index.js',

		'./assets/js/dist/js/data-controls': {
			import: './assets/src/apps/js/data-controls.js',
			library: {
				name: [ 'LP', 'dataControls' ],
				type: 'window',
			},
		},
		'./assets/js/dist/frontend/modal': {
			import: './assets/src/apps/js/frontend/modal.js',
			library: {
				name: [ 'LP', 'modal' ],
				type: 'window',
			},
		},
		'./assets/js/dist/frontend/quiz': {
			import: './assets/src/apps/js/frontend/quiz.js',
			library: {
				name: [ 'LP', 'quiz' ],
				type: 'window',
			},
		},
		'./assets/js/dist/frontend/lp-configs': {
			import: './assets/src/apps/js/frontend/lp-configs.js',
			library: {
				name: [ 'LP', 'config' ],
				type: 'window',
			},
		},
		'./assets/js/dist/frontend/question-types': {
			import: './assets/src/apps/js/frontend/question-types.js',
			library: {
				name: [ 'LP', 'questionTypes' ],
				type: 'window',
			},
		},
		'./assets/js/dist/frontend/courses': './assets/src/js/frontend/courses.js',
		'./assets/js/dist/frontend/instructors': './assets/src/apps/js/frontend/instructors.js',
		'./assets/js/dist/frontend/become-teacher': './assets/src/apps/js/frontend/become-teacher.js',
		'./assets/js/dist/frontend/checkout': './assets/src/apps/js/frontend/checkout.js',
		'./assets/js/dist/frontend/single-course': './assets/src/apps/js/frontend/single-course.js',
		'./assets/js/dist/frontend/single-curriculum': './assets/src/apps/js/frontend/single-curriculum.js',
		'./assets/js/dist/frontend/lesson': './assets/src/apps/js/frontend/lesson.js',
		'./assets/js/dist/frontend/custom': './assets/src/apps/js/frontend/custom.js',
		'./assets/js/dist/frontend/profile': './assets/src/apps/js/frontend/profile.js',
		'./assets/js/dist/frontend/widgets': './assets/src/js/frontend/widgets.js',
		'./assets/js/dist/frontend/course-filter': './assets/src/js/frontend/course-filter.js',
		'./assets/js/dist/blocks/archive-course': './assets/src/apps/js/blocks/archive-course/index.js',
		'./assets/js/dist/blocks/single-course': './assets/src/apps/js/blocks/single-course/index.js',
		'./assets/js/dist/blocks/item-curriculum-course': './assets/src/apps/js/blocks/item-curriculum-course/index.js',

		// Elementor
		'./assets/js/dist/elementor/courses': './assets/src/js/elementor/courses.js',
		'./assets/js/dist/elementor/course-filter': './assets/src/js/elementor/course-filter.js',
	},
	output: {
		path: path.resolve( __dirname ),
		filename: '[name]' + ( isProduction ? '.min.js' : '.js' ),
	},
	plugins: [
		process.env.WP_BUNDLE_ANALYZER && new BundleAnalyzerPlugin(),

		// WP_NO_EXTERNALS global variable controls whether scripts' assets get
		// generated, and the default externals set.
		! process.env.WP_NO_EXTERNALS && new DependencyExtractionWebpackPlugin(),

		/*new MergeIntoSingleFilePlugin( {
			files: {
				'assets/js/vendor/plugins.all.js': [
					'./assets/src/js/vendor/watch.js',
					'./assets/src/js/vendor/jquery/jquery-scrollTo.js',
					'./assets/src/js/vendor/jquery/jquery-timer.js',
					'./assets/src/js/vendor/jquery/jquery.tipsy.js',
				],
				'assets/js/vendor/vue/vue_libs.js': [
					'./assets/src/js/vendor/vue/vue.js',
					'./assets/src/js/vendor/vue/vuex.js',
					'./assets/src/js/vendor/vue/vue-resource.js',
				],
			},
		} ),*/
		new LearnPressDependencyExtractionWebpackPlugin( {
			namespace: '@learnpress',
			library: 'LP',
		} ),
	].filter( Boolean ),
};
