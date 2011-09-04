
<script type="text/javascript"> 

//create the density map class

function DensityMap()
{
	//initialize some variables
	var This = this;
	this.initialized = false;	
	this.geometries = new Array();
	this.defaultStyle = new OpenLayers.Style({
	  	  pointRadius: "8",
				fillColor: "#aaaaaa",
				fillOpacity: "0.6",
				strokeColor: "#888888",
				strokeWidth: 2,
				graphicZIndex: 1
			});
	
	
		this.setCategoryCallBack = function(data){
	
			colors = jQuery.parseJSON(data);	
			for(id in colors)
			{
				var tempStyle = new OpenLayers.Style({
				  	  pointRadius: "8",
						fillColor: "#" + colors[id],
						fillOpacity: "0.6",
						strokeColor: "#" + colors[id],
						strokeWidth: 1,
						graphicZIndex: 1
					});
				var tempStyleMap = new OpenLayers.StyleMap({"default":tempStyle});
				var geometry = This.geometries[id];
				geometry.styleMap = tempStyleMap;
				geometry.redraw();
			}		
	};

	/** Ask the server for the break down of how a category is distributed **/
	this.setCategory = function(categoryId){
		$.get('<?php echo url::base(); ?>densitymap/get_styles/'+categoryId, this.setCategoryCallBack);
	}


	//function to initialize the density map with the layers that contain the
	//geometries of the different areas we're concerned with
	this.initialize = function(ids){
			
			/**************************************************************
			//TODO add something to indicate that we're working here;
			*****************************************************************/
			//if it's already been initialized don't do it again
			if(this.initialized)
			{
				return;
			}
						
			//loop over the geometries in the system and create layers for them
			for(id in ids)
			{
				var geometry = new OpenLayers.Layer.GML("densityMap_"+ids[id], "<?php echo url::base(); ?>densitymap/get_geometries/"+ids[id], 
			{
				format: OpenLayers.Format.GeoJSON,
				projection: map.displayProjection,
				styleMap: new OpenLayers.StyleMap({"default":this.defaultStyle})
			});
			map.addLayer(geometry);			
			this.geometries[ids[id]] = geometry;
		}
		this.initialized = true; 
	};// end initialize method
	
	
}

	


var densityMap;


function testDensityMap()
{
	densityMap = new DensityMap();
	var ids = [<?php $i = 0; foreach($geometries as $geometry){$i++; if($i>1){echo",";}echo '"'.$geometry->id.'"';}?>];

	densityMap.initialize(ids);
	
}

function testDensityMap2()
{
	var categoryId = 1; 
	densityMap.setCategory(categoryId);

}




</script>
