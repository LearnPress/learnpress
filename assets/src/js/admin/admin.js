( function( $ ) {
	const updateItemPreview = function updateItemPreview() {
		$.ajax( {
			url: '',
			data: {
				'lp-ajax': 'toggle_item_preview',
				item_id: this.value,
				previewable: this.checked ? 'yes' : 'no',
				nonce: $( this ).attr( 'data-nonce' ),
			},
			dataType: 'text',
			success: function success( response ) {
				response = LP.parseJSON( response );
			},
		} );
	};
	/**
	 * Callback event for button to creating pages inside error message.
	 *
	 * @param {Event} e
	 */

	const createPages = function createPages( e ) {
		const $button = $( this ).addClass( 'disabled' );
		e.preventDefault();
		$.post( {
			url: $button.attr( 'href' ),
			data: {
				'lp-ajax': 'create-pages',
			},
			dataType: 'text',
			success: function success( res ) {
				const $message = $button.closest( '.lp-notice' ).html( '<p>' + res + '</p>' );
				setTimeout( function() {
					$message.fadeOut();
				}, 2000 );
			},
		} );
	};

	const hideUpgradeMessage = function hideUpgradeMessage( e ) {
		e.preventDefault();
		const $btn = $( this );
		$btn.closest( '.lp-upgrade-notice' ).fadeOut();
		$.post( {
			url: '',
			data: {
				'lp-hide-upgrade-message': 'yes',
			},
			success: function success( res ) {},
		} );
	};

	const pluginActions = function pluginActions( e ) {
		// Premium addon
		if ( $( e.target ).hasClass( 'buy-now' ) ) {
			return;
		}

		e.preventDefault();
		const $plugin = $( this ).closest( '.plugin-card' );

		if ( $( this ).hasClass( 'updating-message' ) ) {
			return;
		}

		$( this ).addClass( 'updating-message button-working disabled' );
		$.ajax( {
			url: $( this ).attr( 'href' ),
			data: {},
			success: function success( r ) {
				$.ajax( {
					url: window.location.href,
					success: function success( r ) {
						const $p = $( r ).find( '#' + $plugin.attr( 'id' ) );

						if ( $p.length ) {
							$plugin.replaceWith( $p );
						} else {
							$plugin.find( '.plugin-action-buttons a' ).removeClass( 'updating-message button-working' ).html( learn_press_admin_localize.plugin_installed );
						}
					},
				} );
			},
		} );
	};

	const preventDefault = function preventDefault( e ) {
		e.preventDefault();
		return false;
	};

	$.fn._filter_post_by_author = function() {
		const $input = $( '#post-search-input' );

		if ( ! $input.length ) {
			return;
		}

		const $form = $( $input[ 0 ].form );
		const $select = $( '<select name="author" id="author"></select>' ).insertAfter( $input ).select2( {
			ajax: {
				url: window.location.href + '&lp-ajax=search-authors',
				dataType: 'json',
				s: '',
			},
			placeholder: 'Search by user',
			minimumInputLength: 3,
			allowClear: true,
		} ).on( 'select2:select', function() {
			$( 'input[name="author"]' ).val( $select.val() );
		} );

		$form.on( 'submit', function() {
			const url = window.location.href.removeQueryVar( 'author' ).addQueryVar( 'author', $select.val() );
		} );
	};

	const updateDb = () => {
		$( '.lp-button-upgrade' ).each( function() {
			$( this ).on( 'click', function( e ) {
				e.preventDefault();

				$( '#lp-update-db-modal' ).removeClass( 'lp-update-db-modal__hidden' );
			} );
		} );

		$( '.lp-update-db-modal__button' ).on( 'click', function( e ) {
			e.preventDefault();

			const $button = $( this );
			const btnText = $button.text();
			const btxUpdating = $button.data( 'loading' );

			const textSuccess = $( '.lp-update-db-modal__content-text' ).data( 'text' );

			$button.addClass( 'loading' );
			$button.text( btxUpdating );

			const updateRequest = () => {
				$.ajax( {
					url: lpGlobalSettings.ajax + '?action=lp_update_database',
					method: 'GET',
					success( response ) {
						if ( response.status === 'success' ) {
							$button.text( btnText );

							$( '.lp-update-db-modal__content' ).addClass( 'lp-update-db-modal__success' );
							$( '.lp-update-db-modal__content-text > h3' ).text( textSuccess );

							$button.removeClass( 'loading' );
							return false;
						}
						updateRequest();
					},
				} );
			};

			updateRequest();
		} );

		const lpUpdateModal = () => {
			$( '.lp-update-db-modal__cancel' ).on( 'click', function( e ) {
				e.preventDefault();
				$( '#lp-update-db-modal' ).addClass( 'lp-update-db-modal__hidden' );
			} );
		};

		lpUpdateModal();
	};

	const lpMetaboxFileInput = () => {
		$( '.lp-meta-box__file' ).each( ( i, element ) => {
			let lpImageFrame;

			const imageGalleryIds = $( element ).find( '.lp-meta-box__file_input' );
			const listImages = $( element ).find( '.lp-meta-box__file_list' );
			const btnUpload = $( element ).find( '.btn-upload' );
			const isMultil = !! $( element ).data( 'multil' );

			$( btnUpload ).on( 'click', ( event ) => {
				event.preventDefault();

				if ( lpImageFrame ) {
					lpImageFrame.open();
					return;
				}

				lpImageFrame = wp.media( {
					states: [
						new wp.media.controller.Library( {
							filterable: 'all',
							multiple: isMultil,
						} ),
					],
				} );

				lpImageFrame.on( 'select', function() {
					const selection = lpImageFrame.state().get( 'selection' );
					let attachmentIds = imageGalleryIds.val();

					selection.forEach( function( attachment ) {
						attachment = attachment.toJSON();

						if ( attachment.id ) {
							if ( ! isMultil ) {
								attachmentIds = attachment.id;
								listImages.empty();
							} else {
								attachmentIds = attachmentIds ? attachmentIds + ',' + attachment.id : attachment.id;
							}

							if ( attachment.type === 'image' ) {
								const attachmentImage = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

								listImages.append(
									'<li class="lp-meta-box__file_list-item image" data-attachment_id="' + attachment.id + '"><img src="' + attachmentImage +
							'" /><ul class="actions"><li><a href="#" class="delete"></a></li></ul></li>'
								);
							} else {
								listImages.append(
									'<li class="lp-meta-box__file_list-item image" data-attachment_id="' + attachment.id + '"><img class="is_file" src="' + attachment.icon +
							'" /><span>' + attachment.filename + '</span><ul class="actions"><li><a href="#" class="delete"></a></li></ul></li>'
								);
							}
						}
					} );

					delImage();

					imageGalleryIds.val( attachmentIds );
				} );

				lpImageFrame.open();
			} );

			if ( isMultil ) {
				listImages.sortable( {
					items: 'li.image',
					cursor: 'move',
					scrollSensitivity: 40,
					forcePlaceholderSize: true,
					forceHelperSize: false,
					helper: 'clone',
					opacity: 0.65,
					placeholder: 'lp-metabox-sortable-placeholder',
					start( event, ui ) {
						ui.item.css( 'background-color', '#f6f6f6' );
					},
					stop( event, ui ) {
						ui.item.removeAttr( 'style' );
					},
					update() {
						let attachmentIds = '';

						listImages.find( 'li.image' ).css( 'cursor', 'default' ).each( function() {
							const attachmentId = $( this ).attr( 'data-attachment_id' );
							attachmentIds = attachmentIds + attachmentId + ',';
						} );

						delImage();

						imageGalleryIds.val( attachmentIds );
					},
				} );
			}

			const delImage = () => {
				$( listImages ).find( 'li.image' ).each( ( i, ele ) => {
					const del = $( ele ).find( 'a.delete' );

					del.on( 'click', function() {
						$( ele ).remove();

						if ( isMultil ) {
							let attachmentIds = '';

							$( listImages ).find( 'li.image' ).css( 'cursor', 'default' ).each( function() {
								const attachmentId = $( this ).attr( 'data-attachment_id' );
								attachmentIds = attachmentIds + attachmentId + ',';
							} );

							imageGalleryIds.val( attachmentIds );
						} else {
							imageGalleryIds.val( '' );
						}

						return false;
					} );
				} );
			};

			delImage();
		} );
	};

	const onReady = function onReady() {
		lpMetaboxFileInput();
		updateDb();
		$( '.learn-press-dropdown-pages' ).LP( 'DropdownPages' );
		$( '.learn-press-advertisement-slider' ).LP( 'Advertisement', 'a', 's' ).appendTo( $( '#wpbody-content' ) );
		$( '.learn-press-toggle-item-preview' ).on( 'change', updateItemPreview );
		$( '.learn-press-tip' ).LP( 'QuickTip' ); //$('.learn-press-tabs').LP('AdminTab');

		$( document ).on( 'click', '#learn-press-create-pages', createPages )
			.on( 'click', '.lp-upgrade-notice .close-notice', hideUpgradeMessage )
			.on( 'click', '.plugin-action-buttons a', pluginActions )
			.on( 'click', '[data-remove-confirm]', preventDefault )
			.on( 'mousedown', '.lp-sortable-handle', function( e ) {
			$( 'html, body' ).addClass( 'lp-item-moving' );
			$( e.target ).closest( '.lp-sortable-handle' ).css( 'cursor', 'inherit' );
		} ).on( 'mouseup', function( e ) {
			$( 'html, body' ).removeClass( 'lp-item-moving' );
			$( '.lp-sortable-handle' ).css( 'cursor', '' );
		} );

		/**
		 * Function Export invoice LP Order
		 *
		 * @author hungkv
		 * @since 3.2.7.8
		 */
		if ( $( '#order-export__section' ).length ) {
			const tabs = document.querySelectorAll( '.tabs' );
			const tab = document.querySelectorAll( '.tab' );
			const panel = document.querySelectorAll( '.panel' );

			function onTabClick( event ) {
				// deactivate existing active tabs and panel

				for ( let i = 0; i < tab.length; i++ ) {
					tab[ i ].classList.remove( 'active' );
				}

				for ( let i = 0; i < panel.length; i++ ) {
					panel[ i ].classList.remove( 'active' );
				}

				// activate new tabs and panel
				event.target.classList.add( 'active' );
				const classString = event.target.getAttribute( 'data-target' );
				document.getElementById( 'panels' ).getElementsByClassName( classString )[ 0 ].classList.add( 'active' );
			}

			for ( let i = 0; i < tab.length; i++ ) {
				tab[ i ].addEventListener( 'click', onTabClick, false );
			}

			// modal export order to pdf

			// Get the modal
			const modal = document.getElementById( 'myModal' );

			// Get the button that opens the modal
			const btn = document.getElementById( 'order-export__button' );

			// Get the <span> element that closes the modal
			const span = document.getElementsByClassName( 'close' )[ 0 ];

			// When the user clicks on the button, open the modal
			btn.onclick = function() {
				modal.style.display = 'block';
			};

			// When the user clicks on <span> (x), close the modal
			span.onclick = function() {
				modal.style.display = 'none';
			};

			// When the user clicks anywhere outside of the modal, close it
			window.onclick = function( event ) {
				if ( event.target == modal ) {
					modal.style.display = 'none';
				}
			};

			if ( $( '#lp-invoice__content' ).length ) {
				$( '#lp-invoice__export' ).click( function() {
					const doc = new jsPDF( 'p', 'pt', 'letter' );

					// We'll make our own renderer to skip this editor
					const specialElementHandlers = {
						'#bypassme'( element, renderer ) {
							return true;
						},
					};
					const margins = {
						top: 80,
						bottom: 60,
						left: 40,
						width: 522,
					};

					doc.fromHTML(
						$( '#lp-invoice__content' )[ 0 ],
						margins.left, // x coord
						margins.top, { // y coord
							width: margins.width, // max width of content on PDF
							elementHandlers: specialElementHandlers,
						},
						function( dispose ) {
							// dispose: object with X, Y of the last line add to the PDF
							//          this allow the insertion of new lines after html
							const blob = doc.output( 'blob' );
							window.open( URL.createObjectURL( blob ) );
						}, margins );
				} );
			}

			// Script update option export to pdf
			$( '#lp-invoice__update' ).click( function() {
				let order_id = $( this ).data( 'id' ),
					site_title = $( 'input[name="site_title"]' ),
					order_date = $( 'input[name="order_date"]' ),
					invoice_no = $( 'input[name="invoice_no"]' ),
					order_customer = $( 'input[name="order_customer"]' ),
					order_email = $( 'input[name="order_email"]' ),
					order_payment = $( 'input[name="order_payment"]' );
				if ( site_title.is( ':checked' ) ) {
					site_title = 'check';
				} else {
					site_title = 'uncheck';
				}
				if ( order_date.is( ':checked' ) ) {
					order_date = 'check';
				} else {
					order_date = 'uncheck';
				}
				if ( invoice_no.is( ':checked' ) ) {
					invoice_no = 'check';
				} else {
					invoice_no = 'uncheck';
				}
				if ( order_customer.is( ':checked' ) ) {
					order_customer = 'check';
				} else {
					order_customer = 'uncheck';
				}
				if ( order_email.is( ':checked' ) ) {
					order_email = 'check';
				} else {
					order_email = 'uncheck';
				}
				if ( order_payment.is( ':checked' ) ) {
					order_payment = 'check';
				} else {
					order_payment = 'uncheck';
				}

				$.ajax( {
					type: 'post',
					dataType: 'html',
					url: 'admin-ajax.php',
					data: {
						site_title,
						order_date,
						invoice_no,
						order_customer,
						order_email,
						order_id,
						order_payment,
						action: 'learnpress_update_order_exports',
					},
					beforeSend() {
						$( '.export-options__loading' ).addClass( 'active' );
					},
					success( response ) {
						$( '#lp-invoice__content' ).html( '' );
						$( '#lp-invoice__content' ).append( response );
						$( '.export-options__loading' ).removeClass( 'active' );
						$( '.options-tab' ).removeClass( 'active' );
						$( '.preview-tab' ).addClass( 'active' );
						$( '#panels .export-options' ).removeClass( 'active' );
						$( '#panels .pdf-preview' ).addClass( 'active' );
					},
					error( jqXHR, textStatus, errorThrown ) {
						console.log( 'The following error occured: ' + textStatus, errorThrown );
					},
				} );
			} );
		}

		$.fn._filter_post_by_author();

		// Scroll to Passing grade when click link final Quiz in Course Setting.
		if ( window.location.hash ) {
			const hash = window.location.hash;

			if ( hash == '#_lp_passing_grade' ) {
				const ele = document.querySelector( hash );

				$( 'html, body' ).animate( {
					scrollTop: $( hash ).offset().top,
				}, 900, 'swing' );

				ele.parentNode.style.border = '2px solid orangered';
			}
		}
	};

	$( document ).ready( onReady );
}( jQuery ) );
