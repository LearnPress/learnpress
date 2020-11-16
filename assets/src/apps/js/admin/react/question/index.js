import { Component } from '@wordpress/element';
import { withSelect, withDispatch, select as wpSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

const { debounce } = lodash;

import store from './store';

const stripSlashes = function stripSlashes( str ) {
	return ( str + '' ).replace( /\\(.?)/g, function( s, n1 ) {
		switch ( n1 ) {
		case '\\':
			return '\\';
		case '0':
			return '\u0000';
		case '':
			return '';
		default:
			return n1;
		}
	} );
};

class Editor extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			blanks: [],
		};
	}

	componentDidMount() {
		const {
			setData,
			initSettings,
		} = this.props;
		const { blankOptions } = initSettings;
		const newBlanks = {};

		blankOptions.map( ( option ) => {
			const optionBlanks = this.getBlanks( option.text );
			newBlanks[ option.question_answer_id ] = optionBlanks ? optionBlanks[ 0 ] : [];
		} );

		setData( {
			question: {
				...initSettings,
				blanks: newBlanks,
			},
		} );
	}

    /**
     * Parse blanks options from content and update state.
     *
     * @param option
     * @param text
     */
    setContent = ( option, text ) => {
    	const optionBlanks = this.getBlanks( text );
    	const {
    		blankOptions,
    		blanks,
    		updateOption,
    		setData,
    		id,
    	} = this.props;

    	const newState = {
    		blankOptions: blankOptions.map( ( opt ) => {
    			return opt.question_answer_id == option.question_answer_id ? {
    				...opt,
    				text,
    			} : opt;
    		} ),
    		blanks: {
    			...blanks,
    			[ option.question_answer_id ]: optionBlanks ? optionBlanks[ 0 ] : [],
    		},
    	};

    	setData( newState, 'question' );
    	this.queue = this.queue || {};
    	this.queue[ option.question_answer_id ] = [ {
    		text,
    		blanks: newState.blanks[ option.question_answer_id ],
    	}, option.question_answer_id, id ];

    	this.updateOption();
    };

    updateOption = debounce( () => {
    	const {
    		updateOption,
    	} = this.props;

    	const queue = this.queue ? Object.values( this.queue ) : [];

    	queue.map( ( item ) => {
    		if ( item ) {
    			updateOption( item[ 0 ], item[ 1 ], item[ 2 ] );
    			delete this.queue[ item[ 1 ] ];
    		}
    	} );
    }, 1000 );

    /**
     * Get blanks from a content and return.
     *
     * @param content
     * @return {Array}
     */
    getBlanks = ( content ) => {
    	// if (undefined === content) {
    	//     content = this.state.passage;
    	// }

    	const blanks = [];
    	const shortcodes = content.match( /\{\{([^\{\"\'].*?)\}\}/g );

    	if ( shortcodes ) {
    		shortcodes.map( ( shortcode ) => {
    			blanks.push( this.getBlank( shortcode ) );
    		} );
    	}
    	return blanks;
    };

    /**
     * Parse details from a blank.
     *
     * @param blank
     * @return {{words: Array, tip: string, corrects: Array}}
     */
    getBlank = ( blank ) => {
    	const contents = blank.match( /\{\{(.*)\}\}/ );
    	const words = contents && contents[ 1 ] ? contents[ 1 ].split( '/' ) : [];
    	const matchTip = words.length ? words[ words.length - 1 ].match( /(.*)(\s(\"(.*)\"|\'(.*)\'))/ ) : false;
    	const corrects = [];

    	// Remove tip from last word. (last-word "This is tip of the blank")
    	if ( matchTip ) {
    		words[ words.length - 1 ] = matchTip[ 1 ];
    	}

    	words.map( ( word, i ) => {
    		// Match the word wrapped by [ and ] is CORRECT word
    		const matchCorrect = word.match( /\[(.*)\]/ );

    		if ( matchCorrect ) {
    			// Remove the "[" and "]"
    			matchCorrect[ 1 ] = stripSlashes( matchCorrect[ 1 ] );
    			corrects.push( matchCorrect[ 1 ] );
    			words[ i ] = matchCorrect[ 1 ];
    		} else {
    			words[ i ] = stripSlashes( word );
    		}
    	} );

    	return {
    		words: words.filter( ( w ) => {
    			return w && w.length;
    		} ),
    		tip: matchTip ? stripSlashes( matchTip[ 4 ] || matchTip[ 5 ] || '' ) : '',
    		corrects: corrects.filter( ( w ) => {
    			return w && w.length;
    		} ),
    	};
    };

    /**
     * Event handler for each input in blanks.
     *
     * @param answer
     */
    onChangeOption = ( answer ) => ( event ) => {
    	const {
    		instantParseBlanks,
    	} = this.props;

    	if ( instantParseBlanks ) {
    		this.setContent( answer, event.target.value );
    	}
    };

    /**
     * Event handler to add new blank to question
     *
     * @param event
     */
    addBlank = ( event ) => {
    	const {
    		addOption,
    		id,
    	} = this.props;

    	addOption( id, { text: 'asdasdasd' } );
    };

    /**
     * Event handler to remove a blank.
     *
     * @param option
     */
    removeBlank = ( option ) => () => {
    	const {
    		id,
    		removeOption,
    	} = this.props;

    	removeOption( id, option.question_answer_id );
    };

    /**
     * Get html of passage content for preview.
     *
     * @return {string}
     */
    getPreview = () => {
    	const {
    		blankOptions,
    		blankFillsStyle,
    		blanksStyle,
    	} = this.props;

    	if ( ! blankOptions ) {
    		return '';
    	}

    	const preview = blankOptions.map( ( answer ) => {
    		const blanks = this.getBlanks( answer.text );
    		const blank = blanks ? blanks[ 0 ] : {};

    		let html = '';

    		if ( blank && blank.words.length ) {
    			html = '<span class="blank-input"></span>';

    			if ( blank.words.length > 1 ) {
    				switch ( blankFillsStyle ) {
    				case 'select':
    					html += '<select>' + blank.words.map( ( word ) => {
    						return `<option value=${ word }>${ word }</option>`;
    					} ).join( '' ) + '</select>';

    					break;
    				case 'enumeration':
    					html += '(' + blank.words.map( ( word ) => {
    						return `<code>${ word }</code>`;
    					} ).join( ', ' ) + ')';
    					break;
    				}
    			}

    			if ( blank.tip ) {
    				html += '?';
    			}
    		}

    		return ( '' + answer.text ).replace( /\{\{(.*)\}\}/, html );
    	} ).join( blanksStyle === 'paragraphs' ? '</div><div>' : ( blanksStyle === 'ordered' ? '</li><li>' : ' ' ) );

    	return blanksStyle === 'paragraphs' ? `<div>${ preview }</div>` : ( blanksStyle === 'ordered' ? `<ol><li>${ preview }</li></ol>` : preview );
    };

    render() {
    	const {
    		blanks,
    		blankOptions,
    	} = this.props;

    	return <React.Fragment>
    		<div className="blank-options">
    			{ blankOptions && (
	<ul className="blanks">
    					{
    						blankOptions.map( ( answer ) => {
    							const blankOptions = blanks[ answer.question_answer_id ] || {};

    							return <li className="blank" key={ answer.question_answer_id }>
    								<textarea className="blank-content" onChange={ this.onChangeOption( answer ) }
    									value={ answer.text }>
	</textarea>
    								{ blankOptions.words && (
	<div className="blank-words">
    										<label>{ __( 'Words fill', 'learnpress' ) }</label>
    										<p>
    											{
    												blankOptions.words && blankOptions.words.map( ( word, i ) => {
    													const className = [ 'word' ];
    													( blankOptions.corrects || [] ).indexOf( word ) !== -1 && className.push( 'correct' );

    													return <code key={ `word-${ word }-${ i }` }
    														className={ className.join( ' ' ) }>{ word }</code>;
    												} )
    											}
		</p>
    									</div>
    								)
    								}

    								{ blankOptions.tip && (
	<div className="blank-tip">
    										<label>{ __( 'Tip', 'learnpress' ) }</label>
    										<p>{ blankOptions.tip }</p>
    									</div>
    								)
    								}

    								<button className="button button-remove"
    									onClick={ this.removeBlank( answer ) }>{ __( 'Remove', 'learnpress' ) }</button>
    							</li>;
    						} )
    					}
    				</ul> )
    			}
    			<button className="button" onClick={ this.addBlank }>{ __( 'Add Blank', 'learnpress' ) }</button>
	</div>
    		<div className="passage-preview" dangerouslySetInnerHTML={ { __html: this.getPreview() } }>
	</div>
    	</React.Fragment>;
    }
}

export default compose( [
	withSelect( ( select ) => {
		const {
			getData,
		} = select( 'learnpress/question' );

		return getData( 'question' );
	} ),
	withDispatch( ( dispatch ) => {
		const {
			setData,
			addOption,
			removeOption,
			updateOption,
		} = dispatch( 'learnpress/question' );

		return {
			setData,
			addOption,
			removeOption,
			updateOption,
		};
	} ),
] )( Editor );
