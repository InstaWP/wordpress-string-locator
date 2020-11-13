process.env.WP_DEVTOOL = false;

const path = require( 'path' );
const defaultConfig = require("@wordpress/scripts/config/webpack.config");
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

module.exports = {
	...defaultConfig,
	entry: {
		"string-locator": [
			path.resolve( process.cwd(), 'src/javascript/', 'string-locator.js' ),
			path.resolve( process.cwd(), 'src/sass', 'string-locator.scss' )
		],
		"string-locator-search": [
			path.resolve( process.cwd(), 'src/javascript', 'string-locator-search.js' )
		],
	},
	output: {
		filename: '[name].js',
		path: path.resolve( process.cwd(), 'string-locator/resources/js/' ),
	},
	module: {
		rules: [
			{
				test: /\.s[ac]ss$/i,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'sass-loader',
				],
			}
		]
	},
	plugins: [
		new CopyPlugin({
			patterns: [
				{
					from: path.resolve( process.cwd(), 'src/php' ),
					to: path.resolve( process.cwd(), 'string-locator' )
				},
				{
					from: path.resolve( process.cwd(), 'docs' ),
					to: path.resolve( process.cwd(), 'string-locator' )
				}
			]
		}),
		new MiniCssExtractPlugin({
			filename: '../css/[name].css',
		}),
	],
	externals: {
		...defaultConfig.externals,
		react: 'React',
		'react-dom': 'ReactDOM',
	}
};
