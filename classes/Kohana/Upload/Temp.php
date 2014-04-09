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
	public static function factory()
	{
		return new Upload_Temp;
	}

	public static function config($name = NULL)
	{
		if ($name !== NULL)
		{
			return Kohana::$config->load("jam.upload.temp.{$name}");
		}
		else
		{
			return Kohana::$config->load("jam.upload.temp");
		}
	}

	public static function valid($file)
	{
		return (substr_count($file, DIRECTORY_SEPARATOR) === 1 AND is_file(Upload_Util::combine(Upload_Temp::config('path'), $file)));
	}

	protected $_directory;

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
			Upload_Util::rmdir($this->realpath($this->directory()));
			$this->_directory = NULL;
		}
	}

	public function realpath($path, $thumbnail = NULL)
	{
		return Upload_Util::combine(Upload_Temp::config('path'), $path, $thumbnail);
	}

	public function url($path, $thumbnail = NULL)
	{
		return Upload_Util::combine(Upload_Temp::config('web'), $path, $thumbnail);
	}

}
