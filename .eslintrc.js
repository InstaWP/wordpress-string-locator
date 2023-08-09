module.exports = {
	root: true,
	"extends": [
		"plugin:@wordpress/eslint-plugin/esnext"
	],
	globals: {
		jQuery: true,
		document: true,
		window: true,
		wp: true,
	},
	env: {
		browser: true,
		es6: true,
		node: true,
	},
	"overrides": [
		{
			"files": ["*.js" ],
			"rules": {
				"camelcase": "off",
			}
		}
	]
};
