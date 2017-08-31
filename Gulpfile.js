/**
 * Gulp tasks
 *
 * 1/ Run "npm install gulp -g" if you did not run it any time in the past.
 * 2/ Run "npm install gulp --save-dev" to install gulp in your project directory.
 * 3/ Run "npm install package-name[ package-name...] --save-dev
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
    concat = require('gulp-concat');

gulp.task('scss', function () {
    return gulp.src(['assets/scss/**/*.scss'])
        .pipe(sourceMaps.init())
        .pipe(scss())
        .pipe(sourceMaps.write())
        .pipe(gulp.dest('assets/css'))
        .pipe(liveReload());
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
gulp.task('svn', ['less', 'copy-trunk'], function () {
    updateReadme(getCurrentVer(true), function () {
        return gulp.start('release', ['copy-tag']);
    })
});

// end of the world!

gulp.task("compile-sass-to-css", function (project) {
    return gulp.src([
        'sources/' + project + '/assets/sass/style.scss',
        'sources/' + project + '/shortcodes/**/assets/sass/*.scss',
        '!sources/' + project + '/shortcodes/**/assets/sass/*-options.scss',
        'sources/' + project + '/inc/widgets/**/assets/sass/*.scss',
        '!sources/' + project + '/inc/widgets/**/assets/sass/*-options.scss',
    ])

        .pipe(concat('_tmp-options.scss'))
        .pipe(gulp.dest('sources/' + project + '/assets/sass/'))
        .pipe(sourcemaps.init())
        .pipe(rename('style.unminify.css'))
        .pipe(sass())
        .pipe(sourcemaps.write({includeContent: false, sourceRoot: 'src'}))
        .pipe(lec())
        .pipe(gulp.dest('sources/' + project + '/'))
        .pipe(rename('style.css'))
        .pipe(cssmin())
        .pipe(lec())

        .pipe(gulp.dest('sources/' + project + '/'))
        .pipe(livereload());
});