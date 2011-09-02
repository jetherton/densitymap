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
		if(Router::$controller == "main")
		{
			Event::add('ushahidi_action.header_scripts', array($this, '_add_js'));
			Event::add('ushahidi_action.map_main_filters', array($this, '_add_test_button'));
		}		
	}
	
	public function _add_js()
	{
		$view = new View('densitymap/densitymap_js');
		$view->render(true);
	}
	
	public function _add_test_button()
	{
		echo "<br/><br/><div><a href=\"#\" onclick=\"testDensityMap(); return false;\"> test density map</a></div>";
	}
}

new densitymap;