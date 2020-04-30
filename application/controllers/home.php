<?php

/**
 * 
 */
class Home extends CI_Controller
{
	
	public function __construct(){
		parent::__construct();
		$this->load->library('session');
		$this->load->helper('url');

		if( !$this->session->has_userdata('user_data') ){
			redirect('/');
		}
	}

	public function index(){
		$this->load->view('layouts/header');
		$this->load->view('pages/index');
		$this->load->view('pages/payments_script');
		$this->load->view('layouts/footer');
	}

	public function student_list(){
		
		$term = $this->input->get('term');
		$fee_type   = $this->input->get('fee_type');
		$home_model = $this->load->model('Home_model');
		$stud_list  = $this->Home_model->student_list($term);
		$other_payee = $this->Home_model->other_payees($term);

		foreach ($stud_list as $key => $value) {

			$fullname = ucwords($value->lname . ", " . $value->fname);
			$course   = $this->course($value->ssi_id);
			$current_status = $this->Home_model->current_status($value->ssi_id);

		    $arr[] = [
		        "value"   => $fullname,
				"label"   => $fullname,
				"stud_id" => $value->stud_id,
				"ssi_id"  => $value->ssi_id,
				"spi_id"  => $value->spi_id,
				"usn_no" => $value->usn_no,
				"acct_no" => $value->acct_no,
				"phone_number" => $value->phone_number,
				'address' => $this->Home_model->stud_address($value->spi_id),
				// "enrollment_status" => ucwords($this->check_enrollment_status($value->ssi_id)),
				"enrollment_status" => ucwords($this->enrollment_flow_status($value->ssi_id)),
				"course"  => $course['course'] . " " . $course['year']->year,
				"course_type" => $course['course_type'],
				"type"    => 'student',
				"data"    => $this->Home_model->breakdown_data("student", $value->ssi_id, $value->acct_no),
				"current_status" =>  $current_status ? strtoupper($current_status->current_stat) : null,
				"other_payee_id" => ""
		    ];
		}

		foreach ($other_payee as $key => $value) {
			$fullname = ucwords($value->payeeLast . ", " . $value->payeeFirst);
		    $arr[] = [
		        "value"   => $fullname,
				"label"   => $fullname . " (non-student)",
				"stud_id" => '',
				"ssi_id"  => '',
				"spi_id"  => '',
				"usn_no" => '',
				"acct_no" => '',
				"phone_number" => '',
				'address' => '',
				"enrollment_status" => '',
				"course_type" => '',
				"course"  => '', 
				"type"    => 'non-student',
				"data"    => $this->Home_model->breakdown_data("non-student", $value->otherPayeeId, false),
				"current_status" => '',
				"other_payee_id" => $value->otherPayeeId
		    ];
		}
	    echo json_encode($arr); // if $arr is empty, there must be something wrong with the array $stud_list or $other_payee
	}

	public function check_enrollment_status($ssi_id){
		$home_model = $this->load->model('Home_model');
		$enrollment_status = $this->Home_model->enrollment_status($ssi_id);
		return $enrollment_status;
	}

	public function enrollment_flow_status($ssi_id){
		$home_model = $this->load->model('Home_model');
		$enrollment_flow_status = $this->Home_model->enrollment_flow_status($ssi_id);
		return $enrollment_flow_status;
	}

	public function course($ssi_id){
		$home_model = $this->load->model('Home_model');
		$course = $this->Home_model->course($ssi_id);
		return $course;	
	}

	public function payment_schedule(){

		$ps = $this->input->get();
		$home_model = $this->load->model('Home_model');
		$payment_schedule = $this->Home_model->payment_schedule($ps);

		echo json_encode($payment_schedule);
	}

	public function view_summary_details(){

		$sem = $this->input->get('sem');
		$sy = $this->input->get('sy');
		$type = $this->input->get('type');
		$ssi_id = $this->input->get('ssi_id');

		$home_model = $this->load->model('Home_model');
		$rsd = $this->Home_model->regular_summary_details($ssi_id, $sem, $sy, $type);

		$data = [
			'bills' => $rsd['bills'],
			'payments' => $rsd['payments']
		];

		echo json_encode($data);
	}

	public function cancel_payment(){
		$paymentId = $this->input->post('paymentId');
		$or = $this->input->post('or');
		$action = $this->input->post('action');
		$home_model = $this->load->model('Home_model');
		$cancel = $this->Home_model->cancel_payment($or, $action, $paymentId);
		echo json_encode($cancel);
	}

	public function or_serving(){
		$home_model = $this->load->model('Home_model');
		$or_serving  = $this->Home_model->or_serving($this->input->get('receipt'));
		echo json_encode($or_serving);
	}

	public function submit_payment(){
		$home_model = $this->load->model('Home_model');
		$data = $this->input;
		if($data->post('fee_type') == 'regular'){
			$res = $this->Home_model->regular_payment($data);
		}
		else{
			$res = $this->Home_model->other_payment($data->post());
		}
		echo json_encode($res);
	}

	public function downpayment_bills(){
		$home_model = $this->load->model('Home_model');
		$db = $this->Home_model->downpayment_bills($_REQUEST);
		echo json_encode($db);
	}

	public function test(){
		$home_model = $this->load->model('Home_model');
		$payment_schedule  = $this->Home_model->test();
	}

	public function testt(){
		$home_model = $this->load->model('Home_model');
		$payment_schedule = $this->Home_model->testt();
	}

	public function receipt(){
		$this->load->view('receipt');
	}

	public function receipt_acknowledgement(){
		$this->load->view('acknowledgement_receipt');
	}

	public function search_otherp_paid(){
		$home_model = $this->load->model('Home_model');
		$sop = $this->Home_model->search_otherp_paid($this->input);
		echo json_encode($sop);
	}

	public function print_or(){
		$home_model = $this->load->model('Home_model');
		$grpd = $this->Home_model->get_regular_payment_details($this->input->post('or'));
		// echo $this->input->post('or');
		echo json_encode($grpd);
	}

}

?>