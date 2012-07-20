<?php
/**
 * Abstract Class for manupulating a server
 * 
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Kohana_Upload_Server
{
	/**
	 * @var  string  default instance name
	 */
	public static $default = 'default';

	/**
	 * @var  array  Database instances
	 */
	public static $instances = array();

	/**
	 * Get a singleton Upload_Server instance. If configuration is not specified,
	 * it will be loaded from the Upload_Server configuration file using the same
	 * group as the name.
	 *
	 *     // Load the default database
	 *     $server = Upload_Server::instance();
	 *
	 *     // Create a custom configured instance
	 *     $server = Upload_Server::instance('custom', $config);
	 *
	 * @param   string   instance name
	 * @param   array    configuration parameters
	 * @return  Database
	 */
	public static function instance($name = NULL, array $config = NULL)
	{
		if ($name === NULL)
		{
			// Use the default instance name
			$name = Upload_Server::$default;
		}

		if ( ! isset(Upload_Server::$instances[$name]))
		{
			if ($config === NULL)
			{
				// Load the configuration for this database
				$config = Arr::get(Kohana::$config->load("jam.upload.servers"), $name);
			}

			if ( ! isset($config['type']))
			{
				throw new Kohana_Exception('Upload_server type not defined in :name configuration',
					array(':name' => $name));
			}

			if ( ! isset($config['params']))
			{
				throw new Kohana_Exception('Upload_server params not defined in :name configuration',
					array(':name' => $name));
			}			

			// Set the driver class name
			$driver = 'Upload_Server_'.ucfirst($config['type']);

			// Create the database connection instance
			new $driver($name, $config['params']);
		}

		return Upload_Server::$instances[$name];
	}

	// Configuration array
	protected $_config;

	// Instance name
	protected $_instance;	

	/**
	 * Stores the Upload_Server configuration locally and name the instance.
	 *
	 * [!!] This method cannot be accessed directly, you must use [Upload_Server::instance].
	 *
	 * @return  void
	 */
	protected function __construct($name, array $config, array $required_keys)
	{
		// Set the instance name
		$this->_instance = $name;

		// Store the config locally
		$this->_config = $config;

		// Store the database instance
		Upload_Server::$instances[$name] = $this;

		if(($missing_keys = array_diff($required_keys, array_keys($config))))
			throw new Kohana_Exception("Missing config options :missing", array(":missing" => join(", ", $missing_keys)));
	}

	/**
	 * Check if the file actually exists
	 *
	 * @param string $file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function file_exists($file);

	/**
	 * Check if the file actually exists and is a file
	 *
	 * @param string $file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function is_file($file);

	/**
	 * Check if the file actually exists and is a directory
	 *
	 * @param string $file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function is_dir($file);

	/**
	 * Delete the specified file
	 *
	 * @param string $file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function unlink($file);

	/**
	 * Create a directory
	 *
	 * @param string $file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function mkdir($dir_name);

	/**
	 * Move a file to the destination
	 *
	 * @param string $file
	 * @param string $new_file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function rename($file, $new_file);

	/**
	 * Copy a file to the destination
	 *
	 * @param string $file
	 * @param string $new_file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function copy($file, $new_file);

	/**
	 * Copy a local file to the server, optionaly deleting it ( Local filesystems implement this with rename so its significatly faster)
	 *
	 * @param string $file
	 * @param string $local_file
	 * @param bool $remove_file	defaults to true
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function save_from_local($file, $local_file, $remove_file = true);

	/**
	 * Copy a file from the server to a local file
	 *
	 * @param string $file
	 * @param string $local_file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function copy_to_local($file, $local_file);

	/**
	 * Copy a file from the server to a local file and delete it from the server ( Local filesystems implement this with rename so its significatly faster)
	 *
	 * @param string $file
	 * @param string $local_file
	 * @return bool
	 * @author Ivan K
	 **/
	abstract public function move_to_local($file, $local_file);

	/**
	 * Return file contents
	 *
	 * @param string $file
	 * @return string
	 * @author Ivan K
	 **/
	abstract public function read($file);

	/**
	 * Write contents to a file
	 *
	 * @param string $file
	 * @param string $content
	 * @return string
	 * @author Ivan K
	 **/
	abstract public function write($file, $content);

	/**
	 * Check if the file is writable
	 *
	 * @param string $file
	 * @param string $content
	 * @return string
	 * @author Ivan K
	 **/	
	abstract public function is_writable($file);

	/**
	 * Return the local file path, used by local filesystems
	 *
	 * @param string $file
	 * @return string
	 * @author Ivan K
	 **/	
	abstract public function realpath($file);

	/**
	 * Return a publicly accessable location of a file
	 *
	 * @param string $file
	 * @return string
	 * @author Ivan K
	 **/	
	abstract public function webpath($file);

		
}