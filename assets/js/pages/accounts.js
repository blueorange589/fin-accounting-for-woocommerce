var app = new Vue({
	el: '#finapp',
	data: {
		txns: [],
		accounts: [],
		accname: 'All',
		filters: {datestart:'', dateend:'', account:''},
		currencySymbol: '',
		siteurl: '',
		row: {},
		addSource: 'new',
		gwlist: [],
		removedAccounts: []
	},
	mounted: function() {
		this.currencySymbol = fin.symbol;
		this.siteurl = fin.siteurl;
		this.getTxns();
		jQuery('.datepicker').datepicker({dateFormat : 'yy-mm-dd'});
	},
	methods: {
		setAccount: function(slug, name) {
			this.filters.account = slug;
			this.getTxns();
			this.accname = name;
		},
		filterByDate: function() {
			if(!fin.validateForm('form-datefilter')) return false;
			var fd = fin.getFormData('form-datefilter');
			this.filters.datestart = fd.datestart;
			this.filters.dateend = fd.dateend;
			this.getTxns();
		},
		getTxns: function() {
			var self = this;
			fin.xhr({handler:'accounts', process:'getTransactions', filters: JSON.stringify(this.filters)}, function (data) { 
				self.txns = data.payload.txns;
				self.accounts = data.payload.accounts;
				if(typeof(data.payload.filters) != 'undefined') { self.filters = data.payload.filters; }
			});
		},
		transferModal: function() {
			fin.openModal('transferModal');
		},
		addNewModal: function() {
			var self = this;
			fin.xhr({handler:'accounts', process:'addAccountVars'}, function (data) { 
				self.removedAccounts = data.payload.removedAccounts;
				self.gwlist = data.payload.gwlist;
				fin.openModal('addNewModal');
			});
		},
		editAccountModal: function(row) {
			this.row = JSON.parse(JSON.stringify(row));
			fin.openModal('editAccountModal');
		},
		exportCSV: function() {
			jQuery(".fin-table").tableToCSV();
		},
		getAccounts: function() {
			var self = this;
			fin.xhr({handler:'accounts', process:'getAccountList'}, function (data) { 
				self.accounts = data.payload.accounts;
			});
		},
		addnewAccount: function() {
			if(!fin.validateForm('form-addaccount')) return false;
			var self = this; 
			var fd = fin.getFormData('form-addaccount');
			fin.xhr(fd, function (data) { 
				self.getTxns();
				fin.closeModal();
				jQuery('#form-addaccount').trigger('reset');
			});
		},
		editAccount: function() {
			if(!fin.validateForm('form-editaccount')) return false;
			var self = this; 
			var fd = fin.getFormData('form-editaccount');
			fin.xhr(fd, function (data) { 
				self.getTxns();
				fin.closeModal();
				jQuery('#form-editaccount').trigger('reset');
			});
		},
		transfer: function() {
			if(!fin.validateForm('form-transfer')) return false;
			var self = this; 
			var fd = fin.getFormData('form-transfer');
			fin.xhr(fd, function (data) { 
				self.getTxns();
				fin.closeModal();
				jQuery('#form-transfer').trigger('reset');
			});
		},
		deleteAccount: function() {
			var self = this;
			fin.xhr({handler:'accounts', process:'deleteAccount', slug: this.row.slug}, function (data) { 
				self.getTxns();
				fin.closeModal();
			});
		},
		sourceChange: function(e) {
			this.addSource = e.target.value;
		},
		formatXfer: function(e) {
			e.target.value = fin.formatMoney(e.target.value);
		},
		flattenXfer: function(e) {
			e.target.value = fin.flattenMoney(e.target.value);
		},
		checkAllowed: function(e) {
			var val = e.target.value;
			var last = val.slice(-1);
			var allowed = ['0','1','2','3','4','5','6','7','8','9','.'];
			if(!allowed.includes(last)) {
				e.target.value = val.slice(0, -1);
			}
		},
	},
	created() {
		this.$root.$refs.app = this;
	}
});