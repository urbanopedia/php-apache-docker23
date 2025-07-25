<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Fees_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sms_model');
    }

    public function getPreviousSessionBalance($enrollID = '', $session_id = '', $with_fine = 0)
    {
        $total_balance = 0;
        $total_fine = 0;
        $variable = $this->db->where(array('student_id' => $enrollID, 'session_id' => $session_id))->get('fee_allocation')->result();
        foreach ($variable as $key => $allocation) {
            $groupsDetails = $this->db->select('fee_type_id')->where('fee_groups_id', $allocation->group_id)->get('fee_groups_details')->result();
            foreach ($groupsDetails as $k => $type) {
                $fine = $this->feeFineCalculation($allocation->id, $type->fee_type_id);
                $b = $this->getBalance($allocation->id, $type->fee_type_id);
                $total_balance += $b['balance'];
                $total_fine += abs($fine - $b['fine']);
            }
        }
        if ($with_fine == 1) {
            return round($total_balance + $total_fine);
        } else {
            return ($total_balance);
        }
    }

    public function feeFineCalculation($allocationID, $typeID)
    {
        $this->db->select('fd.amount,fd.due_date,f.*');
        $this->db->from('fee_allocation as a');
        $this->db->join('fee_groups_details as fd', 'fd.fee_groups_id = a.group_id and fd.fee_type_id = ' . $this->db->escape($typeID), 'left');
        $this->db->join('fee_fine as f', 'f.group_id = fd.fee_groups_id and f.type_id = fd.fee_type_id', 'inner');
        $this->db->where('a.id', $allocationID);
        $this->db->where('f.session_id', get_session_id());
        $getDB = $this->db->get()->row_array();
        if (is_array($getDB) && count($getDB)) {
            $dueDate = $getDB['due_date'];
            if (strtotime($dueDate) < strtotime(date('Y-m-d'))) {
                $feeAmount = $getDB['amount'];
                $feeFrequency = $getDB['fee_frequency'];
                $fineValue = $getDB['fine_value'];
                if ($getDB['fine_type'] == 1) {
                    $fineAmount = $fineValue;
                } else {
                    $fineAmount = ($feeAmount / 100) * $fineValue;
                }
                $now = time(); // or your date as well
                $dueDate = strtotime($dueDate);
                $datediff = $now - $dueDate;
                $overDay = round($datediff / (60 * 60 * 24));
                if ($feeFrequency != 0) {
                    $fineAmount = ($overDay / $feeFrequency) * $fineAmount;
                }
                return $fineAmount;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function getStudentAllocationList($classID = '', $sectionID = '', $groupID = '', $branchID = '')
    {
        $sql = "SELECT e.*, s.photo, CONCAT_WS(' ',s.first_name, s.last_name) as fullname, s.gender, s.register_no, s.parent_id, s.email, s.mobileno, IFNULL(fa.id, 0) as allocation_id FROM enroll as e INNER JOIN student as s ON e.student_id = s.id LEFT JOIN login_credential as l ON l.user_id = s.id AND l.role = '7' LEFT JOIN fee_allocation as fa ON fa.student_id = e.id AND fa.group_id = " . $this->db->escape($groupID) . " AND fa.session_id= " . $this->db->escape(get_session_id()) . " WHERE e.class_id = " . $this->db->escape($classID) . " AND e.branch_id = " . $this->db->escape($branchID) . " AND e.session_id = " . $this->db->escape(get_session_id());
        if ($sectionID != 'all') {
            $sql .= " AND e.section_id =" . $this->db->escape($sectionID);
        }
        $sql .= " AND l.active = '1' ORDER BY s.id ASC";
        return $this->db->query($sql)->result_array();
    }

    public function getInvoiceStatus($enrollID = '')
    {

        $status = "";
        $sessionID = get_session_id();
        $sql = "SELECT SUM(`fee_groups_details`.`amount` + `fee_allocation`.`prev_due`) as `total`, min(`fee_allocation`.`id`) as `inv_no` FROM `fee_allocation` LEFT JOIN `fee_groups_details` ON `fee_groups_details`.`fee_groups_id` = `fee_allocation`.`group_id` LEFT JOIN `fees_type` ON `fees_type`.`id` = `fee_groups_details`.`fee_type_id` WHERE `fee_allocation`.`student_id` = " . $this->db->escape($enrollID) . " AND `fee_allocation`.`session_id` = " . $this->db->escape($sessionID);
        $balance = $this->db->query($sql)->row_array();
        $invNo = empty($balance['inv_no']) ? 0 : str_pad($balance['inv_no'], 4, '0', STR_PAD_LEFT);

        // calculation total transport fee
        $this->db->select("IFNULL(SUM(transport_stoppage_point.route_fare), 0) as amount");
        $this->db->from('transport_fee_details');
        $this->db->join('transport_stoppage_point', 'transport_stoppage_point.id = transport_fee_details.stoppage_point_id', 'inner');
        $this->db->where('transport_fee_details.enroll_id', $enrollID);
        $trans_amount = $this->db->get()->row()->amount;

        // calculation payment history
        $this->db->select("IFNULL(SUM(fee_payment_history.amount), 0) as amount, IFNULL(SUM(fee_payment_history.discount), 0) as discount, IFNULL(SUM(fee_payment_history.fine), 0) as fine");
        $this->db->from("fee_payment_history");
        $this->db->join("fee_allocation", "fee_allocation.id = fee_payment_history.allocation_id", "left");
        $this->db->join("transport_fee_details", "transport_fee_details.id = fee_payment_history.transport_fee_details_id", "left");
        $this->db->where("fee_allocation.student_id", $enrollID);
        $this->db->where("fee_allocation.session_id", $sessionID);
        $this->db->or_group_start();
        $this->db->where("transport_fee_details.enroll_id", $enrollID);
        $this->db->group_end();
        $paid = $this->db->get()->row_array();

        if ($paid['amount'] == 0) {
            $status = 'unpaid';
        } elseif (($balance['total'] + $trans_amount) == ($paid['amount'] + $paid['discount'])) {
            $status = 'total';
        } elseif ($paid['amount'] > 1) {
            $status = 'partly';
        }
        return array('status' => $status, 'invoice_no' => $invNo);
    }

    public function getInvoiceDetails($enrollID = '')
    {
        $sql = "SELECT `fee_allocation`.`group_id`,`fee_allocation`.`prev_due`,`fee_allocation`.`id` as `allocation_id`, `fees_type`.`name`, `fees_type`.`system`, `fee_groups_details`.`amount`, `fee_groups_details`.`due_date`, `fee_groups_details`.`fee_type_id` FROM `fee_allocation` LEFT JOIN
        `fee_groups_details` ON `fee_groups_details`.`fee_groups_id` = `fee_allocation`.`group_id` LEFT JOIN `fees_type` ON `fees_type`.`id` = `fee_groups_details`.`fee_type_id` WHERE
        `fee_allocation`.`student_id` = " . $this->db->escape($enrollID) . " AND `fee_allocation`.`session_id` = " . $this->db->escape(get_session_id()) . " ORDER BY `fee_allocation`.`group_id` ASC, `fees_type`.`id` ASC";
        $student = array();
        $r = $this->db->query($sql)->result_array();
        foreach ($r as $key => $value) {
            if ($value['system'] == 1) {
                $value['amount'] = $value['prev_due'];
            }
            $student[] = $value;
        }
        return $student;
    }

    public function getInvoiceBasic($enrollID = '')
    {
        $sessionID = get_session_id();
        $this->db->select('s.id,s.register_no,e.branch_id,e.id as enroll_id,s.first_name,s.last_name,s.stoppage_point_id,s.email as student_email,s.current_address as student_address,c.name as class_name,b.school_name,b.email as school_email,b.mobileno as school_mobileno,b.address as school_address,p.father_name,se.name as section_name');
        $this->db->from('enroll as e');
        $this->db->join('student as s', 's.id = e.student_id', 'inner');
        $this->db->join('class as c', 'c.id = e.class_id', 'left');
        $this->db->join('section as se', 'se.id = e.section_id', 'left');
        $this->db->join('parent as p', 'p.id = s.parent_id', 'left');
        $this->db->join('branch as b', 'b.id = e.branch_id', 'left');
        if (!is_superadmin_loggedin()) {
            $this->db->where('e.branch_id', get_loggedin_branch_id());
        }
        $this->db->where('e.id', $enrollID);
        $this->db->where('e.session_id', $sessionID);
        return $this->db->get()->row_array();
    }

    public function getStudentFeeDeposit($allocationID, $typeID)
    {
        $sqlDeposit = "SELECT IFNULL(SUM(`amount`), '0.00') as `total_amount`, IFNULL(SUM(`discount`), '0.00') as `total_discount`, IFNULL(SUM(`fine`), '0.00') as `total_fine` FROM `fee_payment_history` WHERE `allocation_id` = " . $this->db->escape($allocationID) . " AND `type_id` = " . $this->db->escape($typeID);
        return $this->db->query($sqlDeposit)->row_array();
    }

    public function getPaymentHistory($allocationID, $groupID)
    {
        $this->db->select('h.*,t.name,t.fee_code,pt.name as payvia');
        $this->db->from('fee_payment_history as h');
        $this->db->join('fees_type as t', 't.id = h.type_id', 'left');
        $this->db->join('payment_types as pt', 'pt.id = h.pay_via', 'left');
        $this->db->where('h.allocation_id', $allocationID);
        $this->db->order_by('h.id', 'asc');
        return $this->db->get()->result_array();
    }

    public function typeSave($data = array())
    {
        $arrayData = array(
            'branch_id' => $this->application_model->get_branch_id(),
            'name' => $data['type_name'],
            'fee_code' => strtolower(str_replace(' ', '-', $data['type_name'])),
            'description' => $data['description'],
        );
        if (!isset($data['type_id'])) {
            $this->db->insert('fees_type', $arrayData);
        } else {
            $this->db->where('id', $data['type_id']);
            $this->db->update('fees_type', $arrayData);
        }
    }

    // add partly of the fee
    public function add_fees($data = array(), $id = '')
    {
        $total_due = get_type_name_by_id('fee_invoice', $id, 'total_due');
        $payment_amount = $data['amount'];
        if (($payment_amount <= $total_due) && ($payment_amount > 0)) {
            $arrayHistory = array(
                'fee_invoice_id' => $id,
                'collect_by' => get_user_stamp(),
                'remarks' => $data['remarks'],
                'method' => $data['method'],
                'amount' => $payment_amount,
                'date' => date("Y-m-d"),
                'session_id' => get_session_id(),
            );
            $this->db->insert('payment_history', $arrayHistory);

            if ($total_due <= $payment_amount) {
                $this->db->where('id', $id);
                $this->db->update('fee_invoice', array('status' => 2));
            } else {
                $this->db->where('id', $id);
                $this->db->update('fee_invoice', array('status' => 1));
            }
            $this->db->where('id', $id);
            $this->db->set('total_paid', 'total_paid + ' . $payment_amount, false);
            $this->db->set('total_due', 'total_due - ' . $payment_amount, false);
            $this->db->update('fee_invoice');

            // send payment confirmation sms
            $arrayHistory['student_id'] = $data['student_id'];
            $arrayHistory['timestamp'] = date("Y-m-d");
            $this->sms_model->send_sms($arrayHistory, 2);
            return true;
        } else {
            return false;
        }
    }

    public function getInvoiceList()
    {
        $branchID = $this->application_model->get_branch_id();
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');

        // getting a list of classes assigned to a teacher
        $assigned_cs_list = $this->app_lib->get_ownClassSection();

        $this->datatables->select('e.id as enroll_id,e.student_id,e.roll,s.first_name,s.last_name,s.register_no,s.mobileno,c.name as class_name,se.name as section_name');
        $this->datatables->from('fee_allocation as fa');
        $this->datatables->join('enroll as e', 'e.id = fa.student_id and e.session_id = fa.session_id', 'inner');
        $this->datatables->join('student as s', 's.id = e.student_id', 'left');
        $this->datatables->join('class as c', 'c.id = e.class_id', 'left');
        $this->datatables->join('section as se', 'se.id = e.section_id', 'left');
        $this->datatables->where('fa.branch_id', 1);
        $this->datatables->where('fa.session_id', get_session_id());
        $this->datatables->where('s.active', 1);
        $this->datatables->where('fa.branch_id', $branchID);
        if (!empty($class_id)) {
            $this->datatables->where('e.class_id', $class_id);
        }
        if (!empty($section_id)) {
            $this->db->where('e.section_id', $section_id);
        }
        // filter classes by teacher assigned classes
        if ($assigned_cs_list != false && !empty($assigned_cs_list)) {
            $this->datatables->group_start();
            foreach ($assigned_cs_list as $class_key => $class_value) {
                foreach ($class_value as $section_key => $section_value) {
                    $this->datatables->or_group_start();
                    $this->datatables->where('e.class_id', $class_key);
                    $this->datatables->where('e.section_id', $section_value);
                    $this->datatables->group_end();

                }
            }
            $this->datatables->group_end();
        }
        $this->datatables->search_value('s.first_name,s.last_name,s.register_no,s.mobileno');
        $this->datatables->column_order('e.id,s.first_name,c.id,se.id,s.register_no,e.roll,s.mobileno,fa.id');
        $this->datatables->group_by('fa.student_id');
        $this->datatables->order_by('fa.id', 'asc');
        $results = $this->datatables->generate();
        $records = array();
        $records = json_decode($results);
        $data = array();
        foreach ($records->data as $key => $record) {
            $full_name = $record->first_name . ' ' . $record->last_name;
            // actions btn
            $actions = '<button type="button" data-loading-text="<i class=\'fas fa-spinner fa-spin\'></i>" data-placement="top" data-toggle="tooltip" data-original-title="' . translate('email') . " " . translate('invoice') . '" class="btn btn-default icon btn-circle" onclick="pdf_sendByemail(' . "'" . $record->enroll_id . "'" . ', this)"><i class="fa-solid fa-envelope"></i></button>';
            if (get_permission('collect_fees', 'is_add')) {
                $actions .= '<a href="' . base_url('fees/invoice/' . $record->enroll_id) . '" class="btn btn-default btn-circle"><i class="far fa-arrow-alt-circle-right"></i> ' . translate('collect') . '</a>';
            }
            if (get_permission('invoice', 'is_delete')) {
                $actions .= btn_delete('fees/invoice_delete/' . $record->enroll_id);
            }

            // dt-data array
            $row = array();
            $row[] = '<div class="checked-area"><div class="checkbox-replace"><label class="i-checks"><input type="checkbox" name="student_id[]" value="' . $record->enroll_id . '"><i></i></label></div></div>';
            $row[] = '<a class="hidden-print" href="' . base_url('student/profile/') . $record->enroll_id . '">' . $full_name . '</a>' . '<span class="visible-print">' . $full_name . '</span>';
            $row[] = $record->class_name;
            $row[] = $record->section_name;
            $row[] = $record->register_no;
            $row[] = $record->roll;
            $row[] = $record->mobileno;
            // getting fees group list
            $feegroup = $this->getfeeGroup($record->enroll_id);
            $groupList = '';
            foreach ($feegroup as $key => $value) {
                $groupList .= "- " . $value['name'] . "<br>";
            }
            $row[] = $groupList;
            // fees status
            $labelmode = '';
            $status = $this->getInvoiceStatus($record->enroll_id)['status'];
            if ($status == 'unpaid') {
                $status = translate('unpaid');
                $labelmode = 'label-danger-custom';
            } elseif ($status == 'partly') {
                $status = translate('partly_paid');
                $labelmode = 'label-info-custom';
            } elseif ($status == 'total') {
                $status = translate('total_paid');
                $labelmode = 'label-success-custom';
            }
            $row[] = "<span class='value label " . $labelmode . " '>" . $status . "</span>";
            $row[] = $actions;

            $data[] = $row;
        }
        $json_data = array(
            "draw" => intval($records->draw),
            "recordsTotal" => intval($records->recordsTotal),
            "recordsFiltered" => intval($records->recordsFiltered),
            "data" => $data,
        );
        return json_encode($json_data);
    }

    public function getDueInvoiceDT_list($class_id = '', $section_id = '', $feegroup_id = '', $fee_feetype_id = '')
    {
        $get_session_id = get_session_id();
        if ($feegroup_id == 'transport') {
            $this->datatables->select('IFNULL(SUM(h.amount), 0) as total_amount, IFNULL(SUM(h.discount), 0) as total_discount, sp.route_fare as full_amount,ff.due_date, e.student_id, e.id as enroll_id,e.roll, s.first_name, s.last_name, s.register_no, s.mobileno, c.name as class_name, se.name as section_name');
            $this->datatables->from('transport_fee_details as fa');
            $this->datatables->join('fee_payment_history as h', 'h ON h.transport_fee_details_id = fa.id', 'left');
            $this->datatables->join('transport_stoppage_point as sp', 'sp.id = fa.stoppage_point_id', 'left');
            $this->datatables->join('transport_fee_fine as ff', 'ff.id = fa.transport_fee_fine_id', 'inner');
            $this->datatables->join('enroll as e', 'e.id = fa.enroll_id', 'inner');;
            $this->datatables->where('fa.transport_fee_fine_id', $fee_feetype_id);
            $this->datatables->where('sp.session_id', $get_session_id);
            $this->datatables->group_by('fa.enroll_id');
            $this->datatables->search_value('s.first_name,s.register_no,e.roll,s.mobileno,ff.due_date');
        } else {
            $this->datatables->select('IFNULL(SUM(h.amount), 0) as total_amount, IFNULL(SUM(h.discount), 0) as total_discount, gd.amount as full_amount, fa.prev_due as prev_due, gd.due_date, e.student_id, e.id as enroll_id,e.roll, s.first_name, s.last_name, s.register_no, s.mobileno, c.name as class_name, se.name as section_name');
            $this->datatables->from('fee_allocation as fa');
            $this->datatables->join('fee_payment_history as h', 'h ON h.allocation_id = fa.id and h.type_id =' . $this->db->escape($fee_feetype_id), 'left');
            $this->datatables->join('fee_groups_details as gd', 'gd.fee_groups_id = fa.group_id and gd.fee_type_id =' . $this->db->escape($fee_feetype_id), 'inner');
            $this->datatables->join('enroll as e', 'e.id = fa.student_id', 'inner');
            $this->datatables->where('fa.group_id', $feegroup_id);
            $this->datatables->where('fa.session_id', $get_session_id);
            $this->datatables->group_by('fa.student_id');
            $this->datatables->search_value('s.first_name,s.register_no,e.roll,s.mobileno,gd.due_date');
        }
        $this->datatables->join('student as s', 's.id = e.student_id', 'left');
        $this->datatables->join('class as c', 'c.id = e.class_id', 'left');
        $this->datatables->join('section as se', 'se.id = e.section_id', 'left');
        $this->datatables->where('e.class_id', $class_id);
        if ($section_id != 'all') {
            $this->datatables->where('e.section_id', $section_id);
        }
        $this->datatables->column_order('fa.id,s.first_name,s.register_no,e.roll,s.mobileno,enroll_id,due_date,full_amount,total_amount,total_discount,full_amount');
        $this->datatables->order_by('e.id', 'asc');
        $results = $this->datatables->generate();
        return $results;
    }

    public function getDueReport($class_id = '', $section_id = '')
    {
        $this->db->select('fa.id as allocation_id,sum(gd.amount + fa.prev_due) as total_fees,e.id as enroll_id,e.roll,s.first_name,s.last_name,s.register_no,s.mobileno,c.name as class_name,se.name as section_name');
        $this->db->from('fee_allocation as fa');
        $this->db->join('fee_groups_details as gd', 'gd.fee_groups_id = fa.group_id', 'left');
        $this->db->join('enroll as e', 'e.id = fa.student_id and e.session_id = ' . $this->db->escape(get_session_id()), 'inner');
        $this->db->join('student as s', 's.id = e.student_id', 'left');
        $this->db->join('class as c', 'c.id = e.class_id', 'left');
        $this->db->join('section as se', 'se.id = e.section_id', 'left');
        $this->db->where('fa.session_id', get_session_id());
        $this->db->where('s.active', 1);
        $this->db->where('e.class_id', $class_id);
        if (!empty($section_id)) {
            $this->db->where('e.section_id', $section_id);
        }
        $this->db->group_by('fa.student_id');
        $this->db->order_by('e.id', 'asc');
        $result = $this->db->get()->result_array();
        foreach ($result as $key => $value) {
            $result[$key]['payment'] = $this->getPaymentDetails($value['enroll_id']);
        }
        return $result;
    }

    public function getPaymentDetails($student_id = '')
    {
        $this->db->select('IFNULL(SUM(amount), 0) as total_paid, IFNULL(SUM(discount), 0) as total_discount, IFNULL(SUM(fine), 0) as total_fine');
        $this->db->from('fee_allocation');
        $this->db->join('fee_payment_history', 'fee_payment_history.allocation_id = fee_allocation.id', 'left');
        $this->db->where('fee_allocation.student_id', $student_id);
        return $this->db->get()->row_array();
    }

    public function getStuPaymentHistory($classID = '', $SectionID = '', $paymentVia = '', $start = '', $end = '', $branchID = '', $onlyFine = false)
    {
        $this->db->select('h.*,ft.name as type_name,e.student_id,e.roll,s.first_name,s.last_name,s.register_no,s.mobileno,c.name as class_name,se.name as section_name,pt.name as pay_via,h.transport_fee_details_id');
        $this->db->from('fee_payment_history as h');
        $this->db->join('fee_allocation as fa', 'fa.id = h.allocation_id', 'inner');
        $this->db->join('fees_type as ft', 'ft.id = h.type_id', 'left');
        $this->db->join('enroll as e', 'e.id = fa.student_id', 'inner');
        $this->db->join('student as s', 's.id = e.student_id', 'inner');
        $this->db->join('class as c', 'c.id = e.class_id', 'left');
        $this->db->join('section as se', 'se.id = e.section_id', 'left');
        $this->db->join('payment_types as pt', 'pt.id = h.pay_via', 'left');
        $this->db->where('fa.session_id', get_session_id());
        $this->db->where('e.session_id', get_session_id());
        $this->db->where('s.active', 1);
        $this->db->where('h.date  >=', $start);
        $this->db->where('h.date <=', $end);
        $this->db->where('e.branch_id', $branchID);
        if ($onlyFine == true) {
            $this->db->where('h.fine !=', 0);
        }
        if (!empty($classID)) {
            $this->db->where('e.class_id', $classID);
        }
        if (!empty($SectionID)) {
            $this->db->where('e.section_id', $SectionID);
        }
        if ($paymentVia != 'all') {
            if ($paymentVia == 'online') {
                $this->db->where('h.collect_by', 'online');
            } else {
                $this->db->where('h.collect_by !=', 'online');
            }
        }
        $this->db->order_by('h.id', 'asc');
        $result = $this->db->get()->result_array();

        if (moduleIsEnabled('transport')) {
            $this->db->select('h.*,ff.month as type_name,e.student_id,e.roll,s.first_name,s.last_name,s.register_no,s.mobileno,c.name as class_name,se.name as section_name,pt.name as pay_via,h.transport_fee_details_id');
            $this->db->from('fee_payment_history as h');
            $this->db->join('transport_fee_details as fa', 'fa.id = h.transport_fee_details_id', 'inner');
            $this->db->join('transport_fee_fine as ff', 'ff.id = fa.transport_fee_fine_id', 'inner');
            $this->db->join('enroll as e', 'e.id = fa.enroll_id and e.session_id =  ' . $this->db->escape(get_session_id()), 'inner');
            $this->db->join('student as s', 's.id = e.student_id', 'inner');
            $this->db->join('class as c', 'c.id = e.class_id', 'left');
            $this->db->join('section as se', 'se.id = e.section_id', 'left');
            $this->db->join('payment_types as pt', 'pt.id = h.pay_via', 'left');
            $this->db->where('ff.session_id', get_session_id());
            $this->db->where('s.active', 1);
            $this->db->where('h.date  >=', $start);
            $this->db->where('h.date <=', $end);
            $this->db->where('e.branch_id', $branchID);
            if ($onlyFine == true) {
                $this->db->where('h.fine !=', 0);
            }
            if (!empty($classID)) {
                $this->db->where('e.class_id', $classID);
            }
            if (!empty($SectionID)) {
                $this->db->where('e.section_id', $SectionID);
            }
            if ($paymentVia != 'all') {
                if ($paymentVia == 'online') {
                    $this->db->where('h.collect_by', 'online');
                } else {
                    $this->db->where('h.collect_by !=', 'online');
                }
            }
            $this->db->order_by('h.id', 'asc');
            $result2 = $this->db->get()->result_array();
        } else {
            $result2 = [];
        }


        if (empty($result)) {
            $result_output = $result2;
        } elseif (empty($result2)) {
            $result_output = $result;
        } else {
            $result_output = array_merge($result, $result2);
        }


        return $result_output;
    }

    public function getStuPaymentReport($classID = '', $sectionID = '', $enrollID = '', $typeID = '', $start = '', $end = '', $branchID = '')
    {
        if (!empty($typeID)) {
            $type = explode("|", $typeID);
            $group = $type[0];
            $type_id = $type[1];
           
        }
        if (empty($group) || $group != "transport" ) {
            $this->db->select('h.*,gd.due_date,ft.name as type_name,e.student_id,e.roll,s.first_name,s.last_name,s.register_no,pt.name as pay_via,h.transport_fee_details_id');
            $this->db->from('fee_payment_history as h');
            $this->db->join('fee_allocation as fa', 'fa.id = h.allocation_id', 'inner');
            $this->db->join('fees_type as ft', 'ft.id = h.type_id', 'left');
            $this->db->join('fee_groups_details as gd', 'gd.fee_groups_id = fa.group_id and gd.fee_type_id = h.type_id', 'left');
            $this->db->join('enroll as e', 'e.id = fa.student_id and e.session_id =  ' . $this->db->escape(get_session_id()), 'inner');
            $this->db->join('student as s', 's.id = e.student_id', 'inner');
            $this->db->join('payment_types as pt', 'pt.id = h.pay_via', 'left');
            $this->db->where('fa.session_id', get_session_id());
            $this->db->where('s.active', 1);
            $this->db->where('h.date >=', $start);
            $this->db->where('h.date <=', $end);
            $this->db->where('e.branch_id', $branchID);
            $this->db->where('e.class_id', $classID);
            if (!empty($type_id)) {
                $this->db->where('h.type_id', $type_id);
            }
            if (!empty($enrollID)) {
                $this->db->where('e.id', $enrollID);
            }
            $this->db->where('e.section_id', $sectionID);
            $this->db->order_by('h.id', 'asc');
            $result1 = $this->db->get()->result_array();
        } else {
             $result1 = [];
        }

        if (empty($group) || $group == "transport" ) {
            $this->db->select('h.*,ff.due_date,ff.month as type_name,e.student_id,e.roll,s.first_name,s.last_name,s.register_no,pt.name as pay_via,h.transport_fee_details_id');
            $this->db->from('fee_payment_history as h');
            $this->db->join('transport_fee_details as fa', 'fa.id = h.transport_fee_details_id', 'inner');
            $this->db->join('transport_fee_fine as ff', 'ff.id = fa.transport_fee_fine_id', 'inner');
            $this->db->join('enroll as e', 'e.id = fa.enroll_id and e.session_id =  ' . $this->db->escape(get_session_id()), 'inner');
            $this->db->join('student as s', 's.id = e.student_id', 'inner');
            $this->db->join('payment_types as pt', 'pt.id = h.pay_via', 'left');
            $this->db->where('ff.session_id', get_session_id());
            $this->db->where('s.active', 1);
            $this->db->where('h.date >=', $start);
            $this->db->where('h.date <=', $end);
            $this->db->where('e.branch_id', $branchID);
            $this->db->where('e.class_id', $classID);
            if (!empty($enrollID)) {
                $this->db->where('e.id', $enrollID);
            }
            if (!empty($type_id)) {
                $this->db->where('fa.transport_fee_fine_id', $type_id);
            }
            $this->db->where('e.section_id', $sectionID);
            $this->db->order_by('h.id', 'asc');
            $result2 = $this->db->get()->result_array();
        } else {
            $result2 = [];
        }
        $result_value2 = array_merge($result1, $result2);
        return $result_value2;
    }

    public function getfeeGroup($studentID = '')
    {
        $this->db->select('g.name');
        $this->db->from('fee_allocation as fa');
        $this->db->join('fee_groups as g', 'g.id = fa.group_id', 'inner');
        $this->db->where('fa.student_id', $studentID);
        $this->db->where('fa.session_id', get_session_id());
        return $this->db->get()->result_array();
    }

    public function reminderSave($data = array())
    {
        $arrayData = array(
            'frequency' => $data['frequency'],
            'days' => $data['days'],
            'student' => (isset($data['chk_student']) ? 1 : 0),
            'guardian' => (isset($data['chk_guardian']) ? 1 : 0),
            'message' => $data['message'],
            'dlt_template_id' => $data['dlt_template_id'],
            'branch_id' => $data['branch_id'],
        );
        if (!isset($data['reminder_id'])) {
            $this->db->insert('fees_reminder', $arrayData);
        } else {
            $this->db->where('id', $data['reminder_id']);
            $this->db->update('fees_reminder', $arrayData);
        }
    }

    public function getFeeReminderByDate($date = '', $branch_id = '')
    {
        $this->db->select('fee_groups_details.*,fees_type.name');
        $this->db->from('fee_groups_details');
        $this->db->join('fees_type', 'fees_type.id = fee_groups_details.fee_type_id', 'inner');
        $this->db->where('fee_groups_details.due_date', $date);
        $this->db->where('fees_type.branch_id', $branch_id);
        $this->db->order_by('fee_groups_details.id', 'asc');
        return $this->db->get()->result_array();
    }

    public function getStudentsListReminder($groupID = '', $typeID = '')
    {
        $sessionID = get_type_name_by_id('global_settings', 1, 'session_id');
        $this->db->select('a.id as allocation_id,CONCAT_WS(" ",s.first_name, s.last_name) as child_name,s.mobileno as child_mobileno,pr.name as guardian_name,pr.mobileno as guardian_mobileno');
        $this->db->from('fee_allocation as a');
        $this->db->join('student as s', 's.id = a.student_id', 'inner');
        $this->db->join('parent as pr', 'pr.id = s.parent_id', 'left');
        $this->db->where('a.group_id', $groupID);
        $this->db->where('a.session_id', $sessionID);
        $result = $this->db->get()->result_array();
        foreach ($result as $key => $value) {
            $result[$key]['payment'] = $this->getPaymentDetailsByTypeID($value['allocation_id'], $typeID);
        }
        return $result;
    }

    public function getPaymentDetailsByTypeID($allocationID, $typeID)
    {
        $this->db->select('IFNULL(SUM(amount), 0) as total_paid, IFNULL(SUM(discount), 0) as total_discount');
        $this->db->from('fee_payment_history');
        $this->db->where('allocation_id', $allocationID);
        $this->db->where('type_id', $typeID);
        return $this->db->get()->row_array();
    }

    public function depositAmountVerify($amount = '')
    {
        if ($amount != "") {
            $typeID = $this->input->post('fees_type');
            if (empty($typeID)) {
                return true;
            }
            $feesType = explode("|", $typeID);
            if ($feesType[0] == 'transport') {
                $remainAmount = $this->getTransportBalance($feesType[1]);
            } else {
                $remainAmount = $this->getBalance($feesType[0], $feesType[1]);
            }
            if ($remainAmount['balance'] < $amount) {
                $this->form_validation->set_message('deposit_verify', '{field} cannot be greater than the remaining.');
                return false;
            } else {
                return true;
            }
        }
        return true;
    }

    public function getBalance($allocationID, $typeID)
    {
        $groupsID = get_type_name_by_id('fee_allocation', $allocationID, 'group_id');
        $systemFeesType = get_type_name_by_id('fees_type', $typeID, 'system');
        if ($systemFeesType == 1) {
            $totalAmount = get_type_name_by_id('fee_allocation', $allocationID, 'prev_due');
        } else {
            $totalAmount = $this->db->select('amount')->where(array('fee_groups_id' => $groupsID, 'fee_type_id' => $typeID))->get('fee_groups_details')->row_array();
            $totalAmount = $totalAmount['amount'];
        }

        $this->db->select('IFNULL(sum(p.amount), 0) as total_amount,IFNULL(sum(p.discount), 0) as total_discount,IFNULL(sum(p.fine), 0) as total_fine');
        $this->db->from('fee_payment_history as p');
        $this->db->where('p.allocation_id', $allocationID);
        $this->db->where('p.type_id', $typeID);
        $paid = $this->db->get()->row_array();
        $balance = $totalAmount - ($paid['total_amount'] + $paid['total_discount']);
        $total_fine = $paid['total_fine'];
        return array('balance' => $balance, 'fine' => $total_fine);
    }

    // voucher transaction save function
    public function saveTransaction($data = array(), $payment_historyID = '')
    {
        $branchID = $this->application_model->get_branch_id();
        $accountID = $data['account_id'];
        $date = $data['date'];
        $amount = $data['amount'];

        // get the current balance of the selected account
        $qbal = $this->app_lib->get_table('accounts', $accountID, true);
        $cbal = $qbal['balance'];
        $bal = $cbal + $amount;
        // query system voucher head / insert
        $arrayHead = array(
            'name' => 'Student Fees Collection',
            'type' => 'income',
            'system' => 1,
            'branch_id' => $branchID,
        );
        $this->db->where($arrayHead);
        $query = $this->db->get('voucher_head');
        if ($query->num_rows() > 0) {
            $voucher_headID = $query->row()->id;
        } else {
            $this->db->insert('voucher_head', $arrayHead);
            $voucher_headID = $this->db->insert_id();
        }
        // query system transactions / insert
        $arrayTransactions = array(
            'account_id' => $accountID,
            'voucher_head_id' => $voucher_headID,
            'type' => 'deposit',
            'system' => 1,
            'date' => date("Y-m-d", strtotime($date)),
            'branch_id' => $branchID,
        );
        $this->db->where($arrayTransactions);
        $query = $this->db->get('transactions');
        if ($query->num_rows() == 1) {
            $transactionsID = $query->row()->id;
            $this->db->set('amount', 'amount+' . $amount, false);
            $this->db->set('cr', 'cr+' . $amount, false);
            $this->db->set('bal', $bal);
            $this->db->where('id', $transactionsID);
            $this->db->update('transactions');
        } else {
            $arrayTransactions['ref'] = '';
            $arrayTransactions['amount'] = $amount;
            $arrayTransactions['dr'] = 0;
            $arrayTransactions['cr'] = $amount;
            $arrayTransactions['bal'] = $bal;
            $arrayTransactions['pay_via'] = 5;
            $arrayTransactions['description'] = date("d-M-Y", strtotime($date)) . " Total Fees Collection";
            $this->db->insert('transactions', $arrayTransactions);
            $transactionsID = $this->db->insert_id();
        }

        $this->db->where('id', $accountID);
        $this->db->update('accounts', array('balance' => $bal));

        // insert transactions links details in DB
        $arrayLinkDetails = array(
            'payment_id' => $payment_historyID,
            'transactions_id' => $transactionsID,
        );
        $this->db->insert('transactions_links_details', $arrayLinkDetails);
    }

    public function carryForwardDue($data = array())
    {
        $type_name = "Previous Session Balance";
        $group_name = "Due Record";
        $branchID = $data['branch_id'];
        $sessionID = $data['session_id'];
        $fee_type_id = 0;
        $fee_group_id = 0;

        $arrayType = array(
            'name' => $type_name,
            'branch_id' => $branchID,
            'system' => 1,
        );
        $fee_type_exists = $this->checkExistsData('fees_type', $arrayType);
        if (!$fee_type_exists) {
            $arrayType['fee_code'] = 'previous-balance';
            $this->db->insert('fees_type', $arrayType);
            $fee_type_id = $this->db->insert_id();
        } else {
            $fee_type_id = $fee_type_exists->id;
        }

        $arrayGroup = array(
            'name' => $group_name,
            'branch_id' => $branchID,
            'session_id' => $sessionID,
            'system' => 1,
        );
        $fee_group_exists = $this->checkExistsData('fee_groups', $arrayGroup);
        if (!$fee_group_exists) {
            $this->db->insert('fee_groups', $arrayGroup);
            $fee_group_id = $this->db->insert_id();
        } else {
            $fee_group_id = $fee_group_exists->id;
        }

        $arrayGroupsDetails = array(
            'fee_groups_id' => $fee_group_id,
            'fee_type_id' => $fee_type_id,
        );
        $fee_group_details_exists = $this->checkExistsData('fee_groups_details', $arrayGroupsDetails);
        if (!$fee_group_details_exists) {
            $arrayGroupsDetails['amount'] = 0;
            $arrayGroupsDetails['due_date'] = $data['due_date'];
            $this->db->insert('fee_groups_details', $arrayGroupsDetails);
        }

        $arrayAllocation = array(
            'student_id' => $data['student_id'],
            'group_id' => $fee_group_id,
            'branch_id' => $branchID,
            'session_id' => $sessionID,
        );
        $fee_allocation_exists = $this->checkExistsData('fee_allocation', $arrayAllocation);
        if (!$fee_allocation_exists) {
            $arrayAllocation['prev_due'] = $data['prev_due'];
            $this->db->insert('fee_allocation', $arrayAllocation);
        } else {
            $arrayAllocation['prev_due'] = $data['prev_due'];
            $this->db->where('id', $fee_allocation_exists->id);
            $this->db->update('fee_allocation', $arrayAllocation);
        }
    }

    public function checkExistsData($table = '', $data = array())
    {
        $this->db->where($data);
        $query = $this->db->get($table);
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return false;
        }
    }

    public function getStudentTransportFees($enroll_id = '', $stoppage_point_id = '')
    {
        if (!empty($enroll_id) && !empty($stoppage_point_id)) {
            $this->db->select("transport_fee_details.*,transport_fee_fine.month,transport_fee_fine.due_date,transport_stoppage_point.route_fare");
            $this->db->from('transport_fee_details');
            $this->db->join('transport_fee_fine', 'transport_fee_fine.id = transport_fee_details.transport_fee_fine_id', 'inner');
            $this->db->join('transport_stoppage_point', 'transport_stoppage_point.id = transport_fee_details.stoppage_point_id', 'inner');
            $this->db->where('transport_fee_details.enroll_id', $enroll_id);
            $this->db->where('transport_fee_details.stoppage_point_id', $stoppage_point_id);
            $this->db->order_by('transport_fee_details.id', 'asc');
            $result = $this->db->get()->result();
            return $result;
        }
        return [];
    }

    public function getStudentTransportFeeDeposit($transport_fee_details_id)
    {
        $sqlDeposit = "SELECT IFNULL(SUM(`amount`), '0.00') as `total_amount`, IFNULL(SUM(`discount`), '0.00') as `total_discount`, IFNULL(SUM(`fine`), '0.00') as `total_fine` FROM `fee_payment_history` WHERE `transport_fee_details_id` = " . $this->db->escape($transport_fee_details_id);
        return $this->db->query($sqlDeposit)->row_array();
    }

    public function transportFeeFineCalculation($fee_details_id = '')
    {
        $this->db->select('ff.*,fd.stoppage_point_id,ts.route_fare');
        $this->db->from('transport_fee_fine as ff');
        $this->db->join('transport_fee_details as fd', 'ff.id = fd.transport_fee_fine_id', 'inner');
        $this->db->join('transport_stoppage_point as ts', 'ts.id = fd.stoppage_point_id', 'inner');
        $this->db->where('fd.id', $fee_details_id);
        $getDB = $this->db->get()->row_array();
        if (is_array($getDB) && count($getDB)) {
            $dueDate = $getDB['due_date'];
            if ((strtotime($dueDate) < strtotime(date('Y-m-d'))) && !empty($getDB['fine_type'])) {
                $feeAmount = $getDB['route_fare'];
                $feeFrequency = $getDB['fee_frequency'];
                $fineValue = $getDB['fine_value'];
                if ($getDB['fine_type'] == 1) {
                    $fineAmount = $fineValue;
                } else {
                    $fineAmount = ($feeAmount / 100) * $fineValue;
                }
                $now = time(); // or your date as well
                $dueDate = strtotime($dueDate);
                $datediff = $now - $dueDate;
                $overDay = round($datediff / (60 * 60 * 24));
                if ($feeFrequency != 0) {
                    $fineAmount = ($overDay / $feeFrequency) * $fineAmount;
                }
                return $fineAmount;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    public function getTransportBalance($fee_details_id)
    {
        $this->db->select("transport_fee_details.stoppage_point_id,transport_stoppage_point.route_fare");
        $this->db->from('transport_fee_details');
        $this->db->join('transport_stoppage_point', 'transport_stoppage_point.id = transport_fee_details.stoppage_point_id', 'inner');
        $this->db->where('transport_fee_details.id', $fee_details_id);
        $result = $this->db->get()->row();
        $route_fare = $result->route_fare;

        $this->db->select('IFNULL(sum(p.amount), 0) as total_amount,IFNULL(sum(p.discount), 0) as total_discount,IFNULL(sum(p.fine), 0) as total_fine');
        $this->db->from('fee_payment_history as p');
        $this->db->where('p.transport_fee_details_id', $fee_details_id);
        $paid = $this->db->get()->row_array();
        $balance = $route_fare - ($paid['total_amount'] + $paid['total_discount']);
        $total_fine = $paid['total_fine'];
        return array('balance' => $balance, 'fine' => $total_fine);
    }

    public function getTransportPaymentHistory($transport_fee_details_id)
    {
        $this->db->select('h.*,t.name,t.fee_code,pt.name as payvia');
        $this->db->from('fee_payment_history as h');
        $this->db->join('fees_type as t', 't.id = h.type_id', 'left');
        $this->db->join('payment_types as pt', 'pt.id = h.pay_via', 'left');
        $this->db->where('h.transport_fee_details_id', $transport_fee_details_id);
        $this->db->order_by('h.id', 'asc');
        return $this->db->get()->result_array();
    }
}
