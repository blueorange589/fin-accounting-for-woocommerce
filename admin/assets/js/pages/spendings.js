var app = new Vue({
	el: '#finapp',
	data: {
		spendings: [],
		withcats: true,
		leftcats: [],
		siteurl : '',
		currencySymbol: '',
		index : 0,
		type: 'all',
		cat: '',
		catlist: false,
		allcats: [],
		row: {},
		catrow: {},
		totals: {amount:0, tr:0},
		filters: {datestart:'', dateend:'', pid:0, paidwith:0}
	},
	mounted: function() {
		this.currencySymbol = fin.symbol;
		this.siteurl = fin.siteurl;
		this.getSpendings();
		jQuery('.datepicker').datepicker({dateFormat : 'yy-mm-dd'});
	},
	methods: {
		switchTab: function(type) {
			if(type=='all') {
				jQuery('.spendings-left').hide();
				jQuery('.spendings-right').css('flex','1');
				jQuery('.fin-tabs').css('margin-left','0px');
			} else {
				jQuery('.spendings-left').show();
				jQuery('.spendings-right').css('flex','0.85');
				jQuery('.fin-tabs').css('margin-left','222px');
				this.leftcats = this.allcats[type];
			}
			this.type = type;
			this.cat = '';
			this.getSpendings();
		},
		setCategory: function(cat) {
			this.cat = cat;
			this.getSpendings();
		},
		getSpendings: function() {
			var self = this;
			fin.xhr({handler:'spendings', process:'getSpendings', type:this.type, cat: this.cat, withcats: this.withcats, filters: JSON.stringify(this.filters)}, function (data) { 
				self.spendings = data.payload.data;
				self.totals = data.payload.totals;
				if(typeof(data.payload.filters) != 'undefined') { self.filters = data.payload.filters; }
				if(typeof(data.payload.categories) != 'undefined') { self.allcats = data.payload.categories; }
				self.withcats = false;
			});
		},
		addSpendingModal: function() {
			this.catlist = this.allcats.cost;
			fin.openModal('addnew');
		},
		addNewSpending: function() {
			if(!fin.validateForm('form-addnewspending')) return false;
			var self = this; 
			var fd = fin.getFormData('form-addnewspending');
			fin.xhr(fd, function (data) { 
				if(data.success) {
					if(self.type == fd.type || self.type=='all') { self.spendings.unshift(data.payload); }
					fin.closeModal();
					jQuery('#form-addnewspending').trigger('reset');
					self.recalculateTotals();
				}
			});
		},
		displayUploader: function(index, row) {
			this.index = index;
			this.row = row;
			fin.openModal('attachmentModal');
		},
		editSpending: function(index, item) {
			this.index = index;
			this.row = JSON.parse(JSON.stringify(item));
			this.catlist = this.allcats[item.type];
			fin.openModal('editSpendingModal');
		},
		updateSpending: function() {
			if(!fin.validateForm('form-editspending')) return false;
			var self = this; 
			var fd = fin.getFormData('form-editspending');
			fin.xhr(fd, function (data) { 
				if(data.success) {
					var i = self.index;
					if(self.type == fd.type || self.type=='all') { Vue.set(self.spendings, self.index, data.payload); }
					fin.closeModal();
					self.recalculateTotals();
				}
			});
		},
		deleteSpending: function(index, coid) {
			this.index = index;
			var self = this; 
			var r = confirm("Remove spending from records?");
			if (r === true) {
				fin.xhr({handler: 'spendings', process:"removeSpending", key:coid},function() {
					self.spendings.splice(index,1);
				});
			}
		},
		recalculateTotals: function() {
			var size = this.spendings.length;
			this.totals.tr = 0;
			this.totals.amount = 0;
			for(i=0; i<size; i++) {
				console.log(parseFloat(fin.flattenMoney(this.spendings[i].amount)));
				this.totals.tr += parseFloat(fin.flattenMoney(this.spendings[i].tr));
				this.totals.amount += parseFloat(fin.flattenMoney(this.spendings[i].amount));
			}
			this.totals.tr = fin.formatMoney(this.totals.tr);
			this.totals.amount = fin.formatMoney(this.totals.amount);
		},
		uploadAttachment: function(form) {
			var self = this;
			//if(!fin.validateForm(form)) { return false; }
				var file_data = jQuery('#upfile').prop('files')[0];
				var form_data = new FormData();
				form_data.append('file', file_data);
        form_data.append('action', 'fafw');
				form_data.append('process', 'attachFile');
				form_data.append('handler', 'spendings');
				form_data.append('nonce', jQuery('#nonce').val());
				form_data.append('key', jQuery('#attkey').val());
				jQuery.ajax({
						url: ajax_object.ajaxurl,
						type: 'post',
						contentType: false,
						processData: false,
						data: form_data,
						success: function (response) {
							var data = JSON.parse(response);
							data.message ? data.success ? toastr.success(data.message) : toastr.error(data.message) : '';
							if(data.success) {
								var i = self.index;
								self.spendings[i].attfile = data.payload.url;
								fin.closeModal();
							}
						},  
						error: function (response) {
							console.log('error', response);
						}
				});
		},
		setCatlist: function(e) {
			var type = e.target.value;
			this.catlist = this.allcats[type];
		},
		fetchCategories: function() {
			var self = this;
			fin.xhr({handler: 'spendings', process:"getCategories"},function(data) {
				self.allcats = data.payload.categories;
				self.leftcats = self.allcats[self.type];
			});
		},
		exportCSV: function() {
			jQuery(".fin-table").tableToCSV();
		},
		categoryName: function(item) {
			if(typeof(this.allcats[item.type]) != 'undefined' && typeof(this.allcats[item.type][item.cat]) != 'undefined') {
				return this.allcats[item.type][item.cat].name;
			}
			return '';
		},
		categoryCode: function(item) {
			if(typeof(this.allcats[item.type]) != 'undefined' && typeof(this.allcats[item.type][item.cat]) != 'undefined') {
				return this.allcats[item.type][item.cat].jcode;
			}
			return '';
		},
		capitalizeFirstLetter(string) {
			if(!string) return '';
			return string.charAt(0).toUpperCase() + string.slice(1);
		},
		addCategoryModal: function() {
			fin.openModal('addcategory');
		},
		addCategory: function() {
			if(!fin.validateForm('form-addcategory')) return false;
			var self = this; 
			var fd = fin.getFormData('form-addcategory');
			fin.xhr(fd, function (data) { 
				if(fd.type == self.type) { jQuery.each(data.payload, function(slug, obj) { Vue.set(self.leftcats, slug, obj); }); }
				fin.closeModal();
			});
		},
		editCategoryModal: function(index, item) {
			var copy = JSON.parse(JSON.stringify(item));
			copy.slug = index;
			copy.type = this.type;
			this.catrow = copy;
			fin.openModal('editCategoryModal');
		},
		editCategory: function() {
			if(!fin.validateForm('form-editcategory')) return false;
			var self = this; 
			var fd = fin.getFormData('form-editcategory');
			fin.xhr(fd, function (data) { 
				self.fetchCategories();
				fin.closeModal();
			});
		},
		removeCategory: function(type, slug) {
			var self = this;
			fin.xhr({handler:'spendings', process:'removeCategory', type: type, cat: slug}, function (data) { 
				self.fetchCategories();
				fin.closeModal();
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
		formatAdd: function(e) {
			e.target.value = fin.formatMoney(e.target.value);
		},
		flattenAdd: function(e) {
			e.target.value = fin.flattenMoney(e.target.value);
		},
		formatEdit: function(key) {
			this.row[key] = fin.formatMoney(this.row[key]);
		},
		flattenEdit: function(key) {
			this.row[key] = fin.flattenMoney(this.row[key]);
		},
		formatMoney: function(val) {
			return fin.formatMoney(val);
		},
		filterByDate: function() {
			if(!fin.validateForm('form-datefilter')) return false;
			var fd = fin.getFormData('form-datefilter');
			this.filters.datestart = fd.datestart;
			this.filters.dateend = fd.dateend;
			this.getSpendings();
		}
	},
	created() {
		this.$root.$refs.app = this;
	}
});