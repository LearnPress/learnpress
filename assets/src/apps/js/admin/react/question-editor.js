import Editor from './question/index';
import store from './question/store';

export default Editor;

export const init = function init( elem, settings ) {
	wp.element.render(
		<Editor initSettings={ settings } />,
		jQuery( elem )[ 0 ]
	);
	const $ = jQuery;

	$( document ).on( 'change', '#_lp_blanks_style, #_lp_blank_fills_style', () => {
		wp.data.dispatch( 'learnpress/question' ).setData( {
			blanksStyle: $( '#_lp_blanks_style' ).val(),
			blankFillsStyle: $( '#_lp_blank_fills_style' ).val(),
		}, 'question' );
	} );
};
