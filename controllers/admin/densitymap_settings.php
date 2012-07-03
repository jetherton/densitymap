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



class Densitymap_settings_Controller extends Admin_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->template->this_page = 'DensityMap';

		// If user doesn't have access, redirect to dashboard
		if ( ! admin::permissions($this->user, "manage"))
		{
			url::redirect(url::site().'admin/dashboard');
		}
	}


	/**
	 * Add Edit geometrys (KML, KMZ, GeoRSS)
	 */
	public function index()
	{
		$this->template->content = new View('densitymap/settings');
		$this->template->content->title = Kohana::lang('densitymap.densitymap');

		// Setup and initialize form field names
		$form = array
		(
			'action' => '',
			'geometry_id' => '',
			'geometry_name' => '',
			//'geometry_url'	=> '',
			'kml_file' => '',
			//'geometry_color' => ''
			'label_lat' => '',
			'label_long' => '',
		);

		// Copy the form as errors, so the errors will be stored with keys corresponding to the form field names
		$errors = $form;
		$form_error = FALSE;
		$form_saved = FALSE;
		$form_action = "";
		$parents_array = array();

		// Check, has the form been submitted, if so, setup validation
		if ($_POST)
		{
			
			// Fetch the submitted data
			$post_data = array_merge($_POST, $_FILES);
			
			
			// geometry instance for the actions
			$geometry = (isset($post_data['geometry_id']) AND Densitymap_geometry_Model::is_valid_geometry($post_data['geometry_id']))
						? new Densitymap_geometry_Model($post_data['geometry_id'])
						: new Densitymap_geometry_Model();
			
			// Check for action
			if ($post_data['dm_action'] == 'a')
			{
				// Manually extract the primary geometry data
				$geometry_data = arr::extract($post_data, 'category_id', 'kml_file_old', 'label_lat', 'label_lon');
				
				// Grab the geometry file to be uploaded
				$geometry_data['kml_file'] = isset($post_data['kml_file']['name'])? $post_data['kml_file']['name'] : NULL;
				
				// Extract the geometry file for upload validation
				$other_data = arr::extract($post_data, 'kml_file');
				
				// Set up validation for the geometry file
				$post = Validation::factory($other_data)
						->pre_filter('trim', TRUE)
						->add_rules('kml_file', 'upload::valid','upload::type[kml,kmz]');
				$old_file = $geometry->kml_file;
				// Test to see if validation has passed
				if ($geometry->validate($geometry_data) AND $post->validate(false))
				{
					
					$geometry->kml_file = $old_file;					
					$geometry->category_id = $geometry_data["category_id"];					
					$geometry->label_lat = $geometry_data["label_lat"];
					$geometry->label_lon = $geometry_data["label_lon"];
					// Success! SAVE
					$geometry->save();
					
					$path_info = upload::save("kml_file");
					if ($path_info)
					{
						$path_parts = pathinfo($path_info);
						$file_name = $path_parts['filename'];
						$file_ext = $path_parts['extension'];

						if (strtolower($file_ext) == "kmz")
						{ 
							// This is a KMZ Zip Archive, so extract
							$archive = new Pclzip($path_info);
							if (TRUE == ($archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING)))
							{
								foreach ($archive_files as $file)
								{
									$ext_file_name = $file['filename'];
								}
							}

							if ($ext_file_name AND $archive->extract(PCLZIP_OPT_PATH, Kohana::config('upload.directory')) == TRUE)
							{ 
								// Okay, so we have an extracted KML - Rename it and delete KMZ file
								rename($path_parts['dirname']."/".$ext_file_name, 
									$path_parts['dirname']."/".$file_name.".kml");

								$file_ext = "kml";
								unlink($path_info);
							}
						}
						$json_file_name = $this->parse_kml($file_name, $file_ext);
						//delete the KML file
						unlink(Kohana::config('upload.directory', TRUE).$file_name.".".$file_ext);						
						$geometry->kml_file = $json_file_name; 
						$geometry->save();
						//delete old file
						if ( ! empty($old_file) AND file_exists(Kohana::config('upload.directory', TRUE).$old_file))
						{
							unlink(Kohana::config('upload.directory', TRUE) . $old_file);
						}
					}
					
					$form_saved = TRUE;
					array_fill_keys($form, '');
					$form_action = strtoupper(Kohana::lang('ui_admin.added_edited'));
				}
				else
				{
					// Validation failed

					// Repopulate the form fields
					$form = arr::overwrite($form, array_merge($geometry_data->as_array(), $post->as_array()));

					// Ropulate the error fields, if any
					$errors = arr::overwrite($errors, array_merge($geometry_data->errors('geometry'), $post->errors('geometry')));
					$form_error = TRUE;
				}
				
			}
			elseif ($post_data['dm_action'] == 'd')
			{
				// Delete action
				if ($geometry->loaded)
				{
					// Delete KMZ file if any
					$kml_file = $geometry->kml_file;
					if ( ! empty($kml_file) AND file_exists(Kohana::config('upload.directory', TRUE).$kml_file))
					{
						unlink(Kohana::config('upload.directory', TRUE) . $kml_file);
					}

					$geometry->delete();
					$form_saved = TRUE;
					$form_action = strtoupper(Kohana::lang('ui_admin.deleted'));
				}
			}			
		}

		// Pagination
		$pagination = new Pagination(array(
			'query_string' => 'page',
			'items_per_page' => $this->items_per_page,
			'total_items' => ORM::factory('densitymap_geometry')->count_all()
		));

		$geometrys = ORM::factory('densitymap_geometry')
					->orderby('id', 'asc')
					->find_all($this->items_per_page, $pagination->sql_offset);

		$this->template->content->errors = $errors;
		$this->template->content->form_error = $form_error;
		$this->template->content->form_saved = $form_saved;
		$this->template->content->form_action = $form_action;
		$this->template->content->pagination = $pagination;
		$this->template->content->total_items = $pagination->total_items;
		$this->template->content->geometrys = $geometrys;
		
		//get array of categories
		$categories = ORM::factory("category")->where("category_visible", "1")->find_all();
		$cat_array = array();
		foreach($categories as $category)
		{
			$cat_array[$category->id] = $category->category_title;
		}
		$this->template->content->cat_array = $cat_array;
		// Javascript Header
		$this->template->colorpicker_enabled = TRUE;
		$this->template->js = new View('densitymap/settings_js');
	}//end index function
	
	
	/**
	 * Turn that KML into geo json
	 * @param unknown_type $kml_file_name
	 */
	private function parse_kml($kml_file_name, $kml_file_extention)
	{
		$json_file = fopen(Kohana::config('upload.directory', TRUE).$kml_file_name.".json", "w");		
		$xml = simplexml_load_file(Kohana::config('upload.directory', TRUE).$kml_file_name.".".$kml_file_extention);
		
		
		//start up the output json
		fwrite($json_file, '{"type": "FeatureCollection", "features": [{"geometry": {"type": "GeometryCollection", "geometries": [');

		
		//go straight to the placemarks
		$placemarks = $xml->Document->Placemark;
		$areasCount = 0;
		//loop over each area
		foreach($placemarks as $placemark)
		{
			$areasCount++;
			if($areasCount > 1)
			{
				fwrite($json_file,",");
			}
			$this->parsePlacemark($placemark, $json_file);		
		}
		
		fwrite($json_file,"]}}]}");
		fclose($json_file);
		
		return $kml_file_name.".json";
		
	}//end parse_kml
	
	
	
	/**
	 * Handles one specific area
	 */
	function parsePlacemark ($placemark, $json_file)
	{
		$cumaltive_lat = 0;
		$cumaltive_lon = 0;
		$count = 0;
		
		//startup the area, it's name and points
		//fwrite($json_file, '{"type": "Polygon", "coordinates": [[');		
		
		//loop over all the polygons in the MultiGeometry
		$polygons = $placemark->MultiGeometry->Polygon;
		if(!$polygons)
		{//what if they're just using a polygon with not "multi geometry"
			$polygons = $placemark->Polygon;
		}
		$polygonCount = 0;
		foreach($polygons as $polygon)
		{
			//handle commas
			$polygonCount++;
			if($polygonCount > 1)
			{
				fwrite($json_file, ",");
			}
			
			//an array of points for a polygon
			fwrite($json_file, '{"type": "Polygon", "coordinates": [[');
			
			//get the coordinates for each polygon
			$coordinatesStr = $polygon->outerBoundaryIs->LinearRing->coordinates[0];
			//split these up on spaces. What's left should read: longitude,latitude,altitude
			//$coordinateArray = explode (" ", $coordinatesStr);
			$coordinateArray =  preg_split('/\s+/', $coordinatesStr);
			
			$tripletsCount = 0;
			//now loop over these triplets
			foreach($coordinateArray as $cordTriplet)
			{			
				$subArray = explode(",", $cordTriplet);
	
				if(count($subArray)< 2)
				{
					continue;
				}
	
				//hanlde commas
				$tripletsCount++;
				if($tripletsCount > 1)
				{
					fwrite($json_file, ",");
				}
				
				$lon = doubleval($subArray[0]);
				$lat = doubleval($subArray[1]);
				//$alt = floatval($subArray[2]);
				
				$count++;
				$cumaltive_lon = $cumaltive_lon + $lon;
				$cumaltive_lat = $cumaltive_lat + $lat;				
				fwrite($json_file, "[$lon,$lat]");
			}
			fwrite($json_file, "]]}");
						
		}	
	}//end parse placemark
	
}