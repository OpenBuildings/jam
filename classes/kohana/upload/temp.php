<?php
/**
 *  Utility class for managing the temporary upload directory
 *  
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Upload_Temp
{
	protected $_directory;

	protected $_config;

	public function __construct(array $config = NULL)
	{
		// Store the config locally
		$this->_config = Arr::merge(Kohana::$config->load("jam.upload.temp"), (array) $config);

		if (($missing_keys = array_diff(array('path', 'web'), array_keys($config))))
			throw new Kohana_Exception("Missing config options :missing", array(":missing" => join(", ", $missing_keys)));
	}

	public function directory($directory = NULL)
	{
		if ($directory !== NULL)
		{
			$this->_directory = $directory;
			return $this;
		}

		if ( ! $this->_directory)
		{
			$this->_directory = uniqid();
		}

		return $this->_directory;
	}

	public function realpath($path)
	{
		return Upload_File::combine($this->_config['path'], $path)
	}

	public function webpath($path)
	{
		return Upload_File::combine($this->_config['web'], $path)
	}

}