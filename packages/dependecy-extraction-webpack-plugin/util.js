let globalOptions = {
	namespace: '@learnpress',
	library: 'LP',
};

/**
 * Default request to global transformation
 *
 * Transform @wordpress dependencies:
 *
 *   @wordpress/api-fetch -> wp.apiFetch
 *   @wordpress/i18n -> wp.i18n
 *
 * @param {string} request Requested module
 * @param {Object} options
 *
 * @return {(string|string[]|undefined)} Script global
 */
function defaultRequestToExternal( request, options ) {
	globalOptions = Object.assign(
		{
			namespace: '@learnpress',
			library: 'LP',
		},
		options
	);

	switch ( request ) {
	case 'moment':
		return request;

	case '@babel/runtime/regenerator':
		return 'regeneratorRuntime';

	case 'lodash':
	case 'lodash-es':
		return 'lodash';

	case 'jquery':
		return 'jQuery';

	case 'react':
		return 'React';

	case 'react-dom':
		return 'ReactDOM';
	}

	const namespace = globalOptions.namespace + '/';

	if ( request.startsWith( namespace ) ) {
		return [ globalOptions.library, camelCaseDash( request.substring( namespace.length ) ) ];
	}
}

/**
 * Default request to WordPress script handle transformation
 *
 * Transform @wordpress dependencies:
 *
 *   @wordpress/i18n -> wp-i18n
 *   @wordpress/escape-html -> wp-escape-html
 *
 * @param {string} request Requested module
 * @param {Object} options
 *
 * @return {(string|undefined)} Script handle
 */
function defaultRequestToHandle( request, options ) {
	globalOptions = Object.assign(
		{
			namespace: '@learnpress',
			library: 'LP',
		},
		options
	);

	switch ( request ) {
	case '@babel/runtime/regenerator':
		return 'wp-polyfill';

	case 'lodash-es':
		return 'lodash';
	}

	const namespace = globalOptions.namespace + '/';

	if ( request.startsWith( namespace ) ) {
		return ( globalOptions.library ) + '-' + request.substring( namespace.length );
	}
}

/**
 * Given a string, returns a new string with dash separators converted to
 * camelCase equivalent. This is not as aggressive as `_.camelCase` in
 * converting to uppercase, where Lodash will also capitalize letters
 * following numbers.
 *
 * Temporarily duplicated from @wordpress/scripts/utils.
 *
 * @param {string} string Input dash-delimited string.
 *
 * @return {string} Camel-cased string.
 */
function camelCaseDash( string ) {
	return string.replace( /-([a-z])/g, ( match, letter ) => letter.toUpperCase() );
}

module.exports = {
	defaultRequestToExternal,
	defaultRequestToHandle,
};
