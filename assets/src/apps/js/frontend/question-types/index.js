import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

export * from './components';

class QuestionTypes extends Component {
	getQuestion = () => {
		const { question } = this.props;

		const types = LP.Hook.applyFilters( 'question-types', {
			single_choice: LP.questionTypes.SingleChoice,
			multi_choice: LP.questionTypes.MultipleChoices,
			true_or_false: LP.questionTypes.TrueOrFalse,
			fill_in_blanks: LP.questionTypes.FillInBlanks,
			sorting_choice: LP.questionTypes.SortingChoice,
		} );

		return types[ question.type ];
	}

	render() {
		const {
			question,
			supportOptions,
		} = this.props;

		const childProps = { ...this.props };
		childProps.supportOptions = supportOptions.indexOf( question.type ) !== -1;

		const TheQuestion = this.getQuestion() || function() {
			return (
				<div className="question-types"
					dangerouslySetInnerHTML={ { __html: sprintf( __( 'Question <code>%s</code> invalid!', 'learnpress' ), question.type ) } }>
				</div>
			);
		};

		return (
			<>
				<TheQuestion { ...childProps } />
			</>
		);
	}
}

export default compose(
	withSelect( ( select, { question: { id } } ) => {
		const {
			getData,
			isCheckedAnswer,
		} = select( 'learnpress/quiz' );

		return {
			supportOptions: getData( 'supportOptions' ),
			isCheckedAnswer: isCheckedAnswer( id ),
			keyPressed: getData( 'keyPressed' ),
			showCorrectReview: getData( 'showCorrectReview' ),
			isReviewing: getData( 'mode' ) === 'reviewing',
		};
	} ),
	withDispatch( () => {
		return {};
	} )
)( QuestionTypes );
