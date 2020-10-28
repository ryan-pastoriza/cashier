<?php

/**
 *
 */
class Home_model extends CI_Model
{

	public $sy;
	public $sem;
	public $syId;
	public $semId;
	public $user_id;
	public $username;

	public function __construct(){
		parent::__construct();
		$this->load->database();
		$this->load->library('session');
		$this->sy  = $this->session->userdata('user_data')['sy'];
		$this->sem = $this->session->userdata('user_data')['sem'];
		$this->user_id = $this->session->userdata('user_data')['user_data']->userId;
		$this->syId = $this->sy_sem_id()['sy'];
		$this->semId = $this->sy_sem_id()['sem'];
		$this->username = $this->session->userdata()['user_data']['user_data']->username;
	}

	public function format_number($var){
		return number_format((double)$var, 2);
	}

	public function student_list($term){
		$sis_db = $this->load->database('sis_db', TRUE);
		$select = [
					'stud_per_info.spi_id', 'stud_per_info.fname', 'stud_per_info.lname', 'stud_per_info.mname', 'stud_sch_info.ssi_id', 'stud_sch_info.stud_id', 'stud_sch_info.usn_no', 'phone_numbers.phone_number', 'stud_sch_info.acct_no'];
		$query  = $sis_db
					->select($select)
					->like("CONCAT(stud_per_info.lname , ', ',stud_per_info.fname)", $term)
					->join('stud_per_info', 'stud_sch_info.spi_id = stud_per_info.spi_id')
					->join('student_phone', 'student_phone.spi_id = stud_per_info.spi_id', 'left')
					->join('phone_numbers', 'phone_numbers.phone_id = student_phone.phone_id', 'left')
					->get('stud_sch_info', 3);
		return $query->result();
	}

	public function current_status($ssi_id){
		$sis_db = $this->load->database('sis_db', TRUE);
		$query = $sis_db->select('*')
					->where('year.sch_year', $this->sy)
					->where('year.semester', $this->sem)
					->where('year.ssi_id', $ssi_id)
					->get('year');
		return $query->row();
	}

	public function stud_address($spi_id){
		$sis_db = $this->load->database('sis_db', TRUE);
		$query  = $sis_db
					->select(['s_main_address.use_present_address', 'address.street', 'brgy.brgy_name', 'city.city_name'])
					->where('spi_id', $spi_id)
					// ->where('use_present_address', 'yes')
					->where('address_type', 'permanentAddress')
					->join('address', 'address.add_id = s_main_address.add_id')
					->join('brgy', 'address.brgy_id = brgy.brgy_id', 'left')
					->join('city', 'address.city_id = city.city_id', 'left')
					->get('s_main_address')
					->row();
		if($query){
			return $query;
		}
		return null;
	}

	public function other_payees($term){


		$query = $this->db
					->select('*')
					->like('payeeFirst', $term)
					->or_like('payeeLast', $term)
					->get('other_payee', 5);
		return $query->result();
	}

	public function enrollment_status($ssi_id){

		$sis_db = $this->load->database('sis_db', TRUE);

		$sis_db->select('*');
		$sis_db->where("ssi_id", $ssi_id);
		$sis_db->where("sch_year", $this->sy);
		$sis_db->where("semester", $this->sem);
		$sis_db->order_by('sch_year DESC, semester DESC');
		$query = $sis_db->get('student_enrollment_stat')->row();

		$status = "";
		if(!empty($query)){
			$status = $query->status;
		}
		else{
			$status = "not enrolled";
		}
		return $status;
	}

	public function enrollment_flow_status($ssi_id){

		$sis_db = $this->load->database('sis_db', TRUE);
		$sis_db->select('*');
		$sis_db->where("ssi_id", $ssi_id);
		$sis_db->where("sch_year", $this->sy);
		$sis_db->where("semester", $this->sem);
		$sis_db->where("step_number", 4);
		$sis_db->join('efs_classifications', 'efs_student_modes.efc_id = efs_classifications.efc_id');
		$sis_db->join('enrollment_flow_sources', 'efs_classifications.ef_id = enrollment_flow_sources.ef_id');
		$query = $sis_db->get('efs_student_modes')->row();

		$status = "";
		if($query){
			if($query->mode == 'done'){
				$status = "enrolled";
			}
			else{
				$status = "not enrolled";
			}
		}
		else{
			$status = "not enrolled";
		}
		return $status;
	}

	public function course($ssi_id){

		$sis_db = $this->load->database('sis_db', TRUE);

		$query = $sis_db
					->select('program_list.*')
					->where('ssi_id', $ssi_id)
					->order_by('sch_year DESC, semester DESC')
					->join('program_list', 'program_list.pl_id = stud_program.pl_id')
					->get('stud_program', 5);

		$course = $query->result() ? $query->result()[0]->prog_abv : 'ERROR';
		$course_type = $query->result() ? $query->result()[0]->level : 'ERROR';
		$year  = $this->course_year($ssi_id);

		return ["course" => $course, "year" => $year, "course_type" => $course_type];
	}

	public function course_year($ssi_id){

		$sis_db = $this->load->database('sis_db', TRUE);
		$query  = $sis_db
					->select('*')
					->order_by('sch_year DESC, semester DESC')
					->where('ssi_id', $ssi_id)
					->get("year", 15);

		return $query->result() ? $query->result()[0] : (object) ["year" => 'ERROR'];
	}

	public function breakdown_data($payee_type, $payee_id, $acct_no){
		if($payee_type == 'student'){
			$array = array(
				"regular_fees" => $this->regular_summary($payee_id),
				"special_payments" => $this->special_payments($payee_type, $payee_id),
				// "old_system" => $this->old_system_balance($payee_id) // remove and unused
				"old_system" => $this->db_ama_summary($acct_no) // new implementation
			);
		}
		else{
			$array = array(
				"regular_fees" => [],
				"special_payments" => $this->special_payments($payee_type, $payee_id),
				"old_system" => []
			);
		}

		return $array;
	}

	public function db_ama_summary($acct_no){
		if($acct_no){
			$db_ama = $this->load->database('db_ama', true);

			// OLD ASSESSMENT
			$assessment  = $db_ama
							->select('SUM(tbl_assessment_copy.amt) as fee_amt, tbl_assessment_copy.sy, tbl_assessment_copy.sem')
							->where('tbl_assessment_copy.acctno', $acct_no)
							->group_by([ 'tbl_assessment_copy.sy', 'tbl_assessment_copy.sem'])
							->get('tbl_assessment_copy')
							->result();

			$payments = $db_ama
							->select('payment.SY, payment.SEM, sum(payment.amt) as total_paid')
							->where('payment.acctno', $acct_no)
							->where('payment.`MODE`', 'cash')
							->group_by(['payment.SY', 'payment.SEM'])
							->get('payment')
							->result();

			$discounts = $this->old_system_discount($acct_no);
			$payment_summary = [];
			$ret = [];
			foreach ($payments as $key => $value) {
				$payment_summary[$value->SY . " - " . $value->SEM] = $value;
			}
			foreach ($assessment as $key => $value) {

				$discount = array_key_exists($value->sy . " - " . $value->sem, $discounts) ? $discounts[$value->sy . " - " . $value->sem] : 0;
				$old_system_paid = array_key_exists($value->sy . " - " . $value->sem, $payment_summary) ? round($payment_summary[$value->sy . " - " . $value->sem]->total_paid, 2) : 0; // paid in old system

				$remaining_old_assessment = round($value->fee_amt - $discount - $old_system_paid, 2); // old_assessment - discount - total paid in old system
				$tutorial = $this->old_system_tutorial($acct_no, $value->sy, $value->sem);
				$payment_new_system = $this->old_system_payments($acct_no, $value->sy, $value->sem); // payments in new system
				$bridging = $this->old_system_bridging($acct_no, $value->sy, $value->sem);
				// echo"TESTStart";
				// var_dump( $bridging['new_balance'] );
				// echo"TESTEnd<br>";
				$bridging_amt = 0;
				if($bridging){
					if($bridging['old_balance']->total_bridging){
						$bridging_amt = round($bridging['old_balance']->total_bridging, 2);
					}
				}
				$tuition_misc_assessment = $remaining_old_assessment - (int)$payment_new_system['total_misc_paid']; // OLD SYSTEM MISC + TUITION - PAYMENTS IN NEW SYSTEM
				$grand_assessment = round($remaining_old_assessment + ($tutorial==0?$tutorial:$tutorial['old_balance']) + $bridging_amt, 2); // remaining old assessment + tutorial + bridging || to be shown in the UI in column assessment
				$grand_remaining_balance = round((int)$grand_assessment - (int)$payment_new_system['total'], 2);
				if($grand_assessment > 0){
					$ret[$value->sy . " - " . $value->sem] = [
						'sy' => $value->sy,
						'sem' => $value->sem,
						'assessment' => round($value->fee_amt, 2),
						'discount' => $discount,
						'old_system_paid' => $old_system_paid,
						'tuition_misc_assessment' => $tuition_misc_assessment,
						'assessment_remaining' => $remaining_old_assessment,
						'tutorial' => $tutorial==0?$tutorial:$tutorial['old_balance'],
						'tutorial_new_system' => $tutorial==0?$tutorial:$tutorial['new_balance'],
						'bridging' => $bridging_amt, // old system
						'bridging_new_system' => $bridging && $bridging['new_balance'] ? $bridging['new_balance']->total_bridging : 0, // new system amount (old assessment - total paid in old system - total paid in new system)
						'grand_assessment' => $grand_assessment,
						'new_system_paid' => $payment_new_system['total'],
						'grand_remaining' => $grand_remaining_balance
					];
				}
				else{
					continue;
				}
			}
			return $ret;
		}
		return [];
	}

	public function old_system_tutorial($acct_no, $sy, $sem){
		$db_ama = $this->load->database('db_ama', true);

		$enrolled = $this->old_system_enrolled($acct_no, $sy, $sem);

		if(!$enrolled){
			return 0;
		}
		$course = $enrolled->course;
		$status = $enrolled->status;

		$noe_qry = $db_ama
					->select('tbl_schedule.no_of_enrollees,	tbl_schedule.Total_credit_unit')
					->where('tbl_stud_load.acctno', $acct_no)
					->where('tbl_stud_load.sem_load', $sem)
					->where('tbl_stud_load.yearLoad', $sy)
					->join('tbl_tutorial_subj', 'tbl_stud_load.Subject_code = tbl_tutorial_subj.Subject_code')
					->join('tbl_schedule',
								'tbl_stud_load.Subject_code = tbl_schedule.Subject_code AND
								 tbl_stud_load.sem_load = tbl_schedule.sem_sched AND
								 tbl_stud_load.yearLoad = tbl_schedule.year_sched')
					->group_by(['tbl_stud_load.Subject_code', 'tbl_stud_load.yearLoad', 'tbl_stud_load.sem_load'])
					->get('tbl_stud_load')
					->row();

		$tpu_qry = $db_ama
					 ->select('course.Unit')
					 ->where('course.course', $course)
					 ->where('course.status', $status)
					 ->where('course.sy', $sy)
					 ->where('course.sem', $sem)
					 ->get('course')
					 ->row();

		$tutorial_qry = $db_ama
					 	 ->select('Sum(tbl_tut_payment_details.amount) as tutorial_paid, tbl_tutorial_payment.sy, tbl_tutorial_payment.sem')
						 ->where('tbl_tutorial_payment.acctno', $acct_no)
						 ->where('tbl_tutorial_payment.sy', $sy)
						 ->where('tbl_tutorial_payment.sem', $sem)
						 ->join('tbl_tut_payment_details', 'tbl_tutorial_payment.tut_payment_ID = tbl_tut_payment_details.tut_payment_ID')
						 ->group_by(['tbl_tutorial_payment.sy', 'tbl_tutorial_payment.sem', 'tbl_tut_payment_details.Subject_code'])
						 ->get('tbl_tutorial_payment')
						 ->row();

		if(!$noe_qry || !$tpu_qry){
			return 0;
		}

		$rnoe = 15;
		$noe  = (int)$noe_qry->no_of_enrollees;
		$nou  = (int)$noe_qry->Total_credit_unit;
		$tpu  = (int)$tpu_qry->Unit;
		$tutorial_payment = $tutorial_qry ? (int)$tutorial_qry->tutorial_paid : 0;

		$balance = ($tpu*$nou*($rnoe-$noe))/$noe - $tutorial_payment;

		$new_sys_payments = $this->db
							->select('sum(paymentdetails.amt2) as total, payments.syId, payments.semId, sy.sy, sem.sem')
							->where('paymentdetails.oldParticular', 'tutorial')
							->where('sy.sy', $sy)
							->where('sem.sem', $sem)
							->join('payments', 'payments.paymentId = paymentdetails.paymentId')
							->join('sy', 'payments.syId = sy.syId')
							->join('sem', 'payments.semId = sem.semId')
							->group_by(['oldParticular'])
							->get('paymentdetails')->row();

		$old_bal = $balance > 0 ? $balance : 0;
		$new_bal = $new_sys_payments ? (int)$old_bal - (int)$new_sys_payments->total : $old_bal;

		return [
			"old_balance" => $old_bal,
			"new_balance" => $new_bal
		];
	}

	public function old_system_discount($acct_no){
		$db_ama = $this->load->database('db_ama', true);
		$total_discount = [];

		$discount = $db_ama
						->select('sum(tbl_discount2.amt) AS discount, tbl_discount2.sem, tbl_discount2.sy')
						->where('tbl_discount2.acctno', $acct_no)
						->group_by(['tbl_discount2.sy', 'tbl_discount2.sem'])
						->get('tbl_discount2')
						->result();

		if($discount){
			foreach ($discount as $key => $value) {
				$total_discount[$value->sy . " - "  . $value->sem] = round($value->discount, 2);
			}
		}
		return $total_discount;
	}

	public function old_system_enrolled($acct_no, $sy, $sem){
		$db_ama = $this->load->database('db_ama', true);
		$enrolled  = $db_ama
						->select('enrolled.course, enrolled.sem, enrolled.sy, enrolled.`status`')
						->where('enrolled.acctno', $acct_no)
						->where('enrolled.sy', $sy)
						->where('enrolled.sem', $sem)
						->order_by('enrolled.sy ASC, enrolled.sem ASC')
						->get('enrolled')
						->row();
		return $enrolled;
	}

	public function old_system_bridging($acct_no, $sy, $sem){
		$db_ama = $this->load->database('db_ama', true);

		$enrolled = $this->old_system_enrolled($acct_no, $sy, $sem);

		if($enrolled){
			$query = "	SELECT
							(Sum(tbl_schedule.Total_credit_unit)*(SELECT
							course.Unit
							From
							course
							Where
							course.sy = '{$sy}' AND
							course.sem = '{$sem}' AND
							course.course = '{$enrolled->course}' AND
							course.`status` = '{$enrolled->status}' LIMIT 1)) as total_bridging
						FROM
						tbl_stud_load
						INNER JOIN tbl_bridging_subj ON tbl_stud_load.Subject_code = tbl_bridging_subj.Subject_code AND tbl_stud_load.sem_load = tbl_bridging_subj.sem AND tbl_stud_load.yearLoad = tbl_bridging_subj.sy
						INNER JOIN tbl_schedule ON tbl_stud_load.Subject_code = tbl_schedule.Subject_code
						Where
							tbl_stud_load.sem_load = '{$sem}' AND
							tbl_stud_load.yearLoad = '{$sy}' AND
							tbl_stud_load.acctno = '{$acct_no}'";
			$bridging = $db_ama->query($query)->row();

			if($bridging){
				// CHECK PAYMENTS THEN DEDUCT IF ANY
				$bridging_payments = $db_ama->query('SELECT * FROM `tbl_bridging_payment` WHERE `sem` = "{$sem}" AND `sy` = "{$sy}" AND `acctno` = "{$acct_no}" GROUP BY acctno, sy, sem')->row();
				$bridging_payments_total = 0;
				if($bridging_payments){
					$bridging_payments_total = $bridging_payments->amount;
				}
				$bridging->total_bridging = $bridging->total_bridging - $bridging_payments_total;

				$bridging_new_system = $this->db
										->select('paymentdetails.oldParticular, SUM(paymentdetails.amt1) as total_paid, sy.sy, sem.sem')
										->where('oldParticular', 'bridging')
										->join('payments', 'payments.paymentId = paymentdetails.paymentId')
										->join('sy', 'payments.syId = sy.syId')
										->join('sem', 'payments.semId = sem.semId')
										->group_by(['oldParticular', 'sy.sy', 'sem.sem'])
										->get('paymentdetails')
										->row();
				$new_balance = $bridging_new_system ? $bridging_new_system->total_paid : $bridging;
				return [
					'old_balance' => $bridging,
					'new_balance' => $new_balance
				];
			}
		}
		return false;
	}

	public function old_system_old_payments($acct_no, $sy, $sem){ // payments made in the old system
		$db_ama = $this->load->database('db_ama', true);

		$payments = $db_ama->query("SELECT
							payment.SY,
							payment.SEM,
							sum(ordetails.PAmt) as amt
						FROM
							payment
						INNER JOIN ordetails ON payment.`OR` = ordetails.`OR` AND payment.acctno = ordetails.acctno
						WHERE
							payment.acctno = '{$acct_no}' AND
							payment.`MODE` = 'cash' AND
							payment.SEM = '{$sem}' AND
							payment.SY = '{$sy}'
						GROUP BY
							payment.SY,
							payment.SEM"
					)->row();
		if($payments){
			return $payments->amt;
		}
		return 0;
	}

	public function old_system_payments($acct_no, $sy, $sem){ // payments made in the new system

		$sem_sy = $this->sy_sem_id($sy, $sem);

		$payments = $this->db
						->where('acctno', $acct_no)
						->where('semId', $sem_sy['sem'])
						->where('syId', $sem_sy['sy'])
						->get('payments')
						->result();
		$total = 0;
		foreach ($payments as $key => $value) {
			$total = (int)$value->amt1 + (int) $total;
		}


		$misc = $this->db
					->select('SUM(paymentdetails.amt1) as misc_total')
					->join('paymentdetails', 'paymentdetails.paymentId = payments.paymentId')
					->where('payments.acctno', $acct_no)
					->where('payments.semId', $sem_sy['sem'])
					->where('payments.syId', $sem_sy['sy'])
					->where('paymentdetails.oldParticular != ', 'bridging')
					->where('paymentdetails.oldParticular != ', 'tutorial')
					->get('payments')
					->row();

		$total_misc_paid = $misc ? $misc->misc_total : 0;

		$array['total'] = $total;
		$array['total_misc_paid'] = $total_misc_paid;

		return $array;
	}

	public function old_system_pay($acct_no, $old_system_payments, $data){ // record payment from old system bills to the new system

		$to_pay = $data['to_pay'];

		$sy_sem_id = $this->sy_sem_id($old_system_payments[0]['sy'], $old_system_payments[0]['sem']);
		$payment_rows = array(
	        'ssi_id' => null,
	        'acctno' => $acct_no,
	        'orNo' => $data['or'],
	        'paymentDate' => $data['date'],
	        'amt1' => $data['to_pay'],
	        'amt2' => $data['to_pay'],
	        'paymentMode' => 'cash',
	        'cashier' => $this->username,
	        'semId' => $sy_sem_id['sem'], // this field will be updated after inserting all applicable particulars in payment details. It will be updated with the first sy/sem ID of first bills
	        'syId' => $sy_sem_id['sy'], // this field will be updated after inserting all applicable particulars in payment details. It will be updated with the first sy/sem ID of first bills
	        'printingType' => $data['printingType'],
		);
		$this->db->insert('payments', $payment_rows);
		$payment_id = $this->db->insert_id();
		$paid_particulars = [];

		foreach ($old_system_payments as $key => $value) {
			if($value['particular'] == 'tuition_misc'){
				$ospm = $this->old_system_pay_misc($acct_no, $value, $payment_id);
				$misc_total = 0;
				foreach ($ospm as $key => $value) {
					if($value['particular'] == 'tuition fee'){
						array_push($paid_particulars, $value);
					}
					else{
						$misc_total += $value['amount'];
					}
				}
				if($misc_total > 0){
					$mp = [
							'particular' => 'Miscellaneous',
							'amount' => $misc_total,
							'amount_oracle' => $misc_total,
							'feeType' => 'OLD SYSTEM'
					];
					array_push($paid_particulars, $mp);
				}
			}
			if($value['particular'] == 'bridging'){
				$ospb = $this->old_system_pay_bridging($acct_no, $value, $payment_id);
				array_push($paid_particulars, $ospb);
			}
			if($value['particular'] == 'tutorial'){
				$ospt = $this->old_system_pay_tutorial($acct_no, $value, $payment_id);
				array_push($paid_particulars, $ospt);
			}
		}
		return $paid_particulars;
	}

	public function old_system_pay_tutorial($acct_no, $data, $payment_id){
		$tutorial = $this->old_system_tutorial($acct_no, $data['sy'], $data['sem']);
		if($tutorial){
			if($tutorial['new_balance'] > 0){
				$rows = ['oldParticular' => 'tutorial', 'amt1' => $data['value'], 'amt2' => $data['value'], 'paymentId' => $payment_id];
				$this->db->insert('paymentdetails', $rows);

				$paid_particulars = [
						'particular' => 'tutorial',
						'amount' => $data['value'],
						'amount_oracle' => $data['value'],
						'feeType' => 'OLD SYSTEM'
				];
				return $paid_particulars;
			}
		}
		return false;
	}

	public function old_system_pay_bridging($acct_no, $data, $payment_id){
		$bridging = $this->old_system_bridging($acct_no, $data['sy'], $data['sem']);
		if($bridging){
			if($bridging['new_balance']){
				$rows = ['oldParticular' => 'bridging', 'amt1' => $data['value'], 'amt2' => $data['value'], 'paymentId' => $payment_id];
				$this->db->insert('paymentdetails', $rows);

				$paid_particulars = [
						'particular' => 'bridging',
						'amount' => $data['value'],
						'amount_oracle' => $data['value'],
						'feeType' => 'OLD SYSTEM'
				];
				return $paid_particulars;
			}
		}
		return false;
	}

	public function old_system_pay_misc($acct_no, $data, $payment_id){
		$db_ama = $this->load->database('db_ama', true);

		// total paid amount in old system
		$total_paid = $db_ama
						->select('payment.SY, payment.SEM, sum(payment.amt) as total_paid')
						->where('payment.acctno', $acct_no)
						->where('payment.`MODE`', 'cash')
						->where('payment.SY', $data['sy'])
						->where('payment.SEM', $data['sem'])
						->group_by(['payment.SY', 'payment.SEM'])
						->get('payment')
						->row();
		$discounts = $this->old_system_discount($acct_no);

		$tp = $total_paid ? $total_paid->total_paid : 0; // total paid amount in old system
		$dc = array_key_exists($data['sy'] . " - " . $data['sem'], $discounts) ? $discounts[$value->sy . " - " . $value->sem] : 0;
		$tp_dc = (int)$tp + (int)$dc; // old system total payments + discount;

		// retrieve old assessment
		$assessment  = $db_ama
						->select('tbl_assessment_copy.amt as fee_amt, tbl_assessment_copy.sy, tbl_assessment_copy.sem, tbl_assessment_copy.particular')
						->where('tbl_assessment_copy.acctno', $acct_no)
						->where('tbl_assessment_copy.sy', $data['sy'])
						->where('tbl_assessment_copy.sem', $data['sem'])
						->get('tbl_assessment_copy')
						->result();
		$to_be_checked = [];

		// arrange all particulars that can still be paid in the new system STORE IN $to_be_checked
		foreach ($assessment as $key => $value) {
			if($tp_dc){
				if($tp_dc > $value->fee_amt){
					$to_be_checked[] = $value;
					$tp_dc -= (int)$value->fee_amt;
				}
				else{
					$to_be_checked[] = $value;
					$tp_dc = 0;
				}
			}
			else{
				break;
			}

		}

		// check if remaining old particulars has already been paid in the new system
		$to_be_paid = [];
		foreach ($to_be_checked as $key => $value) {
			// paid particulars in the new system
			$paid_particular = $this->db
								->select('sy.sy, sem.sem, payments.acctno, paymentdetails.oldParticular, SUM(paymentdetails.amt1) as paid_amt')
								->join('payments', 'payments.paymentId = paymentdetails.paymentId')
								->join('sy', 'sy.syId = payments.syId')
								->join('sem', 'sem.semId = payments.semId')
								->where('sy.sy', $data['sy'])
								->where('sem.sem', $data['sem'])
								->where('paymentdetails.oldParticular', $value->particular)
								->where('paymentdetails.oldParticular IS NOT NULL', NULL)
								->group_by(['paymentdetails.oldParticular'])
								->get('paymentdetails')
								->row();
			$paid_amt = $paid_particular ? $paid_particular->paid_amt : 0;

			$value->remaining_balance = (int)$value->fee_amt - (int)$paid_amt;

			if($value->remaining_balance > 0){
				$to_be_paid[] = $value;
			}
		}

		$to_pay = (int)$data['value'];
		$paid_particulars = []; // FOR RECEIPT, BREAKDOWN OF PAYMENTS
		// start distribution to paymentdetails
		foreach ($to_be_paid as $key => $value) {
			if($to_pay){
				if($to_pay >= $value->remaining_balance){
					$paid_particulars[] = [
							'particular' => $value->particular,
							'amount' => $value->remaining_balance,
							'amount_oracle' => $value->remaining_balance,
							'feeType' => 'OLD SYSTEM'
					];
					$paymentdetail_rows = array(
						'oldParticular' => $value->particular,
						'amt1' => $value->remaining_balance,
						'amt2' => $value->remaining_balance,
						'paymentId' => $payment_id,
					);
					$this->db->insert('paymentdetails', $paymentdetail_rows);
					$to_pay -= (int)$value->remaining_balance;
				}
				else{
					$paid_particulars[] = [
							'particular' => $value->particular,
							'amount' => $to_pay,
							'amount_oracle' => $to_pay,
							'feeType' => 'OLD SYSTEM'
					];
					$paymentdetail_rows = array(
						'oldParticular' => $value->particular,
						'amt1' => $to_pay,
						'amt2' => $to_pay,
						'paymentId' => $payment_id,
					);
					$this->db->insert('paymentdetails', $paymentdetail_rows);
					$to_pay = 0;
				}
			}
			else{
				break;
			}
		}
		return $paid_particulars;
	}

	public function old_system_balance($ssi_id){ // REMOVED AND UNUSED
		$query = "	SELECT
						oa_particular_amount.aoAmountId,
						oa_particular_amount.oaAmount,
						oa_particular_amount.oaSem,
						oa_particular_amount.oaSy,
						oa_particular_amount.oaStudentId,
						oa_particular_amount.oaParticularId,
						oa_particular_amount.oaSem,
						oa_particular_amount.oaSy,
						oa_particular.oaParticularName,
						oa_student.ssi_id,
						oa_payment.oa_payment_id,
						oa_payment.paymentOrNum,
						oa_payment.paymentDate,
						oa_payment.printingType,
						oa_payment.paymentAmount,
						SUM(oa_payment_distribution.paidAmount) as `total_paid`

					FROM
						oa_particular_amount

					INNER JOIN oa_particular
					ON oa_particular_amount.oaParticularId = oa_particular.oaParticularId

					INNER JOIN oa_student
					ON oa_particular_amount.oaStudentId = oa_student.oaStudentId

					LEFT JOIN oa_payment_distribution
					ON oa_payment_distribution.aoAmountId = oa_particular_amount.aoAmountId

					LEFT JOIN oa_payment
					ON oa_payment.oa_payment_id = oa_payment_distribution.oa_payment_id

					WHERE
					oa_student.ssi_id = '{$ssi_id}'

					GROUP BY aoAmountId

					ORDER by
					oa_particular_amount.oaSy,
					oa_particular_amount.oaSem";

		$exec  = $this->db->query($query);
		$payments = [];

		$balance = $exec->result();
		$total_amount = 0;
		$total_paid   = 0;

		foreach ($balance as $key => $value) {
			$total_amount = floatval($total_amount) + floatval($value->oaAmount);
			$total_paid   = floatval($total_paid) + floatval($value->total_paid);
			if($value->oa_payment_id){
				if(array_key_exists($value->paymentOrNum, $payments)){
					$payments[$value->paymentOrNum]["payment_breakdown"][] = $value;
				}
				else{
					$payments[$value->paymentOrNum] = [
						"or" => $value->paymentOrNum,
						"date" => $value->paymentDate,
						"receipt" => $value->printingType,
						"amount" => $value->paymentAmount,
						"payment_breakdown" => [$value]
					];
				}
			}
		}

		$remaining_balance = floatval($total_amount) - floatval($total_paid);

		return [
			"particulars" 		=> $balance,
			"remaining_balance" => $remaining_balance,
			"total_amount"		=> $total_amount,
			"total_paid"		=> $total_paid,
			"payments"			=> $payments
		];
	}

	public function regular_summary($ssi_id){

		$total_discount = 0;
		$discount = $this->db
						->where('discount.ssi_id', $ssi_id)
						->select('SUM(amt1) as total_discount, SUM(amt2) as total_discount2, sy.sy, sem.sem')
						->join('sy', 'sy.syId = discount.syId')
						->join('sem', 'sem.semId = discount.semId')
						->group_by(['sy', 'sem'])
						->get('discount')
						->result();

		$select = ' assessment.assessmentId,
					assessment.ssi_id,
					assessment.feeType,
					sy.sy,
					sem.sem,
					assessment.particular,
					assessment.amt1 AS price1,
					assessment.amt2 AS price2,
					SUM(pd.amt1) AS paid1,
					SUM(pd.amt2) AS paid2';

		$where = array('assessment.ssi_id' => $ssi_id);

		$result = $this->db
					->where($where)
					->select($select)
					->join('sy', 'sy.syId = assessment.syId')
					->join('sem', 'sem.semId = assessment.semId')
					->join('paymentdetails pd', 'pd.assessmentId = assessment.assessmentId', 'LEFT')
					->join('payments', 'payments.paymentId = pd.paymentId', 'LEFT')
					->group_by(['assessmentId'])
					->order_by('sy, sem')
					->get('assessment')->result();

		$a = [];
		foreach ($result as $key => $value) {
			$value->discount = 0;
			$value->discount2 = 0;
			foreach ($discount as $d_key => $d_value) {
				if($value->sy == $d_value->sy && $value->sem == $d_value->sem){
					$value->discount = $d_value->total_discount;
					$value->discount2 = $d_value->total_discount2;
				}
			}
			$a[$value->sy . " " . $value->sem][] = $value;
		}
		$array = [];
		foreach ($a as $key => $value) {
			$sy = '';
			$sem = '';
			$assessment1 = 0.00;
			$assessment2 = 0.00;
			$discount = '';
			$discount2 = '';
			$paid1 = 0.00;
			$paid2 = 0.00;
			foreach ($value as $key1 => $value1) {
				$sy 			= 	$value1->sy;
				$sem 			=	$value1->sem;
				$assessment1 	+= 	floatval($value1->price1);
				$assessment2 	+= 	floatval($value1->price2);
				$discount     	= 	floatval($value1->discount);
				$discount2    	= 	floatval($value1->discount2);
				$paid1 		 	+= 	floatval($value1->paid1);
				$paid2 		 	+= 	floatval($value1->paid2);
			}

			$array[] = [
				'sy' => $sy,
				'sem' => $sem,
				'total_1' => $assessment1,
				'total_2' => $assessment2,
				'total_1_discounted' => $assessment1 - $discount, // minus tutorial and bridging
				'total_2_discounted' => $assessment2 - $discount2,// minus tutorial and bridging
				'discount' => $discount,
				'discount2' => $discount2,
				'paid1' => $paid1,
				'paid2' => $paid2,
				'remaining_balance_1' => ($assessment1 - $discount) - $paid1,
				'remaining_balance_2' => ($assessment2 - $discount2) - $paid2
			];
		}
		return $array;
	}

	public function special_payments($payee_type, $payee_id){
		$select = ' sy.sy,
					sem.sem,
					payments.paymentId,
					payments.orNo,
					particulars.particularName,
					pd.amt2 AS paid_amount,
					payments.amt2 AS or_amount';
		if($payee_type == 'student'){
			$where = array('payments.ssi_id' => $payee_id);
		}
		else{
			$where = array('payments.otherPayeeId' => $payee_id);
		}
		// if($payee_type == 'student'){
		// 	$where = array('payments.ssi_id' => $payee_id, 'particulars.syId'=>$this->semId, 'particulars.semId'=>$this->syId);
		// }
		// else{
		// 	$where = array('payments.otherPayeeId' => $payee_id, 'particulars.syId'=>$this->semId, 'particulars.semId'=>$this->syId);
		// }
		$result = $this->db
					->where($where)
					->select($select)
					->join('sy', 'sy.syId = particulars.syId')
					->join('sem', 'sem.semId = particulars.semId')
					->join('paymentdetails pd', 'pd.particularId = particulars.particularId')
					->join('payments', 'payments.paymentId = pd.paymentId')
					->get('particulars')->result();
		return $result;
	}

	public function payment_schedule($data){
		$sem  = $data['sem'];
		$sy   = $data['sy'];
		$type = 'regular';
		$period = $data['period'];
		$ssi_id = $data['ssi_id'];

		$total = $this->regular_summary($ssi_id);
		$row   = [];

		foreach ($total as $key => $value) {
			if( $value['sem'] == $sem && $value['sy'] == $sy ){
				$row = $value;
				break;
			}
		}
		$ret = [];
		$ret['is_current'] = false;
		$ret['is_empty'] = true;
		if($row){
			$ret['is_empty'] = false;
			$row_total = $row['total_2_discounted'];
			$paid2 = $row['paid2'];

			if($type == 'regular'){
				if( $this->sy == $sy && $this->sem == $sem ){
					$ret['is_current'] = true;
					$ret['sem'] = $this->sem;
					$ret['sy']  = $this->sy;
					$ret['total'] = $row_total;
					switch ($period) {
						case 'prelim':

							$ret['prelim'] = ($row_total * .45) - $paid2;

							if($ret['prelim'] < 0){
								$ret['prelim'] = 0;
							}

							$ret['midterm'] = (($row_total * .65) - $paid2 ) - $ret['prelim'];

							if($ret['midterm'] < 0){
								$ret['midterm'] = 0;
							}

							$ret['prefinal'] = ((($row_total * .85) - $paid2 ) - $ret['prelim']) - $ret['midterm'];

							if($ret['prefinal'] < 0){
								$ret['prefinal'] = 0;
							}

							$ret['final'] = ((($row_total - $paid2 ) - $ret['prelim']) - $ret['midterm']) - $ret['prefinal'];



							// $row_total = (double)$row_total - (double)$ret['prelim']);

							// $ret['midterm'] = ($row_total * .65);
							// $row_total = (double)$row_total - (double)$ret['midterm'];

							// $ret['prefinal'] = ($row_total * .85);
							// $row_total = (double)$row_total - (double)$ret['prefinal'];

							// $ret['final'] = $row_total;
							break;

						case 'midterm':

							$ret['prelim'] = 0;

							$ret['midterm'] = ($row_total * .65);
							$row_total = (double)$row_total - (double)$ret['midterm'];

							$ret['prefinal'] = ($row_total * .85);
							$row_total = (double)$row_total - (double)$ret['prefinal'];

							$ret['final'] = $row_total;
							break;

						case 'prefinal':

							$ret['prelim']  = 0;
							$ret['midterm'] = 0;

							$ret['prefinal'] = ($row_total * .85);
							$row_total = (double)$row_total - (double)$ret['prefinal'];

							$ret['final'] = $row_total;
							break;

						default:
							$ret['prelim']   = 0;
							$ret['midterm']  = 0;
							$ret['prefinal'] = 0;
							$ret['final'] = (double)$row_total;
							break;
					}

				}
				else{
					$ret['prelim'] = "";
					$ret['midterm'] = "";
					$ret['prefinal'] = "";
					$ret['final'] = "";
					$ret['total'] = (double)$row_total;
				}
			}
		}

		return $ret;
	}

	public function payment_schedule_percentage($type, $ssi_id){

		$query = "	SELECT
						fee_schedule.*, a.feeScheduleId, a.packageType,
						fee_schedule_type.feeScheduleType
					FROM
						fee_schedule,
						fee_schedule_type,
						(
							SELECT DISTINCT
								fee_package.packageId,
								fee_package.feeScheduleId,
								fee_package.packageType
							FROM
								student_bill
							INNER JOIN fee_amount ON student_bill.feeAmountId = fee_amount.feeAmountId
							INNER JOIN fee ON fee_amount.feeId = fee.feeId
							INNER JOIN fee_package ON fee.packageId = fee_package.packageId
							LEFT JOIN payment_distribution ON payment_distribution.studentBillId = student_bill.studentBillId
							WHERE
								student_bill.ssi_id = '{$ssi_id}' AND
								student_bill.billType = '{$type}' AND
								fee_package.packageType = '{$type}'
						) AS a
					WHERE
						fee_schedule.packageId = a.packageId
					AND a.feeScheduleId = fee_schedule_type.feeScheduleId";
		$exec  = $this->db->query($query);
		return $exec->result();
	}

	public function regular_summary_details($ssi_id, $sem, $sy, $type){

		$select = ' assessment.assessmentId,
					assessment.ssi_id,
					assessment.feeType,
					sy.sy,
					sem.sem,
					assessment.particular,
					assessment.amt1 as price1,
					assessment.amt2 as price2,
					pd.amt1 as paid1,
					pd.amt2 as paid2,
					payments.amt1 as or_paid1,
					payments.amt2 as or_paid2,
					payments.orNo,
					payments.paymentId,
					payments.printingType,
					payments.paymentStatus,
					payments.paymentDate';
		$where  = array('assessment.ssi_id' => $ssi_id, 'sem.sem' => $sem, 'sy.sy' => $sy);

		$bills = $this->db
					->where($where)
					->select($select)
					->join('sy', 'sy.syId = assessment.syId')
					->join('sem', 'sem.semId = assessment.semId')
					->join('paymentdetails pd', 'pd.assessmentId = assessment.assessmentId', "LEFT")
					->join('payments', 'payments.paymentId = pd.paymentId', "LEFT")
					->get('assessment')
					->result();

		$payments = [];
		$bridging_bills = [];
		$non_bridging_bills = [];
		foreach ($bills as $key => $value) {
			// for payments
			if($value->orNo){
				$payments[$value->orNo]['amount'] = $value->or_paid2;
				$payments[$value->orNo]['amount_oracle'] = $value->or_paid1;
				$payments[$value->orNo]['payment_date'] = $value->paymentDate;
				$payments[$value->orNo]['printing_type'] = $value->printingType;
				$payments[$value->orNo]['payment_status'] = $value->paymentStatus;
				$payments[$value->orNo]['paymentId'] = $value->paymentId;
				$payments[$value->orNo]['details'][] = $value;
			}
			if( strtolower($value->feeType) == 'bridging'){
				$value->particular = "&nbsp;&nbsp;&nbsp;&nbsp;" . $value->particular;
				$bridging_bills[] = $value;
			}
			if( strtolower($value->feeType) != 'bridging'){
				$non_bridging_bills[] = $value;
			}
		}
		$bridging_row = [];
		if(!empty($bridging_bills)){
			$bridging_row = [
				'particular' => '<b>Bridging</b>',
				'price2' => '',
				'paid2' => ''
			];
			array_push($bridging_bills, $bridging_row);
		}

		$final_bills = array_merge($non_bridging_bills, $bridging_bills);

		$a = [
			'bills' => $final_bills,
			'payments' => $payments
		];
		return $a;
	}

	public function process_payment_summary($sem, $sy, $type, $ssi_id){

		$selections = 'student_bill.ssi_id, student_bill.studentBillId, payment_distribution.paymentId, payment.paymentDate, payment.paymentOrNum, payment.paymentAmount, payment.paymentType, payment.printingType, fee_package.sem, fee_package.sy';
		$where = array('student_bill.ssi_id' => $ssi_id, 'fee_package.sy' => $sy, 'fee_package.sem' => $sem, 'student_bill.billType' => $type);

		$result = $this->db
					->select($selections)
					->where($where)
					->join('payment_distribution', 'payment_distribution.studentBillId = student_bill.studentBillId')
					->join('payment', 'payment_distribution.paymentId = payment.paymentId')
					->join('fee_amount', 'student_bill.feeAmountId = fee_amount.feeAmountId')
					->join('fee', 'fee_amount.feeId = fee.feeId')
					->join('fee_package', 'fee.packageId = fee_package.packageId')
					->group_by('paymentOrNum')
					->get('student_bill')
					->result();

		return $result;
	}

	public function cancel_payment($or, $action, $paymentId){

		$this->db->set('paymentStatus', $action);
		$this->db->set('amt1', 0);
		$this->db->set('amt2', 0);
		$this->db->where('orNo', $or);
		$this->db->where('paymentId', $paymentId);
		$this->db->update('payments');
		if($this->db->affected_rows() == 1){

			$this->db->set('amt1', 0);
			$this->db->set('amt2', 0);
			$this->db->where('paymentId', $paymentId);
			$this->db->update('paymentdetails');
			return true;
		}
		else{
			return false;
		}
	}

	public function edit_payment($data){

		$id = $data->post('id');
		$or = $data->post('or');
		$date 	  = $data->post('date');
		$receipt  = $data->post('receipt');
		$total = $data->post('total');
		$total_before = $data->post('total_before');
		// echo $id;
		// echo "<-id";
		$result = false;
		if($total == $total_before){
			$new_amt1 = array(
				'orNo' => $or,
				'paymentDate' => $date,
				'printingType' => $receipt,
			);
			$this->db->where('paymentId', $id);
			$result = $this->db->update('payments', $new_amt1);
		}else{
			try{
				$this->db->trans_start();

				$this->db->where('paymentId',$id)
				->delete('paymentdetails');
				$total_amt1 = 0;
				$total_amt2 = 0;
				$distribution_amount = $total;
				$select = ' assessment.assessmentId,
							assessment.ssi_id,
							assessment.particular,
							assessment.feeType,
							assessment.amt1 as price1,
							assessment.amt2 as price2,
							assessment.syId,
							assessment.semId,
							SUM(IFNULL(pd.amt1, 0)) as paid1,
							SUM(IFNULL(pd.amt2, 0)) as paid2,
							CAST(assessment.amt1 AS DECIMAL(9, 2)) - CAST(IFNULL(SUM(pd.amt1),0) AS DECIMAL(9, 2)) as remaining_balance1,
							CAST(assessment.amt2 AS DECIMAL(9, 2)) - CAST(IFNULL(SUM(pd.amt2),0) AS DECIMAL(9, 2)) as remaining_balance2';

				$result = $this->db
							->select($select)
							->where('(CAST(assessment.amt2 AS DECIMAL(9, 2)) - CAST(IFNULL(pd.amt2,0) AS DECIMAL(9, 2))) >' , 0)
							->where('payments.paymentId',$id)
							->where('assessment.feeType NOT IN ("Tutorial","Bridging")')
							->join('paymentdetails pd', 'pd.assessmentId = assessment.assessmentId', 'LEFT')
							->join('payments', 'payments.ssi_id = assessment.ssi_id')
							->group_by('assessment.assessmentId')
							->order_by('assessment.feeType', 'ASC')
							->get('assessment')->result();

					foreach ($result as $res_key => $res_value) {

						if($distribution_amount > 0){
							if( $distribution_amount >= $res_value->remaining_balance2 ){

								$paymentdetail_rows = array(
									'assessmentId' => $res_value->assessmentId,
									'amt1' => $res_value->remaining_balance1,
									'amt2' => $res_value->remaining_balance2,
									'paymentId' => $id,
								);
								$this->db->insert('paymentdetails', $paymentdetail_rows);
								$total_amt1 += (double)$res_value->remaining_balance1;
								$total_amt2 += (double)$res_value->remaining_balance2;


								$distribution_amount -= floatval($res_value->remaining_balance2);
								$particulars_tbp = [
									'particular' => $res_value->particular,
									'amount' => $res_value->remaining_balance2,
									'amount_oracle' => number_format($res_value->remaining_balance1, 2),
									'feeType' => $res_value->feeType
								];
								// array_push($paid_particulars, $particulars_tbp);
								continue;
							}
							if( $distribution_amount < $res_value->remaining_balance2 ){
								// get percentage then amount for amount1
								$percentage1 = ($distribution_amount / $res_value->price2);
								$amt1_val   = $res_value->price1 * $percentage1;

								$paymentdetail_rows = array(
									'assessmentId' => $res_value->assessmentId,
									'amt1' => $amt1_val,
									'amt2' => $distribution_amount,
									'paymentId' => $id,
								);

								$this->db->insert('paymentdetails', $paymentdetail_rows);
								$total_amt1 += (double)$amt1_val;
								$total_amt2 += (double)$distribution_amount;


								$particulars_tbp = [
									'particular' => $res_value->particular,
									'amount' => $distribution_amount,
									'amount_oracle' => number_format($amt1_val, 2),
									'feeType' => $res_value->feeType
								];
								// array_push($paid_particulars, $particulars_tbp);
								$distribution_amount = 0;
								break;
							}

						}
						else{
							break;
						}
					}

				// return $this->db->get('paymentdetails');
				$new_amt1 = array(
					'orNo' => $or,
					'paymentDate' => $date,
					'printingType' => $receipt,
					'amt1'  => $total_amt1,
					'amt2'  => $total,
				);
				$this->db->where('paymentId', $id);
				$this->db->update('payments', $new_amt1);
				$result = $this->db->trans_complete();
			}catch(Exception $e){
				return $e;
			}
		}
		return $result;
	}

	public function regular_payment($data){

		$or = $data->post('or');
		$payments = $data->post('payments');
		$fee_type = $data->post('fee_type');
		$to_pay   = $data->post('to_pay');
		$receipt  = $data->post('receipt');
		$date 	  = $data->post('date');
		$ssi_id   = $data->post('ssi_id');
		$acct_no   = $data->post('acct_no');
		$course   = $data->post('course');
		$current_status = $data->post('current_status');
		$old_system_payments = [];
		$paid_particulars = []; // all particulars to be paid needed for printing the official receipt

		if(count($this->db->where(['orNo'=>$or])->get('payments')->result())>0){
			return 'or_used';
		}

		if( count( $this->db->where(['orNo'=>$or])->get('payments')->result() ) > 0 ){
			return 'or_used';
		}

		// CHECK IF PAYMENT INCLUDES DOWNPAYMENT
		foreach ($payments as $key => $value) {
			if($value['sy'] == 'DownPayment'){
				$dp_val = $value['value'];
				unset($payments[$key]);
				array_push($payments, ['sy' => $this->sy, 'sem' => $this->sem, 'value' => $dp_val]);
				$bills = $this->set_bills($ssi_id, $course);
			}
			if(isset($value['type'])){
				if($value['type'] == "old_system"){
					// unset($payments[$key]);
					$old_system_payments[] = $value;
				}
			}
		}

		// FOR OLD SYSTEM PAYMENTS
		$osp_data = [
			"ssi_id" => $ssi_id,
			"or" => $or,
			"to_pay" => $to_pay,
			"printingType" => $receipt,
			"payment_mode" => "cash",
			"date" => $date
		];
		if($old_system_payments){
			$os_payments = $this->old_system_pay($acct_no, $old_system_payments, $osp_data);
			return $os_payments;
		}

		$roll_back_items = []; // roll back changes in the database if something went wrong

		$payment_rows = array(
		        'ssi_id' => $ssi_id,
		        'orNo' => $or,
		        'paymentDate' => $date,
		        'amt1' => '',
		        'amt2' => '',
		        'paymentMode' => 'cash',
		        'cashier' => $this->username,
		        'semId' => null, // this field will be updated after inserting all applicable particulars in payment details. It will be updated with the first sy/sem ID of first bills
		        'syId' => null, // this field will be updated after inserting all applicable particulars in payment details. It will be updated with the first sy/sem ID of first bills
		        'printingType' => $receipt,
		);
		$this->db->insert('payments', $payment_rows);
		$payment_id = $this->db->insert_id();

		$roll_back_items['payments'][] = $payment_id;

		$unpaid_particulars = []; // particulars having remaining balance more than 0

		$total_amt1 = 0; // to be filled after inserting all rows in paymentDetails
		$total_amt2 = 0; // to be filled after inserting all rows in paymentDetails
		$payment_syId = "";
		$payment_semId = "";

		foreach ($payments as $key => $value) {

			$distribution_amount = floatval($value['value']);
			$select = ' assessment.assessmentId,
						assessment.ssi_id,
						sy.sy,
						sem.sem,
						assessment.particular,
						assessment.feeType,
						assessment.amt1 as price1,
						assessment.amt2 as price2,
						assessment.syId,
						assessment.semId,
						SUM(IFNULL(pd.amt1, 0)) as paid1,
						SUM(IFNULL(pd.amt2, 0)) as paid2,
						CAST(assessment.amt1 AS DECIMAL(9, 2)) - CAST(IFNULL(SUM(pd.amt1),0) AS DECIMAL(9, 2)) as remaining_balance1,
						CAST(assessment.amt2 AS DECIMAL(9, 2)) - CAST(IFNULL(SUM(pd.amt2),0) AS DECIMAL(9, 2)) as remaining_balance2';

			$result = $this->db
						->select($select)
						->where('assessment.ssi_id', $ssi_id)
						->where('(CAST(assessment.amt2 AS DECIMAL(9, 2)) - CAST(IFNULL(pd.amt2,0) AS DECIMAL(9, 2))) >' , 0)
						->where('sy.sy', $value['sy'])
						->where('sem.sem', $value['sem'])
						// ->where('assessment.feeType NOT IN ("Tutorial","Bridging")')
						->join('sy', 'sy.syId = assessment.syId')
						->join('sem', 'sem.semId = assessment.semId')
						->join('paymentdetails pd', 'pd.assessmentId = assessment.assessmentId', 'LEFT')
						->group_by('assessment.assessmentId')
						->order_by('assessment.priority', 'ASC')
						->get('assessment')->result();

			try {
				foreach ($result as $res_key => $res_value) {
					$payment_syId = $res_value->syId;
					$payment_semId = $res_value->semId;

					if($distribution_amount > 0){
						if( $distribution_amount >= $res_value->remaining_balance2 ){

							$paymentdetail_rows = array(
								'assessmentId' => $res_value->assessmentId,
								'amt1' => $res_value->remaining_balance1,
								'amt2' => $res_value->remaining_balance2,
								'paymentId' => $payment_id,
							);
							$this->db->insert('paymentdetails', $paymentdetail_rows);
							$total_amt1 += (double)$res_value->remaining_balance1;
							$total_amt2 += (double)$res_value->remaining_balance2;

							// item to be rolled back if ever
							$pd_id = $this->db->insert_id();
							$roll_back_items['paymentdetails'][] = $pd_id;

							$distribution_amount -= floatval($res_value->remaining_balance2);
							$particulars_tbp = [
								'particular' => $res_value->particular,
								'amount' => $res_value->remaining_balance2,
								'amount_oracle' => number_format($res_value->remaining_balance1, 2),
								'feeType' => $res_value->feeType
							];
							array_push($paid_particulars, $particulars_tbp);
							continue;
						}
						if( $distribution_amount < $res_value->remaining_balance2 ){
							// get percentage then amount for amount1
							$percentage1 = ($distribution_amount / $res_value->price2);
							$amt1_val   = $res_value->price1 * $percentage1;

							$paymentdetail_rows = array(
								'assessmentId' => $res_value->assessmentId,
								'amt1' => $amt1_val,
								'amt2' => $distribution_amount,
								'paymentId' => $payment_id,
							);

							$this->db->insert('paymentdetails', $paymentdetail_rows);
							$total_amt1 += (double)$amt1_val;
							$total_amt2 += (double)$distribution_amount;

							// item to be rolled back if ever
							$pd_id = $this->db->insert_id();
							$roll_back_items['paymentdetails'][] = $pd_id;

							$particulars_tbp = [
								'particular' => $res_value->particular,
								'amount' => $distribution_amount,
								'amount_oracle' => number_format($amt1_val, 2),
								'feeType' => $res_value->feeType
							];
							array_push($paid_particulars, $particulars_tbp);
							$distribution_amount = 0;
							break;
						}

					}
					else{
						break;
					}
				}
			}
			catch (Exception $e) {

				foreach (array_reverse($roll_back_items) as $key => $value) {
					$id = $key == 'payments' ? 'paymentId' : 'paymentDetailsId';
					foreach ($value as $key1 => $value1) {
						$this->db->where($id, $value1);
						$this->db->delete($key);
					}
				}
				break;
				return false;
			}
		}

		$new_amt1 = array(
	        'amt1'  => $total_amt1,
	        'amt2'  => $to_pay,
	        'syId'  => $payment_syId,
			'semId' => $payment_semId
		);

		$this->db->where('paymentId', $payment_id);
		$this->db->update('payments', $new_amt1);

		// update OR
		$this->receipt_served($or, $receipt);

		// return $paid_particulars;
		//Use Reprint Code cause some particulars cant be found in reciept
		$selectss = [

					'assessment.particular AS particular',
					'paymentdetails.amt2 AS amount',
					'paymentdetails.amt1 AS amount_oracle',
					'assessment.feeType AS feeType'
				];

		$datass = $this->db
					->select($selectss)
					->join('paymentdetails', 'payments.paymentId = paymentdetails.paymentId')
					->join('assessment', 'paymentdetails.assessmentId = assessment.assessmentId')
					->where('payments.orNo', $or)
					->get('payments');

		return $datass->result();
	}

	public function set_bills($ssi_id, $course){

		$sis_db = $this->load->database('sis_db', TRUE);
		$course = $course ? explode(' ', $course)[0] : '';
		$ct_qry = $this->db->select('courseType')
					->where('particularName', $course)
					->where('semId', $this->semId)
					->where('syId', $this->syId)
					->get('particulars')->row();
		$course_type = $ct_qry ? $ct_qry->courseType : null;

		$year = $sis_db
					->where('ssi_id', $ssi_id)
					->where('sch_year', $this->sy)
					->where('semester', $this->sem)
					->get('year')
					->row();

		if($year){ // if $year is empty, it means ssi_id is not enrolled during the current SY/SEM
			// particulars to bill
			$particulars = $this->db
				->where('syId', $this->syId)
				->where('semId', $this->semId)
				->where('feeType', 'Miscellaneous')
				->where('courseType', $course_type)
				->where('studentStatus', ($year->current_stat=='New' ? 'New':'Old'))
				->order_by('priority')
				->get('particulars')->result();

			if($particulars){
				foreach ($particulars as $key => $value) {
					$assessment_rows = array(
				        'ssi_id' => $ssi_id,
				        'particular' => $value->particularName,
				        'amt1' => $value->amt1,
				        'amt2' => $value->amt2,
				        'feeType' => 'Miscellaneous',
				        'semId' => $this->semId,
				        'syId' => $this->syId,
				        'collectionReportGroup' => $value->collectionReportGroup,
				        'priority' => $value->priority
					);
					$this->db->insert('assessment', $assessment_rows);
				}
				return true;
			}
			else{
				return 'NO PARTICULARS'; // no particulars in current SY | SEM
			}
		}
		else{
			return 'NOT ENROLLED'; // not enrolled
		}
	}

	public function other_payment($data){
		$to_pay = $data['to_pay'];
		$or = $data['or'];
		$receipt = $data['receipt'];
		$date = $data['date'];
		$payee_type = $data['payee_type'];
		$ssi_id = $data['ssi_id'];
		$other_payee_id = $data['other_payee_id'];

		$data = $data['data'];

		$payment_rows = array(
		        'ssi_id' => $ssi_id,
		        'orNo' => $or,
		        'paymentDate' => $date,
		        'amt1' => $to_pay,
		        'amt2' => $to_pay,
		        'paymentMode' => 'cash',
		        'cashier' => $this->username,
		        'semId' => $this->semId,
		        'syId' => $this->syId,
		        'printingType' => $receipt,
		        'otherPayeeId' => $other_payee_id
		);
		$this->db->insert('payments', $payment_rows);
		$payment_id = $this->db->insert_id();

		foreach ($data as $key => $value) {
			$a = [
				'assessmentId' => null,
				'particularId' => $value['id'],
				'amt1' => $value['subtotal'],
				'amt2' => $value['subtotal'],
				'paymentId' => $payment_id
			];
			$this->db->insert('paymentdetails', $a);
		}
		// update OR
		$this->receipt_served($or, $receipt);
		return $data;
	}

	public function downpayment_bills($data){
		$sis_db = $this->load->database('sis_db', TRUE);

		$course = $data['course'] ? explode(' ', $data['course'])[0] : '';
		$ct_qry = $this->db->select('courseType')
					->where('particularName', $course)
					->where('semId', $this->semId)
					->where('syId', $this->syId)
					->get('particulars')
					->row();
		$course_type = $ct_qry ? $ct_qry->courseType : null;

		$year = $sis_db
					->where('ssi_id', $data['ssi_id'])
					->where('sch_year', $this->sy)
					->where('semester', $this->sem)
					->get('year')
					->row();

		if($year){ // if enrolled, check in database: sis_db , table: year
			if($course_type){ // Check if course type has bills

				$particulars = $this->db
						->where('syId', $this->syId)
						->where('semId', $this->semId)
						->where('feeType', 'Miscellaneous')
						->where('courseType', $course_type)
						->where('studentStatus', ($year->current_stat == 'New' ? 'New':'Old'))
						// ->where('studentStatus', $year->current_stat)
						->get('particulars')->result();

				if($particulars){
					return true;
				}
				else{
					return "No available particulars in ". $this->sy . " - " . $this->sem. ".Please contact assessment support.";
				}
			}
			else{
				return "No bills for the payee's course type. Please contact assessment support.";
			}

		}
		else{
			return "Payee not yet enrolled in " . $this->sy . " - " . $this->sem;
		}
	}

	public function sy_sem_id($sy = "", $sem = ""){

		$d_sy = $sy ? $sy : $this->sy;
		$d_sem = $sem ? $sem : $this->sem;

		$syId = $this->db
					->select('syId')
					->where('sy', $d_sy)
					->limit(1)
					->get('sy')
					->result();
		$semId = $this->db
					->select('semId')
					->where('sem', $d_sem)
					->limit(1)
					->get('sem')
					->result();
		$a = [
			'sy' => $syId[0]->syId ? $syId[0]->syId : '',
			'sem' => $semId[0]->semId ? $semId[0]->semId : ''
		];
		return $a;
	}

	public function or_serving($receipt = ''){

		$or = $this->db->where('type', strtolower($receipt))->get('or_served')->last_row();
		if($or){
			// add new or
			if($or->status == 'done'){
				$new_or = $this->db->insert('or_served', ['or' => (int)$or->or + 1, 'status' => 'open', 'type' => strtolower($receipt)]);
				$or_serving = (int)$or->or + 1;
			}
			else{
				$or_serving = $or->or;
			}
		}
		else{
			$or_serving = '';
		}

		return $or_serving;
	}

	public function receipt_served($or, $receipt){
		$this->db->where('`or`', $or);
		$this->db->where('type', $receipt);
		$this->db->update('or_served', ['status' => 'done']);
	}

	public function search_otherp_paid($data){
		$select = [
					'paymentdetails.particularId',
					'payments.orNo',
					'GROUP_CONCAT(payments.orNo) as orNo',
					'particulars.particularName',
					'particulars.amt2 AS price_2',
					'SUM(paymentdetails.amt2) AS paid_amt_2',
					'(particulars.amt2 - SUM(paymentdetails.amt2)) as remaining_balance'
				];

		$data = $this->db
					->select($select)
					->join('payments', 'paymentdetails.paymentId = payments.paymentId')
					->join('particulars', 'particulars.particularId = paymentdetails.particularId')
					->like('particulars.particularName', $data->get('particular'))
					->where('payments.ssi_id', $data->get('ssi_id'))
					->group_by('paymentdetails.particularId')
					->get('paymentdetails');

		return $data->result();
	}

	public function get_regular_payment_details($orNo){
		$select = [

					'assessment.particular AS particular',
					'SUM(paymentdetails.amt2) AS amount',
					'SUM(paymentdetails.amt1) AS amount_oracle',
					'assessment.feeType AS feeType'
				];

		$data = $this->db
					->select($select)
					->join('paymentdetails', 'payments.paymentId = paymentdetails.paymentId')
					->join('assessment', 'paymentdetails.assessmentId = assessment.assessmentId')
					->where('payments.orNo', $orNo)
					->group_by('feeType')
					->get('payments');

		return $data->result();
	}

	public function getInfoForEdit($or){
		$select = [

					'payments.paymentId AS paymentId',
					'payments.printingType',
					'payments.paymentDate',
					'paymentdetails.paymentDetailsId AS paymentDetailsId',
					'assessment.assessmentId AS assessmentId',
					'assessment.particular AS particular',
					'paymentdetails.amt2 AS detail_paid_amount',
					'paymentdetails.amt1 AS detail_paid_amount_oracle',
					'assessment.amt2 AS assessment_amount',
					'assessment.amt1 AS assessment_amount_oracle',
					'payments.amt1 as total_paid_oracle',
					'payments.amt2 as total_paid',
				];

		$data = $this->db
					->select($select)
					->join('paymentdetails', 'payments.paymentId = paymentdetails.paymentId')
					->join('assessment', 'paymentdetails.assessmentId = assessment.assessmentId')
					->where('payments.orNo', $or)
					->get('payments');

		return $data->result();
	}

	public function test($acct_no = "05-2-01635", $sy = '2019-2020', $sem ='1st'){
		// $a = $this->old_system_tutorial($acct_no, $sy, $sem);
		// $a = $this->old_system_tutorial($acct_no, $sy, $sem);

		$old_system_payments = Array
		(
		    ['sy' => '2019-2020',
		    'sem' => '1st',
		    'value' => '7757',
		    'type' => 'old_system',
		    'particular' => 'tuition_misc'],
		    // ['sy' => '2019-2020',
		    // 'sem' => '1st',
		    // 'value' => '2',
		    // 'type' => 'old_system',
		    // 'particular' => 'bridging'],
		    ['sy' => '2019-2020',
		    'sem' => '1st',
		    'value' => '600',
		    'type' => 'old_system',
		    'particular' => 'tutorial'],

		);
		$data = Array
		(
		    'ssi_id' => '',
		    'or' => '1040',
		    'to_pay' => '8357',
		    'printingType' => 'OR',
		    'payment_mode' => 'cash',
		    'date' => '2020-02-05',
		);
		$a = $this->old_system_pay($acct_no, $old_system_payments, $data);
	}

	public function testt($spi_id = '9304'){

		$a = $this->db_ama_summary("05-2-01635");
		echo "<pre>";
		print_r($a);
	}

}

?>
