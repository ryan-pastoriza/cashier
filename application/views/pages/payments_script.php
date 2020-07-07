

<script>
	
	var sb = new Vue({
		el: '#app',
		data: {
			// payments
			ses_sy : "<?= $this->session->get_userdata('user_data')['user_data']['sy'] ?>",
			ses_sem : "<?= $this->session->get_userdata('user_data')['user_data']['sem'] ?>",
			ses_role: "<?= strtolower($this->session->userdata('user_data')['user_data']->userRole) ?>",
			has_selected: false,
			name : 'Please select student',
			ssi_id: '',
			spi_id: '',
			usn_no: '',
			acct_no: '',
			other_payee_id: '',
			stud_id : '',
			payee_type: '',
			enrollment_status : '',
			course : '',
			address: {},
			current_status: '',
			course_type : '',
			phone_number : '',
			fee_summary : {
				old_system: []				
			},
			has_current_bill: false,
			has_downpayment_bills: true,
			fee_type: 'regular',
			regular_hide: '',
			special_hide: '',
			sy_regular: [],
			sy_special: [],
			ps_sem: '1st',
			ps_year: '',
			ps_period: 'prelim',
			selected_sum_sy: '',
			selected_sum_sem: '',
			periods: {
				prelim: '',
				midterm: '',
				prefinal: '',
				final: '',
				total: '',
				is_current: '',
				is_empty: ''
			},
			summary_details_bills: {},
			summary_details_payments: {},
			all_or_details: {},
			current_or: '',
			current_or_status: '',
			current_or_id: '',
			tab_key: 0,
			bill_visibility: '',
			payment_visibility: 'hidden',
			// other payees
			other_payees: {},
			other_payees_form : {
				fname: '',
				mname: '',
				lname: '',
				ext: '',
				address: '',
				isValid: '',
			},
			other_payees_modal:{
				id: '',
				fname: '',
				mname: '',
				lname: '',
				ext: '',
				address: '',
			},
			// other particulars
			add_particular_form: {
				particular_type: 'special',
				particular: '',
				price: 0,
			},
			get_other_particulars: {},
			selected_particulars: [],
			selected_particulars_total: 0,
			selected_os_or: {
				or: "",
				data: []
			},
			// payment_form
			or_served: '',
			to_pay: '0',
			cash: '0',
			change: '0',
			distributed_cash_remaining: '0',
			to_be_paid: [],
			formatted_payments: [],
			final_payments: [],
			receipt: 'OR',
			payment_date: "<?php echo date('Y-m-d'); ?>",
			op_search: '',
			ops_particular: [], // other payment search paid particular,,
			old_system_distribution: {}
		},
		created: function () {
			this.current_or_served();
			this.autocomplete();
			this.select_change();
			this.reg_payees_dtable();
			this.view_particulars();
		},
		methods: {
			numberToWords: function(s){		
				var th = ['','thousand','million', 'billion','trillion'];
				var dg = ['zero','one','two','three','four', 'five','six','seven','eight','nine'];
				var tn = ['ten','eleven','twelve','thirteen', 'fourteen','fifteen','sixteen', 'seventeen','eighteen','nineteen'];
				var tw = ['twenty','thirty','forty','fifty', 'sixty','seventy','eighty','ninety']; 
				
				s = (s||'').toString(); s = s.replace(/[\, ]/g,''); if (s != parseFloat(s)) return 'not a number'; var x = s.indexOf('.'); if (x == -1) x = s.length; if (x > 15) return 'too big'; var n = s.split(''); var str = ''; var sk = 0; for (var i=0; i < x; i++) {if ((x-i)%3==2) {if (n[i] == '1') {str += tn[Number(n[i+1])] + ' '; i++; sk=1;} else if (n[i]!=0) {str += tw[n[i]-2] + ' ';sk=1;}} else if (n[i]!=0) {str += dg[n[i]] +' '; if ((x-i)%3==0) str += 'hundred ';sk=1;} if ((x-i)%3==1) {if (sk) str += th[(x-i-1)/3] + ' ';sk=0;}} if (x != s.length) {var y = s.length; str += 'point '; for (var i=x+1; i<y; i++) str += dg[n[i]] +' ';} return str.replace(/\s+/g,' ');
			},
			resetData: function(){
				var a = {
							ses_sy : "<?= $this->session->get_userdata('user_data')['user_data']['sy'] ?>",
							ses_sem : "<?= $this->session->get_userdata('user_data')['user_data']['sem'] ?>",
							has_selected: false,
							name : 'Please select student',
							ssi_id: '',
							spi_id: '',
							other_payee_id: '',
							stud_id : '',
							payee_type: '',
							enrollment_status : '',
							course : '',
							address: {},
							current_status: '',
							course_type : '',
							phone_number : '',
							fee_summary : {
								old_system: []				
							},
							has_current_bill: false,
							fee_type: 'regular',
							regular_hide: '',
							special_hide: '',
							sy_regular: [],
							sy_special: [],
							ps_sem: '1st',
							ps_year: '',
							ps_period: 'prelim',
							selected_sum_sy: '',
							selected_sum_sem: '',
							periods: {
								prelim: '',
								midterm: '',
								prefinal: '',
								final: '',
								total: '',
								is_current: '',
								is_empty: ''
							},
							summary_details_bills: {},
							summary_details_payments: {},
							all_or_details: {},
							current_or: '',
							current_or_status: '',
							current_or_id: '',
							tab_key: 0,
							bill_visibility: '',
							payment_visibility: 'hidden',
							other_payees_form : {
								fname: '',
								mname: '',
								lname: '',
								ext: '',
								address: '',
								isValid: '',
							},
							other_payees_modal:{
								id: '',
								fname: '',
								mname: '',
								lname: '',
								ext: '',
								address: '',
							},
							// other particulars
							add_particular_form: {
								particular_type: 'special',
								particular: '',
								price: 0,
							},
							selected_particulars: [],
							selected_particulars_total: 0,
							selected_os_or: {
								or: "",
								data: []
							},
							// payment_form
							or_served: '',
							to_pay: '0',
							cash: '0',
							change: '0',
							distributed_cash_remaining: '0',
							to_be_paid: [],
							formatted_payments: [],
							final_payments: [],
							receipt: 'OR',
							payment_date: "<?php echo date('Y-m-d'); ?>",
							downpayment_bills: true,							
				};
				Object.assign(this.$data, a);
			},
			capitalize: function (value) {
		    	if (!value) return ''
		    	value = value.toString()
		    	return value.charAt(0).toUpperCase() + value.slice(1)
		  	},
		    formatPrice(value) {
		    	if(value){
			        value = parseFloat(value).toFixed(2);
			    	let val = (value/1).toFixed(2).replace(',', '.')
	        		return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
		    	}
		    	return value;
		    },
		    ucwords: function(str){
				return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
			},
			autocomplete: function(){
				var $this = this;
				$("#selected_student").autocomplete({
					minLength: 2,
					source: function(request, response){
						$.ajax( {
				          	url: '<?= base_url("home/student_list") ?>',
							type: 'GET',
							dataType: 'JSON',
			         	 	data: {
			            		term: request.term,
			            		fee_type: this.fee_type,
			            		sched_sy: $("#school_year").val(),
			            		sched_sem: $("#sem").val()
			          		},
				          	success: function( data ) {
				            	response(data);
				          	}
				        } );
					},
					select: function( event, ui ) {
						$this.select_change();
						$this.has_selected = true;
						if(ui.item.type == 'student'){
							$this.ssi_id = ui.item.ssi_id;
							$this.spi_id = ui.item.spi_id;
							$this.name = ui.item.value;
							$this.address = ui.item.address;
							$this.stud_id = ui.item.stud_id;
							$this.usn_no = ui.item.usn_no;
							$this.acct_no = ui.item.acct_no;
							$this.enrollment_status = ui.item.enrollment_status;
							$this.course = ui.item.course;
							$this.course_type = ui.item.course_type;
							$this.phone_number = ui.item.phone_number;
							$this.fee_summary = ui.item.data;
							$this.current_status = ui.item.current_status;
							$this.school_years_distinct();
							$this.check_old_balances();
							$this.payee_type = ui.item.type;
							$this.downpayment_bills(ui.item.course, ui.item.current_status);
						}
						else{
							$this.fee_type = 'other';
							$this.payee_type = ui.item.type;
							$this.name = ui.item.label;
							$this.fee_summary = ui.item.data;
							$this.other_payee_id = ui.item.other_payee_id;
						}
					},	
			        focus: function() {
			            return false;
			        },
				});
			},
		    select_change: function(event = ""){
		    	var tp = document.querySelector("#to_pay");
		    	if(event){
			    	var e = event.currentTarget;
			    	var type = e.getAttribute('data-type');
			    	var value = event.target.value;

			    	if(type == 'sy') this.ps_year = value;
			    	if(type == 'sem') this.ps_sem = value;
			    	if(type == 'period') this.ps_period = value;
			    	if(type == 'fee_type') this.fee_type = value;
		    	}
		    	if(this.fee_type == 'regular'){
		    		this.regular_hide = '';
		    		this.special_hide = 'hidden';
		    		this.to_pay_disabled = '';
		    		tp.removeAttribute('readonly')
		    	}
		    	if(this.fee_type == 'special' || this.fee_type == 'other'){
		    		this.regular_hide = 'hidden';
		    		this.special_hide = '';
		    		this.to_pay_disabled = 'disabled';
		    		tp.setAttribute('readonly', 'readonly')
		    	}
		    	if(this.ps_year != '') this.process_payment_schedule()
		    },
		    process_payment_schedule: function(){
		    	$this = this.periods;
	    		$.getJSON('<?= base_url("home/payment_schedule") ?>', {sem: this.ps_sem, sy: this.ps_year, fee_type: this.fee_type, period: this.ps_period, ssi_id: this.ssi_id}, function(json, textStatus) {
	    			console.log(json)
	    			$this.prelim = json.prelim;
	    			$this.midterm = json.midterm;
	    			$this.prefinal = json.prefinal;
	    			$this.final = json.final;
	    			$this.total = json.total;
	    			$this.is_current = json.is_current;
	    			$this.is_empty = json.is_empty;
			  	});
		    },
		    school_years_distinct: function(){
		    	var regular = this.fee_summary.regular_fees;
		    	var r = [];
		    	var s = [];
		    	$this = this;
		    	$.each(regular, function(index, val) {
		    		if(!r.includes(val.sy)){
		    			r.push(val.sy)
		    		}
		    		// check if there is a bill for the current year and semester
		    		if(val.sy == $this.ses_sy && val.sem == $this.ses_sem){
		    			$this.has_current_bill = true;
		    		}
		    	});
		    	this.sy_regular = r;
		    },
		    summary_details_switch: function(event){
		    	var value = event.target.value;
		    	if( value == 'student_bill' ){
		    		this.bill_visibility = '';
		    		this.payment_visibility = 'hidden';
		    	}
		    	else{
		    		this.bill_visibility = 'hidden';
		    		this.payment_visibility = '';
		    	}
		    },
		    view_summary_details: function(event = ""){
		    	var e, sy, sem;
		    	if(event){
			    	e = event.currentTarget;
			    	sy = e.getAttribute('data-sy');
			    	sem = e.getAttribute('data-sem');
			    	this.selected_sum_sy = sy;
			    	this.selected_sum_sem = sem;
		    	}
		    	$this = this;
		    	$.getJSON('<?= base_url("home/view_summary_details") ?>', {sy: this.selected_sum_sy, sem: this.selected_sum_sem, ssi_id: $this.ssi_id, type: $this.type }, function(json, textStatus) {
		    		console.log(json)
		    		var misc = {
						particular: 'Miscellaneous',
						price2: 0,
						paid2: 0,   
						price1: 0,
						paid1: 0,   				
						feeType: 'Miscellaneous'   			
		    		};
		    		var bills = [];
		    		json.bills.forEach(function(item){
						if( item.feeType.toLowerCase() == "miscellaneous" ){
							misc.price2 = parseFloat(item.price2) + parseFloat(misc.price2);
							misc.paid2  = (item.paid2 ? parseFloat(item.paid2) : 0) + parseFloat(misc.paid2);
							misc.price1 = parseFloat(item.price1) + parseFloat(misc.price1);
							misc.paid1  = (item.paid1 ? parseFloat(item.paid1) : 0) + parseFloat(misc.paid1);
						}
						else{
							bills.push(item);
						}	
					});
		    		bills.push(misc);
		    		$this.summary_details_bills = bills.reverse();
		    		$this.summary_details_payments = json.payments;
		    	});
		    },
		    or_detail_modal: function(or){
		    	this.current_or = or;
		    	this.current_or_id = this.summary_details_payments[or].paymentId;	
		    	this.all_or_details = this.summary_details_payments[or].details;
		    	this.current_or_status = this.summary_details_payments[or].payment_status;
		    	$("#or_details").modal('toggle')
		    	$("#summary_details_modal").modal('toggle')
		    },
		    cancel_payment: function(action){
		    	$this = this;
		    	$.post('<?= base_url("home/cancel_payment") ?>', {or: this.current_or, action: action, paymentId: this.current_or_id }, function(data, textStatus, xhr) {
			    	swal("Success!", "Cancelation complete.", "success")
			    	$("#or_details").modal('toggle')
		    		$("#summary_details_modal").modal('toggle')
		    		$this.view_summary_details('')
		    	});
		    },
		    reg_payees_dtable: function(event){
		    	var $this = this;
		    	if(event){
			    	var val = event.target.value;
		    	}
	    		$.getJSON('<?= base_url("others/other_payees") ?>', {term: val}, function(json, textStatus) {
	    			$this.other_payees = json;
			  	});
		    },
		    add_payee: function(){
		    	event.preventDefault()
		    	var $this = this;
		    	if(this.other_payees_form.fname == '' || this.other_payees_form.lname == '' || this.other_payees_form.address == ''){
		    		this.other_payees_form.isValid = false;
		    	}
		    	else{
		    		this.other_payees_form.isValid = '';
			    	$.post('<?= base_url("others/add_payee") ?>', {data: this.other_payees_form}, function(data, textStatus, xhr) {
			    		if(data == 1){
		    				$this.other_payees_form.isValid = '';
			    			$this.other_payees_form.lname = '';
			    			$this.other_payees_form.fname = '';
			    			$this.other_payees_form.mname = '';
			    			$this.other_payees_form.ext = '';
			    			$this.other_payees_form.address = '';
			    			swal("Success!", "You just added a new payee!", "success")
			    		}
	    				$this.reg_payees_dtable('')
			    	}, 'JSON');
		    	}
		    },
		    payee_details: function(event){
		    	this.other_payees_modal.id = event.currentTarget.getAttribute('data-id');
		    	this.other_payees_modal.fname = event.currentTarget.getAttribute('data-fname');
		    	this.other_payees_modal.mname = event.currentTarget.getAttribute('data-mname');
		    	this.other_payees_modal.lname = event.currentTarget.getAttribute('data-lname');
		    	this.other_payees_modal.ext   = event.currentTarget.getAttribute('data-ext');
		    	this.other_payees_modal.address = event.currentTarget.getAttribute('data-address');
		    },
		    update_payee: function(){
		    	$this = this;
		    	if(!this.other_payees_modal.fname || !this.other_payees_modal.lname || !this.other_payees_modal.address) {
	    			swal("Oops!", "Please fill up fields with asterisk (*)", "error");
		    	}
		    	else{
		    		$.post('<?= base_url("others/update_payee") ?>', {data: this.other_payees_modal}, function(json, textStatus, xhr) {
		    			swal("Success!", "Update complete!", "success")
		    			$this.reg_payees_dtable('')
		    		}, "JSON");
		    	}
		    },
		    delete_payee: function(){
		    	$this = this;
		    	swal({
				  	title: "Are you sure?",
				  	text: "Confirm delete",
				  	icon: "warning",
				  	dangerMode: true
				})
				.then(willDelete => {
					if (willDelete) {
		    			$.post('<?= base_url("others/delete_payee") ?>', {id: this.other_payees_modal.id}, function(json, textStatus, xhr) {
			    			swal("Success!", "Data has been deleted.", "success")
			    			$this.reg_payees_dtable('')
				    		$("#view_payee").modal('toggle')
			    		}, "JSON");
					}
				});
		    },
		    view_particulars: function(event = ""){
		    	var key = event ? event.target.value : "";
		    	$this = this;
		    	$.getJSON('<?= base_url("others/get_other_particulars") ?>', {key: key}, function(json, textStatus) {
		    		$this.get_other_particulars = json;
		    	});
		    },
		    add_particular_modal: function(){
		    	$("#view_particular_modal").modal('toggle')
		    	$("#add_particular_modal").modal('toggle')
		    },
		    add_particular: function(){
		    	if(!this.add_particular_form.particular){
		    		swal("Ooops!", "Particular Name is required.", "error")
		    	}
		    	else{
		    		if(this.add_particular_form.price < 0){
			    		swal("Ooops!", "We don't give things for free. Don't we?", "error")
		    		}
		    		else{	
		    			var $this = this;
		    			$.post('<?= base_url("others/add_particular") ?>', {particular: this.add_particular_form.particular, price: this.add_particular_form.price, particular_type: this.add_particular_form.particular_type, sStatus: this.enrollment_status,cType: this.course_type}, function(json, textStatus, xhr) {
		    				if(json == "error"){
				    			swal("Oops!", "Something went wrong. Please contact support.", "error")
		    				}
		    				else{
			    				swal("Success!", "You just added a new particular.", "success")
			    				$this.view_particulars();
		    				}
			    		}, "JSON");
		    		}
		    	}
		    },
		    submit_to_list: function(event){
		    	var e = event.currentTarget;
		    	var array = {
		    		id: e.getAttribute('data-id'),
		    		name: e.getAttribute('data-name'),
		    		price: parseFloat(e.getAttribute('data-price')),
		    		quantity: parseFloat(1),
		    		subtotal: parseFloat(e.getAttribute('data-price'))
		    	};

		    	$this = this;
		    	if($this.selected_particulars.length == 0){
			    	this.selected_particulars.push(array);
		    	}
		    	else{
		    		var does_exist = false;
			    	$.each($this.selected_particulars, function(index, val) {
			    		if(val.id == array.id){
			    			does_exist = true;
			    			return false;
			    		}
			    	});
			    	if(does_exist == false){
				    	$this.selected_particulars.push(array);	
			    	}
		    	}
		    	this.calc_otherp_total();
		    },
		    remove_selectedp: function(event){
		    	var e = event.currentTarget;
		    	var k = e.getAttribute('data-key')
		    	this.$delete(this.selected_particulars, k)
		    	this.calc_otherp_total();
		    },
		    add_otherp_quantity: function(event){
		    	var id = event.currentTarget.getAttribute('data-id')
		    	var q  = event.target.value;
		    	var $this = this;
		    	$.each($this.selected_particulars, function(index, val) {
		    		if(val.id == id){
		    			val.quantity = q;
		    			val.subtotal = parseFloat(val.price * val.quantity)
		    		}
		    	});
		    	this.calc_otherp_total();
		    },
		    change_subtotal: function(event){
		    	var id = event.currentTarget.getAttribute('data-id')
		    	var q  = event.target.value;
		    	var $this = this;
		    	$.each($this.selected_particulars, function(index, val) {
		    		if(val.id == id){
		    			val.subtotal = q
		    		}
		    	});
		    	this.calc_otherp_total();
		    },
		    calc_otherp_total: function(){
		    	var $this = this;
		    	var total = 0;
		    	$.each($this.selected_particulars, function(index, val) {
	    			total += parseFloat(val.subtotal)
		    	});
		    	$this.selected_particulars_total = total;
		    	$this.to_pay = $this.selected_particulars_total;
		    },
		    os_or_breakdown: function(event = ""){
		    	$("#old_acc_summary").modal('toggle')
		    	$("#os_or_breakdown").modal('toggle')

		    	if(event){
			    	var e = event.currentTarget;
			    	var or = e.getAttribute('data-or');
			    	var breakdown = this.fee_summary['old_system']['payments'][or].payment_breakdown
			    	this.selected_os_or.or = or;
			    	this.selected_os_or.data = breakdown;
		    	}
		    },
		    check_old_balances: function(){
		    	var os_bal = this.fee_summary.old_system;
				
	    		var notice = "";
	    		var has_old = false;

		    	$.each(os_bal, function(index, val) {
		    		if(val.has_balance){
		    			has_old = true;
		    		}
		    	});
	    		
		    	if(has_old){
					swal({
						title: "Payee has still old system balance(s)!", 
						text: 'Kindly check it.',
					});
		    	}
		    },
		    add_to_payments: function(event) {
		        event.preventDefault();

		        var e = event.currentTarget;
	    		var type = e.getAttribute('data-type')
		    	var balance = (e.getAttribute('data-balance')).replace(',', '');;
		    	var key = e.getAttribute('data-sy') + " " + e.getAttribute('data-sem') + " " + balance
		    	var a;
		    	this.to_pay = balance;
		    	if(balance > 0){
			    	if(type == 'regular'){
			    		var a = key + " " + "regular";
			    	}
			    	if(type == 'down_payment'){
			    		var a = 'DownPayment';
			    	}
			    	if(type == 'old_system'){
			    		var a = key + " " + 'OldSystem';
			    	}
			    	if(!this.to_be_paid.includes(a)){
					    this.to_be_paid = []; // remove this line if you want to group all payments with different sy/sem in one OR
					    this.to_be_paid.push(a);
			    	}
		    	}
		    	else{
		    		alert('Already fully paid')
		    		console.log("Already fully paid")
		    	}
		    	this.format_payments();
		    },
		    format_payments: function(){
		    	this.formatted_payments = [];
		    	var array = [];
		    	$.each(this.to_be_paid, function(index, val) {
		    		var x = val.split(" ")
		    		var a = {
		    			sy: x[0],
		    			sem: x[1],
		    			fp_key: index,
		    			balance: x[2],
		    			type: x[3]
		    		}
		    		array.push(a)
		    	});
		    	this.formatted_payments = array;
		    },
		    remove_from_payments: function(event){
		    	event.preventDefault();
		        var e = event.currentTarget;
	    		var key = e.getAttribute('data-key')
	    		var items = this.to_be_paid;
	    		items.splice(key, 1);
	    		this.format_payments();
		    },
		    submit_payment: function(){		
    			var vpf = this.validate_payment_fields();
	    		if(vpf){
			    	if(this.fee_type == 'regular'){
				    	if(this.to_be_paid.length > 0){
	    					this.distribute_payment(this.to_be_paid[0]);
				    	}
				    	else{
				    		swal("Ooops!", "Select payments first" , "warning");
				    	}
			    	}
			    	else{
			    		if(this.selected_particulars.length > 0){
				    		this.other_payment();
			    		}
			    		else{
				    		swal("Ooops!", "Select particulars first" , "warning");
			    		}
			    	}
	    		}
	    		$(".payment_distribution").val('0.00')
		    },
		    validate_payment_fields: function(){
		    	if(!this.or_served ){
			    	swal("Ooops!", "Please fill up required fields." , "warning");
		    		return false;
		    	}
		    	if(this.to_pay <= 0){
		    		swal("Ooops!", "Please input amount to be paid." , "warning");
		    		return false;
		    	}
		    	if(this.change < 0){
		    		swal("Ooops!", "Insufficient funds." , "warning");
		    		return false;
		    	}
		    	else{
			    	return true;
		    	}
		    },
		    calc_change: function(){
		    	this.distributed_cash_remaining = parseFloat(this.to_pay) 
		    	var change = parseFloat(this.cash) - parseFloat(this.to_pay)
		    	this.change = change;
		    },
		    distribute_payment: function(data){
		    	var a = data.split(" ");
		    	if(a[3] != "OldSystem"){
			    	$("#payment_distribution_modal").modal('toggle')
		    	}
		    	else{
		    		var os = this.fee_summary.old_system[a[0] + " - " + a[1]];
		    		this.old_system_distribution = os;
			    	$("#os_payment_distribution_modal").modal('toggle')
		    	}
		    },
		    process_distribution_amount: function(){
		    	var di = $(".distribution_inputs");
		    	var total = 0;
		    	$.each(di, function(index, val) {
		    		var amt = $(val).val() ? parseFloat($(val).val()) : 0
		    		total = parseFloat(total) + amt;
		    	});
		    	this.distributed_cash_remaining = parseFloat(this.to_pay) - parseFloat(total);
		    },
		    os_process_distribution_amount: function(){
		    	var payments = $('.os_distribution_payments');
		    	var total = 0;
		    	this.distributed_cash_remaining = 0;
		    	$.each(payments, function(index, val) {
		    		if(!$(val).val()){
		    			$(val).val('0.00')
		    		}
		    		total = parseFloat(total) + parseFloat($(val).val());
		    	});
		    	this.distributed_cash_remaining = (this.to_pay - total);
		    },
		    reg_final_payment: function(){
		    	var $this = this;
		    	swal({
					title: "Are you sure?",
					icon: "warning",
					buttons: true,
					dangerMode: true,
				})
				.then((proceed) => {
				  	if (proceed) {
				    	var el = $(".payment_distribution");
				    	$this.final_payments = [];
				    	var dcr = parseFloat(this.distributed_cash_remaining);
				    	if( dcr > 0 || dcr < 0 ){
				    		dcr > 0 ? alert("Please expend remaining cash.") : alert("Insufficient funds."); 
				    	}
				    	else{
				    		$.each(el, function(index, val) {
				    			if($(val).val() > 0){
						    		$this.final_payments.push({
						    			'sy' : $(val).attr('data-sy'),
						    			'sem': $(val).attr('data-sem'),
						    			'value': $(val).val(),
						    			'type': $(val).attr('data-type'),
						    			'particular': $(val).attr('data-particular')
						    		});
				    			}
					    	});

					    	$.post('<?= base_url("home/submit_payment") ?>', 
				    			{
				    				payments: $this.final_payments, 
				    				fee_type: $this.fee_type, 
				    				to_pay: $this.to_pay, 
				    				or: $this.or_served, 
				    				receipt: $this.receipt, 
				    				date: $this.payment_date, 
				    				ssi_id: $this.ssi_id,
				    				acct_no: $this.acct_no,
				    				course_type: $this.course_type,
				    				course: $this.course,
				    				current_status: $this.current_status,
				    			},
				    			function(data, textStatus, xhr) {
					    			this.print_receipt(JSON.parse(data))
					    		}.bind(this)
					    	);
				    	}
					}
				});
		    },
		    other_payment: function(){

		    	var $this = this;
		    	swal({
					title: "Are you sure?",
					icon: "warning",
					buttons: true,
					dangerMode: true,
				})
				.then((proceed) => {
				  	if (proceed) {
						if($this.has_selected){
					    	if($this.to_pay < $this.selected_particulars_total){
				    			swal("Ooops!", "The total amount to be paid is " + $this.selected_particulars_total + ".", "error")
					    	}
					    	else{
						    	$.post('<?= base_url("home/submit_payment") ?>', 
						    		{
						    			data: $this.selected_particulars, 
						    			payments: $this.final_payments, 
					    				fee_type: $this.fee_type, 
					    				to_pay: $this.to_pay, 
					    				or: $this.or_served, 
					    				receipt: $this.receipt, 
					    				date: $this.payment_date, 
					    				ssi_id: $this.ssi_id, 
					    				payee_type: $this.payee_type,
					    				other_payee_id: $this.other_payee_id
						    		}, 
					    		function(data, textStatus, xhr) {
					    			$this.print_receipt(JSON.parse(data))
					    		}.bind($this));
					    	}
				    	}
				    	else{
				    		alert()
				    	}
					}
				});
		    },
		    view_reports: function(){
	    		window.open('<?= base_url("reports/monthly_collection_report") ?>','popUpWindow','height=4000,width=4000,resizable=yes,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no, status=yes');
		    },
		    generate_report: function(){
		    	swal("What report do you wish to generate?", {
				  buttons: {
				    monthly: {
				      text: "Monthly",
				      value: "monthly",
				    },
				  },
				})
				.then((value) => {
					var $this = this;
				 	switch (value) {
				    	case "pdc":
				    	// this.pdc_report()
					      	break;
					    case "monthly":
					    	$this.monthly_report();
				      	break;
				    default:
				    	break;
				  }
				});
		    },
		    monthly_report: function(){
		    	var $this = this;
		    	swal({
				    title: 'Set month and year',
					content: {
					    element: "input",
					    attributes: {
					      type: "month"
					    },
				  	},				  	
				    closeOnClickOutside: false
				})
				.then((value) => {
					if(value){
						$.post('<?= base_url("reports/generate_monthly_report") ?>', {month: value}, function(data, textStatus, xhr) {
							console.log(data)
					    	if(data == 'true'){
				    			swal("Done!", "Report has been succesfully generated in Crystal Report.", "success");
					    	}
					    	else{
				    			swal("Ooops!", "No data to be generated in the selected month.", "error");
					    	}
				    	});
					}
					else{
						$this.generate_report();
					}
				});
		    },
			get_receipt_for_print: function(event){
				event.preventDefault();
				var e = event.currentTarget;
				var or = e.getAttribute('data-key')
				// console.log(or)
				$.post('<?= base_url("home/print_or") ?>', {or: or}, function(data, textStatus, xhr) {
					// console.log(data);
					$this.print_receipt(JSON.parse(data));
				});

			},
		    print_receipt: function(data){
		    	var particulars = []; // FOR ADJUSTED
		    	var particulars_2 = []; // FOR ORACLE

		    	var misc_1 = {
		    		particular: 'Miscellaneous',
		    		amount: 0
		    	};
		    	var misc_2 = {
		    		particular: 'Miscellaneous',
		    		amount: 0
		    	};

		    	if(this.fee_type == 'regular'){
		    		$.each(data, function(index, val) {
		    			if( val.feeType == "Miscellaneous" ){
							misc_1.amount = parseFloat(misc_1.amount) + parseFloat(val.amount);
							misc_2.amount = parseFloat(misc_2.amount) + parseFloat(val.amount_oracle);
						}
						else{
			    			particulars.push({
			    				particular: val.particular,
			    				amount: val.amount
			    			});
			    			particulars_2.push({
			    				particular: val.particular,
			    				amount: val.amount_oracle
			    			});
						}	
		    		});
		    	}
		    	else{
		    		$.each(data, function(index, val) {
		    			particulars.push({
		    				particular: val.name,
		    				amount: val.subtotal
		    			});
		    		});
		    	}

		    	if(misc_1.amount > 0){
			    	particulars.push(misc_1);
			    	particulars_2.push(misc_2);
		    	}
		    	if(this.receipt == 'OR'){
			    	var $this = this;
			    	var amt = '';
			    	var amt_words = "";
			    	var or  = window.open('<?= base_url('home/receipt'); ?>','or','height=500,width=900,left=50,top=50,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no, status=yes');
	    			var street = this.address.street ? this.address.street : '';
	    			var brgy   = this.address.brgy_name ? this.address.brgy_name : '';
	    			var city   = this.address.city_name ? this.address.city_name : '';
	    			var address =  street + " " + brgy + ", " + city;
	    				or.title = 'Adjusted';
			    		or.rows = particulars.reverse();
		    			or.name = this.name;
		    			or.address = address.toUpperCase();
		    			or.amount = this.to_pay;
		    			or.amt_words = this.numberToWords(this.to_pay)
		    			or.date = this.payment_date;

		    		if(this.fee_type == 'regular'){
		    			console.log(this.fee_type);	
				    	var or2 = window.open('<?= base_url('home/receipt'); ?>','or2','height=500,width=900,left=800,top=50,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no, status=yes');
				    		or2.title = "For ORACLE";
				    		or2.rows = particulars_2.reverse();
			    			or2.name = this.name;
			    			or2.address = address.toUpperCase();
			    			or2.amount = this.to_pay;
			    			or2.amt_words = this.numberToWords(this.to_pay)
			    			or2.date = this.payment_date;
		    		}
		    	}

		    	else{
			    	var ar = window.open('<?= base_url('home/receipt_acknowledgement'); ?>','ar','height=500,width=900,left=50,top=50,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,directories=no, status=yes');
			    		ar.or_served = this.or_served;
		    			ar.name = this.name;
		    			ar.amount = this.to_pay;
		    			ar.amt_words = this.numberToWords(this.to_pay)
		    			ar.date = this.payment_date;
		    			ar.rows = particulars;
		    	}
		    	$('.modal').modal('hide');
		    	$("#selected_student").val('');
		    	location.reload();
		    },
		    downpayment_bills: function(course, studentStatus){ // check if there are bills for downpayment
		    	$this = this;
		    	$.getJSON('<?= base_url("home/downpayment_bills") ?>', {course: course, studentStatus: studentStatus, ssi_id: $this.ssi_id}, function(json, textStatus) {
		    		$this.has_downpayment_bills = json;
		    	});
		    },
		    current_or_served: function(){
		    	var receipt = this.receipt;
		    	$.getJSON('<?= base_url("home/or_serving") ?>', {receipt: receipt}, function(json, textStatus) {
		    		this.or_served = json;
		    	}.bind(this));
		    },
		   	search_otherp_paid: function(){
		   		$(".search_other_p_paid").trigger('keyup')
		   		$.getJSON('<?= base_url("home/search_otherp_paid") ?>', {particular: this.op_search, ssi_id: this.ssi_id}, function(json, textStatus) {
		   			console.log(json)
		   			if(json){
			   			this.ops_particular = json;
		   			}
		   		}.bind(this));
		   	},
		   	op_or_details: function(event){
		    	var e = event.currentTarget;
		    	var payment_id = e.getAttribute('data-id');
		   		console.log(payment_id)
		   	},
		   	downpayment_error: function(){
		   		$this = this;
		   		swal("Notice", $this.has_downpayment_bills, "error");
		   	},
		   	old_system_details: function(key){

		   		var data = this.fee_summary.old_system[key];
		   		var tuition_misc = data.tuition_misc_assessment ? data.tuition_misc_assessment : 0;
		   		var bridging = data.bridging_new_system ? data.bridging_new_system : 0;
		   		var tutorial = data.tutorial_new_system ? data.tutorial_new_system : 0;

		   		swal({
					title: "Fee Details",
					text: "Tuition/Miscellaneous: " + tuition_misc + "\nBridging: " + bridging + "\nTutorial: " + tutorial,
					imageUrl: 'thumbs-up.jpg'
				});
		   	}
	  	}
	});

</script>

<style>
	
	.w125p {
		width:200px !important;
		min-width: 200px !important;
		max-width: 200px !important;
	}

	.fee_summary_row:hover, .payment_row:hover, .other_payee_row:hover {
		cursor: pointer;
	}

	.cancel_cursor:hover {
		cursor: no-drop;
	}

	.sd_detail_tbl tbody tr td{
		padding-bottom: 5px !important;
		padding-top: 5px !important;
	}

	.pl5 {
		padding-left:30px !important;
	}

	.mb5 {
		margin-bottom: 5px !important;
	}

	.swal-text {
		text-align: center;
	}

	.payment_select {
		background-color: #31a3a3 !important;
		color: white;
	}
	.payment_select td {
		font-size:18px;
		border:1px solid #888;
	}
</style>