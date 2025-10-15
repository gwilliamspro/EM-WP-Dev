/**
 * Epic Marks Shipping - Reports Charts
 *
 * Chart.js visualizations for shipping reports
 *
 * @package EpicMarksShipping
 * @since 2.0.0
 */

jQuery(document).ready(function($) {

	/**
	 * Cost Analysis Chart
	 * Bar chart comparing estimated vs actual costs
	 */
	if ($('#costAnalysisChart').length && typeof costData !== 'undefined') {
		var ctx = document.getElementById('costAnalysisChart').getContext('2d');
		
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: ['Estimated (Customer Paid)', 'Actual (You Paid)', 'Savings'],
				datasets: [{
					label: 'Shipping Costs',
					data: [
						costData.estimated,
						costData.actual,
						costData.savings
					],
					backgroundColor: [
						'rgba(34, 113, 177, 0.8)',
						'rgba(214, 54, 56, 0.8)',
						'rgba(0, 163, 42, 0.8)'
					],
					borderColor: [
						'rgba(34, 113, 177, 1)',
						'rgba(214, 54, 56, 1)',
						'rgba(0, 163, 42, 1)'
					],
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false
					},
					title: {
						display: true,
						text: 'Cost Comparison',
						font: {
							size: 16
						}
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								return '$' + context.parsed.y.toFixed(2);
							}
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							callback: function(value) {
								return '$' + value.toFixed(2);
							}
						}
					}
				}
			}
		});
	}

	/**
	 * Fulfillment Pie Charts
	 * Orders by location and revenue breakdown
	 */
	if ($('#fulfillmentPieChart').length && typeof fulfillmentData !== 'undefined') {
		var ctxPie = document.getElementById('fulfillmentPieChart').getContext('2d');
		
		new Chart(ctxPie, {
			type: 'pie',
			data: {
				labels: ['Warehouse', 'Store', 'Unknown'],
				datasets: [{
					data: [
						fulfillmentData.warehouse,
						fulfillmentData.store,
						fulfillmentData.unknown
					],
					backgroundColor: [
						'rgba(34, 113, 177, 0.8)',
						'rgba(0, 163, 42, 0.8)',
						'rgba(219, 166, 23, 0.8)'
					],
					borderColor: [
						'rgba(34, 113, 177, 1)',
						'rgba(0, 163, 42, 1)',
						'rgba(219, 166, 23, 1)'
					],
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'bottom'
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								var label = context.label || '';
								var value = context.parsed || 0;
								var total = context.dataset.data.reduce((a, b) => a + b, 0);
								var percentage = ((value / total) * 100).toFixed(1);
								return label + ': ' + value + ' (' + percentage + '%)';
							}
						}
					}
				}
			}
		});
	}

	/**
	 * Revenue Pie Chart
	 * Shipping revenue by type
	 */
	if ($('#revenuePieChart').length && typeof fulfillmentData !== 'undefined') {
		var ctxRevenue = document.getElementById('revenuePieChart').getContext('2d');
		
		new Chart(ctxRevenue, {
			type: 'pie',
			data: {
				labels: ['Warehouse Shipping', 'Store Shipping', 'Ship to Store'],
				datasets: [{
					data: [
						fulfillmentData.warehouse_cost,
						fulfillmentData.store_cost,
						fulfillmentData.ship_to_store_cost
					],
					backgroundColor: [
						'rgba(34, 113, 177, 0.8)',
						'rgba(0, 163, 42, 0.8)',
						'rgba(0, 124, 186, 0.8)'
					],
					borderColor: [
						'rgba(34, 113, 177, 1)',
						'rgba(0, 163, 42, 1)',
						'rgba(0, 124, 186, 1)'
					],
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						position: 'bottom'
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								var label = context.label || '';
								var value = context.parsed || 0;
								var total = context.dataset.data.reduce((a, b) => a + b, 0);
								var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
								return label + ': $' + value.toFixed(2) + ' (' + percentage + '%)';
							}
						}
					}
				}
			}
		});
	}

	/**
	 * Service Performance Bar Chart
	 * Horizontal bar chart showing service usage
	 */
	if ($('#serviceBarChart').length && typeof serviceData !== 'undefined') {
		var ctxService = document.getElementById('serviceBarChart').getContext('2d');
		
		// Prepare data arrays
		var serviceLabels = [];
		var serviceCounts = [];
		var serviceColors = [];
		
		// Color palette
		var colors = [
			'rgba(34, 113, 177, 0.8)',
			'rgba(0, 163, 42, 0.8)',
			'rgba(214, 54, 56, 0.8)',
			'rgba(0, 124, 186, 0.8)',
			'rgba(219, 166, 23, 0.8)',
			'rgba(147, 51, 234, 0.8)',
			'rgba(244, 114, 182, 0.8)'
		];
		
		var colorIndex = 0;
		for (var service in serviceData) {
			if (serviceData.hasOwnProperty(service)) {
				serviceLabels.push(service);
				serviceCounts.push(serviceData[service].count);
				serviceColors.push(colors[colorIndex % colors.length]);
				colorIndex++;
			}
		}
		
		new Chart(ctxService, {
			type: 'bar',
			data: {
				labels: serviceLabels,
				datasets: [{
					label: 'Number of Orders',
					data: serviceCounts,
					backgroundColor: serviceColors,
					borderColor: serviceColors.map(color => color.replace('0.8', '1')),
					borderWidth: 2
				}]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false
					},
					title: {
						display: false
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								var service = context.label;
								var count = context.parsed.x;
								var data = serviceData[service];
								var percentage = data ? data.percentage.toFixed(1) : 0;
								return count + ' orders (' + percentage + '%)';
							}
						}
					}
				},
				scales: {
					x: {
						beginAtZero: true,
						ticks: {
							stepSize: 1
						}
					}
				}
			}
		});
	}

});
