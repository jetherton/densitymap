<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Main controller for Density Map plugin
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   John Etherton <john@ethertontech.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   Density Map Hooks
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */



class Densitymap_Controller extends Controller
{
	
	/***********************************************************************************************************
	 * Construct things
	 * Enter description here ...
	 */
	public function __construct()
	{
		parent::__construct();
	
		$this->table_prefix = Kohana::config('database.default.table_prefix');
	}
	
	/***********************************************************************************************************
	 * return geo json of the geometry in question
	 * @param unknown_type $id
	 */
	public function get_geometries($id = false)
	{
		if(!$id)
		{
			return;
		}
		//get the geometry from the data base
		$geometry = ORM::factory("densitymap_geometry")->where("id", $id)->find();
		$json_file = Kohana::config('upload.directory', TRUE).$geometry->kml_file;
		$content = file_get_contents($json_file);
		echo $content;
	}

	/***********************************************************************************************************
	 * Private function to handle the categories parameter
	 * Enter description here ...
	 */
	private function handleCategoriesParameter()
	{
		if( isset($_GET['c']) AND ! empty($_GET['c']) )
		{
			//check if there are any ',' in the category
			if((strpos($_GET['c'], ",")===false) && is_numeric($_GET['c']))
			{
				$category_ids = array($_GET['c']);	
			}
			else
			{
				$category_ids = explode(",", $_GET['c']); //get rid of that trailing ";"
			}
		}
		else
		{
			$category_ids = array("0");
		}
		$is_all_categories = false;
		If(count($category_ids) == 0 || $category_ids[0] == '0')
		{
			$is_all_categories = true;
		}
		
		$clean_array = array();
		foreach($category_ids as $key=>$val)
		{
			$clean_array[$key] = mysql_real_escape_string($val);
		}
		
		return $clean_array;
	}
	
	/***********************************************************************************************************
	 * Gets and formats the Logical operator for us
	 * Enter description here ...
	 */
	private function handleLogicalOperatorParamter()
	{
		$logical_operator = "or";
		if (isset($_GET['lo']) AND !empty($_GET['lo']))
		{
		    $logical_operator =  $_GET['lo'];
		}
		return $logical_operator;
	}
	
	/***********************************************************************************************************
	 * Handles all the parameters that make the Where Text fun
	 * Enter description here ...
	 */
	private function handleWhereTextParamters()
	{
		$where_text = '';
		// Do we have a media id to filter by?
		if (isset($_GET['m']) AND !empty($_GET['m']) AND $_GET['m'] != '0')
		{
		    $media_type = (int) $_GET['m'];
		    $where_text .= " AND ".$this->table_prefix."media.media_type = " . $media_type;
		}

		if (isset($_GET['s']) AND !empty($_GET['s']))
		{
		    $start_date = (int) $_GET['s'];
		    $where_text .= " AND UNIX_TIMESTAMP(".$this->table_prefix."incident.incident_date) >= '" . $start_date . "'";
		}

		if (isset($_GET['e']) AND !empty($_GET['e']))
		{
		    $end_date = (int) $_GET['e'];
		    $where_text .= " AND UNIX_TIMESTAMP(".$this->table_prefix."incident.incident_date) <= '" . $end_date . "'";
		}
		
		return $where_text;
	}
	
	/***********************************************************************************************************
	 * Handles all the parameters that make the Simple Groups Plugin Work
	 * Enter description here ...
	 */
	private function handleSimpleGroupsIdParameters()
	{
		
		if (isset($_GET['sgid']) AND !empty($_GET['sgid']) AND intval($_GET['sgid']) > 0)
		{
			return intval($_GET['sgid']);
		}
		return null;
	}
	
	
	/***********************************************************************************************************
	 * Figures out what the category array should be
	 * Enter description here ...
	 * @param unknown_type $geometry
	 * @param unknown_type $category_ids
	 */
	private function get_geometry_specific_category_list($geometry_id, $category_ids)
	{
		//first check and see if we're dealing with all categories
		if(count($category_ids) == 1 AND $category_ids[0] == "0")
		{
			$category_ids[0] = $geometry_id;
			return $category_ids;
		}
		//Second check and see if the $geometry's cat is already in there
		$is_in_there = false;
		foreach($category_ids as $cat)
		{
			if($cat == $geometry_id)
			{
				$is_in_there = true;
				break;
			}
		}
		//if it isn't in there, put it in there
		if(!$is_in_there)
		{
			$category_ids[] = $geometry_id;
		}
		return $category_ids;
	}
	
	/***********************************************************************************************************
	 * Gets the counts of things for us
	 */
	private function get_counts()
	{
		//initialize some variables
		$geometries_and_counts = array();
		$logical_operator = $this->handleLogicalOperatorParamter();
		$category_ids = $this->handleCategoriesParameter();
		
		//so first we want to get a list of IDs of the categories that corespond to a ID
		$geo_where = "";
		$geometries = ORM::factory("densitymap_geometry")->find_all();		
		$i = 0;
		//also setup this look up to use later
		$cat_to_geo_id = array();
		foreach($geometries as $geometry)
		{
			$i++;
			if($i > 1)
			{$geo_where .= " OR ";}
			$geo_where .= 'ic1.category_id = '.$geometry->category_id;
			$cat_to_geo_id[$geometry->category_id] = $geometry->id;
		}
			
		
		
		//start setting up the SQL to find out what our counts will be
		$table_prefix = Kohana::config('database.default.table_prefix');
		$sql = 'select count(incident_id) as number, category_id  from (  ';
		$sql .= 'SELECT ic1.incident_id, ic1.category_id ';
		$sql .= 'FROM  `'.$table_prefix.'incident_category` AS ic1 ';
		
		$join_text = "";
		$where_text = "";		
		//ignore all this if we're looking at all categories
		if(count($category_ids) > 0 AND intval($category_ids[0]) != 0)
		{
			$i = 0;
			//handle AND / OR difference
			$operator = ' OR ';
			if($logical_operator == 'and')
			{
				$operator = ' AND ';
			}
			foreach($category_ids as $cat)
			{
			
				//we handle the geometry ID later, in a special way
				if($cat == $geometry->category_id)
				{
					continue;
				}
				
				$i++;				
				if ($i == 1)
				{$where_text  = ' AND ( ';}
				elseif($i > 1)
				{$where_text .= $operator;}
				
				$where_text .= 'ic'.($i + 1) . '.category_id = '. $cat;
				$join_text .= ' LEFT JOIN  `incident_category` AS ic'.($i + 1) . ' ON  `ic1`.incident_id =  `ic'.($i + 1) . '`.`incident_id` ';
			}
		}
		
		//make sure we close that paranthesis if need be
		if(strlen($where_text) > 0)
		{
			$where_text .= ') ';
		}
		
		$sql .= $join_text . ' LEFT JOIN `incident` AS i ON `ic1`.incident_id = `i`.id WHERE i.incident_active = 1 AND ('. $geo_where . ') ' . $where_text;
		$sql .= ' GROUP BY ic1.incident_id ) as temp GROUP BY category_id';
		
		//for debugging
		//echo $sql;	
		$db = new Database();
		$query = $db->query($sql);
		foreach($query as $q)
		{
			$geometries_and_counts[$cat_to_geo_id[$q->category_id]] = $q->number;
		}
		//make sure we have an entry for each and every geometry id
		foreach($geometries as $geo)
		{
			if(!isset($geometries_and_counts[$geo->id]))
			{
				$geometries_and_counts[$geo->id] = 0;
			}
			else
			{
			}
		}
			
		
		return $geometries_and_counts;
	} 
	
	
	
	
	/***********************************************************************************************************
	 * This will figure out the styles for the given geometries based on the
	 * occurance of reports with the dependent category in the geometry's category
	 * @param unknown_type $category_id
	 */
	public function get_styles()
	{
		$geometries_and_counts = $this->get_counts();
		
		$max = -1;
		$min = PHP_INT_MAX;

		//now loop over and figure out the max and min
		foreach($geometries_and_counts as $id=>$count)
		{
			if($count > $max)
			{
				$max = $count;
			}
			if($count < $min)
			{
				$min = $count;
			}
		}

		$delta = $max - $min;
		//now make the colors
		$results = array();
		foreach($geometries_and_counts as $id=>$count)
		{
			//RRGGBB
			if($delta != 0)
			{
				$above_min = $count - $min;
				$color_val = 255-(($above_min/$delta)*255);
				$color_str = (strlen(dechex($color_val)) == 1) ? "0".dechex($color_val) : dechex($color_val);
				$color_str = $color_str . "ff" . $color_str;
				
				$border_color_val = ($color_val > 100 ) ? $color_val - 100 : 0;
				$border_color =  (strlen(dechex($border_color_val)) == 1) ? "0".dechex($border_color_val) : dechex($border_color_val);
				$border_color = $border_color . "ff". $border_color;
				$results[$id] = array("color"=>$color_str, "count"=>$count, "border_color"=>$border_color);
			}
			else
			{
				$results[$id] = array("color"=>"ffffff", "count"=>$count, "border_color"=>"888888");
			}
		}
		$results["max"] = $max;
		$results["min"] = $min;
		echo json_encode($results);
	}
	
	
	/***********************************************************************************************************
	 * based on what's selected this returns the labels
	 */
	public function get_labels()
	{		
		$i = 0;
		$category_ids = $this->handleCategoriesParameter();
		$start_end_paramters = $this->getStartEndParametersStr();
		$simple_groups_id = $this->handleSimpleGroupsIdParameters();
		$logical_operator = $this->handleLogicalOperatorParamter();
		//get started 
		echo '{"type": "FeatureCollection","features":['; 
			
		$geometries_and_counts = $this->get_counts();
		
		$geometries = ORM::factory("densitymap_geometry")->find_all();
		foreach($geometries as $geometry)		
		{
			//get the category so we can put a name in the info window			
			//figure out what translation to show:
			// Get locale
			$l = Kohana::config('locale.language.0');
			// Check for localization of child category
			
			$display_title = Category_Lang_Model::category_title($geometry->category_id,$l);
			if($display_title == "")
			{
				$display_title = ORM::factory('category')->where('id', $geometry->category_id)->find()->category_title;
			}
			
			$count = $geometries_and_counts[$geometry->id];
			
			if($count == 0)
			{
				continue;
			}
			
			$i++;
			if($i > 1)
			{
				echo ",";
			}
			$geometry_cat_id = $geometry->category_id;			
			$cat_ids = $this->get_geometry_specific_category_list($geometry_cat_id, $category_ids);
			//make a string of the categories
			$cat_str = "";
			$i = 0;
			foreach($cat_ids as $cat_id)
			{
				$i++;
				if($i>1){$cat_str .= ",";}
				$cat_str .= '"' . $cat_id . '"';
			}
			$logical_operator_params = "";
			if($logical_operator == "and")
			{
				$logical_operator_params = "&lo=and";	
			}
			
			//Make the URL for this guy set of data
			$url = url::base()."reports/index?dm=" . $geometry_cat_id . $start_end_paramters.$logical_operator_params; 
			//handle the categories			
			foreach($category_ids as $cat_id)
			{
				//$url .= "&c%5B%5D=" . mysql_real_escape_string($cat_id);	
				$url .= "&c%5B%5D=" . $cat_id;	
			}
			if($simple_groups_id != null AND intval($simple_groups_id) != 0)
			{
				$url .= "&sgid=" . $simple_groups_id;
			}
			$fontsize = "12px";
			$radius = "10px";
			$strokewidth = "1";
			if($count > 1000)
			{
				$fontsize = "23px";
				$radius = "23px";
				$strokewidth = "22";
			}
			elseif($count > 500)
			{
				$fontsize = "20px";
				$radius = "20px";
				$strokewidth = "20";
			}
			elseif($count > 100)
			{
				$fontsize = "18px";
				$radius = "18px";
				$strokewidth = "15";
			}
			elseif($count > 10)
			{
				$fontsize = "14px";
				$radius = "14px";
				$strokewidth = "10";
			}
			elseif($count >= 2)
			{
				$fontsize = "12px";
				$radius = "12px";
				$strokewidth = "4";
			}
			
			
			
			/* START AND END TIME*/
			//is it plural or not
			
			$reportStr = ($count == 1) ? Kohana::lang("ui_main.report") : Kohana::lang("ui_main.report")."s"; 
			echo '{"type":"Feature",';  
			echo '"properties": {"name":"<a href=\''.$url.'\'> '.$display_title .':<br/>'.$count . ' ' . $reportStr. '</a>","link": "'. $url .'",';
			echo '"category":[' . $cat_str . '],'; 
			echo '"color": "CC0000", "icon": "", "thumb": "", "timestamp": "0",'; 
			echo '"count": "' . $count . '", "fontsize":"' . $fontsize.'", "radius":"'.$radius.'", "strokewidth":"'.$strokewidth.'"},"geometry":'; 
			echo '{"type":"Point", "coordinates":['.$geometry->label_lon.','.$geometry->label_lat.']}}';
		}
				
			
		echo ']}';
	}
	
	/**
	 * This hanldes all the Start End possible parameters, other than categories, that could appear here
	 * Enter description here ...
	 */
	private function getStartEndParametersStr()
	{
		$params = "";
		if (isset($_GET['s']) AND !empty($_GET['s']))
		{
		    $start_date = intval( $_GET['s']);
		    $params .= "&s=" . $start_date;
		}

		if (isset($_GET['e']) AND !empty($_GET['e']))
		{
		    $end_date = intval($_GET['e']);
		    $params .= "&e=" . $end_date;
		}
		
		return $params;
	}//end method
}//end class