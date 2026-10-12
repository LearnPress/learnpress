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
		<div id="lp-notes"><button class="lp-notes__selection-btn" hidden></button></div>
		<div id="popup-right-sidebar">
			<div class="popup-right-sidebar__item" tabindex="0" data-learning-bar-item="notes">
				<template>
					<div class="lp-learning-bar-item-head">Notes</div>
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
								<time class="lp-notes__card-date"></time>
								<p class="lp-notes__card-content"></p>
								<blockquote class="lp-notes__card-quote"></blockquote>
								<p class="lp-notes__card-orphaned"></p>
								<button class="lp-notes__card-edit">Edit</button>
								<button class="lp-notes__card-delete">Delete</button>
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
	const context = {
		window: dom.window,
		document: dom.window.document,
		CustomEvent: dom.window.CustomEvent,
		NodeFilter: dom.window.NodeFilter,
		CSS: { escape: String },
		lpUtils: { debounce: ( fn ) => fn, lpSetLoadingEl() {} },
		lpToastify: { show() {} },
		SweetAlert: { fire: () => Promise.resolve( { isConfirmed: true } ) },
	};
	vm.createContext( context );
	vm.runInContext( `${ events }\nlpUtils.eventHandlers = eventHandlers;\n${ anchors }\n${ notes }\n${ bar }\nglobalThis.CourseNotes = CourseNotes; globalThis.LearningContentBar = LearningContentBar;`, context );
	dom.window.lpCourseNotes = {
		canEdit: true,
		contentSelector: '#lesson',
		courseId: 10,
		itemId: 20,
		itemType: 'lp_lesson',
		i18n: { orphaned: 'Removed', contentEmpty: 'Empty', error: 'Error' },
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
	return { widget, requests, click, open, close, card, submit, marks };
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
	assert.equal( widget.activeNoteId, '1' );
	assert.equal( card( 1 ).classList.contains( 'is-active' ), true );
	assert.equal( marks()[ 0 ].dataset.noteId, '1' );
	assert.equal( widget.el.list.children.length, 20 );
	click( card( 1 ).querySelector( '.lp-notes__card-delete' ) );
	await Promise.resolve();
	requests[ 1 ].callbacks.success( { status: 'success', data: {} } );
	assert.equal( widget.activeNoteId, null );
	assert.equal( marks().length, 0 );
	assert.equal( widget.el.list.querySelectorAll( '.is-active' ).length, 0 );
	click( widget.el.showMore );
	assert.equal( widget.el.list.children.length, 23 );
	assert.equal( widget.el.showMore.hidden, true );
} );
