const MergeIntoSingleFilePlugin = require( 'webpack-merge-and-include-globally' );
const uglifyJS = require( 'uglify-js' );
const uglifyCSS = require( 'uglifycss' );

const isCompressed = function isCompressed( code, n = 5 ) {
	const m = code.match( /\n/gm );

	return ! m || ( m.length <= n );
};

const minifyJsDest = function minifyJsDest( dest, code, isDev ) {
	if ( isDev === undefined ) {
		isDev = process.env.NODE_ENV !== 'production';
	}

	let min = ! isDev ? '.min' : '';

	if ( ! min && isCompressed( code ) ) {
		min = '.min';
	}

	if ( dest.indexOf( '.min' ) !== -1 ) {
		min = '';
	}
	code = ! isDev ? uglifyJS.minify( code ).code : code;

	return {
		[ `${ dest }${ min }.js` ]: code,
	};
};

const minifyCssDest = function minifyCssDest( dest, code ) {
	const isDev = false;//process.env.NODE_ENV !== 'production';
	const min = ! isDev ? '.min' : '';
	code = ! isDev ? uglifyCSS.processString( code ) : code;

	return {
		[ `${ dest }${ min }.css` ]: code,
	};
};

const isDev = function isDev() {
	return process.env.NODE_ENV !== 'production';
};

const adminSources = () => [
	//'./assets/src/js/vendor/vue/vue.js',
	// './assets/src/js/vendor/vue/vuex.js',
	// './assets/src/js/vendor/vue/vue-resource.js',
	// './assets/src/js/vendor/vue/vue-draggable.js',
	//'./assets/src/js/vendor/jquery/jquery.tipsy.js',
	// './assets/src/js/vendor/chart.min.js',
];

const frontendSources = () => [
	// './assets/src/js/vendor/vue/vue' + ( isDev() ? '' : '.min' ) + '.js',
	// './assets/src/js/vendor/vue/vuex.js',
	// './assets/src/js/vendor/vue/vue-resource.js',
	// './assets/src/js/vendor/vue_libs_c.min.js',
	'./assets/src/js/vendor/watch.js',
	//'./assets/src/js/vendor/jquery/jquery-alert.js',
	'./assets/src/js/vendor/jquery/jquery-appear.js',
	'./assets/src/js/vendor/jquery/jquery-scrollTo.js',
	'./assets/src/js/vendor/jquery/jquery-timer.js',
	'./assets/src/js/vendor/jquery/jquery.tipsy.js',
	'./assets/src/js/vendor/jquery/jquery.scrollbar.js',
];

/** Merge vue libs **/
const vueSources = () => [
	'./assets/src/js/vendor/vue/vue.js',
	'./assets/src/js/vendor/vue/vuex.js',
	'./assets/src/js/vendor/vue/vue-resource.js',
];

const options = {
	files: [
		{ // Run this can error code vue - add on frontend editor
			src: frontendSources(),
			dest( code ) {
				return minifyJsDest( 'assets/js/vendor/plugins.all', code );
			},
		},
		// {
		//     src: adminSources(),
		//     dest: function (code) {
		//         return minifyJsDest('assets/js/vendor/admin.plugins.all', code);
		//     }
		// },
		{
			src: vueSources(),
			dest( code ) {
				return minifyJsDest( 'assets/js/vendor/vue/vue_libs', code );
			},
		},
		{
			src: [
				'./assets/src/css/vendor/jquery.scrollbar.css',
				'./assets/src/css/vendor/jquery.tipsy.css',
				'./assets/src/css/vendor/font-awesome.min.css',
			],
			dest( code ) {
				return minifyCssDest( `assets/css/bundle`, code );
			},
		},
		{
			src: [
				'./assets/src/css/vendor/font-awesome.min.css',
				'./assets/src/css/vendor/jquery.tipsy.css',
			],
			dest( code ) {
				return minifyCssDest( `assets/css/admin.bundle`, code );
			},
		},
		{
			src: [ './assets/src/js/vendor/chart.min.js' ],
			dest( code ) {
				return minifyJsDest( `assets/js/vendor/chart`, code );
			},
		},
	],
};

// adminSources().concat(frontendSources()).filter((value, index, self) => {
//     return self.indexOf(value) === index;
// }).forEach((file) => {
//     options.files.push({
//         src: [file],
//         dest: function (code) {
//             return minifyJsDest(file.replace(/\/assets\/src\//, '/assets/').replace(/(\.js$)/, ''), code);
//         }
//     })
// })

const mergeAndCompressJs = new MergeIntoSingleFilePlugin( options );

module.exports = {
	mergeAndCompressJs,
};
