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
					disable: ! answered,
				} ) } onClick={ this.checkAnswer }
				>
					<span className="instant-check__icon" />
					{ __( 'Check answer', 'learnpress' ) }

					{ ! answered && (
						<div className="instant-check__info" dangerouslySetInnerHTML={ { __html: __( 'You need to answer the question before check answer.', 'learnpress' ) } } />
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
