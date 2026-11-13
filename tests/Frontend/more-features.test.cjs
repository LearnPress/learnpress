// Run: node --test tests/Frontend/more-features.test.cjs
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

const DAY = 24 * 60 * 60 * 1000;
const source = fs
	.readFileSync( path.resolve( __dirname, '../../assets/src/js/admin/edit-course/more-features.js' ), 'utf8' )
	.replace( /export const/g, 'const' )
	.replace( /export class/g, 'class' );

const items = ( n ) =>
	Array.from( { length: n }, ( _, i ) => `<li class="lp-more-features__item" data-slug="a${ i + 1 }"${ i >= 5 ? ' hidden' : '' }><button type="button" class="lp-more-features__dismiss"></button></li>` ).join( '' );

function setup( t, { count = 7, stored = null, brokenStorage = false } = {} ) {
	const dom = new JSDOM(
		`<div class="lp-more-features" data-max="5" data-ttl-days="30" data-storage-key="k">
			<ul>${ items( count ) }</ul>
			<div class="lp-more-features__empty" hidden><a href="#" class="lp-more-features__reset-link">Reset recommendations</a></div>
			<button type="button" class="lp-more-features__reset" hidden>Reset suggestions</button>
		</div>`,
		{ url: 'http://localhost' }
	);
	t.after( () => dom.window.close() );
	const store = dom.window.localStorage;
	let clock = 1_800_000_000_000;
	if ( stored ) {
		store.setItem( 'k', JSON.stringify( stored( clock ) ) );
	}
	const window = brokenStorage
		? { localStorage: { getItem() { throw new Error( 'blocked' ); }, setItem() { throw new Error( 'blocked' ); }, removeItem() { throw new Error( 'blocked' ); } } }
		: dom.window;
	const context = { window, document: dom.window.document, setTimeout: ( fn ) => fn(), Date, JSON, Object, Array, Number, parseInt, MutationObserver: undefined };
	vm.createContext( context );
	vm.runInContext( `${ source }; globalThis.api = { MoreFeatures, parseDismissed, pickVisible };`, context );
	const root = dom.window.document.querySelector( '.lp-more-features' );
	const feature = new context.api.MoreFeatures( root, { delay: 0, now: () => clock } );
	feature.init();
	const shown = () => Array.from( root.querySelectorAll( '.lp-more-features__item' ) ).filter( ( el ) => ! el.hidden ).map( ( el ) => el.dataset.slug );
	const click = ( sel ) => root.querySelector( sel ).dispatchEvent( new dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } ) );
	return { dom, root, store, feature, shown, click, api: context.api, tick: ( ms ) => { clock += ms; } };
}

test( 'shows at most 5 items and hides the Reset button by default', ( t ) => {
	const env = setup( t );
	assert.deepEqual( env.shown(), [ 'a1', 'a2', 'a3', 'a4', 'a5' ] );
	assert.equal( env.root.querySelector( '.lp-more-features__reset' ).hidden, true );
} );

test( 'dismiss hides the item, pulls in the next one and stores a timestamp', ( t ) => {
	const env = setup( t );
	env.root.querySelector( '[data-slug="a2"] .lp-more-features__dismiss' ).click();
	assert.deepEqual( env.shown(), [ 'a1', 'a3', 'a4', 'a5', 'a6' ] );
	const saved = JSON.parse( env.store.getItem( 'k' ) );
	assert.deepEqual( Object.keys( saved ), [ 'a2' ] );
	assert.equal( typeof saved.a2, 'number' );
	assert.equal( env.root.querySelector( '.lp-more-features__reset' ).hidden, false );
} );

test( 'keeps fewer than 5 when no add-on is left to add', ( t ) => {
	const env = setup( t, { count: 5 } );
	env.root.querySelector( '[data-slug="a1"] .lp-more-features__dismiss' ).click();
	assert.deepEqual( env.shown(), [ 'a2', 'a3', 'a4', 'a5' ] );
} );

test( 'dismissing everything shows the empty message with a reset link', ( t ) => {
	const env = setup( t, { count: 2 } );
	env.root.querySelector( '[data-slug="a1"] .lp-more-features__dismiss' ).click();
	env.root.querySelector( '[data-slug="a2"] .lp-more-features__dismiss' ).click();
	assert.deepEqual( env.shown(), [] );
	assert.equal( env.root.querySelector( '.lp-more-features__empty' ).hidden, false );
	env.click( '.lp-more-features__reset-link' );
	assert.deepEqual( env.shown(), [ 'a1', 'a2' ] );
	assert.equal( env.root.querySelector( '.lp-more-features__empty' ).hidden, true );
} );

test( 'reset clears storage and shows the first 5 again', ( t ) => {
	const env = setup( t );
	env.root.querySelector( '[data-slug="a1"] .lp-more-features__dismiss' ).click();
	env.click( '.lp-more-features__reset' );
	assert.equal( env.store.getItem( 'k' ), null );
	assert.deepEqual( env.shown(), [ 'a1', 'a2', 'a3', 'a4', 'a5' ] );
	assert.equal( env.root.querySelector( '.lp-more-features__reset' ).hidden, true );
} );

test( 'entries older than 30 days are dropped on load and removed from storage', ( t ) => {
	const env = setup( t, { stored: ( now ) => ( { a1: now - 31 * DAY, a2: now - 29 * DAY } ) } );
	assert.deepEqual( env.shown(), [ 'a1', 'a3', 'a4', 'a5', 'a6' ] );
	assert.deepEqual( Object.keys( JSON.parse( env.store.getItem( 'k' ) ) ), [ 'a2' ] );
} );

test( 'parseDismissed tolerates corrupt data (arrays, junk, bad values)', ( t ) => {
	const env = setup( t );
	const { parseDismissed } = env.api;
	assert.equal( JSON.stringify( parseDismissed( 'not json', 1, DAY ) ), '{}' );
	assert.equal( JSON.stringify( parseDismissed( '["a"]', 1, DAY ) ), '{}' );
	assert.equal( JSON.stringify( parseDismissed( 'null', 1, DAY ) ), '{}' );
	assert.equal( JSON.stringify( parseDismissed( '{"a":"x","b":null}', 1, DAY ) ), '{}' );
	assert.equal( JSON.stringify( parseDismissed( null, 1, DAY ) ), '{}' );
} );

test( 'works when localStorage is blocked (in-memory only)', ( t ) => {
	const env = setup( t, { brokenStorage: true } );
	env.root.querySelector( '[data-slug="a1"] .lp-more-features__dismiss' ).click();
	assert.deepEqual( env.shown(), [ 'a2', 'a3', 'a4', 'a5', 'a6' ] );
} );
