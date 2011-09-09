
<script type="text/javascript"> 

//create the density map class

function DensityMap()
{
	//initialize some variables
	var This = this;
	this.initialized = false;	
	this.geometries = new Array();
	this.controller = "<?php echo Router::$controller; ?>";
	 
	this.defaultStyle = new OpenLayers.Style({
	  	  pointRadius: "8",
				fillColor: "#aaaaaa",
				fillOpacity: "0.6",
				strokeColor: "#888888",
				strokeWidth: 2,
				graphicZIndex: 1
			});

	/**
	* Tells us if we're in big map world
	*/
	this.usingAdminMap = function()
	{
		if(this.controller == "adminmap" || 
				this.controller == "bigmap" ||
				this.controller == "simplegroups")
		{
			return true;
		} 
		return false;
	};
	
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

	/***
	* Used to load stuff in one layer at a time.
	* hopefully with out freezing
	*/
	this.loadLayer = function(id)
	{
		var geometry = new OpenLayers.Layer.GML("densityMap_"+id, "<?php echo url::base(); ?>densitymap/get_geometries/"+id, 
				{
					format: OpenLayers.Format.GeoJSON,
					projection: map.displayProjection,
					styleMap: new OpenLayers.StyleMap({"default":This.defaultStyle})
				});
				map.addLayer(geometry);			
				This.geometries[id] = geometry;			
	};

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

		//we had some issues with the layers randommly not loading and I think it's because 
		//OSM isn't thread safe, so we use the wait to stagger the loading of layers.
		wait = 300;			
		//loop over the geometries in the system and create layers for them
		for(id in ids)
		{
			//this may not work in IE see http://www.lejnieks.com/2008/08/21/passing-arguments-to-javascripts-settimeout-method-using-closures/ for more info
			setTimeout(this.loadLayer(ids[id]), wait);
			wait = wait + 300;
		}

		//show the options
		$(".densityMap_options").show();
		//set the radio buttons
		$("input[value='densityEnabled']").attr('checked', true);
		$("input[value='dotsEnabled']").attr('checked', true);
	};// end initialize method


	/**
	* creates the UI for the density map based on the existing categories UI
	*/
	this.setupUI = function(){
		var buttons = '<div id="densityMapButtonHolder"><a id="densityMap_hide" class="densityMap_buttons denstiyMapButton_active" href="#" onclick="DensityMap.switchUI(\'Dots\'); return false;"> <?php echo Kohana::lang("densitymap.dots"); ?></a>';
		buttons += '<a id="densityMap_show" class="densityMap_buttons" href="#" onclick="DensityMap.switchUI(\'DensityMap\'); return false;"> <?php echo Kohana::lang("densitymap.density_map"); ?></a>';
		buttons += '<div class="densityMap_options"><table><tr>';
		buttons += '<td><?php echo Kohana::lang("densitymap.show_densitymap");?></td>';
		buttons += '<td><input type="radio" value="densityEnabled" name="enableDensity"/> <?php echo Kohana::lang("densitymap.yes");?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		buttons += '<input type="radio" value="densityDisabled" name="enableDensity"/> <?php echo Kohana::lang("densitymap.no");?></td></tr>';
		buttons += '<tr><td><?php echo Kohana::lang("densitymap.show_dots");?></td>';
		buttons += '<td><input type="radio" value="dotsEnabled" name="enableDots"/> <?php echo Kohana::lang("densitymap.yes");?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		buttons += '<input type="radio" value="dotsDisabled" name="enableDots"/> <?php echo Kohana::lang("densitymap.no");?></td></tr>';
		buttons += '</table></div>';  
		buttons += '</div>'; 
		$("#category_switch").before(buttons);
		$("#category_switch").before('<div id="densityMapCategory"></div>');

		var scale = '<div id="densityMapScale"><span id="densityMapScaleMin"></span><span id="densityMapScaleMax"></span></div>';
		$("#densityMapCategory").append("<?php echo Kohana::lang("densitymap.density_map"); ?>:"+scale);
		//copy the category list from the category_switch UL		
		$("#category_switch").clone().appendTo("#densityMapCategory");
		$("div#densityMapCategory a[id^='cat_']").each( function(index) {
			$(this).attr("id","densityMapcat_" + $(this).attr("id").substring(4));
		});
		$("div#densityMapCategory ul").each( function(index) {
			$(this).attr("id","");
		});

		$("div#densityMapCategory ul")
		
		$("div#densityMapCategory div[id^='child_']").each( function(index) {
			$(this).attr("id","densityMapcatChild_" + $(this).attr("id").substring(6));
		});

		//are we using admin map plugin map?
		if(this.usingAdminMap())
		{
			$("div#densityMapCategory a[id^='drop_cat_']").each( function(index) {
				$(this).attr("id","densityMap_drop_cat_" + $(this).attr("id").substring(9));
			});	
		}

		//asign click handlers to our newly created UI
		$("a[id^='densityMapcat_']").click(this.categoryClickHandler);
		$("a[id^='densityMap_drop_cat_']").click(this.dropCatHandler);

		$("input[name='enableDensity']").change(this.enableDensityHandler);

		$("input[name='enableDots']").change(this.enableDotsHandler);


		//hide some things
		$("#densityMapCategory").hide();
		$("#densityMapScale").hide();
	}; //end setup UI

	/**
	* hanlder for turning on and off the denstiy map
	*/
	this.enableDensityHandler = function()
	{
		var visible;
	    if ($("input[name='enableDensity']:checked").val() == 'densityEnabled')
	    {
			visible = true;
	    }
	    else if ($("input[name='enableDensity']:checked").val() == 'densityDisabled')
	    {
	    	visible = false;
	    }
	    
	    //loop over layers and turn them off
	    for(id in This.geometries)
	    {
		    This.geometries[id].setVisibility(visible);
	    }	     
	}; //end enableDensityHandler

	/**
	* hanlder for turning on and off the dots
	*/
	this.enableDotsHandler = function()
	{
		var visible;
	    if ($("input[name='enableDots']:checked").val() == 'dotsEnabled')
	    {
			visible = true;
	    }
	    else if ($("input[name='enableDots']:checked").val() == 'dotsDisabled')
	    {
	    	visible = false;
	    }
	    
	    //get the dots layer and turn it off
	    //for(id in map.layers)
	    //{
		//    console.log(id + " " + map.layers[id].id);
	    //}
		var reportsLayers = map.getLayersByName("Reports");
		for(id in reportsLayers)
		{		
			reportsLayers[id].setVisibility(visible);
		}
	    
	}; //end enableDensityHandler

	
	/**
	* handles clicks to he dropCat, only for admin map variants
	*/
	this.dropCatHandler = function()
	{
		//get the ID of the category we're dealing with
		var catID = this.id.substring(20);

		//if the kids aren't currenlty shown, show them
		if( !$("#densityMapcatChild_"+catID).is(":visible"))
		{
			$("#densityMapcatChild_"+catID).show();
			$(this).html("-");
			//since all we're doing is showing things we don't need to update the map
			// so just bounce
			
			$("a[id^='densityMapcatChild_']").addClass("forceRefresh"); //have to do this because IE sucks
			$("a[id^='densityMapcatChild_']").removeClass("forceRefresh"); //have to do this because IE sucks
			
			return false;
		}
		else //kids are shown, deactivate them.
		{
			var kids = $("#densityMapcatChild_"+catID).find('a');
			kids.each(function(){
				if($(this).hasClass("active"))
				{
					//remove this category ID from the list of IDs to show
					var idNum = $(this).attr("id").substring(4);
					currentCat = removeCategoryFilter(idNum, currentCat);
				}
			});
			$("#densityMapcatChild_"+catID).hide();
			$(this).html("+");
			return false;
		}
	};//end drop Cat handler

	/***
	* Handles clicks from the UI to switch categories
	*/
	this.categoryClickHandler = function()
	{
		var catID = this.id.substring(14);
		
		//make all the other kids not active
		$("a[id^='densityMapcat_']").removeClass("active"); // Remove All active
		$("[id^='densityMapcatChild_']").hide(); // Hide All Children DIV
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




</script>
