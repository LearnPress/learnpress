/* eslint-disable linebreak-style */
/**
 * LearnPress gulp file by Nhamdv.
 *
 */
const gulp = require( 'gulp' );
const cache = require( 'gulp-cache' );
const lineec = require( 'gulp-line-ending-corrector' );
const notify = require( 'gulp-notify' );
const rename = require( 'gulp-rename' );
// const sass = require( 'gulp-sass' );
// const sort = require( 'gulp-sort' );
const uglify = require( 'gulp-uglify-es' ).default;
const zip = require( 'gulp-zip' );
// const postcss = require( 'gulp-postcss' );
// const rtlcss = require( 'gulp-rtlcss' );
// const plumber = require( 'gulp-plumber' );
// const concat = require( 'gulp-concat' );
// const sourcemaps = require( 'gulp-sourcemaps' );
// const cssnano = require( 'gulp-cssnano' );
const del = require( 'del' );
const beep = require( 'beepbeep' );

const releasesFiles = [
	'./**',
	'!releases/**',
	'!tests/**',
	'!tools/**',
	'!node_modules/**',
	'!vendor/**',
	'!*.json',
	'!*.js',
	'!*.map',
	'!*.xml',
	'!*.sublime-project',
	'!*.sublime-workspace',
	'!*.log',
	'!*.DS_Store',
	'!*.gitignore',
	'!TODO',
	'!*.git',
	'!*.ftppass',
	'!*.DS_Store',
	'!sftp.json',
	'!composer.lock',
	'!*.md',
	'!package.lock',
	'!*.dist',
	'!*.xml',
	'!editorconfig',
	'!.travis.yml',
	'!.babelrc',
];

const errorHandler = ( r ) => {
	notify.onError( '\n\n❌  ==> ERROR: <%= error.message %>\n' )( r );

	beep();
};

// Clear cache.
gulp.task( 'clearCache', ( done ) => {
	return cache.clearAll( done );
} );

// Build sass -> css.
gulp.task( 'styles', () => {
	return gulp
		.src( [ 'assets/scss/**/*.scss' ] )
		.pipe( plumber( errorHandler ) )
		.pipe( sourcemaps.init() )
		.pipe(
			sass( {
				errLogToConsole: true,
				outputStyle: 'expanded',
				precision: 10,
			} )
		)
		.on( 'error', sass.logError )
		.pipe( sourcemaps.write( './' ) )
		.pipe( lineec() )
		.pipe( gulp.dest( 'assets/css' ) );
} );

// Watch sass
gulp.task( 'watch', gulp.series( 'clearCache', () => {
	gulp.watch( [ 'assets/scss/**/*.scss' ], gulp.parallel( 'styles' ) );
} ) );

// Min css.
gulp.task( 'mincss', () => {
	return gulp
		.src( './assets/**/*.css' )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( cssnano( { discardUnused: { fontFace: false, keyframes: false } } ) )
		.pipe( lineec() )
		.pipe( gulp.dest( 'assets' ) );
} );

// Clear JS in admin folder.
gulp.task( 'clearJsAdmin', () => {
	return del( './assets/js/admin/**' );
} );

// Min JS.
gulp.task( 'minJsAdmin', () => {
	return gulp
		.src( [ 'assets/src/js/admin/**/*.js' ] )
		.pipe(
			rename( {
				suffix: '.min',
			} )
		)
		.pipe( uglify() )
		.pipe( lineec() )
		.pipe( gulp.dest( 'assets/js/admin' ) );
} );

// Clean folder to releases.
gulp.task( 'cleanReleases', () => {
	return del( './releases/**' );
} );

// Copy folder to releases.
gulp.task( 'copyReleases', () => {
	return gulp.src( releasesFiles ).pipe( gulp.dest( './releases/learnpress/' ) );
} );

// Zip learnpress in releases.
gulp.task( 'zipReleases', () => {
	return gulp
		.src( './releases/learnpress/**', { base: './releases/' } )
		.pipe( zip( 'learnpress.zip' ) )
		.pipe( gulp.dest( './releases/' ) );
} );

// Notice.
gulp.task( 'noticeReleases', () => {
	return gulp.src( './' ).pipe(
		notify( {
			message: 'LearnPress build in learpress > releases successfully!',
			onLast: true,
		} )
	);
} );

gulp.task(
	'build',
	gulp.series(
		'clearCache',
		'clearJsAdmin',
		'minJsAdmin',
		'cleanReleases',
		'copyReleases',
		'zipReleases',
		( done ) => {
			done();
		}
	)
);

gulp.task( 'release', gulp.series( 'build', 'noticeReleases', ( done ) => {
	done();
} ) );
