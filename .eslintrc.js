const eslintConfig = require( '@wordpress/scripts/config/.eslintrc' );

module.exports = {
	...eslintConfig,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended-with-formatting' ],
	rules: {
		indent: [ 'error', 'tab' ],
		'space-in-parens': [ 'error', 'always' ],
		camelcase: 0,
		'no-undef': 0,
		eqeqeq: 0,
		'no-unused-expressions': [ 'error', { allowShortCircuit: true } ],
		'no-unused-vars': 0,
		'no-shadow': 0,
		'no-console': 0,
		'vars-on-top': 0,
		'jsdoc/require-param-type': 0,
		'array-callback-return': 0,
		'import/no-extraneous-dependencies': 0,
		'import/no-unresolved': 0,
	},
};
