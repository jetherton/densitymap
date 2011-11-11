
<script type="text/javascript"> 

//create the density map class

function DensityMap()
{
	//initialize some variables
	var This = this;
	this.initialized = false;	
	this.geometries = new Array();
	this.controller = "<?php echo Router::$controller; ?>";
	this.showDots = true;
	this.showDensityMap = true;
	this.densityMapDisplayed = false;
	this.label_layer = null;
	//set up the filter that we will apply
	this.currentFilter = new Array();
	this.currentFilter["logicalOperator"] = "or";
	this.currentFilter["categories"] = new Array();  
	this.currentFilter["gMediaType"] = 0;
	this.simpleGroupsId = <?php echo $group_id; ?>;

	
	

	 
	this.defaultStyle = new OpenLayers.Style({
	  	  pointRadius: "8",
				fillColor: "#aaaaaa",
				fillOpacity: "0.6",
				strokeColor: "#888888",
				strokeWidth: 2,
				graphicZIndex: 1
			});

	this.labelStyle = new OpenLayers.Style({
	  	  pointRadius: "8",
				fillColor: "#aaaaaa",
				fillOpacity: "0.0",
				strokeColor: "#888888",
				strokeWidth: 2,
				strokeOpacity: "0.0",
				graphicZIndex: 1,
				label:"${count}",
				fontWeight: "bold",
				fontColor: "#000000",
				fontSize: "20px"
			});



	/**********************************************************************************************************
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

	/**********************************************************************************************************
	* Tells us if we're in Simple Groups world
	*/
	this.usingSimpleGroups = function()
	{
		if(This.simpleGroupsId == 0)
		{
			return false;
		} 
		return true;
	};

	/******************************************************************************************************
	* Handles the new data for the geometries after a change to the filter has been made
	*/
	this.setCategoryCallBack = function(data)
	{
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
						strokeColor: "#777777",
						strokeWidth: 2,
						graphicZIndex: 1
					});
				var tempStyleMap = new OpenLayers.StyleMap({"default":tempStyle});
				var geometry = This.geometries[id];
				geometry.styleMap = tempStyleMap;
				geometry.redraw();
			}
		}		
	};


	/*****************************************************************************************************************
	* This function is called when something changes to update the density map
	*/
	this.updateDensityMap = function()
	{
		var categories = "";
		for(i = 0; i < This.currentFilter["categories"].length; i++)
		{
			if(i>0)
			{
				categories += "&";
			}
			categories += "c%5B%5D=" + This.currentFilter["categories"][i]; 
		}		
		//var params = "?c=" + This.currentFilter["categories"].join(",") +
		var params = "?" + categories +
			'&s=' + This.currentFilter["startDate"] +
			'&e=' + This.currentFilter["endDate"] +
			'&m=' + This.currentFilter["gMediaType"] +
			'&lo=' + This.currentFilter["logicalOperator"];

		//let them know that we're using simple groups
		if (this.usingSimpleGroups())
		{
			params += "&sgid=" + This.simpleGroupsId;
		}

		// Destroy any open popups
		if (selectedFeature) {
			onPopupClose();
		};


		//update the geometry
		$.get('<?php echo url::base(); ?>densitymap/get_styles' + params, this.setCategoryCallBack);

		
		//get the labels
		var labelLayer = new OpenLayers.Layer.GML("densityMap_labels", "<?php echo url::base(); ?>densitymap/get_labels" + params, 
				{
					format: OpenLayers.Format.GeoJSON,
					projection: map.displayProjection,
					styleMap: new OpenLayers.StyleMap({"default":This.labelStyle})
				});
		
				if(This.label_layer != null)
				{
					map.removeLayer(This.label_layer);
				}
				This.label_layer = labelLayer;
				map.addLayer(labelLayer);				
				This.label_layer.setVisibility(This.showDensityMap);

		//now add event hanlders so we get pop ups:
		selectControl = new OpenLayers.Control.SelectFeature(labelLayer);
		map.addControl(selectControl);
		selectControl.activate();
		labelLayer.events.on({
			"featureselected": onFeatureSelect,
			"featureunselected": onFeatureUnselect
		});
		
	};

	/******************************************************************************************************************
	* Used to load stuff in one layer at a time when the density map is first initialized.
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

	/*****************************************************************************************************************
	* Handles events from the map when a new layer is added
	* This is mainly used to keep the dots turned off
	* And check for changes to the time 
	* and media filter
	*/
	this.handleAddLayerEvents = function(e)
	{
		//check to see if the time filter has changed
		if(This.currentFilter["startDate"] != $("#startDate").val() || 
				This.currentFilter["endDate"] != $("#endDate").val() ||
				This.currentFilter["gMediaType"] != gMediaType ||
				(This.currentFilter["logicalOperator"] != $("#currentLogicalOperator").val() && $("#currentLogicalOperator").val() != undefined)) 
		{
			This.currentFilter["startDate"] = $("#startDate").val(); 
			This.currentFilter["endDate"] = $("#endDate").val();
			This.currentFilter["gMediaType"] = gMediaType;
			if($("#currentLogicalOperator").val() != undefined)
			{
				This.currentFilter["logicalOperator"] = $("#currentLogicalOperator").val();
			}
			
			This.updateDensityMap();
		}

		
	    if(!$("#densityMap_show").hasClass("denstiyMapButton_active"))
	    {
		    return;
	    }					
		var reportsLayers = map.getLayersByName("Reports");
		for(id in reportsLayers)
		{		
			reportsLayers[id].setVisibility(This.showDots);
		}

		
	};

	/******************************************************************************************************************
	* function to initialize the density map with the layers that contain the
	* geometries of the different areas we're concerned with
	*/
	this.initialize = function(ids)
	{

		// Get Current Start Date
		
		This.currentFilter["startDate"] = $("#startDate").val();
		// Get Current End Date
		This.currentFilter["endDate"] = $("#endDate").val();

		
		//hook into the events on the map to make sure the dots stay hidden 
		map.events.register("addlayer", map, this.handleAddLayerEvents);
		
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
			//doesn't work in FireFox, so annoying
			//setTimeout(This.loadLayer(ids[id]), wait);
			//wait = wait + 300;
			This.loadLayer(ids[id])
		}

		//show the options
		$(".densityMap_options").show();
		//set the radio buttons
		$("input[value='densityEnabled']").attr('checked', true);
		$("input[value='dotsEnabled']").attr('checked', true);
	};// end initialize method


	/**********************************************************************************************************************
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

		
		$("div#densityMapCategory div[id^='child_']").each( function(index) {
			$(this).attr("id","densityMapcatChild_" + $(this).attr("id").substring(6));
		});

		//are we using admin map plugin map?
		//if(this.usingAdminMap())
		if(true)
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

		//if the user clicks on a dots category, turn dots on:
		$("a[id^='cat_']").click(function()
		{
			//check the dots disabled radio button
			if ($("input[name='enableDots']:checked").val() == 'dotsDisabled')
			{
				$("input#DM_disable_dots").attr('checked', false);
				$("input#DM_enable_dots").attr('checked', true);
				This.enableDotsHandler();
			}
		});
		
	}; //end setup UI

	/**
	* hanlder for turning on and off the denstiy map
	*/
	this.enableDensityHandler = function()
	{
	    if ($("input[name='enableDensity']:checked").val() == 'densityEnabled')
	    {
			This.showDensityMap = true;
	    }
	    else if ($("input[name='enableDensity']:checked").val() == 'densityDisabled')
	    {
	    	This.showDensityMap = false;
	    }
	    
	    //loop over layers and turn them off or on
	    for(id in This.geometries)
	    {
		    This.geometries[id].setVisibility(This.showDensityMap);
	    }	     
	    This.label_layer.setVisibility(This.showDensityMap);

	 	// Destroy any open popups
		if (selectedFeature) {
			onPopupClose();
		};
	}; //end enableDensityHandler

	/**
	* hanlder for turning on and off the dots
	*/
	this.enableDotsHandler = function()
	{		
	    if ($("input[name='enableDots']:checked").val() == 'dotsEnabled')
	    {
	    	This.showDots = true;
	    }
	    else if ($("input[name='enableDots']:checked").val() == 'dotsDisabled')
	    {
	    	This.showDots = false;
	    }
	    
	    
		var reportsLayers = map.getLayersByName("Reports");
		for(id in reportsLayers)
		{		
			reportsLayers[id].setVisibility(This.showDots);
		}

		// Destroy any open popups
		if (selectedFeature) {
			onPopupClose();
		};
	    
	}; //end enableDensityHandler

	/***************************************************************************************************
	* Used to remove categories from the category filter
	* If there was an Array().remove(obj) function, I wouldn't have to do this
	*/
	this.removeCategoryFilter = function(idToRemove)
	{
		var newArray = new Array();
		for(index in This.currentFilter["categories"])
		{
			var catId = This.currentFilter["categories"][index];
			if(catId != idToRemove)
			{
				newArray.push(catId);
			}
		}
		This.currentFilter["categories"] = newArray;

		//deactivate
		$("#densityMapcat_"+idToRemove).removeClass("active");
	}
	
	/*****************************************************************************************
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
					This.removeCategoryFilter(idNum);
				}
			});
			$("#densityMapcatChild_"+catID).hide();
			$(this).html("+");
			return false;
		}
	};//end drop Cat handler

	/******************************************************************************************************
	* Handles clicks from the UI to switch categories
	*/
	this.categoryClickHandler = function()
	{

		if(This.initialized == undefined || This.initialized == false)
		{
			var ids = [<?php $i = 0; foreach($geometries as $geometry){$i++; if($i>1){echo",";}echo '"'.$geometry->id.'"';}?>];
			This.initialize(ids);
			This.initialized = true;
			//etherton add code here
		}
	
		var catID = this.id.substring(14);
		if(!This.usingAdminMap()) // we are using the admin map functionality		
		{
			//make all the other kids not active
			$("a[id^='densityMapcat_']").removeClass("active"); // Remove All active
			$("[id^='densityMapcatChild_']").hide(); // Hide All Children DIV
			$("#densityMapcat_" + catID).addClass("active"); // Add Highlight
			$("#densityMapcatChild_" + catID).show(); // Show children DIV
			$(this).parents("div").show();
	
			
			This.currentFilter["categories"] = [catID];			
		}
		else //we are using the admin map functionality
		{

			
			
			//First we check if the "All Categories" button was pressed. If so unselect everything else
			if( catID == "0")
			{
				if( !$("#densityMapcat_0").hasClass("active")) //it's being activated so unselect everything else
				{
					//unselect all other selected categories
					while(This.currentFilter["categories"].length > 0)
					{
						This.removeCategoryFilter(This.currentFilter["categories"][0]);
					}
				}
			}
			else
			{ //we're dealing wtih single categories or parents
				//first check and see if we're dealing with a parent category
				if( $("#densityMapcatChild_"+catID).find('a').length > 0)
				{
			
					//we want to deactivate any kid categories.
					var kids = $("#densityMapcatChild_"+catID).find('a');
					kids.each(function(){
						if($(this).hasClass("active"))
						{
							//remove this category ID from the list of IDs to show
							var idNum = $(this).attr("id").substring(4);
							This.removeCategoryFilter(idNum);
						}
					});
				
				}//end of if for dealing with parents
				
				//check if we're dealing with a child
				if($(this).attr("cat_parent"))
				{
					//get the parent ID
					parentID = $(this).attr("cat_parent");
					//if it's active deactivate it
					//first check and see if we're adding or removing this category
					if($("#densityMapcat_"+parentID).hasClass("active")) //it is active so make it unactive and remove this category from the list of categories we're looking at.
					{ 
						This.removeCategoryFilter(parentID);
					}
					
				}//end of dealing with kids
				
				//first check and see if we're adding or removing this category
				if($("#densityMapcat_"+catID).hasClass("active")) //it is active so make it unactive and remove this category from the list of categories we're looking at.
				{ 
					This.removeCategoryFilter(catID);
				}
				else //it isn't active so make it active
				{ 
					//seems on really big maps with lots of reports we can't do more than 4 categories at a time.
					if(This.currentFilter["categories"].length < (14+1))
					{
						$("#densityMapcat_"+catID).addClass("active");
						
						//make sure the "all categories" button isn't active
						This.removeCategoryFilter("0");
						
						//add this category ID from the list of IDs to show
						This.currentFilter["categories"].push(catID);
					}
					else
					{
						alert("Sorry, do to the size and complexity of the information on this site we cannot display more than "+maxCategories+" categories at once");
					}
				}
			}
			
			
			//check to make sure something is selected. If nothing is selected then select "all gategories"		
			if( This.currentFilter["categories"].length == 0)
			{
				$("#densityMapcat_0").addClass("active");
				This.currentFilter["categories"] = [0];

			}
		}//end if we're using big map
		// Destroy any open popups
		//onPopupClose();

		//make sure the show density map is set to on if the user clicks a density map category
		if ($("input[name='enableDensity']:checked").val() == 'densityDisabled')
		{
			$("input#DM_disable_DM").attr('checked', false);
			$("input#DM_enable_DM").attr('checked', true);
			This.enableDensityHandler();
		}
		
		This.updateDensityMap();
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
		if(densityMap.initialized == false)
		{
			$("a#densityMapcat_0").click();
		}
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
