( function( $ ) {
	$.fn.LP_Chart_Line = function( data, config ) {
		return $.each( this, function() {
			const $elem = $( this ),
				$canvas = $( '<canvas />' );
			$elem.html( '' );
			$canvas.appendTo( $elem );
			var lineChart = new Chart($canvas,{
				type: 'line',
				data: data,
				options: config
			})
		} );
		//
	};

	$.fn.LP_Statistic_Users = function() {
		if ( parseInt( $( this ).length ) === 0 ) {
			return;
		}
		return $.each( this, function() {
			const $buttons = $( '.chart-buttons button' ).on( 'click', function() {
					const $button = $( this ),
						type = $button.data( 'type' );
					let from = '',
						to = '';
					$buttons.not( this ).not( '[data-type="user-custom-time"]' ).prop( 'disabled', false );
					if ( type == 'user-custom-time' ) {
						from = $( '#user-custom-time input[name="from"]' ).val();
						to = $( '#user-custom-time input[name="to"]' ).val();

						if ( from == '' || to == '' ) {
							return false;
						}
					} else {
						$button.prop( 'disabled', true );
					}

					const $container = $( '#learn-press-chart' );
					$container.addClass( 'loading' );
					$.ajax( {
						url: 'admin-ajax.php',
						data: {
							action: 'learnpress_load_chart',
							type,
							range: [ from, to ],
						},
						dataType: 'text',
						success( response ) {
							response = LP.parseJSON( response );
							$container.LP_Chart_Line( response, LP_Chart_Config );
							$container.removeClass( 'loading' );
						},
					} );
					return false;
				} ),
				$inputs = $( '.chart-buttons #user-custom-time input[type="text"]' ).on( 'change', function() {
					const _valid_date = function() {
						if ( new Date( $inputs[ 0 ].value ) < new Date( $inputs[ 1 ].value ) ) {
							return true;
						}
					};
					$buttons.filter( '[data-type="user-custom-time"]' ).prop( 'disabled', $inputs.filter( function() {
						return this.value == '';
					} ).get().length || ! _valid_date() );
				} );
		} );
	};

	$.fn.LP_Statistic_Courses = function() {
		if ( parseInt( $( this ).length ) === 0 ) {
			return;
		}
		return $.each( this, function() {
			var $buttons = $( '.chart-buttons button' ).on( 'click', function() {
					let $button = $( this ),
						type = $button.data( 'type' ),
						from = '',
						to = '',
						$container = $( '#learn-press-chart' );
					$buttons.not( this ).not( '[data-type="course-custom-time"]' ).prop( 'disabled', false );
					if ( type == 'course-custom-time' ) {
						from = $( '#course-custom-time input[name="from"]' ).val();
						to = $( '#course-custom-time input[name="to"]' ).val();

						if ( from == '' || to == '' ) {
							return false;
						}
					} else {
						$button.prop( 'disabled', true );
					}
					$container.addClass( 'loading' );
					$.ajax( {
						url: 'admin-ajax.php',
						data: {
							action: 'learnpress_load_chart',
							type,
							range: [ from, to ],
						},
						dataType: 'text',
						success( response ) {
							response = LP.parseJSON( response );
							$container.LP_Chart_Line( response, LP_Chart_Config );
							$container.removeClass( 'loading' );
						},
					} );
					return false;
				} ),
				$inputs = $( '.chart-buttons #course-custom-time input[type="text"]' ).on( 'change', function() {
					const _valid_date = function() {
						if ( new Date( $inputs[ 0 ].value ) < new Date( $inputs[ 1 ].value ) ) {
							return true;
						}
					};
					$buttons.filter( '[data-type="course-custom-time"]' ).prop( 'disabled', $inputs.filter( function() {
						return this.value == '';
					} ).get().length || ! _valid_date() );
				} );
		} );
	};

	$.fn.LP_Statistic_Orders = function() {
		if ( parseInt( $( this ).length ) === 0 ) {
			return;
		}
		$( '.panel_report_option' ).hide();
		$( '#panel_report_sales_by_' + $( '#report_sales_by' ).val() ).show();
		$( '#report_sales_by' ).on( 'change', function() {
			$( '.panel_report_option' ).hide();
			$( '#panel_report_sales_by_' + $( this ).val() ).show();
			if ( 'date' == $( this ).val() ) {
				LP_Statistic_Orders_Upgrade_Chart();
			}
		} );

		/**
		 * Upgrade Chart for Order Statistics
		 *
		 * @return {boolean}
		 */
		var LP_Statistic_Orders_Upgrade_Chart = function() {
			let type = '',
				from = '',
				to = '',
				report_sales_by = 'date',
				cat_id = 0,
				course_id = 0;
			report_sales_by = $( '#report_sales_by' ).val();
			$container = $( '#learn-press-chart' );
			$container.addClass( 'loading' );
			// get type
			const $buttons = $( '.chart-buttons button:disabled' ).not( '[data-type="order-custom-time"]' );
			if ( parseInt( $buttons.length ) > 0 ) {
				type	= $( $buttons[ 0 ] ).data( 'type' );
			} else {
				type	= 'order-custom-time';
				from	= $( '#order-custom-time input[name="from"]' ).val();
				to		= $( '#order-custom-time input[name="to"]' ).val();
				if ( from == '' || to == '' ) {
					return false;
				}
			}
			if ( 'course' === report_sales_by ) {
				course_id = $( '#report-by-course-id' ).val();
			} else if ( 'category' === report_sales_by ) {
				cat_id = $( '#report-by-course-category-id' ).val();
			}

			$.ajax( {
				url: 'admin-ajax.php',
				data: {
					action: 'learnpress_load_chart',
					type,
					range: [ from, to ],
					report_sales_by,
					course_id,
					cat_id,
				},
				dataType: 'text',
				success( response ) {
					response = LP.parseJSON( response );
					$container.LP_Chart_Line( response, LP_Chart_Config );
					$container.removeClass( 'loading' );
				},
			} );
		};

		$( '#report-by-course-id' ).select2( {
			placeholder: 'Select a course',
			minimumInputLength: 1,
			ajax: {
				url: ajaxurl + '?action=learnpress_search_course',
				dataType: 'json',
				quietMillis: 250,
				data( term, page ) {
					return {
						q: term, // search term
					};
				},
				results( data, page ) {
					return { results: data.items };
				},
				cache: true,
			},
		} );

		$( '#report-by-course-id' ).on( 'change', function() {
			LP_Statistic_Orders_Upgrade_Chart();
		} );

		$( '#report-by-course-category-id' ).select2( {
			placeholder: 'Select a course',
			minimumInputLength: 1,
			ajax: {
				url: ajaxurl + '?action=learnpress_search_course_category',
				dataType: 'json',
				quietMillis: 250,
				data( term, page ) {
					return {
						q: term, // search term
					};
				},
				results( data, page ) {
					return { results: data.items };
				},
				cache: true,
			},
		} );

		$( '#report-by-course-category-id' ).on( 'change', function() {
			LP_Statistic_Orders_Upgrade_Chart();
		} );

		var $buttons = $( '.chart-buttons button' ).on( 'click', function() {
			const $button = $( this ),
				type = $button.data( 'type' ),
				from = '',
				to = '',
				$container = $( '#learn-press-chart' );
			$buttons.not( this ).not( '[data-type="order-custom-time"]' ).prop( 'disabled', false );
			if ( type !== 'order-custom-time' ) {
				$button.prop( 'disabled', true );
				$( '#order-custom-time input[name="from"]' ).val( '' );
				$( '#order-custom-time input[name="to"]' ).val( '' );
			}
			LP_Statistic_Orders_Upgrade_Chart();
			return false;
		} );

		var $inputs = $( '.chart-buttons #order-custom-time input[type="text"]' ).on( 'change', function() {
			const _valid_date = function() {
				if ( new Date( $inputs[ 0 ].value ) < new Date( $inputs[ 1 ].value ) ) {
					return true;
				}
			};
			$buttons.filter( '[data-type="order-custom-time"]' ).prop( 'disabled', $inputs.filter( function() {
				return this.value == '';
			} ).get().length || ! _valid_date() );
		} );
	};
	$( function() {
		if ( typeof $.fn.datepicker != 'undefined' ) {
			$( '.date-picker' ).datepicker( {
				dateFormat: 'yy/mm/dd',
			} );
		}
		$( '.learn-press-statistic-users' ).LP_Statistic_Users();
		$( '.learn-press-statistic-courses' ).LP_Statistic_Courses();
		$( '.learn-press-statistic-orders' ).LP_Statistic_Orders();
	} );
	return;

}( jQuery ) );


