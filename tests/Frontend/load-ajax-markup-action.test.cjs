// Run: node --test tests/Frontend/load-ajax-markup-action.test.cjs
// Security: markup (ex: Contributor post content) with .lp-load-ajax-element / .lp-target
// must never choose the server action, only 'load_content_via_ajax' is allowed.
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

const jsDir = path.resolve( __dirname, '../../assets/src/js' );

const utilsRaw = fs.readFileSync( path.join( jsDir, 'utils.js' ), 'utf8' );
const utilsNames = [ ...utilsRaw.matchAll( /^export const (\w+)/gm ) ].map( ( m ) => m[ 1 ] );
const utilsSource = `${ utilsRaw.replace( /^export const/gm, 'const' ) }\nglobalThis.lpUtils = { ${ utilsNames.join( ', ' ) } };`;

const loadAjaxSource = fs
	.readFileSync( path.join( jsDir, 'loadAJAX.js' ), 'utf8' )
	.replace( /^import .*$/m, '' )
	.replace( /^export default .*$/m, '' );

const attr = ( obj ) => JSON.stringify( obj ).replace( /"/g, '&quot;' );

function setup( t, html ) {
	const dom = new JSDOM( html, { url: 'http://localhost/?p=13&preview=true' } );
	t.after( () => dom.window.close() );

	const calls = [];
	const fetchMock = ( url, options = {} ) => {
		calls.push( { url: String( url ), options } );
		return new Promise( () => {} ); // Never resolve, only inspect request.
	};

	const context = {
		window: dom.window,
		document: dom.window.document,
		lpData: {
			user_id: 1,
			nonce: 'adminnonce',
			lpAjaxUrl: 'http://localhost/lp-ajax-handle/',
			ajaxUrl: 'http://localhost/wp-admin/admin-ajax.php',
			urlParams: {},
		},
		fetch: fetchMock,
		FormData: dom.window.FormData,
		URL,
		Promise,
		JSON,
		Object,
		String,
		Array,
		parseInt,
		RegExp,
		console: { log: () => {} },
		MutationObserver: dom.window.MutationObserver,
		setTimeout,
	};
	context.globalThis = context;
	vm.createContext( context );
	vm.runInContext( utilsSource, context );
	vm.runInContext( loadAjaxSource, context );

	return { context, calls };
}

test( 'auto-load ignores action from data-send markup', ( t ) => {
	const payload = {
		action: 'addon_action',
		action_type: 'deactivate',
		addon: { basename: 'akismet/akismet.php', name: 'Akismet' },
	};
	const { calls } = setup(
		t,
		`<div class="entry-content"><div class="lp-load-ajax-element"><div class="lp-target" data-send="${ attr( payload ) }"></div></div></div>`
	);

	assert.ok( calls.length >= 1 );
	for ( const call of calls ) {
		const body = call.options.body;
		assert.equal( body.get( 'lp-load-ajax' ), 'load_content_via_ajax' );
		assert.equal( JSON.parse( body.get( 'data' ) ).action, 'load_content_via_ajax' );
	}
} );

test( 'auto-load still works for server rendered container (no action)', ( t ) => {
	const payload = { args: { id_url: 'x' }, callback: { class: 'A', method: 'b' }, id: 'lp-target-1' };
	const { calls } = setup(
		t,
		`<div class="lp-load-ajax-element"><div class="lp-target" data-send="${ attr( payload ) }"></div></div>`
	);

	assert.equal( calls.length, 1 );
	assert.equal( calls[ 0 ].options.body.get( 'lp-load-ajax' ), 'load_content_via_ajax' );
	assert.deepEqual( JSON.parse( calls[ 0 ].options.body.get( 'data' ) ).callback, payload.callback );
} );

test( 'pagination click ignores action from data-send markup', ( t ) => {
	const payload = { action: 'addon_action', action_type: 'activate', args: { paged: 1, enableScrollToView: false, enableUpdateParamsUrl: false } };
	const { context, calls } = setup(
		t,
		`<div class="lp-target" data-send="${ attr( payload ) }"><a class="page-numbers next" href="#">Next</a></div>`
	);

	context.document.querySelector( 'a.page-numbers' ).click();

	assert.equal( calls.length, 1 );
	assert.equal( calls[ 0 ].options.body.get( 'lp-load-ajax' ), 'load_content_via_ajax' );
	assert.equal( JSON.parse( calls[ 0 ].options.body.get( 'data' ) ).action, 'load_content_via_ajax' );
} );

test( 'getDataSetCurrent strips action', ( t ) => {
	const { context } = setup(
		t,
		`<div class="lp-target" data-send="${ attr( { action: 'addon_action', args: {} } ) }"></div>`
	);

	const data = context.window.lpAJAXG.getDataSetCurrent( context.document.querySelector( '.lp-target' ) );
	assert.equal( data.action, undefined );
} );

test( 'courses-v2 load more ignores action from data-send markup', ( t ) => {
	const payload = { action: 'addon_action', action_type: 'deactivate', args: { paged: 1 } };
	const { context, calls } = setup(
		t,
		`<div class="lp-target" data-send="${ attr( payload ) }"><button class="courses-btn-load-more-no-css">More<span class="lp-loading-no-css"></span></button></div>`
	);

	context.IntersectionObserver = class {
		observe() {}
	};
	const coursesSource = fs
		.readFileSync( path.join( jsDir, 'frontend/courses-v2.js' ), 'utf8' )
		.replace( /^import API from .*$/m, 'const API = {};' )
		.replace( /^import \{[\s\S]*?\} from '\.\.\/utils\.js';$/m, '' );
	vm.runInContext( coursesSource, context );

	context.document.querySelector( '.courses-btn-load-more-no-css' ).click();

	assert.equal( calls.length, 1 );
	assert.equal( calls[ 0 ].options.body.get( 'lp-load-ajax' ), 'load_content_via_ajax' );
	assert.equal( JSON.parse( calls[ 0 ].options.body.get( 'data' ) ).action, 'load_content_via_ajax' );
} );
