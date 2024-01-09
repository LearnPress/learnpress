import classNames from 'classnames';

import { Component } from '@wordpress/element';
import { withDispatch, withSelect } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

class ButtonCheck extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			loading: false,
		};
	}

	checkAnswer = () => {
		const { checkAnswer, question, answered } = this.props;

		// Fix temporary for FIB.
		if ( question.type === 'fill_in_blanks' ) {
			const elFIB = document.querySelector( `.question-fill_in_blanks[data-id="${ question.id }"]` );
			const elInputs = elFIB.querySelectorAll( '.lp-fib-input > input' );
			elInputs.forEach( ( elInput ) => {
				if ( elInput.value.length > 0 ) {
					this.setState( {
						loading: true,
					} );
					checkAnswer( question.id );
					return false;
				}
			} );
		}

		if ( answered ) {
			checkAnswer( question.id );

			this.setState( {
				loading: true,
			} );
		}
	};

	render() {
		const { answered } = this.props;

		return (
			<>
				<button className={ classNames( 'lp-button', 'instant-check', {
					loading: this.state.loading,
				} ) } onClick={ this.checkAnswer }
				>
					<span className="instant-check__icon" />
					{ __( 'Check answers', 'learnpress' ) }

					{ ! answered && (
						<div className="instant-check__info" dangerouslySetInnerHTML={ { __html: __( 'You need to answer the question before checking the answer key.', 'learnpress' ) } } />
					) }
				</button>
			</>
		);
	}
}

export default compose(
	withSelect( ( select, { question: { id } } ) => {
		const { getQuestionAnswered } = select( 'learnpress/quiz' );

		return {
			answered: getQuestionAnswered( id ),
		};
	} ),
	withDispatch( ( dispatch, { id } ) => {
		const { checkAnswer } = dispatch( 'learnpress/quiz' );

		return {
			checkAnswer( id ) {
				checkAnswer( id );
			},
		};
	} )
)( ButtonCheck );
