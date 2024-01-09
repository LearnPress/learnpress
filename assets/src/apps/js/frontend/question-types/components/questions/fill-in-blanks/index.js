import { __ } from '@wordpress/i18n';
import QuestionBase from '../../question-base';

let flagEventEnterInput = false;

class QuestionFillInBlanks extends QuestionBase {
	componentDidMount() {
		const { answered, question } = this.props;

		if ( answered ) {
			const allFIBs = document.querySelectorAll( '.lp-fib-input > input' );

			[ ...allFIBs ].map( ( ele ) => {
				const question_id = parseInt( ele.closest( '.question' ).dataset.id );

				if ( question_id === question.id ) {
					if ( answered[ ele.dataset.id ] ) {
						ele.value = answered[ ele.dataset.id ];
					}
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
		if ( ! flagEventEnterInput ) {
			document.addEventListener( 'input', ( e ) => {
				const target = e.target;
				const parent = target.closest( '.lp-fib-input' );

				if ( parent ) {
					const elQuestionFIB = target.closest( '.question-fill_in_blanks' );
					const question_id = elQuestionFIB.dataset.id;
					this.setAnswered( question_id, target.dataset.id, target.value );
				}
			} );
		}

		flagEventEnterInput = true;
	};

	setAnswered = ( question_id, id, value ) => {
		const {
			updateUserQuestionFibAnswers,
			question,
			status,
		} = this.props;

		if ( status !== 'started' ) {
			return 'LP Error: can not set answers';
		}

		const newAnswered = {};
		newAnswered[ id ] = value;

		updateUserQuestionFibAnswers( question_id, id, value );
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
