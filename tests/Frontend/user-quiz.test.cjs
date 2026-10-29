// Run: node --test tests/Frontend/user-quiz.test.cjs
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

const read = ( file ) => fs.readFileSync( path.resolve( __dirname, '../../', file ), 'utf8' );
const withoutImports = ( source ) => source.replace( /import[\s\S]*?from '[^']+';/g, '' );

const dataSend = ( data ) => JSON.stringify( { quiz_id: 20, course_id: 30, ...data } ).replace( /"/g, '&quot;' );
const submitSend = dataSend( { id_url: 'user-submit-quiz', action: 'user_submit_quiz' } );

// Markup same as UserQuizTemplate::html_doing_quiz (shortened), 1 question per page.
const doingHtml = ( timer = 'data-duration="600" data-remaining="5" data-time-spend="595"' ) => `
	<div class="lp-quiz-doing" data-per-page="1" data-total="4" data-num-pages="4">
		<div class="quiz-status"><div>
			<div class="questions-index">Question <span>1 of 4</span></div>
			<div class="current-point">Earned Point: <span class="current-point__value">1</span></div>
			<div><div class="submit-quiz"><button type="button" class="lp-button" id="button-submit-quiz" data-title="Finish Quiz" data-content="Are you sure to submit the quiz?" data-send="${ submitSend }">Finish Quiz</button></div>
			<div class="countdown" ${ timer }><i class="lp-icon-clock"></i><span>00:05</span><input type="hidden" name="lp-quiz-time-spend" value="595"/></div></div>
		</div></div>
		<div class="quiz-questions">
			<div class="question question-single_choice" data-id="101" data-page="1">
				<h4 class="question-title"><span>Q1</span><button type="button" class="btn-show-hint"><span>Hint</span></button></h4>
				<ul class="answer-options">
					<li class="answer-option"><input type="radio" class="option-check" name="learn-press-question-101" value="a"/></li>
					<li class="answer-option"><input type="radio" class="option-check" name="learn-press-question-101" value="b"/></li>
				</ul>
				<div class="question-hint-content" style="display: none;">The hint</div>
				<button type="button" class="lp-button instant-check" data-send="${ dataSend( { id_url: 'user-check-question', action: 'user_check_question', question_id: 101 } ) }">Check answers<div class="instant-check__info">Need answer</div></button>
			</div>
			<div class="question question-multi_choice" data-id="102" data-page="2" style="display: none;">
				<input type="checkbox" class="option-check" name="learn-press-question-102" value="a"/>
				<input type="checkbox" class="option-check" name="learn-press-question-102" value="b"/>
				<input type="checkbox" class="option-check" name="learn-press-question-102" value="c"/>
			</div>
			<div class="question question-fill_in_blanks" data-id="103" data-page="3" style="display: none;">
				<div class="lp-fib-content"><div>Hello <div class="lp-fib-input"><input type="text" data-id="b1" value=""/></div> and <div class="lp-fib-input"><input type="text" data-id="b2" value=""/></div></div></div>
			</div>
			<div class="question question-sorting_choice" data-id="104" data-page="4" style="display: none;">
				<ul class="answer-options lp-sorting-choice">
					<li class="answer-option"><span class="lp-sorting-choice__handle"></span><input type="hidden" class="option-sort" value="v2"/></li>
					<li class="answer-option"><span class="lp-sorting-choice__handle"></span><input type="hidden" class="option-sort" value="v1"/></li>
				</ul>
			</div>
		</div>
		<div class="quiz-buttons align-center is-first">
			<div class="button-left fixed"><div class="questions-pagination" data-num-pages="4"></div></div>
			<div class="button-right"><button type="button" class="lp-button submit-quiz" hidden data-title="Finish Quiz" data-content="Sure?" data-send="${ submitSend }">Finish Quiz</button></div>
		</div>
	</div>`;

const introHtml = `
	<ul class="quiz-intro"></ul>
	<div class="quiz-buttons"><div class="button-left">
		<button type="button" class="lp-button start" data-send="${ dataSend( { id_url: 'user-start-quiz', action: 'user_start_quiz' } ) }">Start</button>
	</div></div>`;

const completedHtml = ( retakeLabel = 'Retake (2)' ) => `
	<div class="lp-quiz-completed">
		<div class="lp-quiz-result-wrap">
			<div class="quiz-result passed"></div>
			<div class="quiz-buttons">
				<div class="button-left"><button type="button" class="lp-button start retake-quiz" data-retake-max="3" data-title="Retake" data-content="Are you sure you want to retake the quiz?" data-send="${ dataSend( { id_url: 'user-retake-quiz', action: 'user_retake_quiz' } ) }">${ retakeLabel }</button></div>
				<div class="button-right"><button type="button" class="lp-button review-quiz">Review</button></div>
			</div>
		</div>
		<div class="lp-quiz-review" style="display: none;" data-per-page="1" data-total="2" data-num-pages="2">
			<div class="quiz-questions">
				<div class="question" data-id="101" data-page="1"></div>
				<div class="question" data-id="102" data-page="2" style="display: none;"></div>
			</div>
			<div class="quiz-buttons align-center"><div class="button-left fixed"><div class="questions-pagination" data-num-pages="2"></div></div>
			<div class="button-right"><button type="button" class="lp-button back-quiz">Result</button></div></div>
		</div>
	</div>`;

/**
 * @param {Object} t
 * @param {string} screenHtml
 * @param {string} status
 * @param {Object} options { userId, popupConfirmFinish, storage: { key: value }, beforeInit( dom ) }
 */
function setup( t, screenHtml = doingHtml(), status = 'started', options = {} ) {
	const dom = new JSDOM(
		`<div id="popup-header"><div class="popup-header__inner"><div class="items-progress" data-total-items="4"><span class="items-completed">0</span><div class="learn-press-progress__active"></div></div></div></div>
		<div id="popup-sidebar"><ul class="course-curriculum">
			<li class="course-item course-item-20 status-started"><div class="course-item__status"><span class="completed"></span></div></li>
			<li class="course-item course-item-21"><div class="course-item__status"><span class="completed"></span></div></li>
		</ul></div>
		<div id="learn-press-user-quiz" class="lp-user-quiz" data-quiz-id="20" data-course-id="30" data-status="${ status }">${ screenHtml }</div>`,
		{ url: 'https://example.test/course/quiz/' }
	);
	t.after( () => dom.window.close() );

	Object.entries( options.storage || {} ).forEach( ( [ key, value ] ) => dom.window.localStorage.setItem( key, value ) );

	const utils = read( 'assets/src/js/utils.js' );
	const events = utils.slice( utils.indexOf( 'export const eventHandlers =' ), utils.indexOf( '/**\n * Debounce' ) ).replace( 'export const', 'const' );
	const source = withoutImports( read( 'assets/src/js/frontend/user-quiz.js' ) )
		.replace( 'export class', 'class' )
		.split( 'const userQuiz = new UserQuiz();' )[ 0 ];

	const requests = [];
	const toasts = [];
	const confirmations = [];
	const sortables = [];
	const intervals = [];
	const clock = { now: 1000000 };
	let confirmationResult = { isConfirmed: true };
	const context = {
		window: dom.window,
		document: dom.window.document,
		JSON,
		Date: { now: () => clock.now },
		lpUtils: {
			lpOnElementReady: ( selector, callback ) => callback( dom.window.document.querySelector( selector ) ),
			lpSetLoadingEl: ( el, status ) => el.classList.toggle( 'loading', !! status ),
		},
		lpToastify: { show: ( message, status ) => toasts.push( { message, status } ) },
		SweetAlert: {
			fire: ( opts ) => {
				confirmations.push( opts );
				return Promise.resolve( confirmationResult );
			},
		},
		Sortable: function( el, opts ) {
			sortables.push( { el, opts } );
		},
	};
	vm.createContext( context );
	vm.runInContext(
		`${ events }\nlpUtils.eventHandlers = eventHandlers;\n${ source }
		globalThis.UserQuiz = UserQuiz; globalThis.getQuestionAnswer = getQuestionAnswer; globalThis.setQuestionAnswer = setQuestionAnswer;
		globalThis.formatTimer = formatTimer; globalThis.getPageNumbers = getPageNumbers; globalThis.replacePlaceholders = replacePlaceholders;`,
		context
	);
	dom.window.lpAJAXG = { fetchAJAX: ( data, callbacks ) => requests.push( { data, callbacks } ) };
	dom.window.lpUserQuizSettings = { i18n: { yes: 'Yes', cancel: 'Cancel', error: 'Error', prev: 'Prev', next: 'Next' } };
	dom.window.lpData = { user_id: options.userId ?? 7 };
	dom.window.lpGlobalSettings = { option_enable_popup_confirm_finish: options.popupConfirmFinish ?? 'yes' };
	dom.window.setInterval = ( fn ) => {
		intervals.push( fn );
		return intervals.length;
	};
	dom.window.clearInterval = ( id ) => {
		intervals[ id - 1 ] = null;
	};
	if ( options.beforeInit ) {
		options.beforeInit( dom );
	}

	// Events are registered once per class (static flag), reset for each test.
	context.UserQuiz._loadedEvents = false;
	const userQuiz = new context.UserQuiz();
	userQuiz.init();

	const $ = ( selector ) => dom.window.document.querySelector( selector );
	const click = ( el ) => el.dispatchEvent( new dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	const change = ( el ) => el.dispatchEvent( new dom.window.Event( 'change', { bubbles: true } ) );
	const respond = ( index, response ) => {
		requests[ index ].callbacks.success( response );
		requests[ index ].callbacks.completed();
	};
	const tick = ( seconds ) => {
		clock.now += seconds * 1000;
		intervals.filter( Boolean ).forEach( ( fn ) => fn() );
	};
	const visibleQuestions = ( scope = '.lp-quiz-doing' ) => [ ...dom.window.document.querySelectorAll( `${ scope } .question[data-id]` ) ]
		.filter( ( el ) => el.style.display !== 'none' )
		.map( ( el ) => el.dataset.id );

	return {
		dom,
		context,
		userQuiz,
		requests,
		toasts,
		confirmations,
		sortables,
		intervals,
		clock,
		$,
		click,
		change,
		respond,
		tick,
		visibleQuestions,
		setConfirmation: ( result ) => {
			confirmationResult = result;
		},
	};
}

const flush = () => new Promise( ( resolve ) => setImmediate( resolve ) );

// Markup same as UserCourseTemplate::html_btn_finish (shortened).
const finishCourseHtml = '<form class="lp-form form-button form-button-finish-course" method="post" data-confirm="Do you want to finish the course?"><button class="lp-button btn-finish-course"><span>Finish Course</span></button></form>';
const finishFormHtml = finishCourseHtml.replace( ' method="post"', ' method="post" data-user-quiz-loaded="1"' );

// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------

test( 'formatTimer, getPageNumbers, replacePlaceholders', ( t ) => {
	const { context } = setup( t );

	assert.equal( context.formatTimer( 0, 600 ), '00:00' );
	assert.equal( context.formatTimer( 599, 600 ), '09:59' );
	assert.equal( context.formatTimer( 3605, 7200 ), '01:00:05' );

	assert.deepEqual( [ ...context.getPageNumbers( 1, 3 ) ], [ 1, 2, 3 ] );
	assert.deepEqual( [ ...context.getPageNumbers( 5, 10 ) ], [ 1, 'dots', 4, 5, 6, 'dots', 10 ] );
	assert.deepEqual( [ ...context.getPageNumbers( 1, 10 ) ], [ 1, 2, 'dots', 10 ] );

	assert.equal( context.replacePlaceholders( 'Question <span>%1$d to %2$d of %3$d</span>', 1, 5, 10 ), 'Question <span>1 to 5 of 10</span>' );
} );

// -------------------------------------------------------------------------
// Answers
// -------------------------------------------------------------------------

test( 'getQuestionAnswer returns answer by question type', ( t ) => {
	const { context, $ } = setup( t );
	const q = ( id ) => $( `.question[data-id="${ id }"]` );

	assert.equal( context.getQuestionAnswer( q( 101 ) ), null );
	q( 101 ).querySelectorAll( 'input' )[ 1 ].checked = true;
	assert.equal( context.getQuestionAnswer( q( 101 ) ), 'b' );

	assert.equal( context.getQuestionAnswer( q( 102 ) ), null );
	q( 102 ).querySelectorAll( 'input' )[ 0 ].checked = true;
	q( 102 ).querySelectorAll( 'input' )[ 2 ].checked = true;
	assert.deepEqual( [ ...context.getQuestionAnswer( q( 102 ) ) ], [ 'a', 'c' ] );

	assert.equal( context.getQuestionAnswer( q( 103 ) ), null );
	q( 103 ).querySelector( 'input[data-id="b1"]' ).value = '  Hà Nội ';
	assert.deepEqual( { ...context.getQuestionAnswer( q( 103 ) ) }, { b1: '  Hà Nội ' } );

	// Sorting: options are shuffled by server, not an answer until user sorted.
	assert.equal( context.getQuestionAnswer( q( 104 ) ), null );
	q( 104 ).querySelector( '.lp-sorting-choice' ).dataset.sorted = '1';
	assert.deepEqual( [ ...context.getQuestionAnswer( q( 104 ) ) ], [ 'v2', 'v1' ] );
} );

test( 'getQuestionAnswer lets add-on change answer via wp.hooks', ( t ) => {
	const { context, dom, $ } = setup( t );
	dom.window.wp = { hooks: { applyFilters: ( name, answer, el ) => ( name === 'lp-user-quiz/question-answer' ? `addon-${ el.dataset.id }` : answer ) } };

	assert.equal( context.getQuestionAnswer( $( '.question[data-id="101"]' ) ), 'addon-101' );
} );

test( 'setQuestionAnswer restores inputs and sorting order', ( t ) => {
	const { context, $ } = setup( t );
	const q = ( id ) => $( `.question[data-id="${ id }"]` );

	context.setQuestionAnswer( q( 101 ), 'a' );
	context.setQuestionAnswer( q( 102 ), [ 'b', 'c' ] );
	context.setQuestionAnswer( q( 103 ), { b2: 'world' } );
	context.setQuestionAnswer( q( 104 ), [ 'v1', 'v2' ] );

	assert.equal( q( 101 ).querySelector( 'input[value="a"]' ).checked, true );
	assert.deepEqual( [ ...q( 102 ).querySelectorAll( 'input' ) ].map( ( el ) => el.checked ), [ false, true, true ] );
	assert.equal( q( 103 ).querySelector( 'input[data-id="b2"]' ).value, 'world' );
	assert.deepEqual( [ ...q( 104 ).querySelectorAll( 'input.option-sort' ) ].map( ( el ) => el.value ), [ 'v1', 'v2' ] );
	// Restored order is an answer.
	assert.deepEqual( [ ...context.getQuestionAnswer( q( 104 ) ) ], [ 'v1', 'v2' ] );
} );

test( 'answers are saved on change and restored after reload', ( t ) => {
	const { dom, context, $, change } = setup( t );
	const radio = $( '.question[data-id="101"] input[value="b"]' );
	radio.checked = true;
	change( radio );

	const saved = JSON.parse( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Answered' ) );
	assert.equal( saved[ 101 ], 'b' );
	// Sorting question not touched is not saved.
	assert.equal( saved[ 104 ], undefined );

	// Reload: new HTML without answers, new instance restores them.
	radio.checked = false;
	const reloaded = new context.UserQuiz();
	reloaded.init();
	assert.equal( radio.checked, true );
} );

test( 'instant check info hides when question answered', ( t ) => {
	const { $, change } = setup( t );
	const elInfo = $( '.question[data-id="101"] .instant-check__info' );
	assert.equal( elInfo.hidden, false );

	const radio = $( '.question[data-id="101"] input[value="a"]' );
	radio.checked = true;
	change( radio );

	assert.equal( elInfo.hidden, true );
} );

test( 'sorting list readonly (checked) is not sortable', ( t ) => {
	const { sortables } = setup( t, doingHtml().replace( 'class="answer-options lp-sorting-choice"', 'class="answer-options lp-sorting-choice lp-sorting-choice--readonly"' ) );

	assert.equal( sortables.length, 0 );
} );

test( 'style of sorting handle does not depend on attribute draggable (Sortable sets draggable="false" after drag)', () => {
	const scss = read( 'assets/src/scss/frontend/user-quiz.scss' );

	assert.ok( ! /\[draggable/.test( scss ) );
	assert.ok( scss.includes( 'lp-sorting-choice--readonly' ) );
} );

test( 'sorting list is draggable and saves order on drop', ( t ) => {
	const { dom, sortables, $ } = setup( t );

	assert.equal( sortables.length, 1 );
	assert.equal( sortables[ 0 ].el, $( '.lp-sorting-choice' ) );

	const elList = $( '.lp-sorting-choice' );
	elList.appendChild( elList.firstElementChild );
	sortables[ 0 ].opts.onEnd();

	assert.deepEqual( JSON.parse( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Answered' ) )[ 104 ], [ 'v1', 'v2' ] );
} );

// -------------------------------------------------------------------------
// Pagination / hint
// -------------------------------------------------------------------------

test( 'pagination shows one page, builds links and finish button on last page', ( t ) => {
	const { dom, $, click, visibleQuestions } = setup( t );

	assert.deepEqual( visibleQuestions(), [ '101' ] );
	assert.equal( $( '.questions-pagination .page-numbers.current' ).textContent, '1' );
	assert.equal( $( '.questions-pagination .page-numbers.prev' ), null );
	assert.ok( $( '.questions-pagination .page-numbers.next' ) );
	assert.equal( $( '.questions-index' ).innerHTML, 'Question <span>1 of 4</span>' );
	assert.equal( $( '.quiz-buttons .lp-button.submit-quiz' ).hidden, true );

	click( $( '.questions-pagination .page-numbers.next' ) );
	assert.deepEqual( visibleQuestions(), [ '102' ] );
	assert.equal( $( '.questions-index' ).innerHTML, 'Question <span>2 of 4</span>' );
	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Page' ), '2' );
	assert.ok( $( '.questions-pagination .page-numbers.prev' ) );

	click( $( '.questions-pagination .page-numbers[data-page="4"]' ) );
	assert.deepEqual( visibleQuestions(), [ '104' ] );
	assert.equal( $( '.quiz-buttons .lp-button.submit-quiz' ).hidden, false );
	assert.ok( $( '.lp-quiz-doing > .quiz-buttons' ).classList.contains( 'is-last' ) );
	assert.equal( $( '.questions-pagination .page-numbers.next' ), null );
} );

test( 'current page is restored after reload', ( t ) => {
	const { visibleQuestions } = setup( t, doingHtml(), 'started', { storage: { LP_Quiz_7_30_20_Page: '3' } } );

	assert.deepEqual( visibleQuestions(), [ '103' ] );
} );

test( 'hint toggles on click', ( t ) => {
	const { $, click } = setup( t );
	const elHint = $( '.question[data-id="101"] .question-hint-content' );

	click( $( '.btn-show-hint span' ) );
	assert.equal( elHint.style.display, '' );
	click( $( '.btn-show-hint' ) );
	assert.equal( elHint.style.display, 'none' );
} );

// -------------------------------------------------------------------------
// Timer
// -------------------------------------------------------------------------

test( 'timer counts down and auto submits without confirm when time is up', ( t ) => {
	const { $, tick, requests, confirmations } = setup( t );

	assert.equal( $( '.countdown span' ).textContent, '00:05' );
	tick( 2 );
	assert.equal( $( '.countdown span' ).textContent, '00:03' );
	assert.equal( $( 'input[name="lp-quiz-time-spend"]' ).value, '597' );
	assert.equal( requests.length, 0 );

	tick( 3 );
	assert.equal( $( '.countdown span' ).textContent, '00:00' );
	assert.equal( confirmations.length, 0 );
	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, 'user_submit_quiz' );
	assert.equal( requests[ 0 ].data.time_spend, 600 );

	// Timer stopped.
	tick( 5 );
	assert.equal( requests.length, 1 );
} );

test( 'timer counts up when quiz has no duration', ( t ) => {
	const { $, tick, requests } = setup( t, doingHtml( 'data-duration="0" data-remaining="0" data-time-spend="65"' ) );

	assert.equal( $( '.countdown span' ).textContent, '01:05' );
	tick( 10 );
	assert.equal( $( '.countdown span' ).textContent, '01:15' );
	assert.equal( $( 'input[name="lp-quiz-time-spend"]' ).value, '75' );
	assert.equal( requests.length, 0 );
} );

test( 'time up while another request is running: submit when it is completed', ( t ) => {
	const { $, click, tick, requests, respond } = setup( t );

	$( '.question[data-id="101"] input[value="b"]' ).checked = true;
	click( $( '.instant-check' ) );
	assert.equal( requests.length, 1 );

	// Time is up, instant check is still running: submit is not lost.
	tick( 5 );
	assert.equal( requests.length, 1 );

	respond( 0, { status: 'success', data: { content: '<div class="question" data-id="101" data-page="1"></div>', correct: true, mark: 1 } } );
	assert.equal( requests.length, 2 );
	assert.equal( requests[ 1 ].data.action, 'user_submit_quiz' );
} );

test( 'time is already up when page load: submit once, timer is not started', ( t ) => {
	const { requests, intervals } = setup( t, doingHtml( 'data-duration="600" data-remaining="0" data-time-spend="600"' ) );

	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, 'user_submit_quiz' );
	assert.equal( intervals.filter( Boolean ).length, 0 );
} );

test( 'answers of other user or other course are not restored', ( t ) => {
	const otherUser = setup( t, doingHtml(), 'started', { storage: { LP_Quiz_8_30_20_Answered: '{"101":"b"}' } } );
	assert.equal( otherUser.$( '.question[data-id="101"] input[value="b"]' ).checked, false );

	const otherCourse = setup( t, doingHtml(), 'started', { storage: { LP_Quiz_7_31_20_Answered: '{"101":"b"}' } } );
	assert.equal( otherCourse.$( '.question[data-id="101"] input[value="b"]' ).checked, false );

	const sameUser = setup( t, doingHtml(), 'started', { storage: { LP_Quiz_7_30_20_Answered: '{"101":"b"}' } } );
	assert.equal( sameUser.$( '.question[data-id="101"] input[value="b"]' ).checked, true );
} );

// -------------------------------------------------------------------------
// Start / retake
// -------------------------------------------------------------------------

test( 'start quiz sends action without confirm and renders doing screen', ( t ) => {
	const { dom, $, click, requests, confirmations, respond } = setup( t, introHtml, '', {
		storage: { LP_Quiz_7_30_20_Answered: '{"101":"a"}', LP_Quiz_7_30_20_Page: '3' },
	} );

	click( $( '.lp-button.start' ) );

	assert.equal( confirmations.length, 0 );
	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, 'user_start_quiz' );
	assert.equal( requests[ 0 ].data.quiz_id, 20 );
	assert.equal( $( '.lp-button.start' ).disabled, true );

	respond( 0, { status: 'success', data: { content: doingHtml(), status: 'started' } } );

	assert.ok( $( '.lp-quiz-doing' ) );
	assert.equal( $( '#learn-press-user-quiz' ).dataset.status, 'started' );
	// Old answers / page of previous attempt are not restored.
	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Answered' ), null );
	assert.equal( $( '.question[data-id="101"] input[value="a"]' ).checked, false );
	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Page' ), '1' );
	// Logged in user, guest state not saved.
	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Guest' ), null );
} );

test( 'retake needs SweetAlert confirm', async ( t ) => {
	const { $, click, requests, confirmations, setConfirmation } = setup( t, completedHtml(), 'completed' );

	setConfirmation( { isConfirmed: false } );
	click( $( '.retake-quiz' ) );
	await flush();
	assert.equal( confirmations.length, 1 );
	assert.equal( confirmations[ 0 ].title, 'Retake' );
	assert.equal( confirmations[ 0 ].text, 'Are you sure you want to retake the quiz?' );
	assert.equal( confirmations[ 0 ].confirmButtonText, 'Yes' );
	assert.equal( confirmations[ 0 ].cancelButtonText, 'Cancel' );
	assert.equal( requests.length, 0 );

	setConfirmation( { isConfirmed: true } );
	click( $( '.retake-quiz' ) );
	await flush();
	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, 'user_retake_quiz' );
} );

// -------------------------------------------------------------------------
// Review
// -------------------------------------------------------------------------

test( 'review toggles result and review questions without request', ( t ) => {
	const { $, click, requests, visibleQuestions } = setup( t, completedHtml(), 'completed' );

	click( $( '.review-quiz' ) );
	assert.equal( $( '.lp-quiz-result-wrap' ).style.display, 'none' );
	assert.equal( $( '.lp-quiz-review' ).style.display, '' );
	assert.deepEqual( visibleQuestions( '.lp-quiz-review' ), [ '101' ] );
	assert.ok( $( '.lp-quiz-review .questions-pagination .page-numbers.next' ) );

	click( $( '.lp-quiz-review .questions-pagination .page-numbers.next' ) );
	assert.deepEqual( visibleQuestions( '.lp-quiz-review' ), [ '102' ] );

	click( $( '.back-quiz' ) );
	assert.equal( $( '.lp-quiz-result-wrap' ).style.display, '' );
	assert.equal( $( '.lp-quiz-review' ).style.display, 'none' );
	assert.equal( requests.length, 0 );
} );

// -------------------------------------------------------------------------
// Submit
// -------------------------------------------------------------------------

test( 'submit quiz sends answers and time spend after confirm, updates curriculum', async ( t ) => {
	const { dom, $, click, change, requests, confirmations, respond } = setup( t );
	const actions = [];
	dom.window.wp = { hooks: { applyFilters: ( name, value ) => value, doAction: ( name, ...args ) => actions.push( { name, args } ) } };
	const radio = $( '.question[data-id="101"] input[value="a"]' );
	radio.checked = true;
	change( radio );
	$( '.question[data-id="103"] input[data-id="b1"]' ).value = 'world';

	click( $( '#button-submit-quiz' ) );
	await flush();

	assert.equal( confirmations[ 0 ].title, 'Finish Quiz' );
	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, 'user_submit_quiz' );
	assert.equal( requests[ 0 ].data.time_spend, 595 );
	// Sorting question (104) not touched: skipped.
	assert.deepEqual( JSON.parse( JSON.stringify( requests[ 0 ].data.answered ) ), {
		101: 'a',
		103: { b1: 'world' },
	} );

	respond( 0, { status: 'success', data: { content: completedHtml(), status: 'completed', graduation: 'passed', html_finish_course: finishCourseHtml } } );

	assert.ok( $( '.lp-quiz-completed' ) );
	assert.equal( $( '#learn-press-user-quiz' ).dataset.status, 'completed' );
	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Answered' ), null );
	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_7_30_20_Page' ), null );

	const elItem = $( '.course-item-20' );
	assert.ok( elItem.classList.contains( 'status-completed' ) );
	assert.ok( elItem.classList.contains( 'passed' ) );
	assert.ok( ! elItem.classList.contains( 'status-started' ) );
	assert.equal( $( '.items-completed' ).textContent, '2' );
	assert.equal( $( '.learn-press-progress__active' ).style.left, '-50%' );

	// Button "Finish course" from server shows on header.
	assert.equal( $( '.popup-header__inner form.form-button-finish-course' ).dataset.userQuizLoaded, '1' );
	assert.ok( $( '.popup-header__inner' ).classList.contains( 'can-finish-course' ) );

	const submitted = actions.find( ( action ) => action.name === 'lp-user-quiz/submitted' );
	assert.equal( submitted.args[ 1 ], 20 );
	const answerHook = actions.filter( ( action ) => action.name === 'lp-js-quiz-answer' ).pop();
	assert.equal( answerHook.args[ 1 ], 'completed' );
} );

test( 'error response shows toast and keeps screen', async ( t ) => {
	const { $, click, requests, toasts, respond } = setup( t );

	click( $( '.lp-button.submit-quiz' ) );
	await flush();
	respond( 0, { status: 'error', message: 'User quiz is invalid!' } );

	assert.deepEqual( toasts[ 0 ], { message: 'User quiz is invalid!', status: 'error' } );
	assert.ok( $( '.lp-quiz-doing' ) );
	assert.equal( $( '.lp-button.submit-quiz' ).disabled, false );

	// Can request again after completed.
	click( $( '.lp-button.submit-quiz' ) );
	await flush();
	assert.equal( requests.length, 2 );
} );

test( 'only one request at a time', async ( t ) => {
	const { $, click, requests } = setup( t );

	click( $( '.lp-button.submit-quiz' ) );
	await flush();
	click( $( '#button-submit-quiz' ) );
	await flush();

	assert.equal( requests.length, 1 );
} );

// -------------------------------------------------------------------------
// Instant check
// -------------------------------------------------------------------------

test( 'instant check needs answer, then replaces question and updates earned point', ( t ) => {
	const { $, click, requests, respond } = setup( t );

	click( $( '.instant-check' ) );
	assert.equal( requests.length, 0 );

	$( '.question[data-id="101"] input[value="b"]' ).checked = true;
	click( $( '.instant-check' ) );
	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, 'user_check_question' );
	assert.equal( requests[ 0 ].data.question_id, 101 );
	assert.equal( requests[ 0 ].data.answered, 'b' );

	respond( 0, {
		status: 'success',
		data: {
			content: '<div class="question question-single_choice" data-id="101" data-page="1"><div class="question-response correct"></div></div>',
			correct: true,
			mark: 2,
			minus: 0,
		},
	} );

	assert.ok( $( '.question[data-id="101"] .question-response.correct' ) );
	assert.equal( $( '.instant-check' ), null );
	assert.equal( $( '.current-point__value' ).textContent, '3' );
} );

test( 'instant check wrong with negative marking minus earned point, not below 0', ( t ) => {
	const { $, click, respond } = setup( t );

	$( '.question[data-id="101"] input[value="b"]' ).checked = true;
	click( $( '.instant-check' ) );
	respond( 0, {
		status: 'success',
		data: { content: '<div class="question" data-id="101" data-page="1"></div>', correct: false, mark: 0, minus: 2 },
	} );

	assert.equal( $( '.current-point__value' ).textContent, '0' );
} );

// -------------------------------------------------------------------------
// Guest
// -------------------------------------------------------------------------

test( 'guest start saves start time, reload resumes doing quiz with time and answers', ( t ) => {
	const first = setup( t, introHtml, '', { userId: 0 } );
	first.click( first.$( '.lp-button.start' ) );
	first.respond( 0, { status: 'success', data: { content: doingHtml( 'data-duration="600" data-remaining="600" data-time-spend="0"' ), status: 'started' } } );
	const guest = JSON.parse( first.dom.window.localStorage.getItem( 'LP_Quiz_0_30_20_Guest' ) );
	assert.equal( guest.startAt, 1000000 );

	// Reload 100s later: server renders intro (guest not saved), JS starts again.
	const second = setup( t, introHtml, '', {
		userId: 0,
		storage: {
			LP_Quiz_0_30_20_Guest: JSON.stringify( { startAt: 1000000 - 100000 } ),
			LP_Quiz_0_30_20_Answered: '{"101":"b"}',
		},
	} );
	assert.equal( second.requests.length, 1 );
	assert.equal( second.requests[ 0 ].data.action, 'user_start_quiz' );
	second.respond( 0, { status: 'success', data: { content: doingHtml( 'data-duration="600" data-remaining="600" data-time-spend="0"' ), status: 'started' } } );

	assert.equal( second.$( '.countdown span' ).textContent, '08:20' );
	assert.equal( second.$( 'input[name="lp-quiz-time-spend"]' ).value, '100' );
	assert.equal( second.$( '.question[data-id="101"] input[value="b"]' ).checked, true );
	// Start time is kept.
	assert.equal( JSON.parse( second.dom.window.localStorage.getItem( 'LP_Quiz_0_30_20_Guest' ) ).startAt, 900000 );
} );

test( 'guest retake count is kept on browser', async ( t ) => {
	const first = setup( t, completedHtml( 'Retake' ), 'completed', { userId: 0, storage: { LP_Quiz_0_30_20_Guest_Retaken: '1' } } );
	assert.equal( first.$( '.retake-quiz' ).textContent, 'Retake (2)' );

	first.click( first.$( '.retake-quiz' ) );
	await flush();
	first.respond( 0, { status: 'success', data: { content: doingHtml(), status: 'started' } } );
	assert.equal( first.dom.window.localStorage.getItem( 'LP_Quiz_0_30_20_Guest_Retaken' ), '2' );

	const second = setup( t, completedHtml( 'Retake' ), 'completed', { userId: 0, storage: { LP_Quiz_0_30_20_Guest_Retaken: '3' } } );
	assert.equal( second.$( '.retake-quiz' ), null );
} );

test( 'guest submit clears guest state, no button finish course', async ( t ) => {
	const { dom, $, click, respond } = setup( t, doingHtml(), 'started', {
		userId: 0,
		storage: { LP_Quiz_0_30_20_Guest: JSON.stringify( { startAt: 1000000 } ) },
	} );

	click( $( '#button-submit-quiz' ) );
	await flush();
	respond( 0, { status: 'success', data: { content: completedHtml( 'Retake' ), status: 'completed', graduation: 'failed' } } );

	assert.equal( dom.window.localStorage.getItem( 'LP_Quiz_0_30_20_Guest' ), null );
	assert.equal( $( '.popup-header__inner form' ), null );
} );

test( 'button finish course not added when header has it', async ( t ) => {
	const { dom, $, click, respond } = setup( t, doingHtml(), 'started', {
		beforeInit: ( dom ) => {
			dom.window.document.querySelector( '.popup-header__inner' ).insertAdjacentHTML( 'beforeend', '<form class="form-button-finish-course"></form>' );
		},
	} );

	click( $( '#button-submit-quiz' ) );
	await flush();
	respond( 0, { status: 'success', data: { content: completedHtml(), status: 'completed', graduation: 'passed', html_finish_course: finishCourseHtml } } );

	assert.equal( dom.window.document.querySelectorAll( '.popup-header__inner form' ).length, 1 );
	assert.equal( $( '.popup-header__inner form' ).dataset.userQuizLoaded, undefined );
} );

// -------------------------------------------------------------------------
// Finish course button added after submit
// -------------------------------------------------------------------------


test( 'finish course button added confirms by SweetAlert then submits form', async ( t ) => {
	const { dom, $, click, confirmations, setConfirmation } = setup( t, completedHtml(), 'completed', {
		beforeInit: ( dom ) => {
			dom.window.document.querySelector( '.popup-header__inner' ).insertAdjacentHTML( 'beforeend', finishFormHtml );
		},
	} );
	let submitted = 0;
	$( 'form.form-button-finish-course' ).submit = () => submitted++;
	dom.window.document.addEventListener( 'submit', ( e ) => e.preventDefault() );

	setConfirmation( { isConfirmed: false } );
	click( $( '.btn-finish-course span' ) );
	await flush();
	// No data-title, title is text of button.
	assert.equal( confirmations[ 0 ].title, 'Finish Course' );
	assert.equal( confirmations[ 0 ].text, 'Do you want to finish the course?' );
	assert.equal( submitted, 0 );

	setConfirmation( { isConfirmed: true } );
	click( $( '.btn-finish-course' ) );
	await flush();
	assert.equal( submitted, 1 );
} );

test( 'finish course button submits without confirm when option popup confirm off', async ( t ) => {
	const { dom, $, click, confirmations } = setup( t, completedHtml(), 'completed', {
		popupConfirmFinish: 'no',
		beforeInit: ( dom ) => {
			dom.window.document.querySelector( '.popup-header__inner' ).insertAdjacentHTML( 'beforeend', finishFormHtml );
		},
	} );
	let defaultPrevented = null;
	dom.window.document.addEventListener( 'click', ( e ) => {
		defaultPrevented = e.defaultPrevented;
	} );
	dom.window.document.addEventListener( 'submit', ( e ) => e.preventDefault() );

	click( $( '.btn-finish-course' ) );
	await flush();

	assert.equal( confirmations.length, 0 );
	assert.equal( defaultPrevented, false );
} );

test( 'finish course button not added by user quiz is not handled', async ( t ) => {
	const { dom, $, click, confirmations } = setup( t, completedHtml(), 'completed', {
		beforeInit: ( dom ) => {
			dom.window.document.querySelector( '.popup-header__inner' ).insertAdjacentHTML( 'beforeend', finishFormHtml.replace( ' data-user-quiz-loaded="1"', '' ) );
		},
	} );
	dom.window.document.addEventListener( 'submit', ( e ) => e.preventDefault() );

	click( $( '.btn-finish-course' ) );
	await flush();

	assert.equal( confirmations.length, 0 );
} );
