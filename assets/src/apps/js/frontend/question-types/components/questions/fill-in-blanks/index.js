import { __ } from '@wordpress/i18n';
import QuestionBase from '../../question-base';

class QuestionFillInBlanks extends QuestionBase {
	componentDidMount() {
		const { answered } = this.props;

		if ( answered ) {
			const allFIBs = document.querySelectorAll( '.lp-fib-input > input' );

			[ ...allFIBs ].map( ( ele ) => {
				if ( answered[ ele.dataset.id ] ) {
					ele.value = answered[ ele.dataset.id ];
				}
			} );
		}

		this.updateFibAnswer();
	}

	componentDidUpdate( prevProps ) {
		if ( ! prevProps.answered ) {
			this.updateFibAnswer();
		}
	}

	updateFibAnswer = () => {
		const allFIBs = document.querySelectorAll( '.lp-fib-input > input' );
		const { answered } = this.props;

		const answereds = answered || {};

		[ ...allFIBs ].map( ( ele ) => {
			if ( answered === undefined ) {
				ele.value = '';
			}

			ele.addEventListener( 'input', ( e ) => {
				this.setAnswered( answereds, ele.dataset.id, e.target.value );
			} );

			ele.addEventListener( 'paste', ( e ) => {
				this.setAnswered( answereds, ele.dataset.id, e.target.value );
			} );
		} );
	};

	setAnswered = ( answered, id, value ) => {
		const {
			updateUserQuestionAnswers,
			question,
			status,
		} = this.props;

		if ( status !== 'started' ) {
			return 'LP Error: can not set answers';
		}

		const newAnswered = Object.assign( answered, { [ id ]: value } );

		updateUserQuestionAnswers( question.id, newAnswered );
	};

	getCorrectLabel = () => {
		const { question, mark } = this.props;

		let getMark = mark || 0;

		if ( mark ) {
			if ( ! Number.isInteger( mark ) ) {
				getMark = mark.toFixed( 2 );
			}
		}
		return this.maybeShowCorrectAnswer() && (
			<div className="question-response correct">
				<span className="label">{ __( 'Points', 'learnpress' ) }</span>
				<span className="point">{ `${ getMark }/${ question.point } ${ __( 'point', 'learnpress' ) }` }</span>
				<span className="lp-fib-note"><span style={ { background: '#00adff' } }></span>{ __( 'Correct', 'learnpress' ) }</span>
				<span className="lp-fib-note"><span style={ { background: '#d85554' } }></span>{ __( 'Incorrect', 'learnpress' ) }</span>
			</div>
		);
	};

	convertInputField = ( option ) => {
		const { answered, isReviewing, showCorrectReview, isCheckedAnswer } = this.props;

		let title = option.title;

		const answers = option?.answers;

		option.ids.map( ( id, index ) => {
			const textReplace = '{{FIB_' + id + '}}';
			let elContent = '';

			const answerID = answers ? answers?.[ id ] : undefined;

			if ( answerID || isReviewing ) {
				elContent += `<span class="lp-fib-answered ${ ( showCorrectReview || isCheckedAnswer ) && answerID?.correct ? ( answerID?.isCorrect ? 'correct' : 'fail' ) : '' }">`;

				if ( ! answerID?.isCorrect ) {
					elContent += `<span class="lp-fib-answered__answer">${ answered?.[ id ] ?? '' }</span>`;
				}

				if ( ! answerID?.isCorrect && answerID?.correct ) {
					elContent += ' → ';
				}

				elContent += `<span class="lp-fib-answered__fill">${ answerID?.correct ?? '' }</span>`;
				elContent += '</span>';
			} else {
				elContent += '<div class="lp-fib-input" style="display: inline-block; width: auto;">';
				elContent += '<input type="text" data-id="' + id + '" value="" />';
				elContent += '</div>';
			}

			title = title.replace( textReplace, elContent );
		} );

		return title;
	};

	render() {
		return (
			<>
				<div className="lp-fib-content">
					{ this.getOptions().map( ( option ) => {
						return (
							<div key={ `blank-${ option.uid }` } dangerouslySetInnerHTML={ { __html: this.convertInputField( option ) || option.value } }></div>
						);
					} ) }
				</div>

				{ ! this.isDefaultType() && this.getWarningMessage() }
				{ this.getCorrectLabel() }
			</>
		);
	}
}

export default QuestionFillInBlanks;
