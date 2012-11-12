<?php
/**
 *  Utility class for managing the temporary upload directory
 *  
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Upload_Temp
{
	public static function factory(array $config = NULL)
	{
		return new Upload_Temp($config);
	}

	protected $_directory;

	protected $_config;

	public function __construct(array $config = NULL)
	{
		// Store the config locally
		$this->_config = Arr::merge(Kohana::$config->load("jam.upload.temp"), (array) $config);

		if (($missing_keys = array_diff(array('path', 'web'), array_keys($this->_config))))
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

	public function directory_path($thumbnail = NULL)
	{
		$directory = $this->realpath($this->directory(), $thumbnail);

		if ( ! is_dir($directory))
		{
			mkdir($directory, 0777);
		}

		return $directory;
	}

	public function directory_url($thumbnail = NULL)
	{
		return $this->webpath($this->directory(), $thumbnail);
	}

	public function clear()
	{
		if ($this->_directory)
		{
			Upload_File::recursive_rmdir($this->realpath($this->directory()));
			$this->_directory = NULL;
		}
	}

	public function valid($file)
	{
		return (substr_count($file, DIRECTORY_SEPARATOR) === 1 AND is_file($this->realpath($file)));
	}

	public function realpath($path, $thumbnail = NULL)
	{
		return Upload_File::combine($this->_config['path'], $path, $thumbnail);
	}

	public function webpath($path, $thumbnail = NULL)
	{
		return Upload_File::combine($this->_config['web'], $path, $thumbnail);
	}

}