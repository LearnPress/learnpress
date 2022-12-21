const { Hook } = LP;

export const classNames = {
	Quiz: {
		Result: [ 'quiz-result' ],
		Content: [ 'quiz-content' ],
		Questions: [ 'quiz-questions' ],
		Buttons: [ 'quiz-buttons' ],
		Attempts: [ 'quiz-attempts' ],
	},
};

const questionCheckers = {
	single_choice() {

	},

	multi_choice() {

	},

	true_or_false() {

	},
};

export const isQuestionCorrect = {
	fill_in_blank() {
		return true;
	},
};

/**
 * Question blocks.
 *
 * Allow to sort the blocks of question
 */
export const questionBlocks = function() {
	return LP.Hook.applyFilters( 'question-blocks', [ 'title', 'content', 'answer-options', 'explanation', 'hint', 'buttons' ] );
};

export const questionFooterButtons = function() {
	return LP.Hook.applyFilters( 'question-footer-buttons', [ 'instant-check' ] );
};

export const questionTitleParts = function() {
	return LP.Hook.applyFilters( 'question-title-parts', [ 'index', 'title', 'hint', 'edit-permalink' ] );
};

export const questionChecker = function( type ) {
	const c = LP.Hook.applyFilters( 'question-checkers', questionCheckers );

	return type && c[ type ] ? c[ type ] : function() {
		return {};
	};
};

export const quizStartBlocks = function() {
	const blocks = Hook.applyFilters( 'quiz-start-blocks', {
		meta: true,
		description: true,
		custom: 'Hello',
	} );
};
