<!-- Web Fonts  -->
<link href="<?php echo is_secure('fonts.googleapis.com/css?family=Signika:wght@300..700&display=swap');?>" rel="stylesheet"> 
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap/css/bootstrap.css');?>">
<?php if ($this->app_lib->isRTLenabled()) { ?>
<link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.rtl.min.css');?>">
<?php } ?>
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/font-awesome/css/all.min.css');?>">

<!-- Jquery Datatables CSS -->
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/datatables/media/css/dataTables.bootstrap.min.css');?>">

<link rel="stylesheet" href="<?php echo base_url('assets/vendor/select2/css/select2.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/select2-bootstrap-theme/select2-bootstrap.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.min.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/simple-line-icons/css/simple-line-icons.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/magnific-popup/magnific-popup.css');?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/custom-style.css?v=' . version_combine());?>">
<link rel="stylesheet" href="<?php echo base_url('assets/css/skins/default.css?v=' . version_combine());?>">
<link rel="stylesheet" href="<?php echo base_url('assets/vendor/sweetalert/sweetalert-custom.css?v=' . version_combine());?>">
<?php if ($this->app_lib->isRTLenabled()) { ?>
<link rel="stylesheet" href="<?php echo base_url('assets/css/style-rtl.css?v=' . version_combine());?>">
<?php } ?>
<!-- jquery -->
<script src="<?php echo base_url('assets/vendor/jquery/jquery.min.js?v=' . version_combine());?>"></script>
<script src="<?php echo base_url('assets/vendor/jquery-ui/jquery-ui.min.js');?>"></script>
<script src="<?php echo base_url('assets/vendor/modernizr/modernizr.js');?>"></script>