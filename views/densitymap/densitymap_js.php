
<script type="text/javascript"> 


function testDensityMap()
{
	// Get Current Zoom
	var currZoom = map.getZoom();

	// Get Current Center
	var currCenter = map.getCenter();
	
	//test 
	var category = 1;
	
	// Add New Layer
	addMarkers('', '', '', currZoom, currCenter, '', layerID, 'layers', '<?php echo url::base(); ?>/densitymap/get_kml/'+category, layerColor);

}

</script>
