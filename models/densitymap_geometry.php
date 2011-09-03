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
	
	
	/** 
	 * Validates and optionally saves a new geometry record from an array
	 * 
	 * @param array $array Values to check
	 * @param bool $save Saves the record when validation succeeds
	 * @return bool
	 */
	public function validate(array & $array, $save = FALSE)
	{
		// Set up validation
		$array = Validation::factory($array)
				->pre_filter('trim');
		
		// Add callbacks for the layer url and layer file
		$array->add_callbacks('kml_file', array($this, 'file_check'));
		
		// Pass validation to parent and return
		return parent::validate($array, $save);
	}
	
	
	/**
	 * Performs validation checks on the geometry file - Checks that at least
	 * one of them has been specified using the applicable validation rules
	 *
	 * @param Validation $array Validation object containing the field names to be checked
	 */
	public function file_check(Validation $array)
	{
		// Ensure at least a geometry URL or geometry file has been specified
		if (empty($array->kml_file) AND empty($array->kml_file_old))
		{
			$array->add_error('geometry_url', 'atleast');
		}
	}
	
	
	/**
	 * Checks if the specified geometry id is a valid integer and exists in the database
	 *
	 * @param int $geometry_id 
	 * @return bool
	 */
	public static function is_valid_geometry($geometry_id)
	{
		return (intval($geometry_id) > 0)
				? self::factory('densitymap_geometry', intval($geometry_id))->loaded
				: FALSE;
	}
	
}
