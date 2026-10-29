/**
 * User quiz - learning page (replace React app frontend/quiz.js).
 *
 * HTML of each screen is rendered by PHP (UserQuizTemplate), this file only:
 * - Read params from data-send of buttons, call window.lpAJAXG.fetchAJAX (UserQuizAjax actions).
 * - Replace HTML returned (screen, checked question).
 * - Collect answers of user, save on localStorage to restore when reload page.
 * - Timer, pagination of questions, hint, review, sorting drag & drop.
 * - Guest (not logged in, course no required enroll): see ./user-quiz/guest.js.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
import SweetAlert from 'sweetalert2';
import Sortable from 'sortablejs';
import * as lpUtils from '../utils.js';
import * as lpToastify from '../lpToastify.js';
import { UserQuizGuest } from './user-quiz/guest.js';

/**
 * Get answer of a question from its inputs.
 * Single choice, true or false: string. Multi choice, sorting: array. Fill in blanks: { blank_id: fill }.
 * Add-on question types use filter 'lp-user-quiz/question-answer'.
 *
 * @param {Element} elQuestion .question[data-id]
 * @return {*} answer, null when not answered
 */
const getQuestionAnswer = ( elQuestion ) => {
	let answer = null;

	const elOptions = elQuestion.querySelectorAll( 'input.option-check' );
	if ( elOptions.length ) {
		const values = [ ...elOptions ].filter( ( el ) => el.checked ).map( ( el ) => el.value );
		if ( elOptions[ 0 ].type === 'checkbox' ) {
			answer = values.length ? values : null;
		} else {
			answer = values.length ? values[ 0 ] : null;
		}
	}

	const elBlanks = elQuestion.querySelectorAll( '.lp-fib-input input[data-id]' );
	if ( elBlanks.length ) {
		const blanks = {};
		elBlanks.forEach( ( el ) => {
			if ( el.value !== '' ) {
				blanks[ el.dataset.id ] = el.value;
			}
		} );
		answer = Object.keys( blanks ).length ? blanks : null;
	}

	// Options of sorting choice are shuffled by server, only an answer after user sorted (data-sorted).
	const elSortList = elQuestion.querySelector( '.lp-sorting-choice' );
	if ( elSortList ) {
		answer = elSortList.dataset.sorted ? [ ...elSortList.querySelectorAll( 'input.option-sort' ) ].map( ( el ) => el.value ) : null;
	}

	if ( window.wp?.hooks ) {
		answer = window.wp.hooks.applyFilters( 'lp-user-quiz/question-answer', answer, elQuestion );
	}

	return answer;
};

/**
 * Set answer to inputs of a question (restore from localStorage).
 *
 * @param {Element} elQuestion .question[data-id]
 * @param {*}       answer     same format as getQuestionAnswer()
 */
const setQuestionAnswer = ( elQuestion, answer ) => {
	if ( answer === null || answer === undefined ) {
		return;
	}

	const values = Array.isArray( answer ) ? answer.map( String ) : [ String( answer ) ];
	elQuestion.querySelectorAll( 'input.option-check' ).forEach( ( el ) => {
		el.checked = values.includes( el.value );
	} );

	if ( typeof answer === 'object' && ! Array.isArray( answer ) ) {
		elQuestion.querySelectorAll( '.lp-fib-input input[data-id]' ).forEach( ( el ) => {
			if ( answer[ el.dataset.id ] !== undefined ) {
				el.value = answer[ el.dataset.id ];
			}
		} );
	}

	const elSortList = elQuestion.querySelector( '.lp-sorting-choice' );
	if ( elSortList && Array.isArray( answer ) ) {
		elSortList.dataset.sorted = '1';
		values.forEach( ( value ) => {
			const elInput = [ ...elSortList.querySelectorAll( 'input.option-sort' ) ].find( ( el ) => el.value === value );
			if ( elInput ) {
				elSortList.appendChild( elInput.closest( 'li' ) );
			}
		} );
	}

	if ( window.wp?.hooks ) {
		window.wp.hooks.doAction( 'lp-user-quiz/question-answer-restored', elQuestion, answer );
	}
};

/**
 * Format timer, same as UserQuizTemplate::format_timer(): MM:SS when total < 1 hour, else HH:MM:SS.
 *
 * @param {number} seconds
 * @param {number} total   seconds, use to choose format
 * @return {string} time
 */
const formatTimer = ( seconds, total ) => {
	if ( seconds <= 0 ) {
		return '00:00';
	}

	const pad = ( n ) => String( Math.floor( n ) ).padStart( 2, '0' );
	if ( total < 3600 ) {
		return `${ pad( seconds / 60 ) }:${ pad( seconds % 60 ) }`;
	}

	return `${ pad( seconds / 3600 ) }:${ pad( ( seconds % 3600 ) / 60 ) }:${ pad( seconds % 60 ) }`;
};

/**
 * Page numbers to show, same as old (end size 1, mid size 1).
 *
 * @param {number} current
 * @param {number} total
 * @param {number} endSize
 * @param {number} midSize
 * @return {Array} numbers and 'dots'
 */
const getPageNumbers = ( current, total, endSize = 1, midSize = 1 ) => {
	const pages = [];
	let dots = false;

	for ( let number = 1; number <= total; number++ ) {
		if (
			number === current ||
			number <= endSize ||
			( number >= current - midSize && number <= current + midSize ) ||
			number > total - endSize
		) {
			pages.push( number );
			dots = true;
		} else if ( dots ) {
			pages.push( 'dots' );
			dots = false;
		}
	}

	return pages;
};

/**
 * Replace %1$d, %2$d... (and %d in order) of text.
 *
 * @param {string} text
 * @param {Array}  args
 * @return {string} text
 */
const replacePlaceholders = ( text, ...args ) => {
	let i = 0;
	return text.replace( /%(?:(\d+)\$)?[ds]/g, ( match, position ) => {
		const value = position ? args[ position - 1 ] : args[ i++ ];
		return value === undefined ? match : String( value );
	} );
};

export class UserQuiz {
	static selectors = {
		elWrap: '#learn-press-user-quiz',
		elDoing: '.lp-quiz-doing',
		elReview: '.lp-quiz-review',
		elResultWrap: '.lp-quiz-result-wrap',
		elQuestion: '.question[data-id]',
		elBtnStart: '.lp-button.start',
		elBtnRetake: '.retake-quiz',
		elBtnFinish: '#button-submit-quiz, .lp-button.submit-quiz',
		elBtnFinishStatusBar: '#button-submit-quiz',
		elBtnCheck: '.lp-button.instant-check',
		elBtnHint: '.btn-show-hint',
		elBtnReview: '.lp-button.review-quiz',
		elBtnBackResult: '.lp-button.back-quiz',
		elBtnPage: '.questions-pagination .page-numbers[data-page]',
		elPagination: '.questions-pagination',
		elQuestionsIndex: '.questions-index',
		elCountdown: '.countdown',
		elInputTimeSpend: 'input[name="lp-quiz-time-spend"]',
		elAnswerInput: '.option-check, .lp-fib-input input, .option-sort',
		elCheckInfo: '.instant-check__info',
		elHintContent: '.question-hint-content',
		elSortList: '.lp-sorting-choice',
		elSortListEditable: '.lp-sorting-choice:not(.lp-sorting-choice--readonly)',
		elHeader: '.popup-header__inner',
		elFormFinishCourse: 'form.form-button-finish-course',
		elBtnFinishCourseLoaded: 'form.form-button-finish-course[data-user-quiz-loaded] button',
	};

	constructor() {
		this.elWrap = null;
		this.quizId = 0;
		this.courseId = 0;
		this.isRequesting = false;
		this.userId = 0;
		this.isGuest = false;
		this.submitAfterRequest = false;
		this.i18n = {};
		this.timer = null;
		this.guest = new UserQuizGuest( this );
	}

	init() {
		lpUtils.lpOnElementReady( UserQuiz.selectors.elWrap, ( elWrap ) => {
			this.elWrap = elWrap;
			this.quizId = parseInt( elWrap.dataset.quizId ) || 0;
			this.courseId = parseInt( elWrap.dataset.courseId ) || 0;
			this.userId = parseInt( window.lpData?.user_id ) || 0;
			this.isGuest = 0 === this.userId;
			// Localized by LP_Assets::_get_script_data() (handle lp-user-quiz).
			this.i18n = window.lpUserQuizSettings?.i18n || {};

			this.onScreenRendered();
			this.events();
			this.guest.maybeRestoreCompleted();
			this.guest.maybeResume();
		} );
	}

	events() {
		if ( UserQuiz._loadedEvents ) {
			return;
		}
		UserQuiz._loadedEvents = true;

		const handler = ( selector, callBack ) => ( { selector, class: this, callBack } );

		lpUtils.eventHandlers( 'click', [
			handler( UserQuiz.selectors.elBtnStart, this.startQuiz.name ),
			handler( UserQuiz.selectors.elBtnFinish, this.submitQuiz.name ),
			handler( UserQuiz.selectors.elBtnCheck, this.checkQuestion.name ),
			handler( UserQuiz.selectors.elBtnHint, this.toggleHint.name ),
			handler( UserQuiz.selectors.elBtnReview, this.showReview.name ),
			handler( UserQuiz.selectors.elBtnBackResult, this.showResult.name ),
			handler( UserQuiz.selectors.elBtnPage, this.clickPage.name ),
			handler( UserQuiz.selectors.elBtnFinishCourseLoaded, this.finishCourse.name ),
		] );

		const answerChanged = handler( UserQuiz.selectors.elAnswerInput, this.answerChanged.name );
		lpUtils.eventHandlers( 'change', [ answerChanged ] );
		lpUtils.eventHandlers( 'input', [ answerChanged ] );
	}

	// ---------------------------------------------------------------------
	// Actions
	// ---------------------------------------------------------------------

	/**
	 * Start quiz, or retake quiz completed (button has class retake-quiz, need confirm).
	 *
	 * @param {Object} args
	 */
	startQuiz( args ) {
		const elBtn = args.target.closest( UserQuiz.selectors.elBtnStart );
		if ( ! elBtn || ! this.elWrap.contains( elBtn ) ) {
			return;
		}

		const isRetake = elBtn.matches( UserQuiz.selectors.elBtnRetake );
		this.confirm( elBtn, () => this.doStart( elBtn, isRetake ) );
	}

	/**
	 * @param {Element} elBtn    button has data-send
	 * @param {boolean} isRetake
	 * @param {boolean} isResume guest reload page when doing quiz
	 */
	doStart( elBtn, isRetake = false, isResume = false ) {
		this.request( elBtn, ( data ) => {
			this.guest.onStart( isRetake, isResume );

			if ( ! isResume ) {
				this.clearStorage();
			}

			this.renderScreen( data.content, data.status );
		} );
	}

	/**
	 * Submit quiz with answers and time spend.
	 *
	 * @param {Object} args
	 */
	submitQuiz( args ) {
		const elBtn = args.target.closest( UserQuiz.selectors.elBtnFinish );
		if ( ! elBtn || ! this.elWrap.contains( elBtn ) ) {
			return;
		}

		this.confirm( elBtn, () => this.doSubmit( elBtn ) );
	}

	/**
	 * Submit quiz without confirm (call when time is up).
	 *
	 * @param {Element} elBtn button has data-send
	 */
	doSubmit( elBtn ) {
		const elTimeSpend = this.elWrap.querySelector( UserQuiz.selectors.elInputTimeSpend );

		this.request(
			elBtn,
			( data ) => {
				this.stopTimer();
				this.clearStorage();
				this.guest.onSubmit( data );
				this.renderScreen( data.content, data.status );
				this.afterSubmit( data );
			},
			{
				answered: this.getAnswered(),
				time_spend: elTimeSpend ? parseInt( elTimeSpend.value ) || 0 : 0,
			}
		);
	}

	/**
	 * Instant check a question, replace the question by HTML returned.
	 *
	 * @param {Object} args
	 */
	checkQuestion( args ) {
		const elBtn = args.target.closest( UserQuiz.selectors.elBtnCheck );
		const elQuestion = elBtn?.closest( UserQuiz.selectors.elQuestion );
		if ( ! elQuestion || ! this.elWrap.contains( elQuestion ) ) {
			return;
		}

		// Checked once, cannot check again (same as server: already_checked).
		if ( elQuestion.querySelector( '.question-response' )
			|| this.guest.getCheckedIds().includes( parseInt( elQuestion.dataset.id ) ) ) {
			return;
		}

		const answer = getQuestionAnswer( elQuestion );
		if ( answer === null ) {
			return;
		}

		this.request(
			elBtn,
			( data ) => {
				const elTemp = document.createElement( 'div' );
				elTemp.innerHTML = data.content;
				const elQuestionNew = elTemp.firstElementChild;
				if ( ! elQuestionNew ) {
					return;
				}

				elQuestion.replaceWith( elQuestionNew );
				this.updateEarnedPoint( data );
				this.saveAnswers();
				this.guest.saveChecked( elQuestionNew.dataset.id || elQuestion.dataset.id, answer, data );

				if ( window.wp?.hooks ) {
					window.wp.hooks.doAction( 'lp-user-quiz/question-checked', elQuestionNew, data, this.quizId );
				}
			},
			{ answered: answer }
		);
	}

	/**
	 * Add point of question checked to Earned Point, minus point when answer wrong and negative marking.
	 * Not less than 0, same as result saved on server.
	 *
	 * @param {Object} data response of check question { mark, minus }
	 */
	updateEarnedPoint( data ) {
		const elPoint = this.elWrap.querySelector( '.current-point__value' );
		if ( ! elPoint ) {
			return;
		}

		const point = Math.max( 0, ( parseFloat( elPoint.textContent ) || 0 ) + ( parseFloat( data.mark ) || 0 ) - ( parseFloat( data.minus ) || 0 ) );
		elPoint.textContent = Number.isInteger( point ) ? String( point ) : point.toFixed( 2 );
	}

	/**
	 * Show / hide hint of question.
	 *
	 * @param {Object} args
	 */
	toggleHint( args ) {
		const elQuestion = args.target.closest( UserQuiz.selectors.elQuestion );
		const elHint = elQuestion?.querySelector( UserQuiz.selectors.elHintContent );
		if ( ! elHint ) {
			return;
		}

		elHint.style.display = elHint.style.display === 'none' ? '' : 'none';
	}

	showReview( args ) {
		this.toggleReview( args, true );
	}

	showResult( args ) {
		this.toggleReview( args, false );
	}

	/**
	 * Review questions after completed (rendered hidden by PHP), no request.
	 *
	 * @param {Object}  args
	 * @param {boolean} isReview
	 */
	toggleReview( args, isReview ) {
		if ( ! this.elWrap.contains( args.target ) ) {
			return;
		}

		const elResultWrap = this.elWrap.querySelector( UserQuiz.selectors.elResultWrap );
		const elReview = this.elWrap.querySelector( UserQuiz.selectors.elReview );
		if ( ! elResultWrap || ! elReview ) {
			return;
		}

		elResultWrap.style.display = isReview ? 'none' : '';
		elReview.style.display = isReview ? '' : 'none';
		if ( isReview ) {
			this.showPage( elReview, 1 );
		}

		this.scrollToTop();
	}

	// ---------------------------------------------------------------------
	// Pagination
	// ---------------------------------------------------------------------

	clickPage( args ) {
		const elBtn = args.target.closest( UserQuiz.selectors.elBtnPage );
		const elScope = elBtn?.closest( `${ UserQuiz.selectors.elDoing }, ${ UserQuiz.selectors.elReview }` );
		if ( ! elScope ) {
			return;
		}

		this.showPage( elScope, parseInt( elBtn.dataset.page ) || 1 );
		this.scrollToTop();
	}

	/**
	 * Show questions of page, build page links, update questions index, finish button.
	 *
	 * @param {Element} elScope .lp-quiz-doing or .lp-quiz-review
	 * @param {number}  page
	 */
	showPage( elScope, page ) {
		const numPages = parseInt( elScope.dataset.numPages ) || 1;
		const perPage = parseInt( elScope.dataset.perPage ) || 0;
		const total = parseInt( elScope.dataset.total ) || 0;
		page = Math.min( Math.max( 1, page ), numPages );
		elScope.dataset.currentPage = String( page );

		elScope.querySelectorAll( UserQuiz.selectors.elQuestion ).forEach( ( elQuestion ) => {
			elQuestion.style.display = ( parseInt( elQuestion.dataset.page ) || 1 ) === page ? '' : 'none';
		} );

		const elPagination = elScope.querySelector( UserQuiz.selectors.elPagination );
		if ( elPagination ) {
			elPagination.innerHTML = this.htmlPageNumbers( page, numPages );
		}

		const elIndex = elScope.querySelector( UserQuiz.selectors.elQuestionsIndex );
		if ( elIndex ) {
			const start = perPage > 0 ? ( ( page - 1 ) * perPage ) + 1 : 1;
			const end = perPage > 0 ? Math.min( start + perPage - 1, total ) : total;
			elIndex.innerHTML = end > start
				? replacePlaceholders( this.i18n.question_index_range || 'Question <span>%1$d to %2$d of %3$d</span>', start, end, total )
				: replacePlaceholders( this.i18n.question_index || 'Question <span>%1$d of %2$d</span>', start, total );
		}

		const isLast = page === numPages;
		elScope.querySelectorAll( '.quiz-buttons' ).forEach( ( elButtons ) => {
			elButtons.classList.toggle( 'is-first', page === 1 );
			elButtons.classList.toggle( 'is-last', isLast );
		} );

		// Finish button below questions shows on last page.
		elScope.querySelectorAll( '.quiz-buttons .lp-button.submit-quiz' ).forEach( ( elBtn ) => {
			elBtn.hidden = ! isLast;
		} );

		if ( elScope.matches( UserQuiz.selectors.elDoing ) ) {
			this.storageSet( this.getStorageKey( 'Page' ), String( page ) );
		}
	}

	/**
	 * HTML page links, same markup as old.
	 *
	 * @param {number} current
	 * @param {number} numPages
	 * @return {string} html
	 */
	htmlPageNumbers( current, numPages ) {
		if ( numPages <= 1 ) {
			return '';
		}

		let html = '<div class="nav-links">';
		if ( current > 1 ) {
			html += `<button type="button" class="page-numbers prev" data-type="question-navx" data-page="${ current - 1 }">${ this.i18n.prev || 'Prev' }</button>`;
		}

		getPageNumbers( current, numPages ).forEach( ( number ) => {
			if ( number === 'dots' ) {
				html += '<span class="page-numbers dots">&hellip;</span>';
			} else if ( number === current ) {
				html += `<span class="page-numbers current">${ number }</span>`;
			} else {
				html += `<button type="button" class="page-numbers" data-page="${ number }">${ number }</button>`;
			}
		} );

		if ( current < numPages ) {
			html += `<button type="button" class="page-numbers next" data-type="question-navx" data-page="${ current + 1 }">${ this.i18n.next || 'Next' }</button>`;
		}

		return html + '</div>';
	}

	scrollToTop() {
		if ( typeof this.elWrap.scrollIntoView === 'function' ) {
			this.elWrap.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}

	// ---------------------------------------------------------------------
	// Timer
	// ---------------------------------------------------------------------

	/**
	 * Count down from remaining time when quiz has duration (auto submit at 0), else count up.
	 * Guest: time from start saved on localStorage, keep when reload page.
	 */
	startTimer() {
		this.stopTimer();

		const elCountdown = this.elWrap.querySelector( `${ UserQuiz.selectors.elDoing } ${ UserQuiz.selectors.elCountdown }` );
		if ( ! elCountdown ) {
			return;
		}

		const elTime = elCountdown.querySelector( 'span' );
		const elTimeSpend = elCountdown.querySelector( UserQuiz.selectors.elInputTimeSpend );
		const duration = parseInt( elCountdown.dataset.duration ) || 0;
		let remainingStart = parseInt( elCountdown.dataset.remaining ) || 0;
		let timeSpendStart = parseInt( elCountdown.dataset.timeSpend ) || 0;

		const guestTimeSpend = this.guest.getTimeSpend();
		if ( guestTimeSpend !== null ) {
			timeSpendStart = guestTimeSpend;
			remainingStart = Math.max( 0, duration - timeSpendStart );
		}

		const startedAt = Date.now();
		const tick = () => {
			const elapsed = Math.floor( ( Date.now() - startedAt ) / 1000 );
			const timeSpend = timeSpendStart + elapsed;
			if ( elTimeSpend ) {
				elTimeSpend.value = String( timeSpend );
			}

			if ( duration <= 0 ) {
				elTime.textContent = formatTimer( timeSpend, timeSpend );
				return true;
			}

			let remaining = Math.max( 0, remainingStart - elapsed );
			if ( window.wp?.hooks ) {
				remaining = window.wp.hooks.applyFilters( 'lp-user-quiz/remaining-time', remaining, this.elWrap );
			}
			elTime.textContent = formatTimer( remaining, duration );

			if ( remaining <= 0 ) {
				this.stopTimer();
				this.timeUp();
				return false;
			}

			return true;
		};

		// Time is already up when page load: submitted, not start timer.
		if ( tick() && this.timer === null && this.elWrap.querySelector( UserQuiz.selectors.elDoing ) ) {
			this.timer = window.setInterval( tick, 1000 );
		}
	}

	stopTimer() {
		if ( this.timer !== null ) {
			window.clearInterval( this.timer );
			this.timer = null;
		}
	}

	/**
	 * Time is up, submit without confirm, same as old.
	 */
	timeUp() {
		// Another request is running (instant check...), submit when it is completed.
		if ( this.isRequesting ) {
			this.submitAfterRequest = true;
			return;
		}

		const elBtn = this.elWrap.querySelector( UserQuiz.selectors.elBtnFinishStatusBar ) ||
			this.elWrap.querySelector( UserQuiz.selectors.elBtnFinish );
		if ( elBtn ) {
			this.doSubmit( elBtn );
		}
	}

	// ---------------------------------------------------------------------
	// Screen
	// ---------------------------------------------------------------------

	/**
	 * Replace screen of quiz (doing, completed) by HTML returned.
	 *
	 * @param {string} content
	 * @param {string} status
	 */
	renderScreen( content, status ) {
		this.stopTimer();
		this.elWrap.innerHTML = content;
		this.elWrap.dataset.status = status || '';
		this.onScreenRendered();
	}

	/**
	 * Run after screen rendered (page load, start, retake, submit).
	 */
	onScreenRendered() {
		const elDoing = this.elWrap.querySelector( UserQuiz.selectors.elDoing );
		if ( elDoing ) {
			this.guest.restoreChecked();
			this.restoreAnswers();
			this.showPage( elDoing, parseInt( this.storageGet( this.getStorageKey( 'Page' ) ) ) || 1 );
			this.initSortable();
			elDoing.querySelectorAll( UserQuiz.selectors.elQuestion ).forEach( ( elQuestion ) => this.updateCheckInfo( elQuestion ) );
			this.startTimer();
		}

		this.guest.updateRetake();

		if ( window.wp?.hooks ) {
			// Hook old of React app, used by add-ons.
			window.wp.hooks.doAction( 'lp-js-quiz-answer', this.getAnswered(), this.elWrap.dataset.status );
			window.wp.hooks.doAction( 'lp-user-quiz/screen-rendered', this.elWrap, this.elWrap.dataset.status );
		}
	}

	/**
	 * Drag & drop options of sorting choice.
	 */
	initSortable() {
		this.elWrap.querySelectorAll( `${ UserQuiz.selectors.elDoing } ${ UserQuiz.selectors.elSortListEditable }` ).forEach( ( elList ) => {
			if ( elList.dataset.sortable ) {
				return;
			}

			elList.dataset.sortable = '1';
			new Sortable( elList, {
				animation: 150,
				onEnd: () => {
					elList.dataset.sorted = '1';
					this.saveAnswers();
					this.updateCheckInfo( elList.closest( UserQuiz.selectors.elQuestion ) );
				},
			} );
		} );
	}

	/**
	 * Hide info "need to answer" of button check when question answered.
	 *
	 * @param {Element} elQuestion
	 */
	updateCheckInfo( elQuestion ) {
		const elInfo = elQuestion?.querySelector( UserQuiz.selectors.elCheckInfo );
		if ( elInfo ) {
			elInfo.hidden = getQuestionAnswer( elQuestion ) !== null;
		}
	}

	/**
	 * After submit: update curriculum item, items progress, hooks.
	 *
	 * @param {Object} data response of submit
	 */
	afterSubmit( data ) {
		const graduation = data.graduation || '';
		document.querySelectorAll( `.course-curriculum .course-item.course-item-${ this.quizId }` ).forEach( ( elItem ) => {
			elItem.classList.remove( 'status-started', 'passed', 'failed' );
			elItem.classList.add( 'has-status', 'status-completed' );
			if ( graduation ) {
				elItem.classList.add( graduation );
			}
		} );

		this.updateItemsProgress();
		this.showFinishCourse( data.html_finish_course );
		window.onbeforeunload = null;

		if ( window.wp?.hooks ) {
			window.wp.hooks.doAction( 'lp-user-quiz/submitted', data, this.quizId );
		}
	}

	/**
	 * Update number items completed on header, same as old.
	 */
	updateItemsProgress() {
		const elProgress = document.querySelector( '#popup-header .items-progress' );
		const elCompleted = elProgress?.querySelector( '.items-completed' );
		if ( ! elCompleted ) {
			return;
		}

		const totalItems = parseInt( elProgress.dataset.totalItems ) || 0;
		const completed = document.querySelectorAll( '#popup-sidebar .course-curriculum .course-item__status .completed' ).length;
		elCompleted.textContent = String( completed );

		const elActive = elProgress.querySelector( '.learn-press-progress__active' );
		if ( elActive && totalItems > 0 ) {
			elActive.style.left = `-${ 100 - ( completed * 100 / totalItems ) }%`;
		}
	}

	/**
	 * Show button "Finish course" on header when course can finish after submit (page not reload).
	 * HTML from server (UserCourseTemplate::html_btn_finish), same purpose as old React app
	 * (single-curriculum/components/items-progress.js getResponse).
	 *
	 * @param {string} html
	 */
	showFinishCourse( html ) {
		const elHeader = document.querySelector( UserQuiz.selectors.elHeader );
		if ( ! html || typeof html !== 'string' || ! elHeader ||
			elHeader.querySelector( UserQuiz.selectors.elFormFinishCourse ) ) {
			return;
		}

		elHeader.insertAdjacentHTML( 'beforeend', html );
		elHeader.classList.add( 'can-finish-course' );
		// Mark form added here, confirm by SweetAlert (button rendered by page load is handled by old code).
		elHeader.querySelectorAll( UserQuiz.selectors.elFormFinishCourse ).forEach( ( elForm ) => {
			elForm.dataset.userQuizLoaded = '1';
		} );
	}

	/**
	 * Confirm finish course (button added by showFinishCourse) when enable option popup confirm finish,
	 * same as old show-lp-overlay-complete-item.js but use SweetAlert.
	 *
	 * @param {Object} args
	 */
	finishCourse( args ) {
		const elForm = args.target.closest( UserQuiz.selectors.elFormFinishCourse );
		if ( ! elForm || window.lpGlobalSettings?.option_enable_popup_confirm_finish !== 'yes' ) {
			return;
		}

		args.e.preventDefault();

		SweetAlert.fire( {
			// Form of UserCourseTemplate::html_btn_finish has not data-title, use text of button.
			title: elForm.dataset.title || elForm.querySelector( 'button' )?.textContent.trim() || '',
			text: elForm.dataset.confirm,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: this.i18n.cancel || 'Cancel',
			confirmButtonText: this.i18n.yes || 'Yes',
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				elForm.submit();
			}
		} );
	}

	// ---------------------------------------------------------------------
	// Request
	// ---------------------------------------------------------------------

	/**
	 * Show confirm (SweetAlert) when button has data-title, same as admin edit-quiz.js.
	 *
	 * @param {Element}  elBtn
	 * @param {Function} callBack called when confirmed or not need confirm
	 */
	confirm( elBtn, callBack ) {
		if ( ! elBtn.dataset.title ) {
			callBack();
			return;
		}

		SweetAlert.fire( {
			title: elBtn.dataset.title,
			text: elBtn.dataset.content,
			icon: 'warning',
			showCloseButton: true,
			showCancelButton: true,
			cancelButtonText: this.i18n.cancel || 'Cancel',
			confirmButtonText: this.i18n.yes || 'Yes',
			reverseButtons: true,
		} ).then( ( result ) => {
			if ( result.isConfirmed ) {
				callBack();
			}
		} );
	}

	/**
	 * Call AJAX with data-send of button.
	 *
	 * @param {Element}  elBtn     button has data-send
	 * @param {Function} onSuccess ( data, message )
	 * @param {Object}   extraData merge to data-send
	 */
	request( elBtn, onSuccess, extraData = {} ) {
		const ajaxHandle = window.lpAJAXG;
		if ( this.isRequesting || ! ajaxHandle || typeof ajaxHandle.fetchAJAX !== 'function' ) {
			return;
		}

		let dataSend;
		try {
			dataSend = JSON.parse( elBtn.dataset.send || '{}' );
		} catch ( e ) {
			return;
		}

		this.isRequesting = true;
		elBtn.disabled = true;
		lpUtils.lpSetLoadingEl( elBtn, 1 );

		ajaxHandle.fetchAJAX(
			{ ...dataSend, ...extraData },
			{
				success: ( response ) => {
					if ( response?.status !== 'success' ) {
						lpToastify.show( response?.message || this.i18n.error || 'Error', 'error' );
						return;
					}

					onSuccess( response.data || {}, response.message || '' );
				},
				error: ( error ) => {
					lpToastify.show( error?.message || this.i18n.error || 'Error', 'error' );
				},
				completed: () => {
					this.isRequesting = false;
					// Button can be removed when HTML replaced.
					if ( elBtn.isConnected ) {
						elBtn.disabled = false;
						lpUtils.lpSetLoadingEl( elBtn, 0 );
					}

					if ( this.submitAfterRequest ) {
						this.submitAfterRequest = false;
						this.timeUp();
					}
				},
			}
		);
	}

	// ---------------------------------------------------------------------
	// Answers
	// ---------------------------------------------------------------------

	/**
	 * @param {Object} args
	 */
	answerChanged( args ) {
		this.saveAnswers();
		this.updateCheckInfo( args?.target?.closest( UserQuiz.selectors.elQuestion ) );
	}

	/**
	 * Answers of questions doing.
	 *
	 * @return {Object} { question_id: answer }
	 */
	getAnswered() {
		const answered = {};
		const elDoing = this.elWrap.querySelector( UserQuiz.selectors.elDoing );
		if ( ! elDoing ) {
			return answered;
		}

		elDoing.querySelectorAll( UserQuiz.selectors.elQuestion ).forEach( ( elQuestion ) => {
			let answer = getQuestionAnswer( elQuestion );
			// Question checked (guest): inputs may be locked, use answer saved when checked.
			if ( answer === null ) {
				answer = this.guest.getCheckedAnswer( elQuestion.dataset.id );
			}

			if ( answer !== null ) {
				answered[ elQuestion.dataset.id ] = answer;
			}
		} );

		return answered;
	}

	saveAnswers() {
		if ( ! this.elWrap?.querySelector( UserQuiz.selectors.elDoing ) ) {
			return;
		}

		this.storageSet( this.getStorageKey(), JSON.stringify( this.getAnswered() ) );
	}

	restoreAnswers() {
		let answered = {};
		try {
			answered = JSON.parse( this.storageGet( this.getStorageKey() ) || '{}' ) || {};
		} catch ( e ) {
			return;
		}

		this.elWrap.querySelectorAll( `${ UserQuiz.selectors.elDoing } ${ UserQuiz.selectors.elQuestion }` ).forEach( ( elQuestion ) => {
			// Question checked has answer from server.
			if ( elQuestion.querySelector( '.question-response' ) ) {
				return;
			}

			setQuestionAnswer( elQuestion, answered[ elQuestion.dataset.id ] );
		} );
	}

	clearStorage() {
		[ 'Answered', 'Page' ].forEach( ( suffix ) => this.storageRemove( this.getStorageKey( suffix ) ) );
		this.guest.clear();
	}

	/**
	 * Key has user id and course id: users on the same browser, a quiz in many courses do not share data.
	 *
	 * @param {string} suffix 'Answered' (default), 'Page', 'Guest', 'Guest_Retaken', ... see guest.js
	 * @return {string} key
	 */
	getStorageKey( suffix = 'Answered' ) {
		return `LP_Quiz_${ this.userId }_${ this.courseId }_${ this.quizId }_${ suffix }`;
	}

	storageGet( key ) {
		try {
			return window.localStorage.getItem( key );
		} catch ( e ) {
			return null;
		}
	}

	storageSet( key, value ) {
		try {
			window.localStorage.setItem( key, value );
		} catch ( e ) {}
	}

	storageRemove( key ) {
		try {
			window.localStorage.removeItem( key );
		} catch ( e ) {}
	}
}

const userQuiz = new UserQuiz();
userQuiz.init();
