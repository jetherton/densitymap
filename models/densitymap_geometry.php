<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Geometry model for Density Map plugin
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   John Etherton <john@ethertontech.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module	   Density Map Installer
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */


class Densitymap_geometry_Model extends ORM_Tree
{	
	protected $has_one = array('category');
	
	// Database table name
	protected $table_name = 'densitymap_geometry';
	
}
