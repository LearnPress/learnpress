// console.log('load-js');

document.addEventListener("DOMContentLoaded", function() {
	const orderStatisticsLoad = () => {
		const elementLoad = document.querySelector('input.statistics-type');
		if (!elementLoad) {
			return;
		}
		orderLoadData();
	}
	const orderLoadData = (filterType = '', date = '') => {
		wp.apiFetch({
			path: wp.url.addQueryArgs('lp/v1/statistics/order-statistics', {
				filterType: filterType,
				date: date
			}),
			method: 'GET',
		}).then((res) => {
			const { data, status, message } = res;
			if (status === 'error') {
				throw new Error(message || 'Error');
			}
			let chart = Chart.getChart("orders-chart-content");
			if (chart === undefined) {
				chart = generateOrderChart(data.chart_data);
			} else {
				chart.data.labels = data.chart_data.labels;
				chart.data.datasets[0].data = data.chart_data.data;
				chart.config.options.scales.x.title.text = data.chart_data.x_label;
				chart.update();
			}
			if (data.statistics.length > 0) {
				let totalOrder = 0;
				for (let i = data.statistics.length - 1; i >= 0; i--) {
					let v = data.statistics[i];
					if (v.order_status == 'completed') {
						document.querySelector('.completed-order-count').textContent = v.count_order;
						totalOrder += ~~v.count_order;
					} else if (v.order_status == 'pending') {
						document.querySelector('.pending-order-count').textContent = v.count_order;
						totalOrder += ~~v.count_order;
					} else if (v.order_status == 'processing') {
						document.querySelector('.processing-order-count').textContent = v.count_order;
						totalOrder += ~~v.count_order;
					} else if (v.order_status == 'cancelled') {
						document.querySelector('.cancelled-order-count').textContent = v.count_order;
						totalOrder += ~~v.count_order;
					} else if (v.order_status == 'failed') {
						document.querySelector('.failed-order-count').textContent = v.count_order;
						totalOrder += ~~v.count_order;
					}
				}
				document.querySelector('.total-order-count').textContent = totalOrder;
			} else {
				document.querySelector('.completed-order-count').textContent = 0;
				document.querySelector('.pending-order-count').textContent = 0;
				document.querySelector('.processing-order-count').textContent = 0;
				document.querySelector('.cancelled-order-count').textContent = 0;
				document.querySelector('.failed-order-count').textContent = 0;
				document.querySelector('.total-order-count').textContent = 0;
			}
		}).catch((err) => {
				console.log(err);
		}).finally(() => {});
	}
	const generateOrderChart = (data = []) => {
		let canvas = document.getElementById('orders-chart-content');
		const chart_data = {
			labels: data.labels,
			datasets: [{
				label: data.line_label,
				borderColor: 'blue',
				data: data.data,
				backgroundColor: 'blue',
			}, ]
		};
		const config = {type: 'line', data: chart_data, options: {responsive: true, plugins: { legend: {display: false } }, scales: {y: {min: 0, ticks: {stepSize: 1 }, title: {display: true, text: data.line_label } }, x: { title: {display: true, text: data.x_label } } } }, }; 
		const chart = new Chart(canvas, config);
		return chart;
	}
	document.querySelectorAll('.btn-filter-time').forEach(btn => btn.addEventListener( 'click', () => {
		let filterType = btn.dataset.filter;
		if (filterType == 'custom') {
			document.querySelector( '.custom-filter-time' ).style.display = 'flex';
		} else {
			orderLoadData(filterType);
		}
	}));
	document.querySelector('.custom-filter-btn').addEventListener( 'click', (e) => {
		let time1 = document.querySelector( '#ct-filter-1' ).value,
			time2 = document.querySelector( '#ct-filter-2' ).value;
			console.log( time1 + ' - ' + time2 );
		if ( ! time1 || ! time2 ) {
			alert( 'Choose date' );
		} else {
			orderLoadData( 'custom', `${time1}+${time2}` );
		}
	} );
	orderStatisticsLoad();
});
