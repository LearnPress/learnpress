// console.log('load-js');

document.addEventListener("DOMContentLoaded", function() {
	const orderStatisticsLoad = ( ) => {
		const elementLoad = document.querySelector( 'input.statistics-type' );
		if ( !elementLoad ) {
			return;
		}
		orderLoadData();
	}
	const orderLoadData = ( filterType='', date='' ) => {
		wp.apiFetch( {
			path: wp.url.addQueryArgs( 'lp/v1/statistics/order-statistics', {
				filterType: filterType,
				date: date
			} ),
			method: 'GET',
		} ).then( ( res ) => { 
			const { data, status, message } = res;
			if ( status === 'error' ) {
				throw new Error( message || 'Error' );
			}
			generateOrderChart( data.chart_data );
		} ).catch((err) => {
			console.log(err); 
		}).finally(() => {});
	}
	const generateOrderChart = ( data = [] ) => {
		let canvas = document.getElementById( 'orders-chart-content' );
		
			// const labels = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23];
		const chart_data = {
		  labels: data.labels,
		  datasets: [
		    {
		      label: 'Completed Orders',
		      borderColor: 'blue',
		      data:data.data,
		      backgroundColor: 'blue',
		    },
		  ]
		};
		const config = { type: 'line', data: chart_data, options: { responsive: true, plugins: { title: { display: true, text: 'Completed Orders' } }, scales: { y: { min: 0, ticks: {stepSize: 1 } } } }, };

		const chart = new Chart( canvas, config );
	}
	orderStatisticsLoad();
});