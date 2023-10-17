( function( $ ) {
	/*const updateItemPreview = function updateItemPreview() {
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
	};*/
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
			placeholder: wp.i18n.__( 'Search by user', 'learnpress' ),
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
		//$( '.learn-press-toggle-item-preview' ).on( 'change', updateItemPreview );
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

		// Show/hide meta-box field with type checkbox
		$( 'input' ).on( 'click', function( e ) {
			const el = $( e.target );
			if ( ! el.length ) {
				return;
			}

			const id = el.attr( 'id' );
			if ( ! id ) {
				return;
			}

			const classHide = id.replace( 'learn_press_', '' );
			const elHide = $( `.show_if_${ classHide }` );

			if ( el.is( ':checked' ) ) {
				elHide.show();
			} else {
				elHide.hide();
			}
		} );
	};

	$( document ).ready( onReady );
}( jQuery ) );
