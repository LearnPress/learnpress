/**
 * Dashboard charts and interactions for Course Builder.
 *
 * @since 4.3.0
 * @version 1.0.0
 */

export class BuilderDashboard {
	constructor() {
		this.charts = {};
		this.init();
	}

	init() {
		const dataEl = document.getElementById( 'lp-cb-dashboard-chart-data' );
		if ( ! dataEl ) {
			return;
		}

		try {
			this.chartData = JSON.parse( dataEl.textContent );
		} catch ( e ) {
			console.warn( 'Dashboard: Failed to parse chart data', e );
			return;
		}

		// Wait for Chart.js to be available
		this.waitForChartJs().then( () => {
			this.initSalesChart();
			this.initStudentsChart();
			this.bindFilterEvents();
		} );
	}

	waitForChartJs() {
		return new Promise( ( resolve ) => {
			if ( window.Chart ) {
				resolve();
				return;
			}

			const check = setInterval( () => {
				if ( window.Chart ) {
					clearInterval( check );
					resolve();
				}
			}, 100 );

			// Timeout after 10s
			setTimeout( () => {
				clearInterval( check );
				resolve();
			}, 10000 );
		} );
	}

	createGradient( ctx, color ) {
		const gradient = ctx.createLinearGradient( 0, 0, 0, ctx.canvas.height );
		gradient.addColorStop( 0, color + '40' );
		gradient.addColorStop( 1, color + '05' );
		return gradient;
	}

	getChartConfig( labels, data, color, prefix = '' ) {
		return {
			type: 'line',
			data: {
				labels: labels,
				datasets: [ {
					data: data,
					borderColor: color,
					borderWidth: 2.5,
					backgroundColor: ( context ) => {
						const { ctx } = context.chart;
						return this.createGradient( ctx, color );
					},
					fill: true,
					tension: 0.4,
					pointBackgroundColor: color,
					pointBorderColor: '#fff',
					pointBorderWidth: 2,
					pointRadius: 0,
					pointHoverRadius: 6,
					pointHoverBorderWidth: 2,
				} ],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						backgroundColor: '#1e1e1e',
						titleColor: '#fff',
						bodyColor: '#fff',
						borderColor: 'rgba(255,255,255,0.1)',
						borderWidth: 1,
						cornerRadius: 8,
						padding: 12,
						displayColors: false,
						callbacks: {
							label: ( context ) => {
								const val = context.parsed.y || 0;
								return prefix + val.toLocaleString();
							},
						},
					},
				},
				scales: {
					x: {
						grid: { display: false },
						ticks: {
							color: '#9ca3af',
							font: { size: 11 },
							maxRotation: 0,
						},
						border: { display: false },
					},
					y: {
						grid: {
							color: 'rgba(0,0,0,0.04)',
							drawBorder: false,
						},
						ticks: {
							color: '#9ca3af',
							font: { size: 11 },
							callback: ( value ) => prefix + value.toLocaleString(),
						},
						border: { display: false },
						beginAtZero: true,
					},
				},
				interaction: {
					intersect: false,
					mode: 'index',
				},
			},
		};
	}

	initSalesChart() {
		const canvas = document.getElementById( 'lp-cb-chart-sales' );
		if ( ! canvas ) {
			return;
		}

		const { labels = [], data = [] } = this.chartData.sales || {};
		const config = this.getChartConfig( labels, data, '#10b981', '$' );
		this.charts.sales = new window.Chart( canvas, config );
	}

	initStudentsChart() {
		const canvas = document.getElementById( 'lp-cb-chart-students' );
		if ( ! canvas ) {
			return;
		}

		const { labels = [], data = [] } = this.chartData.students || {};
		const config = this.getChartConfig( labels, data, '#f59e0b' );
		this.charts.students = new window.Chart( canvas, config );
	}

	bindFilterEvents() {
		const filters = document.querySelectorAll( '.chart-card__filter' );
		filters.forEach( ( select ) => {
			select.addEventListener( 'change', ( e ) => {
				const chartType = e.target.dataset.chart;
				const period = e.target.value;
				this.fetchChartData( chartType, period );
			} );
		} );
	}

	async fetchChartData( chartType, period ) {
		const chart = this.charts[ chartType ];
		if ( ! chart ) {
			return;
		}

		const formData = new FormData();
		formData.append( 'action', 'lp_cb_dashboard_chart_data' );
		formData.append( 'chart_type', chartType );
		formData.append( 'period', period );
		formData.append( 'nonce', this.chartData.nonce );

		try {
			const response = await fetch( this.chartData.ajaxUrl, {
				method: 'POST',
				body: formData,
			} );

			const result = await response.json();
			if ( result.success && result.data ) {
				chart.data.labels = result.data.labels || [];
				chart.data.datasets[ 0 ].data = result.data.data || [];
				chart.update( 'none' );
			}
		} catch ( e ) {
			console.warn( 'Dashboard: Failed to fetch chart data', e );
		}
	}
}
