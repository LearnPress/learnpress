const $ = window.jQuery || jQuery;

const i18n = function i18n( i18n ) {
	const state = $.extend( {}, i18n );
	const getters = {
		all: function( state ) {
			return state;
		},
	};

	return {
		namespaced: true,
		state: state,
		getters: getters,
	};
};

export default i18n;
