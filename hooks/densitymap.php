<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Hooks for Density Map plugin
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

class densitymap {
	
	/**
	 * Registers the main event add method
	 */
	public function __construct()
	{	
		// Hook into routing
		Event::add('system.pre_controller', array($this, 'add'));
	}
	
	/**
	 * Adds all the events to the main Ushahidi application
	 */
	public function add()
	{
		if(Router::$controller == "main" || 
			Router::$controller == "bigmap" ||
			Router::$method == "groupmap")
		{
			Event::add('ushahidi_action.header_scripts', array($this, '_add_js'));
			plugin::add_stylesheet("densitymap/css/densitymap");
		}		
		if(Router::$controller == "reports")
		{
			Event::add('ushahidi_filter.fetch_incidents_set_params', array($this,'_add_incident_filter'));
			
			Event::add('ushahidi_action.report_filters_ui', array($this,'_add_report_filter_ui'));
			
			Event::add('ushahidi_action.header_scripts', array($this, '_add_report_filter_js'));
		}
		if(Router::$controller == "json" || Router::$controller == "densitymap") //any time the map is brought up
		{
			Event::add('ushahidi_filter.fetch_incidents_set_params', array($this,'_add_incident_filter'));
		}
	}
	
	/**
	 * This will add in the UI needed for the Density map filter
	 */
	public function _add_report_filter_js()
	{
		if (isset($_GET['dm']) AND !is_array($_GET['dm']) AND intval($_GET['dm']) >= 0)
		{

			$view = new View('densitymap/report_filter_js');
			$cat_str = "";
			//are categories involved?
			if (isset($_GET['c']) AND is_array($_GET['c']))
			{
				// Sanitize each of the category ids
				$i = 0;
				$category_ids = array();
				foreach ($_GET['c'] as $c_id)
				{
					if (intval($c_id) > 0)
					{
						$i++;
						if($i > 1){$cat_str .= ",";}
						$cat_str .= intval($c_id);
					}
				}				
			}
			$view->cat_list = $cat_str;
			$view->render(true);
		}
	}
	
	/**
	 * This will add in the UI needed for the Density map filter
	 */
	public function _add_report_filter_ui()
	{
		if (isset($_GET['dm']) AND !is_array($_GET['dm']) AND intval($_GET['dm']) >= 0)
		{
			$category = ORM::factory("category")->where("id", $_GET['dm'])->find();
			$view = new View('densitymap/report_filter_ui');
			$view->geometry_name = $category->category_title;
			$view->geometry_id = $category->id;
			$view->render(true);
		}
	}
	
	/**
	 * This method will add in some Density Map specific filtering
	 */
	public function _add_incident_filter()
	{
		//We're going to assume that the big map plugin will handle the AND / OR / Simple Groups stuff
		
		//check for the "dm" get parameter
		if (isset($_GET['dm']) AND !is_array($_GET['dm']) AND intval($_GET['dm']) >= 0)
		{
			//get the table prefix
			$table_prefix = Kohana::config('database.default.table_prefix');
			
			//get the params
			$cat_id = intval($_GET['dm']);
			$params = Event::$data;
			array_push($params,	'i.id IN (SELECT DISTINCT incident_id FROM '.$table_prefix.'incident_category WHERE category_id = '. $cat_id. ')');

			Event::$data = $params;
		}
		
	}
	
	public function _add_js()
	{
		$group_id = 0;
		//handle the pages where a map might appear for a group
		if(Router::$method == "groupmap")
		{
			$group_id = Router::$arguments[0];
		}
		
		$geometries = ORM::factory("densitymap_geometry")->find_all();
		$view = new View('densitymap/densitymap_js');
		$view->geometries = $geometries;
		$view->group_id = $group_id;
		$view->render(true);
	}
}

new densitymap;