<style type="text/css">
    #print {
        margin-bottom: 20px;
        margin-top: 0px;
        padding: 2px 15px;
        font-size: 14px;
        font-weight: 500;
    }
</style>
<!-- Main Banner Starts -->
<div class="main-banner" style="background: url(<?php echo base_url('uploads/frontend/banners/' . $page_data['banner_image']); ?>) center top;">
    <div class="container px-md-0">
        <h2><span><?php echo $page_data['page_title']; ?></span></h2>
    </div>
</div>
<!-- Main Banner Ends -->
<!-- Breadcrumb Starts -->
<div class="breadcrumb">
    <div class="container px-md-0">
        <ul class="list-unstyled list-inline">
            <li class="list-inline-item"><a href="<?php echo base_url('home'); ?>">Home</a></li>
            <li class="list-inline-item active"><?php echo $page_data['page_title']; ?></li>
        </ul>
    </div>
</div>
<div class="container px-md-0 main-container">
<div class="row">
    <div class="col-md-12">
        <div class="box2 form-box">
	        <?php
	        if($this->session->flashdata('success')) {
	            echo '<div class="alert alert-success"><i class="icon-text-ml far fa-check-circle"></i>' . $this->session->flashdata('success') . '</div>';
	        }
	        ?>
        	<div id="card_holder">
                <button type="button" class="btn btn-1" id="print"><i class="fas fa-print"></i> <?=translate('print')?></button>
                <div id="card">
				<style type="text/css">
					@media print {
						.pagebreak {
							page-break-before: always;
						}
					}
					.mark-container {
					    background: #fff;
					    width: 1000px;
					    position: relative;
					    z-index: 2;
					    margin: 0 auto;
					    padding: 20px 30px;
					}
					table {
					    border-collapse: collapse;
					    width: 100%;
					    margin: 0 auto;
					}
		            .status-list {
		                margin: 0;
		                padding: 0;
		                list-style: none;
		            }
		            .status-list li {
		                display: inline-block;
		                text-align: center;
		                border: 1px solid #ddd;
		                padding: 9px 14px;
		                border-radius: 4px;
		                font-size: 13px;
		                margin-top: 0.5rem;
		                background-color: #cff4fc;
		            }
		            .status-list li span {
		                font-weight: bold;
		                display: block;
		            }
				</style>
				<?php $getSchool = $this->db->where(array('id' => $student['branch_id']))->get('branch')->row_array(); ?>
					<div class="mark-container">
						<table border="0" style="margin-top: 20px; height: 100px;">
							<tbody>
								<tr>
									<td style="width:40%;vertical-align: top;"><img style="max-width:225px;" src="<?=$this->application_model->getBranchImage($student['branch_id'], 'report-card-logo')?>"></td>
									<td style="width:60%;vertical-align: top;">
										<table align="right" class="table-head text-right" >
											<tbody style="text-align: right;">
												<tr><th style="font-size: 26px;" class="text-right"><?=$getSchool['school_name']?></th></tr>
												<tr><td><?=$getSchool['address']?></td></tr>
												<tr><td><?=$getSchool['mobileno']?></td></tr>
												<tr><td><?=$getSchool['email']?></td></tr>
											</tbody>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
						<h4 style="padding-top: 30px">Admission Form (Student Copy)</h4>
						<ul class="status-list">
							<li>Reference No<span><?php echo empty($student['reference_no']) ? "N/A" : $student['reference_no']; ?></span></li>
							<li>Admission Status <?php
								if ($student['status'] == 1)
									$status = '<span class="text-info">' . translate('under_review') . '</span>';
								else if ($student['status']  == 2)
									$status = '<span class="text-success">' . translate('approved') . '</span>';
								else if ($student['status']  == 3)
									$status = '<span class="text-danger">' . translate('declined') . '</span>';
								echo ($status);
								?></li>
							<li>Payment Status<?php if ($student['payment_status'] == 0) { ?>
							    <span class="text-warning">Unpaid</span>
							    <?php } else if ($student['payment_status'] == 1) { ?>
							    <span class="text-success">Paid</span>
							    <?php } ?></li>
						</ul>

						<table class="table table-condensed table-bordered" style="margin-top: 20px;">
							<tbody>
								<tr>
									<th>Admission ID</td>
									<td colspan="2"><?=$student['id'] ?></td>
									<th>Apply Date</td>
									<td colspan="2"><?=_d($student['apply_date'])?></td>
								</tr>
								<tr>
									<th>Academic Session</td>
									<td><?=get_type_name_by_id('schoolyear', get_global_setting('session_id'), "school_year")?></td>
									<th>Class</td>
									<td colspan><?=$student['class_name'] ?></td>
									<th>Section</td>
									<td><?=(empty($student['section_name'])) ? "N/A" : $student['section_name'] ?></td>
								</tr>
							</tbody>
						</table>

						<table class="table table-condensed table-bordered" style="margin-top: 20px;">
							<tbody>
								<tr>
									<th>First Name</td>
									<td><?=$student['first_name']?></td>
									<th>Last Name</td>
									<td><?=$student['last_name']?></td>
									<th>Gender</td>
									<td><?=ucfirst($student['gender'])?></td>
								</tr>
								<tr>
									<th>Date of Birth</td>
									<td><?=_d($student['birthday'])?></td>
									<th>Mobile No</td>
									<td><?=$student['mobile_no'] ?></td>
									<th>Email</td>
									<td><?=$student['email']?></td>
								</tr>
								<tr>
									<th>Father Name</td>
									<td><?=(empty($student['father_name'])) ? "N/A" : $student['father_name'] ?></td>
									<th>Apply Date</td>
									<td colspan="4"><?=_d($student['apply_date'])?></td>								</tr>
								<tr>
									<th>Mother Name</td>
									<td><?=(empty($student['mother_name'])) ? "N/A" : $student['mother_name'] ?></td>
									<th>Class</td>
									<td><?=$student['class_name']?></td>
									<th>Section</td>
									<td><?=(empty($student['section_name'])) ? "N/A" : $student['section_name'] ?></td>
								</tr>
								<tr>
									<th>Present Address</td>
									<td colspan="2"><?=(empty($student['present_address'])) ? "N/A" : $student['present_address'] ?></td>
									<th>Permanent Address</td>
									<td colspan="2"><?=(empty($student['permanent_address'])) ? "N/A" : $student['permanent_address'] ?></td>
								</tr>
						<?php 
						$show_custom_fields = custom_form_table('student', $student['branch_id'], true);
						if (!empty($show_custom_fields)) {
							foreach(array_chunk($show_custom_fields, 3) as $linkGroup) { 
								$colspan = "";
								$groupNo = count($linkGroup);
								if ($groupNo < 3) {
									$colspan = " colspan= '" . (6 - $groupNo) . "'";
								} ?>
								<tr>
									<?php
										$i = 1;
										foreach($linkGroup as $link) {
									?>
									<th><?php echo $link['field_label'] ?></th>   
									<td<?php echo ($groupNo == $i ? $colspan : ''); ?>><?php echo get_online_custom_table_custom_field_value($link['id'], $student['id']);?></td>        
									<?php $i++; } ?>
								</tr>
						<?php } } ?>
							</tbody>
						</table>
						<table class="table table-condensed table-bordered" style="margin-top: 20px;">
							<tbody>
								<tr>
									<th>Relation</td>
									<td><?=(empty($student['guardian_relation'])) ? "N/A" : $student['guardian_relation'] ?></td>
									<th>Guardian Name</td>
									<td><?=(empty($student['guardian_name'])) ? "N/A" : $student['guardian_name'] ?></td>
									<th>Father Name</td>
									<td><?=(empty($student['father_name'])) ? "N/A" : $student['father_name'] ?></td>
								</tr>
								<tr>
									<th>Mother Name</td>
									<td><?=(empty($student['mother_name'])) ? "N/A" : $student['mother_name'] ?></td>
									<th>Guardian Email</td>
									<td><?=(empty($student['grd_email'])) ? "N/A" : $student['grd_email'] ?></td>
									<th>Guardian Mobile No</td>
									<td><?=(empty($student['grd_mobile_no'])) ? "N/A" : $student['grd_mobile_no'] ?></td>
								</tr>
								<tr>
									<th>Guardian Address</td>
									<td colspan="6"><?=(empty($student['grd_address'])) ? "N/A" : $student['grd_address'] ?></td>
								</tr>
							</tbody>
						</table>
						<?php if ($student['payment_status'] == 1) {
							$paymentDetails = json_decode($student['payment_details'], true);

							?>
						<h4 style="padding-top: 30px">Payment Details</h4>
						<table class="table table-condensed table-bordered" style="margin-top: 20px;">
							<tbody>
								<tr>
									<th>Paid Amount</td>
									<td><?=$student['symbol'] . " " .  $student['payment_amount'] ?></td>
									<th>Payment Method</td>
									<td colspan="2"><?=ucfirst($paymentDetails['payment_method'])?></td>
								</tr>
							</tbody>
						</table>
						<?php } ?>
					</div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('#print').on('click', function(e){
            var oContent = document.getElementById('card').innerHTML;
		    var frameDoc=window.open('', 'document-print');
		    frameDoc.document.open();
		    //create a new HTML document.
		    frameDoc.document.write('<html><head><title></title>');
		    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'assets/vendor/bootstrap/css/bootstrap.min.css">');
		    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'assets/css/custom-style.css">');
		    frameDoc.document.write('<link rel="stylesheet" href="' + base_url + 'assets/css/ramom.css">');
		    frameDoc.document.write('</head><body onload="window.print()">');
		    frameDoc.document.write(oContent);
		    frameDoc.document.write('</body></html>');
		    frameDoc.document.close();
		    setTimeout(function () {
		        frameDoc.close();      
		    }, 5000);
        });
    });
</script>




