
<script type="text/javascript"> 


var mymarkers;

function testDensityMap2()
{
	var style3 = new OpenLayers.Style({
  	  pointRadius: "8",
			fillColor: "#30E9FF",
			fillOpacity: "0.7",
			strokeColor: "#197700",
			strokeWidth: 3,
			graphicZIndex: 1
		});
	
	mymarkers.styleMap =  new OpenLayers.StyleMap({"default":style3});
	mymarkers.redraw();
}

function testDensityMap()
{

          var style2 = new OpenLayers.Style({
				pointRadius: "8",
				fillColor: "#30E900",
				fillOpacity: "0.7",
				strokeColor: "#197700",
				strokeWidth: 3,
				graphicZIndex: 1
			});

          var style3 = new OpenLayers.Style({
        	  pointRadius: "8",
				fillColor: "#30E9FF",
				fillOpacity: "0.7",
				strokeColor: "#197700",
				strokeWidth: 3,
				graphicZIndex: 1
			});
          
          mymarkers = new OpenLayers.Layer.GML("single report", "<?php echo url::base(); ?>densitymap/get_geometries", 
      			{
      				format: OpenLayers.Format.GeoJSON,
      				projection: map.displayProjection,
      				styleMap: new OpenLayers.StyleMap({"default":style3})
      			});
      			
      			map.addLayer(mymarkers);

      	mymarkers.styleMap =  new OpenLayers.StyleMap({"default":style2});

}

</script>
