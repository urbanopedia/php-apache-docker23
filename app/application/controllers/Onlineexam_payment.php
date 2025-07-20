<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom school management system
 * @version : 7.0
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Onlineexam_payment.php
 * @copyright : Reserved RamomCoder Team
 */

class Onlineexam_payment extends Admin_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('userrole_model');
        $this->load->model('fees_model');
        $this->load->library('paypal_payment');
        $this->load->library('stripe_payment');
        $this->load->library('razorpay_payment');
        $this->load->library('sslcommerz');
        $this->load->library('midtrans_payment');
        $this->load->library('paytm_kit_lib');
    }

    public function checkout()
    {
        if (!is_student_loggedin()) {
            ajax_access_denied();
        }
        if ($_POST) {
            $examID = $this->input->post('exam_id');
            $payVia = $this->input->post('pay_via');
            $this->form_validation->set_rules('exam_id', translate('exam_id'), 'trim|required');
            
            if ($payVia == 'payumoney') {
                $this->form_validation->set_rules('payer_name', translate('name'), 'trim|required');
                $this->form_validation->set_rules('email', translate('email'), 'trim|required|valid_email');
                $this->form_validation->set_rules('phone', translate('phone'), 'trim|required');
            }

            if ($payVia == 'toyyibpay' || $payVia == 'payhere') {
                $this->form_validation->set_rules('toyyibpay_email', translate('email'), 'trim|required|valid_email');
                $this->form_validation->set_rules('toyyibpay_phone', translate('phone'), 'trim|required');
            }

            if ($payVia == 'sslcommerz') {
                $this->form_validation->set_rules('sslcommerz_name', translate('name'), 'trim|required');
                $this->form_validation->set_rules('sslcommerz_email', translate('email'), 'trim|required|valid_email');
                $this->form_validation->set_rules('sslcommerz_address', translate('address'), 'trim|required');
                $this->form_validation->set_rules('sslcommerz_postcode', translate('postcode'), 'trim|required');
                $this->form_validation->set_rules('sslcommerz_state', translate('state'), 'trim|required');
                $this->form_validation->set_rules('sslcommerz_phone', translate('phone'), 'trim|required');
            }

            if ($this->form_validation->run() !== false) {
                $stu = $this->userrole_model->getStudentDetails();
                $onlineExam = $this->userrole_model->getSingle('online_exam', $examID, true);

                $params = array(
                    'student_id' => $stu['student_id'],
                    'student_name' => $stu['fullname'],
                    'student_email' => $stu['student_email'],
                    'register_no' => $stu['register_no'],
                    'exam_id' => $onlineExam->id,
                    'amount' => $onlineExam->fee,
                    'currency' => $this->data['global_config']['currency'],
                );

                if ($payVia == 'paypal') {
                    $url = base_url("onlineexam_payment/paypal");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'stripe') {
                    $url = base_url("onlineexam_payment/stripe");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'payumoney') {
                    $payerData = array(
                        'name' => $this->input->post('payer_name'),
                        'email' => $this->input->post('email'),
                        'phone' => $this->input->post('phone'),
                    );
                    $params['payer_data'] = $payerData;
                    $url = base_url("onlineexam_payment/payumoney");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'paystack') {
                    $url = base_url("onlineexam_payment/paystack");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'razorpay') {
                    $url = base_url("onlineexam_payment/razorpay");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'sslcommerz') {
                    $params['tran_id'] = "SSLC" . uniqid();
                    $params['cus_name'] = $this->input->post('sslcommerz_name');
                    $params['cus_email'] = $this->input->post('sslcommerz_email');
                    $params['cus_address'] = $this->input->post('sslcommerz_address');
                    $params['cus_postcode'] = $this->input->post('sslcommerz_postcode');
                    $params['cus_state'] = $this->input->post('sslcommerz_state');
                    $params['cus_phone'] = $this->input->post('sslcommerz_phone');
                    $url = base_url("onlineexam_payment/sslcommerz");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'jazzcash') {
                    $url = base_url("onlineexam_payment/jazzcash");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'midtrans') {
                    $url = base_url("onlineexam_payment/midtrans");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'flutterwave') {
                    $url = base_url("onlineexam_payment/flutterwave");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'paytm') {
                    $url = base_url("onlineexam_payment/paytm");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'toyyibpay') {
                    $url = base_url("onlineexam_payment/toyyibpay");
                    $params['payer_email'] = $this->input->post('toyyibpay_email');
                    $params['payer_phone'] = $this->input->post('toyyibpay_phone');
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'payhere') {
                    $url = base_url("onlineexam_payment/payhere");
                    $params['payer_email'] = $this->input->post('toyyibpay_email');
                    $params['payer_phone'] = $this->input->post('toyyibpay_phone');
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'nepalste') {
                    $url = base_url("onlineexam_payment/nepalste");
                    $this->session->set_userdata("params", $params);
                }

                if ($payVia == 'bkash') {
                    $url = base_url("onlineexam_payment/bkash");
                    $this->session->set_userdata("params", $params);
                }

                $array = array('status' => 'success', 'url' => $url);
            } else {
                $error = $this->form_validation->error_array();
                $array = array('status' => 'fail', 'url' => '', 'error' => $error);
            }
            echo json_encode($array);
        }
    }

    public function paypal()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['paypal_username'] == "" || $config['paypal_password'] == "" || $config['paypal_signature'] == "") {
                set_alert('error', 'Paypal config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $data = array(
                    'cancelUrl' => base_url('onlineexam_payment/getsuccesspayment'),
                    'returnUrl' => base_url('onlineexam_payment/getsuccesspayment'),
                    'name' => $params['student_name'],
                    'description' => "Online Exam fees deposit. Student Register No - " . $params['register_no'],
                    'amount' => floatval($params['amount']),
                    'currency' => $params['currency'],
                );
                $response = $this->paypal_payment->payment($data);
                if ($response->isSuccessful()) {

                } elseif ($response->isRedirect()) {
                    $response->redirect();
                } else {
                    echo $response->getMessage();
                }
            }
        }
    }

    /* paypal successpayment redirect */
    public function getsuccesspayment()
    {
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            // null session data
            $this->session->set_userdata("params", "");
            $data = array(
                'name' => $params['student_name'],
                'description' => "Online Exam fees deposit. Student Register No - " . $params['register_no'],
                'amount' => floatval($params['amount']),
                'currency' => $params['currency'],
            );
            $response = $this->paypal_payment->success($data);
            $paypalResponse = $response->getData();
            if ($response->isSuccessful()) {
                $purchaseId = $_GET['PayerID'];
                if (isset($paypalResponse['PAYMENTINFO_0_ACK']) && $paypalResponse['PAYMENTINFO_0_ACK'] === 'Success') {
                    if ($purchaseId) {
                        $ref_id = $paypalResponse['PAYMENTINFO_0_TRANSACTIONID'];
                        // payment info update in invoice
                        $arrayFees = array(
                            'student_id' => $params['student_id'],
                            'exam_id' => $params['exam_id'],
                            'payment_method' => "",
                            'amount' => floatval($paypalResponse['PAYMENTINFO_0_AMT']),
                            'transaction_id' => "Fees deposits online via Paypal Ref ID: " . $ref_id,
                            'created_at' => date('Y-m-d H:i:s'),
                        );
                        $this->savePaymentData($arrayFees);
                        set_alert('success', translate('payment_successfull'));
                        redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
                    }
                }
            } elseif ($response->isRedirect()) {
                $response->redirect();
            } else {
                set_alert('error', translate('payment_cancelled'));
                redirect(base_url('userrole/online_exam'));
            }
        }
    }

    public function stripe()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['stripe_secret'] == "") {
                set_alert('error', 'Stripe config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $data = array(
                    'imagesURL' => $this->application_model->getBranchImage(get_loggedin_branch_id(), 'logo-small'),
                    'success_url' => base_url("onlineexam_payment/stripe_success?session_id={CHECKOUT_SESSION_ID}"),
                    'cancel_url' => base_url("onlineexam_payment/stripe_success?session_id={CHECKOUT_SESSION_ID}"),
                    'name' => $params['student_name'],
                    'description' => "Online Exam fees deposit. Student Register No - " . $params['register_no'],
                    'amount' => floatval($params['amount']),
                    'currency' => $params['currency'],
                );
                $response = $this->stripe_payment->payment($data);
                $data['sessionId'] = $response['id'];
                $data['stripe_publishiable'] = $config['stripe_publishiable'];
                $this->load->view('layout/stripe', $data);
            }
        }
    }

    public function stripe_success()
    {
        $sessionId = $this->input->get('session_id');
        $params = $this->session->userdata('params');
        if (!empty($sessionId) && !empty($params)) {
            try {
                $response = $this->stripe_payment->verify($sessionId);
                if (isset($response->payment_status) && $response->payment_status == 'paid') {
                    $amount = floatval($response->amount_total) / 100;
                    $ref_id = $response->payment_intent;
                    // payment info update in invoice
                    $arrayFees = array(
                        'student_id' => $params['student_id'],
                        'exam_id' => $params['exam_id'],
                        'payment_method' => "",
                        'amount' => $amount,
                        'transaction_id' => "Fees deposits online via Stripe Ref ID: " . $ref_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    );
                    $this->savePaymentData($arrayFees);
                    set_alert('success', translate('payment_successfull'));
                    redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
                } else {
                    // payment failed: display message to customer
                    set_alert('error', "Something went wrong!");
                    redirect(base_url('userrole/online_exam'));
                }
            } catch (\Exception$ex) {
                set_alert('error', $ex->getMessage());
                redirect(site_url('userrole/online_exam'));
            }
        }
    }

    public function paystack()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['paystack_secret_key'] == "") {
                set_alert('error', 'Paystack config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $result = array();
                $amount = ($params['amount'] * 100);
                $ref = app_generate_hash();
                $callback_url = base_url() . 'onlineexam_payment/verify_paystack_payment/' . $ref;
                $postdata = array('email' => $params['student_email'], 'amount' => $amount, "reference" => $ref, "callback_url" => $callback_url);
                $url = "https://api.paystack.co/transaction/initialize";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata)); //Post Fields
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                $headers = [
                    'Authorization: Bearer ' . $config['paystack_secret_key'],
                    'Content-Type: application/json',
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $request = curl_exec($ch);
                curl_close($ch);
                //
                if ($request) {
                    $result = json_decode($request, true);
                }

                $redir = $result['data']['authorization_url'];
                header("Location: " . $redir);
            }
        }
    }

    public function verify_paystack_payment($ref)
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        // null session data
        $this->session->set_userdata("params", "");
        $result = array();
        $url = 'https://api.paystack.co/transaction/verify/' . $ref;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['paystack_secret_key']]
        );
        $request = curl_exec($ch);
        curl_close($ch);
        //
        if ($request) {
            $result = json_decode($request, true);
            // print_r($result);
            if ($result) {
                if ($result['data']) {
                    //something came in
                    if ($result['data']['status'] == 'success') {
                        // payment info update in invoice
                        $arrayFees = array(
                            'student_id' => $params['student_id'],
                            'exam_id' => $params['exam_id'],
                            'payment_method' => "",
                            'amount' => $params['amount'],
                            'transaction_id' => "Fees deposits online via Paystack Ref ID: " . $ref,
                            'created_at' => date('Y-m-d H:i:s'),
                        );
                        $this->savePaymentData($arrayFees);
                        set_alert('success', translate('payment_successfull'));
                        redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));

                    } else {
                        // the transaction was not successful, do not deliver value'
                        // print_r($result);  //uncomment this line to inspect the result, to check why it failed.
                        set_alert('error', "Transaction Failed");
                        redirect(base_url('userrole/online_exam'));
                    }
                } else {
                    //echo $result['message'];
                    set_alert('error', "Transaction Failed");
                    redirect(base_url('userrole/online_exam'));
                }
            } else {
                //print_r($result);
                //die("Something went wrong while trying to convert the request variable to json. Uncomment the print_r command to see what is in the result variable.");
                set_alert('error', "Transaction Failed");
                redirect(base_url('userrole/online_exam'));
            }
        } else {
            //var_dump($request);
            //die("Something went wrong while executing curl. Uncomment the var_dump line above this line to see what the issue is. Please check your CURL command to make sure everything is ok");
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/online_exam'));
        }
    }

    /* PayUmoney Payment */
    public function payumoney()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['payumoney_key'] == "" || $config['payumoney_salt'] == "") {
                set_alert('error', 'PayUmoney config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                // api config
                if ($config['payumoney_demo'] == 1) {
                    $api_link = "https://test.payu.in/_payment";
                } else {
                    $api_link = "https://secure.payu.in/_payment";
                }
                $key = $config['payumoney_key'];
                $salt = $config['payumoney_salt'];

                // payumoney details
                $amount = floatval($params['amount']);
                $payer_name = $params['payer_data']['name'];
                $payer_email = $params['payer_data']['email'];
                $payer_phone = $params['payer_data']['phone'];
                $product_info = "Online Exam fees deposit. Student Register No - " . $params['register_no'];
                // redirect url
                $success = base_url('onlineexam_payment/payumoney_success');
                $fail = base_url('onlineexam_payment/payumoney_success');
                // generate transaction id
                $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $params['txn_id'] = $txnid;
                $this->session->set_userdata("params", $params);

                // optional udf values
                $udf1 = '';
                $udf2 = '';
                $udf3 = '';
                $udf4 = '';
                $udf5 = '';

                $hashstring = $key . '|' . $txnid . '|' . $amount . '|' . $product_info . '|' . $payer_name . '|' . $payer_email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||' . $salt;
                $hash = strtolower(hash('sha512', $hashstring));
                $data = array(
                    'salt' => $salt,
                    'key' => $key,
                    'payu_base_url' => $api_link,
                    'action' => $api_link,
                    'surl' => $success,
                    'furl' => $fail,
                    'txnid' => $txnid,
                    'amount' => $amount,
                    'firstname' => $payer_name,
                    'email' => $payer_email,
                    'phone' => $payer_phone,
                    'productinfo' => $product_info,
                    'hash' => $hash,
                );
                $this->load->view('layout/payumoney', $data);
            }
        }
    }

    /* payumoney successpayment redirect */
    public function payumoney_success()
    {
        if ($this->input->server('REQUEST_METHOD') == 'POST') {
            $params = $this->session->userdata('params');
            // null session data
            $this->session->set_userdata("params", "");
            if ($this->input->post('status') == "success") {
                $txn_id = $params['txn_id'];
                $mihpayid = $this->input->post('mihpayid');
                $transactionid = $this->input->post('txnid');
                if ($txn_id == $transactionid) {
                    // payment info update in invoice
                    $arrayFees = array(
                        'student_id' => $params['student_id'],
                        'exam_id' => $params['exam_id'],
                        'payment_method' => "",
                        'amount' => $this->input->post('amount'),
                        'transaction_id' => "Fees deposits online via PayU TXN ID: " . $txn_id . " / PayU Ref ID: " . $mihpayid,
                        'created_at' => date('Y-m-d H:i:s'),
                    );
                    $this->savePaymentData($arrayFees);

                    set_alert('success', translate('payment_successfull'));
                    redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
                } else {
                    set_alert('error', translate('invalid_transaction'));
                    redirect(base_url('userrole/online_exam'));
                }
            } else {
                set_alert('error', "Transaction Failed");
                redirect(base_url('userrole/online_exam'));
            }
        }
    }

    public function razorpay()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['razorpay_key_id'] == "" || $config['razorpay_key_secret'] == "") {
                set_alert('error', 'Razorpay config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $params['invoice_no'] = $params['register_no'];
                $params['fine'] = 0;
                $response = $this->razorpay_payment->payment($params);
                $params['razorpay_order_id'] = $response;
                $this->session->set_userdata("params", $params);
                $arrayData = array(
                    'key' => $config['razorpay_key_id'],
                    'amount' => ($params['amount'] * 100),
                    'name' => $params['student_name'],
                    'description' => "Submitting student fees online. Student ID - " . $params['student_id'],
                    'image' => base_url('uploads/app_image/logo-small.png'),
                    'currency' => 'INR',
                    'order_id' => $params['razorpay_order_id'],
                    'theme' => ["color" => "#F37254"],
                );
                $data['return_url'] = base_url('userrole/online_exam');
                $data['pay_data'] = json_encode($arrayData);
                $this->load->view('layout/razorpay', $data);
            }
        }
    }

    public function razorpay_verify()
    {
        $params = $this->session->userdata('params');
        if ($this->input->post('razorpay_payment_id')) {
            // null session data
            $this->session->set_userdata("params", "");
            $attributes = array(
                'razorpay_order_id' => $params['razorpay_order_id'],
                'razorpay_payment_id' => $this->input->post('razorpay_payment_id'),
                'razorpay_signature' => $this->input->post('razorpay_signature'),
            );
            $response = $this->razorpay_payment->verify($attributes);
            if ($response == true) {
                // payment info update in invoice
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => $params['amount'],
                    'transaction_id' => "Fees deposits online via Razorpay TxnID: " . $attributes['razorpay_payment_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);

                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
            } else {
                set_alert('error', $response);
                redirect(base_url('userrole/online_exam'));
            }
        }
    }

    public function sslcommerz()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['sslcz_store_id'] == "" || $config['sslcz_store_passwd'] == "") {
                set_alert('error', 'SSLcommerz config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {

                $post_data = array();
                $post_data['total_amount'] = floatval($params['amount']);
                $post_data['currency'] = "BDT";
                $post_data['tran_id'] = $params['tran_id'];
                $post_data['success_url'] = base_url('onlineexam_payment/sslcommerz_success');
                $post_data['fail_url'] = base_url('onlineexam_payment/sslcommerz_success');
                $post_data['cancel_url'] = base_url('onlineexam_payment/sslcommerz_success');
                $post_data['ipn_url'] = base_url() . "ipn";

                # CUSTOMER INFORMATION
                $post_data['cus_name'] = $params['cus_name'];
                $post_data['cus_email'] = $params['cus_email'];
                $post_data['cus_add1'] = $params['cus_address'];
                $post_data['cus_city'] = $params['cus_state'];
                $post_data['cus_state'] = $params['cus_state'];
                $post_data['cus_postcode'] = $params['cus_postcode'];
                $post_data['cus_country'] = "Bangladesh";
                $post_data['cus_phone'] = $params['cus_phone'];

                $post_data['product_profile'] = "non-physical-goods";
                $post_data['shipping_method'] = "No";
                $post_data['num_of_item'] = "1";
                $post_data['product_name'] = "School Fee";
                $post_data['product_category'] = "SchoolFee";

                $this->sslcommerz->RequestToSSLC($post_data);
            }
        }
    }

    /* sslcommerz successpayment redirect */
    public function sslcommerz_success()
    {
        $params = $this->session->userdata('params');
        if (($_POST['status'] == 'VALID') && ($params['tran_id'] == $_POST['tran_id'])) {
            if ($this->sslcommerz->ValidateResponse($_POST['currency_amount'], "BDT", $_POST)) {
                $tran_id = $params['tran_id'];
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => floatval($_POST['currency_amount']),
                    'transaction_id' => "Fees deposits online via SSLcommerz TXN ID: " . $tran_id,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
            }
        } else {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/online_exam'));
        }
    }

    public function jazzcash()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['jazzcash_merchant_id'] == "" || $config['jazzcash_passwd'] == "" || $config['jazzcash_integerity_salt'] == "") {
                set_alert('error', 'Jazzcash config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $integeritySalt = $config['jazzcash_integerity_salt'];
                $pp_TxnRefNo = 'T' . date('YmdHis');
                $post_data = array(
                    "pp_Version" => "2.0",
                    "pp_TxnType" => "MPAY",
                    "pp_Language" => "EN",
                    "pp_IsRegisteredCustomer" => "Yes",
                    "pp_TokenizedCardNumber" => "",
                    "pp_CustomerEmail" => "",
                    "pp_CustomerMobile" => "",
                    "pp_CustomerID" => uniqid(),
                    "pp_MerchantID" => $config['jazzcash_merchant_id'],
                    "pp_Password" => $config['jazzcash_passwd'],
                    "pp_TxnRefNo" => $pp_TxnRefNo,
                    "pp_Amount" => floatval($params['amount']) * 100,
                    "pp_DiscountedAmount" => "",
                    "pp_DiscountBank" => "",
                    "pp_TxnCurrency" => "PKR",
                    "pp_TxnDateTime" => date('YmdHis'),
                    "pp_BillReference" => uniqid(),
                    "pp_Description" => "Submitting student fees online. Student ID - " . $params['student_id'],
                    "pp_TxnExpiryDateTime" => date('YmdHis', strtotime("+1 hours")),
                    "pp_ReturnURL" => base_url('onlineexam_payment/jazzcash_success'),
                    "ppmpf_1" => "1",
                    "ppmpf_2" => "2",
                    "ppmpf_3" => "3",
                    "ppmpf_4" => "4",
                    "ppmpf_5" => "5",
                );

                $sorted_string = $integeritySalt . '&';
                $sorted_string .= $post_data['pp_Amount'] . '&';
                $sorted_string .= $post_data['pp_BillReference'] . '&';
                $sorted_string .= $post_data['pp_CustomerID'] . '&';
                $sorted_string .= $post_data['pp_Description'] . '&';
                $sorted_string .= $post_data['pp_IsRegisteredCustomer'] . '&';
                $sorted_string .= $post_data['pp_Language'] . '&';
                $sorted_string .= $post_data['pp_MerchantID'] . '&';
                $sorted_string .= $post_data['pp_Password'] . '&';
                $sorted_string .= $post_data['pp_ReturnURL'] . '&';
                $sorted_string .= $post_data['pp_TxnCurrency'] . '&';
                $sorted_string .= $post_data['pp_TxnDateTime'] . '&';
                $sorted_string .= $post_data['pp_TxnExpiryDateTime'] . '&';
                $sorted_string .= $post_data['pp_TxnRefNo'] . '&';
                $sorted_string .= $post_data['pp_TxnType'] . '&';
                $sorted_string .= $post_data['pp_Version'] . '&';
                $sorted_string .= $post_data['ppmpf_1'] . '&';
                $sorted_string .= $post_data['ppmpf_2'] . '&';
                $sorted_string .= $post_data['ppmpf_3'] . '&';
                $sorted_string .= $post_data['ppmpf_4'] . '&';
                $sorted_string .= $post_data['ppmpf_5'];

                //sha256 hash encoding
                $pp_SecureHash = hash_hmac('sha256', $sorted_string, $integeritySalt);
                $post_data['pp_SecureHash'] = $pp_SecureHash;
                if ($config['jazzcash_sandbox'] == 1) {
                    $data['api_url'] = "https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/";
                } else {
                    $data['api_url'] = "https://jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/";
                }
                $data['post_data'] = $post_data;
                $this->load->view('layout/jazzcash_pay', $data);
            }
        }
    }

    /* jazzcash successpayment redirect */
    public function jazzcash_success()
    {
        $params = $this->session->userdata('params');
        if ($_POST['pp_ResponseCode'] == '000') {
            $tran_id = $_POST['pp_TxnRefNo'];
            $arrayFees = array(
                'student_id' => $params['student_id'],
                'exam_id' => $params['exam_id'],
                'payment_method' => "",
                'amount' => floatval($params['amount']),
                'transaction_id' => "Fees deposits online via JazzCash TXN ID: " . $tran_id,
                'created_at' => date('Y-m-d H:i:s'),
            );
            $this->savePaymentData($arrayFees);
            set_alert('success', translate('payment_successfull'));
            redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
        } elseif ($_POST['pp_ResponseCode'] == '112') {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/online_exam'));
        } else {
            set_alert('error', $_POST['pp_ResponseMessage']);
            redirect(base_url('userrole/online_exam'));
        }
    }

    public function midtrans()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['midtrans_client_key'] == "" && $config['midtrans_server_key'] == "") {
                set_alert('error', 'Stripe config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $amount = number_format($params['amount'], 2, '.', '');
                $orderID = rand();
                $params['orderID'] = $orderID;
                $this->session->set_userdata("params", $params);
                $response = $this->midtrans_payment->get_SnapToken(round($amount), $orderID);
                $data['snapToken'] = $response;
                $data['midtrans_client_key'] = $config['midtrans_client_key'];
                $this->load->view('layout/midtrans', $data);
            }
        }
    }

    public function midtrans_success()
    {
        $params = $this->session->userdata('params');
        $response = json_decode($_POST['post_data']);
        if (!empty($params) && !empty($params['orderID']) && !empty($response)) {
            // null session data
            $this->session->set_userdata("params", "");
            if ($response->order_id == $params['orderID']) {
                $tran_id = $response->transaction_id;
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => floatval($params['amount']),
                    'transaction_id' => "Fees deposits online via Midtrans TXN ID: " . $tran_id,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
            } else {
                set_alert('error', "Something went wrong!");
            }
            echo json_encode(array('url' => base_url('userrole/onlineexam_take/' . $params['exam_id'])));
        }
    }

    public function flutterwave()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['flutterwave_public_key'] == "" && $config['flutterwave_secret_key'] == "") {
                set_alert('error', 'Flutter Wave config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $amount = floatval($params['amount']);
                $txref = "rsm" . app_generate_hash();
                $params['txref'] = $txref;
                $this->session->set_userdata("params", $params);
                $callback_url = base_url('onlineexam_payment/verify_flutterwave_payment');
                $data = array(
                    'student_name' => $params['student_name'],
                    'amount' => $amount,
                    'customer_email' => $params['student_email'],
                    'currency' => $params['currency'],
                    "txref" => $txref,
                    "pubKey" => $config['flutterwave_public_key'],
                    "redirect_url" => $callback_url,
                );
                $this->load->view('layout/flutterwave', $data);
            }
        }
    }

    public function verify_flutterwave_payment()
    {
        if (isset($_GET['cancelled']) && $_GET['cancelled'] == 'true') {
            set_alert('error', "Payment Cancelled");
            redirect(base_url('userrole/online_exam'));
        }

        if (isset($_GET['tx_ref'])) {
            $config = $this->get_payment_config();
            $params = $this->session->userdata('params');
            $this->session->set_userdata("params", "");
            $postdata = array(
                "SECKEY" => $config['flutterwave_secret_key'],
                "txref" => $params['txref'],
            );
            $url = 'https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata)); //Post Fields
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $headers = [
                'content-type: application/json',
                'cache-control: no-cache',
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $request = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($request, true);
            if ($result['status'] == 'success' && isset($result['data']['chargecode']) && ($result['data']['chargecode'] == '00' || $result['data']['chargecode'] == '0')) {
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => floatval($params['amount']),
                    'transaction_id' => "Fees deposits online via FlutterWave TXREF: " . $params['txref'],
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);


                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
            } else {
                set_alert('error', "Transaction Failed");
                redirect(base_url('userrole/online_exam'));
            }
        } else {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/online_exam'));
        }
    }


    //Paytm payment gateway script start
    public function paytm()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['paytm_merchantmid'] == "" && $config['paytm_merchantkey'] == "") {
                set_alert('error', 'Paytm config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $PAYTM_MERCHANT_MID = $config['paytm_merchantmid'];
                $PAYTM_MERCHANT_KEY = $config['paytm_merchantkey'];
                $PAYTM_MERCHANT_WEBSITE = $config['paytm_merchant_website'];
                $PAYTM_INDUSTRY_TYPE = $config['paytm_industry_type'];
                $transactionURL = 'https://securegw.paytm.in/theia/processTransaction'; //For Production or LIVE Credentials
                // $transactionURL = 'https://securegw-stage.paytm.in/theia/processTransaction'; //TEST Credentials

                $orderID = time();
                $paytmParams = array();
                $paytmParams['ORDER_ID'] = $orderID;
                $paytmParams['TXN_AMOUNT'] = floatval($params['amount']);
                $paytmParams["CUST_ID"] = get_loggedin_user_id();
                $paytmParams["EMAIL"] = (!empty($params['student_email']) ? $params['student_email'] : "");
                $paytmParams["MID"] = $PAYTM_MERCHANT_MID;
                $paytmParams["CHANNEL_ID"] = "WEB";
                $paytmParams["WEBSITE"] = $PAYTM_MERCHANT_WEBSITE;
                $paytmParams["CALLBACK_URL"] = base_url('onlineexam_payment/paytm_success');
                $paytmParams["INDUSTRY_TYPE_ID"] = $PAYTM_INDUSTRY_TYPE;

                $paytmChecksum = $this->paytm_kit_lib->generateSignature($paytmParams, $PAYTM_MERCHANT_MID);
                $paytmParams["CHECKSUMHASH"] = $paytmChecksum;
                $data = array();
                $data['paytmParams'] = $paytmParams;
                $data['transactionURL'] = $transactionURL;
                $this->load->view('layout/paytm', $data);
            }
        }
    }

    public function paytm_success()
    {
        $params = $this->session->userdata('params');
        $this->session->set_userdata("params", "");
        $config = $this->get_payment_config();
        $PAYTM_MERCHANT_KEY = $config['paytm_merchantkey'];
        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";
        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";
        $isValidChecksum = $this->paytm_kit_lib->verifySignature($paramList, $PAYTM_MERCHANT_KEY, $paytmChecksum);
        if ($isValidChecksum == "TRUE") {
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $tran_id = $_POST['TXNID'];
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => floatval($params['amount']),
                    'transaction_id' => "Fees deposits online via Paytm TXREF: " . $tran_id,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
            } else {
                set_alert('error', "Something went wrong!");
                redirect(base_url('userrole/online_exam'));
            }
        } else {
            set_alert('error', "Checksum mismatched.");
            redirect(base_url('userrole/online_exam'));
        }
    }

    // toyyibpay payment gateway script start
    public function toyyibpay()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['toyyibpay_secretkey'] == "" && $config['toyyibpay_categorycode'] == "") {
                set_alert('error', 'toyyibPay config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                $payment_data = array(
                    'userSecretKey' => $config['toyyibpay_secretkey'],
                    'categoryCode' => $config['toyyibpay_categorycode'],
                    'billName' => 'School Fee',
                    'billDescription' => 'Student Fee',
                    'billPriceSetting' => 1,
                    'billPayorInfo' => 1,
                    'billAmount' => floatval($params['amount']) * 100,
                    'billReturnUrl' => base_url('onlineexam_payment/toyyibpay_success'),
                    'billCallbackUrl' => base_url('onlineexam_payment/toyyibpay_callbackurl'),
                    'billExternalReferenceNo' => substr(hash('sha256', mt_rand() . microtime()), 0, 20),
                    'billTo' => $params['student_name'],
                    'billEmail' => $params['payer_email'],
                    'billPhone' => $params['payer_phone'],
                    'billSplitPayment' => 0,
                    'billSplitPaymentArgs' => '',
                    'billPaymentChannel' => '0',
                    'billContentEmail' => 'Thank you for pay online exam fees',
                    'billChargeToCustomer' => 1,
                );

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payment_data);
                $result = curl_exec($curl);
                $info = curl_getinfo($curl);
                curl_close($curl);
                $obj = json_decode($result);
                if (!empty($obj)) {
                    $url = "https://toyyibpay.com/" . $obj[0]->BillCode;
                    header("Location: $url");
                } else {
                    set_alert('error', "Transaction Failed");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public function toyyibpay_success()
    {
        $params = $this->session->userdata('params');
        if ($_GET['status_id'] == 1) {
            $this->session->set_userdata("params", "");
            set_alert('success', translate('payment_successfull'));
            redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
        } else {
            set_alert('error', "Transaction Failed");
            redirect(base_url('userrole/online_exam'));
        }
    }

    public function toyyibpay_callbackurl()
    {
        if (!empty($_POST['status']) && $_POST['status'] == 1) {
            $refno = $_POST['refno'];
            $params = $this->session->userdata('params');
            $arrayFees = array(
                'student_id' => $params['student_id'],
                'exam_id' => $params['exam_id'],
                'payment_method' => "",
                'amount' => floatval($params['amount']),
                'transaction_id' => "Fees deposits online via toyyibPay TXREF: " . $refno,
                'created_at' => date('Y-m-d H:i:s'),
            );
            $this->savePaymentData($arrayFees);
        }
    }

    // payhere payment gateway script start
    public function payhere()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['payhere_merchant_id'] == "" && $config['payhere_merchant_secret'] == "") {
                set_alert('error', 'Payhere config not available.');
                redirect($_SERVER['HTTP_REFERER']);
            } else {

                $merchantID = $config['payhere_merchant_id'];
                $orderID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $currency = 'LKR';
                $merchant_secret = $config['payhere_merchant_secret'];
                $hash = strtoupper(
                    md5(
                        $merchantID .
                        $orderID .
                        number_format($params['amount'], 2, '.', '') .
                        $currency .
                        strtoupper(md5($merchant_secret))
                    )
                );
                $paytmParams = array();
                $paytmParams['merchant_id'] = $merchantID;
                $paytmParams['return_url'] = base_url('onlineexam_payment/payhere_return');
                $paytmParams["cancel_url"] = base_url('onlineexam_payment/payhere_cancel');
                $paytmParams["notify_url"] = base_url('onlineexam_payment/payhere_notify');
                $paytmParams["order_id"] = $orderID;
                $paytmParams["items"] = "School online exam fees";
                $paytmParams["currency"] = "LKR";
                $paytmParams["amount"] = floatval($params['amount']);
                $paytmParams["first_name"] = $params['student_name'];
                $paytmParams["last_name"] = '';
                $paytmParams["email"] = $params['payer_email'];
                $paytmParams["phone"] = $params['payer_phone'];
                $paytmParams["address"] = '';
                $paytmParams["city"] = '';
                $paytmParams["country"] = 'Sri Lanka';
                $paytmParams["hash"] = $hash;
                $data['paytmParams'] = $paytmParams;
                $this->load->view('layout/payhere', $data);
            }
        }
    }

    public function payhere_notify()
    {
        if ($_POST) {
            $config = $this->get_payment_config();
            $merchant_id = $_POST['merchant_id'];
            $order_id = $_POST['order_id'];
            $payhere_amount = $_POST['payhere_amount'];
            $payhere_currency = $_POST['payhere_currency'];
            $status_code = $_POST['status_code'];
            $md5sig = $_POST['md5sig'];
            $merchant_secret = $config['payhere_merchant_secret'];
            $local_md5sig = strtoupper(
                md5(
                    $merchant_id .
                    $order_id .
                    $payhere_amount .
                    $payhere_currency .
                    $status_code .
                    strtoupper(md5($merchant_secret))
                )
            );
            if (($local_md5sig === $md5sig) && ($status_code == 2)) {
                $params = $this->session->userdata('params');
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => floatval($params['amount']),
                    'transaction_id' => "Fees deposits online via Payhere TXREF: " . $order_id,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);
            }
        }
    }

    public function payhere_cancel()
    {
        $params = $this->session->userdata('params');
        $this->session->set_userdata("params", "");
        set_alert('error', "Transaction Failed");
        redirect(base_url('userrole/online_exam'));
    }

    public function payhere_return()
    {
        set_alert('success', translate('payment_successfull'));
        redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));
    }

    public function nepalste()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['nepalste_public_key'] == "" && $config['nepalste_secret_key'] == "") {
                set_alert('error', 'Nepalste config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {

                $orderID = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
                $params['myIdentifier'] = $orderID;
                $this->session->set_userdata("params", $params);
                $parameters = [
                    'identifier' => $orderID,
                    'currency' => 'NPR',
                    'amount' => number_format($params['amount'], 2, '.', ''),
                    'details' => "Admission Fees deposits online via nepalste Student ID:" . $params['student_id'],
                    'ipn_url' => base_url('onlineexam_payment/nepalste_notify'),
                    'cancel_url' => base_url('onlineexam_payment/payhere_cancel'),
                    'success_url' => base_url('onlineexam_payment/payhere_return'),
                    'public_key' => $config['nepalste_public_key'],
                    'site_logo' => $this->application_model->getBranchImage(get_loggedin_branch_id(), 'logo-small'),
                    'checkout_theme' => 'dark',
                    'customer_name' => $params['student_name'],
                    'customer_email' => (empty($params['student_email']) ? 'john@mail.com' : $params['student_email']),
                ]; 

                //live end point
                $url = "https://nepalste.com.np/payment/initiate";
                
                /* test end point
                $url = "https://nepalste.com.np/sandbox/payment/initiate";*/

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch); 
                curl_close($ch);
                $obj = json_decode($result);
                if (!empty($obj)) {
                    $url = $obj->url;
                    header("Location: $url");
                } else {
                    set_alert('error', "Transaction Failed");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public function nepalste_notify()
    {
        if ($_POST) {
            $params = $this->session->userdata('params');
            $this->session->set_userdata("params", "");
            $config = $this->get_payment_config();

            //Receive the response parameter
            $status = $_POST['status'];
            $signature = $_POST['signature'];
            $identifier = $_POST['identifier'];
            $data = $_POST['data'];

            // Generate your signature
            $customKey = $data['amount'].$identifier;
            $secret = $config['nepalste_secret_key'];
            $mySignature = strtoupper(hash_hmac('sha256', $customKey , $secret));
            $myIdentifier = $params['myIdentifier'];
            if($status == "success" && $signature == $mySignature &&  $identifier ==  $myIdentifier) {
                // payment info update in invoice
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => $params['amount'],
                    'transaction_id' => "Fees deposits online via Paystack Order ID: " . $identifier,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);
            }
        }
    }

    public function bkash_getToken($credentials_arr = [])
    {
        $post_token = array(
            'app_key' => $credentials_arr['bkash_app_key'],
            'app_secret' => $credentials_arr['bkash_app_secret'],
        );
        $url = curl_init($credentials_arr['base_url'] . "/checkout/token/grant");
        $post_token = json_encode($post_token);
        $header = array(
            'Content-Type:application/json',
            "password:" . $credentials_arr['bkash_password'],
            "username:" . $credentials_arr['bkash_username'],
        );
        curl_setopt($url, CURLOPT_HTTPHEADER, $header);
        curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($url, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($url, CURLOPT_POSTFIELDS, $post_token);
        curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
        $result_data = curl_exec($url);
        curl_close($url);
        $response = json_decode($result_data, true);
        $this->session->set_userdata("bkash_token", $response['id_token']);
        return $response['id_token'];
    }

    public function bkash()
    {
        $config = $this->get_payment_config();
        $params = $this->session->userdata('params');
        if (!empty($params)) {
            if ($config['bkash_app_key'] == "" && $config['bkash_app_secret'] == "") {
                set_alert('error', 'bKash config not available');
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                if ($config['bkash_sandbox']) {
                    $url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'; //sandbox
                } else {
                    $url = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
                }
                $config['base_url'] = $url;

                // create Token
                $this->bkash_getToken($config);
                $post_token = array(
                    'mode' => '0011',
                    'amount' => floatval($params['amount']),
                    'payerReference' => "Admission Fees deposits online via nepalste Student ID:" . $params['student_id'],
                    'callbackURL' => base_url('onlineexam_payment/bkash_callback'),
                    'currency' => 'BDT',
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => 'INV' . rand(),
                );

                $url = curl_init($config['base_url'] . "/checkout/create");
                $post_token = json_encode($post_token);
                $header = array(
                    'Content-Type:application/json',
                    'Authorization:' . $this->session->userdata("bkash_token"),
                    'X-APP-Key:' . $config['bkash_app_key'],
                );
                curl_setopt($url, CURLOPT_HTTPHEADER, $header);
                curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($url, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($url, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($url, CURLOPT_POSTFIELDS, $post_token);
                curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
                $result_data = curl_exec($url);
                curl_close($url);

                $response = json_decode($result_data, true);
                if (isset($response['bkashURL']) && !empty($response['bkashURL'])) {
                    header("Location: " . $response['bkashURL']);
                } else {
                    set_alert('error', "Transaction Failed");
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public function bkash_callback()
    {
        if ($_GET['status'] == 'success') {
            $config = $this->get_payment_config();
            $params = $this->session->userdata('params');
            $this->session->set_userdata("params", "");

            $paymentID = $_GET['paymentID'];
            $post_token = array(
                'paymentID' => $paymentID,
            );

            if ($config['bkash_sandbox']) {
                $url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'; //sandbox
            } else {
                $url = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
            }
            $config['base_url'] = $url;

            $url = curl_init($config['base_url'] . "/checkout/execute");
            $post_token = json_encode($post_token);
            $header = array(
                'Content-Type:application/json',
                'Authorization:' . $this->session->userdata("bkash_token"),
                'X-APP-Key:' . $config['bkash_app_key'],
            );
            curl_setopt($url, CURLOPT_HTTPHEADER, $header);
            curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($url, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($url, CURLOPT_POSTFIELDS, $post_token);
            curl_setopt($url, CURLOPT_FOLLOWLOCATION, 1);
            $result_data = curl_exec($url);
            curl_close($url);
            $obj = json_decode($result_data);

            if (!empty($obj) && $obj->statusCode == '0000') {
                $arrayFees = array(
                    'student_id' => $params['student_id'],
                    'exam_id' => $params['exam_id'],
                    'payment_method' => "",
                    'amount' => floatval($params['amount']),
                    'transaction_id' => "Fees deposits online via bKash TXREF: " . $obj->trxID,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                $this->savePaymentData($arrayFees);
                set_alert('success', translate('payment_successfull'));
                redirect(base_url('userrole/onlineexam_take/' . $params['exam_id']));

            } else {
                set_alert('error', "Transaction Failed");
                redirect($_SERVER['HTTP_REFERER']);
            }
        } else {
            set_alert('error', "Something went wrong!");
            redirect(base_url('userrole/online_exam'));
        }
    }

    private function savePaymentData($data)
    {
        // insert in DB
        $this->db->insert('online_exam_payment', $data);
    }
}
