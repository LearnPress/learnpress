document.addEventListener( 'DOMContentLoaded', function() {
	const $ = window.jQuery;
	const postID = document.getElementById( 'current-material-post-id' ).value,
		max_file_size = document.getElementById( 'material-max-file-size' ).value,
		accept_file = document.querySelector( '.lp-material--field-upload' ).getAttribute( 'accept' ).split( ',' ),
		can_upload = document.getElementById( 'available-to-upload' ),
		add_btn = document.getElementById( 'btn-lp--add-material' ),
		group_template = document.getElementById( 'lp-material--add-material-template' ),
		material__group_container = document.getElementById( 'lp-material--group-container' ),
		material_tab = document.getElementById( 'lp-material-container' ),
		material_save_btn = document.getElementById( 'btn-lp--save-material' );
	const getResponse = async ( ele, postID ) => {
		const elementMaterial = document.querySelector( '.lp-material--table tbody' );
		try {
			const url = `${ lpDataAdmin.lp_rest_url }lp/v1/material/item-materials/${ postID }?is_admin=1&per_page=-1`;
			fetch( url, {
				method: 'GET',
				headers: {
					'X-WP-Nonce': lpGlobalSettings.nonce,
					'Content-Type': 'application/json',
				},
			} )
				.then( ( response ) => response.json() )
				.then( ( response ) => {
					const { data, status } = response;
					if ( status !== 'success' ) {
						console.error( response.message );
						return;
					}

					if ( data && data.items && data.items.length > 0 ) {
						const materials = data.items;
						if ( ele.querySelector( '.lp-skeleton-animation' ) ) {
							ele.querySelector( '.lp-skeleton-animation' ).remove();
						}
						for ( let i = 0; i < materials.length; i++ ) {
							insertRow( elementMaterial, materials[ i ] );
						}
					}
				} )
				.catch( ( err ) => console.log( err ) );
		} catch ( error ) {
			console.log( error.message );
		}
	};
	const insertRow = ( parent, data ) => {
		if ( ! parent ) {
			return;
		}
		const delete_btn_text = document.getElementById( 'delete-material-row-text' ).value;
		parent.insertAdjacentHTML(
			'beforeend',
			`<tr data-id="${ data.file_id }" data-sort="${ data.orders }" >
              <td class="sort"><span class="dashicons dashicons-menu"></span> ${ data.file_name }</td>
              <td>${ capitalizeFirstChar( data.method ) }</td>
              <td><a href="javascript:void(0)" class="delete-material-row" data-id="${ data.file_id }">${ delete_btn_text }</a></td>
            </tr>`
		);
	};
	const capitalizeFirstChar = ( str ) => str.charAt( 0 ).toUpperCase() + str.substring( 1 );
	//load material data from API
	getResponse( material_tab, postID );

	//add material group field
	add_btn.addEventListener( 'click', function( e ) {
		const can_upload_data = ~~this.getAttribute( 'can-upload' );
		const groups = material__group_container.querySelectorAll( '.lp-material--group' ).length;
		if ( groups >= can_upload_data ) {
			return false;
		}
		material__group_container.insertAdjacentHTML( 'afterbegin', group_template.innerHTML );
	} );
	//switch input when change method between "upload" and "external"
	material_tab.addEventListener( 'change', function( event ) {
		const target = event.target;
		if ( target.classList.contains( 'lp-material--field-method' ) ) {
			const method = target.value;
			const upload_field_template = document.getElementById( 'lp-material--upload-field-template' ).innerHTML,
				external_field_template = document.getElementById( 'lp-material--external-field-template' ).innerHTML;
			switch ( method ) {
			case 'upload' :
				target.parentNode.insertAdjacentHTML( 'afterend', upload_field_template );
				target.closest( '.lp-material--group' ).querySelector( '.lp-material--external-wrap' ).remove();
				break;
			case 'external' :
				target.parentNode.insertAdjacentHTML( 'afterend', external_field_template );
				target.closest( '.lp-material--group' ).querySelector( '.lp-material--upload-wrap' ).remove();
				break;
			}
		}
		if ( target.classList.contains( 'lp-material--field-upload' ) ) {
			if ( target.value && target.files.length > 0 ) {
				if ( ! accept_file.includes( target.files[ 0 ].type ) ) {
					alert( 'This file is not allowed! Please choose another file!' );
					target.value = '';
				} else if ( target.files[ 0 ].size > max_file_size * 1024 * 1024 ) {
					alert( `This file size is greater than ${ max_file_size }MB! Please choose another file!` );
					target.value = '';
				}
			}
		}
	} );
	// Dynamic click action ...
	material_tab.addEventListener( 'click', function( event ) {
		const target = event.target;
		if ( target.classList.contains( 'lp-material--delete' ) && target.nodeName == 'BUTTON' ) {
			target.closest( '.lp-material--group' ).remove();
		} else if ( target.classList.contains( 'lp-material-save-field' ) ) {
			// save a file
			let material = target.closest( '.lp-material--group' );
			material = singleNode( material );
			// target.classList.add( 'loading' );
			lpSaveMaterial( material, true, target );
			// target.classList.remove( 'loading' );
		}
		return false;
	} );

	//save all material files
	material_save_btn.addEventListener( 'click', function( event ) {
		const materials = material__group_container.querySelectorAll( '.lp-material--group' );
		if ( materials.length > 0 ) {
			// material_save_btn.classList.add( 'loading' );
			lpSaveMaterial( materials, false, material_save_btn );
			// material_save_btn.classList.remove( 'loading' );
		}
	} );
	function lpSaveMaterial( materials, is_single = false, target ) {
		if ( materials.length > 0 ) {
			let material_data = [];
			let formData = new FormData(),
				send_request = true;

			materials.forEach( function( ele, index ) {
				const label = ele.querySelector( '.lp-material--field-title' ).value,
					method = ele.querySelector( '.lp-material--field-method' ).value,
					external_field = ele.querySelector( '.lp-material--field-external-link' ),
					upload_field = ele.querySelector( '.lp-material--field-upload' );
				let file, link;
				if ( ! label ) {
					send_request = false;
				}
				switch ( method ) {
				case 'upload' :
					if ( upload_field.value ) {
						file = upload_field.files[ 0 ].name;
						link = '';
						formData.append( 'file[]', upload_field.files[ 0 ] );
					} else {
						send_request = false;
					}
					break;
				case 'external' :
					link = external_field.value;
					file = '';
					if ( ! link ) {
						send_request = false;
					}
					break;
				}
				material_data.push( { label, method, file, link } );
			} );

			if ( ! send_request ) {
				alert( 'Enter file title, choose file or enter file link!' );
			} else {
				// console.log(material_data);
				material_data = JSON.stringify( material_data );
				const url = `${ lpGlobalSettings.rest }lp/v1/material/item-materials/${ postID }`;
				formData.append( 'data', material_data );
				target.classList.add( 'loading' );

				fetch( url, {
					method: 'POST',
					headers: {
						'X-WP-Nonce': lpGlobalSettings.nonce,
					},
					body: formData,
				} ) // wrapped
					.then( ( res ) => res.text() )
					.then( ( resString ) => {
						// console.log( data );
						if ( ! is_single ) {
							material__group_container.innerHTML = '';
						} else {
							materials[ 0 ].remove();
						}
						const res = JSON.parse( resString );
						const { message, data, status } = res;
						alert( message );

						if ( status === 'success' ) {
							if ( data.length > 0 ) {
								const material_table = document.querySelector( '.lp-material--table' );
								const thead = material_table.querySelector( 'thead' );
								const tbody = material_table.querySelector( 'tbody' );

								thead.classList.remove( 'hidden' );
								for ( let i = 0; i < data.length; i++ ) {
									const row = data[ i ];
									insertRow( tbody, row );
								}
								can_upload.innerText = parseInt( can_upload.innerText ) - data.length;
								add_btn.setAttribute( 'can-upload', can_upload.innerText );
							}
						}
					} )
					.finally( () => {
						target.classList.remove( 'loading' );
					} )
					.catch( ( err ) => console.log( err ) );
			}
		}
	}
	//delete material
	document.addEventListener( 'click', function( e ) {
		const target = e.target;
		if ( target.classList.contains( 'delete-material-row' ) && target.nodeName == 'A' ) {
			const rowID = target.getAttribute( 'data-id' ), //material file ID
				message = document.getElementById( 'delete-material-message' ).value;//Delete message content
			if ( confirm( message ) ) {
				wp.apiFetch( {
					path: `lp/v1/material/${ rowID }`,
					method: 'DELETE',
					data: {
						item_id: postID,
					},
				} ).then( ( res ) => {
					// console.log( res );
					if ( res.status !== 200 || ! res.delete ) {
						alert( res.message );
					} else {
						target.closest( 'tr' ).remove();
						can_upload.innerText = ~~can_upload.innerText + 1;
						add_btn.setAttribute( 'can-upload', ~~can_upload.innerText );
					}
				} ).catch( ( err ) => {
					console.log( err );
				} ).finally( () => {

				} );
			}
		}
	} );
	const singleNode = ( ( nodeList ) => ( node ) => {
		const layer = { // define our specific case
			0: { value: node, enumerable: true },
			length: { value: 1 },
			item: {
				value( i ) {
					return this[ +i || 0 ];
				},
				enumerable: true,
			},
		};
		return Object.create( nodeList, layer ); // put our case on top of true NodeList
	} )( document.createDocumentFragment().childNodes ); // scope a true NodeList
	$( '.lp-material--table tbody' ).sortable( {
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.sort',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		update( event, ui ) {
			$( this ).sortable( 'option', 'disabled', true );
			updateSort();
			$( this ).sortable( 'option', 'disabled', false );
		},
	} );
	function updateSort() {
		const items = $( '.lp-material--table tbody tr' ),
			data = [];
		// $( ".lp-material--table tbody tr" ).sortable( "option", "disabled", true );
		items.each( function( i, item ) {
			$( this ).attr( 'data-sort', i + 1 );
			data.push( { file_id: ~~$( this ).attr( 'data-id' ), orders: i + 1 } );
		} );
		wp.apiFetch( {
			path: `lp/v1/material/item-materials/${ postID }`,
			method: 'PUT',
			data: {
				sort_arr: JSON.stringify( data ),
			},
		} ).then( ( res ) => {
			if ( res.status == 200 ) {
				//
			} else {
				alert( 'Sort table fail.' );
			}
		} ).catch( ( err ) => {
			console.log( err );
		} ).finally( () => {

		} );
		// $( ".lp-material--table tbody tr" ).sortable( "option", "disabled", false );
		// console.log( data );
	}
} );
