// npm i -g gulp
'use strict';
const zip = require('gulp-zip');

var gulp = require('gulp'),
    gulpCopy = require('gulp-copy'),
    clean = require('gulp-clean'),
    less = require('gulp-less'),
    liveReload = require('gulp-livereload'),
    sourceMaps = require('gulp-sourcemaps'),
    readFile = require('read-file'),
    replace = require('gulp-replace'),
    mkdirp = require("mkdirp");

gulp.task('less', function () {
    return gulp.src(['assets/less/**/*.less'])
        .pipe(sourceMaps.init())
        .pipe(less())
        .pipe(sourceMaps.write())
        .pipe(gulp.dest('assets/css'))
        .pipe(liveReload());
});

gulp.task('watch', function () {
    liveReload.listen();
    gulp.watch(['assets/less/**/*.less'], ['less']);
});

gulp.task('default', ['less', 'watch']);

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