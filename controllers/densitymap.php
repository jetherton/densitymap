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
			if (is_array($_GET['c']))
			{
				$category_ids = $_GET['c'];	
			}			
			elseif((strpos($_GET['c'], ",")===false) && is_numeric($_GET['c']))
			{
				$category_ids = $_GET['c'];
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
	
		//loop through each of the geometries and see how many reports fall under both the geometry category and
		//the dependent
		$geometries = ORM::factory("densitymap_geometry")->find_all();
		$geometries_and_counts = array();
		foreach($geometries as $geometry)
		{
			$_GET['dm']=$geometry->category_id;
			$reports = reports::fetch_incidents();			          
				
			$geometries_and_counts[$geometry->id] = count($reports);
		}//end foreach loop over all the geometries
		
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
			elseif($max == 0) //if it's all zeros return white
			{
				$results[$id] = array("color"=>"ffffff", "count"=>$count, "border_color"=>"888888");
			}
			else //if it's all the same non-zero value, return green
			{
				$results[$id] = array("color"=>"00ff00", "count"=>$count, "border_color"=>"888888");
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
		
		//get started 
		echo '{"type": "FeatureCollection","features":['; 
			
		$geometries_and_counts = $this->get_counts();
		
		$geometries = ORM::factory("densitymap_geometry")->find_all();
		foreach($geometries as $geometry)
		{
			$i++;
			if($i > 1)
			{
				echo ",";
			}
			
			$geometry_cat_id = $geometry->category_id;			
			$count = $geometries_and_counts[$geometry->id];
			
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
		
			$url = 'reports/index?dm=' . $geometry_cat_id .'&' . $_SERVER['QUERY_STRING'];
			/* START AND END TIME*/
			//is it plural or not
			$reportStr = ($count == 1) ? Kohana::lang("ui_main.report") : Kohana::lang("ui_main.report")."s"; 
			echo '{"type":"Feature",';  
			echo '"properties": {"name":"<a href=\''.$url.'\'> '.$count . ' ' . $reportStr. '</a>","link": "'. $url .'",';
			echo '"category":[' . $cat_str . '],'; 
			echo '"color": "CC0000", "icon": "", "thumb": "", "timestamp": "0",'; 
			echo '"count": "' . $count . '"},"geometry":'; 
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