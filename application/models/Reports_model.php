<?php

class Reports_model extends CI_Model	
{

	public function __construct(){
		parent::__construct();
		$this->load->database();
	}

	public function generate_monthly_report($data){
		$month_year = explode('-', $data->post('month'));

		$select = ['payments.paymentDate', 'payments.orNo', 'payments.amt1', 'payments.ssi_id', 'payments.paymentId', 'payments.acctno'];
		$payments = $this->db
						->select($select)
						->where('payments.acctno IS NULL', NULL)
						->where('MONTH(payments.paymentDate)', $month_year[1])
						->where('YEAR(payments.paymentDate)', $month_year[0])
						->where('payments.printingType', 'OR')
						->get('payments')->result();
		$array = [];
		$this->old_system_report($month_year);
		foreach ($payments as $key => $value) {
			$payee = $this->get_payee_detail($value->ssi_id, $value->acctno);
			$or_particulars = $this->or_particulars($value->paymentId);

			$merchandise_amt = 0;
			$others_amt = 0;
			$unifast_amt = 0;
			$special_amt = 0;
			$scnl_amt = 0;
			$netR_amt = 0;

			$elearning_amt = 0;
			$nccUk_amt = 0;
			$msFee_amt = 0;
			$oracle_amt = 0;
			$hp_amt = 0;

			$studentServices_amt = 0;
			$sap_amt = 0;
			$stcab_amt = 0;
			$insurance_amt = 0;
			$office365_amt = 0;
			$shs_amt = 0;

			foreach ($or_particulars['or_details'] as $or_key => $or_val) {
				if(strtolower($or_val->collectionReportGroup) == 'merchandise'){
					$merchandise_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'others'){
					$others_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'unifast'){
					$unifast_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'specialexam'){
					$special_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'scnl'){
					$scnl_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'netr'){
					$netR_amt += $or_val->amt1;
				}

				if(strtolower($or_val->collectionReportGroup) == 'elearning'){
					$elearning_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'nccuk'){
					$nccUk_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'msfee'){
					$msFee_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'oracle'){
					$oracle_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'hp'){
					$hp_amt += $or_val->amt1;
				}

				if(strtolower($or_val->collectionReportGroup) == 'studentservices'){
					$studentServices_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'sap'){
					$sap_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'stcab'){
					$stcab_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'insurance'){
					$insurance_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'office365'){
					$office365_amt += $or_val->amt1;
				}
				if(strtolower($or_val->collectionReportGroup) == 'shs'){
					$shs_amt += $or_val->amt1;
				}
			}
			$a = [
				'date' => $value->paymentDate,
				'or' => $value->orNo,
				'name' => $payee->lname . ", " . $payee->fname,
				'particular' => $or_particulars['particulars'],
				'grossReceipt' => $value->amt1,
				'merchandise' => $merchandise_amt ? $merchandise_amt : '-',
				'others' => $others_amt ? $others_amt : '-',
				'unifast' => $unifast_amt ? $unifast_amt : '-',
				'specialExam' => $special_amt ? $special_amt : '-',
				'scnl' => $scnl_amt ? $scnl_amt : '-',
				'elearning' => $elearning_amt ? $elearning_amt : '-',
				'nccUk' => $nccUk_amt ? $nccUk_amt : '-',
				'msFee' => $msFee_amt ? $msFee_amt : '-',
				'oracle' => $oracle_amt ? $oracle_amt : '-',
				'hp' => $hp_amt ? $hp_amt : '-',
				'studentServices' => $studentServices_amt ? $studentServices_amt : '-',
				'sap' => $sap_amt ? $sap_amt : '-',
				'stcab' => $stcab_amt ? $stcab_amt : '-',
				// 'culturalFee' => $culturalFee_amt ? $culturalFee_amt : '-',
				'insurance' => $insurance_amt ? $insurance_amt : '-',
				'office365' => $office365_amt ? $office365_amt : '-',
				'shs' => $shs_amt ? $shs_amt : '-',
				'netR' => $netR_amt ? $netR_amt : '-'
			];
			array_push($array, $a);
		}
		if($array){
			$this->db->insert_batch('collectionreport', $array); 
		}
		else{
			return false;
		}
		return true;
	}

	public function adjusted_monthly_report($data){
		
		$this->test();
	}

	public function get_payee_detail($ssi_id, $acct_no){
		
		$sis_db = $this->load->database('sis_db', TRUE);
		if($ssi_id){
			$query  = $sis_db
						->where('ssi_id', $ssi_id)
						->join('stud_per_info', 'stud_sch_info.spi_id = stud_per_info.spi_id')
						->get('stud_sch_info')->row();
		}
		else{

			$query  = $sis_db
						->where('stud_sch_info.acct_no', $acct_no)
						->join('stud_per_info', 'stud_sch_info.spi_id = stud_per_info.spi_id')
						->get('stud_sch_info')->row();
		}
		return $query;
	}

	public function or_particulars($payment_id){
		$particulars = '';
		$paymentdetails = $this->db
							->select(['paymentdetails.*', 'particulars.particularName', 'particulars.collectionReportGroup as p_collectionGroup', 'particulars.feeType', 'assessment.particular', 'assessment.collectionReportGroup as a_collectionGroup'])
							->where('paymentId', $payment_id)
							->join('particulars', 'particulars.particularId = paymentdetails.particularId', 'LEFT')
							->join('assessment', 'assessment.assessmentId = paymentdetails.assessmentId', 'LEFT')
							->get('paymentdetails')->result();
		foreach ($paymentdetails as $key => $value) {
			$particulars .= $value->particularName ? $value->particularName . "," : $value->particular . ",";
			
			if($value->p_collectionGroup){
				$value->collectionReportGroup = $value->p_collectionGroup;
			}
			else{
				$value->collectionReportGroup = $value->a_collectionGroup;
			}
			unset($value->p_collectionGroup);
			unset($value->a_collectionGroup);
		}

		return [
			'particulars' => $particulars,
			'or_details'  => $paymentdetails
		];
	}

	public function old_system_report($month_year){
		
		$sis_db = $this->load->database('sis_db', TRUE);
		$old_payments = $this->db
							->select('p.paymentId, p.paymentDate, p.orNo, p.acctno, p.amt1 as or_gross')
							->where('p.acctno IS NOT NULL', NULL)
							->where('MONTH(p.paymentDate)', $month_year[1])
							->where('YEAR(p.paymentDate)', $month_year[0])
							->get('payments p')
							->result();

		$particulars_tbd = $this->db // particulars to be distributed or breakdown for miscellaneous
							->where('particulars.feeType', 'miscellaneous')
							->where('particulars.collectionReportGroup !=', 'netR')
							->group_by(['particularName'])
							->get('particulars')
							->result();

		foreach ($old_payments as $key => $value) {
			$pd = $this->db
					->where('paymentId', $value->paymentId)
					->get('paymentdetails pd')->result();
			$value->paymentdetails = $pd;
		}

		foreach ($old_payments as $key => $value) {		
			$array = [];
			$spi = $sis_db
					->select('CONCAT(lname, ", ", fname) as full_name')
					->where('ssi.acct_no', $value->acctno)
					->join('stud_per_info spi', 'spi.spi_id = ssi.spi_id')
					->get('stud_sch_info ssi')->row();

			$netR_amt = 0;
			$particulars = "";
			$array['date'] = $value->paymentDate;
			$array['grossReceipt'] = $value->or_gross;
			$array['name'] = $spi->full_name;
			$array['or'] = $value->orNo;
			$others = 0; //remaining amount will be inserted here
			$misc = [];

			foreach ($value->paymentdetails as $pd_key => $pd_value) {
				
				if($pd_value->oldParticular != 'miscellaneous'){
					$particulars .= ucfirst($pd_value->oldParticular . ",");
					$netR_amt += (int)$pd_value->amt1;
				}
				else{
					$amount = $pd_value->amt1;
					$misc_particulars = [];
					foreach ($particulars_tbd as $ptbd_key => $ptbd_value) {
						if($amount > 0){
							if($amount > $ptbd_value->amt1){
								$misc_particulars[$ptbd_value->collectionReportGroup] = $ptbd_value->amt1;
								$amount -= (int)$ptbd_value->amt1;
							}
							else{
								$misc_particulars[$ptbd_value->collectionReportGroup] = $amount;
								$amount = 0;
							}
							$particulars .= ucfirst($ptbd_value->particularName . ",");
						}
						else{
							break;
						}
					}
					if($amount > 0){
						$misc_particulars['others'] = $amount;
					}
					$misc = $misc_particulars;
				}

			}
			foreach ($misc as $key => $value) {
				$array[$key] = $value;
			}
			unset($array['misc']);
			$array['netR'] = $netR_amt;
			$array['particular'] = $particulars;
			$this->db->insert('collectionreport', $array);
		}
		return true;
	}

	public function test($ssi_id = '9304'){
		$month_year = explode('-', $data->post('month'));

		$sis_db = $this->load->database('sis_db', TRUE);
		$old_payments = $this->db
							->select('p.paymentId, p.paymentDate, p.orNo, p.acctno, p.amt1 as or_gross')
							->where('p.acctno IS NOT NULL', NULL)
							->where('MONTH(payments.paymentDate)', $month_year[1])
							->where('YEAR(payments.paymentDate)', $month_year[0])
							->get('payments p')
							->result();

		$particulars_tbd = $this->db // particulars to be distributed or breakdown for miscellaneous
							->where('particulars.feeType', 'miscellaneous')
							->where('particulars.collectionReportGroup !=', 'netR')
							->group_by(['particularName'])
							->get('particulars')
							->result();

		foreach ($old_payments as $key => $value) {
			$pd = $this->db
					->where('paymentId', $value->paymentId)
					->get('paymentdetails pd')->result();
			$value->paymentdetails = $pd;
		}

		foreach ($old_payments as $key => $value) {		
			$array = [];
			$spi = $sis_db
					->select('CONCAT(lname, ", ", fname) as full_name')
					->where('ssi.acct_no', $value->acctno)
					->join('stud_per_info spi', 'spi.spi_id = ssi.spi_id')
					->get('stud_sch_info ssi')->row();

			$netR_amt = 0;
			$particulars = "";
			$array['date'] = $value->paymentDate;
			$array['grossReceipt'] = $value->or_gross;
			$array['name'] = $spi->full_name;
			$array['or'] = $value->orNo;
			$others = 0; //remaining amount will be inserted here
			$misc = [];

			foreach ($value->paymentdetails as $pd_key => $pd_value) {
				
				if($pd_value->oldParticular != 'miscellaneous'){
					$particulars .= ucfirst($pd_value->oldParticular . ",");
					$netR_amt += (int)$pd_value->amt1;
				}
				else{
					$amount = $pd_value->amt1;
					$misc_particulars = [];
					foreach ($particulars_tbd as $ptbd_key => $ptbd_value) {
						if($amount > 0){
							if($amount > $ptbd_value->amt1){
								$misc_particulars[$ptbd_value->collectionReportGroup] = $ptbd_value->amt1;
								$amount -= (int)$ptbd_value->amt1;
							}
							else{
								$misc_particulars[$ptbd_value->collectionReportGroup] = $amount;
								$amount = 0;
							}
							$particulars .= ucfirst($ptbd_value->particularName . ",");
						}
						else{
							break;
						}
					}
					if($amount > 0){
						$misc_particulars['others'] = $amount;
					}
					$misc = $misc_particulars;
				}

			}
			foreach ($misc as $key => $value) {
				$array[$key] = $value;
			}
			unset($array['misc']);
			$array['netR'] = $netR_amt;
			$array['particular'] = $particulars;
			$this->db->insert('collectionreport', $array);
		}



		// $array = [];
		// foreach ($old_payments as $op_key => $op_value) {
		// 	$amt = (int)$op_value->amt1;
		// 	$elearning = 0;
		// 	$insurance = 0;
		// 	$msfee = 0;
		// 	$nccUk = 0;
		// 	$office365 = 0;
		// 	$oracle = 0;
		// 	$scnl = 0;
		// 	$studentServices = 0;
		// 	$netR = 0;
		// 	if($op_value->oldParticular != "miscellaneous"){
		// 		$netR += $op_value->amt1;
		// 	}

		// 	$spi = $sis_db
		// 				->select('CONCAT(lname, ", ", fname) as full_name')
		// 				->where('ssi.acct_no', $op_value->acctno)
		// 				->join('stud_per_info spi', 'spi.spi_id = ssi.spi_id')
		// 				->get('stud_sch_info ssi')->row();

		// 	$array[$op_key]['date'] = $op_value->paymentDate;
		// 	$array[$op_key]['or']   = $op_value->orNo;
		// 	$array[$op_key]['name'] = $spi->full_name;
		// 	$array[$op_key]['grossReceipt'] = $op_value->or_gross;
		// 	$particulars = "";

		// 	foreach ($particulars_tbd as $p_key => $p_value) {
		// 		if($amt > 0){
		// 			$particulars .= $p_value->particularName . ", ";
		// 			if($amt > $p_value->amt1){
		// 				${$p_value->collectionReportGroup} = $p_value->amt1;
		// 				$amt = $amt - (int)$p_value->amt1;
		// 			}
		// 			else{
		// 				${$p_value->collectionReportGroup} = $amt;
		// 				$amt = $amt - (int)$p_value->amt1;
		// 				break;
		// 			}
		// 		}
		// 		else{
		// 			break;
		// 		}
		// 	}
		// 	$array[$op_key]['particular'] = $amt > 0 ? $particulars . "others" : $particulars;
		// 	$array[$op_key]['elearning']  = $elearning ? $elearning : "-";
		// 	$array[$op_key]['insurance']  = $insurance ? $insurance : "-";
		// 	$array[$op_key]['msfee']  = $msfee ? $msfee : "-";
		// 	$array[$op_key]['nccUk']  = $nccUk ? $nccUk : "-";
		// 	$array[$op_key]['office365']  = $office365 ? $office365 : "-";
		// 	$array[$op_key]['oracle']  = $oracle ? $oracle : "-";
		// 	$array[$op_key]['scnl']  = $scnl ? $scnl : "-";
		// 	$array[$op_key]['studentServices']  = $studentServices ? $studentServices : "-";
		// 	$array[$op_key]['others']  = $amt ? $amt : "-";
		// 	$array[$op_key]['netR']  = $netR ? $netR : "-";

		// }
		// if($array){
		// 	$this->db->insert_batch('collectionreport', $array);
		// }
		// else{
		// 	return false;
		// }
		// return true;
	}

}

?>