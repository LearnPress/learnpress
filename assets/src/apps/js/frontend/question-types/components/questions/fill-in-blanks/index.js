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

	render() {
		return (
			<>
				<div className="lp-fib-content">
					{ this.getOptions().map( ( option ) => {
						return (
							<div key={ `blank-${ option.uid }` } dangerouslySetInnerHTML={ { __html: option.title || option.value } }></div>
						);
					} )
					}
				</div>
			</>
		);
	}
}

export default QuestionFillInBlanks;
