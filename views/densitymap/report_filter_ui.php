<h3>
	<a href="#" class="small-link-button f-clear reset" onclick="densityMapRemoveParameterKey('dm', 'fl-densityMap');">
		<?php echo Kohana::lang('ui_main.clear'); ?>
	</a>
	<a class="f-title" href="#"><?php echo Kohana::lang('densitymap.density_map'); ?></a>
</h3>
<div class="f-densityMap-box">
	<ul class="filter-list fl-densityMap">
		<li>
			<?php echo $geometry_name . " "; print form::checkbox('density_map_filter_checkbox', $geometry_id, TRUE, "onchange='densityMapFilterToggle(".$geometry_id.");'");?>
		</li>
	</ul>
</div>