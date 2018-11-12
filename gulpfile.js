let gulp = require("gulp");
let concat = require('gulp-concat');
let rename = require("gulp-rename");
let uglify = require('gulp-uglify-es').default;

gulp.task("uglify", function () {
	return gulp.src("js/devel/app.js")
		.pipe(concat('js/devel/settings.js'))
		.pipe(concat('js/devel/contactlist.js'))
		.pipe(concat('js/devel/conversation.js'))
		.pipe(concat('js/devel/helpers.js'))
		.pipe(concat('js/devel/notifications.js'))
		.pipe(rename("app.min.js"))
		.pipe(uglify(/* options */))
		.pipe(gulp.dest("js/"));
});