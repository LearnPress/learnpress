/* eslint-disable no-mixed-spaces-and-tabs */
import { Component } from '@wordpress/element';
import { select as wpSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const { isArray, get, set } = lodash;

class QuestionBase extends Component {
	constructor( props ) {
		super( ...arguments );

		const { question } = props;

		this.state = {
			optionClass: [ 'answer-option' ],
			questionId: 0,
			options: question ? this.parseOptions( question.options ) : [],
			self: this,
		};

		if ( props.$wrap ) {
			this.$wrap = props.$wrap;
		}
	}

	static getDerivedStateFromProps( props, state ) {
		return state.self.prepare( props, state );
	}

	componentDidMount() {
		const newState = this.prepare( this.props, this.state );

		if ( newState ) {
			this.setState( newState );
		}
	}

	prepare = ( props, state ) => {
		const { question } = props;

		if ( question && question.id !== state.questionId ) {
			return {
				options: state.self.parseOptions( question.options ),
			};
		}

		return null;
	}

	setInputRef = ( el, k ) => {
		if ( ! this.inputs ) {
			this.inputs = {};
		}

		this.inputs[ k ] = el;
	}

	/**
	 * Only show correct answer
	 * status = completed
	 * todo: check isset answered but if skip it will not show.
	 *
	 * @author Nhamdv
	 */
	maybeShowCorrectAnswer = () => {
		const { status, isCheckedAnswer, showCorrectReview, isReviewing } = this.props;

		return ( status === 'completed' && showCorrectReview ) || ( isCheckedAnswer && ! isReviewing );
	};

	/**
	 * Disable answer option in review mode or user has checked the question.
	 *
	 * @param option Doc.
	 */
	maybeDisabledOption = ( option ) => {
		const {
			answered,
			status,
			isCheckedAnswer,
		} = this.props;

		return isCheckedAnswer || ( status !== 'started' );
	};

	/**
	 * Event callback for clicking on answer option to
	 * store answered
	 */
	setAnswerChecked = () => ( event ) => {
		const {
			updateUserQuestionAnswers,
			question,
			status,
		} = this.props;

		if ( status !== 'started' ) {
			return __( 'LP Error: can not set answers', 'learnpress' );
		}

		const $options = this.$wrap.find( '.option-check' );
		const answered = [];
		const isSingle = question.type !== 'multi_choice';

		$options.each( ( i, option ) => {
			if ( option.checked ) {
				answered.push( option.value );

				if ( isSingle ) {
					return false;
				}
			}
		} );

		updateUserQuestionAnswers( question.id, isSingle ? answered[ 0 ] : answered );
	};

	maybeCheckedAnswer = ( value ) => {
		const { answered } = this.props;

		if ( isArray( answered ) ) {
			return !! answered.find( ( a ) => {
				return a == value;
			} );
		}

		return value == answered;
	};

	getOptionType = ( questionType, option ) => {
		let type = 'radio';

		switch ( questionType ) {
		case 'multi_choice':
			type = 'checkbox';
			break;
		}

		return type;
	};

	isDefaultType = () => {
		return this.props.supportOptions;
	};

	getWarningMessage = () => {
		return <>{ __( 'The render function should be overwritten from the base.', 'learnpress' ) }</>;
	};

	getOptionClass = ( option ) => {
		const { answered } = this.props;

		const classes = [ 'answer-option' ];

		return classes;
	};

	parseOptions = ( options ) => {
		if ( options ) {
			options = ! isArray( options ) ? JSON.parse( CryptoJS.AES.decrypt( options.data, options.key, { format: CryptoJSAesJson } ).toString( CryptoJS.enc.Utf8 ) ) : options;
			options = ! isArray( options ) ? JSON.parse( options ) : options;
		}

		return options || [];
	};

	getOptions = () => {
		return this.state.options || [];
	};

	isCorrect = () => {
		const { answered } = this.props;

		if ( ! answered ) {
			return false;
		}

		let i, option, options;

		for ( i = 0, options = this.getOptions(); i < options.length; i++ ) {
			option = options[ i ];

			if ( option.isTrue === 'yes' ) {
				if ( answered == option.value ) {
					return true;
				}
			}
		}

		return false;
	};

	isChecked = () => {
		const { question } = this.props;

		return wpSelect( 'learnpress/quiz' ).isCheckedAnswer( question.id );
	};

	getCorrectLabel = () => {
		const { status, answered, question } = this.props;

		const checker = LP.config.isQuestionCorrect[ question.type ] || this.isCorrect;
		const isCorrect = checker.call( this );

		return this.maybeShowCorrectAnswer() && (
			<div className={ `question-response` + ( isCorrect ? ' correct' : ' incorrect' ) }>
				<span className="label">{ isCorrect ? __( 'Correct', 'learnpress' ) : __( 'Incorrect', 'learnpress' ) }</span>
				<span className="point">{ sprintf( __( '%d/%d point', 'learnpress' ), isCorrect ? question.point : 0, question.point ) }</span>
			</div>
		);
	};

	render() {
		const { question, status } = this.props;

		return (
			<div className="question-answers">

				{ this.isDefaultType() && (
					<ul id={ `answer-options-${ question.id }` } className="answer-options">

						{ this.getOptions().map( ( option ) => {
							const ID = `learn-press-answer-option-${ option.uid }`;

							return (
								<li className={ this.getOptionClass( option ).join( ' ' ) }
									key={ `answer-option-${ option.uid }` }
								>
									<input type={ this.getOptionType( question.type, option ) }
										className="option-check"
										name={ status === 'started' ? `learn-press-question-${ question.id }` : '' }
										id={ ID }
										ref={ ( el ) => {
											this.setInputRef( el, option.value );
										} }
										onChange={ this.setAnswerChecked() }
										disabled={ this.maybeDisabledOption( option ) }
										checked={ this.maybeCheckedAnswer( option.value ) }
										value={ status === 'started' ? option.value : '' }
									/>

									<label htmlFor={ ID }
										className="option-title"
										dangerouslySetInnerHTML={ { __html: option.title || option.value } }>
									</label>
								</li>
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
export default QuestionBase;
