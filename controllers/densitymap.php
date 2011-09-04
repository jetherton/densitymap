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
	
	/**
	 * This will figure out the styles for the given geometries based on the
	 * occurance of reports with the dependent category in the geometry's category
	 * @param unknown_type $category_id
	 */
	public function get_styles($category_id)
	{
		//loop through each of the geometries and see how many reports fall under both the geometry category and
		//the dependent 
		$geometries = ORM::factory("densitymap_geometry")->find_all();
		$geometries_and_counts = array();
		foreach($geometries as $geometry)
		{
			$mappings = ORM::factory("incident_category")
				->where("incident_category.category_id = ". $geometry->category_id." OR incident_category.category_id = ". $category_id)
				->orderby("incident_id", "ASC")
				->find_all();
				
			//now loop over these and see where there was an actual match and create the count
			$count = 0;
			$last_report_id = null;
			foreach($mappings as $mapping)
			{
				//if there are to mappings with the same incident_id then we made a positive hit
				if($mapping->incident_id == $last_report_id)
				{
					$count++;
				}
				$last_report_id = $mapping->incident_id;
			}
			$geometries_and_counts[$geometry->id] = $count;
		}//end of looping over all the geometries
		
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
			$above_min = $count - $min;
			$color_val = 255-(($above_min/$delta)*255);
			$color_str = (strlen(dechex($color_val)) == 1) ? "0".dechex($color_val) : dechex($color_val); 
			$color_str = $color_str . "ff" . $color_str;
			$results[$id] = $color_str;
		}
		echo json_encode($results);
	}
}