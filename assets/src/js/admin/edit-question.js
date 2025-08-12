/**
 * Edit question JS handler.
 *
 * @since 4.2.9
 * @version 1.0.0
 */

import * as lpUtils from '../utils.js';

const className = {
	elEditQuestionWrap: '.lp-edit-question-wrap',
	elQuestionToggleAll: '.lp-question-toggle-all',
	elEditListQuestions: '.lp-edit-list-questions',
	elQuestionItem: '.lp-question-item',
	elQuestionToggle: '.lp-question-toggle',
	elBtnShowPopupItemsToSelect: '.lp-btn-show-popup-items-to-select',
	elPopupItemsToSelectClone: '.lp-popup-items-to-select.clone',
	elBtnAddQuestion: '.lp-btn-add-question',
	elBtnRemoveQuestion: '.lp-btn-remove-question',
	elBtnUpdateQuestionTitle: '.lp-btn-update-question-title',
	elBtnUpdateQuestionDes: '.lp-btn-update-question-des',
	elBtnUpdateQuestionHint: '.lp-btn-update-question-hint',
	elBtnUpdateQuestionExplain: '.lp-btn-update-question-explanation',
	elQuestionTitleNewInput: '.lp-question-title-new-input',
	elQuestionTitleInput: '.lp-question-title-input',
	elQuestionTypeLabel: '.lp-question-type-label',
	elQuestionTypeNew: '.lp-question-type-new',
	elAddNewQuestion: 'add-new-question',
	elQuestionClone: '.lp-question-item.clone',
	elAnswersConfig: '.lp-answers-config',
	elBtnAddAnswer: '.lp-btn-add-question-answer',
	elQuestionAnswerItemAddNew: '.lp-question-answer-item-add-new',
	elQuestionAnswerTitleInput: '.lp-question-answer-title-input',
	elBtnDeleteAnswer: '.lp-btn-delete-question-answer',
	elQuestionByType: '.lp-question-by-type',
	elInputAnswerSetTrue: '.lp-input-answer-set-true',
	elQuestionAnswerItem: '.lp-question-answer-item',
	elBtnUpdateQuestionAnswer: '.lp-btn-update-question-answer',
	elBtnFibInsertBlank: '.lp-btn-fib-insert-blank',
	elBtnFibDeleteAllBlanks: '.lp-btn-fib-delete-all-blanks',
	elBtnFibSaveContent: '.lp-btn-fib-save-content',
	elBtnFibClearAllContent: '.lp-btn-fib-clear-all-content',
	elFibInput: '.lp-question-fib-input',
	elFibOptionTitleInput: '.lp-question-fib-option-title-input',
	elFibBlankOptions: '.lp-question-fib-blank-options',
	elFibBlankOptionItem: '.lp-question-fib-blank-option-item',
	elFibBlankOptionItemClone: '.lp-question-fib-blank-option-item.clone',
	elFibBlankOptionIndex: '.lp-question-fib-option-index',
	elBtnFibOptionDelete: '.lp-btn-fib-option-delete',
	elQuestionFibOptionMatchCaseInput: '.lp-question-fib-option-match-case-input',
	elQuestionFibOptionMatchCaseWrap: '.lp-question-fib-option-match-case-wrap',
	elQuestionFibOptionDetail: '.lp-question-fib-option-detail',
	elQuestionFibOptionComparisonInput: '.lp-question-fib-option-comparison-input',
	LPTarget: '.lp-target',
	elCollapse: 'lp-collapse',
	elSectionToggle: '.lp-section-toggle',
	elTriggerToggle: '.lp-trigger-toggle',
};

// Get section by id
const initTinyMCE = ( elEditQuestionWrap ) => {
	const elTextareas =
		document.querySelectorAll( '.lp-editor-tinymce' );

	elTextareas.forEach( ( elTextarea ) => {
		// const elParent = elTextarea.closest( '.lp-question-item' );
		const idTextarea = elTextarea.id;

		reInitTinymce( idTextarea );
	} );
};

// Re-initialize TinyMCE editor
const reInitTinymce = ( id ) => {
	window.tinymce.execCommand( 'mceRemoveEditor', true, id );
	window.tinymce.execCommand( 'mceAddEditor', true, id );
	//eventEditorTinymceChange( id );
};

// Element root ready.
lpUtils.lpOnElementReady(
	`${ className.elEditQuestionWrap }`,
	( elEditQuestionWrap ) => {
		initTinyMCE( elEditQuestionWrap );
	}
);
