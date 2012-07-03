<?php 
/**
 * View for the settings page for Density Map plugin
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   John Etherton <john@ethertontech.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
?>
			<div class="bg">
				<h2>
					<?php echo Kohana::lang("densitymap.density_map_settings"); ?>
				</h2>
				<?php
				if ($form_error) {
				?>
					<!-- red-box -->
					<div class="red-box">
						<h3><?php echo Kohana::lang('ui_main.error');?></h3>
						<ul>
						<?php
						foreach ($errors as $error_item => $error_description)
						{
							// print "<li>" . $error_description . "</li>";
							print (!$error_description) ? '' : "<li>" . $error_description . "</li>";
						}
						?>
						</ul>
					</div>
				<?php
				}

				if ($form_saved) {
				?>
					<!-- green-box -->
					<div class="green-box">
						<h3><?php echo Kohana::lang('densitymap.geometry_has_been');?> <?php echo $form_action; ?>!</h3>
					</div>
				<?php
				}
				?>
				<!-- report-table -->
				<div class="report-form">
								<!-- tabs -->
				<div class="tabs">
					<!-- tabset -->
					<a name="add"></a>
					<ul class="tabset">
						<li><a href="#" class="active"><?php echo Kohana::lang('ui_main.add_edit');?></a></li>
					</ul>
					<!-- tab -->
					<div class="tab">
						<?php print form::open(NULL,array('enctype' => 'multipart/form-data', 
							'id' => 'geometryMain', 'name' => 'geometryMain')); ?>
						<input type="hidden" id="geometry_id" 
							name="geometry_id" value="" />
						<input type="hidden" name="dm_action" 
							id="dm_action" value="a"/>
						<input type="hidden" name="kml_file_old" 
							id="kml_file_old" value=""/>
						<div class="tab_form_item">
							<strong><?php echo Kohana::lang('ui_main.category');?>:</strong><br />
							<?php print form::dropdown('category_id',$cat_array); ?>
						</div>
						<div class="tab_form_item">
							<strong><?php echo Kohana::lang('densitymap.label_lat');?>:</strong><br />
							<?php print form::input('label_lat', ''); ?>
						</div>
						<div class="tab_form_item">
							<strong><?php echo Kohana::lang('densitymap.label_lon');?>:</strong><br />
							<?php print form::input('label_lon', ''); ?>
						</div>
						<div class="tab_form_item">
							<strong><?php echo Kohana::lang('ui_main.kml_kmz_upload');?>:</strong><br />
							<?php print form::upload('kml_file', '', ''); ?>
						</div>
						<div style="clear:both"></div>
						<div class="tab_form_item">
							&nbsp;<br />
							<input type="image" src="<?php echo url::file_loc('img'); ?>media/img/admin/btn-save.gif" class="save-rep-btn" />
						</div>
						<?php print form::close(); ?>			
					</div>
				</div>
				
					<?php print form::open(NULL,array('id' => 'geometryListing',
					 	'name' => 'geometryListing')); ?>
						<input type="hidden" name="dm_action" id="dm_action" value="">
						<input type="hidden" name="geometry_id" id="geometry_id_action" value="">
						<div class="table-holder">
							<table class="table">
								<thead>
									<tr>
										<th class="col-1">&nbsp;</th>
										<th class="col-2"><?php echo Kohana::lang('ui_main.category');?></th>
										<th class="col-3"><?php echo Kohana::lang('densitymap.label_lat_lon');?></th>
										<th class="col-4"><?php echo Kohana::lang('ui_main.actions');?></th>
									</tr>
								</thead>
								<tfoot>
									<tr class="foot">
										<td colspan="4">
											<?php echo $pagination; ?>
										</td>
									</tr>
								</tfoot>
								<tbody>
									<?php
									if ($total_items == 0)
									{
									?>
										<tr>
											<td colspan="4" class="col">
												<h3><?php echo Kohana::lang('ui_main.no_results');?></h3>
											</td>
										</tr>
									<?php	
									}
									foreach ($geometrys as $geometry)
									{
										$geometry_id = $geometry->id;
										$category_id = $geometry->category_id;
										$kml_file = $geometry->kml_file;
										$lon = $geometry->label_lon;
										$lat = $geometry->label_lat;
										?>
										<tr>
											<td class="col-1">&nbsp;</td>
											<td class="col-2">
												<div class="post">
													<h4><?php echo isset($cat_array[$category_id]) ? $cat_array[$category_id] : "--CATEGORY MISSING--" ; ?></h4>
												</div>
												<ul class="info">
													<?php
													if($kml_file)
													{
														?><li class="none-separator"><?php echo Kohana::lang('ui_main.kml_kmz_file');?>: <strong><?php echo $kml_file; ?></strong>
														
														<?php
													}
													?>
												</ul>
											</td>
											<td class="col-3">
												<?php echo $lat. ", ". $lon; ?>
											</td>
											<td class="col-4">
												<ul>
													<li class="none-separator"><a href="#add" onClick="fillFields('<?php echo(rawurlencode($geometry_id)); ?>','<?php echo(rawurlencode($category_id)); ?>','<?php echo(rawurlencode($kml_file)); ?>', '<?php echo(rawurlencode($lat));?>', '<?php echo(rawurlencode($lon)); ?>')"><?php echo Kohana::lang('ui_main.edit');?></a></li>													
													<li><a href="javascript:geometryAction('d','DELETE','<?php echo(rawurlencode($geometry_id)); ?>')" class="del"><?php echo Kohana::lang('ui_main.delete');?></a></li>
												</ul>
											</td>
										</tr>
										<?php									
									}
									?>
								</tbody>
							</table>
						</div>
					<?php print form::close(); ?>
				</div>
				
			</div>
