let gulp = require("gulp");
let rename = require("gulp-rename");
let uglify = require('gulp-uglify-es').default;

gulp.task("uglify", function () {
	return gulp.src("js/devel/*.js")
		.pipe(rename("app.min.js"))
		.pipe(uglify(/* options */))
		.pipe(gulp.dest("js/"));
});