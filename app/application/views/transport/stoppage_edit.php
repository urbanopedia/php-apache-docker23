<section class="panel">
	<div class="tabs-custom">
		<ul class="nav nav-tabs">
			<li>
				<a href="<?=base_url('transport/stoppage')?>"><i class="fas fa-list-ul"></i> <?=translate('stoppage_list')?></a>
			</li>
			<li class="active">
				<a href="#edit" data-toggle="tab" ><i class="far fa-edit"></i> <?=translate('edit_stoppage')?></a>
			</li>
		</ul>
		<div class="tab-content">
			<div class="tab-pane active" id="edit">
				<?php echo form_open($this->uri->uri_string(), array('class' => 'form-horizontal form-bordered frm-submit'));
				echo form_hidden('stoppage_id', $stoppage['id']);
				?>
				<?php if (is_superadmin_loggedin()): ?>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('branch')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<?php
							$arrayBranch = $this->app_lib->getSelectList('branch');
							echo form_dropdown("branch_id", $arrayBranch, $stoppage['branch_id'], "class='form-control' id='branch_id'
							data-plugin-selectTwo data-width='100%' data-minimum-results-for-search='Infinity'");
						?>
						<span class="error"></span>
					</div>
				</div>
				<?php endif; ?>
				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('stoppage')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<input type="text" class="form-control" name="stop_position" value="<?=$stoppage['stop_position']?>" />
						<span class="error"></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('stop_time')?> <span class="required">*</span></label>
					<div class="col-md-6">
						<input type="text" class="form-control" name="stop_time" value="<?=$stoppage['stop_time']?>" data-plugin-timepicker />
						<span class="error"></span>
					</div>
				</div>

				<div class="form-group">
					<label class="col-md-3 control-label"><?=translate('route_fare')?> <span class="required">*</span></label>
					<div class="col-md-6 mb-md">
						<input type="text" class="form-control" name="route_fare" value="<?=$stoppage['route_fare']?>" />
						<span class="error"></span>
					</div>
				</div>
				
				<footer class="panel-footer">
					<div class="row">
						<div class="col-md-offset-3 col-md-2">
							<button type="submit" class="btn btn-default btn-block" data-loading-text="<i class='fas fa-spinner fa-spin'></i> Processing">
								<i class="fas fa-plus-circle"></i> <?=translate('update')?>
							</button>
						</div>
					</div>
				</footer>
				<?php echo form_close();?>
			</div>
		</div>
	</div>
</section>