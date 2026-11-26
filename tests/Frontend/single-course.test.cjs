// Run: node --test tests/Frontend/single-course.test.cjs
const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

const read = ( file ) => fs.readFileSync( path.resolve( __dirname, '../../', file ), 'utf8' );
const withoutImports = ( source ) => source.replace( /import[\s\S]*?from '[^']+';/g, '' );

const retakeSend = ( data = {} ) => JSON.stringify( { id_url: 'user-retake-course', action: 'user_retake_course', course_id: 30, ...data } ).replace( /"/g, '&quot;' );

// Markup same as UserCourseTemplate::html_btn_retake.
const confirmAttrs = 'data-title="Retake course" data-content="Do you want to retake the course?" data-confirm-yes="Đồng ý" data-confirm-cancel="Hủy"';
const retakeHtml = ( send = retakeSend(), attrs = confirmAttrs ) => `
	<form name="lp-form-retake-course" class="lp-form-retake-course" method="post" data-confirm="Do you want to retake the course" data-lp-action-nonce="nonce-123">
		<input type="hidden" name="retake-course" value="30"/>
		<button type="submit" class="lp-button button-retake-course" ${ attrs } data-send="${ send }">Retake course (2)</button>
		<div class="lp-ajax-message"></div>
	</form>`;

function setup( t, html = retakeHtml(), options = {} ) {
	const dom = new JSDOM( html, { url: 'https://example.test/course/c/' } );
	t.after( () => dom.window.close() );

	const utils = read( 'assets/src/js/utils.js' );
	const events = utils.slice( utils.indexOf( 'export const eventHandlers =' ), utils.indexOf( '/**\n * Debounce' ) ).replace( 'export const', 'const' );
	const source = withoutImports( read( 'assets/src/js/frontend/single-course.js' ) )
		.replace( 'export class', 'class' )
		.split( 'const singleCourse = new SingleCourse();' )[ 0 ];

	const requests = [];
	const timeouts = [];
	const redirects = [];
	const confirmations = [];
	const confirmation = { result: { isConfirmed: options.confirmed ?? true } };
	const toasts = [];
	const hrefs = [];
	let elPopup = null;
	const context = {
		window: dom.window,
		document: dom.window.document,
		FormData: dom.window.FormData,
		JSON,
		lpToastify: { show: ( message, status ) => toasts.push( { message, status } ) },
		setTimeout: ( fn, ms ) => timeouts.push( { fn, ms } ),
		SweetAlert: {
			fire: ( opts ) => {
				confirmations.push( opts );
				// Popup HTML, preConfirm reads option chose.
				elPopup = dom.window.document.createElement( 'div' );
				elPopup.innerHTML = opts.html || '';
				if ( options.beforeConfirm ) {
					options.beforeConfirm( elPopup );
				}
				const result = { ...confirmation.result };
				if ( result.isConfirmed && opts.preConfirm ) {
					result.value = opts.preConfirm();
				}
				return Promise.resolve( result );
			},
			getPopup: () => elPopup,
		},
		lpUtils: {
			lpSetLoadingEl: ( el, status ) => el.classList.toggle( 'loading', !! status ),
		},
	};
	vm.createContext( context );
	vm.runInContext( `${ events }\nlpUtils.eventHandlers = eventHandlers;\n${ source }\nglobalThis.SingleCourse = SingleCourse;`, context );

	// Same window.lpAJAXG of loadAJAX.js (getDataSetCurrent removes action).
	dom.window.lpAJAXG = {
		fetchAJAX: ( data, callbacks ) => requests.push( { data, callbacks } ),
		getDataSetCurrent: ( elTarget ) => {
			const data = JSON.parse( elTarget.dataset.send );
			delete data.action;
			return data;
		},
		setDataSetCurrent: ( elTarget, data ) => ( elTarget.dataset.send = JSON.stringify( data ) ),
	};
	Object.defineProperty( dom.window, 'location', {
		value: {
			replace: ( url ) => redirects.push( url ),
			set href( url ) {
				hrefs.push( url );
			},
		},
		configurable: true,
	} );

	const singleCourse = new context.SingleCourse();
	singleCourse.init();

	const doc = dom.window.document;
	const elBtn = doc.querySelector( '.button-retake-course' );
	const elMessage = doc.querySelector( '.lp-ajax-message' );
	const click = ( el = elBtn ) => {
		const event = new dom.window.MouseEvent( 'click', { bubbles: true, cancelable: true } );
		el.dispatchEvent( event );
		return event;
	};

	// Wait SweetAlert promise resolved.
	const flush = () => new Promise( ( resolve ) => setImmediate( resolve ) );

	const submit = ( elForm, elSubmitter ) => {
		const event = new dom.window.Event( 'submit', { bubbles: true, cancelable: true } );
		event.submitter = elSubmitter;
		elForm.dispatchEvent( event );
		return event;
	};

	return { dom, doc, elBtn, elMessage, click, submit, flush, requests, timeouts, redirects, hrefs, toasts, confirmations, singleCourse };
}

test( 'click retake sends data-send with action nonce of form', async ( t ) => {
	const { click, flush, requests, elBtn } = setup( t );

	const event = click();
	await flush();

	assert.equal( event.defaultPrevented, true );
	assert.equal( requests.length, 1 );
	assert.deepEqual( { ...requests[ 0 ].data }, {
		id_url: 'user-retake-course',
		action: 'user_retake_course',
		course_id: 30,
		lp_action_nonce: 'nonce-123',
	} );
	assert.equal( elBtn.disabled, true );
	assert.equal( elBtn.classList.contains( 'loading' ), true );
} );

test( 'markup can not choose server action', async ( t ) => {
	const { click, flush, requests } = setup( t, retakeHtml( retakeSend( { action: 'user_submit_quiz' } ) ) );

	click();
	await flush();

	assert.equal( requests[ 0 ].data.action, 'user_retake_course' );
} );

test( 'success shows message, hides button and redirects', async ( t ) => {
	const { click, flush, requests, elBtn, elMessage, timeouts, redirects } = setup( t );

	click();
	await flush();
	const { callbacks } = requests[ 0 ];
	callbacks.success( { status: 'success', message: 'Now you can begin this course', data: { url_redirect: 'https://example.test/course/c/lessons/l/' } } );
	callbacks.completed();

	assert.equal( elMessage.textContent, 'Now you can begin this course' );
	assert.equal( elMessage.classList.contains( 'error' ), false );
	assert.equal( elBtn.style.display, 'none' );
	assert.equal( elBtn.disabled, true, 'keep disabled while redirecting' );
	assert.equal( elBtn.classList.contains( 'loading' ), false );
	assert.equal( timeouts.length, 1 );

	timeouts[ 0 ].fn();
	assert.deepEqual( redirects, [ 'https://example.test/course/c/lessons/l/' ] );
} );

test( 'error response shows message as text and enables button again', async ( t ) => {
	const { click, flush, requests, elBtn, elMessage, timeouts } = setup( t );

	click();
	await flush();
	requests[ 0 ].callbacks.success( { status: 'error', message: '<b>You can&#039;t retake this course!</b>' } );
	requests[ 0 ].callbacks.completed();

	// Message escaped by server: decode entities, strip tags.
	assert.equal( elMessage.textContent, "You can't retake this course!" );
	assert.equal( elMessage.querySelector( 'b' ), null );
	assert.equal( elMessage.classList.contains( 'error' ), true );
	assert.equal( elMessage.style.display, 'block' );
	assert.equal( elBtn.disabled, false );
	assert.equal( timeouts.length, 0 );
} );

test( 'request error shows message and enables button again', async ( t ) => {
	const { click, flush, requests, elBtn, elMessage } = setup( t );

	click();
	await flush();
	requests[ 0 ].callbacks.error( new Error( 'Network' ) );
	requests[ 0 ].callbacks.completed();

	assert.equal( elMessage.textContent, 'error: Network' );
	assert.equal( elBtn.disabled, false );
} );

test( 'does not send again while requesting', async ( t ) => {
	const { click, flush, requests, elBtn } = setup( t );

	click();
	await flush();
	elBtn.disabled = false;
	click();
	await flush();

	assert.equal( requests.length, 1 );
} );

test( 'submit form retake is prevented', ( t ) => {
	const { dom, doc } = setup( t );

	const event = new dom.window.Event( 'submit', { bubbles: true, cancelable: true } );
	doc.querySelector( '.lp-form-retake-course' ).dispatchEvent( event );

	assert.equal( event.defaultPrevented, true );
} );

test( 'button without data-send (old template) is ignored', async ( t ) => {
	const { click, flush, requests, doc } = setup( t );

	const elBtn = doc.querySelector( '.button-retake-course' );
	elBtn.removeAttribute( 'data-send' );
	click( elBtn );
	await flush();

	assert.equal( requests.length, 0 );
} );

test( 'confirm SweetAlert with title, content and buttons text from data attributes', async ( t ) => {
	const { click, flush, confirmations } = setup( t );

	click();
	await flush();

	assert.equal( confirmations.length, 1 );
	assert.equal( confirmations[ 0 ].title, 'Retake course' );
	assert.equal( confirmations[ 0 ].text, 'Do you want to retake the course?' );
	assert.equal( confirmations[ 0 ].icon, 'warning' );
	assert.equal( confirmations[ 0 ].confirmButtonText, 'Đồng ý' );
	assert.equal( confirmations[ 0 ].cancelButtonText, 'Hủy' );
} );

test( 'confirm uses default buttons text when button has not data-confirm-yes, data-confirm-cancel', async ( t ) => {
	const { click, flush, confirmations } = setup( t, retakeHtml( retakeSend(), 'data-title="Retake course" data-content="Do you want to retake the course?"' ) );

	click();
	await flush();

	assert.equal( confirmations[ 0 ].confirmButtonText, 'Yes' );
	assert.equal( confirmations[ 0 ].cancelButtonText, 'Cancel' );
} );

test( 'cancel confirm does not send request', async ( t ) => {
	const { click, flush, requests, confirmations, elBtn } = setup( t, retakeHtml(), { confirmed: false } );

	click();
	await flush();

	assert.equal( confirmations.length, 1 );
	assert.equal( requests.length, 0 );
	assert.equal( elBtn.disabled, false );
	assert.equal( elBtn.classList.contains( 'loading' ), false );
} );

test( 'button without data-title sends request without confirm', async ( t ) => {
	const { click, flush, requests, confirmations } = setup( t, retakeHtml( retakeSend(), '' ) );

	click();
	await flush();

	assert.equal( confirmations.length, 0 );
	assert.equal( requests.length, 1 );
} );

test( 'does not confirm again while requesting', async ( t ) => {
	const { click, flush, confirmations } = setup( t );

	click();
	await flush();
	click();
	await flush();

	assert.equal( confirmations.length, 1 );
} );

// Markup same as templates/single-course/extra-info.php.
const extraBox = ( title, { active = false, content = true } = {} ) => `
	<div class="course-extra-box${ active ? ' active' : '' }">
		<h3 class="course-extra-box__title"><span>${ title }</span></h3>
		${ content ? `<div class="course-extra-box__content"><div class="course-extra-box__content-inner"><ul><li>${ title } item</li></ul></div></div>` : '' }
	</div>`;
const extraBoxesHtml = extraBox( 'Requirements' ) + extraBox( 'Features' ) + extraBox( 'Target audiences' );

function setupExtraBoxes( t, html = extraBoxesHtml ) {
	const ctx = setup( t, html );
	const boxes = [ ...ctx.doc.querySelectorAll( '.course-extra-box' ) ];
	boxes.forEach( ( elBox, i ) => {
		const elContent = elBox.querySelector( '.course-extra-box__content' );
		if ( elContent ) {
			Object.defineProperty( elContent, 'scrollHeight', { value: 100 + i, configurable: true } );
		}
	} );
	const content = ( i ) => boxes[ i ].querySelector( '.course-extra-box__content' );
	const clickTitle = ( i ) => ctx.click( boxes[ i ].querySelector( '.course-extra-box__title span' ) );
	const transitionEnd = ( i ) => content( i ).dispatchEvent( new ctx.dom.window.Event( 'transitionend' ) );

	return { ...ctx, boxes, content, clickTitle, transitionEnd };
}

test( 'extra boxes are not changed by JS when load page (collapsed by CSS)', ( t ) => {
	const { boxes, content } = setupExtraBoxes( t, extraBox( 'Requirements', { active: true } ) + extraBox( 'Features' ) );

	assert.equal( boxes[ 0 ].classList.contains( 'active' ), true );
	boxes.forEach( ( elBox, i ) => {
		assert.equal( content( i ).style.height, '' );
	} );
} );

test( 'click title of box active (theme renders .active) collapses it', ( t ) => {
	const { boxes, content, clickTitle } = setupExtraBoxes( t, extraBox( 'Requirements', { active: true } ) + extraBox( 'Features' ) );

	clickTitle( 0 );

	assert.equal( boxes[ 0 ].classList.contains( 'active' ), false );
	assert.equal( content( 0 ).style.height, '0px' );
} );

test( 'click title expands box and collapses others', ( t ) => {
	const { boxes, content, clickTitle } = setupExtraBoxes( t );

	clickTitle( 0 );
	assert.equal( boxes[ 0 ].classList.contains( 'active' ), true );
	assert.equal( content( 0 ).style.height, '100px' );

	clickTitle( 1 );
	assert.equal( boxes[ 1 ].classList.contains( 'active' ), true );
	assert.equal( content( 1 ).style.height, '101px' );
	assert.equal( boxes[ 0 ].classList.contains( 'active' ), false );
	assert.equal( content( 0 ).style.height, '0px' );
} );

test( 'click title of opened box collapses it', ( t ) => {
	const { boxes, content, clickTitle } = setupExtraBoxes( t );

	clickTitle( 2 );
	clickTitle( 2 );

	assert.equal( boxes[ 2 ].classList.contains( 'active' ), false );
	assert.equal( content( 2 ).style.height, '0px' );
} );

test( 'expanded box height is auto after transition, collapse from auto', ( t ) => {
	const { content, clickTitle, transitionEnd } = setupExtraBoxes( t );

	clickTitle( 0 );
	transitionEnd( 0 );
	assert.equal( content( 0 ).style.height, 'auto' );

	clickTitle( 0 );
	assert.equal( content( 0 ).style.height, '0px' );
} );

test( 'transition end after collapse does not set height auto', ( t ) => {
	const { content, clickTitle, transitionEnd } = setupExtraBoxes( t );

	clickTitle( 0 );
	clickTitle( 0 );
	transitionEnd( 0 );

	assert.equal( content( 0 ).style.height, '0px' );
} );

test( 'box without content (theme override) does not break other boxes', ( t ) => {
	const { boxes, content, clickTitle } = setupExtraBoxes( t, extraBox( 'Broken', { content: false } ) + extraBox( 'Features' ) );

	clickTitle( 0 );
	assert.equal( boxes[ 0 ].classList.contains( 'active' ), true );

	clickTitle( 1 );
	assert.equal( boxes[ 1 ].classList.contains( 'active' ), true );
	assert.equal( content( 1 ).style.height, '101px' );
	assert.equal( boxes[ 0 ].classList.contains( 'active' ), false );
} );

// Markup same as SingleCourseTemplate::html_btn_enroll_course, html_btn_purchase_course.
const send = ( data ) => JSON.stringify( { course_id: 30, ...data } ).replace( /"/g, '&quot;' );
const enrollHtml = `
	<div class="course-detail-info"></div>
	<form name="enroll-course" class="enroll-course" method="post" data-lp-action-nonce="nonce-123">
		<input type="hidden" name="enroll-course" value="30"/>
		<button type="submit" class="lp-button button-enroll-course" data-send="${ send( { id_url: 'user-enroll-course', action: 'user_enroll_course' } ) }">Start Now</button>
	</form>`;
const purchaseHtml = `
	<form name="purchase-course" class="purchase-course " method="post" data-lp-action-nonce="nonce-123">
		<input type="hidden" name="purchase-course" value="30"/>
		<input type="hidden" name="addon_plan" value="gold"/>
		<input type="checkbox" name="extras" value="a" checked/>
		<input type="checkbox" name="extras" value="b" checked/>
		<button class="lp-button button-purchase-course" data-confirm-yes="Đồng ý" data-confirm-cancel="Hủy" data-send="${ send( { id_url: 'user-purchase-course', action: 'user_purchase_course' } ) }">Buy Now</button>
	</form>`;

test( 'submit form enroll sends action enroll with nonce', ( t ) => {
	const { doc, submit, requests } = setup( t, enrollHtml );
	const elBtn = doc.querySelector( '.button-enroll-course' );

	const event = submit( doc.querySelector( 'form.enroll-course' ), elBtn );

	assert.equal( event.defaultPrevented, true );
	assert.deepEqual( { ...requests[ 0 ].data }, {
		id_url: 'user-enroll-course',
		action: 'user_enroll_course',
		course_id: 30,
		lp_action_nonce: 'nonce-123',
	} );
	assert.equal( elBtn.disabled, true );
} );

test( 'enroll success shows decoded message, hides button and redirects', ( t ) => {
	const { doc, submit, requests, hrefs } = setup( t, enrollHtml );
	const elForm = doc.querySelector( 'form.enroll-course' );
	const elBtn = doc.querySelector( '.button-enroll-course' );

	submit( elForm, elBtn );
	requests[ 0 ].callbacks.success( { status: 'success', message: 'Congrats! You&#039;re enrolled', data: { redirect: 'https://example.test/course/c/lessons/l/' } } );
	requests[ 0 ].callbacks.completed();

	assert.equal( elBtn.style.display, 'none' );
	assert.equal( elBtn.disabled, true );
	assert.equal( elForm.querySelector( '.learn-press-message.success' ).textContent, "Congrats! You're enrolled" );
	assert.deepEqual( hrefs, [ 'https://example.test/course/c/lessons/l/' ] );
} );

test( 'enroll error shows toast (form has no .lp-ajax-message) and enables button', ( t ) => {
	const { doc, submit, requests, toasts, hrefs } = setup( t, enrollHtml );
	const elBtn = doc.querySelector( '.button-enroll-course' );

	submit( doc.querySelector( 'form.enroll-course' ), elBtn );
	requests[ 0 ].callbacks.success( { status: 'error', message: 'Course is out of stock' } );
	requests[ 0 ].callbacks.completed();

	assert.deepEqual( toasts, [ { message: 'Course is out of stock', status: 'error' } ] );
	assert.equal( elBtn.disabled, false );
	assert.equal( elBtn.style.display, '' );
	assert.deepEqual( hrefs, [] );
} );

test( 'form enroll without data-send (old template) is not prevented', ( t ) => {
	const { doc, submit, requests } = setup( t, enrollHtml );
	doc.querySelector( '.button-enroll-course' ).removeAttribute( 'data-send' );

	const event = submit( doc.querySelector( 'form.enroll-course' ) );

	assert.equal( event.defaultPrevented, false );
	assert.equal( requests.length, 0 );
} );

test( 'submit form purchase sends fields of form', ( t ) => {
	const { doc, submit, requests } = setup( t, purchaseHtml );

	submit( doc.querySelector( 'form.purchase-course' ) );

	const data = requests[ 0 ].data;
	assert.equal( data.action, 'user_purchase_course' );
	assert.equal( data.course_id, 30 );
	assert.equal( data.lp_action_nonce, 'nonce-123' );
	assert.equal( data.repurchase_type, '' );
	assert.deepEqual( JSON.parse( JSON.stringify( data.form_data ) ), {
		'purchase-course': '30',
		addon_plan: 'gold',
		extras: [ 'a', 'b' ],
	} );
} );

test( 'purchase markup can not choose server action', ( t ) => {
	const { doc, submit, requests } = setup( t, purchaseHtml.replace( 'user_purchase_course', 'user_retake_course' ) );

	submit( doc.querySelector( 'form.purchase-course' ) );

	assert.equal( requests[ 0 ].data.action, 'user_purchase_course' );
} );

test( 'purchase success redirects to checkout', ( t ) => {
	const { doc, submit, requests, hrefs } = setup( t, purchaseHtml );
	const elBtn = doc.querySelector( '.button-purchase-course' );

	submit( doc.querySelector( 'form.purchase-course' ) );
	requests[ 0 ].callbacks.success( { status: 'success', message: '&quot;PHP&quot; has been added to your cart.', data: { redirect: 'https://example.test/checkout/' } } );
	requests[ 0 ].callbacks.completed();

	assert.equal( doc.querySelector( '.learn-press-message.success' ).textContent, '"PHP" has been added to your cart.' );
	assert.equal( elBtn.style.display, 'none' );
	assert.deepEqual( hrefs, [ 'https://example.test/checkout/' ] );
} );

const repurchaseData = {
	type: 'allow_repurchase',
	title_popup: 'Repurchase Options',
	html: '<div class="lp_allow_repurchase_select"><ul><li><label><input name="_lp_allow_repurchase_type" value="reset" type="radio"/> Reset</label></li><li><label><input name="_lp_allow_repurchase_type" value="keep" type="radio" checked="checked"/> Keep</label></li></ul></div>',
};

test( 'purchase repurchase: popup options, send again with option chose', async ( t ) => {
	const { doc, submit, flush, requests, confirmations, hrefs } = setup( t, purchaseHtml, {
		beforeConfirm: ( elPopup ) => {
			elPopup.querySelector( '[value=reset]' ).checked = true;
		},
	} );
	const elBtn = doc.querySelector( '.button-purchase-course' );

	submit( doc.querySelector( 'form.purchase-course' ) );
	requests[ 0 ].callbacks.success( { status: 'success', message: '', data: repurchaseData } );
	requests[ 0 ].callbacks.completed();

	assert.equal( elBtn.disabled, false, 'button enabled while popup shown' );
	assert.equal( elBtn.style.display, '' );
	assert.equal( confirmations[ 0 ].title, 'Repurchase Options' );
	assert.equal( confirmations[ 0 ].confirmButtonText, 'Đồng ý' );
	assert.deepEqual( hrefs, [] );

	await flush();

	assert.equal( requests.length, 2 );
	assert.equal( requests[ 1 ].data.repurchase_type, 'reset' );
	assert.equal( requests[ 1 ].data.action, 'user_purchase_course' );
} );

test( 'purchase repurchase: close popup does not send again', async ( t ) => {
	const { doc, submit, flush, requests } = setup( t, purchaseHtml, { confirmed: false } );

	submit( doc.querySelector( 'form.purchase-course' ) );
	requests[ 0 ].callbacks.success( { status: 'success', message: '', data: repurchaseData } );
	requests[ 0 ].callbacks.completed();
	await flush();

	assert.equal( requests.length, 1 );
} );


// Markup same as CourseMaterialTemplate (content loaded via load_content_via_ajax).
const materialSend = ( paged = 1 ) => JSON.stringify( {
	action: 'load_content_via_ajax',
	args: { id_url: 'course-material', item_id: 30, item_type: 'lp_course', paged, per_page: 2, total_pages: 3 },
	callback: { class: 'LearnPress\\TemplateHooks\\Course\\CourseMaterialTemplate', method: 'render_material_items' },
} ).replace( /"/g, '&quot;' );
const materialHtml = ( paged = 1 ) => `
	<div class="lp-material-skeleton"><div class="lp-load-ajax-element">
		<div class="lp-target" data-send="${ materialSend( paged ) }">
			<table class="course-material-table"><tbody><tr><td>File 1</td></tr><tr><td>File 2</td></tr></tbody></table>
			<div class="lp-loadmore-material"><button class="lp-btn lp-loadmore-material">Load More</button></div>
		</div>
	</div></div>`;

test( 'material load more sends next page, without action of markup', ( t ) => {
	const { doc, click, requests } = setup( t, materialHtml() );
	const elBtn = doc.querySelector( 'button.lp-loadmore-material' );

	click( elBtn );

	assert.equal( requests.length, 1 );
	assert.equal( requests[ 0 ].data.action, undefined, 'request is load_content_via_ajax (default of fetchAJAX)' );
	assert.equal( requests[ 0 ].data.args.paged, 2 );
	assert.equal( elBtn.disabled, true );
	assert.equal( elBtn.classList.contains( 'loading' ), true );
} );

test( 'material load more appends rows and saves page', ( t ) => {
	const { doc, click, requests } = setup( t, materialHtml() );
	const elBtn = doc.querySelector( 'button.lp-loadmore-material' );

	click( elBtn );
	requests[ 0 ].callbacks.success( { status: 'success', data: { content: '<tr><td>File 3</td></tr><tr><td>File 4</td></tr>', paged: 2, total_pages: 3 } } );
	requests[ 0 ].callbacks.completed();

	assert.equal( doc.querySelectorAll( 'table.course-material-table tbody tr' ).length, 4 );
	assert.equal( JSON.parse( doc.querySelector( '.lp-target' ).dataset.send ).args.paged, 2 );
	assert.equal( elBtn.disabled, false );
	assert.equal( elBtn.classList.contains( 'loading' ), false );
	assert.ok( doc.querySelector( 'div.lp-loadmore-material' ), 'button kept, still has pages' );

	click( elBtn );
	assert.equal( requests[ 1 ].data.args.paged, 3 );
} );

test( 'material load more removes button block on last page', ( t ) => {
	const { doc, click, requests } = setup( t, materialHtml( 2 ) );

	click( doc.querySelector( 'button.lp-loadmore-material' ) );
	requests[ 0 ].callbacks.success( { status: 'success', data: { content: '<tr><td>File 5</td></tr>', paged: 3, total_pages: 3 } } );
	requests[ 0 ].callbacks.completed();

	assert.equal( doc.querySelector( '.lp-loadmore-material' ), null );
	assert.equal( doc.querySelectorAll( 'table.course-material-table tbody tr' ).length, 3 );
} );

test( 'material load more error shows toast and keeps page', ( t ) => {
	const { doc, click, requests, toasts } = setup( t, materialHtml() );
	const elBtn = doc.querySelector( 'button.lp-loadmore-material' );

	click( elBtn );
	requests[ 0 ].callbacks.success( { status: 'error', message: 'Item not found' } );
	requests[ 0 ].callbacks.completed();

	assert.deepEqual( toasts, [ { message: 'Item not found', status: 'error' } ] );
	assert.equal( JSON.parse( doc.querySelector( '.lp-target' ).dataset.send ).args.paged, 1 );
	assert.equal( elBtn.disabled, false );
} );

test( 'material load more does not send again while loading', ( t ) => {
	const { doc, click, requests } = setup( t, materialHtml() );
	const elBtn = doc.querySelector( 'button.lp-loadmore-material' );

	click( elBtn );
	click( elBtn );

	assert.equal( requests.length, 1 );
} );

test( 'click wrapper of load more (not button) does nothing', ( t ) => {
	const { doc, click, requests } = setup( t, materialHtml() );

	click( doc.querySelector( 'div.lp-loadmore-material' ) );

	assert.equal( requests.length, 0 );
} );

// Markup same as templates/single-course/tabs/tabs.php.
const tabsHtml = `
	<div id="learn-press-course-tabs" class="course-tabs">
		<input type="radio" name="learn-press-course-tab-radio" id="tab-overview-input" checked value="overview"/>
		<input type="radio" name="learn-press-course-tab-radio" id="tab-curriculum-input" value="curriculum"/>
		<input type="radio" name="learn-press-course-tab-radio" id="tab-instructor-input" value="instructor"/>
		<div class="wrapper-course-nav-tabs TabsDragScroll">
			<ul class="learn-press-nav-tabs course-nav-tabs" data-tabs="3">
				<li class="course-nav course-nav-tab-overview active"><label for="tab-overview-input">Overview</label></li>
				<li class="course-nav course-nav-tab-curriculum"><label for="tab-curriculum-input">Curriculum</label></li>
				<li class="course-nav course-nav-tab-instructor"><label for="tab-instructor-input">Instructor</label></li>
			</ul>
		</div>
		<div class="course-tab-panels"></div>
	</div>
	<input type="radio" name="other-radio" id="tab-other-input"/>`;

function chooseTab( dom, elRadio ) {
	elRadio.checked = true;
	elRadio.dispatchEvent( new dom.window.Event( 'change', { bubbles: true } ) );
}

const activeTabs = ( doc ) => [ ...doc.querySelectorAll( '.learn-press-nav-tabs li.active' ) ].map( ( el ) => el.textContent.trim() );

test( 'change tab sets active nav tab, removes active of others', ( t ) => {
	const { dom, doc } = setup( t, tabsHtml );

	chooseTab( dom, doc.getElementById( 'tab-curriculum-input' ) );
	assert.deepEqual( activeTabs( doc ), [ 'Curriculum' ] );

	chooseTab( dom, doc.getElementById( 'tab-instructor-input' ) );
	assert.deepEqual( activeTabs( doc ), [ 'Instructor' ] );
} );

test( 'change tab does not save cookie course-tab', ( t ) => {
	const { dom, doc } = setup( t, tabsHtml );

	chooseTab( dom, doc.getElementById( 'tab-curriculum-input' ) );

	assert.equal( dom.window.document.cookie, '' );
} );

test( 'change radio outside course tabs does nothing', ( t ) => {
	const { dom, doc } = setup( t, tabsHtml );

	chooseTab( dom, doc.getElementById( 'tab-other-input' ) );

	assert.deepEqual( activeTabs( doc ), [ 'Overview' ] );
} );

test( 'change tab without nav label keeps active nav tab', ( t ) => {
	const { dom, doc } = setup( t, tabsHtml.replace( '<label for="tab-instructor-input">Instructor</label>', 'Instructor' ) );

	chooseTab( dom, doc.getElementById( 'tab-instructor-input' ) );

	assert.deepEqual( activeTabs( doc ), [ 'Overview' ] );
} );

// Nav tabs drag scroll (templates/single-course/tabs/tabs.php).
const dragTabsHtml = `
	<div class="wrapper-course-nav-tabs TabsDragScroll">
		<ul class="learn-press-nav-tabs course-nav-tabs">
			<li class="course-nav"><label>Overview</label></li>
			<li class="course-nav"><label>Curriculum</label></li>
		</ul>
	</div>`;

function mouse( dom, el, type, movementX = 0 ) {
	const event = new dom.window.MouseEvent( type, { bubbles: true, cancelable: true } );
	Object.defineProperty( event, 'movementX', { value: movementX } );
	el.dispatchEvent( event );
}

test( 'drag nav tabs scrolls horizontal while mouse is down', ( t ) => {
	const { dom, doc } = setup( t, dragTabsHtml );
	const elMenu = doc.querySelector( '.TabsDragScroll ul' );
	elMenu.scrollLeft = 100;

	mouse( dom, elMenu, 'mousemove', -30 );
	assert.equal( elMenu.scrollLeft, 100, 'no scroll when mouse is not down' );

	mouse( dom, elMenu, 'mousedown' );
	mouse( dom, elMenu, 'mousemove', -30 );
	assert.equal( elMenu.scrollLeft, 130 );
	assert.equal( elMenu.classList.contains( 'dragging' ), true );

	mouse( dom, doc.body, 'mouseup' );
	assert.equal( elMenu.classList.contains( 'dragging' ), false );
	mouse( dom, elMenu, 'mousemove', -30 );
	assert.equal( elMenu.scrollLeft, 130, 'stop scroll after mouseup' );
} );

test( 'click nav tab scrolls it to center', ( t ) => {
	const { doc, click } = setup( t, dragTabsHtml );
	const elMenu = doc.querySelector( '.TabsDragScroll ul' );
	const elLi = elMenu.querySelectorAll( 'li' )[ 1 ];
	const scrolls = [];
	elMenu.scrollTo = ( opts ) => scrolls.push( opts );
	Object.defineProperty( elMenu, 'clientWidth', { value: 300 } );
	Object.defineProperty( elLi, 'offsetLeft', { value: 400 } );
	Object.defineProperty( elLi, 'offsetWidth', { value: 100 } );

	click( elLi.querySelector( 'label' ) );

	assert.deepEqual( JSON.parse( JSON.stringify( scrolls ) ), [ { left: 300, behavior: 'smooth' } ] );
} );

// Social share, copy link (SingleCourseModernLayout::html_share).
const shareHtml = `
	<div class="social-swapper social-share-toggle">
		<div class="share-toggle-icon"><i class="lp-icon-share-alt"></i><label class="share-label">Share</label></div>
		<div class="wrapper-content-widget"><div class="content-widget-social-share">
			<div class="clipboard-post">
				<input class="clipboard-value" type="text" value="https://example.test/course/c/">
				<button class="btn-clipboard" data-copied="Copied!">Copy<span class="tooltip">Copy to Clipboard</span></button>
			</div>
			<ul class="lp-social-media"><li>Facebook</li></ul>
		</div></div>
	</div>
	<p class="outside">Outside</p>`;

test( 'click share icon toggles social share', ( t ) => {
	const { doc, click } = setup( t, shareHtml );
	const elToggle = doc.querySelector( '.social-share-toggle' );

	click( doc.querySelector( '.share-label' ) );
	assert.equal( elToggle.classList.contains( 'social-share-toggle__open' ), true );

	click( doc.querySelector( '.share-toggle-icon' ) );
	assert.equal( elToggle.classList.contains( 'social-share-toggle__open' ), false );
} );

test( 'click inside share content keeps open, click outside closes', ( t ) => {
	const { doc, click } = setup( t, shareHtml );
	const elToggle = doc.querySelector( '.social-share-toggle' );

	click( doc.querySelector( '.share-toggle-icon' ) );
	click( doc.querySelector( '.lp-social-media li' ) );
	assert.equal( elToggle.classList.contains( 'social-share-toggle__open' ), true );

	click( doc.querySelector( '.outside' ) );
	assert.equal( elToggle.classList.contains( 'social-share-toggle__open' ), false );
} );

test( 'press Escape closes social share', ( t ) => {
	const { dom, doc, click } = setup( t, shareHtml );
	const elToggle = doc.querySelector( '.social-share-toggle' );

	click( doc.querySelector( '.share-toggle-icon' ) );
	doc.dispatchEvent( new dom.window.KeyboardEvent( 'keydown', { key: 'Escape', bubbles: true } ) );

	assert.equal( elToggle.classList.contains( 'social-share-toggle__open' ), false );
} );

test( 'copy link by execCommand shows copied text', ( t ) => {
	const { doc, click } = setup( t, shareHtml );
	const commands = [];
	doc.execCommand = ( command ) => {
		commands.push( [ command, doc.activeElement.value ] );
		return true;
	};
	const elBtn = doc.querySelector( '.btn-clipboard' );

	click( elBtn );

	assert.deepEqual( commands, [ [ 'copy', 'https://example.test/course/c/' ] ] );
	assert.equal( elBtn.childNodes[ 0 ].textContent, 'Copied!' );
	assert.equal( elBtn.querySelector( '.tooltip' ).textContent, 'Copied!' );
} );

test( 'copy link failed does not show copied text', ( t ) => {
	const { doc, click } = setup( t, shareHtml );
	doc.execCommand = () => false;
	const elBtn = doc.querySelector( '.btn-clipboard' );

	click( elBtn );

	assert.equal( elBtn.querySelector( '.tooltip' ).textContent, 'Copy to Clipboard' );
} );

test( 'copy link by Clipboard API on secure context', async ( t ) => {
	const { dom, doc, click, flush } = setup( t, shareHtml );
	const written = [];
	Object.defineProperty( dom.window, 'isSecureContext', { value: true, configurable: true } );
	Object.defineProperty( dom.window.navigator, 'clipboard', {
		value: { writeText: ( text ) => {
			written.push( text );
			return Promise.resolve();
		} },
		configurable: true,
	} );
	doc.execCommand = () => assert.fail( 'execCommand must not be called' );
	const elBtn = doc.querySelector( '.btn-clipboard' );

	click( elBtn );
	await flush();

	assert.deepEqual( written, [ 'https://example.test/course/c/' ] );
	assert.equal( elBtn.querySelector( '.tooltip' ).textContent, 'Copied!' );
} );

test( 'copied text from data-copied is not HTML', ( t ) => {
	const { doc, click } = setup( t, shareHtml.replace( 'data-copied="Copied!"', 'data-copied="&lt;img src=x&gt;"' ) );
	doc.execCommand = () => true;
	const elBtn = doc.querySelector( '.btn-clipboard' );

	click( elBtn );

	assert.equal( elBtn.querySelector( 'img' ), null );
	assert.equal( elBtn.querySelector( '.tooltip' ).textContent, '<img src=x>' );
} );
