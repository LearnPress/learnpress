/**
 * Builder Material Handler
 * Handles material upload and management for lesson popup in course builder.
 * Pure JavaScript implementation with optimizations
 *
 * @since 4.3.0
 * @version 1.0.0
 */

export class BuilderMaterial {
	constructor( container = null ) {
		this.container = container;
		this.initialized = false;
		this.eventsBound = false;
		this.sortable = null;

		// Store references for cleanup
		this.boundHandlers = {
			handleAddMaterial: null,
			handleChange: null,
			handleClick: null,
			handleSaveAll: null,
			handleDelete: null,
			handleDragStart: null,
			handleDragOver: null,
			handleDrop: null,
			handleDragEnd: null,
		};

		// Cache DOM elements
		this.elements = {};

		if ( this.container ) {
			this.init();
		}
	}

	/**
	 * Reinitialize with new container (called from BuilderPopup)
	 */
	reinit( container ) {
		if ( this.initialized ) {
			this.destroy();
		}

		this.container = container;

		if ( this.container ) {
			this.init();
		}
	}

	/**
	 * Cache all DOM elements for better performance
	 */
	cacheElements() {
		const el = this.elements;

		el.postId = this.container.querySelector( '#current-material-post-id' );
		el.maxFileSize = this.container.querySelector( '#material-max-file-size' );
		el.uploadField = this.container.querySelector( '.lp-material--field-upload' );
		el.canUpload = this.container.querySelector( '#available-to-upload' );
		el.addBtn = this.container.querySelector( '#btn-lp--add-material' );
		el.groupTemplate = this.container.querySelector( '#lp-material--add-material-template' );
		el.groupContainer = this.container.querySelector( '#lp-material--group-container' );
		el.materialTab = this.container.querySelector( '#lp-material-container' ) || this.container;
		el.saveBtn = this.container.querySelector( '#btn-lp--save-material' );
		el.uploadTemplate = this.container.querySelector( '#lp-material--upload-field-template' );
		el.externalTemplate = this.container.querySelector( '#lp-material--external-field-template' );
		el.deleteText = this.container.querySelector( '#delete-material-row-text' );
		el.deleteMessage = this.container.querySelector( '#delete-material-message' );
		el.materialTable = this.container.querySelector( '.lp-material--table' );
		el.tbody = el.materialTable?.querySelector( 'tbody' );
		el.thead = el.materialTable?.querySelector( 'thead' );
	}

	init() {
		if ( ! this.container ) return;

		// Cache all elements
		this.cacheElements();

		const { postId, materialTab } = this.elements;

		// Validate required elements
		if ( ! postId || ! materialTab ) return;

		// Store config values
		this.postID = postId.value;
		this.maxFileSize = this.elements.maxFileSize?.value || 10;
		this.acceptFile = this.elements.uploadField
			? this.elements.uploadField
					.getAttribute( 'accept' )
					?.split( ',' )
					.map( ( s ) => s.trim() ) || []
			: [];

		// Load existing materials
		this.loadMaterials();

		// Bind events
		this.bindEvents();

		// Initialize native drag & drop sortable
		this.initSortable();

		this.initialized = true;
	}

	/**
	 * Load materials from API with better error handling
	 */
	async loadMaterials() {
		const { materialTab, tbody } = this.elements;

		if ( ! materialTab || ! this.postID || ! tbody ) return;

		try {
			const restUrl = this.getRestUrl();
			const url = `${ restUrl }lp/v1/material/item-materials/${ this.postID }`;

			const response = await fetch( url, {
				method: 'GET',
				headers: {
					'X-WP-Nonce': this.getNonce(),
					'Content-Type': 'application/json',
				},
			} );

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const result = await response.json();
			const { data, status } = result;

			if ( status !== 'success' ) {
				console.error( result.message );
				return;
			}

			if ( data?.items?.length > 0 ) {
				// Remove skeleton loader
				const skeleton = materialTab.querySelector( '.lp-skeleton-animation' );
				skeleton?.remove();

				// Use DocumentFragment for better performance
				const fragment = document.createDocumentFragment();
				data.items.forEach( ( item ) => {
					const row = this.createRow( item );
					fragment.appendChild( row );
				} );
				tbody.appendChild( fragment );

				// Reinit sortable after loading data
				this.initSortable();
			}
		} catch ( error ) {
			console.error( 'Load materials error:', error.message );
		}
	}

	/**
	 * Create a table row element (more efficient than insertAdjacentHTML)
	 */
	createRow( data ) {
		const tr = document.createElement( 'tr' );
		tr.dataset.id = data.file_id;
		tr.dataset.sort = data.orders;
		tr.draggable = true;

		const deleteBtnText = this.elements.deleteText?.value || 'Delete';

		tr.innerHTML = `
			<td class="sort">
				<span class="dashicons dashicons-menu"></span> ${ this.escapeHtml( data.file_name ) }
			</td>
			<td>${ this.capitalizeFirstChar( data.method ) }</td>
			<td>
				<a href="javascript:void(0)" class="delete-material-row" data-id="${ data.file_id }">
					${ deleteBtnText }
				</a>
			</td>
		`;

		return tr;
	}

	/**
	 * Escape HTML to prevent XSS
	 */
	escapeHtml( text ) {
		const div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	capitalizeFirstChar( str ) {
		return str.charAt( 0 ).toUpperCase() + str.substring( 1 );
	}

	/**
	 * Bind all events with delegation for better performance
	 */
	bindEvents() {
		if ( this.eventsBound ) return;

		const { addBtn, materialTab, saveBtn } = this.elements;

		// Create bound handlers for later removal
		this.boundHandlers.handleAddMaterial = () => this.handleAddMaterial();
		this.boundHandlers.handleChange = ( e ) => this.handleChange( e );
		this.boundHandlers.handleClick = ( e ) => this.handleClick( e );
		this.boundHandlers.handleSaveAll = () => this.handleSaveAll();
		this.boundHandlers.handleDelete = ( e ) => this.handleDelete( e );

		// Add material button
		addBtn?.addEventListener( 'click', this.boundHandlers.handleAddMaterial );

		// Use event delegation on materialTab
		if ( materialTab ) {
			materialTab.addEventListener( 'change', this.boundHandlers.handleChange );
			materialTab.addEventListener( 'click', this.boundHandlers.handleClick );
		}

		// Save all button
		saveBtn?.addEventListener( 'click', this.boundHandlers.handleSaveAll );

		// Delete material (use event delegation on container)
		this.container.addEventListener( 'click', this.boundHandlers.handleDelete );

		this.eventsBound = true;
	}

	/**
	 * Handle add material button click
	 */
	handleAddMaterial() {
		const { addBtn, groupContainer, groupTemplate } = this.elements;

		if ( ! addBtn || ! groupContainer || ! groupTemplate ) return;

		const canUploadData = parseInt( addBtn.getAttribute( 'can-upload' ) ) || 0;
		const groups = groupContainer.querySelectorAll( '.lp-material--group' ).length;

		if ( groups >= canUploadData ) return;

		groupContainer.insertAdjacentHTML( 'afterbegin', groupTemplate.innerHTML );
	}

	/**
	 * Handle change events with delegation
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
		const { uploadTemplate, externalTemplate } = this.elements;

		if ( ! uploadTemplate || ! externalTemplate ) return;

		const group = target.closest( '.lp-material--group' );
		if ( ! group ) return;

		switch ( method ) {
			case 'upload':
				target.parentNode.insertAdjacentHTML( 'afterend', uploadTemplate.innerHTML );
				group.querySelector( '.lp-material--external-wrap' )?.remove();
				break;
			case 'external':
				target.parentNode.insertAdjacentHTML( 'afterend', externalTemplate.innerHTML );
				group.querySelector( '.lp-material--upload-wrap' )?.remove();
				break;
		}
	}

	/**
	 * Validate uploaded file
	 */
	validateFile( target ) {
		if ( ! target.value || ! target.files?.length ) return;

		const file = target.files[ 0 ];

		if ( this.acceptFile.length > 0 && ! this.acceptFile.includes( file.type ) ) {
			alert( 'This file is not allowed! Please choose another file!' );
			target.value = '';
			return;
		}

		if ( file.size > this.maxFileSize * 1024 * 1024 ) {
			alert(
				`This file size is greater than ${ this.maxFileSize }MB! Please choose another file!`
			);
			target.value = '';
		}
	}

	/**
	 * Handle click events with delegation
	 */
	handleClick( event ) {
		const target = event.target;

		// Delete group
		if ( target.classList.contains( 'lp-material--delete' ) && target.nodeName === 'BUTTON' ) {
			target.closest( '.lp-material--group' )?.remove();
			return;
		}

		// Save single material
		if ( target.classList.contains( 'lp-material-save-field' ) ) {
			const material = target.closest( '.lp-material--group' );
			if ( material ) {
				this.saveMaterial( [ material ], true, target );
			}
		}
	}

	/**
	 * Handle save all button
	 */
	handleSaveAll() {
		const { groupContainer, saveBtn } = this.elements;

		if ( ! groupContainer ) return;

		const materials = Array.from( groupContainer.querySelectorAll( '.lp-material--group' ) );
		if ( materials.length > 0 ) {
			this.saveMaterial( materials, false, saveBtn );
		}
	}

	/**
	 * Save material(s) with improved validation
	 */
	async saveMaterial( materials, isSingle = false, targetBtn ) {
		if ( ! materials.length ) return;

		const materialData = [];
		const formData = new FormData();
		let isValid = true;

		for ( const ele of materials ) {
			const label = ele.querySelector( '.lp-material--field-title' )?.value;
			const method = ele.querySelector( '.lp-material--field-method' )?.value;
			const externalField = ele.querySelector( '.lp-material--field-external-link' );
			const uploadField = ele.querySelector( '.lp-material--field-upload' );

			if ( ! label ) {
				isValid = false;
				break;
			}

			let file = '';
			let link = '';

			switch ( method ) {
				case 'upload':
					if ( uploadField?.value && uploadField.files?.length > 0 ) {
						file = uploadField.files[ 0 ].name;
						formData.append( 'file[]', uploadField.files[ 0 ] );
					} else {
						isValid = false;
					}
					break;
				case 'external':
					link = externalField?.value || '';
					if ( ! link ) {
						isValid = false;
					}
					break;
			}

			if ( ! isValid ) break;

			materialData.push( { label, method, file, link } );
		}

		if ( ! isValid ) {
			alert( 'Enter file title, choose file or enter file link!' );
			return;
		}

		formData.append( 'data', JSON.stringify( materialData ) );
		targetBtn?.classList.add( 'loading' );

		try {
			const restUrl = this.getRestUrl();
			const url = `${ restUrl }lp/v1/material/item-materials/${ this.postID }`;

			const response = await fetch( url, {
				method: 'POST',
				headers: {
					'X-WP-Nonce': this.getNonce(),
				},
				body: formData,
			} );

			if ( ! response.ok ) {
				throw new Error( `HTTP error! status: ${ response.status }` );
			}

			const text = await response.text();
			let res;

			try {
				res = JSON.parse( text );
			} catch ( e ) {
				console.error( 'Response is not valid JSON:', text.substring( 0, 200 ) );
				throw new Error( 'Server returned invalid response. Check console for details.' );
			}

			// Clear or remove materials
			if ( ! isSingle ) {
				materials.forEach( ( ele ) => {
					ele.querySelector( '.lp-material--field-title' ).value = '';
					const uploadField = ele.querySelector( '.lp-material--field-upload' );
					if ( uploadField ) uploadField.value = '';
					const externalField = ele.querySelector( '.lp-material--field-external-link' );
					if ( externalField ) externalField.value = '';
				} );
			} else {
				materials[ 0 ].remove();
			}

			const { message, data, status } = res;
			alert( message );

			if ( status === 'success' && data?.length > 0 ) {
				const { thead, tbody } = this.elements;

				thead?.classList.remove( 'hidden' );

				if ( tbody ) {
					const fragment = document.createDocumentFragment();
					data.forEach( ( row ) => {
						fragment.appendChild( this.createRow( row ) );
					} );
					tbody.appendChild( fragment );
				}

				this.updateCanUploadCount( -data.length );
				this.initSortable();
			}
		} catch ( err ) {
			console.error( 'Save material error:', err );
			alert( 'Error saving material: ' + err.message );
		} finally {
			targetBtn?.classList.remove( 'loading' );
		}
	}

	/**
	 * Handle delete material
	 */
	async handleDelete( e ) {
		const target = e.target;

		if ( ! target.classList.contains( 'delete-material-row' ) || target.nodeName !== 'A' ) {
			return;
		}

		e.preventDefault();

		const rowID = target.dataset.id;
		const message =
			this.elements.deleteMessage?.value || 'Are you sure you want to delete this material?';

		if ( ! confirm( message ) ) return;

		try {
			const restUrl = this.getRestUrl();
			const url = `${ restUrl }lp/v1/material/${ rowID }`;

			const response = await fetch( url, {
				method: 'DELETE',
				headers: {
					'X-WP-Nonce': this.getNonce(),
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					item_id: this.postID,
				} ),
			} );

			const res = await response.json();

			if ( res.status !== 200 || ! res.delete ) {
				alert( res.message );
			} else {
				target.closest( 'tr' )?.remove();
				this.updateCanUploadCount( 1 );
			}
		} catch ( err ) {
			console.error( 'Delete material error:', err );
			alert( 'Error deleting material: ' + err.message );
		}
	}

	/**
	 * Update can upload count
	 */
	updateCanUploadCount( delta ) {
		const { canUpload, addBtn } = this.elements;

		if ( canUpload && addBtn ) {
			const newCount = parseInt( canUpload.textContent ) + delta;
			canUpload.textContent = newCount;
			addBtn.setAttribute( 'can-upload', newCount );
		}
	}

	/**
	 * Initialize native drag & drop sortable (no jQuery)
	 */
	initSortable() {
		const { tbody } = this.elements;

		if ( ! tbody ) return;

		// Remove existing listeners
		this.destroySortable();

		const rows = tbody.querySelectorAll( 'tr' );

		// Create bound handlers
		this.boundHandlers.handleDragStart = ( e ) => this.handleDragStart( e );
		this.boundHandlers.handleDragOver = ( e ) => this.handleDragOver( e );
		this.boundHandlers.handleDrop = ( e ) => this.handleDrop( e );
		this.boundHandlers.handleDragEnd = () => this.handleDragEnd();

		rows.forEach( ( row ) => {
			row.draggable = true;
			row.addEventListener( 'dragstart', this.boundHandlers.handleDragStart );
			row.addEventListener( 'dragover', this.boundHandlers.handleDragOver );
			row.addEventListener( 'drop', this.boundHandlers.handleDrop );
			row.addEventListener( 'dragend', this.boundHandlers.handleDragEnd );
		} );

		this.sortable = { tbody, rows };
	}

	handleDragStart( e ) {
		this.draggedElement = e.currentTarget;
		e.currentTarget.style.opacity = '0.4';
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData( 'text/html', e.currentTarget.innerHTML );
	}

	handleDragOver( e ) {
		if ( e.preventDefault ) {
			e.preventDefault();
		}
		e.dataTransfer.dropEffect = 'move';

		const target = e.currentTarget;
		if ( this.draggedElement !== target ) {
			const rect = target.getBoundingClientRect();
			const next = ( e.clientY - rect.top ) / ( rect.bottom - rect.top ) > 0.5;
			target.parentNode.insertBefore( this.draggedElement, next ? target.nextSibling : target );
		}

		return false;
	}

	handleDrop( e ) {
		if ( e.stopPropagation ) {
			e.stopPropagation();
		}
		return false;
	}

	handleDragEnd() {
		this.draggedElement.style.opacity = '1';
		this.draggedElement = null;

		// Update sort order after drag ends
		this.updateSort();
	}

	/**
	 * Update sort order
	 */
	async updateSort() {
		const { tbody } = this.elements;
		if ( ! tbody ) return;

		const items = tbody.querySelectorAll( 'tr' );
		const data = Array.from( items ).map( ( item, index ) => {
			item.dataset.sort = index + 1;
			return {
				file_id: parseInt( item.dataset.id ),
				orders: index + 1,
			};
		} );

		try {
			const restUrl = this.getRestUrl();
			const url = `${ restUrl }lp/v1/material/item-materials/${ this.postID }`;

			const response = await fetch( url, {
				method: 'PUT',
				headers: {
					'X-WP-Nonce': this.getNonce(),
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					sort_arr: JSON.stringify( data ),
				} ),
			} );

			const res = await response.json();

			if ( res.status !== 200 ) {
				alert( 'Sort table fail.' );
			}
		} catch ( err ) {
			console.error( 'Update sort error:', err );
		}
	}

	/**
	 * Helper methods for REST URL and nonce
	 */
	getRestUrl() {
		return window.lpGlobalSettings?.rest || window.lpData?.lp_rest_url || '/wp-json/';
	}

	getNonce() {
		return window.lpGlobalSettings?.nonce || window.lpData?.nonce || '';
	}

	/**
	 * Destroy sortable
	 */
	destroySortable() {
		if ( ! this.sortable ) return;

		const { rows } = this.sortable;
		rows?.forEach( ( row ) => {
			row.removeEventListener( 'dragstart', this.boundHandlers.handleDragStart );
			row.removeEventListener( 'dragover', this.boundHandlers.handleDragOver );
			row.removeEventListener( 'drop', this.boundHandlers.handleDrop );
			row.removeEventListener( 'dragend', this.boundHandlers.handleDragEnd );
		} );

		this.sortable = null;
	}

	/**
	 * Destroy instance and cleanup
	 */
	destroy() {
		const { addBtn, materialTab, saveBtn } = this.elements;

		// Remove event listeners
		if ( addBtn ) {
			addBtn.removeEventListener( 'click', this.boundHandlers.handleAddMaterial );
		}

		if ( materialTab ) {
			materialTab.removeEventListener( 'change', this.boundHandlers.handleChange );
			materialTab.removeEventListener( 'click', this.boundHandlers.handleClick );
		}

		if ( saveBtn ) {
			saveBtn.removeEventListener( 'click', this.boundHandlers.handleSaveAll );
		}

		if ( this.container ) {
			this.container.removeEventListener( 'click', this.boundHandlers.handleDelete );
		}

		// Destroy sortable
		this.destroySortable();

		// Reset bound handlers
		this.boundHandlers = {
			handleAddMaterial: null,
			handleChange: null,
			handleClick: null,
			handleSaveAll: null,
			handleDelete: null,
			handleDragStart: null,
			handleDragOver: null,
			handleDrop: null,
			handleDragEnd: null,
		};

		// Clear cached elements
		this.elements = {};

		this.initialized = false;
		this.eventsBound = false;
	}
}

export default BuilderMaterial;
