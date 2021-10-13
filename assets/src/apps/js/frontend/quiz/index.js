import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { Content, Meta, Buttons, Questions, Attempts, Result, Status } from './components';

import store from './store';

const { chunk } = lodash;
class Quiz extends Component {
	constructor( props ) {
		super( ...arguments );

		this.state = {
			currentPage: 1,
			numPages: 0,
			pages: [],
		};
	}

	componentDidMount() {
		const { settings, setQuizData } = this.props;

		const { question_ids, questions_per_page } = settings;

		const chunks = chunk( question_ids, questions_per_page );

		settings.currentPage = 1;
		settings.numPages = chunks.length;
		settings.pages = chunks;

		const answered = settings.id ? localStorage.getItem( `LP_Quiz_${ settings.id }_Answered` ) : false;

		if ( answered ) {
			settings.answered = JSON.parse( answered );
		}

		setQuizData( settings );
	}

	startQuiz = ( event ) => {
		this.props.startQuiz();
	};

	render() {
		const { status, isReviewing } = this.props;

		const isA =
			-1 !== [ '', 'completed', 'viewed' ].indexOf( status ) || ! status;
		const notStarted =
			-1 !== [ '', 'viewed', undefined ].indexOf( status ) || ! status;

		// Just render content if status !== undefined (meant all data loaded)
		return (
			undefined !== status && (
				<>
					<div>
						{ ! isReviewing && 'completed' === status && (
							<Result />
						) }

						{ ! isReviewing && notStarted && <Meta /> }
						{ ! isReviewing && notStarted && <Content /> }

						{ 'started' === status && <Status /> }

						{ ( -1 !== [ 'completed', 'started' ].indexOf( status ) ||
								isReviewing ) && <Questions /> }

						<Buttons />

						{ isA && ! isReviewing && <Attempts /> }
					</div>
				</>
			)
		);
	}
}

export default compose( [
	withSelect( ( select ) => {
		const { getQuestions, getData } = select( 'learnpress/quiz' );

		return {
			questions: getQuestions(),
			status: getData( 'status' ),
			store: getData(),
			answered: getData( 'answered' ),
			isReviewing: getData( 'mode' ) === 'reviewing',
			questionIds: getData( 'questionIds' ),
			checkCount: getData( 'instantCheck' ),
			questionsPerPage: getData( 'questionsPerPage' ) || 1,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { setQuizData, startQuiz } = dispatch( 'learnpress/quiz' );

		return {
			setQuizData,
			startQuiz,
		};
	} ),
] )( Quiz );
