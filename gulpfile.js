'use strict';

const gulp   = require('gulp');
const sass   = require('gulp-sass')(require('sass'));
const rename = require('gulp-rename');
const uglify = require('gulp-uglify');
const concat = require('gulp-concat');

const paths = {
  scssCore : ['css/scss/**/*.scss', 'css/'],
  jsBackend: ['js/dolicar.js', 'js/modules/*.js']
};

/** Core */
gulp.task('scssCore', function() {
  return gulp.src(paths.scssCore[0])
    .pipe(sass({outputStyle: 'compressed'}, '').on('error', sass.logError))
    .pipe(rename('./dolicar.min.css'))
    .pipe(gulp.dest(paths.scssCore[1]));
});

gulp.task('jsBackend', function () {
  return gulp.src(paths.jsBackend)
    .pipe(concat('dolicar.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest('./js/')); // It will create folder client.min.js
});

/** Watch */
gulp.task('default', function() {
  gulp.watch(paths.scssCore[0], gulp.series('scssCore'));
  gulp.watch(paths.jsBackend[1], gulp.series('jsBackend'));
});
