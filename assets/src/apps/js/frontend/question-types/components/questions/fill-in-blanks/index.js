import { __ } from '@wordpress/i18n';
import QuestionBase from '../../question-base';

class QuestionFillInBlanks extends QuestionBase {
	componentDidMount() {
		this.updateFibAnswer();
	}

	componentDidUpdate( prevProps ) {
		if ( ! prevProps.answered ) {
			this.updateFibAnswer();
		}
	}

	updateFibAnswer = () => {
		const allFIBs = document.querySelectorAll( '.lp-fib-input > input' );
		const answered = {};

		[ ...allFIBs ].map( ( ele ) => {
			ele.addEventListener( 'input', ( e ) => {
				this.setAnswered( answered, ele.dataset.id, e.target.value );
			} );

			ele.addEventListener( 'paste', ( e ) => {
				this.setAnswered( answered, ele.dataset.id, e.target.value );
			} );
		} );
	}

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

	render() {
		return (
			<>
				<div className="lp-fib-content">
					{ this.getOptions().map( ( option ) => {
						return (
							<div key={ `blank-${ option.uid }` } dangerouslySetInnerHTML={ { __html: option.title || option.value } }></div>
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
