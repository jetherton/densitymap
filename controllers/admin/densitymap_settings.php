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
	 * Add Edit Layers (KML, KMZ, GeoRSS)
	 */
	public function index()
	{
		$this->template->content = new View('densitymap/settings');
		$this->template->content->title = Kohana::lang('densitymap.densitymap');

		// Setup and initialize form field names
		$form = array
		(
			'action' => '',
			'layer_id' => '',
			'layer_name' => '',
			'layer_url'	=> '',
			'layer_file' => '',
			'layer_color' => ''
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
			
			// Layer instance for the actions
			$layer = (isset($post_data['layer_id']) AND Layer_Model::is_valid_layer($post_data['layer_id']))
						? new Layer_Model($post_data['layer_id'])
						: new Layer_Model();
						
			// Check for action
			if ($post_data['action'] == 'a')
			{
				// Manually extract the primary layer data
				$layer_data = arr::extract($post_data, 'layer_name', 'layer_color', 'layer_url', 'layer_file_old');
				
				// Grab the layer file to be uploaded
				$layer_data['layer_file'] = isset($post_data['layer_file']['name'])? $post_data['layer_file']['name'] : NULL;
				
				// Extract the layer file for upload validation
				$other_data = arr::extract($post_data, 'layer_file');
				
				// Set up validation for the layer file
				$post = Validation::factory($other_data)
						->pre_filter('trim', TRUE)
						->add_rules('layer_file', 'upload::valid','upload::type[kml,kmz]');
				
				// Test to see if validation has passed
				if ($layer->validate($layer_data) AND $post->validate())
				{
					// Success! SAVE
					$layer->save();
					
					$path_info = upload::save("layer_file");
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

						$layer->layer_file = $file_name.".".$file_ext;
						$layer->save();
					}
					
					$form_saved = TRUE;
					array_fill_keys($form, '');
					$form_action = strtoupper(Kohana::lang('ui_admin.added_edited'));
				}
				else
				{
					// Validation failed

					// Repopulate the form fields
					$form = arr::overwrite($form, array_merge($layer_data->as_array(), $post->as_array()));

					// Ropulate the error fields, if any
					$errors = arr::overwrite($errors, array_merge($layer_data->errors('layer'), $post->errors('layer')));
					$form_error = TRUE;
				}
				
			}
			elseif ($post_data['action'] == 'd')
			{
				// Delete action
				if ($layer->loaded)
				{
					// Delete KMZ file if any
					$layer_file = $layer->layer_file;
					if ( ! empty($layer_file) AND file_exists(Kohana::config('upload.directory', TRUE).$layer_file))
					{
						unlink(Kohana::config('upload.directory', TRUE) . $layer_file);
					}

					$layer->delete();
					$form_saved = TRUE;
					$form_action = strtoupper(Kohana::lang('ui_admin.deleted'));
				}
			}
			elseif ($post_data['action'] == 'v')
			{
				// Show/Hide Action
				if ($layer->loaded == TRUE)
				{
					$layer->layer_visible =  ($layer->layer_visible == 1)? 0 : 1;
					$layer->save();
					
					$form_saved = TRUE;
					$form_action = strtoupper(Kohana::lang('ui_admin.modified'));
				}
			}
			elseif ($post_data['action'] == 'i')
			{
				// Delete KML/KMZ action
				if ($layer->loaded == TRUE)
				{
					$layer_file = $layer->layer_file;
					if ( ! empty($layer_file) AND file_exists(Kohana::config('upload.directory', TRUE).$layer_file))
					{
						unlink(Kohana::config('upload.directory', TRUE) . $layer_file);
					}

					$layer->layer_file = null;
					$layer->save();
					
					$form_saved = TRUE;
					$form_action = strtoupper(Kohana::lang('ui_admin.modified'));
				}
			}
		}

		// Pagination
		$pagination = new Pagination(array(
			'query_string' => 'page',
			'items_per_page' => $this->items_per_page,
			'total_items' => ORM::factory('layer')->count_all()
		));

		$layers = ORM::factory('layer')
					->orderby('layer_name', 'asc')
					->find_all($this->items_per_page, $pagination->sql_offset);

		$this->template->content->errors = $errors;
		$this->template->content->form_error = $form_error;
		$this->template->content->form_saved = $form_saved;
		$this->template->content->form_action = $form_action;
		$this->template->content->pagination = $pagination;
		$this->template->content->total_items = $pagination->total_items;
		$this->template->content->layers = $layers;

		// Javascript Header
		$this->template->colorpicker_enabled = TRUE;
		$this->template->js = new View('densitymap/settings_js');
	}//end index function
	
}