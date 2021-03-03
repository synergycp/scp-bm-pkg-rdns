var gulp = require("scp-ng-gulp")(require("gulp"));

gulp.require("settings").dir = __dirname;

var PATH = {
  PUBLIC: "../public/client/",
  MARKUP: "app/",
  SCRIPTS: "app/",
  ASSETS: "resources/assets/",
};
var js = {
  src: PATH.SCRIPTS,
  app: "app.js",
};

var scripts = gulp.require("scripts");
gulp.task(
  "scripts",
  scripts.app({
    dest: PATH.PUBLIC + js.app,
    src: [PATH.SCRIPTS + "**/*.module.js", PATH.SCRIPTS + "**/*.js"],
  })
);

var templates = gulp.require("templates");
gulp.task(
  "templates",
  templates({
    src: [PATH.MARKUP + "**/*.pug"],
    dest: PATH.PUBLIC,
  })
);

var copy = gulp.require("copy");
gulp.task(
  "copy",
  copy({
    src: PATH.ASSETS + "**/*.*",
    dest: PATH.PUBLIC,
    base: "resources",
  })
);

// noop
gulp.task("prod", function (done) {
  done();
});

gulp.task("default", gulp.parallel(["copy", "templates", "scripts"]));

gulp.task("build", gulp.series(["default"]));
