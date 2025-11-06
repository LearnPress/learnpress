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
		//'./assets/js/dist/admin/editor/course': './assets/src/apps/js/admin/editor/course.js',
		'./assets/dist/js/admin/admin-courses': './assets/src/js/admin/admin-courses.js',
		'./assets/dist/js/admin/edit-course': './assets/src/js/admin/edit-course.js',
		//'./assets/dist/js/admin/edit-curriculum': './assets/src/js/admin/edit-curriculum.js',
		//'./assets/dist/js/admin/edit-curriculum/edit-course-ai': './assets/src/js/admin/edit-curriculum/edit-course-ai.js',
		'./assets/dist/js/admin/edit-quiz': './assets/src/js/admin/edit-quiz.js',
		'./assets/dist/js/admin/edit-question': './assets/src/js/admin/edit-question.js',
		//'./assets/js/dist/admin/editor/quiz': './assets/src/apps/js/admin/editor/quiz.js',
		//'./assets/js/dist/admin/editor/question': './assets/src/apps/js/admin/editor/question.js',
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
		'./assets/js/dist/frontend/curriculum': './assets/src/js/frontend/curriculum.js',
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

		// Block Gutenberg
		'./assets/js/dist/blocks/archive-course-legacy': './assets/src/apps/js/blocks/archive-course-legacy/index.js',
		'./assets/js/dist/blocks/single-course-legacy': './assets/src/apps/js/blocks/single-course-legacy/index.js',
		'./assets/js/dist/blocks/single-course': './assets/src/apps/js/blocks/single-course/index.js',
		'./assets/js/dist/blocks/single-instructor': './assets/src/apps/js/blocks/single-instructor/index.js',
		'./assets/js/dist/blocks/course-address': './assets/src/apps/js/blocks/course-elements/course-address/index.js',
		'./assets/js/dist/blocks/course-title': './assets/src/apps/js/blocks/course-elements/course-title/index.js',
		'./assets/js/dist/blocks/course-featured': './assets/src/apps/js/blocks/course-elements/course-featured/index.js',
		'./assets/js/dist/blocks/course-instructor': './assets/src/apps/js/blocks/course-elements/course-instructor/index.js',
		'./assets/js/dist/blocks/course-categories': './assets/src/apps/js/blocks/course-elements/course-categories/index.js',
		'./assets/js/dist/blocks/course-description': './assets/src/apps/js/blocks/course-elements/course-description/index.js',
		'./assets/js/dist/blocks/course-features': './assets/src/apps/js/blocks/course-elements/course-features/index.js',
		'./assets/js/dist/blocks/course-target-audiences': './assets/src/apps/js/blocks/course-elements/course-target-audiences/index.js',
		'./assets/js/dist/blocks/course-requirements': './assets/src/apps/js/blocks/course-elements/course-requirements/index.js',
		'./assets/js/dist/blocks/course-faqs': './assets/src/apps/js/blocks/course-elements/course-faqs/index.js',
		'./assets/js/dist/blocks/course-curriculum': './assets/src/apps/js/blocks/course-elements/course-curriculum/index.js',
		'./assets/js/dist/blocks/course-instructor-info': './assets/src/apps/js/blocks/course-elements/course-instructor-info/index.js',
		'./assets/js/dist/blocks/course-image': './assets/src/apps/js/blocks/course-elements/course-image/index.js',
		'./assets/js/dist/blocks/course-price': './assets/src/apps/js/blocks/course-elements/course-price/index.js',
		'./assets/js/dist/blocks/course-material': './assets/src/apps/js/blocks/course-elements/course-material/index.js',
		'./assets/js/dist/blocks/course-progress': './assets/src/apps/js/blocks/course-elements/course-progress/index.js',
		'./assets/js/dist/blocks/course-student': './assets/src/apps/js/blocks/course-elements/course-student/index.js',
		'./assets/js/dist/blocks/course-lesson': './assets/src/apps/js/blocks/course-elements/course-lesson/index.js',
		'./assets/js/dist/blocks/course-duration': './assets/src/apps/js/blocks/course-elements/course-duration/index.js',
		'./assets/js/dist/blocks/course-quiz': './assets/src/apps/js/blocks/course-elements/course-quiz/index.js',
		'./assets/js/dist/blocks/course-level': './assets/src/apps/js/blocks/course-elements/course-level/index.js',
		'./assets/js/dist/blocks/course-button': './assets/src/apps/js/blocks/course-elements/course-button/index.js',
		'./assets/js/dist/blocks/course-button-read-more': './assets/src/apps/js/blocks/course-elements/course-button-read-more/index.js',
		'./assets/js/dist/blocks/course-capacity': './assets/src/apps/js/blocks/course-elements/course-capacity/index.js',
		'./assets/js/dist/blocks/course-delivery': './assets/src/apps/js/blocks/course-elements/course-delivery/index.js',
		'./assets/js/dist/blocks/course-offline-lesson': './assets/src/apps/js/blocks/course-elements/course-offline-lesson/index.js',
		'./assets/js/dist/blocks/course-share': './assets/src/apps/js/blocks/course-elements/course-share/index.js',
		'./assets/js/dist/blocks/course-item-curriculum': './assets/src/apps/js/blocks/course-elements/course-item-curriculum/index.js',
		'./assets/js/dist/blocks/course-feature-review': './assets/src/apps/js/blocks/course-elements/course-feature-review/index.js',
		'./assets/js/dist/blocks/course-search': './assets/src/apps/js/blocks/courses/course-search/index.js',
		'./assets/js/dist/blocks/course-order-by': './assets/src/apps/js/blocks/courses/course-order-by/index.js',
		'./assets/js/dist/blocks/course-filter': './assets/src/apps/js/blocks/course-filter/index.js',
		'./assets/js/dist/blocks/course-search-filter': './assets/src/apps/js/blocks/course-filter-elements/course-search-filter/index.js',
		'./assets/js/dist/blocks/course-author-filter': './assets/src/apps/js/blocks/course-filter-elements/course-author-filter/index.js',
		'./assets/js/dist/blocks/course-level-filter': './assets/src/apps/js/blocks/course-filter-elements/course-level-filter/index.js',
		'./assets/js/dist/blocks/course-price-filter': './assets/src/apps/js/blocks/course-filter-elements/course-price-filter/index.js',
		'./assets/js/dist/blocks/course-categories-filter': './assets/src/apps/js/blocks/course-filter-elements/course-categories-filter/index.js',
		'./assets/js/dist/blocks/course-tag-filter': './assets/src/apps/js/blocks/course-filter-elements/course-tag-filter/index.js',
		'./assets/js/dist/blocks/button-submit-filter': './assets/src/apps/js/blocks/course-filter-elements/button-submit-filter/index.js',
		'./assets/js/dist/blocks/button-reset-filter': './assets/src/apps/js/blocks/course-filter-elements/button-reset-filter/index.js',
		'./assets/js/dist/blocks/instructor-background': './assets/src/apps/js/blocks/instructor-elements/instructor-background/index.js',
		'./assets/js/dist/blocks/instructor-avatar': './assets/src/apps/js/blocks/instructor-elements/instructor-avatar/index.js',
		'./assets/js/dist/blocks/instructor-name': './assets/src/apps/js/blocks/instructor-elements/instructor-name/index.js',
		'./assets/js/dist/blocks/instructor-social': './assets/src/apps/js/blocks/instructor-elements/instructor-social/index.js',
		'./assets/js/dist/blocks/instructor-course': './assets/src/apps/js/blocks/instructor-elements/instructor-course/index.js',
		'./assets/js/dist/blocks/instructor-student': './assets/src/apps/js/blocks/instructor-elements/instructor-student/index.js',
		'./assets/js/dist/blocks/instructor-description': './assets/src/apps/js/blocks/instructor-elements/instructor-description/index.js',
		'./assets/js/dist/blocks/breadcrumb': './assets/src/apps/js/blocks/breadcrumb/index.js',
		'./assets/js/dist/blocks/list-courses': './assets/src/apps/js/blocks/courses/list-courses/index.js',
		'./assets/js/dist/blocks/course-item-template': './assets/src/apps/js/blocks/courses/course-item-template/index.js',
		'./assets/js/dist/blocks/course-results': './assets/src/apps/js/blocks/courses/course-results/index.js',
		'./assets/js/dist/blocks/item-close': './assets/src/apps/js/blocks/single-course-item/item-close/index.js',
		'./assets/js/dist/blocks/item-comment': './assets/src/apps/js/blocks/single-course-item/item-comment/index.js',
		'./assets/js/dist/blocks/item-content': './assets/src/apps/js/blocks/single-course-item/item-content/index.js',
		'./assets/js/dist/blocks/item-curriculum': './assets/src/apps/js/blocks/single-course-item/item-curriculum/index.js',
		'./assets/js/dist/blocks/item-hidden-sidebar': './assets/src/apps/js/blocks/single-course-item/item-hidden-sidebar/index.js',
		'./assets/js/dist/blocks/item-navigation': './assets/src/apps/js/blocks/single-course-item/item-navigation/index.js',
		'./assets/js/dist/blocks/item-progress': './assets/src/apps/js/blocks/single-course-item/item-progress/index.js',
		'./assets/js/dist/blocks/item-search': './assets/src/apps/js/blocks/single-course-item/item-search/index.js',

		// Elementor
		'./assets/js/dist/elementor/courses': './assets/src/js/elementor/courses.js',
		'./assets/js/dist/elementor/course-filter': './assets/src/js/elementor/course-filter.js',

		// Site editor check
		'./assets/js/dist/gutenberg/editor-check': './assets/src/apps/js/admin/gutenberg/editor-check.js',
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
					options: {
						// presets: [ '@babel/preset-env' ],
					},
				},
			},
			{
				test: /\.css$/i,
				use: [ 'style-loader', 'css-loader' ],
			},
		],
	},
};
