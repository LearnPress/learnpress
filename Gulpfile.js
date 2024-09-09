/**
 * Gulp file by nhamdv.
 */
const gulp = require( 'gulp' );
const cache = require( 'gulp-cache' );
//const lineec = require( 'gulp-line-ending-corrector' );
//const notify = require( 'gulp-notify' );
const notifier = require( 'node-notifier' );
const rename = require( 'gulp-rename' );
const sass = require( 'gulp-sass' )( require( 'sass' ) );
const replace = require( 'gulp-replace' );
const uglify = require( 'gulp-uglify-es' ).default;
const zip = require( 'gulp-vinyl-zip' );
const plumber = require( 'gulp-plumber' );
const uglifycss = require( 'gulp-uglifycss' );
const del = require( 'del' );
const readFile = require( 'read-file' );
const wpPot = require( 'gulp-wp-pot' );
const rtlcss = require( 'gulp-rtlcss' );

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
	'assets/src/**',
	'!assets/src/scss/**',
	'!assets/src/app/**',
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
	'!inc/**/*.http',
	'!packages/**',
	'!languages/strings/**',
	'!languages/learnpress-js.pot',
];

const errorHandler = ( err ) => {
	console.error( 'Error:', err.message );
	this.emit( 'end' );
};

// Clear cache.
gulp.task( 'clearCache', ( done ) => {
	return cache.clearAll( done );
} );

gulp.task( 'styles', () => {
	return gulp
		.src( [ 'assets/src/scss/**/*.scss' ] )
		.pipe( plumber( errorHandler ) )
		// .pipe( sourcemaps.init() )
		.pipe( sass.sync().on( 'error', sass.logError ) )
		// .pipe( sourcemaps.write( './' ) )
		//.pipe( lineec() )
		.pipe( gulp.dest( 'assets/css' ) )
		.pipe( rtlcss() )
		.pipe( rename( { suffix: '-rtl' } ) )
		.pipe( gulp.dest( 'assets/css' ) );
} );

// Watch sass
gulp.task( 'watch', gulp.series( 'clearCache', () => {
	gulp.watch( [ 'assets/src/scss/**/*.scss' ], gulp.parallel( 'styles' ) );
} ) );

// Min CSS frontend.
gulp.task( 'mincss', () => {
	return gulp
		.src( [ 'assets/css/**/*.css', '!assets/css/**/*.min.css' ] )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( uglifycss() )
		//.pipe( lineec() )
		.pipe( gulp.dest( 'assets/css' ) );
} );

// Clear JS folder.
gulp.task( 'clearJsAdmin', () => {
	return del( './assets/js/admin/**' );
} );
gulp.task( 'clearJsFrontend', () => {
	return del( './assets/js/frontend/**' );
} );

// Min JS.
gulp.task( 'minJsAdmin', () => {
	return gulp
		.src(
			[
				'assets/src/js/admin/**/*.js',
				'!assets/src/js/admin/admin.js',
				'!assets/src/js/admin/admin-order.js',
				'!assets/src/js/admin/admin-tools.js',
				'!assets/src/js/admin/addons.js',
				'!assets/src/js/admin/admin-statistic.js',
				'!assets/src/js/admin/tools/assign-user-course.js',
			]
		)
		.pipe(
			rename( {
				suffix: '.min',
			} )
		)
		.pipe( uglify() )
		//.pipe( lineec() )
		.pipe( gulp.dest( 'assets/js/admin' ) );
} );
gulp.task( 'minJsFrontend', () => {
	return gulp
		.src( [ 'assets/src/js/frontend/**/*.js' ] )
		.pipe(
			rename( {
				suffix: '.min',
			} )
		)
		.pipe( uglify() )
		//.pipe( lineec() )
		.pipe( gulp.dest( 'assets/js/frontend' ) );
} );

// Clean folder to releases.
gulp.task( 'cleanReleases', () => {
	return del( './releases/**' );
} );

// Copy folder to releases.
gulp.task( 'copyReleases', () => {
	gulp.src( [ 'vendor/autoload.php' ] ).pipe( gulp.dest( './releases/learnpress/vendor' ) );
	gulp.src( [ 'vendor/composer/**' ] ).pipe( gulp.dest( './releases/learnpress/vendor/composer' ) );
	return gulp.src( releasesFiles ).pipe( gulp.dest( './releases/learnpress/' ) );
} );

// Update file Readme
gulp.task( 'updateReadme', () => {
	return gulp.src( [ 'readme.txt' ] )
		.pipe( replace( /Stable tag: (.*)/g, 'Stable tag: ' + getCurrentVer( true ) ) )
		.pipe( gulp.dest( './releases/learnpress/', { overwrite: true } ) );
} );

// Zip learnpress in releases.
gulp.task( 'zipReleases', () => {
	const version = getCurrentVer();

	return gulp
		.src( './releases/learnpress/**', { base: './releases/' } )
		.pipe( zip.dest( './releases/learnpress.' + version + '.zip' ) );
} );

// Notice.
gulp.task( 'noticeReleases', () => {
	const version = getCurrentVer();

	return gulp.src( './' )
		//.pipe( gulp.dest( './' ) )
		.on( 'end', function() {
			notifier.notify( {
				title: 'LearnPress',
				message: 'LearnPress version ' + version + ' build successfully!',
				sound: 'Bottle',
			} );
		} );
} );

gulp.task( 'makepot', function() {
	return gulp.src( [ './**/*.php', '!node_modules/**', '!releases/**', '!vendor/**' ] )
		.pipe( wpPot( {
			domain: 'learnpress',
			package: 'learnpress',
		} ) )
		.pipe( gulp.dest( './languages/learnpress.pot' ) );
} );

gulp.task(
	'build',
	gulp.series(
		'clearCache',
		'clearJsAdmin',
		'clearJsFrontend',
		'minJsAdmin',
		'minJsFrontend',
		'mincss',
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

gulp.task( 'updatePot', () => {
	return gulp
		.src( [ './languages/learnpress.pot' ] )
		.pipe( replace( /(assets\/.*\/frontend\/quiz)(.*)(.js)/g, 'assets/js/dist/frontend/quiz.min.js' ) )
		.pipe( replace( /(assets\/.*\/frontend\/question)(.*)(.js)/g, 'assets/js/dist/frontend/quiz.min.js' ) )
		.pipe( replace( /(assets\/.*\/frontend\/modal)(.*)(.js)/g, 'assets/js/dist/frontend/quiz.min.js' ) )
		.pipe( replace( /(assets\/.*\/frontend\/profile)(.*)(.js)/g, 'assets/js/dist/frontend/profile.min.js' ) )
		.pipe( replace( /(assets\/.*\/admin\/pages\/tools)(.*)(.js)/g, 'assets/js/dist/admin/pages/tools.min.js' ) )
		.pipe( gulp.dest( './languages/' ) );
} );

