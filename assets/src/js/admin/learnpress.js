const $ = jQuery;
const $doc = $( document );
const $win = $( window );

const makePaymentsSortable = function makePaymentsSortable() {
	// Make payments sortable
	$( '.learn-press-payments.sortable tbody' ).sortable( {
		handle: '.dashicons-menu',
		helper( e, ui ) {
			ui.children().each( function() {
				$( this ).width( $( this ).width() );
			} );
			return ui;
		},
		axis: 'y',
		start( event, ui ) {

		},
		stop( event, ui ) {

		},
		update( event, ui ) {
			const order = $( this ).children().map( function() {
				return $( this ).find( 'input[name="payment-order"]' ).val();
			} ).get();

			$.post( {
				url: '',
				data: {
					'lp-ajax': 'update-payment-order',
					order,
				},
				success( response ) {
				},
			} );
		},
	} );
};

const initTooltips = function initTooltips() {
	$( '.learn-press-tooltip' ).each( function() {
		const $el = $( this ),
			args = $.extend( { title: 'data-tooltip', offset: 10, gravity: 's' }, $el.data() );
		$el.tipsy( args );
	} );
};

const initSelect2 = function initSelect2() {
	if ( $.fn.select2 ) {
		$( '.lp-select-2 select' ).select2();
	}
};

const initSingleCoursePermalink = function initSingleCoursePermalink() {
	$doc
		.on( 'change', '.learn-press-single-course-permalink input[type="radio"]', function() {
			const $check = $( this ),
				$row = $check.closest( '.learn-press-single-course-permalink' );
			if ( $row.hasClass( 'custom-base' ) ) {
				$row.find( 'input[type="text"]' ).prop( 'readonly', false );
			} else {
				$row.siblings( '.custom-base' ).find( 'input[type="text"]' ).prop( 'readonly', true );
			}
		} )
		.on( 'change', 'input.learn-press-course-base', function() {
			$( '#course_permalink_structure' ).val( $( this ).val() );
		} )
		.on( 'focus', '#course_permalink_structure', function() {
			$( '#learn_press_custom_permalink' ).click();
		} )
		.on( 'change', '#learn_press_courses_page_id', function() {
			$( 'tr.learn-press-courses-page-id' ).toggleClass( 'hide-if-js', ! parseInt( this.value ) );
		} );
};

const togglePaymentStatus = function togglePaymentStatus( e ) {
	e.preventDefault();
	const $row = $( this ).closest( 'tr' ),
		$button = $( this ),
		status = $row.find( '.status' ).hasClass( 'enabled' ) ? 'no' : 'yes';

	$.ajax( {
		url: '',
		data: {
			'lp-ajax': 'update-payment-status',
			status,
			id: $row.data( 'payment' ),
		},
		success( response ) {
			response = LP.parseJSON( response );
			for ( const i in response ) {
				$( '#payment-' + i + ' .status' ).toggleClass( 'enabled', response[ i ] );
			}
		},
	} );
};

const updateEmailStatus = function updateEmailStatus() {
	( function() {
		$.post( {
			url: window.location.href,
			data: {
				'lp-ajax': 'update_email_status',
				status: $( this ).parent().hasClass( 'enabled' ) ? 'no' : 'yes',
				id: $( this ).data( 'id' ),
			},
			dataType: 'text',
			success: $.proxy( function( res ) {
				res = LP.parseJSON( res );
				for ( const i in res ) {
					$( '#email-' + i + ' .status' ).toggleClass( 'enabled', res[ i ] );
				}
			}, this ),
		} );
	} ).apply( this );
};

const toggleSalePriceSchedule = function toggleSalePriceSchedule() {
	const $el = $( this ),
		id = $el.attr( 'id' );

	if ( id === '_lp_sale_price_schedule' ) {
		$( this ).hide();
		$( '#field-_lp_sale_start, #field-_lp_sale_end' ).removeClass( 'hide-if-js' );
		$win.trigger( 'resize.calculate-tab' );
	} else {
		$( '#_lp_sale_price_schedule' ).show();
		$( '#field-_lp_sale_start, #field-_lp_sale_end' ).addClass( 'hide-if-js' ).find( '#_lp_sale_start, #_lp_sale_end' ).val( '' );
		$win.trigger( 'resize.calculate-tab' );
	}

	return false;
};

const callbackFilterTemplates = function callbackFilterTemplates() {
	const $link = $( this );

	if ( $link.hasClass( 'current' ) ) {
		return false;
	}

	const $templatesList = $( '#learn-press-template-files' ),
		$templates = $templatesList.find( 'tr[data-template]' ),
		template = $link.data( 'template' ),
		filter = $link.data( 'filter' );

	$link.addClass( 'current' ).siblings( 'a' ).removeClass( 'current' );

	if ( ! template ) {
		if ( ! filter ) {
			$templates.removeClass( 'hide-if-js' );
		} else {
			$templates.map( function() {
				$( this ).toggleClass( 'hide-if-js', $( this ).data( 'filter-' + filter ) !== 'yes' );
			} );
		}
	} else {
		$templates.map( function() {
			$( this ).toggleClass( 'hide-if-js', $( this ).data( 'template' ) !== template );
		} );
	}

	$( '#learn-press-no-templates' ).toggleClass( 'hide-if-js', !! $templatesList.find( 'tr.template-row:not(.hide-if-js):first' ).length );

	return false;
};

const toggleEmails = function toggleEmails( e ) {
	e.preventDefault();
	const $button = $( this ),
		status = $button.data( 'status' );

	$.ajax( {
		url: '',
		data: {
			'lp-ajax': 'update_email_status',
			status,
		},
		success( response ) {
			response = LP.parseJSON( response );
			for ( const i in response ) {
				$( '#email-' + i + ' .status' ).toggleClass( 'enabled', response[ i ] );
			}
		},
	} );
};

const importCourses = function importCourses() {
	const $container = $( '#learn-press-install-sample-data-notice' ),
		action = $( this ).attr( 'data-action' );
	if ( ! action ) {
		return;
	}
	e.preventDefault();

	if ( action === 'yes' ) {
		$container
			.find( '.install-sample-data-notice' ).slideUp()
			.siblings( '.install-sample-data-loading' ).slideDown();
	} else {
		$container.fadeOut();
	}
	$.ajax( {
		url: ajaxurl,
		dataType: 'html',
		type: 'post',
		data: {
			action: 'learnpress_install_sample_data',
			yes: action,
		},
		success( response ) {
			response = LP.parseJSON( response );
			if ( response.url ) {
				$.ajax( {
					url: response.url,
					success() {
						$container
							.find( '.install-sample-data-notice' ).html( response.message ).slideDown()
							.siblings( '.install-sample-data-loading' ).slideUp();
					},
				} );
			} else {
				$container
					.find( '.install-sample-data-notice' ).html( response.message ).slideDown()
					.siblings( '.install-sample-data-loading' ).slideUp();
			}
		},
	} );
};

const onChangeCoursePrices = function onChangeCoursePrices( e ) {
	const _self = $( this ),
		_price = $( '#_lp_price' ),
		_sale_price = $( '#_lp_sale_price' ),
		_target = $( e.target ).attr( 'id' );
	_self.find( '#field-_lp_price div, #field-_lp_sale_price div' ).remove( '.learn-press-tip-floating' );
	if ( parseInt( _sale_price.val() ) >= parseInt( _price.val() ) ) {
		if ( _target === '_lp_price' ) {
			_price.parent( '.rwmb-input' ).append( '<div class="learn-press-tip-floating">' + lpAdminCourseEditorSettings.i18n.notice_price + '</div>' );
		} else if ( _target === '_lp_sale_price' ) {
			_sale_price.parent( '.rwmb-input' ).append( '<div class="learn-press-tip-floating">' + lpAdminCourseEditorSettings.i18n.notice_sale_price + '</div>' );
		}
	}
};

const onChangeSaleStartDate = function onChangeSaleStartDate( e ) {
	const _sale_start_date = $( this ),
		_sale_end_date = $( '#_lp_sale_end' ),
		_start_date = Date.parse( _sale_start_date.val() ),
		_end_date = Date.parse( _sale_end_date.val() ),
		_parent_start = _sale_start_date.parent( '.rwmb-input' ),
		_parent_end = _sale_end_date.parent( '.rwmb-input' );

	if ( ! _start_date ) {
		_parent_start.append( '<div class="learn-press-tip-floating">' + lpAdminCourseEditorSettings.i18n.notice_invalid_date + '</div>' );
	}

	$( '#field-_lp_sale_start div, #field-_lp_sale_end div' ).remove( '.learn-press-tip-floating' );

	if ( _start_date > _end_date ) {
		_parent_start.append( '<div class="learn-press-tip-floating">' + lpAdminCourseEditorSettings.i18n.notice_sale_start_date + '</div>' );
	}
};

const onChangeSaleEndDate = function onChangeSaleEndDate( e ) {
	const _sale_end_date = $( this ),
		_sale_start_date = $( '#_lp_sale_start' ),
		_start_date = Date.parse( _sale_start_date.val() ),
		_end_date = Date.parse( _sale_end_date.val() ),
		_parent_start = _sale_start_date.parent( '.rwmb-input' ),
		_parent_end = _sale_end_date.parent( '.rwmb-input' );

	if ( ! _end_date ) {
		_parent_end.append( '<div class="learn-press-tip-floating">' + lpAdminCourseEditorSettings.i18n.notice_invalid_date + '</div>' );
	}

	$( '#field-_lp_sale_start div, #field-_lp_sale_end div' ).remove( '.learn-press-tip-floating' );
	if ( _start_date > _end_date ) {
		_parent_end.append( '<div class="learn-press-tip-floating">' + lpAdminCourseEditorSettings.i18n.notice_sale_end_date + '</div>' );
	}
};

const onReady = function onReady() {
	makePaymentsSortable();
	initSelect2();
	initTooltips();
	initSingleCoursePermalink();

	$( '.learn-press-tabs' ).LP( 'AdminTab' );

	$( document )
		.on( 'click', '.learn-press-payments .status .dashicons', togglePaymentStatus )
		.on( 'click', '.change-email-status', updateEmailStatus )
		.on( 'click', '#_lp_sale_price_schedule', toggleSalePriceSchedule )
		.on( 'click', '#_lp_sale_price_schedule_cancel', toggleSalePriceSchedule )
		.on( 'click', '.learn-press-filter-template', callbackFilterTemplates )
		.on( 'click', '#learn-press-enable-emails, #learn-press-disable-emails', toggleEmails )
		.on( 'click', '#learn-press-install-sample-data-notice a', importCourses )
		.on( 'input', '#meta-box-tab-course_payment', onChangeCoursePrices )
		.on( 'change', '#_lp_sale_start', onChangeSaleStartDate )
		.on( 'change', '#_lp_sale_end', onChangeSaleEndDate );
};

$( document ).ready( onReady );
