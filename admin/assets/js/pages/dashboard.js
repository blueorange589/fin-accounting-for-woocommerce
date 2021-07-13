var app = new Vue({
	el: '#finapp',
	data: {
		info: [],
		charttypes: [],
		chartpms: [],
		chartcats: [],
		sptotal: [],
		cats: [],
		currencySymbol: '',
		siteurl: '',
		filters: {datestart:'', dateend:''}
	},
	mounted: function() {
		this.currencySymbol = fin.symbol;
		this.siteurl = fin.siteurl;
		this.getDashboardData();
		jQuery('.datepicker').datepicker({dateFormat : 'yy-mm-dd'});
	},
	methods: {
		filterByDate: function() {
			if(!fin.validateForm('form-datefilter')) return false;
			var fd = fin.getFormData('form-datefilter');
			this.filters.datestart = fd.datestart;
			this.filters.dateend = fd.dateend;
			this.getDashboardData();
		},
		getDashboardData: function() {
			var self = this;
			fin.xhr({handler:'orders', process:'getDashboard', filters: JSON.stringify(this.filters)}, function (data) { 
				self.chartcats = data.payload.chartcats;
				self.charttypes = data.payload.charttypes;
				self.chartpms = data.payload.chartpms;
				self.sptotal = data.payload.sptotal;
				self.cats = data.payload.cats;
				self.egws = data.payload.egws;
				self.info = data.payload.info;
				self.info.cogs = self.floatFix(self.info.cogs);
				self.info.profit = self.floatFix(self.info.profit);
				self.info.stotal = self.floatFix(self.info.stotal);
				self.info.taxes = self.floatFix(self.info.taxes);
				self.filters = data.payload.filters;
				self.drawTypeChart();
				self.drawMethodChart();
			});
		},
		drawTypeChart: function() {
			var self = this;
			var ctx = document.getElementById('spendings-pie-chart').getContext('2d');
			var chart = new Chart(ctx, {
					type: 'doughnut',
					data: {
							labels: Object.keys(self.charttypes),
							datasets: [{
									label: 'Spendings',
									backgroundColor: [
										'rgb(245, 222, 61)',
										'rgb(51, 41, 254)',
										'rgb(0, 208, 61)',
									],
									borderColor: 'rgb(242, 242, 242)',
									borderWidth: 0,
									data: Object.values(self.charttypes),
							}]
					},
					options: {maintainAspectRatio:false, responsive:true, height:500,legend: {
							position: 'bottom',
						},animation: {
							animateScale: true,
							animateRotate: true
						}}
			});
		},
		drawMethodChart: function(){
			var self = this;
			var ctx2 = document.getElementById('spendings-bar-chart').getContext('2d');
			var chart2 = new Chart(ctx2, {
					type: 'horizontalBar',
					data: {
							labels: Object.keys(self.chartpms),
							datasets: [{
									label: 'Spendings',
									backgroundColor: '#3329FE',
									borderColor: 'rgb(242, 242, 242)',
									borderWidth: 0,
									data: Object.values(self.chartpms)
							}]
					},
					options: {maintainAspectRatio:false, responsive:true, height:500,legend: {
						position: 'bottom',
					},animation: {
						animateScale: true,
						animateRotate: true
					},
					scales: {
						xAxes: [{
								ticks: {
										beginAtZero: true
								}
						}]
					}}
			});
		},
		floatFix: function(val) {
			return parseFloat(val).toFixed(2);
		}
	},
	created() {
		this.$root.$refs.app = this;
	}
});