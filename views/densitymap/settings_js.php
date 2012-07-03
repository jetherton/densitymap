<?php
/**
 * Javascript view for the settings page for Density Map plugin
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
// geometrys JS
function fillFields(id, category_id, kml_file_old, lat, lon)
{
	$("#geometry_id").attr("value", unescape(id));
	$("#category_id").val(category_id);
	$("#label_lat").val(lat);
	$("#label_lon").val(lon);
	$("#kml_file_old").attr("value", unescape(kml_file_old));
}

function geometryAction ( action, confirmAction, id )
{
	var statusMessage;
	var answer = confirm('<?php echo Kohana::lang('ui_admin.are_you_sure_you_want_to'); ?> ' + confirmAction + '?')
	if (answer){
		// Set Category ID
		$("#geometry_id_action").attr("value", id);
		// Set Submit Type
		$("#dm_action").attr("value", action);		
		// Submit Form
		$("#geometryListing").submit();
	}
}