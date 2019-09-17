const { series, src, dest } = require('gulp');
const del = require('del');
const path = require( 'path' );
const sass = require( 'gulp-sass' );
const sourcemaps = require( 'gulp-sourcemaps' );

function clean(cb) {
	del( './build/**' );

	cb();
}

function copy(cb) {
	src( [ './src/**', './docs/**' ] )
		.pipe( dest( './build' ) );

	cb();
}

function css(cb) {
	del( './build/resources/styles/**/*.css' );

	src( [ './assets/styles/**/*.scss' ] )
		.pipe( sourcemaps.init() )
		.pipe( sass() )
		.pipe( sourcemaps.write() )
		.pipe( dest( './build/resources/css' ) );

	cb();
}

exports.copy = copy;
exports.css = css;
exports.build = series( copy, css );
exports.default = series( clean, css, copy );
