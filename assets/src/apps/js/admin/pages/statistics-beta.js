// console.log('load-js');
document.addEventListener( 'DOMContentLoaded', function () {
	const lpStatisticsLoad = () => {
		const elementLoad = document.querySelector( 'input.statistics-type' );
		if ( ! elementLoad ) {
			return;
		}
		if ( elementLoad.value == 'orders-statistics' ) {
			orderLoadData();
		} else if ( elementLoad.value == 'overview-statistics' ) {
			overviewLoadData();
		}
	};
	const overviewLoadData = ( filterType = 'today', date = '' ) => {
		wp.apiFetch( {
			path: wp.url.addQueryArgs(
				'lp/v1/statistics/overviews-statistics',
				{
					filtertype: filterType,
					date: date,
				}
			),
			method: 'GET',
		} )
			.then( ( res ) => {
				const { data, status, message } = res;
				if ( status === 'error' ) {
					throw new Error( message || 'Error' );
				}
				let chart = Chart.getChart( 'net-sales-chart-content' ),
					chartEle = document.getElementById(
						'net-sales-chart-content'
					);
				chartEle.removeAttribute( 'hidden' );
				loadLpSkeletonAnimations();
				if ( chart === undefined ) {
					chart = generateChart(
						'net-sales-chart-content',
						data.chart_data
					);
				} else {
					chart.data.labels = data.chart_data.labels;
					chart.data.datasets[ 0 ].data = data.chart_data.data;
					chart.config.options.scales.x.title.text =
						data.chart_data.x_label;
					chart.update();
				}
				document.querySelector( '.total-sales' ).textContent =
					data.total_sales;
				document.querySelector( '.total-orders' ).textContent =
					data.total_orders;
				document.querySelector( '.total-courses' ).textContent =
					data.total_courses;
				document.querySelector( '.total-instructors' ).textContent =
					data.total_instructors;
				document.querySelector( '.total-students' ).textContent =
					data.total_students;
				if ( data.top_courses.length > 0 ) {
					let topCourses = data.top_courses,
						topCoursesWrap =
							document.querySelector( '.top-course-sold' );
					for ( let i = 0; i < topCourses.length; i++ ) {
						topCoursesWrap.insertAdjacentHTML(
							'beforeend',
							`<li>${ topCourses[ i ].course_name } - ${ topCourses[ i ].course_count }</li>`
						);
					}
				}
				if ( data.top_categories.length > 0 ) {
					let topCategories = data.top_categories,
						topCategoriesWrap =
							document.querySelector( '.top-category-sold' );
					for ( let i = 0; i < topCategories.length; i++ ) {
						topCategoriesWrap.insertAdjacentHTML(
							'beforeend',
							`<li>${ topCategories[ i ].term_name } - ${ topCategories[ i ].term_count }</li>`
						);
					}
				}
			} )
			.catch( ( err ) => {
				console.log( err );
			} )
			.finally( () => {} );
	};
	const orderLoadData = ( filterType = 'today', date = '' ) => {
		wp.apiFetch( {
			path: wp.url.addQueryArgs( 'lp/v1/statistics/order-statistics', {
				filtertype: filterType,
				date: date,
			} ),
			method: 'GET',
		} )
			.then( ( res ) => {
				const { data, status, message } = res;
				if ( status === 'error' ) {
					throw new Error( message || 'Error' );
				}
				let chart = Chart.getChart( 'orders-chart-content' );

				if ( chart === undefined ) {
					chart = generateChart(
						'orders-chart-content',
						data.chart_data
					);
				} else {
					chart.data.labels = data.chart_data.labels;
					chart.data.datasets[ 0 ].data = data.chart_data.data;
					chart.config.options.scales.x.title.text =
						data.chart_data.x_label;
					chart.update();
				}
				if ( data.statistics.length > 0 ) {
					let totalOrder = 0;
					for ( let i = data.statistics.length - 1; i >= 0; i-- ) {
						let v = data.statistics[ i ];
						if ( v.order_status == 'completed' ) {
							document.querySelector(
								'.completed-order-count'
							).textContent = v.count_order;
							totalOrder += ~~v.count_order;
						} else if ( v.order_status == 'pending' ) {
							document.querySelector(
								'.pending-order-count'
							).textContent = v.count_order;
							totalOrder += ~~v.count_order;
						} else if ( v.order_status == 'processing' ) {
							document.querySelector(
								'.processing-order-count'
							).textContent = v.count_order;
							totalOrder += ~~v.count_order;
						} else if ( v.order_status == 'cancelled' ) {
							document.querySelector(
								'.cancelled-order-count'
							).textContent = v.count_order;
							totalOrder += ~~v.count_order;
						} else if ( v.order_status == 'failed' ) {
							document.querySelector(
								'.failed-order-count'
							).textContent = v.count_order;
							totalOrder += ~~v.count_order;
						}
					}
					document.querySelector( '.total-order-count' ).textContent =
						totalOrder;
				} else {
					document.querySelector(
						'.completed-order-count'
					).textContent = 0;
					document.querySelector(
						'.pending-order-count'
					).textContent = 0;
					document.querySelector(
						'.processing-order-count'
					).textContent = 0;
					document.querySelector(
						'.cancelled-order-count'
					).textContent = 0;
					document.querySelector(
						'.failed-order-count'
					).textContent = 0;
					document.querySelector(
						'.total-order-count'
					).textContent = 0;
				}
			} )
			.catch( ( err ) => {
				console.log( err );
			} )
			.finally( () => {} );
	};
	const generateChart = ( chartEle = '', data = [] ) => {
		let canvas = document.getElementById( chartEle );
		const chart_data = {
			labels: data.labels,
			datasets: [
				{
					label: data.line_label,
					borderColor: 'blue',
					data: data.data,
					backgroundColor: 'blue',
				},
			],
		};
		const config = {
			type: 'line',
			data: chart_data,
			options: {
				responsive: true,
				plugins: {
					legend: {
						display: false,
					},
				},
				scales: {
					y: {
						min: 0,
						ticks: {
							// stepSize: 1,
						},
						title: {
							display: true,
							text: data.line_label,
						},
					},
					x: {
						title: {
							display: true,
							text: data.x_label,
						},
					},
				},
			},
		};
		const chart = new Chart( canvas, config );
		return chart;
	};
	const loadLpSkeletonAnimations = ( show = false ) => {
		if ( show ) {
			document
				.querySelectorAll( '.lp-skeleton-animation' )
				.forEach( ( animation ) => {
					animation.style.display = 'block';
				} );
		} else {
			document
				.querySelectorAll( '.lp-skeleton-animation' )
				.forEach( ( animation ) => {
					animation.style.display = 'none';
				} );
		}
	};
	document.querySelectorAll( '.btn-filter-time' ).forEach( ( btn ) =>
		btn.addEventListener( 'click', () => {
			let filterType = btn.dataset.filter;
			if ( filterType == 'custom' ) {
				document.querySelector( '.custom-filter-time' ).style.display =
					'flex';
			} else {
				let elementLoad = document.querySelector(
					'input.statistics-type'
				);
				if ( elementLoad ) {
					document
						.querySelector( '.statistics-content canvas' )
						.setAttribute( 'hidden', true );
					loadLpSkeletonAnimations( true );
					if ( elementLoad.value == 'orders-statistics' ) {
						orderLoadData( filterType );
					} else if ( elementLoad.value == 'overview-statistics' ) {
						document.querySelector(
							'.top-category-sold'
						).innerHTML = '';
						document.querySelector( '.top-course-sold' ).innerHTML =
							'';
						overviewLoadData( filterType );
					}
				}
			}
		} )
	);
	document
		.querySelector( '.custom-filter-btn' )
		.addEventListener( 'click', ( e ) => {
			let time1 = document.querySelector( '#ct-filter-1' ).value,
				time2 = document.querySelector( '#ct-filter-2' ).value;
			if ( ! time1 || ! time2 ) {
				alert( 'Choose date' );
			} else {
				let elementLoad = document.querySelector(
					'input.statistics-type'
				);
				document
					.querySelector( '.statistics-content canvas' )
					.setAttribute( 'hidden', true );
				loadLpSkeletonAnimations( true );
				if ( elementLoad ) {
					if ( elementLoad.value == 'orders-statistics' ) {
						orderLoadData( 'custom', `${ time1 }+${ time2 }` );
					} else if ( elementLoad.value == 'overview-statistics' ) {
						document.querySelector(
							'.top-category-sold'
						).innerHTML = '';
						document.querySelector( '.top-course-sold' ).innerHTML =
							'';
						overviewLoadData( 'custom', `${ time1 }+${ time2 }` );
					}
				}
			}
		} );
	lpStatisticsLoad();
} );
