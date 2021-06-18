import { Component } from '@wordpress/element';
import { withSelect, withDispatch, select } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

class Buttons extends Component {
	startQuiz = ( event ) => {
		event && event.preventDefault();
		const btn = document.querySelector( '.lp-button.start' );
		btn && btn.setAttribute( 'disabled', 'disabled' );
		const { startQuiz, status } = this.props;

		if ( status === 'completed' ) {
			const { confirm, isOpen } = select( 'learnpress/modal' );

			if ( 'no' === confirm( __( 'Are you sure you want to retake quiz?', 'learnpress' ), this.startQuiz ) ) {
				! isOpen() && btn && btn.removeAttribute( 'disabled' );
				return;
			}
		}
		if ( lpQuizSettings.checkNorequizenroll == '1' ) {
			// remove & set start_time to local.storage
			window.localStorage.removeItem( 'quiz_start_' + lpQuizSettings.id );
			window.localStorage.setItem( 'quiz_start_' + lpQuizSettings.id, Date.now() );
			// Set retake to local.storage
			const retakenNumber = window.localStorage.getItem( 'quiz_retake_' + lpQuizSettings.id );
			if ( retakenNumber >= 1 ) {
				window.localStorage.setItem( 'quiz_retake_' + lpQuizSettings.id, parseInt( retakenNumber ) + 1 );
			} else {
				window.localStorage.setItem( 'quiz_retake_' + lpQuizSettings.id, 1 );
			}
			// Reset User Data
			window.localStorage.removeItem( 'quiz_userdata_' + lpQuizSettings.id );
		}
		startQuiz();
	};

	nav = ( to ) => ( event ) => {
		let {
			questionNav,
			currentPage,
			numPages,
			setCurrentPage,
		} = this.props;

		switch ( to ) {
		case 'prev':
			if ( currentPage > 1 ) {
				currentPage = currentPage - 1;
			} else if ( questionNav === 'infinity' ) {
				currentPage = numPages;
			} else {
				currentPage = 1;
			}
			break;
		default:
			if ( currentPage < numPages ) {
				currentPage = currentPage + 1;
			} else if ( questionNav === 'infinity' ) {
				currentPage = 1;
			} else {
				currentPage = numPages;
			}
		}

		setCurrentPage( currentPage );
	};

	moveTo = ( pageNum ) => ( event ) => {
		event.preventDefault();

		const {
			numPages,
			setCurrentPage,
		} = this.props;

		if ( pageNum < 1 || pageNum > numPages ) {
			return;
		}

		setCurrentPage( pageNum );
	};

	isLast = () => {
		const { currentPage, numPages } = this.props;

		return currentPage === numPages;
	};

	isFirst = () => {
		const { currentPage } = this.props;

		return currentPage === 1;
	};

	submit = () => {
		const { submitQuiz } = this.props;

		const { confirm } = select( 'learnpress/modal' );

		if ( 'no' === confirm( __( 'Are you sure to submit quiz?', 'learnpress' ), this.submit ) ) {
			return;
		}
		submitQuiz();
	};

	setQuizMode = ( mode ) => () => {
		const { setQuizMode } = this.props;

		setQuizMode( mode );
	};

	isReviewing = () => {
		const { isReviewing } = this.props;

		return isReviewing;
	};

	pageNumbers( args ) {
		const { numPages, currentPage } = this.props;

		if ( numPages < 2 ) {
			return '';
		}

		args = {
			numPages,
			currentPage,
			midSize: 1,
			endSize: 1,
			prevNext: true,
			...( args || {} ),
		};

		if ( args.endSize < 1 ) {
			args.endSize = 1;
		}

		if ( args.midSize < 0 ) {
			args.midSize = 1;
		}

		const numbers = [ ...Array( numPages ).keys() ];
		let dots = false;

		return (
			<div className="nav-links">

				{ args.prevNext && ! this.isFirst() && (
					<button
						className="page-numbers prev"
						data-type="question-navx"
						onClick={ this.nav( 'prev' ) }
					>
						{ __( 'Prev', 'learnpress' ) }
					</button>
				) }

				{ numbers.map( ( number ) => {
					number = number + 1;

					if ( number === args.currentPage ) {
						dots = true;

						return (
							<span key={ `page-number-${ number }` } className="page-numbers current">{ number }</span>
						);
					}

					if ( number <= args.endSize || ( args.currentPage && number >= args.currentPage - args.midSize && number <= args.currentPage + args.midSize ) || number > args.numPages - args.endSize ) {
						dots = true;

						return (
							<button
								key={ `page-number-${ number }` }
								className="page-numbers"
								onClick={ this.moveTo( number ) }
							>
								{ number }
							</button>
						);
					} else if ( dots ) {
						dots = false;

						return (
							<span key={ `page-number-${ number }` } className="page-numbers dots">&hellip;</span>
						);
					}

					return '';
				} ) }

				{ args.prevNext && ! this.isLast() && (
					<button
						className="page-numbers next"
						data-type="question-navx"
						onClick={ this.nav( 'next' ) }
					>
						{ __( 'Next', 'learnpress' ) }
					</button>
				) }
			</div>
		);
	}

	render() {
		const {
			status,
			questionNav,
			isReviewing,
			showReview,
			numPages,
			question,
			questionsPerPage,
			canRetry,
			retakeNumber,
		} = this.props;

		const classNames = [ 'quiz-buttons' ];

		if ( status === 'started' || isReviewing ) {
			classNames.push( 'align-center' );
		}

		if ( questionNav === 'questionNav' ) {
			classNames.push( 'infinity' );
		}

		if ( this.isFirst() ) {
			classNames.push( 'is-first' );
		}

		if ( this.isLast() ) {
			classNames.push( 'is-last' );
		}

		const popupSidebar = document.querySelector( '#popup-sidebar' );
		const quizzApp = document.querySelector( '#learn-press-quiz-app' );

		let styles = '';

		if ( status === 'started' || isReviewing ) {
			styles = { marginLeft: popupSidebar && popupSidebar.offsetWidth / 2, width: quizzApp && quizzApp.offsetWidth };
		} else {
			styles = null;
		}
		let navPositionClass = ' fixed';
		if ( lpQuizSettings.navigationPosition == 'no' ) {
			navPositionClass = ' nav-center';
		}
		return (
			<>
				<div className={ classNames.join( ' ' ) }>
					<div
						className={ `button-left` + ( ( status === 'started' || isReviewing ) ? navPositionClass : '' ) }
						style={ styles }
					>

						{ ( ( status === 'completed' && canRetry ) || -1 !== [ '', 'viewed' ].indexOf( status ) ) && ! isReviewing && (
							<button className="lp-button start" onClick={ this.startQuiz }>
								{ ( status === 'completed' ) ? `${ __( 'Retake', 'learnpress' ) }${ retakeNumber ? ` (${ retakeNumber })` : '' }` :	__( 'Start', 'learnpress' ) }
							</button>
						) }

						{ ( 'started' === status || isReviewing ) && ( numPages > 1 ) && (
							<>
								<div className="questions-pagination">
									{ this.pageNumbers() }
								</div>
							</>
						) }
					</div>

					<div className="button-right">
						{ ( 'started' === status ) && (
							<>
								{ ( ( 'infinity' === questionNav || this.isLast() ) && ! isReviewing ) && (
									<button
										className="lp-button submit-quiz"
										onClick={ this.submit }
									>
										{ __( 'Finish Quiz', 'learnpress' ) }
									</button>
								) }
							</>
						) }

						{ isReviewing && showReview && (
							<button
								className="lp-button back-quiz"
								onClick={ this.setQuizMode( '' ) }
							>
								{ __( 'Result', 'learnpress' ) }
							</button>
						) }

						{ 'completed' === status && showReview && ! isReviewing && (
							<button
								className="lp-button review-quiz"
								onClick={ this.setQuizMode( 'reviewing' ) }
							>
								{ __( 'Review', 'learnpress' ) }
							</button>
						) }
					</div>
				</div>

				{ this.props.message && this.props.success !== true && (
					<div className="learn-press-message error">
						{ this.props.message }
					</div>
				) }
			</>
		);
	}
}

/**
 * Helper function to check a button should be show or not.
 *
 * Buttons [hint, check]
 */
export const MaybeShowButton = compose(
	withSelect( ( select ) => {
		const {
			getData,
		} = select( 'learnpress/quiz' );

		return {
			status: getData( 'status' ),
			showCheck: getData( 'instantCheck' ),
			checkedQuestions: getData( 'checkedQuestions' ),
			hintedQuestions: getData( 'hintedQuestions' ),
			questionsPerPage: getData( 'questionsPerPage' ),
		};
	} )
)( ( props ) => {
	const {
		showCheck,
		checkedQuestions,
		hintedQuestions,
		question,
		status,
		type,
		Button,
	} = props;

	if ( status !== 'started' ) {
		return false;
	}

	const theButton = <Button question={ question } />;

	switch ( type ) {
	case 'hint':

		if ( ! hintedQuestions ) {
			return theButton;
		}

		if ( ! question.hasHint ) {
			return false;
		}

		return hintedQuestions.indexOf( question.id ) === -1 && theButton;

	case 'check':

		if ( ! showCheck ) {
			return false;
		}

		if ( ! checkedQuestions ) {
			return theButton;
		}

		return checkedQuestions.indexOf( question.id ) === -1 && theButton;
	}
} );

export default compose( [
	withSelect( ( select ) => {
		const {
			getData,
			getCurrentQuestion,
		} = select( 'learnpress/quiz' );

		const data = {
			id: getData( 'id' ),
			status: getData( 'status' ),
			questionIds: getData( 'questionIds' ),
			questionNav: getData( 'questionNav' ),
			isReviewing: getData( 'reviewQuestions' ) && getData( 'mode' ) === 'reviewing',
			showReview: getData( 'reviewQuestions' ),
			showCheck: getData( 'instantCheck' ),
			checkedQuestions: getData( 'checkedQuestions' ),
			hintedQuestions: getData( 'hintedQuestions' ),
			numPages: getData( 'numPages' ),
			pages: getData( 'pages' ),
			currentPage: getData( 'currentPage' ),
			questionsPerPage: getData( 'questionsPerPage' ),
			pageNumbers: getData( 'pageNumbers' ),
			keyPressed: getData( 'keyPressed' ),
			canRetry: getData( 'retakeCount' ) > 0 && getData( 'retaken' ) < getData( 'retakeCount' ),
			retakeNumber: getData( 'retakeCount' ) > 0 && getData( 'retaken' ) < getData( 'retakeCount' ) ? getData( 'retakeCount' ) - getData( 'retaken' ) : null,
			message: getData( 'messageResponse' ) || false,
			success: getData( 'successResponse' ) !== undefined ? getData( 'successResponse' ) : true,
		};

		if ( data.questionsPerPage === 1 ) {
			data.question = getCurrentQuestion( 'object' );
		}

		if ( lpQuizSettings.checkNorequizenroll == '1' ) {
			const retakenCurrent = window.localStorage.getItem( 'quiz_retake_' + lpQuizSettings.id );
			if ( getData( 'retakeCount' ) > retakenCurrent ) {
				data.retakeNumber = getData( 'retakeCount' ) - retakenCurrent;
				data.canRetry = true;
			} else {
				data.canRetry = false;
			}
		}

		return data;
	} ),
	withDispatch( ( dispatch, { id } ) => {
		const {
			startQuiz,
			setCurrentQuestion,
			submitQuiz,
			setQuizMode,
			showHint,
			checkAnswer,
			setCurrentPage,
		} = dispatch( 'learnpress/quiz' );

		return {
			startQuiz,
			setCurrentQuestion,
			setQuizMode,
			setCurrentPage,
			submitQuiz( id ) {
				submitQuiz( id );
			},
			showHint( id ) {
				showHint( id );
			},
			checkAnswer( id ) {
				checkAnswer( id );
			},
		};
	} ),
] )( Buttons );
