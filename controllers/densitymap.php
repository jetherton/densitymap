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
		echo '{"type": "FeatureCollection", "features": [{"geometry": {"type": "GeometryCollection", "geometries": [';
		echo '{"type": "LineString", "coordinates": [[-12, 5], [-10, 6]]';
		echo '}, {"type": "Polygon", "coordinates": [[["-10", "5"], ["-10", "6"],'; 
		echo ' ["-11", "6"], ["-11", "5"], ["-10", "5"]]]';
		echo '},{"type":"Point", "coordinates":["15.87646484375", "44.1748046875"]}]}, "type": "Feature",';
		echo '"properties": {}}]}';
	}
}