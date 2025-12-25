/**
 * Builder Material Handler
 * Handles material upload and management for lesson popup in course builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

export class BuilderMaterial {
	constructor( container = null ) {
		this.container = container;
		this.$ = window.jQuery;
		this.initialized = false;
		this.eventsBound = false;

		// Store references for cleanup
		this.boundHandlers = {
			handleAddMaterial: null,
			handleChange: null,
			handleClick: null,
			handleSaveAll: null,
			handleDelete: null,
		};

		if ( this.container ) {
			this.init();
		}
	}

	/**
	 * Reinitialize with new container (called from BuilderPopup)
	 */
	reinit( container ) {
		// Destroy previous instance if exists
		if ( this.initialized ) {
			this.destroy();
		}

		this.container = container;
		
		if ( this.container ) {
			this.init();
		}
	}

	init() {
		if ( ! this.container ) {
			return;
		}

		// Get elements within the container
		this.postIdEl = this.container.querySelector( '#current-material-post-id' );
		this.maxFileSizeEl = this.container.querySelector( '#material-max-file-size' );
		this.uploadFieldEl = this.container.querySelector( '.lp-material--field-upload' );
		this.canUploadEl = this.container.querySelector( '#available-to-upload' );
		this.addBtn = this.container.querySelector( '#btn-lp--add-material' );
		this.groupTemplate = this.container.querySelector( '#lp-material--add-material-template' );
		this.groupContainer = this.container.querySelector( '#lp-material--group-container' );
		this.materialTab = this.container.querySelector( '#lp-material-container' );
		this.saveBtn = this.container.querySelector( '#btn-lp--save-material' );

		// Validate required elements
		if ( ! this.postIdEl || ! this.materialTab ) {
			return;
		}

		this.postID = this.postIdEl.value;
		this.maxFileSize = this.maxFileSizeEl ? this.maxFileSizeEl.value : 10;
		this.acceptFile = this.uploadFieldEl ? this.uploadFieldEl.getAttribute( 'accept' ).split( ',' ) : [];

		// Load existing materials
		this.loadMaterials();

		// Bind events
		this.bindEvents();

		// Initialize sortable
		this.initSortable();

		this.initialized = true;
	}

	/**
	 * Load materials from API
	 */
	async loadMaterials() {
		if ( ! this.materialTab || ! this.postID ) {
			return;
		}

		const elementMaterial = this.container.querySelector( '.lp-material--table tbody' );
		if ( ! elementMaterial ) {
			return;
		}

		try {
			// Use lpGlobalSettings for frontend context (lpDataAdmin is only available in admin)
			const restUrl = window.lpGlobalSettings?.rest || window.lpData?.lp_rest_url || '/wp-json/';
			const url = `${ restUrl }lp/v1/material/item-materials/${ this.postID }`;
			const response = await fetch( url, {
				method: 'GET',
				headers: {
					'X-WP-Nonce': lpData.nonce,
					'Content-Type': 'application/json',
				},
			} );

			const result = await response.json();
			const { data, status } = result;

			if ( status !== 'success' ) {
				console.error( result.message );
				return;
			}

			if ( data && data.items && data.items.length > 0 ) {
				const skeleton = this.materialTab.querySelector( '.lp-skeleton-animation' );
				if ( skeleton ) {
					skeleton.remove();
				}

				data.items.forEach( ( item ) => {
					this.insertRow( elementMaterial, item );
				} );

				// Reinit sortable after loading data
				this.initSortable();
			}
		} catch ( error ) {
			console.error( 'Load materials error:', error.message );
		}
	}

	/**
	 * Insert a row into the material table
	 */
	insertRow( parent, data ) {
		if ( ! parent ) {
			return;
		}

		const deleteTextEl = this.container.querySelector( '#delete-material-row-text' );
		const deleteBtnText = deleteTextEl ? deleteTextEl.value : 'Delete';

		parent.insertAdjacentHTML(
			'beforeend',
			`<tr data-id="${ data.file_id }" data-sort="${ data.orders }">
				<td class="sort"><span class="dashicons dashicons-menu"></span> ${ data.file_name }</td>
				<td>${ this.capitalizeFirstChar( data.method ) }</td>
				<td><a href="javascript:void(0)" class="delete-material-row" data-id="${ data.file_id }">${ deleteBtnText }</a></td>
			</tr>`
		);
	}

	capitalizeFirstChar( str ) {
		return str.charAt( 0 ).toUpperCase() + str.substring( 1 );
	}

	/**
	 * Bind all events
	 */
	bindEvents() {
		if ( this.eventsBound ) {
			return;
		}

		// Create bound handlers for later removal
		this.boundHandlers.handleAddMaterial = ( e ) => this.handleAddMaterial( e );
		this.boundHandlers.handleChange = ( e ) => this.handleChange( e );
		this.boundHandlers.handleClick = ( e ) => this.handleClick( e );
		this.boundHandlers.handleSaveAll = ( e ) => this.handleSaveAll( e );
		this.boundHandlers.handleDelete = ( e ) => this.handleDelete( e );

		// Add material button
		if ( this.addBtn ) {
			this.addBtn.addEventListener( 'click', this.boundHandlers.handleAddMaterial );
		}

		// Change events (method switch, file validation)
		if ( this.materialTab ) {
			this.materialTab.addEventListener( 'change', this.boundHandlers.handleChange );
			this.materialTab.addEventListener( 'click', this.boundHandlers.handleClick );
		}

		// Save all button
		if ( this.saveBtn ) {
			this.saveBtn.addEventListener( 'click', this.boundHandlers.handleSaveAll );
		}

		// Delete material (use event delegation on container)
		this.container.addEventListener( 'click', this.boundHandlers.handleDelete );

		this.eventsBound = true;
	}

	/**
	 * Handle add material button click
	 */
	handleAddMaterial( e ) {
		if ( ! this.addBtn || ! this.groupContainer || ! this.groupTemplate ) {
			return;
		}

		const canUploadData = ~~this.addBtn.getAttribute( 'can-upload' );
		const groups = this.groupContainer.querySelectorAll( '.lp-material--group' ).length;

		if ( groups >= canUploadData ) {
			return false;
		}

		this.groupContainer.insertAdjacentHTML( 'afterbegin', this.groupTemplate.innerHTML );
	}

	/**
	 * Handle change events
	 */
	handleChange( event ) {
		const target = event.target;

		// Switch between upload and external
		if ( target.classList.contains( 'lp-material--field-method' ) ) {
			this.handleMethodSwitch( target );
		}

		// File validation
		if ( target.classList.contains( 'lp-material--field-upload' ) ) {
			this.validateFile( target );
		}
	}

	/**
	 * Handle method switch (upload/external)
	 */
	handleMethodSwitch( target ) {
		const method = target.value;
		const uploadTemplate = this.container.querySelector( '#lp-material--upload-field-template' );
		const externalTemplate = this.container.querySelector( '#lp-material--external-field-template' );

		if ( ! uploadTemplate || ! externalTemplate ) {
			return;
		}

		const group = target.closest( '.lp-material--group' );
		if ( ! group ) {
			return;
		}

		switch ( method ) {
			case 'upload':
				target.parentNode.insertAdjacentHTML( 'afterend', uploadTemplate.innerHTML );
				const externalWrap = group.querySelector( '.lp-material--external-wrap' );
				if ( externalWrap ) {
					externalWrap.remove();
				}
				break;
			case 'external':
				target.parentNode.insertAdjacentHTML( 'afterend', externalTemplate.innerHTML );
				const uploadWrap = group.querySelector( '.lp-material--upload-wrap' );
				if ( uploadWrap ) {
					uploadWrap.remove();
				}
				break;
		}
	}

	/**
	 * Validate uploaded file
	 */
	validateFile( target ) {
		if ( ! target.value || ! target.files || target.files.length === 0 ) {
			return;
		}

		const file = target.files[ 0 ];

		if ( this.acceptFile.length > 0 && ! this.acceptFile.includes( file.type ) ) {
			alert( 'This file is not allowed! Please choose another file!' );
			target.value = '';
			return;
		}

		if ( file.size > this.maxFileSize * 1024 * 1024 ) {
			alert( `This file size is greater than ${ this.maxFileSize }MB! Please choose another file!` );
			target.value = '';
		}
	}

	/**
	 * Handle click events
	 */
	handleClick( event ) {
		const target = event.target;

		// Delete group
		if ( target.classList.contains( 'lp-material--delete' ) && target.nodeName === 'BUTTON' ) {
			target.closest( '.lp-material--group' ).remove();
		}

		// Save single material
		if ( target.classList.contains( 'lp-material-save-field' ) ) {
			let material = target.closest( '.lp-material--group' );
			material = this.singleNode( material );
			this.saveMaterial( material, true, target );
		}

		return false;
	}

	/**
	 * Handle save all button
	 */
	handleSaveAll( event ) {
		if ( ! this.groupContainer ) {
			return;
		}

		const materials = this.groupContainer.querySelectorAll( '.lp-material--group' );
		if ( materials.length > 0 ) {
			this.saveMaterial( materials, false, this.saveBtn );
		}
	}

	/**
	 * Save material(s)
	 */
	saveMaterial( materials, isSingle = false, targetBtn ) {
		if ( materials.length === 0 ) {
			return;
		}

		let materialData = [];
		let formData = new FormData();
		let sendRequest = true;

		materials.forEach( ( ele ) => {
			const label = ele.querySelector( '.lp-material--field-title' ).value;
			const method = ele.querySelector( '.lp-material--field-method' ).value;
			const externalField = ele.querySelector( '.lp-material--field-external-link' );
			const uploadField = ele.querySelector( '.lp-material--field-upload' );

			let file = '';
			let link = '';

			if ( ! label ) {
				sendRequest = false;
				return;
			}

			switch ( method ) {
				case 'upload':
					if ( uploadField && uploadField.value && uploadField.files.length > 0 ) {
						file = uploadField.files[ 0 ].name;
						formData.append( 'file[]', uploadField.files[ 0 ] );
					} else {
						sendRequest = false;
					}
					break;
				case 'external':
					link = externalField ? externalField.value : '';
					if ( ! link ) {
						sendRequest = false;
					}
					break;
			}

			materialData.push( { label, method, file, link } );
		} );

		if ( ! sendRequest ) {
			alert( 'Enter file title, choose file or enter file link!' );
			return;
		}

		materialData = JSON.stringify( materialData );
		const restUrl = window.lpGlobalSettings?.rest || window.lpData?.lp_rest_url || '/wp-json/';
		const url = `${ restUrl }lp/v1/material/item-materials/${ this.postID }`;
		formData.append( 'data', materialData );
		targetBtn.classList.add( 'loading' );

		fetch( url, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': window.lpGlobalSettings?.nonce || window.lpData?.nonce || '',
			},
			body: formData,
		} )
			.then( ( response ) => {
				// Check if response is ok
				if ( ! response.ok ) {
					throw new Error( `HTTP error! status: ${ response.status }` );
				}
				return response.text();
			} )
			.then( ( resString ) => {
				// Try to parse JSON, if fails, it might be HTML error page
				let res;
				try {
					res = JSON.parse( resString );
				} catch ( e ) {
					console.error( 'Response is not valid JSON:', resString.substring( 0, 200 ) );
					throw new Error( 'Server returned invalid response. Check console for details.' );
				}

				if ( ! isSingle ) {
					this.groupContainer.innerHTML = '';
				} else {
					materials[ 0 ].remove();
				}

				const { message, data, status } = res;
				alert( message );

				if ( status === 'success' && data.length > 0 ) {
					const materialTable = this.container.querySelector( '.lp-material--table' );
					const thead = materialTable ? materialTable.querySelector( 'thead' ) : null;
					const tbody = materialTable ? materialTable.querySelector( 'tbody' ) : null;

					if ( thead ) {
						thead.classList.remove( 'hidden' );
					}

					if ( tbody ) {
						data.forEach( ( row ) => {
							this.insertRow( tbody, row );
						} );
					}

					if ( this.canUploadEl && this.addBtn ) {
						this.canUploadEl.innerText = parseInt( this.canUploadEl.innerText ) - data.length;
						this.addBtn.setAttribute( 'can-upload', this.canUploadEl.innerText );
					}

					// Reinit sortable after adding new rows
					this.initSortable();
				}
			} )
			.catch( ( err ) => {
				console.error( 'Save material error:', err );
				alert( 'Error saving material: ' + err.message );
			} )
			.finally( () => {
				targetBtn.classList.remove( 'loading' );
			} );
	}

	/**
	 * Handle delete material
	 */
	handleDelete( e ) {
		const target = e.target;

		if ( ! target.classList.contains( 'delete-material-row' ) || target.nodeName !== 'A' ) {
			return;
		}

		const rowID = target.getAttribute( 'data-id' );
		const messageEl = this.container.querySelector( '#delete-material-message' );
		const message = messageEl ? messageEl.value : 'Are you sure you want to delete this material?';

		if ( ! confirm( message ) ) {
			return;
		}

		const restUrl = window.lpGlobalSettings?.rest || window.lpData?.lp_rest_url || '/wp-json/';
		const url = `${ restUrl }lp/v1/material/${ rowID }`;

		fetch( url, {
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': window.lpGlobalSettings?.nonce || window.lpData?.nonce || '',
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				item_id: this.postID,
			} ),
		} )
			.then( ( response ) => response.json() )
			.then( ( res ) => {
				if ( res.status !== 200 || ! res.delete ) {
					alert( res.message );
				} else {
					target.closest( 'tr' ).remove();

					if ( this.canUploadEl && this.addBtn ) {
						this.canUploadEl.innerText = ~~this.canUploadEl.innerText + 1;
						this.addBtn.setAttribute( 'can-upload', ~~this.canUploadEl.innerText );
					}
				}
			} )
			.catch( ( err ) => {
				console.error( 'Delete material error:', err );
				alert( 'Error deleting material: ' + err.message );
			} );
	}

	/**
	 * Initialize sortable
	 */
	initSortable() {
		const $ = this.$;
		if ( ! $ || ! $.fn.sortable ) {
			return;
		}

		const tbody = this.container.querySelector( '.lp-material--table tbody' );
		if ( ! tbody ) {
			return;
		}

		// Destroy existing sortable if exists
		if ( $( tbody ).sortable( 'instance' ) ) {
			$( tbody ).sortable( 'destroy' );
		}

		$( tbody ).sortable( {
			items: 'tr',
			cursor: 'move',
			axis: 'y',
			handle: 'td.sort',
			scrollSensitivity: 40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			update: ( event, ui ) => {
				$( tbody ).sortable( 'option', 'disabled', true );
				this.updateSort();
				$( tbody ).sortable( 'option', 'disabled', false );
			},
		} );
	}

	/**
	 * Update sort order
	 */
	updateSort() {
		const items = this.container.querySelectorAll( '.lp-material--table tbody tr' );
		const data = [];

		items.forEach( ( item, index ) => {
			item.setAttribute( 'data-sort', index + 1 );
			data.push( {
				file_id: ~~item.getAttribute( 'data-id' ),
				orders: index + 1,
			} );
		} );

		const restUrl = window.lpGlobalSettings?.rest || window.lpData?.lp_rest_url || '/wp-json/';
		const url = `${ restUrl }lp/v1/material/item-materials/${ this.postID }`;

		fetch( url, {
			method: 'PUT',
			headers: {
				'X-WP-Nonce': window.lpGlobalSettings?.nonce || window.lpData?.nonce || '',
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				sort_arr: JSON.stringify( data ),
			} ),
		} )
			.then( ( response ) => response.json() )
			.then( ( res ) => {
				if ( res.status !== 200 ) {
					alert( 'Sort table fail.' );
				}
			} )
			.catch( ( err ) => {
				console.error( 'Update sort error:', err );
			} );
	}

	/**
	 * Helper to create a single-node NodeList
	 */
	singleNode( node ) {
		const nodeList = document.createDocumentFragment().childNodes;
		const layer = {
			0: { value: node, enumerable: true },
			length: { value: 1 },
			item: {
				value( i ) {
					return this[ +i || 0 ];
				},
				enumerable: true,
			},
		};
		return Object.create( nodeList, layer );
	}

	/**
	 * Destroy instance and cleanup
	 */
	destroy() {
		// Remove event listeners
		if ( this.addBtn && this.boundHandlers.handleAddMaterial ) {
			this.addBtn.removeEventListener( 'click', this.boundHandlers.handleAddMaterial );
		}

		if ( this.materialTab ) {
			if ( this.boundHandlers.handleChange ) {
				this.materialTab.removeEventListener( 'change', this.boundHandlers.handleChange );
			}
			if ( this.boundHandlers.handleClick ) {
				this.materialTab.removeEventListener( 'click', this.boundHandlers.handleClick );
			}
		}

		if ( this.saveBtn && this.boundHandlers.handleSaveAll ) {
			this.saveBtn.removeEventListener( 'click', this.boundHandlers.handleSaveAll );
		}

		if ( this.container && this.boundHandlers.handleDelete ) {
			this.container.removeEventListener( 'click', this.boundHandlers.handleDelete );
		}

		// Remove sortable if exists
		const $ = this.$;
		if ( $ && $.fn.sortable ) {
			const tbody = this.container.querySelector( '.lp-material--table tbody' );
			if ( tbody && $( tbody ).sortable( 'instance' ) ) {
				$( tbody ).sortable( 'destroy' );
			}
		}

		// Reset bound handlers
		this.boundHandlers = {
			handleAddMaterial: null,
			handleChange: null,
			handleClick: null,
			handleSaveAll: null,
			handleDelete: null,
		};

		this.initialized = false;
		this.eventsBound = false;
	}
}

export default BuilderMaterial;
