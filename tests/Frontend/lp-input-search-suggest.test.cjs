const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );
const read = ( file ) => fs.readFileSync( path.resolve( __dirname, '../../', file ), 'utf8' );

const component = ( idUrl, name ) => `<div class="lp-wrap-input-search-suggest" id="${ name }">
	<input type="text" class="lp-input-search-suggest" autocomplete="off">
	<input type="hidden" class="lp-input-search-suggest-value" name="${ name }" value="">
	<div class="lp-search-suggest-result"><div class="lp-target" data-send='{"args":{"id_url":"${ idUrl }","search":""},"callback":{}}'></div><div class="loading-after"><div class="lp-loading-change lp-hidden"></div></div></div>
</div>`;

const htmlList = '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-item" data-id="15" data-text="Course A"><b>Course A</b></li></ul>';

function setup( t, htmlMore = '' ) {
	const dom = new JSDOM( `${ component( 'lp-search-course-suggest', 'course_id' ) }${ component( 'lp-search-user-suggest', 'user_id' ) }${ htmlMore }<p id="outside">Outside</p>` );
	t.after( () => dom.window.close() );
	const { document } = dom.window;
	const requests = [];
	const messages = [];
	const pending = [];
	const window = {
		lpAJAXG: {
			getDataSetCurrent: ( el ) => JSON.parse( el.dataset.send ),
			setDataSetCurrent: ( el, data ) => ( el.dataset.send = JSON.stringify( data ) ),
			showHideLoading: ( el, status ) => el.nextElementSibling.querySelector( '.lp-loading-change' ).classList.toggle( 'lp-hidden', ! status ),
			fetchAJAX: ( data, callbacks ) => requests.push( { data: JSON.parse( JSON.stringify( data ) ), callbacks } ),
		},
	};
	const utils = read( 'assets/src/js/utils.js' );
	const events = utils.slice( utils.indexOf( 'export const eventHandlers =' ), utils.indexOf( '/**\n * Debounce' ) ).replace( 'export const', 'const' );
	const source = read( 'assets/src/js/lp-input-search-suggest.js' ).replace( /import[\s\S]*?from '[^']+';/g, '' ).replace( 'export class', 'class' ).split( 'new LpInputSearchSuggest().init();' )[ 0 ];
	const lpUtils = {
		// Fake debounce: only the last call of each debounced function runs on flush().
		debounce: ( func ) => {
			const slot = { run: null };
			pending.push( slot );
			return ( args ) => ( slot.run = () => func( args ) );
		},
		lpClassName: { hidden: 'lp-hidden' },
		lpShowHideEl: ( el, status ) => el && el.classList.toggle( 'lp-hidden', ! status ),
		lpSetLoadingEl: ( el, status ) => el && el.classList.toggle( 'loading', !! status ),
	};
	const context = { window, document, CustomEvent: dom.window.CustomEvent, lpUtils, lpToastify: { show: ( ...args ) => messages.push( args ) } };
	vm.createContext( context );
	vm.runInContext( `${ events }; lpUtils.eventHandlers = eventHandlers; ${ source }; new LpInputSearchSuggest().init(); new LpInputSearchSuggest().init();`, context );

	const els = ( name ) => {
		const wrap = document.getElementById( name );
		return {
			wrap,
			input: wrap.querySelector( '.lp-input-search-suggest' ),
			value: wrap.querySelector( '.lp-input-search-suggest-value' ),
			result: wrap.querySelector( '.lp-search-suggest-result' ),
			target: wrap.querySelector( '.lp-target' ),
		};
	};
	const type = ( name, text ) => {
		const { input } = els( name );
		input.value = text;
		input.dispatchEvent( new dom.window.Event( 'input', { bubbles: true } ) );
	};
	const flush = () => pending.forEach( ( slot ) => {
		const run = slot.run;
		slot.run = null;
		if ( run ) {
			run();
		}
	} );
	const click = ( el ) => el.dispatchEvent( new dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	const respond = ( index, content = htmlList ) => {
		requests[ index ].callbacks.success( { status: 'success', data: { content } } );
		requests[ index ].callbacks.completed();
	};

	const key = ( name, keyName ) => {
		const event = new dom.window.KeyboardEvent( 'keydown', { key: keyName, bubbles: true, cancelable: true } );
		els( name ).input.dispatchEvent( event );
		return event;
	};
	const active = ( name ) => Array.from( document.querySelectorAll( `#${ name } .lp-search-suggest-item` ) ).map( ( el ) => el.classList.contains( 'is-active' ) );

	return { dom, document, requests, messages, els, type, flush, click, respond, key, active };
}

test( 'no request on load; typing sends one debounced request with the keyword', ( t ) => {
	const env = setup( t );
	assert.equal( env.requests.length, 0 );

	env.type( 'course_id', 'c' );
	env.type( 'course_id', 'co' );
	env.type( 'course_id', ' cou ' );
	assert.equal( env.requests.length, 0 );

	env.flush();
	assert.equal( env.requests.length, 1 );
	assert.equal( env.requests[ 0 ].data.args.search, 'cou' );
	assert.equal( env.requests[ 0 ].data.args.paged, 1 );
	assert.equal( env.requests[ 0 ].data.args.id_url, 'lp-search-course-suggest' );
	assert.equal( env.els( 'course_id' ).wrap.classList.contains( 'loading' ), true );

	env.respond( 0 );
	const { wrap, target, result } = env.els( 'course_id' );
	assert.equal( target.innerHTML, htmlList );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );
	assert.equal( wrap.classList.contains( 'loading' ), false );
} );

test( 'empty text clears the list and the hidden value without a request', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'cou' );
	env.flush();
	env.respond( 0 );
	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item' ) );
	assert.equal( env.els( 'course_id' ).value.value, '15' );

	env.type( 'course_id', '  ' );
	env.flush();
	const { target, value, result } = env.els( 'course_id' );
	assert.equal( env.requests.length, 1 );
	assert.equal( target.innerHTML, '' );
	assert.equal( value.value, '' );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
} );

test( 'text cleared while waiting for the debounce sends no request', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'cou' );
	env.els( 'course_id' ).input.value = '';
	env.flush();
	assert.equal( env.requests.length, 0 );
} );

test( 'stale responses are ignored', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'co' );
	env.flush();
	env.type( 'course_id', 'cou' );
	env.flush();
	assert.equal( env.requests.length, 2 );

	env.respond( 0, '<ul><li>stale</li></ul>' );
	const { wrap, target } = env.els( 'course_id' );
	assert.equal( target.innerHTML, '' );
	assert.equal( wrap.classList.contains( 'loading' ), true );

	env.respond( 1 );
	assert.equal( target.innerHTML, htmlList );
	assert.equal( wrap.classList.contains( 'loading' ), false );
} );

test( 'selecting an item fills the inputs, closes the list and dispatches the event', ( t ) => {
	const env = setup( t );
	const events = [];
	env.document.addEventListener( 'lp-search-suggest-selected', ( e ) => events.push( e ) );

	env.type( 'course_id', 'cou' );
	env.flush();
	env.respond( 0 );
	// Click a child of the item.
	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item b' ) );

	const { wrap, input, value, result } = env.els( 'course_id' );
	assert.equal( input.value, 'Course A' );
	assert.equal( value.value, '15' );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
	assert.equal( events.length, 1, 'events are attached once' );
	assert.equal( events[ 0 ].detail.id, '15' );
	assert.equal( events[ 0 ].detail.text, 'Course A' );
	assert.equal( events[ 0 ].detail.elWrap, wrap );
	assert.equal( env.requests.length, 1 );

	// Typing again invalidates the selected ID.
	env.type( 'course_id', 'Course A2' );
	assert.equal( value.value, '' );
} );

test( 'selecting while a request is running stops the loading and ignores its response', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'co' );
	env.flush();
	env.respond( 0 );
	env.type( 'course_id', 'cou' );
	env.flush();
	assert.equal( env.els( 'course_id' ).wrap.classList.contains( 'loading' ), true );

	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item' ) );
	const { wrap, target, value, result } = env.els( 'course_id' );
	assert.equal( wrap.classList.contains( 'loading' ), false );

	env.respond( 1, '<ul><li>late</li></ul>' );
	assert.equal( target.innerHTML, '' );
	assert.equal( value.value, '15' );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
	assert.equal( wrap.classList.contains( 'loading' ), false );
} );

test( 'selecting while the debounce is waiting sends no request', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'co' );
	env.flush();
	env.respond( 0 );
	env.type( 'course_id', 'cou' );
	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item' ) );
	env.flush();

	assert.equal( env.requests.length, 1 );
	assert.equal( env.els( 'course_id' ).value.value, '15' );
	assert.equal( env.els( 'course_id' ).result.classList.contains( 'lp-hidden' ), true );

	// The removed list is not opened again on focus.
	env.els( 'course_id' ).input.dispatchEvent( new env.dom.window.FocusEvent( 'focusin', { bubbles: true } ) );
	assert.equal( env.els( 'course_id' ).result.classList.contains( 'lp-hidden' ), true );
} );

test( 'two components work independently', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'cou' );
	env.type( 'user_id', 'john' );
	env.flush();
	assert.equal( env.requests.length, 2 );
	assert.equal( env.requests[ 0 ].data.args.id_url, 'lp-search-course-suggest' );
	assert.equal( env.requests[ 0 ].data.args.search, 'cou' );
	assert.equal( env.requests[ 1 ].data.args.id_url, 'lp-search-user-suggest' );
	assert.equal( env.requests[ 1 ].data.args.search, 'john' );

	env.respond( 0 );
	assert.equal( env.els( 'course_id' ).target.innerHTML, htmlList );
	assert.equal( env.els( 'user_id' ).target.innerHTML, '' );

	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item' ) );
	assert.equal( env.els( 'course_id' ).value.value, '15' );
	assert.equal( env.els( 'user_id' ).value.value, '' );
	assert.equal( env.els( 'user_id' ).input.value, 'john' );
} );

test( 'click outside closes the list, focus opens it again', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'cou' );
	env.flush();
	env.respond( 0 );
	const { input, result } = env.els( 'course_id' );

	env.click( input );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );

	env.click( env.document.getElementById( 'outside' ) );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );

	input.dispatchEvent( new env.dom.window.FocusEvent( 'focusin', { bubbles: true } ) );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );

	// Click inside the other component closes this one.
	env.click( env.els( 'user_id' ).input );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
} );

test( 'errors are shown with toastify and keep the list', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'cou' );
	env.flush();
	env.requests[ 0 ].callbacks.success( { status: 'error', message: 'Invalid nonce' } );
	env.requests[ 0 ].callbacks.completed();
	assert.deepEqual( env.messages[ 0 ], [ 'Invalid nonce', 'error' ] );
	assert.equal( env.els( 'course_id' ).target.innerHTML, '' );
	assert.equal( env.els( 'course_id' ).wrap.classList.contains( 'loading' ), false );

	env.type( 'course_id', 'cour' );
	env.flush();
	env.requests[ 1 ].callbacks.error( new Error( 'Network' ) );
	env.requests[ 1 ].callbacks.completed();
	assert.deepEqual( env.messages[ 1 ], [ 'Network', 'error' ] );
} );

const htmlList3 = '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-item" data-id="1" data-text="A">A</li><li class="lp-search-suggest-item" data-id="2" data-text="B">B</li><li class="lp-search-suggest-item" data-id="3" data-text="C">C</li></ul>';

test( 'ArrowDown/ArrowUp move the active item and stop at the ends', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'a' );
	env.flush();
	env.respond( 0, htmlList3 );
	assert.deepEqual( env.active( 'course_id' ), [ false, false, false ] );

	assert.equal( env.key( 'course_id', 'ArrowDown' ).defaultPrevented, true );
	assert.deepEqual( env.active( 'course_id' ), [ true, false, false ] );
	env.key( 'course_id', 'ArrowDown' );
	env.key( 'course_id', 'ArrowDown' );
	env.key( 'course_id', 'ArrowDown' );
	assert.deepEqual( env.active( 'course_id' ), [ false, false, true ] );
	env.key( 'course_id', 'ArrowUp' );
	assert.deepEqual( env.active( 'course_id' ), [ false, true, false ] );
	env.key( 'course_id', 'ArrowUp' );
	env.key( 'course_id', 'ArrowUp' );
	assert.deepEqual( env.active( 'course_id' ), [ true, false, false ] );

	// The other component is not affected.
	assert.deepEqual( env.active( 'user_id' ), [] );
	assert.equal( env.requests.length, 1 );
} );

test( 'ArrowUp without an active item starts at the last item', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'a' );
	env.flush();
	env.respond( 0, htmlList3 );
	env.key( 'course_id', 'ArrowUp' );
	assert.deepEqual( env.active( 'course_id' ), [ false, false, true ] );
} );

test( 'Enter selects the active item and dispatches the event', ( t ) => {
	const env = setup( t );
	const events = [];
	env.document.addEventListener( 'lp-search-suggest-selected', ( e ) => events.push( e ) );
	env.type( 'course_id', 'a' );
	env.flush();
	env.respond( 0, htmlList3 );

	// No active item: Enter keeps its default behavior.
	assert.equal( env.key( 'course_id', 'Enter' ).defaultPrevented, false );
	assert.equal( events.length, 0 );

	env.key( 'course_id', 'ArrowDown' );
	env.key( 'course_id', 'ArrowDown' );
	assert.equal( env.key( 'course_id', 'Enter' ).defaultPrevented, true );

	const { input, value, result } = env.els( 'course_id' );
	assert.equal( input.value, 'B' );
	assert.equal( value.value, '2' );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
	assert.equal( events.length, 1 );
	assert.equal( events[ 0 ].detail.id, '2' );

	// The list is removed: Enter and arrows do nothing.
	assert.equal( env.key( 'course_id', 'Enter' ).defaultPrevented, false );
	assert.equal( env.key( 'course_id', 'ArrowDown' ).defaultPrevented, false );
	assert.equal( events.length, 1 );
} );

test( 'Escape closes the list, ArrowDown opens it again with the same active item', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'a' );
	env.flush();
	env.respond( 0, htmlList3 );
	env.key( 'course_id', 'ArrowDown' );
	env.key( 'course_id', 'ArrowDown' );

	const { result } = env.els( 'course_id' );
	assert.equal( env.key( 'course_id', 'Escape' ).defaultPrevented, true );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );

	// Closed list: Enter does not select.
	assert.equal( env.key( 'course_id', 'Enter' ).defaultPrevented, false );
	assert.equal( env.els( 'course_id' ).value.value, '' );

	env.key( 'course_id', 'ArrowDown' );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );
	assert.deepEqual( env.active( 'course_id' ), [ false, true, false ] );
} );

test( 'arrows ignore the "No results" row and other keys', ( t ) => {
	const env = setup( t );
	env.type( 'course_id', 'zzz' );
	env.flush();
	env.respond( 0, '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-empty">No results</li></ul>' );
	assert.equal( env.key( 'course_id', 'ArrowDown' ).defaultPrevented, false );
	assert.equal( env.key( 'course_id', 'a' ).defaultPrevented, false );
} );

// Multiple select.
const itemMultiple = ( id, text, checked = false ) => `<li class="lp-search-suggest-item lp-search-suggest-item--multiple${ checked ? ' is-selected' : '' }" data-id="${ id }" data-text="${ text }" data-type-select="multiple"><input type="checkbox" class="lp-search-suggest-checkbox" tabindex="-1" value="${ id }"${ checked ? ' checked' : '' }>${ text } (#${ id })</li>`;
const listMultiple = ( items ) => `<ul class="lp-search-suggest-list">${ items.map( ( item ) => itemMultiple( ...item ) ).join( '' ) }</ul>`;
const componentMultiple = ( value = '', htmlSelected = '' ) => `<div class="lp-wrap-input-search-suggest" id="ids" data-type-select="multiple">
	<input type="text" class="lp-input-search-suggest" autocomplete="off">
	<input type="hidden" class="lp-input-search-suggest-value" name="ids" value="${ value }">
	<div class="lp-search-suggest-result lp-hidden"><div class="lp-target" data-send='{"args":{"id_url":"lp-search-course-suggest","search":"","type_select":"multiple"},"callback":{}}'>${ htmlSelected }</div><div class="loading-after"><div class="lp-loading-change lp-hidden"></div></div></div>
</div>`;
const list123 = listMultiple( [ [ 1, 'A' ], [ 2, 'B' ], [ 3, 'C' ] ] );
const checkedOf = ( env ) => Array.from( env.document.querySelectorAll( '#ids .lp-search-suggest-item' ) ).map( ( el ) => `${ el.dataset.id }:${ el.querySelector( 'input' ).checked ? 1 : 0 }${ el.classList.contains( 'is-selected' ) ? 1 : 0 }` );
const focus = ( env, name ) => env.els( name ).input.dispatchEvent( new env.dom.window.FocusEvent( 'focusin', { bubbles: true } ) );

test( 'multiple: selecting items sets the hidden value like 1,2,3 and keeps the list open', ( t ) => {
	const env = setup( t, componentMultiple() );
	const events = [];
	env.document.addEventListener( 'lp-search-suggest-selected', ( e ) => events.push( e.detail ) );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	const items = () => env.document.querySelectorAll( '#ids .lp-search-suggest-item' );
	const { input, value, result } = env.els( 'ids' );

	env.click( items()[ 0 ] );
	env.click( items()[ 1 ] );
	env.click( items()[ 2 ] );
	assert.equal( value.value, '1,2,3' );
	assert.deepEqual( checkedOf( env ), [ '1:11', '2:11', '3:11' ] );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );
	assert.equal( input.value, 'a', 'keyword is kept' );
	assert.equal( env.requests.length, 1 );

	// Unselect.
	env.click( items()[ 1 ] );
	assert.equal( value.value, '1,3' );
	assert.deepEqual( checkedOf( env ), [ '1:11', '2:00', '3:11' ] );

	assert.equal( events.length, 4 );
	assert.equal( events[ 2 ].id, '1,2,3' );
	assert.deepEqual( Array.from( events[ 2 ].ids ), [ '1', '2', '3' ] );
	assert.deepEqual( { ...events[ 2 ].item }, { id: '3', text: 'C', checked: true } );
	assert.deepEqual( { ...events[ 3 ].item }, { id: '2', text: 'B', checked: false } );
	assert.equal( events[ 3 ].elWrap, env.els( 'ids' ).wrap );
} );

test( 'multiple: clicking the checkbox itself toggles once', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	const checkbox = env.document.querySelector( '#ids .lp-search-suggest-item[data-id="2"] input' );

	checkbox.click();
	assert.equal( env.els( 'ids' ).value.value, '2' );
	assert.equal( checkbox.checked, true );

	checkbox.click();
	assert.equal( env.els( 'ids' ).value.value, '' );
	assert.equal( checkbox.checked, false );
} );

test( 'multiple: typing keeps the value and new results are checked', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="2"]' ) );

	env.type( 'ids', 'ab' );
	assert.equal( env.els( 'ids' ).value.value, '2' );
	env.flush();
	env.respond( 1, listMultiple( [ [ 4, 'D' ], [ 2, 'B' ] ] ) );
	// Selected first, then the results not selected.
	assert.deepEqual( checkedOf( env ), [ '2:11', '4:00' ] );

	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="4"]' ) );
	assert.equal( env.els( 'ids' ).value.value, '2,4' );
} );

test( 'multiple: focus without a keyword lists the items selected, checked', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="3"]' ) );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="1"]' ) );
	const { result, value } = env.els( 'ids' );

	// Close by click outside, then clear the keyword: the items selected are listed.
	env.click( env.document.getElementById( 'outside' ) );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
	env.type( 'ids', '' );
	assert.deepEqual( checkedOf( env ), [ '3:11', '1:11' ] );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );
	assert.equal( value.value, '3,1' );

	// Unselect in this list, close with Escape, focus again: only the item still selected.
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="3"]' ) );
	assert.equal( value.value, '1' );
	assert.equal( env.key( 'ids', 'Escape' ).defaultPrevented, true );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );
	focus( env, 'ids' );
	assert.deepEqual( checkedOf( env ), [ '1:11' ] );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );
	assert.equal( env.requests.length, 1 );
} );

test( 'multiple: items selected printed by PHP are listed on focus', ( t ) => {
	const env = setup( t, componentMultiple( '5,6', listMultiple( [ [ 5, 'E', true ], [ 6, 'F', true ] ] ) ) );
	const { result, value } = env.els( 'ids' );
	assert.equal( result.classList.contains( 'lp-hidden' ), true );

	focus( env, 'ids' );
	assert.deepEqual( checkedOf( env ), [ '5:11', '6:11' ] );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );
	assert.equal( env.requests.length, 0 );

	// Search, select one more, clear the keyword: all three are listed.
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, listMultiple( [ [ 1, 'A' ], [ 5, 'E' ] ] ) );
	assert.deepEqual( checkedOf( env ), [ '5:11', '6:11', '1:00' ] );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="1"]' ) );
	assert.equal( value.value, '5,6,1' );
	env.type( 'ids', '' );
	assert.deepEqual( checkedOf( env ), [ '5:11', '6:11', '1:11' ] );
} );

test( 'multiple: focus with nothing selected shows nothing', ( t ) => {
	const env = setup( t, componentMultiple() );
	focus( env, 'ids' );
	assert.equal( env.els( 'ids' ).result.classList.contains( 'lp-hidden' ), true );
	assert.equal( env.els( 'ids' ).target.innerHTML, '' );
} );

test( 'multiple: Enter toggles the active item and keeps the list open', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	const { value, result } = env.els( 'ids' );

	env.key( 'ids', 'ArrowDown' );
	env.key( 'ids', 'ArrowDown' );
	assert.equal( env.key( 'ids', 'Enter' ).defaultPrevented, true );
	assert.equal( value.value, '2' );
	assert.equal( result.classList.contains( 'lp-hidden' ), false );

	env.key( 'ids', 'ArrowDown' );
	env.key( 'ids', 'Enter' );
	assert.equal( value.value, '2,3' );
	env.key( 'ids', 'Enter' );
	assert.equal( value.value, '2' );

	// The item selected with the keyboard is not active in the list of items selected.
	env.type( 'ids', '' );
	assert.equal( env.document.querySelectorAll( '#ids .is-active' ).length, 0 );
} );

test( 'multiple and single components do not affect each other', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.type( 'course_id', 'cou' );
	env.flush();
	env.respond( 0, list123 );
	env.respond( 1 );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="1"]' ) );
	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item' ) );

	assert.equal( env.els( 'ids' ).value.value, '1' );
	assert.equal( env.els( 'course_id' ).value.value, '15' );
	assert.equal( env.els( 'course_id' ).input.value, 'Course A' );
	assert.equal( env.els( 'ids' ).input.value, 'a' );
} );

test( 'multiple: results list the items selected first, then the results not selected', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="3"]' ) );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="2"]' ) );
	// Toggling does not move the items under the cursor.
	assert.deepEqual( checkedOf( env ), [ '1:00', '2:11', '3:11' ] );

	// New search: 3 and 2 (order of selection) at the top, 2 is not repeated in the results.
	env.type( 'ids', 'ab' );
	env.flush();
	env.respond( 1, listMultiple( [ [ 7, 'G' ], [ 2, 'B' ], [ 8, 'H' ] ] ) );
	assert.deepEqual( checkedOf( env ), [ '3:11', '2:11', '7:00', '8:00' ] );
	assert.equal( env.document.querySelectorAll( '#ids .lp-search-suggest-list' ).length, 1 );

	// No results: the items selected are still listed, followed by the "No results" row.
	env.type( 'ids', 'zzz' );
	env.flush();
	env.respond( 2, '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-empty">No results</li></ul>' );
	assert.deepEqual( checkedOf( env ), [ '3:11', '2:11' ] );
	assert.equal( env.document.querySelector( '#ids .lp-search-suggest-list' ).lastElementChild.className, 'lp-search-suggest-empty' );

	// An error message is shown as is.
	env.type( 'ids', 'zzzz' );
	env.flush();
	env.respond( 3, '<div class="learn-press-message error">Error</div>' );
	assert.equal( env.els( 'ids' ).target.innerHTML, '<div class="learn-press-message error">Error</div>' );
	assert.equal( env.els( 'ids' ).value.value, '3,2' );
} );

test( 'multiple: nothing selected keeps the results as they are', ( t ) => {
	const env = setup( t, componentMultiple() );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	assert.equal( env.els( 'ids' ).target.innerHTML, list123 );
} );

test( 'multiple without the wrapper attribute: a new search keeps the items selected, only untick removes one', ( t ) => {
	// Wrapper not marked and args without type_select: only the items say it is multiple.
	const html = componentMultiple().replace( ' data-type-select="multiple"', '' ).replace( ',"type_select":"multiple"', '' );
	const env = setup( t, html );
	assert.equal( env.els( 'ids' ).wrap.dataset.typeSelect, undefined );

	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, list123 );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="1"]' ) );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="3"]' ) );
	assert.equal( env.els( 'ids' ).value.value, '1,3' );

	// New searches: nothing is removed.
	env.type( 'ids', 'ab' );
	assert.equal( env.els( 'ids' ).value.value, '1,3' );
	env.flush();
	env.respond( 1, listMultiple( [ [ 7, 'G' ] ] ) );
	assert.equal( env.els( 'ids' ).value.value, '1,3' );
	assert.deepEqual( checkedOf( env ), [ '1:11', '3:11', '7:00' ] );

	env.type( 'ids', 'abc' );
	env.flush();
	env.respond( 2, '<ul class="lp-search-suggest-list"><li class="lp-search-suggest-empty">No results</li></ul>' );
	assert.equal( env.els( 'ids' ).value.value, '1,3' );
	assert.deepEqual( checkedOf( env ), [ '1:11', '3:11' ] );

	// Untick removes only that item.
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="1"]' ) );
	assert.equal( env.els( 'ids' ).value.value, '3' );
	env.type( 'ids', 'abcd' );
	env.flush();
	env.respond( 3, listMultiple( [ [ 1, 'A' ], [ 9, 'I' ] ] ) );
	assert.equal( env.els( 'ids' ).value.value, '3' );
	assert.deepEqual( checkedOf( env ), [ '3:11', '1:00', '9:00' ] );
} );

test( 'multiple detected from the AJAX args: the first search keeps the value printed by PHP', ( t ) => {
	const html = componentMultiple( '5,6', listMultiple( [ [ 5, 'E', true ], [ 6, 'F', true ] ] ) ).replace( ' data-type-select="multiple"', '' );
	const env = setup( t, html.replace( /data-type-select="multiple"/g, '' ) );

	env.type( 'ids', 'a' );
	assert.equal( env.els( 'ids' ).value.value, '5,6' );
} );

// Multiple select: button "Clear all".
const htmlActions = '<li class="lp-search-suggest-actions"><button type="button" class="lp-search-suggest-clear-all">Clear all</button></li>';
const listMultipleActions = ( items ) => listMultiple( items ).replace( '<ul class="lp-search-suggest-list">', `<ul class="lp-search-suggest-list">${ htmlActions }` );
const actionsOf = ( env ) => Array.from( env.document.querySelectorAll( '#ids .lp-search-suggest-list > li' ) ).map( ( el ) => el.classList.contains( 'lp-search-suggest-actions' ) ? `actions:${ el.classList.contains( 'lp-hidden' ) ? 'hidden' : 'shown' }` : el.dataset.id || 'empty' );

test( 'multiple: Clear all unselects all items and keeps the list open', ( t ) => {
	const env = setup( t, componentMultiple() );
	const events = [];
	env.document.addEventListener( 'lp-search-suggest-selected', ( e ) => events.push( e.detail ) );
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, listMultipleActions( [ [ 1, 'A' ], [ 2, 'B' ], [ 3, 'C' ] ] ) );
	// Nothing selected: the button is hidden.
	assert.deepEqual( actionsOf( env ), [ 'actions:hidden', '1', '2', '3' ] );

	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="1"]' ) );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-item[data-id="3"]' ) );
	assert.deepEqual( actionsOf( env ), [ 'actions:shown', '1', '2', '3' ] );
	assert.equal( env.els( 'ids' ).value.value, '1,3' );

	const clicked = new env.dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } );
	env.document.querySelector( '#ids .lp-search-suggest-clear-all' ).dispatchEvent( clicked );
	assert.equal( clicked.defaultPrevented, true );
	assert.equal( env.els( 'ids' ).value.value, '' );
	assert.deepEqual( checkedOf( env ), [ '1:00', '2:00', '3:00' ] );
	assert.deepEqual( actionsOf( env ), [ 'actions:hidden', '1', '2', '3' ] );
	assert.equal( env.els( 'ids' ).result.classList.contains( 'lp-hidden' ), false );
	assert.equal( env.els( 'ids' ).input.value, 'a' );
	assert.equal( env.requests.length, 1 );

	const detail = events[ events.length - 1 ];
	assert.equal( detail.id, '' );
	assert.equal( detail.ids.length, 0 );
	assert.equal( detail.item, null );

	// Nothing is listed on focus after the keyword is cleared.
	env.type( 'ids', '' );
	assert.equal( env.els( 'ids' ).target.innerHTML, '' );
	assert.equal( env.els( 'ids' ).result.classList.contains( 'lp-hidden' ), true );

	// A new search has no item selected.
	env.type( 'ids', 'ab' );
	env.flush();
	env.respond( 1, listMultipleActions( [ [ 1, 'A' ], [ 4, 'D' ] ] ) );
	assert.deepEqual( checkedOf( env ), [ '1:00', '4:00' ] );
	assert.deepEqual( actionsOf( env ), [ 'actions:hidden', '1', '4' ] );
} );

test( 'multiple: Clear all stays the first row, before the items selected', ( t ) => {
	const env = setup( t, componentMultiple( '5,6', listMultipleActions( [ [ 5, 'E', true ], [ 6, 'F', true ] ] ) ) );

	// List of the items selected, built by JS on focus.
	focus( env, 'ids' );
	assert.deepEqual( actionsOf( env ), [ 'actions:shown', '5', '6' ] );

	// Results: button, items selected, results not selected.
	env.type( 'ids', 'a' );
	env.flush();
	env.respond( 0, listMultipleActions( [ [ 1, 'A' ], [ 6, 'F' ] ] ) );
	assert.deepEqual( actionsOf( env ), [ 'actions:shown', '5', '6', '1' ] );
	assert.equal( env.document.querySelectorAll( '#ids .lp-search-suggest-clear-all' ).length, 1 );

	// The button is not an item: arrows skip it.
	env.key( 'ids', 'ArrowDown' );
	assert.equal( env.document.querySelector( '#ids .is-active' ).dataset.id, '5' );

	// Clear all from the list of the items selected.
	env.type( 'ids', '' );
	assert.deepEqual( actionsOf( env ), [ 'actions:shown', '5', '6' ] );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-clear-all' ) );
	assert.equal( env.els( 'ids' ).value.value, '' );
	// No keyword and nothing selected any more: the list is emptied and closed.
	assert.equal( env.els( 'ids' ).target.innerHTML, '' );
	assert.equal( env.els( 'ids' ).result.classList.contains( 'lp-hidden' ), true );
} );

test( 'Clear all of one component does not touch the others', ( t ) => {
	const env = setup( t, componentMultiple( '5', listMultipleActions( [ [ 5, 'E', true ] ] ) ) );
	env.type( 'course_id', 'cou' );
	env.flush();
	env.respond( 0 );
	env.click( env.document.querySelector( '#course_id .lp-search-suggest-item' ) );

	focus( env, 'ids' );
	env.click( env.document.querySelector( '#ids .lp-search-suggest-clear-all' ) );
	assert.equal( env.els( 'ids' ).value.value, '' );
	assert.equal( env.els( 'course_id' ).value.value, '15' );
} );
