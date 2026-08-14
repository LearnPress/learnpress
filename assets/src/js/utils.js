/**
 * Utils functions
 *
 * @param url
 * @param data
 * @param functions
 * @since 4.2.5.1
 * @version 1.0.7
 */
export const lpClassName = {
	hidden: 'lp-hidden',
	loading: 'loading',
	elCollapse: 'lp-collapse',
	elSectionToggle: '.lp-section-toggle',
	elTriggerToggle: '.lp-trigger-toggle',
	elBtnFullScreen: '.lp-btn-full-screen-view',
	elFullScreen: 'lp-full-screen-view',
	elBtnFullScreenClose: 'lp-full-screen-view__close',
};

export const lpFetchAPI = ( url, data = {}, functions = {} ) => {
	if ( 'function' === typeof functions.before ) {
		functions.before();
	}

	fetch( url, { method: 'GET', ...data } )
		.then( ( response ) => response.json() )
		.then( ( response ) => {
			if ( 'function' === typeof functions.success ) {
				functions.success( response );
			}
		} )
		.catch( ( err ) => {
			if ( 'function' === typeof functions.error ) {
				functions.error( err );
			}
		} )
		.finally( () => {
			if ( 'function' === typeof functions.completed ) {
				functions.completed();
			}
		} );
};

/**
 * Get current URL without params.
 *
 * @since 4.2.5.1
 */
export const lpGetCurrentURLNoParam = () => {
	let currentUrl = window.location.href;
	const hasParams = currentUrl.includes( '?' );
	if ( hasParams ) {
		currentUrl = currentUrl.split( '?' )[ 0 ];
	}

	return currentUrl;
};

export const lpAddQueryArgs = ( endpoint, args ) => {
	const url = new URL( endpoint );

	Object.keys( args ).forEach( ( arg ) => {
		url.searchParams.set( arg, args[ arg ] );
	} );

	return url;
};

/**
 * Listen element viewed.
 *
 * @param el
 * @param callback
 * @since 4.2.5.8
 */
export const listenElementViewed = ( el, callback ) => {
	const observerSeeItem = new IntersectionObserver( function ( entries ) {
		for ( const entry of entries ) {
			if ( entry.isIntersecting ) {
				callback( entry );
			}
		}
	} );

	observerSeeItem.observe( el );
};

/**
 * Listen element created.
 *
 * @param callback
 * @since 4.2.5.8
 */
export const listenElementCreated = ( callback ) => {
	const observerCreateItem = new MutationObserver( function ( mutations ) {
		mutations.forEach( function ( mutation ) {
			if ( mutation.addedNodes ) {
				mutation.addedNodes.forEach( function ( node ) {
					if ( node.nodeType === 1 ) {
						callback( node );
					}
				} );
			}
		} );
	} );

	observerCreateItem.observe( document, { childList: true, subtree: true } );
	// End.
};

/**
 * Listen element created.
 *
 * @param selector
 * @param callback
 * @since 4.2.7.1
 */
export const lpOnElementReady = ( selector, callback ) => {
	const element = document.querySelector( selector );
	if ( element ) {
		callback( element );
		return;
	}

	const observer = new MutationObserver( ( mutations, obs ) => {
		const element = document.querySelector( selector );
		if ( element ) {
			obs.disconnect();
			callback( element );
		}
	} );

	observer.observe( document.documentElement, {
		childList: true,
		subtree: true,
	} );
};

// Parse JSON from string with content include LP_AJAX_START.
export const lpAjaxParseJsonOld = ( data ) => {
	if ( typeof data !== 'string' ) {
		return data;
	}

	const m = String.raw( { raw: data } ).match(
		/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/s
	);

	try {
		if ( m ) {
			data = JSON.parse( m[ 1 ].replace( /(?:\r\n|\r|\n)/g, '' ) );
		} else {
			data = JSON.parse( data );
		}
	} catch ( e ) {
		data = {};
	}

	return data;
};

// status 0: hide, 1: show
export const lpShowHideEl = ( el, status = 0 ) => {
	if ( ! el ) {
		return;
	}

	if ( ! status ) {
		el.classList.add( lpClassName.hidden );
	} else {
		el.classList.remove( lpClassName.hidden );
	}
};

// status 0: hide, 1: show
export const lpSetLoadingEl = ( el, status ) => {
	if ( ! el ) {
		return;
	}

	if ( ! status ) {
		el.classList.remove( lpClassName.loading );
	} else {
		el.classList.add( lpClassName.loading );
	}
};

// Toggle collapse section
export const toggleCollapse = (
	e,
	target,
	elTriggerClassName = '',
	elsExclude = [],
	callback
) => {
	if ( ! elTriggerClassName ) {
		elTriggerClassName = lpClassName.elTriggerToggle;
	}

	// Exclude elements, which should not trigger the collapse toggle
	if ( elsExclude && elsExclude.length > 0 ) {
		for ( const elExclude of elsExclude ) {
			if ( target.closest( elExclude ) ) {
				return;
			}
		}
	}

	const elTrigger = target.closest( elTriggerClassName );
	if ( ! elTrigger ) {
		return;
	}

	//console.log( 'elTrigger', elTrigger );

	const elSectionToggle = elTrigger.closest(
		`${ lpClassName.elSectionToggle }`
	);
	if ( ! elSectionToggle ) {
		return;
	}

	elSectionToggle.classList.toggle( `${ lpClassName.elCollapse }` );

	if ( 'function' === typeof callback ) {
		callback( elSectionToggle );
	}
};

// Get data of form
export const getDataOfForm = ( form ) => {
	const dataSend = {};
	const formData = new FormData( form );
	for ( const pair of formData.entries() ) {
		const key = pair[ 0 ];
		const value = formData.getAll( key );
		if ( ! dataSend.hasOwnProperty( key ) ) {
			// Convert value array to string.
			dataSend[ key ] = value.join( ',' );
		}
	}

	return dataSend;
};

// Get field keys of form
export const getFieldKeysOfForm = ( form ) => {
	const keys = [];
	const elements = form.elements;
	for ( let i = 0; i < elements.length; i++ ) {
		const name = elements[ i ].name;
		if ( name && ! keys.includes( name ) ) {
			keys.push( name );
		}
	}
	return keys;
};

// Merge data handle with data form.
export const mergeDataWithDatForm = ( elForm, dataHandle ) => {
	const dataForm = getDataOfForm( elForm );
	const keys = getFieldKeysOfForm( elForm );
	keys.forEach( ( key ) => {
		if ( ! dataForm.hasOwnProperty( key ) ) {
			delete dataHandle[ key ];
		} else if ( dataForm[ key ][ 0 ] === '' ) {
			delete dataForm[ key ];
			delete dataHandle[ key ];
		}
	} );

	dataHandle = { ...dataHandle, ...dataForm };

	return dataHandle;
};

/**
 * Event trigger
 * For each list of event handlers, listen event on document.
 *
 * eventName: 'click', 'change', ...
 * eventHandlers = [ { selector: '.lp-button', callBack: function(){}, class: object } ]
 *
 * @param eventName
 * @param eventHandlers
 */
export const eventHandlers = ( eventName, eventHandlers ) => {
	document.addEventListener( eventName, ( e ) => {
		const target = e.target;
		let args = {
			e,
			target,
		};

		eventHandlers.forEach( ( eventHandler ) => {
			args = { ...args, ...eventHandler };

			//console.log( args );

			// Check condition before call back
			if ( eventHandler.conditionBeforeCallBack ) {
				if ( eventHandler.conditionBeforeCallBack( args ) !== true ) {
					return;
				}
			}

			// Special check for keydown event with checkIsEventEnter = true
			if ( eventName === 'keydown' && eventHandler.checkIsEventEnter ) {
				if ( e.key !== 'Enter' ) {
					return;
				}
			}

			if ( target.closest( eventHandler.selector ) ) {
				if ( eventHandler.class ) {
					// Call method of class, function callBack will understand exactly {this} is class object.
					eventHandler.class[ eventHandler.callBack ]( args );
				} else {
					// For send args is objected, {this} is eventHandler object, not class object.
					eventHandler.callBack( args );
				}
			}
		} );
	} );
};

/**
 * Debounce - delays function execution until after `wait` ms of inactivity.
 *
 * Each call resets the timer. Only the last call in a burst executes.
 *
 * USE CASES:
 * - Search inputs, form validation, window resize
 * - Multiple elements need independent timers
 * - When you need to call with different arguments
 *
 * EXAMPLES:
 * const debouncedSearch = debounce( (query) => fetchResults(query), 300 );
 * searchInput.addEventListener('input', (e) => debouncedSearch(e.target.value));
 *
 * const debouncedResize = debounce( recalculateLayout, 250 );
 * window.addEventListener('resize', debouncedResize);
 *
 * ⚠️ Create ONCE outside event handlers, not inside.
 *
 * @param {Function} func - Function to debounce (can be anonymous)
 * @param {number}   wait - Milliseconds to wait (default: 500)
 * @return {Function} Debounced wrapper function
 * @since 4.3.7
 * @version 1.0.0
 */
export const debounce = ( func, wait = 500 ) => {
	let timer;

	return ( args ) => {
		clearTimeout( timer );
		timer = setTimeout( () => func( args ), wait );
	};
};

/**
 * Initialize lp-toggle-enable components.
 *
 * Finds all `.lp-toggle-enable` elements and wires up toggle behavior.
 * Reads initial state from `data-enabled` attribute ("true"/"false").
 * Calls `data-on-toggle` callback (if provided via options) on state change.
 *
 * HTML structure:
 * <label class="lp-toggle-enable" data-enabled="true">
 *   <input type="checkbox" class="lp-toggle-enable__input" />
 *   <span class="lp-toggle-enable__track"></span>
 * </label>
 *
 * @param {string}   selector CSS selector for toggle elements (default: '.lp-toggle-enable')
 * @param {Function} onToggle Optional callback( el, isEnabled ) called on state change
 * @since 4.4.5
 * @version 1.0.0
 */
window.lpToggleEnableInit = 0;
export const toggleEnable = ( onToggle = null ) => {
	if ( window.lpToggleEnableInit ) {
		return;
	}
	window.lpToggleEnableInit = 1;

	const selector = '.lp-toggle-enable';

	const updateUI = ( toggle, isEnabled ) => {
		toggle.classList.toggle( 'is-enabled', isEnabled );
		const input = toggle.querySelector( '.lp-toggle-enable__input' );
		if ( input ) {
			input.checked = isEnabled;
			input.value = isEnabled ? '1' : '0';
		}
	};

	// Delegate click handling via eventHandlers.
	eventHandlers( 'click', [
		{
			selector,
			callBack: ( args ) => {
				const { e, target } = args;
				const toggle = target.closest( selector );

				if ( ! toggle || toggle.classList.contains( 'is-disabled' ) ) {
					return;
				}

				e.preventDefault();

				const isEnabled = ! toggle.classList.contains( 'is-enabled' );
				updateUI( toggle, isEnabled );

				if ( 'function' === typeof onToggle ) {
					onToggle( toggle, isEnabled );
				}
			},
		},
	] );
};

/**
 * Initialize custom fullscreen view buttons.
 *
 * Delegates clicks on `.lp-btn-full-screen-view` buttons to
 * `lpToggleFullscreenView`. Reads the `data-target` attribute to find the
 * target element. Falls back to the button's parent element when
 * `data-target` is not provided.
 *
 * @since 4.4.5
 * @version 1.0.0
 */
window.lpFullScreenViewInit = 0;
export const fullScreenView = () => {
	if ( window.lpFullScreenViewInit ) {
		return;
	}
	window.lpFullScreenViewInit = 1;

	let lastScrollY = 0;

	const lpToggleFullscreenView = ( elTarget, elBtnFullScreen = null ) => {
		const isFullscreen = elTarget.classList.contains(
			lpClassName.elFullScreen
		);

		if ( isFullscreen ) {
			elTarget.classList.remove( lpClassName.elFullScreen );
			document.documentElement.classList.remove(
				'lp-full-screen-active'
			);
			window.scrollTo( 0, lastScrollY );
		} else {
			lastScrollY = window.scrollY;
			elTarget.classList.add( lpClassName.elFullScreen );
			document.documentElement.classList.add( 'lp-full-screen-active' );
		}

		if ( ! isFullscreen ) {
			if (
				! elTarget.querySelector(
					`.${ lpClassName.elBtnFullScreenClose }`
				)
			) {
				const closeButton = document.createElement( 'button' );
				closeButton.type = 'button';
				closeButton.className = lpClassName.elBtnFullScreenClose;
				closeButton.setAttribute( 'aria-label', 'Close' );
				closeButton.innerHTML =
					lpData.i18n.closeButtonFullScreen || 'Close &times;';

				closeButton.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					lpToggleFullscreenView( elTarget );
				} );

				elTarget.appendChild( closeButton );
			}
		} else {
			const closeButton = elTarget.querySelector(
				`.${ lpClassName.elBtnFullScreenClose }`
			);
			if ( closeButton ) {
				closeButton.remove();
			}
		}
	};

	eventHandlers( 'click', [
		{
			selector: lpClassName.elBtnFullScreen,
			callBack: ( args ) => {
				const { e, target } = args;
				const elBtnFullScreen = target.closest(
					lpClassName.elBtnFullScreen
				);

				if ( ! elBtnFullScreen ) {
					console.log( 'No full screen button found' );
					return;
				}

				e.preventDefault();

				let elTarget = null;
				const targetSelector = elBtnFullScreen.dataset.targetFullscreen;
				console.log( targetSelector );
				if ( targetSelector ) {
					elTarget = document.querySelector( targetSelector );
				}

				if ( ! elTarget ) {
					console.log( 'No target element found' );
					return;
				}

				lpToggleFullscreenView( elTarget, elBtnFullScreen );
			},
		},
	] );
};
