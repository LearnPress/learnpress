// console.log('load-js');
var test_response;
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
		} ).catch((err) => {
			console.log(err); 
		}).finally(() => {});
	}
	orderStatisticsLoad();
});