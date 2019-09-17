const path = require( 'path' );
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
	...defaultConfig,
	entry: {
		"string-locator": path.resolve( process.cwd(), 'assets/javascript', 'string-locator.js' ),
		"string-locator-search": path.resolve( process.cwd(), 'assets/javascript', 'string-locator-search.js' ),
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'build/resources/js/' ),
	}
};
