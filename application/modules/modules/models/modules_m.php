<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Modules model
 *
 * @author 		PyroCMS Development Team
 * @package 	PyroCMS
 * @subpackage 	Modules
 * @category	Modules
 * @since 		v0.9.7
 */
class Modules_m extends MY_Model
{
	private $_table = 'modules';

	/**
	 * Constructor method
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('modules/module');
	}

	/**
	 * Get
	 *
	 * Return an array containing module data
	 *
	 * @access	public
	 * @param	string	$module		The name of the module to load
	 * @return	array
	 */
	public function get($module = '')
	{
		// Have to return an associative array of NULL values for backwards compatibility.
		$null_array = array(
			'name' => NULL,
			'slug' => NULL,
			'version' => NULL,
			'type' => NULL,
			'description' => NULL,
			'skip_xss' => NULL,
			'is_frontend' => NULL,
			'is_backend' => NULL,
			'is_backend_menu' => NULL,
			'controllers' => NULL,
			'enabled' => 1,
			'is_core' => NULL
		);

		if (is_array($module) || empty($module))
		{
			return $null_array;
		}


		$this->db->where(array('slug' => $module));
		$result = $this->db->get($this->_table)->row();

		if (!empty($result))
		{
			// Return FALSE if the module is disabled
			if ($result->enabled == 0)
			{
				return FALSE;
			}

			$descriptions = unserialize($result->description);
			if (!isset($descriptions[CURRENT_LANGUAGE]))
			{
				$description = $descriptions['en'];
			} else
			{
				$description = $descriptions[CURRENT_LANGUAGE];
			}

			$names = unserialize($result->name);
			if (!isset($names[CURRENT_LANGUAGE]))
			{
				$name = $names['en'];
			} else
			{
				$name = $names[CURRENT_LANGUAGE];
			}

			return array(
				'name' => $name,
				'slug' => $result->slug,
				'version' => $result->version,
				'type' => $result->type,
				'description' => $description,
				'skip_xss' => $result->skip_xss,
				'is_frontend' => $result->is_frontend,
				'is_backend' => $result->is_backend,
				'is_backend_menu' => $result->is_backend_menu,
				'controllers' => unserialize($result->controllers),
				'enabled' => $result->enabled,
				'is_core' => $result->is_core
			);
		}

		return $null_array;
	}

	/**
	 * Add
	 *
	 * Adds a module to the database
	 *
	 * @access	public
	 * @param	array	$module		Information about the module
	 * @return	object
	 */
	public function add($module)
	{
		return $this->db->insert($this->_table, array(
			'name' => serialize($module['name']),
			'slug' => $module['slug'],
			'version' => $module['version'],
			'type' => $module['type'],
			'description' => serialize($module['description']),
			'skip_xss' => $module['skip_xss'],
			'is_frontend' => $module['is_frontend'],
			'is_backend' => $module['is_backend'],
			'is_backend_menu' => $module['is_backend_menu'],
			'controllers' => serialize($module['controllers']),
			'enabled' => $module['enabled'],
			'is_core' => $module['is_core']
		));
	}

	/**
	 * Update
	 *
	 * Updates a module in the database
	 *
	 * @access	public
	 * @param	array	$slug		Module slug to update
	 * @param	array	$module		Information about the module
	 * @return	object
	 */
	public function update($slug, $module)
	{
		return $this->db->where('slug', $slug)->update($this->_table, $module);
	}

	/**
	 * Delete
	 *
	 * Delete a module from the database
	 *
	 * @param	array	$module_slug	The module slug
	 * @access	public
	 * @return	object
	 */
	public function delete($module_slug)
	{
		return $this->db->delete($this->_table, array('slug' => $module_slug));
	}

	/**
	 * Get Modules
	 *
	 * Return an array of objects containing module related data
	 *
	 * @param	array	$params				The array containing the modules to load
	 * @param	bool	$return_disabled	Whether to return disabled modules
	 * @access	public
	 * @return	array
	 */
	public function get_modules($params = array(), $return_disabled = FALSE)
	{
		$modules = array();

		foreach ($this->db->get($this->_table)->result() as $result)
		{
			// Skip the disabled modules
			if (!$return_disabled && $result->enabled == 0)
			{
				continue;
			}

			$descriptions = unserialize($result->description);
			if (!isset($descriptions[CURRENT_LANGUAGE]))
			{
				$description = $descriptions['en'];
			} else
			{
				$description = $descriptions[CURRENT_LANGUAGE];
			}

			$names = unserialize($result->name);
			if (!isset($names[CURRENT_LANGUAGE]))
			{
				$name = $names['en'];
			}

			else
			{
				$name = $names[CURRENT_LANGUAGE];
			}

			$module = array(
				'name' => $name,
				'slug' => $result->slug,
				'version' => $result->version,
				'type' => $result->type,
				'description' => $description,
				'skip_xss' => $result->skip_xss,
				'is_frontend' => $result->is_frontend,
				'is_backend' => $result->is_backend,
				'is_backend_menu' => $result->is_backend_menu,
				'controllers' => unserialize($result->controllers),
				'enabled' => $result->enabled,
				'is_core' => $result->is_core
			);

			if (!empty($params['is_frontend']) && empty($module['is_frontend']))
			{
				continue;
			}

			if (!empty($params['is_backend']))
			{
				if (empty($module['is_backend']))
				{
					continue;
				}

				// This user has no permissions for this module
				if (!$this->permissions_m->has_admin_access($this->user->group_id, $module['slug']))
				{
					continue;
				}
			}

			if (isset($params['is_core']) && $module['is_core'] != $params['is_core'])
			{
				continue;
			}

			if (isset($params['is_backend_menu']) && $module['is_backend_menu'] != $params['is_backend_menu'])
			{
				continue;
			}

			$modules[] = $module;
		}

		return $modules;
	}

	/**
	 * Get Module Controllers
	 *
	 * Gets the controller of the specified module
	 *
	 * @param	string	$module		The name of the module
	 * @access	public
	 * @return	array
	 */
	function get_module_controllers($module = '')
	{
		$module = $this->get($module);

		if (is_array($module['controllers']))
		{
			return array_keys($module['controllers']);
		}

		return array();
	}

	/**
	 * Get Module Controller Methods
	 *
	 * Get the methods of the specified module/controller combination
	 *
	 * @access public
	 * @return mixed
	 */
	public function get_module_controller_methods($module, $controller)
	{
		$module = $this->get($module);

		return!empty($module['controllers'][$controller]['methods']) ? $module['controllers'][$controller]['methods'] : array();
	}

	/**
	 * Exists
	 *
	 * Checks if a module exists
	 *
	 * @param	string	$module	The module slug
	 * @return	bool
	 */
	public function exists($module)
	{
		if ($this->db->get_where($this->_table, array('slug' => $module), 1)->num_rows() > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Enable
	 *
	 * Enables a module
	 *
	 * @param	string	$module	The module slug
	 * @return	bool
	 */
	public function enable($module)
	{
		if ($this->exists($module))
		{
			$this->db->where('slug', $module)->update($this->_table, array('enabled' => 1));
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Disable
	 *
	 * Disables a module
	 *
	 * @param	string	$module	The module slug
	 * @return	bool
	 */
	public function disable($module)
	{
		if ($this->exists($module))
		{
			$this->db->where('slug', $module)->update($this->_table, array('enabled' => 0));
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Install
	 *
	 * Installs a module
	 *
	 * @param	string	$module	The module slug
	 * @return	bool
	 */
	public function install($module_slug)
	{

		if (!is_file('third_party/modules/' . $module_slug . '/details.xml'))
		{
			return FALSE;
		}

		$module = $this->_parse_xml('third_party/modules/' . $module_slug . '/details.xml');

		$module['is_core'] = 0;
		$module['enabled'] = 1;
		$module['slug'] = $module_slug;

		// Run the install sql if it is there
		if (isset($module['install']) && !empty($module['install']))
		{
			$install_sql = explode('-- command split --', trim($module['install']));

			foreach ($install_sql as $sql)
			{
				$sql = trim($sql);
				if (!empty($sql))
				{
					$this->db->query(trim($sql));
				}
			}
		}

		return $this->add($module);
	}

	/**
	 * Uninstall
	 *
	 * Unnstalls a module
	 *
	 * @param	string	$module	The module slug
	 * @return	bool
	 */
	public function uninstall($module_slug)
	{

		if (!is_file('third_party/modules/' . $module_slug . '/details.xml'))
		{
			return FALSE;
		}

		$module = $this->_parse_xml('third_party/modules/' . $module_slug . '/details.xml');

		// Run the uninstall sql if it is there
		if (isset($module['uninstall']) && !empty($module['uninstall']))
		{
			$uninstall_sql = explode('-- command split --', trim($module['uninstall']));

			foreach ($uninstall_sql as $sql)
			{
				$sql = trim($sql);
				if (!empty($sql))
				{
					$this->db->query(trim($sql));
				}
			}
		}

		return $this->delete($module_slug);
	}


	public function import_all()
    {
    	$modules = array();
		
		$this->db->empty_table($this->_table);

    	// Loop through directories that hold modules
    	foreach (array(APPPATH.'modules/', 'third_party/modules/') as $directory)
    	{
    		// Loop through modules
	        foreach(glob($directory.'*', GLOB_ONLYDIR) as $module_name)
	        {				
	        	if(file_exists($xml_file = $module_name.'/details.xml'))
	        	{
	        		$module = $this->_parse_xml($xml_file) + array('slug'=>basename($module_name));

	        		$module['is_core'] = basename(dirname($directory)) != 'third_party';

					$module['enabled'] = 1;

					$names = $module['name'];
					if(!isset($names[CURRENT_LANGUAGE]))
					{
						$name = $names['en'];
					}
					else
					{
						$name = $names[CURRENT_LANGUAGE];
					}

					$this->add($module);
	        	}
	        }
        }

	}

	/**
	 * Parse XML
	 *
	 * Parses the details.xml file
	 *
	 * @param	string	$xml_file	The XML file to load
	 * @access	private
	 * @return	array
	 */
	private function _parse_xml($xml_file)
	{
		$xml = simplexml_load_file($xml_file);

		// Loop through all controllers in the XML file
		$controllers = array();

		foreach ($xml->controllers as $controller)
		{
			$controller_array['name'] = (string) $controller->attributes()->name;

			// Store methods from the controller
			$controller_array['methods'] = array();

			if ($controller->method)
			{
				// Loop through to save methods
				foreach ($controller->method as $method)
				{
					$controller_array['methods'][] = (string) $method;
				}
			}

			// Save it all to one variable
			$controllers[$controller_array['name']] = $controller_array;
		}

		return array(
			'name' => (array) $xml->name,
			'version' => (string) $xml->attributes()->version,
			'type' => (string) $xml->attributes()->type,
			'description' => (array) $xml->description,
			'skip_xss' => $xml->skip_xss == 1,
			'is_frontend' => $xml->is_frontend == 1,
			'is_backend' => $xml->is_backend == 1,
			'is_backend_menu' => $xml->is_backend_menu == 1,
			'controllers' => $controllers,
			'install' => $xml->install,
			'uninstall' => $xml->uninstall,
		);
	}

}