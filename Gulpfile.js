// npm i -g gulp
'use strict';

var gulp = require('gulp');
const zip = require('gulp-zip');
var gulpCopy = require('gulp-copy');
var clean = require('gulp-clean');

var less = require('gulp-less');

var live_reload = require('gulp-livereload');
var sourcemaps = require('gulp-sourcemaps');

gulp.task('less', function () {
    return gulp.src(['assets/less/**/*.less'])
        .pipe(sourcemaps.init())
        .pipe(less())
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('assets/css'))
        .pipe(live_reload());
});

gulp.task('watch', function () {
    live_reload.listen();
    gulp.watch(['assets/less/**/*.less'], ['less']);
});

gulp.task('default', ['less', 'watch']);

/**
 * Build zip
 */
function clean_dist() {
    return gulp.src('dist', {read: false})
        .pipe(clean());
}

// SVN
var svnPath = '/Users/tu/Documents/foobla/svn/learnpress';
gulp.task('svn', function(){
    gulp.src(svnPath, {read: false, force: true}).pipe(clean());
    gulp.src([
        'assets/**/*',
        '!node_modules/**/*'
    ]).pipe(gulpCopy(svnPath));
    //console.log(gulp.dest('/Users/tu/Documents/foobla/svn'));
});

gulp.task('build', ['zip'], clean_dist);

gulp.task('clean', clean_dist);

gulp.task('copy', ['clean'], function () {
    return gulp
        .src([
            'admin/**/*',
            'assets/**/*',
            'inc/**/*',
            'providers/**/*',
            'helpers/**/*',
            '!inc/includes/kirki/node_modules/**/*',
            'languages/**/*',
            'thim-core.php',
            'readme.txt',
            'index.php',
            '!**/*.scss',
            '!**/*.log',
            '!**/Gruntfile.js',
            '!**/package.json'
        ])
        .pipe(gulpCopy('dist/thim-core', {}))
});

gulp.task('zip', ['copy'], function () {
    return gulp.src('dist/**/*')
        .pipe(zip('thim-core.zip'))
        .pipe(gulp.dest(''));
});