
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
			$("#densityMapScale").show();
			colors = jQuery.parseJSON(data);	
			for(id in colors)
			{
				if(id == "max")
				{				
					$("#densityMapScaleMax").text("<?php echo Kohana::lang("densitymap.max"); ?>: " + colors[id]); 
				}
				else if (id == "min")
				{				
					$("#densityMapScaleMin").text("<?php echo Kohana::lang("densitymap.min"); ?>: " + colors[id]);
				}
				else
				{ 
					var tempStyle = new OpenLayers.Style({
					  	  pointRadius: "8",
							fillColor: "#" + colors[id]["color"],
							fillOpacity: "0.6",
							strokeColor: "#" + colors[id]["color"],
							strokeWidth: 1,
							graphicZIndex: 1,
							label:colors[id]["count"],
							fontWeight: "bold",
							fontColor: "#000000",
							fontSize: "20px"
						});
					var tempStyleMap = new OpenLayers.StyleMap({"default":tempStyle});
					var geometry = This.geometries[id];
					geometry.styleMap = tempStyleMap;
					geometry.redraw();
				}
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

		
	};// end initialize method


	/**
	* creates the UI for the density map based on the existing categories UI
	*/
	this.setupUI = function(){
		var buttons = '<div id="densityMapButtonHolder"><a id="densityMap_show" class="densityMap_buttons" href="#" onclick="DensityMap.switchUI(\'DensityMap\'); return false;"> <?php echo Kohana::lang("densitymap.density_map"); ?></a>';
		buttons += '<a id="densityMap_hide" class="densityMap_buttons denstiyMapButton_active" href="#" onclick="DensityMap.switchUI(\'Dots\'); return false;"> <?php echo Kohana::lang("densitymap.dots"); ?></a></div>';
		$("#category_switch").before(buttons);
		$("#category_switch").before('<div id="densityMapCategory"></div>');

		var scale = '<div id="densityMapScale"><span id="densityMapScaleMin"></span><span id="densityMapScaleMax"></span></div>';
		$("#densityMapCategory").append("<?php echo Kohana::lang("densitymap.density_map"); ?>:"+scale);
		//copy the category list from the category_switch UL		
		$("#category_switch").clone().appendTo("#densityMapCategory");
		$("div#densityMapCategory ul li a ").each( function(index) {
			$(this).attr("id","densityMapcat_" + $(this).attr("id").substring(4));
		});
		$("div#densityMapCategory ul").each( function(index) {
			$(this).attr("id","");
		});

		$("div#densityMapCategory ul")
		
		$("div#densityMapCategory ul li div ").each( function(index) {
			$(this).attr("id","densityMapcatChild_" + $(this).attr("id").substring(6));
		});

		//asign click handlers to our newly created UI
		$("a[id^='densityMapcat_']").click(this.categoryClickHandler);
		$("#densityMapCategory").hide();
		$("#densityMapScale").hide();
	}; //end setup UI


	/***
	* Handles clicks from the UI to switch categories
	*/
	this.categoryClickHandler = function()
	{
		var catID = this.id.substring(14);
		
		//make all the other kids not active
		$("a[id^='densityMapcat_']").removeClass("active"); // Remove All active
		$("[id^='densityMapcatChild']").hide(); // Hide All Children DIV
		$("#densityMapcat_" + catID).addClass("active"); // Add Highlight
		$("#densityMapcatChild_" + catID).show(); // Show children DIV
		$(this).parents("div").show();

		if(This.initialized == undefined || This.initialized == false)
		{
			var ids = [<?php $i = 0; foreach($geometries as $geometry){$i++; if($i>1){echo",";}echo '"'.$geometry->id.'"';}?>];
			This.initialize(ids);
			This.initialized = true;
		}
		This.setCategory(catID);
				
		return false;
	};//end category click handler

	
	
}//end density map class

DensityMap.switchUI = function(whatToShow)
{
	if(whatToShow == "Dots")
	{
		$("#densityMapCategory").hide("slow");
		$("#category_switch").show("slow");
		$("#densityMap_hide").addClass("denstiyMapButton_active");
		$("#densityMap_show").removeClass("denstiyMapButton_active");
		
	}
	else // density map time
	{
		$("#densityMapCategory").show("slow");
		$("#category_switch").hide("slow");		
		$("#densityMap_show").addClass("denstiyMapButton_active");
		$("#densityMap_hide").removeClass("denstiyMapButton_active");		
	}
};// end switchUI.	


var densityMap;


/**
 * Code to run when the page loads so we can inject in some UI.
 */
 $(document).ready(function() {
	 densityMap = new DensityMap();
	 densityMap.setupUI();
	});


function testDensityMap()
{
	var ids = [<?php $i = 0; foreach($geometries as $geometry){$i++; if($i>1){echo",";}echo '"'.$geometry->id.'"';}?>];
	densityMap.initialize(ids);
	
}

function testDensityMap2()
{
	var categoryId = 1; 
	densityMap.setCategory(categoryId);

}




</script>
