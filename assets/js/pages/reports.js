var app = new Vue({
	el: '#finapp',
	data: {
		users: [],
	},
	mounted: function() {
		this.getUsers();
	},
	methods: {
		getUsers: function() {
			
		},
		exportCSV: function() {
			jQuery(".plreport").tableToCSV();
		},
	},
	created() {
		this.$root.$refs.app = this;
	}
});