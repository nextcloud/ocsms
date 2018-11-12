let gulp = require("gulp");
let concat = require('gulp-concat');
let rename = require("gulp-rename");
let uglify = require('gulp-uglify-es').default;

js_files = [
	'js/devel/app.js',
	'js/devel/settings.js',
	'js/devel/contactlist.js',
	'js/devel/conversation.js',
	'js/devel/helpers.js',
	'js/devel/notifications.js'
];

gulp.task("uglify", function () {
	return gulp.src(js_files)
		.pipe(concat("app.min.js"))
		.pipe(uglify(/* options */))
		.pipe(gulp.dest("js/"));
});
