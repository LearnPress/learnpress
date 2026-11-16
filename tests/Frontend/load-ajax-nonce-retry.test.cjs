// Run: node --test tests/Frontend/load-ajax-nonce-retry.test.cjs
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

const jsDir = path.resolve( __dirname, '../../assets/src/js' );

// utils.js has no import: strip "export" and expose all exported names.
const utilsRaw = fs.readFileSync( path.join( jsDir, 'utils.js' ), 'utf8' );
const utilsNames = [ ...utilsRaw.matchAll( /^export const (\w+)/gm ) ].map( ( m ) => m[ 1 ] );
const utilsSource = `${ utilsRaw.replace( /^export const/gm, 'const' ) }\nglobalThis.lpUtils = { ${ utilsNames.join( ', ' ) } };`;

const loadAjaxSource = fs
	.readFileSync( path.join( jsDir, 'loadAJAX.js' ), 'utf8' )
	.replace( /^import .*$/m, '' )
	.replace( /^export default .*$/m, '' );

const jsonResponse = ( body, headers = {} ) => ( {
	ok: true,
	headers: { get: ( name ) => headers[ name ] ?? null },
	json: () => Promise.resolve( body ),
	text: () => Promise.resolve( '' ),
} );

/**
 * @param {Array} queue Responses returned by fetch in order, function(url) => response.
 */
function setup( t, { userId = 5, ajaxUrl = 'http://localhost/wp-admin/admin-ajax.php', queue } ) {
	const dom = new JSDOM( '<div></div>', { url: 'http://localhost/courses/' } );
	t.after( () => dom.window.close() );

	const calls = [];
	const fetchMock = ( url, options = {} ) => {
		calls.push( { url: String( url ), options } );
		const next = queue.shift();
		return Promise.resolve( typeof next === 'function' ? next( String( url ), options ) : next );
	};

	const context = {
		window: dom.window,
		document: dom.window.document,
		lpData: {
			user_id: userId,
			nonce: 'oldnonce01',
			lpAjaxUrl: 'http://localhost/lp-ajax/',
			ajaxUrl,
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
		console,
		MutationObserver: dom.window.MutationObserver,
		setTimeout,
	};
	context.globalThis = context;
	vm.createContext( context );
	vm.runInContext( utilsSource, context );
	vm.runInContext( loadAjaxSource, context );

	return { context, calls };
}

const run = ( context, params ) =>
	new Promise( ( resolve ) => {
		const result = {};
		context.window.lpAJAXG.fetchAJAX( params, {
			success: ( response ) => ( result.success = response ),
			error: ( error ) => ( result.error = error ),
			completed: () => resolve( result ),
		} );
	} );

const nonceOf = ( call ) => {
	const body = call.options.body;
	return body ? body.get( 'nonce' ) : new URL( call.url ).searchParams.get( 'nonce' );
};

test( 'valid nonce: one request only', async ( t ) => {
	const { context, calls } = setup( t, { queue: [ jsonResponse( { status: 'success' } ) ] } );

	const result = await run( context, { args: {}, callback: {} } );

	assert.equal( calls.length, 1 );
	assert.equal( result.success.status, 'success' );
} );

test( 'nonce invalid: refresh nonce then retry once with new nonce', async ( t ) => {
	const { context, calls } = setup( t, {
		queue: [
			jsonResponse( {}, { 'X-LP-Nonce-Invalid': '1' } ),
			{ ok: true, text: () => Promise.resolve( 'abcdef0123\n' ) },
			jsonResponse( { status: 'success', data: 'retried' } ),
		],
	} );

	const result = await run( context, { args: {}, callback: {} } );

	assert.equal( calls.length, 3 );
	assert.equal( nonceOf( calls[ 0 ] ), 'oldnonce01' );
	assert.match( calls[ 1 ].url, /action=rest-nonce/ );
	assert.equal( nonceOf( calls[ 2 ] ), 'abcdef0123' );
	assert.equal( calls[ 2 ].options.headers[ 'X-WP-Nonce' ], 'abcdef0123' );
	assert.equal( result.success.data, 'retried' );
	assert.equal( context.lpData.nonce, 'abcdef0123' );
} );

test( 'retry also works for GET request (nonce in URL)', async ( t ) => {
	const { context, calls } = setup( t, {
		queue: [
			jsonResponse( {}, { 'X-LP-Nonce-Invalid': '1' } ),
			{ ok: true, text: () => Promise.resolve( 'abcdef0123' ) },
			jsonResponse( { status: 'success' } ),
		],
	} );

	await run( context, { args: {}, callback: {}, method_request: 'GET' } );

	assert.equal( nonceOf( calls[ 0 ] ), 'oldnonce01' );
	assert.equal( nonceOf( calls[ 2 ] ), 'abcdef0123' );
	assert.equal( calls[ 2 ].options.method, 'GET' );
} );

test( 'refresh returns "0" (not logged in): no retry, original response used', async ( t ) => {
	const { context, calls } = setup( t, {
		queue: [
			jsonResponse( { status: 'error', message: 'invalid' }, { 'X-LP-Nonce-Invalid': '1' } ),
			{ ok: true, text: () => Promise.resolve( '0' ) },
		],
	} );

	const result = await run( context, { args: {}, callback: {} } );

	assert.equal( calls.length, 2 );
	assert.equal( result.success.message, 'invalid' );
	assert.equal( context.lpData.nonce, 'oldnonce01' );
} );

test( 'no ajaxUrl (settings missing): no refresh request, original response used', async ( t ) => {
	const { context, calls } = setup( t, {
		ajaxUrl: '',
		queue: [ jsonResponse( { status: 'error' }, { 'X-LP-Nonce-Invalid': '1' } ) ],
	} );

	const result = await run( context, { args: {}, callback: {} } );

	assert.equal( calls.length, 1 );
	assert.equal( result.success.status, 'error' );
} );

test( 'nonce still invalid after retry: stop, no loop', async ( t ) => {
	const { context, calls } = setup( t, {
		queue: [
			jsonResponse( {}, { 'X-LP-Nonce-Invalid': '1' } ),
			{ ok: true, text: () => Promise.resolve( 'abcdef0123' ) },
			jsonResponse( { status: 'error' }, { 'X-LP-Nonce-Invalid': '1' } ),
		],
	} );

	await run( context, { args: {}, callback: {} } );

	assert.equal( calls.length, 3 );
} );

test( 'many blocks fail at the same time: share one refresh request', async ( t ) => {
	const invalid = () => jsonResponse( {}, { 'X-LP-Nonce-Invalid': '1' } );
	const ok = () => jsonResponse( { status: 'success' } );
	const { context, calls } = setup( t, {
		queue: [
			invalid(),
			invalid(),
			{ ok: true, text: () => Promise.resolve( 'abcdef0123' ) },
			ok(),
			ok(),
		],
	} );

	await Promise.all( [
		run( context, { args: {}, callback: {} } ),
		run( context, { args: {}, callback: {} } ),
	] );

	const refreshCalls = calls.filter( ( c ) => /action=rest-nonce/.test( c.url ) );
	assert.equal( refreshCalls.length, 1 );
	assert.equal( calls.length, 5 );
} );
