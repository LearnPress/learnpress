const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );
const read = ( file ) => fs.readFileSync( path.resolve( __dirname, '../../', file ), 'utf8' );

function setup( t ) {
	const dom = new JSDOM( `<form class="lp-student-notes__edit-form" data-note-id="1">
		<textarea name="content" required>&lt;img src=x&gt;Original note</textarea>
		<button type="submit" class="lp-button lp-be-btn lp-be-btn--primary">Save</button>
		<button type="button" class="lp-button lp-be-btn lp-be-btn--outline lp-student-notes__cancel">Cancel</button>
	</form><button class="lp-button lp-be-btn lp-be-btn--danger lp-student-notes__delete" data-note-id="1" data-confirm="Delete this note?">Delete</button>` );
	t.after( () => dom.window.close() );
	const requests = [];
	const messages = [];
	const modals = [];
	let confirmed = false;
	let reloads = 0;
	let closes = 0;
	const window = {
		location: { reload: () => ++reloads },
		lpAJAXG: { fetchAJAX: ( data, callbacks ) => requests.push( { data, callbacks } ) },
	};
	const utils = read( 'assets/src/js/utils.js' );
	const events = utils.slice( utils.indexOf( 'export const eventHandlers =' ), utils.indexOf( '/**\n * Debounce' ) ).replace( 'export const', 'const' );
	const source = read( 'assets/src/js/admin/student-notes.js' ).replace( /import[\s\S]*?from '[^']+';/g, '' ).replace( 'export class', 'class' ).split( 'new StudentNotesAdmin().init();' )[ 0 ];
	const context = { SweetAlert: { fire: ( options ) => { modals.push( options ); if ( options.html ) { dom.window.document.body.appendChild( options.html ); } return Promise.resolve( { isConfirmed: confirmed } ); }, close: () => ++closes, getCloseButton: () => null }, window, document: dom.window.document, __: ( text ) => text, lpUtils: { lpSetLoadingEl() {} }, lpToastify: { show: ( ...args ) => messages.push( args ) } };
	vm.createContext( context );
	vm.runInContext( `${ events }; lpUtils.eventHandlers = eventHandlers; ${ source }; globalThis.admin = new StudentNotesAdmin(); admin.init();`, context );
	const form = dom.window.document.querySelector( 'form' );
	const input = form.elements.content;
	const click = ( selector ) => dom.window.document.querySelector( selector ).dispatchEvent( new dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	const submit = () => form.dispatchEvent( new dom.window.Event( 'submit', { bubbles: true, cancelable: true } ) );
	return { dom, modals, requests, messages, form, input, click, submit, admin: context.admin, setConfirmed: ( value ) => { confirmed = value; }, reloads: () => reloads, closes: () => closes };
}

test( 'LearnPress edit form uses the shared save endpoint and retains the draft on failure', ( t ) => {
	const env = setup( t );
	assert.equal( env.input.value, '<img src=x>Original note' );
	env.input.value = '';
	env.submit();
	assert.equal( env.requests.length, 0 );
	env.input.value = 'Updated note';
	env.submit();
	assert.equal( env.requests[ 0 ].data.action, 'lp_note_save' );
	assert.equal( env.requests[ 0 ].data.note_id, '1' );
	assert.equal( env.requests[ 0 ].data.content, 'Updated note' );
	assert.equal( env.requests[ 0 ].data.course_id, undefined );
	assert.equal( env.input.disabled, true );
	env.click( '.lp-student-notes__cancel' );
	env.submit();
	assert.equal( env.closes(), 0 );
	assert.equal( env.requests.length, 1 );
	env.requests[ 0 ].callbacks.success( { status: 'error', message: 'Permission denied' } );
	env.requests[ 0 ].callbacks.completed();
	assert.equal( env.input.value, 'Updated note' );
	assert.equal( env.input.disabled, false );
	assert.equal( env.reloads(), 0 );
	assert.deepEqual( env.messages[ 0 ], [ 'Permission denied', 'error' ] );
	env.submit();
	env.requests[ 1 ].callbacks.success( { status: 'success', message: 'Saved' } );
	env.requests[ 1 ].callbacks.completed();
	assert.equal( env.reloads(), 1 );
} );

test( 'delete uses the shared LearnPress confirmation and existing endpoint', async ( t ) => {
	const env = setup( t );
	env.click( '.lp-student-notes__delete' );
	await new Promise( ( resolve ) => setImmediate( resolve ) );
	assert.equal( env.requests.length, 0 );
	env.setConfirmed( true );
	env.click( '.lp-student-notes__delete' );
	await new Promise( ( resolve ) => setImmediate( resolve ) );
	assert.equal( env.requests[ 0 ].data.action, 'lp_note_delete' );
	assert.equal( env.requests[ 0 ].data.note_id, '1' );
	env.requests[ 0 ].callbacks.success( { status: 'success', message: 'Deleted' } );
	env.requests[ 0 ].callbacks.completed();
	assert.equal( env.reloads(), 1 );
} );

test( 'network errors unlock the LearnPress form and Cancel closes the shared dialog', ( t ) => {
	const env = setup( t );
	env.submit();
	env.requests[ 0 ].callbacks.error();
	env.requests[ 0 ].callbacks.completed();
	assert.equal( env.admin.isRequesting, false );
	assert.equal( env.input.disabled, false );
	assert.equal( env.reloads(), 0 );
	assert.equal( env.messages[ 0 ][ 1 ], 'error' );
	env.click( '.lp-student-notes__cancel' );
	assert.equal( env.closes(), 1 );
} );


test( 'view and edit open the shared LearnPress dialog with cloned content and existing button classes', ( t ) => {
	const env = setup( t );
	env.dom.window.document.body.insertAdjacentHTML( 'beforeend', '<button data-note-template="edit-note" data-title="Edit">Edit</button><template id="edit-note"><form class="lp-student-notes__edit-form" data-note-id="2"><textarea name="content">&lt;img src=x&gt;Draft</textarea><button type="submit" class="lp-button lp-be-btn lp-be-btn--primary">Save</button></form></template>' );
	env.click( '[data-note-template]' );
	const options = env.modals[ 0 ];
	assert.equal( options.title, 'Edit' );
	assert.equal( options.showCloseButton, true );
	assert.equal( options.showConfirmButton, false );
	assert.equal( options.html.querySelector( 'textarea' ).value, '<img src=x>Draft' );
	assert.equal( options.html.querySelector( 'img' ), null );
	assert.ok( options.html.querySelector( '.lp-be-btn--primary' ) );
	env.admin.isRequesting = true;
	assert.equal( options.allowOutsideClick(), false );
	assert.equal( options.allowEscapeKey(), false );
} );
