/**
 * Statistics chart.
 *
 * @since 4.2.5.5
 * @version 1.0.0
 */

import Chart from 'chart.js/auto';

document.addEventListener( 'DOMContentLoaded', function() {
	const lpStatisticsLoad = () => {
		const elementLoad = document.querySelector( 'input.statistics-type' );
		if ( ! elementLoad ) {
			return;
		}
		if ( elementLoad.value === 'orders-statistics' ) {
			orderLoadData();
		} else if ( elementLoad.value === 'overview-statistics' ) {
			overviewLoadData();
		} else if ( elementLoad.value === 'courses-statistics' ) {
			courseLoadData();
		} else if ( elementLoad.value === 'users-statistics' ) {
			userLoadData();
		}
	};
	const overviewLoadData = ( filterType = 'today', date = '' ) => {
		wp.apiFetch( {
			path: wp.url.addQueryArgs(
				'lp/v1/statistics/overviews-statistics',
				{
					filtertype: filterType,
					date,
				}
			),
			method: 'GET',
		} )
			.then( ( res ) => {
				const { data, status, message } = res;
				if ( status === 'error' ) {
					throw new Error( message || 'Error' );
				}
				const configChartOverview = {
					options: {
						scales: {
							y: {
								min: 0,
								ticks: {
									callback( value, index, ticks ) {
										return '$' + value;
									},
								},
							},
						},
					},
				};
				initStatisticChart(
					'net-sales-chart-content',
					data.chart_data,
					configChartOverview
				);
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
					const topCourses = data.top_courses,
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
					const topCategories = data.top_categories,
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
				date,
			} ),
			method: 'GET',
		} )
			.then( ( res ) => {
				const { data, status, message } = res;
				if ( status === 'error' ) {
					throw new Error( message || 'Error' );
				}
				initStatisticChart( 'orders-chart-content', data.chart_data );
				// chartEle.style.display = 'block';
				if ( data.statistics.length > 0 ) {
					let totalOrder = 0;
					for ( let i = data.statistics.length - 1; i >= 0; i-- ) {
						const v = data.statistics[ i ];
						if ( v.order_status == 'completed' ) {
							document.querySelector(
								'.completed-order-count'
							).textContent = v.count_order;
							totalOrder += parseInt( v.count_order );
						} else if ( v.order_status == 'pending' ) {
							document.querySelector(
								'.pending-order-count'
							).textContent = v.count_order;
							totalOrder += parseInt( v.count_order );
						} else if ( v.order_status == 'processing' ) {
							document.querySelector(
								'.processing-order-count'
							).textContent = v.count_order;
							totalOrder += parseInt( v.count_order );
						} else if ( v.order_status == 'cancelled' ) {
							document.querySelector(
								'.cancelled-order-count'
							).textContent = v.count_order;
							totalOrder += parseInt( v.count_order );
						} else if ( v.order_status == 'failed' ) {
							document.querySelector(
								'.failed-order-count'
							).textContent = v.count_order;
							totalOrder += parseInt( v.count_order );
						}
					}
					document.querySelector( '.total-order-count' ).textContent =
						totalOrder;
				} else {
					document
						.querySelectorAll( '.statistics-item-count' )
						.forEach( ( ele ) => {
							ele.textContent = 0;
						} );
				}
			} )
			.catch( ( err ) => {
				console.log( err );
			} )
			.finally( () => {} );
	};
	const courseLoadData = ( filterType = 'today', date = '' ) => {
		wp.apiFetch( {
			path: wp.url.addQueryArgs( 'lp/v1/statistics/course-statistics', {
				filtertype: filterType,
				date,
			} ),
			method: 'GET',
		} )
			.then( ( res ) => {
				const { data, status, message } = res;
				if ( status === 'error' ) {
					throw new Error( message || 'Error' );
				}
				initStatisticChart( 'course-chart-content', data.chart_data );
				if ( data.courses.length > 0 ) {
					let totalCourse = 0;
					for ( let i = 0; i < data.courses.length; i++ ) {
						const v = data.courses[ i ];
						if ( v.course_status == 'publish' ) {
							document.querySelector(
								'.statistics-courses.published'
							).textContent = v.course_count;
							totalCourse += parseInt( v.course_count );
						} else if ( v.course_status == 'pending' ) {
							document.querySelector(
								'.statistics-courses.pending'
							).textContent = v.course_count;
							totalCourse += parseInt( v.course_count );
						} else if ( v.course_status == 'future' ) {
							document.querySelector(
								'.statistics-courses.future'
							).textContent = v.course_count;
							totalCourse += parseInt( v.course_count );
						}
					}
					document.querySelector(
						'.statistics-courses.total'
					).textContent = totalCourse;
				} else {
					document
						.querySelectorAll( '.statistics-courses' )
						.forEach( ( ele ) => {
							ele.textContent = 0;
						} );
				}
				if ( data.items.length > 0 ) {
					for ( let i = 0; i < data.items.length; i++ ) {
						const v = data.items[ i ];
						if ( v.item_type == 'lp_lesson' ) {
							document.querySelector(
								'.statistics-items.lessons'
							).textContent = v.item_count;
						} else if ( v.item_type == 'lp_quiz' ) {
							document.querySelector(
								'.statistics-items.quizes'
							).textContent = v.item_count;
						} else if ( v.item_type == 'lp_assignment' ) {
							document.querySelector(
								'.statistics-items.assignment'
							).textContent = v.item_count;
						}
					}
				} else {
					document
						.querySelectorAll( '.statistics-items' )
						.forEach( ( ele ) => {
							ele.textContent = 0;
						} );
				}
			} )
			.catch( ( err ) => {
				console.log( err );
			} )
			.finally( () => {} );
	};
	const userLoadData = ( filterType = 'today', date = '' ) => {
		wp.apiFetch( {
			path: wp.url.addQueryArgs( 'lp/v1/statistics/user-statistics', {
				filtertype: filterType,
				date,
			} ),
			method: 'GET',
		} )
			.then( ( res ) => {
				const { data, status, message } = res;
				if ( status === 'error' ) {
					throw new Error( message || 'Error' );
				}
				initStatisticChart( 'user-chart-content', data.chart_data );
				const totalUserActived = 0;
				document.querySelector(
					'.statistics-instructors'
				).textContent = data.total_instructors;
				document.querySelector( '.statistics-students' ).textContent =
					data.total_students;
				document.querySelector(
					'.statistics-user-actived'
				).textContent = data.total_instructors + data.total_students;
				document.querySelector(
					'.statistics-not-started'
				).textContent = data.user_not_start_course;
				if ( data.user_course_statused.length > 0 ) {
					let userGraduration = data.user_course_statused,
						userFinished = 0;
					for ( let i = 0; i < userGraduration.length; i++ ) {
						if (
							userGraduration[ i ].graduation_status ===
							'in-progress'
						) {
							document.querySelector(
								'.statistics-graduration.in-progress'
							).textContent = userGraduration[ i ].user_count;
						} else {
							userFinished += parseInt( userGraduration[ i ].user_count );
						}
					}
					document.querySelector(
						'.statistics-graduration.finished'
					).textContent = userFinished;
				} else {
					document
						.querySelectorAll( '.statistics-graduration' )
						.forEach( ( ele ) => {
							ele.textContent = 0;
						} );
				}
				if ( Object.keys( data.top_enrolled_instructor ).length > 0 ) {
					const topInstructor = data.top_enrolled_instructor,
						topInstructorWrap = document.querySelector(
							'.top-intructor-by-student'
						);
					Object.keys( topInstructor ).forEach( function( key ) {
						// console.log(key, topInstructor[key]);
						topInstructorWrap.insertAdjacentHTML(
							'beforeend',
							`<li>${ topInstructor[ key ].name } - ${ topInstructor[ key ].students }</li>`
						);
					} );
				}
				if ( data.top_enrolled_courses.length > 0 ) {
					const topCourse = data.top_enrolled_courses,
						topCourseWrap = document.querySelector(
							'.top-course-by-student'
						);
					for ( let i = 0; i < topCourse.length; i++ ) {
						topCourseWrap.insertAdjacentHTML(
							'beforeend',
							`<li>${ topCourse[ i ].course_name } - ${ topCourse[ i ].enrolled_user }</li>`
						);
					}
				}
			} )
			.catch( ( err ) => {
				console.log( err );
			} )
			.finally( () => {} );
	};
	const generateChart = ( chartEle = '', data = [], config = {} ) => {
		const canvas = document.getElementById( chartEle );
		const chart_data = {
			labels: data.labels,
			datasets: [
				{
					label: data.line_label,
					borderColor: 'rgb(49 74 199)',
					borderWidth: 2,
					data: data.data,
					backgroundColor: 'rgb(49 74 199)',
				},
			],
		};
		const configDefault = {
			type: 'line',
			data: chart_data,
			options: {
				responsive: true,
				maintainAspectRatio: false,
				aspectRatio: 0.8,
				plugins: {
					legend: {
						display: false,
					},
				},
				scales: {
					y: {
						min: 0,
					},
					x: {
						title: {
							display: true,
							text: data.x_label,
							align: 'end',
						},
					},
				},
			},
		};

		const configChart = { ...configDefault, ...config };
		configChart.options = { ...configDefault.options, ...config.options };

		// console.log( configChart );

		const chart = new Chart( canvas, configChart );
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
	document.querySelectorAll( '.btn-filter-time' ).forEach( ( btn ) => {
		btn.addEventListener( 'click', () => {
			document
				.querySelectorAll( '.btn-filter-time' )
				.forEach( ( ele ) => ele.classList.remove( 'active' ) );
			btn.classList.add( 'active' );
			const filterType = btn.dataset.filter;
			if ( filterType == 'custom' ) {
				document.querySelector( '.custom-filter-time' ).style.display =
					'flex';
			} else {
				const elementLoad = document.querySelector(
					'input.statistics-type'
				);
				if ( elementLoad ) {
					document.querySelector(
						'.statistics-content canvas'
					).style.display = 'none';
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
					} else if ( elementLoad.value == 'courses-statistics' ) {
						courseLoadData( filterType );
					} else if ( elementLoad.value == 'users-statistics' ) {
						document.querySelector(
							'.top-course-by-student'
						).innerHTML = '';
						document.querySelector(
							'.top-intructor-by-student'
						).innerHTML = '';
						userLoadData( filterType );
					}
				}
			}
		} );
	} );
	document
		.querySelector( '.custom-filter-btn' )
		.addEventListener( 'click', ( e ) => {
			const time1 = document.querySelector( '#ct-filter-1' ).value,
				time2 = document.querySelector( '#ct-filter-2' ).value;
			if ( ! time1 || ! time2 ) {
				alert( 'Choose date' );
			} else {
				const elementLoad = document.querySelector(
					'input.statistics-type'
				);
				document.querySelector(
					'.statistics-content canvas'
				).style.display = 'none';
				loadLpSkeletonAnimations( true );
				if ( elementLoad ) {
					if ( elementLoad.value === 'orders-statistics' ) {
						orderLoadData( 'custom', `${ time1 }+${ time2 }` );
					} else if ( elementLoad.value === 'overview-statistics' ) {
						document.querySelector(
							'.top-category-sold'
						).innerHTML = '';
						document.querySelector( '.top-course-sold' ).innerHTML =
							'';
						overviewLoadData( 'custom', `${ time1 }+${ time2 }` );
					} else if ( elementLoad.value === 'courses-statistics' ) {
						courseLoadData( 'custom', `${ time1 }+${ time2 }` );
					} else if ( elementLoad.value === 'users-statistics' ) {
						userLoadData( 'custom', `${ time1 }+${ time2 }` );
					}
				}
			}
		} );
	lpStatisticsLoad();
	const initStatisticChart = (
		chartID = '',
		chartData = [],
		chartConfig = false
	) => {
		let chart = Chart.getChart( chartID );
		const chartEle = document.getElementById( chartID );

		// console.log( data );
		chartEle.style.display = 'block';
		loadLpSkeletonAnimations();

		if ( chart === undefined ) {
			if ( chartConfig ) {
				chart = generateChart( chartID, chartData, chartConfig );
			} else {
				chart = generateChart( chartID, chartData );
			}
		} else {
			chart.data.labels = chartData.labels;
			chart.data.datasets[ 0 ].data = chartData.data;
			chart.config.options.scales.x.title.text = chartData.x_label;
			chart.update();
		}
	};
} );
