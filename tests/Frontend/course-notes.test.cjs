// Run: node --test tests/Frontend/course-notes.test.cjs
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

const read = ( file ) => fs.readFileSync( path.resolve( __dirname, '../../', file ), 'utf8' );
const withoutImports = ( source ) => source.replace( /import[\s\S]*?from '[^']+';/g, '' );

function setup( t ) {
	const dom = new JSDOM( `
		<div id="lesson">Alpha beta gamma delta</div>
		<div id="lp-notes"><button class="lp-notes__selection-btn" hidden></button>
			<template class="lp-notes__all-template"><div class="lp-notes__all"><div class="lp-load-ajax-element">
				<div class="loading-first">Loading</div>
				<div class="lp-target" data-send='{"args":{"course_id":10,"paged":1,"enableUpdateParamsUrl":false,"enableScrollToView":false},"callback":{"class":"LearnPress\\\\TemplateHooks\\\\Course\\\\CourseNoteTemplate","method":"render_course_notes"}}'></div>
				<div class="loading-after"><div class="lp-loading-change lp-hidden"></div></div>
			</div></div></template>
		</div>
		<div id="popup-right-sidebar">
			<div class="popup-right-sidebar__item" tabindex="0" data-learning-bar-item="notes">
				<template>
					<div class="lp-learning-bar-item-head">Notes <a href="#" class="lp-notes__view-all">View all</a></div>
					<div id="lp-notes-content" class="lp-learning-bar-item-content">
						<button class="lp-notes__add">Add</button>
						<form class="lp-notes__form" hidden>
							<blockquote class="lp-notes__form-quote" hidden></blockquote>
							<textarea class="lp-notes__form-content"></textarea>
							<button class="lp-notes__form-save">Save</button>
							<button type="button" class="lp-notes__form-cancel">Cancel</button>
						</form>
						<div class="lp-notes__list"></div>
						<button class="lp-notes__show-more" hidden>More</button>
						<p class="lp-notes__empty" hidden></p>
						<template class="lp-notes__card-template">
							<article class="lp-notes__card" tabindex="-1">
								<time class="lp-notes__card-date"></time><p class="lp-notes__card-student" hidden></p>
								<p class="lp-notes__card-content"></p>
								<blockquote class="lp-notes__card-quote"></blockquote>
								<p class="lp-notes__card-orphaned"></p>
								<div class="lp-notes__card-actions"><button class="lp-notes__card-edit">Edit</button>
								<button class="lp-notes__card-delete">Delete</button></div>
							</article>
						</template>
					</div>
				</template>
			</div>
			<div class="popup-right-sidebar__item" tabindex="0" data-learning-bar-item="ai-assistant">
				<template>
					<div class="lp-learning-bar-item-head">AI</div>
					<div class="lp-learning-bar-item-content">AI</div>
				</template>
			</div>
		</div>
		<div class="lp-addon-content-bar">
			<div class="lp-addon-content-bar__head">
				<button class="lp-icon-close">Close</button>
				<div class="lp-addon-content-bar__left"></div>
			</div>
			<div class="lp-addon-content-bar__content"></div>
		</div>
	`, { url: 'https://example.test/lesson/' } );
	t.after( () => dom.window.close() );
	dom.window.HTMLElement.prototype.scrollIntoView = function() {};

	// Evaluate the real controllers and delegated events, omitting bundle startup.
	const utils = read( 'assets/src/js/utils.js' );
	const events = utils.slice( utils.indexOf( 'export const eventHandlers =' ), utils.indexOf( '/**\n * Debounce' ) ).replace( 'export const', 'const' );
	const anchors = read( 'assets/src/js/frontend/course-notes-anchor.js' ).replace( /export const /g, 'const ' );
	const notes = withoutImports( read( 'assets/src/js/frontend/course-notes.js' ) ).replace( 'export class', 'class' ).split( 'const courseNotes = new CourseNotes();' )[ 0 ];
	const bar = withoutImports( read( 'assets/src/apps/js/frontend/single-curriculum/learning-content-bar.js' ) ).split( 'const learningContentBar = new LearningContentBar();' )[ 0 ];
	const requests = [];
	const modals = [];
	const confirmations = [];
	let confirmationResult = { isConfirmed: true };
	let activeModal = null;
	const context = {
		window: dom.window,
		document: dom.window.document,
		CustomEvent: dom.window.CustomEvent,
		NodeFilter: dom.window.NodeFilter,
		CSS: { escape: String },
		lpUtils: { debounce: ( fn ) => fn, lpSetLoadingEl() {} },
		lpToastify: { show() {} },
		SweetAlert: { fire: ( options ) => {
			if ( activeModal ) {
				const previous = activeModal;
				previous.popup.remove();
				Promise.resolve().then( () => previous.options.didClose?.() );
			}
			const popup = dom.window.document.createElement( 'div' );
			if ( options.html ) {
				if ( typeof options.html === 'string' ) {
					popup.innerHTML = options.html;
				} else {
					popup.appendChild( options.html );
				}
				modals.push( popup );
			} else {
				confirmations.push( options );
			}
			dom.window.document.body.appendChild( popup );
			activeModal = { popup, options };
			options.didOpen?.( popup );
			return Promise.resolve( confirmationResult );
		} },
	};
	vm.createContext( context );
	vm.runInContext( `${ events }\nlpUtils.eventHandlers = eventHandlers;\n${ anchors }\n${ notes }\n${ bar }\nglobalThis.CourseNotes = CourseNotes; globalThis.LearningContentBar = LearningContentBar;`, context );
	dom.window.lpCourseNotes = {
		canEdit: true,
		contentSelector: '#lesson',
		courseId: 10,
		itemId: 20,
		itemType: 'lp_lesson',
		i18n: { delete: 'Delete', cancel: 'Cancel', deleteConfirm: 'Delete this note?', orphaned: 'Removed', contentEmpty: 'Empty', error: 'Error' },
		notes: Array.from( { length: 25 }, ( _, i ) => ( {
			note_id: i + 1,
			note_type: i < 2 ? 'highlight' : 'text',
			content: `Note ${ i + 1 }`,
			created_at: '2026-10-12 00:00:00',
			highlight_text: i === 0 ? 'Alpha' : 'beta',
			anchor: { scope: 'lesson_content', quote: { exact: i === 0 ? 'Alpha' : 'beta' }, position: { start: i === 0 ? 0 : 6, end: i === 0 ? 5 : 10 } },
		} ) ),
	};
	dom.window.lpAJAXG = { fetchAJAX: ( data, callbacks ) => requests.push( { data, callbacks } ) };
	const widget = new context.CourseNotes();
	new context.LearningContentBar().init();
	widget.init();
	const click = ( el ) => el.dispatchEvent( new dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	const open = ( item ) => click( dom.window.document.querySelector( `.popup-right-sidebar__item[data-learning-bar-item="${ item }"]` ) );
	const close = () => click( dom.window.document.querySelector( '.lp-addon-content-bar__head .lp-icon-close' ) );
	const card = ( id ) => widget.el.list.querySelector( `[data-note-id="${ id }"]` );
	const submit = () => widget.el.form.dispatchEvent( new dom.window.Event( 'submit', { bubbles: true, cancelable: true } ) );
	const marks = () => [ ...widget.contentRoot.querySelectorAll( 'mark' ) ];
	open( 'notes' );
	return { widget, requests, click, open, close, card, submit, marks, dom, context, modals, confirmations, setConfirmation: ( result ) => { confirmationResult = result; } };
}

test( 'pending saves block changing or cancelling the form and recover after errors', ( t ) => {
	const { widget, requests, click, card, submit, close, open } = setup( t );
	click( card( 1 ).querySelector( '.lp-notes__card-edit' ) );
	widget.el.formContent.value = 'Draft A';
	submit();
	assert.equal( widget.isSaving, true );
	assert.equal( widget.el.formContent.disabled, true );
	assert.equal( widget.el.form.querySelector( '.lp-notes__form-cancel' ).disabled, true );
	assert.equal( card( 2 ).querySelector( '.lp-notes__card-edit' ).disabled, true );
	assert.equal( card( 2 ).querySelector( '.lp-notes__card-delete' ).disabled, true );
	assert.equal( widget.el.selectionBtn.disabled, true );
	click( card( 2 ).querySelector( '.lp-notes__card-edit' ) );
	click( widget.el.form.querySelector( '.lp-notes__form-cancel' ) );
	widget.handleAddClick( { e: { preventDefault() {} } } );
	assert.equal( widget.formState.noteId, 1 );
	assert.equal( widget.el.formContent.value, 'Draft A' );
	close();
	open( 'notes' );
	assert.equal( widget.el.form.hidden, false );
	requests[ 0 ].callbacks.error();
	requests[ 0 ].callbacks.completed();
	assert.equal( widget.el.formContent.value, 'Draft A' );
	assert.equal( widget.el.formContent.disabled, false );
	assert.equal( card( 2 ).querySelector( '.lp-notes__card-edit' ).disabled, false );
	submit();
	requests[ 1 ].callbacks.success( { status: 'success', data: { note: { ...widget.notes[ 0 ], content: 'Draft A' } } } );
	requests[ 1 ].callbacks.completed();
	assert.equal( widget.el.form.hidden, true );
	assert.equal( widget.notes[ 0 ].content, 'Draft A' );
	click( card( 2 ).querySelector( '.lp-notes__card-edit' ) );
	assert.equal( widget.formState.noteId, 2 );
} );

test( 'View all uses the shared AJAX loader and numbered pagination without changing the lesson URL', ( t ) => {
	const { widget, click, dom, context, modals } = setup( t );
	const calls = [];
	context.FormData = dom.window.FormData;
	context.lpUtils.lpGetCurrentURLNoParam = () => dom.window.location.href;
	context.lpUtils.lpFetchAPI = ( url, data, callbacks ) => calls.push( { data: JSON.parse( data.body.get( 'data' ) ), callbacks } );
	context.lpUtils.lpShowHideEl = ( el, visible ) => { el.hidden = ! visible; };
	const loader = withoutImports( read( 'assets/src/js/loadAJAX.js' ) );
	const factory = loader.slice( loader.indexOf( 'const lpAJAX = () =>' ), loader.indexOf( 'window.lpAJAXG = lpAJAX();' ) );
	vm.runInContext( `const lpSettings = { lpAjaxUrl: 'https://example.test/ajax', nonce: 'test', user_id: 5, urlParams: {} };\n${ factory }\nglobalThis.sharedAJAX = lpAJAX();`, context );
	dom.window.lpAJAXG = context.sharedAJAX;
	dom.window.document.addEventListener( 'click', ( e ) => context.sharedAJAX.clickNumberPage( e, e.target ) );
	click( widget.el.viewAll );
	assert.equal( modals.length, 1 );
	assert.equal( calls.length, 1 );
	assert.equal( calls[ 0 ].data.args.course_id, 10 );
	assert.equal( calls[ 0 ].data.args.owner_id, undefined );
	assert.equal( calls[ 0 ].data.callback.method, 'render_course_notes' );
	calls[ 0 ].callbacks.success( { status: 'success', data: { content: '<article>Lesson A note</article><a class="page-numbers" href="?paged=2">2</a>' } } );
	calls[ 0 ].callbacks.completed();
	click( modals[ 0 ].querySelector( '.page-numbers' ) );
	assert.equal( calls.length, 2 );
	assert.equal( calls[ 1 ].data.args.paged, 2 );
	calls[ 1 ].callbacks.success( { status: 'success', data: { content: '<article>Lesson B note</article>' } } );
	calls[ 1 ].callbacks.completed();
	assert.equal( modals[ 0 ].querySelector( '.lp-target' ).textContent, 'Lesson B note' );
	assert.equal( dom.window.location.href, 'https://example.test/lesson/' );
	widget.setSaving( true );
	assert.equal( widget.el.viewAll.getAttribute( 'aria-disabled' ), 'true' );
	click( widget.el.viewAll );
	assert.equal( modals.length, 1 );
} );

test( 'switching tools clears highlights while preserving a draft', ( t ) => {
	const { widget, click, card, open, close, marks } = setup( t );
	click( card( 1 ) );
	assert.equal( marks().length, 1 );
	open( 'ai-assistant' );
	assert.equal( marks().length, 0 );
	assert.equal( widget.activeNoteId, null );
	close();
	assert.equal( marks().length, 0 );
	open( 'notes' );
	click( card( 1 ).querySelector( '.lp-notes__card-edit' ) );
	widget.el.formContent.value = 'Unsaved draft';
	open( 'ai-assistant' );
	open( 'notes' );
	assert.equal( widget.el.formContent.value, 'Unsaved draft' );
	click( card( 1 ) );
	widget.setSaving( true );
	close();
	assert.equal( marks().length, 0 );
	assert.equal( widget.el.formContent.value, 'Unsaved draft' );
} );

test( 'deleting another note preserves selection and deleting the selected note clears it', async ( t ) => {
	const { widget, requests, click, card, marks } = setup( t );
	assert.equal( widget.el.list.children.length, 10 );
	click( card( 1 ) );
	click( widget.el.showMore );
	assert.equal( widget.el.list.children.length, 20 );
	click( card( 3 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	assert.equal( requests[ 0 ].data.note_id, 3 );
	requests[ 0 ].callbacks.success( { status: 'success', data: {} } );
	requests[ 0 ].callbacks.completed();
	assert.equal( widget.activeNoteId, '1' );
	assert.equal( card( 1 ).classList.contains( 'is-active' ), true );
	assert.equal( marks()[ 0 ].dataset.noteId, '1' );
	assert.equal( widget.el.list.children.length, 20 );
	click( card( 1 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	requests[ 1 ].callbacks.success( { status: 'success', data: {} } );
	requests[ 1 ].callbacks.completed();
	assert.equal( widget.activeNoteId, null );
	assert.equal( marks().length, 0 );
	assert.equal( widget.el.list.querySelectorAll( '.is-active' ).length, 0 );
	click( widget.el.showMore );
	assert.equal( widget.el.list.children.length, 23 );
	assert.equal( widget.el.showMore.hidden, true );
} );

function popupCards( env, ids ) {
	let reloads = 0;
	env.dom.window.lpAJAXG.getElements = () => { ++reloads; };
	env.click( env.widget.el.viewAll );
	const popup = env.modals[ 0 ];
	popup.querySelector( '.lp-load-ajax-element' ).classList.add( 'loaded' );
	popup.querySelector( '.lp-target' ).innerHTML = `<details class="lp-notes__lesson" open><summary>Lesson</summary><div class="lp-notes__lesson-cards">${ ids.map( ( id ) => `<article class="lp-notes__card" data-note-id="${ id }" data-note-type="text"><p class="lp-notes__card-content">Note ${ id }</p><div class="lp-notes__card-actions"><button class="lp-notes__card-edit">Edit</button><button class="lp-notes__card-delete">Delete</button></div></article>` ).join( '' ) }</div></details>`;
	return { get popup() { return env.widget.popupRoot; }, card: ( id ) => env.widget.popupRoot?.querySelector( `[data-note-id="${ id }"]` ), reloads: () => reloads };
}

test( 'popup edits a note from another lesson without adding it to the sidebar, and retains the draft on failure', ( t ) => {
	const env = setup( t );
	const { widget, click, dom, requests } = env;
	const { card } = popupCards( env, [ 99, 100 ] );
	click( card( 99 ).querySelector( '.lp-notes__card-edit' ) );
	const form = widget.popupForm;
	const input = form.querySelector( '.lp-notes__form-content' );
	input.value = 'Updated other lesson';
	form.dispatchEvent( new dom.window.Event( 'submit', { bubbles: true, cancelable: true } ) );
	assert.equal( requests[ 0 ].data.action, 'lp_note_save' );
	assert.equal( requests[ 0 ].data.note_id, '99' );
	assert.equal( requests[ 0 ].data.course_id, undefined );
	assert.equal( input.disabled, true );
	click( card( 100 ).querySelector( '.lp-notes__card-edit' ) );
	widget.handleFormCancel( { e: { preventDefault() {} }, target: form } );
	assert.equal( widget.popupEditCard.dataset.noteId, '99' );
	requests[ 0 ].callbacks.error();
	requests[ 0 ].callbacks.completed();
	assert.equal( input.value, 'Updated other lesson' );
	assert.equal( input.disabled, false );
	form.dispatchEvent( new dom.window.Event( 'submit', { bubbles: true, cancelable: true } ) );
	requests[ 1 ].callbacks.success( { status: 'success', data: { note: { note_id: 99, content: '<b>Updated</b>', note_type: 'text' } } } );
	requests[ 1 ].callbacks.completed();
	assert.equal( card( 99 ).querySelector( '.lp-notes__card-content' ).textContent, '<b>Updated</b>' );
	assert.equal( card( 99 ).querySelector( 'b' ), null );
	assert.equal( widget.notes.length, 25 );
	assert.equal( widget.getNote( 99 ), null );
	assert.equal( widget.popupForm, null );
	assert.equal( widget.activeNoteId, null );
} );

test( 'popup edits and deletes synchronize current lesson notes and require delete confirmation', async ( t ) => {
	const env = setup( t );
	const { widget, click, dom, requests } = env;
	const { card } = popupCards( env, [ 3, 4 ] );
	click( card( 3 ).querySelector( '.lp-notes__card-edit' ) );
	widget.popupForm.querySelector( '.lp-notes__form-content' ).value = 'Updated current lesson';
	widget.popupForm.dispatchEvent( new dom.window.Event( 'submit', { bubbles: true, cancelable: true } ) );
	requests[ 0 ].callbacks.success( { status: 'success', data: { note: { ...widget.getNote( 3 ), content: 'Updated current lesson' } } } );
	requests[ 0 ].callbacks.completed();
	assert.equal( env.card( 3 ).querySelector( '.lp-notes__card-content' ).textContent, 'Updated current lesson' );
	env.setConfirmation( { isConfirmed: false } );
	click( card( 3 ).querySelector( '.lp-notes__card-delete' ) );
	assert.equal( requests.length, 1 );
	await Promise.resolve();
	assert.equal( requests.length, 1 );
	assert.ok( card( 3 ) );
	assert.equal( env.confirmations[ 0 ].icon, 'warning' );
	assert.equal( env.confirmations[ 0 ].showCancelButton, true );
	env.setConfirmation( { isConfirmed: true } );
	click( card( 3 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	assert.equal( widget.isSaving, true );
	requests[ 1 ].callbacks.error();
	requests[ 1 ].callbacks.completed();
	assert.ok( card( 3 ) );
	click( card( 3 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	requests[ 2 ].callbacks.success( { status: 'success', data: {} } );
	requests[ 2 ].callbacks.completed();
	assert.equal( card( 3 ), null );
	assert.equal( widget.getNote( 3 ), null );
	assert.equal( env.card( 3 ), null );
	assert.equal( widget.isSaving, false );
} );

test( 'deleting the final popup note reloads groups with the shared loader', async ( t ) => {
	const env = setup( t );
	const { card, popup, reloads } = popupCards( env, [ 99 ] );
	const target = popup.querySelector( '.lp-target' );
	const params = JSON.parse( target.dataset.send );
	params.args.paged = 2;
	target.dataset.send = JSON.stringify( params );
	env.click( card( 99 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	env.requests[ 0 ].callbacks.success( { status: 'success', data: {} } );
	env.requests[ 0 ].callbacks.completed();
	assert.equal( reloads(), 3 );
	assert.equal( JSON.parse( target.dataset.send ).args.paged, 1 );
	assert.equal( env.widget.popupRoot.querySelector( '.lp-notes__lesson' ), null );
} );

test( 'sidebar edits in the card, keeps its position and draft across list updates, and restores the add form on cancel', ( t ) => {
	const env = setup( t );
	const { widget, click, card, requests, submit } = env;
	const scrollContainer = widget.panelRoot.closest( '.lp-addon-content-bar__content' );
	scrollContainer.scrollTop = 240;
	click( card( 1 ).querySelector( '.lp-notes__card-edit' ) );
	assert.equal( widget.el.form.parentElement, card( 1 ) );
	assert.equal( scrollContainer.scrollTop, 240 );
	assert.equal( widget.el.formQuote.hidden, true );
	assert.equal( widget.el.formQuote.textContent, '' );
	assert.equal( card( 1 ).querySelector( '.lp-notes__card-quote' ).textContent, 'Alpha' );
	widget.el.formContent.value = 'Inline draft';
	click( widget.el.formContent );
	assert.equal( widget.activeNoteId, null );
	widget.renderList();
	assert.equal( widget.el.form.parentElement, card( 1 ) );
	assert.equal( widget.el.formContent.value, 'Inline draft' );
	assert.equal( scrollContainer.scrollTop, 240 );
	submit();
	requests[ 0 ].callbacks.success( { status: 'success', data: { note: { ...widget.getNote( 1 ), content: 'Inline draft' } } } );
	requests[ 0 ].callbacks.completed();
	assert.equal( widget.el.form.parentElement, widget.panelRoot );
	assert.equal( widget.el.form.hidden, true );
	assert.equal( scrollContainer.scrollTop, 240 );
	click( card( 2 ).querySelector( '.lp-notes__card-edit' ) );
	click( widget.el.form.querySelector( '.lp-notes__form-cancel' ) );
	assert.equal( card( 2 ).querySelector( '.lp-notes__card-content' ).hidden, false );
	assert.equal( widget.el.form.parentElement, widget.panelRoot );
	widget.openForm( { mode: 'create', noteType: 'highlight', quote: 'New selection' } );
	assert.equal( widget.el.form.parentElement, widget.panelRoot );
	assert.equal( widget.el.formQuote.textContent, 'New selection' );
	assert.equal( widget.el.formQuote.hidden, false );
} );

test( 'popup edit retains the card highlight without repeating it inside the form', ( t ) => {
	const env = setup( t );
	const { card } = popupCards( env, [ 99 ] );
	card( 99 ).dataset.noteType = 'highlight';
	const quote = env.dom.window.document.createElement( 'blockquote' );
	quote.className = 'lp-notes__card-quote';
	quote.textContent = 'Original highlight';
	card( 99 ).prepend( quote );
	env.click( card( 99 ).querySelector( '.lp-notes__card-edit' ) );
	const formQuote = env.widget.popupForm.querySelector( '.lp-notes__form-quote' );
	assert.equal( formQuote.hidden, true );
	assert.equal( formQuote.textContent, '' );
	assert.equal( quote.textContent, 'Original highlight' );
	assert.equal( quote.hidden, false );
} );

test( 'cancelling the shared Delete popup preserves course page, disclosure state, scroll and an edit draft', async ( t ) => {
	const env = setup( t );
	const view = popupCards( env, [ 99, 100 ] );
	const target = view.popup.querySelector( '.lp-target' );
	const list = env.dom.window.document.createElement( 'div' );
	list.className = 'lp-notes__list';
	list.appendChild( target.querySelector( '.lp-notes__lesson' ) );
	target.appendChild( list );
	list.scrollTop = 180;
	const params = JSON.parse( target.dataset.send );
	params.args.paged = 2;
	target.dataset.send = JSON.stringify( params );
	env.click( view.card( 99 ).querySelector( '.lp-notes__card-edit' ) );
	const form = env.widget.popupForm;
	form.querySelector( '.lp-notes__form-content' ).value = 'Unsaved draft';
	list.querySelector( 'details' ).open = false;
	env.setConfirmation( { isConfirmed: false } );
	env.click( view.card( 100 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	await Promise.resolve();
	assert.equal( env.requests.length, 0 );
	assert.equal( env.widget.popupForm, form );
	assert.equal( form.querySelector( '.lp-notes__form-content' ).value, 'Unsaved draft' );
	assert.equal( form.isConnected, true );
	assert.equal( list.scrollTop, 180 );
	assert.equal( list.querySelector( 'details' ).open, false );
	assert.equal( JSON.parse( target.dataset.send ).args.paged, 2 );
	assert.equal( view.popup.querySelector( '.lp-notes__delete-prompt' ), null );
} );

function selectLesson( env, rects ) {
	const { widget, dom } = env;
	const range = dom.window.document.createRange();
	range.selectNodeContents( widget.contentRoot );
	const selection = dom.window.getSelection();
	selection.removeAllRanges();
	selection.addRange( range );
	const selectedRange = selection.getRangeAt( 0 );
	selectedRange.getClientRects = () => rects;
	selectedRange.getBoundingClientRect = () => rects[ 0 ];
	Object.defineProperty( dom.window.document.documentElement, 'clientWidth', { value: 800, configurable: true } );
	Object.defineProperty( dom.window, 'innerHeight', { value: 600, configurable: true } );
	Object.defineProperties( widget.el.selectionBtn, { offsetWidth: { value: 100 }, offsetHeight: { value: 36 } } );
	return selectedRange;
}

const selectionRect = ( top, bottom, right = 300 ) => ( { left: 100, right, top, bottom, width: right - 100, height: bottom - top } );

test( 'long selections anchor to a visible line and keep Add Note inside the viewport', ( t ) => {
	const env = setup( t );
	const { widget, click } = env;
	selectLesson( env, [ selectionRect( -100, -80 ), selectionRect( 570, 590, 790 ), selectionRect( 900, 920 ) ] );
	widget.updateSelectionButton();
	assert.equal( widget.el.selectionBtn.hidden, false );
	assert.equal( widget.el.selectionBtn.style.left, '692px' );
	assert.equal( widget.el.selectionBtn.style.top, '526px' );
	// The full selection is retained even though only one line anchors the button.
	click( widget.el.selectionBtn );
	assert.equal( widget.formState.noteType, 'highlight' );
	assert.equal( widget.el.formQuote.textContent, widget.contentRoot.textContent );
} );

test( 'scrolling a lesson pane repositions Add Note and hides it only when the selection is out of view', ( t ) => {
	const env = setup( t );
	const { widget, dom } = env;
	widget.contentRoot.style.overflow = 'auto';
	widget.contentRoot.getBoundingClientRect = () => ( { left: 80, right: 500, top: 100, bottom: 400 } );
	const rects = [ selectionRect( 350, 370 ), selectionRect( 650, 670 ) ];
	selectLesson( env, rects );
	widget.updateSelectionButton();
	assert.equal( widget.el.selectionBtn.hidden, false );
	assert.equal( widget.el.selectionBtn.style.top, '306px' );
	rects[ 0 ] = selectionRect( 200, 220 );
	widget.contentRoot.dispatchEvent( new dom.window.Event( 'scroll' ) );
	assert.equal( widget.el.selectionBtn.hidden, false );
	assert.equal( widget.el.selectionBtn.style.top, '228px' );
	rects[ 0 ] = selectionRect( 50, 70 );
	widget.contentRoot.dispatchEvent( new dom.window.Event( 'scroll' ) );
	assert.equal( widget.el.selectionBtn.hidden, true );
	rects[ 0 ] = selectionRect( 150, 170 );
	widget.contentRoot.dispatchEvent( new dom.window.Event( 'scroll' ) );
	assert.equal( widget.el.selectionBtn.hidden, false );
} );

test( 'selection fallback and resize keep the button positioned without page scroll offsets', ( t ) => {
	const env = setup( t );
	const { widget, dom } = env;
	const range = selectLesson( env, [ selectionRect( 100, 120 ) ] );
	range.getClientRects = () => [];
	Object.defineProperties( dom.window, { scrollX: { value: 30 }, scrollY: { value: 500 } } );
	widget.updateSelectionButton();
	assert.equal( widget.el.selectionBtn.style.left, '250px' );
	assert.equal( widget.el.selectionBtn.style.top, '128px' );
	Object.defineProperty( dom.window, 'innerHeight', { value: 150, configurable: true } );
	dom.window.dispatchEvent( new dom.window.Event( 'resize' ) );
	assert.equal( widget.el.selectionBtn.style.top, '56px' );
	dom.window.getSelection().removeAllRanges();
	widget.updateSelectionButton();
	assert.equal( widget.el.selectionBtn.hidden, true );
} );


test( 'staff cards safely show the note owner and preserve it after editing', ( t ) => {
	const { widget, click, card, requests, submit } = setup( t );
	widget.notes[ 0 ].student_name = '<img src=x>Student A';
	widget.renderList();
	const owner = card( 1 ).querySelector( '.lp-notes__card-student' );
	assert.equal( owner.hidden, false );
	assert.equal( owner.textContent, '<img src=x>Student A' );
	assert.equal( owner.querySelector( 'img' ), null );
	assert.equal( card( 2 ).querySelector( '.lp-notes__card-student' ).hidden, true );
	click( card( 1 ).querySelector( '.lp-notes__card-edit' ) );
	widget.el.formContent.value = 'Updated by staff';
	submit();
	const { student_name, ...savedNote } = widget.notes[ 0 ];
	requests[ 0 ].callbacks.success( { status: 'success', data: { note: { ...savedNote, content: 'Updated by staff' } } } );
	requests[ 0 ].callbacks.completed();
	assert.equal( card( 1 ).querySelector( '.lp-notes__card-student' ).textContent, student_name );
	assert.equal( card( 1 ).querySelector( '.lp-notes__card-content' ).textContent, 'Updated by staff' );
} );
