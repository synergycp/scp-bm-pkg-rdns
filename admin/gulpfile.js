var gulp = require('scp-ng-gulp')(require('gulp'));
var _ = require('lodash');

gulp.require('settings').dir = __dirname;

var PATH = {
  PUBLIC: 'public/',
  MARKUP: 'app/',
  SCRIPTS: 'app/',
  ASSETS: 'resources/assets/',
};
var js = {
  src: PATH.SCRIPTS,
  app: 'app.js',
};
var scss = {
  img: 'assets/img/',
  src: 'app/',
};
var appStyles = {
  src: [scss.src + '**/*.scss'],
  dest: PATH.PUBLIC,
  base: scss.src,
  image: scss.image,
};

/*
// CSS
var styles = gulp.require('styles');
gulp.task('styles', [
  'styles:app',
  'styles:app:rtl',
]);
gulp.task('styles:app', styles.add(appStyles));
gulp.task('styles:app:rtl', styles.rtl(appStyles));
*/
gulp.task('styles', gulp.noop);

var scripts = gulp.require('scripts');
gulp.task('scripts', scripts.app({
  dest: PATH.PUBLIC + js.app,
  src: [
    PATH.SCRIPTS + '**/*.module.js',
    PATH.SCRIPTS + '**/*.js'
  ],
}));

var templates = gulp.require('templates');
gulp.task('templates', templates({
  src: [PATH.MARKUP + '**/*.pug'],
  dest: PATH.PUBLIC,
}));

var copy = gulp.require('copy');
gulp.task('copy', copy({
  src: PATH.ASSETS+'**/*.*',
  dest: PATH.PUBLIC,
  base: 'resources',
}));

var production = gulp.require('production');
gulp.task('prod', production());

gulp.task('default', [
  'copy',
  'styles',
  'templates',
  'scripts',
]);
gulp.task('build', ['default']);
