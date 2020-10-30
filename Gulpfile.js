/**
 * Gulp file by nhamdv.
 */
const gulp = require( 'gulp' );
const cache = require( 'gulp-cache' );
const lineec = require( 'gulp-line-ending-corrector' );
const notify = require( 'gulp-notify' );
const rename = require( 'gulp-rename' );
const sass = require( 'gulp-sass' );
const replace = require( 'gulp-replace' );
const uglify = require( 'gulp-uglify-es' ).default;
const zip = require( 'gulp-zip' );
const plumber = require( 'gulp-plumber' );
const sourcemaps = require( 'gulp-sourcemaps' );
// const concat = require( 'gulp-concat' );
// const cssnano = require( 'gulp-cssnano' );
// const postcss = require( 'gulp-postcss' );
// const rtlcss = require( 'gulp-rtlcss' );
const del = require( 'del' );
const beep = require( 'beepbeep' );
const readFile = require( 'read-file' );

let currentVer = null;

const getCurrentVer = function( force ) {
	if ( currentVer === null || force === true ) {
		const current = readFile.sync( 'learnpress.php', { encoding: 'utf8' } ).match( /Version:\s*(.*)/ );
		currentVer = current ? current[ 1 ] : null;
	}

	return currentVer;
};

const releasesFiles = [
	'./**',
	'!assets/src/**',
	'!assets/scss/**',
	'!assets/**/*.js.map',
	'!assets/**/*.dev.js',
	'!assets/**/*bak*',
	'!assets/**/*bk*',
	'!assets/**/*.asset.php',
	'!assets/**/*.deps.json',
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

gulp.task( 'styles', () => {
	return gulp
		.src( [ 'assets/scss/**/*.scss' ] )
		.pipe( plumber( errorHandler ) )
		// .pipe( sourcemaps.init() )
		.pipe(
			sass( {
				errLogToConsole: true,
				outputStyle: 'expanded',
				precision: 10,
			} )
		)
		.on( 'error', sass.logError )
		// .pipe( sourcemaps.write( './' ) )
		.pipe( lineec() )
		.pipe( gulp.dest( 'assets/css' ) );
} );

gulp.task( 'watch', gulp.series( 'clearCache', () => {
	gulp.watch( [ 'assets/scss/**/*.scss' ], gulp.parallel( 'styles' ) );
} ) );

gulp.task( 'cleanReleases', () => {
	return del( './releases/**' );
} );

gulp.task( 'copyReleases', () => {
	return gulp.src( releasesFiles ).pipe( gulp.dest( './releases/learnpress/' ) );
} );

gulp.task( 'updateReadme', () => {
	return gulp.src( [ 'readme.txt' ] )
		.pipe( replace( /Stable tag: (.*)/g, 'Stable tag: ' + getCurrentVer( true ) ) )
		.pipe( gulp.dest( './releases/learnpress/', { overwrite: true } ) );
} );

gulp.task( 'zipReleases', () => {
	return gulp
		.src( './releases/learnpress/**', { base: './releases/' } )
		.pipe( zip( 'learnpress.' + getCurrentVer() + '.zip' ) )
		.pipe( gulp.dest( './releases/' ) );
} );

gulp.task( 'noticeReleases', () => {
	const version = getCurrentVer();

	return gulp.src( './' ).pipe(
		notify( {
			message: 'LearnPress version ' + version + ' build successfully!',
			onLast: true,
		} )
	);
} );

gulp.task(
	'build',
	gulp.series(
		'clearCache',
		'cleanReleases',
		'copyReleases',
		'updateReadme',
		'zipReleases',
		( done ) => {
			done();
		}
	)
);

gulp.task( 'release', gulp.series( 'build', 'noticeReleases', ( done ) => {
	done();
} ) );

