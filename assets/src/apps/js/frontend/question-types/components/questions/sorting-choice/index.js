import { __ } from '@wordpress/i18n';
import QuestionBase from '../../question-base';

class QuestionSortingChoice extends QuestionBase {
	componentDidMount() {
		const { updateUserQuestionAnswers, question } = this.props;

		const ele = document.querySelector( `#answer-options-${ question.id }` );

		return jQuery( ele ).sortable( {
			items: '.answer-option',
			cursor: 'move',
			axis: 'y',
			handle: '.option-drag',
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			update( event, ui ) {
				const options = ele.querySelectorAll( '.answer-option' );
				const answers = [];

				[ ...options ].map( ( option ) => {
					return answers.push( option.dataset.value );
				} );

				if ( answers.length > 0 ) {
					updateUserQuestionAnswers( question.id, answers );
				}
			},
		} );
	}

	isCorrect = () => {
		const { answered } = this.props;

		if ( ! answered ) {
			return false;
		}

		let i, option, options, sort;

		for ( i = 0, options = this.getOptions(); i < options.length; i++ ) {
			option = options[ i ];
			sort = option.sorting;

			if ( answered[ sort ] !== option.value ) {
				return false;
			}
		}

		return true;
	};

	getCorrectLabel = () => {
		const { question } = this.props;

		const checker = this.isCorrect;
		const isCorrect = checker.call( this );

		return this.maybeShowCorrectAnswer() && (
			<>
				<div className={ `question-response` + ( isCorrect ? ' correct' : ' incorrect' ) }>
					<span className="label">{ isCorrect ? __( 'Correct', 'learnpress' ) : __( 'Incorrect', 'learnpress' ) }</span>
					<span className="point">{ sprintf( __( '%d/%d point', 'learnpress' ), isCorrect ? question.point : 0, question.point ) }</span>
				</div>
			</>
		);
	};

	getAnswerSortingChoice = () => {
		const { question } = this.props;

		const options = question.options || [];

		const checker = this.isCorrect;
		const isCorrect = checker.call( this );
		const getAnswer = [];

		if ( ! isCorrect && options.length > 0 ) {
			options.map( ( option ) => {
				const sorting = option.sorting;

				if ( sorting !== undefined ) {
					return getAnswer[ sorting ] = option.title;
				}
			} );
		}

		return getAnswer;
	}

	render() {
		const { question } = this.props;

		const getAnswer = this.getAnswerSortingChoice();

		return (
			<div className="question-answers">

				{ this.isDefaultType() && (
					<ul id={ `answer-options-${ question.id }` } className="answer-options lp-sorting-choice-ul">

						{ this.getOptions().map( ( option, key ) => {
							return (
								<>
									<li className={ this.getOptionClass( option ).join( ' ' ) } key={ `answer-option-${ option.value }` } data-value={ option.value }>
										<span className="option-drag" style={ { display: 'flex', alignItems: 'center', position: 'absolute', height: '100%', left: 14 } }>
											<svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none" /><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z" /></svg>
										</span>

										<label htmlFor={ `learn-press-answer-option-${ option.value }` }
											className="option-title"
											dangerouslySetInnerHTML={ { __html: option.title || option.value } }>
										</label>
									</li>

									{ getAnswer.length > 0 && getAnswer[ key ] !== undefined && (
										<div className={ 'lp-sorting-choice__check-answer' } key={ `lp-checked-answer-${ key }` } style={ { marginBottom: 10 } }>
											{ getAnswer[ key ] }
										</div>
									) }
								</>
							);
						} ) }
					</ul>
				) }

				{ ! this.isDefaultType() && this.getWarningMessage() }
				{ this.getCorrectLabel() }
			</div>
		);
	}
}

export default QuestionSortingChoice;
