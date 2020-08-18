<div class="jumbotron" style="padding:10px; min-height: 400px;">
	<div class="row">
		<div class="col-lg-7">				
			<h5>
				<i class="fa fa-list"></i> Fee Summary
				<select v-on:change='select_change($event)' name="fee_type" id="fee_type" class="pull-right input-sm" data-type='fee_type' v-model="fee_type">
					<option value="regular">Regular Fees</option>
                    <!-- <option value="special">Special Fees</option> -->
                    <option value="other">Special / Other Payments</option>
				</select>
				<hr style="border:0.5px solid #777 !important;">
			</h5>
	
			<div id="payment_content">
				<div class="table-responsive" v-if="fee_type == 'regular'">
					<table class="table table-hover" id="fee_summary_tbl">
						<thead>
							<th>School Year</th>
							<th>Semester</th>
							<th>Fee Type</th>
							<th>Assessment <small>/discount</small></th>
							<th>Paid</th>
							<th>Balance</th>
						</thead>
						<tbody>
							<tr v-if='fee_summary.length == 0'>
								<td colspan="6" class="text-center"> <i>*** Please select student ***</i> </td>
							</tr>
							<tr v-for="fs in fee_summary['regular_fees']" v-bind:class='regular_hide' class="fee_summary_row" v-bind:data-sy="fs.sy" v-bind:data-sem="fs.sem" v-on:click="view_summary_details($event)" data-toggle="modal" data-target="#summary_details_modal" @contextmenu="add_to_payments($event)" data-type="regular" v-bind:data-balance="ses_role == 'cashier' ? fs.remaining_balance_2 : fs.remaining_balance_1">
								<td> {{ fs.sy }} </td>

								<td> {{ fs.sem }} </td>
								<td> REGULAR</td>

								<td v-if="ses_role == 'cashier'"> {{ formatPrice(fs.total_2_discounted) }} <small v-if="fs.discount">(-{{fs.discount2}})</small></td>
								<td v-else>{{ formatPrice(fs.total_1_discounted) }}<small v-if="fs.discount">(-{{fs.discount}})</small></td>

								<td v-if="ses_role == 'cashier'"> {{ formatPrice(fs.paid2) }} </td>
								<td v-else> {{ formatPrice(fs.paid1) }} </td>

								<td v-if="ses_role == 'cashier'"> {{ formatPrice(fs.remaining_balance_2) }} </td>
								<td v-else> {{ formatPrice(fs.remaining_balance_1) }} </td>
							</tr>		
							<tr v-if="(has_selected) && (fee_summary['old_system'])" v-bind:class="regular_hide" class="fee_summary_row" data-toggle="modal" data-target="#old_acc_summary" data-type="old_system" v-for="(os, key) in fee_summary['old_system']" @contextmenu="add_to_payments($event)" v-bind:data-balance="os.grand_remaining" v-bind:data-sy="os.sy" v-bind:data-sem="os.sem" v-on:click="old_system_details(key)">
								<td>{{os.sy}}</td>
								<td>{{os.sem}}</td>
								<td>OLD SYSTEM</td>
								<td>{{os.grand_assessment}}<small v-if="os.discount">(-{{os.discount}})</small></td>
								<td>{{os.new_system_paid}}</td>
								<td>{{os.grand_remaining}}</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div v-if="fee_type == 'other'">
					<table class="table table-hover">
						<thead>
							<th>Particular</th>
							<th>Price</th>
							<th>Quantity</th>
							<th>Subtotal</th>
							<th class="text-center">Action</th>
						</thead>
						<tbody>
							<tr v-for="(particular, key) in selected_particulars" v-if="selected_particulars.length">
								<td>{{particular.name}}</td>
								<td>{{particular.price}}</td>
								<td><input type="number" class="form-control input-xs" min="1" value="1" style="width:80px !important;" v-on:change="add_otherp_quantity($event)" :data-id="particular.id"></td>
								<!-- <td>{{particular.subtotal}}</td> -->
								<td>
									<input type="text" :value="particular.subtotal" class="form-control input-xs" style="max-width: 150px;" v-on:keyup="change_subtotal($event)" :data-id="particular.id">
								</td>
								<td class="text-center">
									<button class="btn btn-default btn-xs" :data-key="key" v-on:click="remove_selectedp($event)" :data-price="particular.price">
										<span class="fa fa-minus"></span>
									</button>
								</td>
							</tr>
							<tr v-if="selected_particulars.length">
								<td colspan="3" class="text-right"><b>Total</b></td>
								<td colspan="2"><b>{{selected_particulars_total}}</b></td>
							</tr>
							<tr v-if="selected_particulars.length == 0">
								<td colspan="5" class="text-center"><i> <span class="fa fa-arrow-right" v-for="index in 3"></span> Select particulars first <span class="fa fa-arrow-right" v-for="index in 3"></span></i></td>
							</tr>
						</tbody>
					</table>
				</div>
	
			</div>
		</div>	
		<div class="col-lg-3">
			<div v-if="fee_type == 'regular'">
				<h5>
					<i class="fa fa-calendar"></i> Payment Schedule
					<hr style="border:0.5px solid #777 !important;">
				</h5>
				<div class="row" style="padding:0px 10px 0px 10px;">
					<div class="row">
						<div class="col-lg-4">
							<select name="school_year" id="school_year" class="form-control input-sm" data-type="sy" v-on:change='select_change($event)'>
								<!-- <option disabled selected v-if="!sy_regular.length && !sy_special.length"> School Year </option> -->
								<option disabled selected> School Year </option>
								<option v-for="sr in sy_regular" v-bind:class="regular_hide"> {{ sr }} </option>
								<!-- <option v-for="sr in sy_special" v-bind:class="special_hide"> {{ sr }} </option> -->
							</select>
						</div>
						<div class="col-lg-4">
							<select name="sem" id="sem" class="form-control input-sm" data-type="sem" v-on:change='select_change($event)'>
								<option value="1st">1st</option>
								<option value="2nd">2nd</option>
							</select>
						</div>
						<div class="col-lg-4">
							<select name="period" id="period" class="form-control input-sm" data-type="period" v-on:change='select_change($event)' disabled>
								<option value="prelim">Prelim</option>
								<option value="midterm">Midterm</option>
								<option value="prefinal">Prefinal</option>
								<option value="Final">Final</option>
							</select>
						</div>
					</div>
					<div class="row" style="padding:10px 10px 0px 10px;">
						<table class="table table-hover" id="payment_sched_tbl">
							<tbody v-if="!has_selected">
								<tr>	
									<td class="text-center"><i>*** Please select student ***</i></td>
								</tr>
							</tbody>
							<tbody v-if="has_selected && ps_year == ''">
								<tr>
									<td class="text-center"><i>*** Please school year ***</i></td>
								</tr>
							</tbody>
							<tbody v-if="periods.is_empty === false && periods.is_current">
								<tr>
									<td class="w125p">Prelim</td>
									<td><b>&#8369; {{ formatPrice((parseFloat(periods.prelim)).toFixed(2)) }} </b></td>
								</tr>
								<tr>
									<td class="w125p">Midterm</td>
									<td><b>&#8369; {{ formatPrice((parseFloat(periods.midterm)).toFixed(2)) }} </b></td>
								</tr>
								<tr>
									<td class="w125p">Prefinal</td>
									<td><b>&#8369; {{ formatPrice((parseFloat(periods.prefinal)).toFixed(2)) }} </b></td>
								</tr>
								<tr>
									<td class="w125p">Final</td>
									<td><b>&#8369; {{ formatPrice((parseFloat(periods.final)).toFixed(2)) }} </b></td>
								</tr>
							</tbody>
							<tbody v-if="periods.is_empty === false && periods.is_current === false">
								<tr>
									<td class="w125p">Total Balance</td>
									<td><b>&#8369; {{ formatPrice((parseFloat(periods.total)).toFixed(2)) }}</b></td>
								</tr>
							</tbody>
							<tbody v-if="periods.is_empty === true">
								<tr>
									<td class="text-center"><i>*** Empty result ***</i></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div v-if="fee_type == 'other'">
				<h5>
					<i class="fa fa-list"></i> Particular list
					<hr style="border:0.5px solid #777 !important;">
				</h5>
				<div class="row" style="padding:0px 10px 0px 10px;">
					<div class="form-group">
						<div class="row">
							<div class="col-lg-6"></div>
							<div class="col-lg-6">
                                <input type="text" class="form-control" id="select_otherp" placeholder="Search particular here" v-on:keyup="view_particulars($event)">
							</div>
						</div>
					</div>
					<table class="table table-hover">
						<thead>
							<th>Particular</th>
							<th>Type</th>
							<th>Price</th>
							<th class="text-center">Action</th>
						</thead>
						<tbody>
							<tr v-for="particular in get_other_particulars">
								<td>{{particular.particularName}}</td>
								<td>{{capitalize(particular.billType)}}</td>
								<td>{{particular.amt2}}</td>
								<td class="text-center">
									<button class="btn btn-success btn-xs" v-on:click="submit_to_list($event)" :data-id="particular.particularId" :data-name="particular.particularName" :data-price="particular.amt2">
										<span class="fa fa-plus"></span>
									</button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-2" style="border-left: solid #777 1px;min-height: 500px;">
			<div v-if="fee_type == 'regular'">
				<h5 class="text-center">
					<i class="fa fa-check"></i> Selected payments <i class="fa fa-check"></i><br>
					<hr style="border:0.5px solid #777 !important;">
				</h5>
				<div class="text-center">			
					<!-- ADD the line below in v-if in line 202 regarding enrollment flow status -->
					<!-- && enrollment_status == 'Enrolled' -->
					<button class="btn btn-sm btn-success dp_btn" ref="dp_btn" v-if="has_current_bill == false && has_selected && has_downpayment_bills == true" v-on:click="add_to_payments($event)" data-type="down_payment" data-balance="1" data-sy="NA" data-sem="NA">Down Payment</button>
					<button class="btn btn-sm btn-danger" v-on:click="downpayment_error()" v-bind:title="has_downpayment_bills" v-if="has_selected && has_downpayment_bills != true">Down Payment Unavailable <span class="fa fa-question-circle"></span> </button>
				</div><br>
				<div class="table-responsive" v-if="fee_type == 'regular'" style="text-align:center;">
					<table class="table-condensed" style="margin-left: auto; margin-right: auto;">
						<tr v-for="(fp, key) in formatted_payments" v-if="to_be_paid" @contextmenu="remove_from_payments($event)" v-bind:data-key="fp.fp_key" class="cancel_cursor payment_select">
							<td>{{fp.sy}}</td>
							<td>{{fp.sem}}</td>
						</tr>
					</table>
				</div>
			</div>
			<div v-else>
				<h5>
					<i class="fa fa-money"></i> Payment History<br>
					<hr style="border:0.5px solid #777 !important;">
				</h5>
				<button class="btn btn-success btn-xs pull-right" data-toggle="modal" data-target="#op-search-particular" v-if="has_selected">
					<i class="fa fa-search"></i> Search particular
				</button><br><br>
				<div class="table-responsive pre-scrollable " style="min-height: 300px !important;" v-if="has_selected">
					<table class="table table-hover table-condensed">
						<thead>
							<th>OR</th>
							<th>Amount</th>
						</thead>
						<tbody>
							<tr v-for="sp in fee_summary.special_payments" v-on:click="op_or_details($event)" :data-id="sp.paymentId">
								<td>{{sp.orNo}}</td>
								<td>{{formatPrice(sp.paid_amount)}}</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<div class="modal" id="summary_details_modal" role="dialog">
		<div class="modal-dialog">
			<div class="modal-content" style="background: #eee !important;">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h5 class="modal-title text-center">Student Bill and Payment Transaction Records</h5>
					<h5 class="modal-title text-center"><b>{{selected_sum_sy}} | {{selected_sum_sem}}</b></h5>
					<select id="fs_breakdown" class="input-sm pull-right" v-on:change="summary_details_switch($event)">
						<option value="student_bill">Student Bills</option>
						<option value="payments">Payment Transactions</option>
					</select>
				</div>
		  		<div class="modal-body">
					<table class="table table-hover sd_detail_tbl" v-bind:class="bill_visibility">
						<thead>
							<th>Particular</th>
							<th>Price</th>
							<th>Paid Amount</th>
							<th>FEE TYPE</th>
						</thead>
						<tbody>
							<tr v-for="(sd, index ) in summary_details_bills">
								<td v-html="sd.particular">{{sd.particular}}</td>
								
								<td v-if="ses_role == 'cashier'">{{sd.price2}}</td>
								<td v-else>{{sd.price1}}</td>

								<td v-if="ses_role == 'cashier'">{{sd.paid2}}</td>
								<td v-else>{{sd.paid1}}</td>

								<td>{{sd.feeType}}</td>
							</tr>
						</tbody>
					</table>
					<table class="table table-hover" v-bind:class="payment_visibility">
						<thead>
							<th>OR No.</th>
							<th>Amount</th>
							<th>Printing Type</th>
							<th>Payment Date</th>
							<th>Payment Status</th>
						</thead>
						<tbody v-if="summary_details_payments.length == 0">
							<tr>
								<td colspan="6" class="text-center"><i>*** Empty result ***</i></td>
							</tr>
						</tbody>
						<tbody>
							<tr v-for="(sdp, index) in summary_details_payments" class="payment_row" v-on:click="or_detail_modal(index)" @contextmenu="get_receipt_for_print($event)" v-bind:data-key="index">
								<td>{{index}}</td>
								<td v-if="ses_role == 'cashier'">{{formatPrice(sdp.amount_oracle)}}</td>
								<td v-else>{{formatPrice(sdp.amount)}}</td>
								<td>{{sdp.printing_type}}</td>
								<td>{{sdp.payment_date}}</td>
								<td>{{sdp.payment_status}}</td>
							</tr>
						</tbody>
					</table>
		  		</div>
		  		<div class="modal-footer">
		    		<!-- <button type="button" class="btn btn-sm btn-danger btn-default" data-dismiss="modal">Close</button> -->
		  		</div>
			</div>
		</div>
	</div>


	<div id="or_details" class="modal" role="dialog" data-backdrop="static" data-keyboard="false">
		<div class="modal-dialog">
			<div class="modal-content">
		  		<div class="modal-header">
			    	<button type="button" class="close" data-dismiss="modal" data-toggle="modal" data-target="#summary_details_modal">&times;</button>
			    	<h4 class="modal-title"><small>OR</small> <b>#{{current_or}}</b> <small>Breakdown</small></h4>
			  	</div>
			  	<div class="modal-body">
					<table class="table table-striped table-hover">
						<thead>
							<th>Particular</th>
							<th>Paid Amount</th>
						</thead>
						<tbody>
							<tr v-for="(aod, index) in all_or_details">
								<td>{{ aod.particular }}</td>
								<td v-if="ses_role == 'cashier'">{{ aod.paid2 }}</td>
								<td v-else>{{ aod.paid1 }}</td>
							</tr>
						</tbody>
					</table>
			  	</div>
			  	<div class="modal-footer">
					<div class="btn-group">
				    	<button v-if="current_or_status != 'Cancelled'" type="button" class="btn btn-success btn-sm dropdown-toggle" v-on:click="edit_detail_modal(current_or)" aria-haspopup="true" aria-expanded="false">Edit OR</button>
				  	</div>
					<div class="btn-group">
				    	<button v-if="current_or_status != 'Cancelled'" type="button" class="btn btn-danger btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Cancel OR</button>
				    	<div class="dropdown-menu">
							<ul class="list-unstyled text-center">
								<li>Are you sure?</li>
								<li><a class="dropdown-item" href="#" v-on:click="cancel_payment('Cancelled')">Yes</a></li>
								<li><a class="dropdown-item" href="#">No</a></li>
							</ul>
					  	</div>
				  	</div>
			  	</div>
			</div>
		</div>
	</div>

	<div class="modal" id="edit_payment_modal">
		<div class="modal-dialog">
			<div class="modal-content">
		  		<div class="modal-header">
			    	<button type="button" class="close" data-dismiss="modal" data-toggle="modal" data-target="#or_details">&times;</button>
			    	<h4 class="modal-title"><small>OR</small> <b>#{{current_or}}</b> <small>Edit</small></h4>
			  	</div>
				<div class="modal-body">
					<h2>OR Information</h2>
					<div class="mb5">
						<select class="form-control input-sm" id="edit_receipt" v-model="edit_receipt">
							<option value="OR">Official Receipt</option>
							<option value="AR">Acknowledgement Receipt</option>
							<!-- <option value="TR">Temporary Receipt</option> -->
						</select>
					</div>
					<div class="mb5">
						<input type="text" class="form-control input-sm" placeholder="OR NUMBER" id="edit_or" v-model="edit_or">
					</div>
					<div class="mb5">
						<input type="date" class="form-control input-sm" id="edit_date" v-model="edit_date">
					</div>
					<div class="form-group mb5">
						<input type="text" class="form-control input-sm" id="edit_total" v-model.lazy="edit_total">
					</div>
					<div class="form-group mb5">
						<button type="button" class="btn btn-lg btn-primary form-control" v-if="has_selected" style="min-height: 70px; font-size: 20px;" v-on:click="edit_payment()" v-if="ses_role != 'cashier'">EDIT OR DETAILS</button>
					</div>
					<hr>
					<h2>Payment Details</h2>

					<table class="table table-striped table-hover">
						<thead>
							<th>Particular</th>
							<th>Paid Amount</th>
						</thead>
						<tbody>
							<tr v-for="(aod, index) in all_or_details">
								<td>{{ aod.particular }}</td>
								<td v-if="ses_role == 'cashier'">{{ aod.paid2 }}</td>
								<td v-else>{{ aod.paid1 }}</td>
							</tr>
						</tbody>
					</table>
					<div class="form-group mb5">
					  	<label for="edit_total_details">Total Amount</label>
						<input type="text" class="form-control input-sm" disabled id="edit_total" v-model="edit_total">
					</div>
<!-- 
					<div v-for="(val, index) in all_edit_details" v-bind:key="val.paymentDetailsId">
						<div class="form-group mb5">
						  	<label for="edit_particular">Particular</label>
							<input type="text" class="form-control input-sm" :id="edit_particular"  value="val.particular">
						</div>
						<div class="form-group mb5">
						  	<label for="edit_amount">Amount</label>
							<input type="text"  class="form-control input-sm" id="edit_amount" v-model="val.detail_paid_amount">
						</div><br>
					</div>
					<div class="form-group mb5">
						<button type="button" class="btn btn-lg btn-primary form-control" v-if="has_selected" style="min-height: 70px; font-size: 20px;" v-on:click="edit_payment_details()" v-if="ses_role != 'cashier'">EDIT PAYMENT DETAILS</button>
					</div> -->
				</div>
			</div>
		</div>
	</div>

	<div class="modal" id="payment_modal">
		<div class="modal-dialog">
			<div class="modal-content">
	
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" class="pull-right">&times;</button><br>
				</div>
	
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

</div>

<div class="modal" id="os_or_breakdown" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">

			<div class="modal-body">
				<button type="button" class="close" @click="os_or_breakdown()">&times;</button>
				<h4>OR #{{selected_os_or.or}} Breakdown</h4>
				<hr>
				<table class="table table-hover">
					<thead>
						<th>Particular Name</th>
						<th>Price</th>
						<th>Paid Amount</th>
					</thead>
					<tbody>
						<tr v-for="(data, key) in selected_os_or['data']">
							<td>{{ucwords(data.oaParticularName)}}</td>
							<td>{{data.oaAmount}}</td>
							<td>{{data.paymentAmount}}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<div class="modal" id="payment_distribution_modal" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close pull-right" data-dismiss="modal">&times;</button>
				<h4>Distribute Cash</h4>
				<hr>
					<h4>Cash: {{ cash }}</h4>
					<h4>Remaining: {{ distributed_cash_remaining }}</h4>
					<br>
					<table class="table table-hover table-bordered">
						<thead>
							<th>School year</th>
							<th>Sem</th>
							<th>Remaining Balance</th>
							<th>Distribution</th>
						</thead>
						<tbody>
							<tr v-for="(fp, key) in formatted_payments">
								<td>{{ fp.sy }}</td>
								<td>{{ fp.sem }}</td>
								<td>{{ formatPrice(fp.balance) }}</td>
								<td><input type="number" min="0" ref="all_payments" step="0.01" class="form-control input-sm distribution_inputs payment_distribution" v-on:keyup="process_distribution_amount()" value="0.00" v-bind:data-sy="fp.sy" v-bind:data-sem="fp.sem" v-bind:data-type="fp.type"></td>
							</tr>
						</tbody>
					</table>
				<hr>
				<div class="text-right">
					<button type="button" class="btn btn-success btn-sm" v-on:click="reg_final_payment()">Submit</button>				
				</div>
			</div>

		</div>
	</div>
</div>

<div class="modal" id="os_payment_distribution_modal" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-body">
				<button type="button" class="close pull-right" data-dismiss="modal">&times;</button>
				<h4>Distribute Cash For OLD SYSTEM</h4>
				<hr>
					<h4>Cash: {{ cash }}</h4>
					<h4>Remaining: {{ distributed_cash_remaining }}</h4>
					<br>
					<table class="table table-hover table-bordered">
						<thead>
							<th>School year</th>
							<th>Sem</th>
							<th>Particular Name</th>
							<th>Remaining Balance</th>
							<th>Distribution</th>
						</thead>
						<tbody>
							<tr v-if="old_system_distribution.grand_remaining > 0">
								<td>{{old_system_distribution.sy}}</td>
								<td>{{old_system_distribution.sem}}</td>
								<td>Tuition/Miscellaneous</td>
								<td>{{old_system_distribution.tuition_misc_assessment}}</td>
								<td><input type="number" min="0"step="0.01" ref="all_payments" class="form-control input-sm os_distribution_payments payment_distribution" v-on:keyup="os_process_distribution_amount()" value="0.00" v-bind:data-sy="old_system_distribution.sy" v-bind:data-sem="old_system_distribution.sem" data-type="old_system" data-particular="tuition_misc"></td>
							</tr>
							<tr v-if="old_system_distribution.bridging > 0">
								<td>{{old_system_distribution.sy}}</td>
								<td>{{old_system_distribution.sem}}</td>
								<td>Bridging</td>
								<td>{{old_system_distribution.bridging}}</td>
								<td><input type="number" min="0"step="0.01" ref="all_payments" class="form-control input-sm os_distribution_payments payment_distribution" v-on:keyup="os_process_distribution_amount()" value="0.00" v-bind:data-sy="old_system_distribution.sy" v-bind:data-sem="old_system_distribution.sem" data-type="old_system" data-particular="bridging"></td>
							</tr>
							<tr v-if="old_system_distribution.tutorial_new_system > 0">
								<td>{{old_system_distribution.sy}}</td>
								<td>{{old_system_distribution.sem}}</td>
								<td>Tutorial Fee</td>
								<td>{{old_system_distribution.tutorial_new_system}}</td>
								<td><input type="number" min="0"step="0.01" ref="all_payments" class="form-control input-sm os_distribution_payments payment_distribution" v-on:keyup="os_process_distribution_amount()" value="0.00" v-bind:data-sy="old_system_distribution.sy" v-bind:data-sem="old_system_distribution.sem" data-type="old_system" data-particular="tutorial"></td>
							</tr>
						</tbody>
					</table>
				<hr>
				<div class="text-right">
					<button type="button" class="btn btn-success btn-sm" v-on:click="reg_final_payment()">Submit</button>				
				</div>
			</div>

		</div>
	</div>
</div>

<div class="modal" id="op-search-particular">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					<span class="sr-only">Close</span>
				</button>
				<h4 class="modal-title">Paid Particulars</h4>
			</div>
			<div class="modal-body" style="min-height: 200px;">
				<input type="text" class="form-control input-sm pull-right search_other_p_paid" v-model="op_search" style="max-width: 200px;" placeholder="search here" v-on:keyup="search_otherp_paid()">

				<table class="table table-hover table-bordered mt" style="margin-top: 40px;">
					<thead>
						<th>OR <small> (paid in)</small></th>
						<th>Particular</th>
						<th>Price</th>
						<th>Paid Amount</th>
						<th>Remaining Balance</th>
					</thead>
					<tbody>
						<tr class="text-center" v-if="ops_particular.length == 0">
							<td colspan="5"><i>*** Please input something ***</i></td>
						</tr>
						<tr v-for="p in ops_particular" v-else>
							<td>{{p.orNo}}</td>
							<td>{{p.particularName}}</td>
							<td>{{p.price_2}}</td>
							<td>{{p.paid_amt_2}}</td>
							<td>{{p.remaining_balance}}</td>
						</tr>
					</tbody>
				</table>

			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->