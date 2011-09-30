<script type="text/javascript">
/**
* Remove the Density Map filters from the reports query
*/
function densityMapRemoveParameterKey()
{
	delete urlParameters['dm'];
	$("#density_map_filter_checkbox").removeAttr("checked");
}

/**
* Toggle the Density Map filters from the reports query
*/
function densityMapFilterToggle(id)
{
	if(urlParameters['dm'] == undefined)
	{
		urlParameters['dm'] = id;
	}
	else
	{
		delete urlParameters['dm'];
	}
}

/**
 * Set the selected categories as selected
 */
$(document).ready(function() {

	var categories = [<?php echo $cat_list; ?>];
	for( i in categories)
	{
		if(!$("#filter_link_cat_" + categories[i]).hasClass("selected"))
		{
			$("#filter_link_cat_" + categories[i]).trigger("click");
		}
	}
	$("#reset_all_filters").click(function(){densityMapRemoveParameterKey();});
});

</script>