<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Performs install/uninstall methods for the Density Map plugin
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

class Densitymap_Install {

	/**
	 * Constructor to load the shared database library
	 */
	public function __construct()
	{
		$this->db = Database::instance();
	}

	/**
	 * Creates the required database tables for the smssync plugin
	 */
	public function run_install()
	{
		// Create the database tables.
		// Also include table_prefix in name
		$this->db->query('CREATE TABLE IF NOT EXISTS `'.Kohana::config('database.default.table_prefix').'densitymap_geometry` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `category_id` int(11) NOT NULL,
				  `kml_file` varchar(200) default NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1');
		
		
	}

	/**
	 * Deletes the database tables for the actionable module
	 */
	public function uninstall()
	{
	}
}