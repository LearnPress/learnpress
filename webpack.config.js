const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );
//const MergeIntoSingleFilePlugin = require( 'webpack-merge-and-include-globally' );
const LearnPressDependencyExtractionWebpackPlugin = require( './packages/dependecy-extraction-webpack-plugin' );

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
	...defaultConfig,
	entry: {
		'./assets/js/dist/admin/admin': './assets/src/js/admin/admin.js',
		'./assets/js/dist/admin/learnpress': './assets/src/js/admin/learnpress.js',
		'./assets/js/dist/admin/admin-notices': './assets/src/js/admin/admin-notices.js',
		'./assets/js/dist/admin/admin-order': './assets/src/js/admin/admin-order.js',
		'./assets/js/dist/admin/admin-tools': './assets/src/js/admin/admin-tools.js',
		'./assets/js/dist/admin/editor/course': './assets/src/apps/js/admin/editor/course.js',
		'./assets/js/dist/admin/editor/quiz': './assets/src/apps/js/admin/editor/quiz.js',
		'./assets/js/dist/admin/editor/question': './assets/src/apps/js/admin/editor/question.js',
		'./assets/js/dist/admin/pages/tools': './assets/src/apps/js/admin/pages/tools.js',
		'./assets/js/dist/admin/pages/setup': './assets/src/apps/js/admin/pages/setup.js',
		//'./assets/js/dist/admin/pages/statistic': './assets/src/apps/js/admin/pages/statistic.js',
		'./assets/js/dist/admin/admin-statistic': './assets/src/js/admin/admin-statistic.js',
		// './assets/js/dist/admin/pages/sync-data': './assets/src/apps/js/admin/pages/sync-data.js',
		'./assets/js/dist/admin/pages/themes-addons': './assets/src/apps/js/admin/pages/themes-addons.js',
		'./assets/js/dist/admin/addons': './assets/src/js/admin/addons.js',
		'./assets/js/dist/admin/pages/dashboard': './assets/src/apps/js/admin/pages/dashboard.js',
		'./assets/js/dist/admin/pages/widgets': './assets/src/apps/js/admin/pages/widgets.js',
		'./assets/js/dist/admin/course-material': './assets/src/js/admin/course-material.js',
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
		'./assets/js/dist/frontend/courses-v2': './assets/src/js/frontend/courses-v2.js',
		'./assets/js/dist/frontend/profile': './assets/src/js/frontend/profile.js',
		'./assets/js/dist/frontend/instructors': './assets/src/apps/js/frontend/instructors.js',
		'./assets/js/dist/frontend/become-teacher': './assets/src/js/frontend/become-teacher.js',
		'./assets/js/dist/frontend/checkout': './assets/src/js/frontend/checkout.js',
		'./assets/js/dist/frontend/single-course': './assets/src/apps/js/frontend/single-course.js',
		'./assets/js/dist/frontend/single-curriculum': './assets/src/apps/js/frontend/single-curriculum.js',
		'./assets/js/dist/frontend/lesson': './assets/src/apps/js/frontend/lesson.js',
		'./assets/js/dist/frontend/custom': './assets/src/apps/js/frontend/custom.js',
		'./assets/js/dist/frontend/widgets': './assets/src/js/frontend/widgets.js',
		'./assets/js/dist/frontend/course-filter': './assets/src/js/frontend/course-filter.js',
		'./assets/js/dist/blocks/archive-course': './assets/src/apps/js/blocks/archive-course/index.js',
		'./assets/js/dist/blocks/title-single-course': './assets/src/apps/js/blocks/widget-single-course/title-single-course/index.js',
		'./assets/js/dist/blocks/description-single-course': './assets/src/apps/js/blocks/widget-single-course/description-single-course/index.js',
		'./assets/js/dist/blocks/categories-single-course': './assets/src/apps/js/blocks/widget-single-course/categories-single-course/index.js',
		'./assets/js/dist/blocks/tags-single-course': './assets/src/apps/js/blocks/widget-single-course/tags-single-course/index.js',
		'./assets/js/dist/blocks/image-single-course': './assets/src/apps/js/blocks/widget-single-course/image-single-course/index.js',
		'./assets/js/dist/blocks/instructor-single-course': './assets/src/apps/js/blocks/widget-single-course/instructor-single-course/index.js',
		'./assets/js/dist/blocks/tabs-single-course': './assets/src/apps/js/blocks/widget-single-course/tabs-single-course/index.js',
		'./assets/js/dist/blocks/price-single-course': './assets/src/apps/js/blocks/widget-single-course/price-single-course/index.js',
		'./assets/js/dist/blocks/feature-review-single-course': './assets/src/apps/js/blocks/widget-single-course/feature-review-single-course/index.js',
		'./assets/js/dist/blocks/level-single-course': './assets/src/apps/js/blocks/widget-single-course/level-single-course/index.js',
		'./assets/js/dist/blocks/student-single-course': './assets/src/apps/js/blocks/widget-single-course/student-single-course/index.js',
		'./assets/js/dist/blocks/lesson-single-course': './assets/src/apps/js/blocks/widget-single-course/lesson-single-course/index.js',
		'./assets/js/dist/blocks/quiz-single-course': './assets/src/apps/js/blocks/widget-single-course/quiz-single-course/index.js',
		'./assets/js/dist/blocks/btn-purchase-single-course': './assets/src/apps/js/blocks/widget-single-course/btn-purchase-single-course/index.js',
		'./assets/js/dist/blocks/duration-single-course': './assets/src/apps/js/blocks/widget-single-course/duration-single-course/index.js',
		'./assets/js/dist/blocks/requirements-single-course': './assets/src/apps/js/blocks/widget-single-course/requirements-single-course/index.js',
		'./assets/js/dist/blocks/features-single-course': './assets/src/apps/js/blocks/widget-single-course/features-single-course/index.js',
		'./assets/js/dist/blocks/target-audiences-single-course': './assets/src/apps/js/blocks/widget-single-course/target-audiences-single-course/index.js',
		'./assets/js/dist/blocks/time-single-course': './assets/src/apps/js/blocks/widget-single-course/time-single-course/index.js',
		'./assets/js/dist/blocks/progress-single-course': './assets/src/apps/js/blocks/widget-single-course/progress-single-course/index.js',
		'./assets/js/dist/blocks/breadcrumb': './assets/src/apps/js/blocks/breadcrumb/index.js',
		'./assets/js/dist/blocks/comment': './assets/src/apps/js/blocks/widget-single-course/comment/index.js',
		'./assets/js/dist/blocks/lp-single-course': './assets/src/apps/js/blocks/widget-single-course/lp-single-course/index.js',
		'./assets/js/dist/blocks/course-summary': './assets/src/apps/js/blocks/widget-single-course/course-summary/index.js',
		'./assets/js/dist/blocks/course-detail-info': './assets/src/apps/js/blocks/widget-single-course/course-detail-info/index.js',
		'./assets/js/dist/blocks/course-meta-primary': './assets/src/apps/js/blocks/widget-single-course/course-meta-primary/index.js',
		'./assets/js/dist/blocks/course-meta-secondary': './assets/src/apps/js/blocks/widget-single-course/course-meta-secondary/index.js',
		'./assets/js/dist/blocks/lp-content-area': './assets/src/apps/js/blocks/widget-single-course/lp-content-area/index.js',
		'./assets/js/dist/blocks/content-left': './assets/src/apps/js/blocks/widget-single-course/content-left/index.js',
		'./assets/js/dist/blocks/course-summary-sidebar': './assets/src/apps/js/blocks/widget-single-course/course-summary-sidebar/index.js',
		'./assets/js/dist/blocks/search-archive-course': './assets/src/apps/js/blocks/widget-archive-course/search/index.js',
		'./assets/js/dist/blocks/order-by-archive-course': './assets/src/apps/js/blocks/widget-archive-course/order-by/index.js',
		'./assets/js/dist/blocks/template-course-archive-course': './assets/src/apps/js/blocks/widget-archive-course/template-course/index.js',
		'./assets/js/dist/blocks/list-course-archive-course': './assets/src/apps/js/blocks/widget-archive-course/list-course/index.js',
		'./assets/js/dist/blocks/sidebar-archive-course': './assets/src/apps/js/blocks/widget-archive-course/sidebar/index.js',
		'./assets/js/dist/blocks/pagination-archive-course': './assets/src/apps/js/blocks/widget-archive-course/pagination/index.js',
		'./assets/js/dist/blocks/title-course-archive-course': './assets/src/apps/js/blocks/widget-archive-course/title-course/index.js',
		'./assets/js/dist/blocks/meta-course-archive-course': './assets/src/apps/js/blocks/widget-archive-course/meta-course/index.js',
		'./assets/js/dist/blocks/media-course-archive-course': './assets/src/apps/js/blocks/widget-archive-course/media-course/index.js',
		'./assets/js/dist/blocks/instructor-category-archive-course': './assets/src/apps/js/blocks/widget-archive-course/instructor-category/index.js',
		'./assets/js/dist/blocks/info-course-archive-course': './assets/src/apps/js/blocks/widget-archive-course/info-course/index.js',
		'./assets/js/dist/blocks/lp-archive-course': './assets/src/apps/js/blocks/widget-archive-course/lp-archive-course/index.js',
		'./assets/js/dist/blocks/profile-username': './assets/src/apps/js/blocks/widget-profile/username/index.js',
		'./assets/js/dist/blocks/profile-background-image': './assets/src/apps/js/blocks/widget-profile/background-image/index.js',
		'./assets/js/dist/blocks/profile-content': './assets/src/apps/js/blocks/widget-profile/content/index.js',
		'./assets/js/dist/blocks/lp-profile': './assets/src/apps/js/blocks/widget-profile/lp-profile/index.js',
		'./assets/js/dist/blocks/profile-sidebar': './assets/src/apps/js/blocks/widget-profile/sidebar/index.js',
		'./assets/js/dist/blocks/profile-avatar': './assets/src/apps/js/blocks/widget-profile/avatar/index.js',
		'./assets/js/dist/blocks/single-course': './assets/src/apps/js/blocks/single-course/index.js',
		'./assets/js/dist/blocks/item-curriculum-course': './assets/src/apps/js/blocks/widget-single-course/item-curriculum-course/index.js',

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
	resolve: {
		// Add `.ts` and `.tsx` as a resolvable extension.
		extensions: [ '.ts', '.tsx', '.js', '.css', '.scss' ],
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
				},
			},
			{
				test: /\.css$/i,
				use: [ 'style-loader', 'css-loader' ],
			},
		],
	},
};
