import { Component } from '@wordpress/element';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';

import Question from './question';

class Questions extends Component {
	constructor( props ) {
		super( ...arguments );

		this.needToTop = false;
		this.state = {
			isReviewing: null,
			currentPage: 0,
			self: this,
		};
	}

	static getDerivedStateFromProps( props, state ) {
		const checkProps = [ 'isReviewing', 'currentPage' ];
		const changedProps = {};

		for ( let i = 0; i < checkProps.length; i++ ) {
			if ( props[ checkProps[ i ] ] !== state[ checkProps[ i ] ] ) {
				changedProps[ checkProps[ i ] ] = props[ checkProps[ i ] ];
			}
		}

		if ( Object.values( changedProps ).length ) {
			state.self.needToTop = true;
			return changedProps;
		}

		return null;
	}

	// componentWillReceiveProps(nextProps){
	//     const checkProps = ['isReviewing', 'currentPage'];
	//
	//     for(let i = 0; i < checkProps.length; i++){
	//         if(this.props[checkProps[i]] !== nextProps[checkProps[i]]){
	//             this.needToTop = true;
	//             return;
	//         }
	//     }
	//
	// }

	// componentWillUpdate() {
	//     this.needToTop = this.state.needToTop;
	//     this.setState({needToTop: false});
	// }

	componentDidUpdate() {
		if ( this.needToTop ) {
			jQuery( '#popup-content' )
				.animate( { scrollTop: 0 } )
				.find( '.content-item-scrollable:last' )
				.animate( { scrollTop: 0 } );
			this.needToTop = false;
		}
	}

	startQuiz = ( event ) => {
		event.preventDefault();

		const {
			startQuiz,
		} = this.props;

		startQuiz();
	};

	isInVisibleRange = ( id, index ) => {
		const {
			currentPage,
			questionsPerPage,
		} = this.props;
		return currentPage === Math.ceil( index / questionsPerPage );
	};

	nav = ( event ) => {
		const { sendKey } = this.props;

		switch ( event.keyCode ) {
		case 37:
			return sendKey( 'left' );
		case 38:
			return;
		case 39:
			return sendKey( 'right' );
		case 40:
			return;
		default:
			if ( event.keyCode >= 49 && event.keyCode <= 57 ) {
				sendKey( event.keyCode - 48 );
			}
		}
	}

	render() {
		const {
			status,
			currentQuestion,
			questions,
			questionsRendered,
			isReviewing,
			questionsPerPage,
		} = this.props;
		let isShow = true;

		if ( status === 'completed' && ! isReviewing ) {
			isShow = false;
		}

		return (
			<>
				<div tabIndex={ 100 } onKeyUp={ this.nav }>
					<div className="quiz-questions" style={ { display: isShow ? '' : 'none' } }>
						{ questions.map( ( question, index ) => {
							const isCurrent = questionsPerPage ? false : currentQuestion === question.id;
							const isRendered = questionsRendered && questionsRendered.indexOf( question.id ) !== -1;
							const isVisible = this.isInVisibleRange( question.id, index + 1 );
							return (
								( isRendered || ! isRendered ) || isVisible
									? <Question
											key={ `loop-question-${ question.id }` }
											isCurrent={ isCurrent }
											isShow={ isVisible }
											isShowIndex={ questionsPerPage ? index + 1 : false }
											questionsPerPage={ questionsPerPage }
											question={ question }
									/> : ''
							);
						} ) }
					</div>
				</div>
			</>
		);
	}
}

export default compose(
	withSelect( ( select, a, b ) => {
		const {
			getData,
			getQuestions,
		} = select( 'learnpress/quiz' );

		return {
			status: getData( 'status' ),
			currentQuestion: getData( 'currentQuestion' ),
			questions: getQuestions(),
			questionsRendered: getData( 'questionsRendered' ),
			isReviewing: getData( 'mode' ) === 'reviewing',
			numPages: getData( 'numPages' ),
			currentPage: getData( 'currentPage' ),
			questionsPerPage: getData( 'questionsPerPage' ) || 1,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const {
			startQuiz,
			sendKey,
		} = dispatch( 'learnpress/quiz' );

		return {
			startQuiz,
			sendKey,
		};
	} )
)( Questions );
