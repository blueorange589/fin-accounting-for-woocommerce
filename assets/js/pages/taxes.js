const taxlist = Vue.component('taxlist', {	
	template: '#taxlist',
	components: {},
	data: function () {
			return {
					title: '',
					tlist: [],
					summary: {},
					totals: {'tax':0, 'ship':0}
			}
	},
	beforeRouteEnter (to, from, next) {
		if(!to.params.msu || !to.params.mse) {return false;}
		var self = this;
		fin.xhr({ handler:'taxes', process:'listPayableTaxes',msu: to.params.msu, mse: to.params.mse }, function(data) {
			var dt = data;
			next(vm => { 
				vm.tlist = dt.payload.payable;
				vm.summary = dt.payload.summary;
				vm.totals = dt.payload.totals;
				vm.title = vm.formatDate(to.params.msu, 'monthday')+'-'+vm.formatDate(to.params.mse, 'monthday');
			})
		});
  },
	created() {
	},
	methods: {
		formatDate: function(ut, format) {
			return fin.formatDate(ut, format);
		},
		exportCSV: function() {
			jQuery(".fin-table").tableToCSV();
		},
		columnTotal: function(col, digits) {
			if(typeof(digits)=='undefined') { digits = 0; }
			var total = 0;
			this.tlist.forEach(function(obj, k) {
				total += obj[col];		
			});
			if(digits>0) {
				return (Math.round(total * 100) / 100).toFixed(2);
			} else {
				return parseInt(total);
			}
		}
	}
});

const taxhome = Vue.component('taxhome', {
	template: '#taxhome',
	data: function () {
		return {
			rows: [],
			currencySymbol: '',
		}
	},
	mounted: function() {
		this.currencySymbol = fin.symbol;
		this.siteurl = fin.siteurl;
		this.getTaxes();
		jQuery('.datepicker').datepicker({dateFormat : 'yy-mm-dd'});
	},
	methods: {
		getTaxes: function() {
			
		},
		listPayableTaxes: function(msu, mse) {
			this.$root.$refs.app.$router.push({ name: 'list', params: { msu: msu, mse: mse } });
			this.$root.$refs.app.tab='list';
		},
		addTaxPaidModal: function() {
			fin.openModal('addtaxpaid');
		},
		addTaxPaid: function() {
			var self = this;
			var fd = fin.getFormData('form-addtaxpaid');
			fin.xhr(fd, function (data) { 
				if(data.success) {
					location.reload();
				}
			});
		},
		checkAllowed: function(e) {
			var val = e.target.value;
			var last = val.slice(-1);
			var allowed = ['0','1','2','3','4','5','6','7','8','9','.'];
			if(!allowed.includes(last)) {
				e.target.value = val.slice(0, -1);
			}
		},
		formatMoneyAdd: function(e) {
			e.target.value = fin.formatMoney(e.target.value);
		},
		flattenMoneyAdd: function(e) {
			e.target.value = fin.flattenMoney(e.target.value);
		},
	}
});


const router = new VueRouter({
	mode: 'hash',
	routes: [
		{path: '/', name:'home', component: taxhome},
		{path: '/list', name:'list', component: taxlist}
	],
});

var app = new Vue({
	router,
	el: '#finapp',
	data: {
		tab: 'home'
	},
	methods: {
	},
	created() {
			this.$root.$refs.app = this;
	}
});