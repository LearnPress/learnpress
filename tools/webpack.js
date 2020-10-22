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
	'./assets/src/js/vendor/vue/vue' + ( isDev() ? '' : '.min' ) + '.js',
	'./assets/src/js/vendor/vue/vuex.js',
	'./assets/src/js/vendor/vue/vue-resource.js',
	'./assets/src/js/vendor/vue/vue-draggable.js',
	'./assets/src/js/vendor/jquery/jquery.tipsy.js',
	// './assets/src/js/vendor/chart.min.js',
];

const frontendSources = () => [
	'./assets/src/js/vendor/vue/vue' + ( isDev() ? '' : '.min' ) + '.js',
	'./assets/src/js/vendor/vue/vuex.js',
	'./assets/src/js/vendor/vue/vue-resource.js',
	'./assets/src/js/vendor/watch.js',
	'./assets/src/js/vendor/jquery/jquery-appear.js',
	'./assets/src/js/vendor/jquery/jquery-scrollTo.js',
	'./assets/src/js/vendor/jquery/jquery-timer.js',
	'./assets/src/js/vendor/jquery/jquery.tipsy.js',
];

const options = {
	files: [
		{
			src: frontendSources(),
			dest( code ) {
				return minifyJsDest( 'assets/js/vendor/plugins.all', code );
			},
		},
		{
			src: adminSources(),
			dest( code ) {
				return minifyJsDest( 'assets/js/vendor/admin.plugins.all', code );
			},
		},
		{
			src: [
				'./assets/src/css/vendor/jquery.tipsy.css',
				'./assets/src/css/vendor/jalert.css',
			],
			dest( code ) {
				return minifyCssDest( 'assets/css/bundle', code );
			},
		},
	],
};

adminSources().concat( frontendSources() ).concat( [
	'./assets/src/js/vendor/chart.min.js',
] ).filter( ( value, index, self ) => {
	return self.indexOf( value ) === index;
} ).forEach( ( file ) => {
	options.files.push( {
		src: [ file ],
		dest( code ) {
			return minifyJsDest( file.replace( /\/assets\/src\//, '/assets/' ).replace( /(\.js$)/, '' ), code );
		},
	} );
} );

const mergeAndCompressJs = new MergeIntoSingleFilePlugin( options );

module.exports = {
	mergeAndCompressJs,
};
