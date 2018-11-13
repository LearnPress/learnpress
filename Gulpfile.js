/**
 * Gulp tasks
 *
 * 1/ Run "npm install gulp -g" if you did not run it any time in the past.
 * 2/ Run "npm install gulp --save-dev" to install gulp in your project directory.
 * 3/ Run "npm install package-name[ package-name...] --save-dev
 *
 * EX: npm install gulp gulp-zip gulp-copy gulp-clean gulp-sass gulp-livereload gulp-sourcemaps read-file gulp-replace mkdirp gulp-concat gulp-uglify gulp-clean-css pump --save-dev

 */
'use strict';
const zip = require('gulp-zip');

var gulp = require('gulp'),
    gulpCopy = require('gulp-copy'),
    clean = require('gulp-clean'),
    scss = require('gulp-sass'),
    liveReload = require('gulp-livereload'),
    sourceMaps = require('gulp-sourcemaps'),
    readFile = require('read-file'),
    replace = require('gulp-replace'),
    mkdirp = require("mkdirp"),
    concat = require('gulp-concat'),
    cleanCSS = require('gulp-clean-css'),
    through = require('through2');

gulp.task('scss', function () {
    return gulp.src(['assets/scss/**/*.scss'])
        .pipe(sourceMaps.init())
        .pipe(scss())
        .pipe(function (destPath, options) {
            function sourceMapWrite(file, encoding, callback) {
                console.log('xxxxx', file)

                this.push(file);
                callback();
            }
            return through.obj(sourceMapWrite);
        }())
        .pipe(gulp.dest('assets/css'))
    //.pipe(liveReload());
});

gulp.task('watch', function () {
    liveReload.listen();
    gulp.watch(['assets/scss/**/*.scss'], ['scss']);
    gulp.watch(['assets/js/admin/utils/*.js'], ['compress-js']);
});

gulp.task('default', ['scss', 'watch', 'compress-js']);


var uglify = require('gulp-uglify');
var pump = require('pump');

gulp.task('compress-js', function (cb) {
    return gulp.src('assets/js/admin/utils/*.js')
        .pipe(concat('utils.js'))
        .pipe(uglify())
        .pipe(gulp.dest('assets/js/admin'))
});

gulp.task('compress-css', function () {
    return gulp.src('./assets/**/*.css')
        .pipe(cleanCSS())
        .pipe(gulp.dest('./assets'));
});

/*
 * SVN: Copy working directory to SVN and prepare something before submitting.
 */
var rootPath = '/Users/tu/Documents/foobla',
    svnPath = rootPath + '/svn/learnpress',
    releasePath = rootPath + '/releases/learnpress',
    svnTrunkPath = svnPath + '/trunk',
    svnTagsPath = svnPath + '/tags',
    currentVer = null,
    copySvnFiles = [
        'assets/**/*',
        'dummy-data/**/*',
        'inc/**/*',
        'languages/**/*',
        'templates/**/*',
        'index.php',
        'learnpress.php'
    ],
    getCurrentVer = function (force) {
        if (currentVer === null || force === true) {
            currentVer = readFile.sync('learnpress.php', {encoding: 'utf8'}).match(/Version:\s*(.*)/);
            currentVer = currentVer ? currentVer[1] : null;
        }
        return currentVer;
    },
    updateReadme = function (version, callback) {
        return gulp.src(['readme.txt'])
            .pipe(replace(/Stable tag: (.*)/g, 'Stable tag: ' + version))
            .pipe(gulp.dest(svnTrunkPath, {overwrite: true}))
            .on('end', function () {
                callback ? callback() : 'do nothing';
            });
    },
    removeConst = function (callback) {
        return gulp.src(['inc/lp-constants.php'])
            .pipe(replace(/define\( 'LP_DEBUG_DEV'(.*)/g, ''))
            .pipe(gulp.dest(svnTrunkPath, {overwrite: true}))
            .on('end', function () {
                callback ? callback() : 'do nothing';
            });
    };
// Clear trunk/tag path
gulp.task('clr-tag', function () {
    return gulp.src(svnTagsPath + '/' + getCurrentVer() + '/', {read: false}).pipe(clean({force: true}));
});
gulp.task('clr-trunk', function () {
    return gulp.src(svnTrunkPath + '/', {read: false}).pipe(clean({force: true}));
});

// Copy working dir to trunk
gulp.task('copy-trunk', ['clr-trunk'], function () {
    mkdirp(svnTrunkPath);
    return gulp.src(copySvnFiles).pipe(gulpCopy(svnTrunkPath));
});

// Copy trunk to current tag
gulp.task('copy-tag', ['clr-tag'], function () {
    var tagPath = svnTagsPath + '/' + getCurrentVer();
    mkdirp(tagPath);
    process.chdir(svnTrunkPath);
    var copyFiles = copySvnFiles;
    copyFiles.push('readme.txt');
    return gulp.src(copyFiles).pipe(gulpCopy(tagPath));
});

gulp.task('clr-release', function () {
    return gulp.src(releasePath + '/', {read: false}).pipe(clean({force: true}));
});

gulp.task('copy-release', ['clr-release'], function () {
    mkdirp(releasePath);
    process.chdir(svnTrunkPath);
    var copyFiles = copySvnFiles;
    copyFiles.push('readme.txt');
    return gulp.src(copyFiles).pipe(gulpCopy(releasePath));
});

gulp.task('release', ['copy-release'], function () {
    process.chdir(releasePath);
    var zipPath = releasePath.replace(/learnpress/, '');
    return gulp.src(zipPath + '/**/learnpress/**/*')
        .pipe(zip('learnpress.' + getCurrentVer(true) + '.zip'))
        .pipe(gulp.dest(zipPath));
});

// main task
gulp.task('svn', ['scss', 'copy-trunk'], function () {
    updateReadme(getCurrentVer(true), function () {
        return gulp.start('release', ['copy-tag']);
    })
});

// Create zipped version
gulp.task('clr-zip', function () {
    return gulp.src(releasePath + '/', {read: false}).pipe(clean({force: true}));
});

gulp.task('copy-zip', ['clr-zip'], function () {
    mkdirp(releasePath);
    var copyFiles = copySvnFiles;
    copyFiles.push('readme.txt');
    return gulp.src(copyFiles).pipe(gulpCopy(releasePath));
});

gulp.task('mk-zip', ['copy-zip'], function () {
    process.chdir(releasePath);
    var zipPath = releasePath.replace(/learnpress/, '');
    return gulp.src(zipPath + '/**/learnpress/**/*')
        .pipe(zip('learnpress.' + getCurrentVer(true) + '.zip'))
        .pipe(gulp.dest(zipPath));
});

gulp.task('zip', ['mk-zip'], function () {

});

gulp.task('scss-popup', function () {
    return gulp.src(['assets/scss/frontend/_item-popup.scss'])
        .pipe(scss())
        .pipe(gulp.dest('assets/css/frontend'))
});

/**
 * Join and compress front-end script
 */
gulp.task('cfjs', function () {
    return gulp.src([
        'assets/js/vendor/watch.js',
        'assets/js/vendor/jquery.alert.js',
        'assets/js/vendor/jquery-scrollbar/jquery.scrollbar.js',
        'assets/js/vendor/jquery.scrollTo.js',
        'assets/js/learnpress.js',
        'assets/js/frontend/course.js',
        'assets/js/frontend/quiz.js',
        //'assets/js/frontend/profile.js',
        //'assets/js/frontend/become-teacher.js',
    ])
        .pipe(concat('learnpress-frontend.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('assets/js/frontend'))
});

/**
 * Join and compress frontend css
 */
gulp.task('cfcss', function () {
    return gulp.src([
        'assets/css/font-awesome.min.css',
        'assets/js/vendor/jquery-scrollbar/jquery.scrollbar.css',
        'assets/css/learnpress.css',
    ])
        .pipe(concat('learnpress-frontend.min.css'))
        .pipe(cleanCSS())
        .pipe(gulp.dest('assets/css'))
});

gulp.task('mk-zip', ['copy-zip'], function () {
    process.chdir(releasePath);
    var zipPath = releasePath.replace(/learnpress/, '');
    return gulp.src(zipPath + '/**/learnpress/**/*')
        .pipe(zip('learnpress.' + getCurrentVer(true) + '.zip'))
        .pipe(gulp.dest(zipPath));
});

gulp.task('zipx', ['mk-zip'], function () {
    var zipPath = releasePath + '.' + currentVer + '.zip';
    console.log(zipPath)
    return gulp.src([zipPath])
        .pipe(gulpCopy("/Users/tu/Documents/htdocs/"));
})

gulp.task('format', ['scss'], function () {

})
// end of the world!