const Question = {
	changeQuestionType( context, payload ) {
		const draftQuestion = undefined !== payload.question ? payload.question : '';

		LP.Request( {
			type: 'change-question-type',
			question_type: payload.type,
			draft_question: context.getters.autoDraft ? draftQuestion : '',
		} ).then( function( response ) {
			const result = response.body;

			if ( result.success ) {
				context.commit( 'UPDATE_AUTO_DRAFT_STATUS', false );
				context.commit( 'CHANGE_QUESTION_TYPE', result.data );
			}
		} );
	},

	updateAnswersOrder( context, order ) {
		LP.Request( {
			type: 'sort-answer',
			order,
		} ).then(
			function( response ) {
				const result = response.body;
				if ( result.success ) {
					// context.commit('SET_ANSWERS', result.data);
				}
			}
		);
	},

	updateAnswerTitle( context, answer ) {
		if ( typeof answer.question_answer_id == 'undefined' ) {
			return;
		}

		answer = JSON.stringify( answer );

		LP.Request( {
			type: 'update-answer-title',
			answer,
		} );
	},

	updateCorrectAnswer( context, correct ) {
		LP.Request( {
			type: 'change-correct',
			correct: JSON.stringify( correct ),
		} ).then(
			function( response ) {
				const result = response.body;
				if ( result.success ) {
					context.commit( 'UPDATE_ANSWERS', result.data );
					context.commit( 'UPDATE_AUTO_DRAFT_STATUS', false );
				}
			}
		);
	},

	deleteAnswer( context, payload ) {
		context.commit( 'DELETE_ANSWER', payload.id );
		LP.Request( {
			type: 'delete-answer',
			answer_id: payload.id,
		} ).then(
			function( response ) {
				const result = response.body;

				if ( result.success ) {
					context.commit( 'SET_ANSWERS', result.data );
				} else {
					// notice error
				}
			} );
	},

	newAnswer( context, data ) {
		context.commit( 'ADD_NEW_ANSWER', data.answer );
		LP.Request( {
			type: 'new-answer',
		} ).then(
			function( response ) {
				const result = response.body;

				if ( result.success ) {
					context.commit( 'UPDATE_ANSWERS', result.data );
				} else {
					// notice error
				}
			} );
	},

	newRequest( context ) {
		context.commit( 'INCREASE_NUMBER_REQUEST' );
		context.commit( 'UPDATE_STATUS', 'loading' );

		window.onbeforeunload = function() {
			return '';
		};
	},

	requestCompleted( context, status ) {
		context.commit( 'DECREASE_NUMBER_REQUEST' );

		if ( context.getters.currentRequest === 0 ) {
			context.commit( 'UPDATE_STATUS', status );
			window.onbeforeunload = null;
		}
	},
};

export default Question;
