<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Kohana_Upload_File {

	/**
	 * lixlpixel recursive PHP functions
	 * recursive_remove_directory( directory to delete, empty )
	 * expects path to directory and optional TRUE / FALSE to empty
	 * 
	 * @param  string  $directory 
	 * @param  boolean $empty     
	 * @return boolean
	 */
	public static function recursive_rmdir($directory, $empty = FALSE)
	{
		if (substr($directory, -1) == DIRECTORY_SEPARATOR)
		{
			$directory = substr($directory,0,-1);
		}

		if ( ! file_exists($directory) OR ! is_dir($directory))
		{
			return FALSE;
		}
		elseif (is_readable($directory))
		{
			$handle = opendir($directory);
			while (FALSE !== ($item = readdir($handle)))
			{
				if ($item != '.' AND $item != '..')
				{
					$path = $directory.DIRECTORY_SEPARATOR.$item;

					if (is_dir($path)) 
					{
						Upload_File::recursive_rmdir($path);
					}
					else
					{
						unlink($path);
					}
				}
			}
			closedir($handle);

			if ($empty === FALSE)
			{
					if ( ! rmdir($directory))
					{
						return FALSE;
					}
			}
		}
		return TRUE;
	}

	static public function sanitize($filename)
	{
		// Transliterate strange chars
		$filename = UTF8::transliterate_to_ascii($filename);

		// Sanitize the filename
		$filename = preg_replace('/[^a-z0-9-\.]/', '-', strtolower($filename));

		// Remove spaces
		$filename = preg_replace('/\s+/u', '_', $filename);

		// Strip multiple dashes
		$filename = preg_replace('/-{2,}/', '-', $filename);

		return $filename;
	}

	public static function is_filename($filename)
	{
		return (bool) pathinfo($filename, PATHINFO_EXTENSION);
	}

	public static function pick_filename($file, $url)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		parse_str($query, $query);
		$url = basename(parse_url($url, PHP_URL_PATH));
		$file = basename($file);

		$candidates = array_merge(
			array_values((array) $query),
			(array) $url,
			(array) $file
		);

		$candidates = array_filter($candidates, 'Upload_File::is_filename');
		$candidates[] = $url ? $url : $file;

		return Upload_File::sanitize(reset($candidates));
	}

	public static function normalize_extension($file = NULL, $mime_type = NULL, $url = NULL)
	{
		$ext = NULL;
		$filename = Upload_File::pick_filename($file, $url);

		$base = pathinfo($filename, PATHINFO_FILENAME);

		if ($mime_type)
		{
			$ext = File::ext_by_mime($mime_type);
		}
		
		if ( ! $ext AND $file AND is_file($file))
		{
			$ext = File::ext_by_mime(File::mime($file));
		}
		
		if ( ! $ext)
		{
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if ($ext)
			{
				$filename = substr($filename, 0, - strlen($ext) - 1);
				$ext = preg_replace('/(\?[^?]+|\&.+)$/', '', $ext);
			}
		}

		if ( ! $ext)
		{
			$ext = 'jpg';
		}
		
		return $base.'.'.$ext;
	}

	/**
	 * Create a filename path from function arguments with / based on the operating system
	 * @code
	 * $filename = file::combine('usr','local','bin'); // will be "user/local/bin"
	 * @endcode
	 * @return string
	 * @author Ivan Kerin
	 */
	public static function combine()
	{
		$args = func_get_args();

		foreach ($args as $i => &$arg)
		{
			$arg = $i == 0 ? rtrim($arg, DIRECTORY_SEPARATOR) : trim($arg, DIRECTORY_SEPARATOR);
		}
		
		return join(DIRECTORY_SEPARATOR, array_filter($args));
	}

	public static function from_url($url, $directory)
	{
		$url = str_replace(' ', '%20', $url);

		$curl = curl_init($url);
		$file = Upload_File::combine($directory, uniqid());
		$handle = fopen($file, 'w');

		curl_setopt($curl, CURLOPT_FILE, $handle);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_exec($curl);
		fclose($handle);
		
		$filename = Upload_File::normalize_extension($file, curl_getinfo($curl, CURLINFO_CONTENT_TYPE), curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));

		$result_file = Upload_File::combine($directory, $filename);
		
		rename($file, $result_file);
		
		return is_file($result_file) ? $filename : NULL;
	}

	public static function from_upload(array $file, $directory)
	{
		if ( ! Upload::not_empty($file))
			return NULL;

		$filename = Upload_File::sanitize($file['name']);
		$result_file = Upload_File::combine($directory, $filename);

		move_uploaded_file($file['tmp_name'], $result_file);

		return is_file($result_file) ? $filename : NULL;
	}

	public static function from_file($file, $directory)
	{
		if ( ! is_file($file) OR Kohana::$environment !== Kohana::TESTING)
			return NULL;

		$filename = Upload_File::normalize_extension($file);
		$result_file = Upload_File::combine($directory, $filename);

		rename($file, $result_file);

		return is_file($result_file) ? $filename : NULL;
	}

	public static function from_stream($file, $directory)
	{
		$input = fopen('php://input', "r");
		$hamdle = fopen($file, 'w');
		$realSize = stream_copy_to_stream($input, $hamdle);
		fclose($input);
		fclose($hamdle);

		return is_file($result_file);
	}

	public static function from_temp($file, $directory)
	{
		return Upload_Temp::preloaded_filename($file);
	}

	public static function transform_image($from, $to, array $transformations = array())
	{
		$thumb = Image::factory($from, Kohana::$config->load('jam.upload.image_driver'));

		// Process tranformations
		foreach ($transformations as $transformation => $params)
		{
			if ($transformation !== 'factory' OR $transformation !== 'save' OR $transformation !== 'render')
			{
				// Call the method excluding the factory, save and render methods
				call_user_func_array(array($image, $transformation), $params);
			}
		}

		$thumb->save($to, 95);
	}

	protected $_source;

	protected $_source_type = NULL;

	protected $_server;

	protected $_path;

	protected $_temp;

	protected $_filename;

	protected $_transformations = array();

	protected $_thumbnails = array();

	protected $_aspect;

	public function __construct($server, $path)
	{
		$this->_server = $server;

		$this->_path = $path;
	}

	public function guess_source_type($source)
	{
		if (is_array($source)) 
		{
			return Upload::valid($source) ? 'upload' : FALSE;
		}
		elseif ($source == 'php://input')
		{
			return 'stream';
		}
		elseif (Valid::url($source))
		{
			return 'url';
		}
		elseif ($this->temp()->valid($source))
		{
			return 'temp';
		}
		elseif (is_file($source))
		{
			return 'file';
		}
		else
		{
			return FALSE;
		}
	}

	public function source_type()
	{
		return $this->_source_type;
	}

	public function path($path = NULL)
	{
		if ($path !== NULL)
		{
			$this->_path = $path;

			return $this;
		}

		return $this->_path;
	}

	public function source($source = NULL)
	{
		if ($source !== NULL)
		{
			if ($this->_source_type = $this->guess_source_type($source))
			{
				$this->_source = $source;

				if ($this->_source_type == 'temp')
				{
					$this->temp()->directory(dirname($source));
					$this->filename(basename($source));
				}
			}

			return $this;
		}

		return $this->_source;
	}

	public function transformations(array $transformations = NULL)
	{
		if ($transformations !== NULL)
		{
			$this->_transformations = $transformations;

			return $this;
		}

		return $this->_transformations;
	}

	public function thumbnails(array $thumbnails = NULL)
	{
		if ($thumbnails !== NULL)
		{
			$this->_thumbnails = $thumbnails;

			return $this;
		}

		return $this->_thumbnails;
	}

	public function set_size($width, $height)
	{
		$this->_aspect = new Image_Aspect($width, $height);
	}

	public function width()
	{
		return $this->aspect()->width();
	}

	public function height()
	{
		return $this->aspect()->height();
	}

	public function aspect()
	{
		return $this->_aspect;
	}

	public function temp()
	{
		if ( ! $this->_temp)
		{
			$this->_temp = new Upload_Temp();
		}

		return $this->_temp;
	}

	public function server()
	{
		return Upload_Server::instance($this->_server);
	}

	public function filename($filename = NULL)
	{
		if ($filename !== NULL)
		{
			$this->_filename = $filename;

			return $this;
		}

		return $this->_filename;
	}

	public function save_to_temp()
	{
		if ( ! $this->source())
			throw new Kohana_Exception("Cannot move file to temp directory, source does not exist, path :path, filename :filename", array(':path' => $this->path(), ':filename' => $this->filename()));

		if ( ! $this->source_type())
			throw new Kohana_Exception("Not a valid source for file input - :source", array(':source' => $this->_source));	

		$from_method = "from_".$this->source_type();
		$filename = Upload_File::$from_method($this->source(), $this->temp()->directory_path());
		$this->filename($filename);

		if ($this->_transformations)
		{
			Upload_File::transform_image($this->file(), $this->file(), $this->transformations());
		}

		foreach ($this->thumbnails() as $thumbnail => $transformations) 
		{
			Upload_File::transform_image($this->file(), $this->file($thumbnail), $transformations);	
		}
	}

	public function save()
	{
		$this->server()->save_from_local($this->full_path(), $this->file());

		foreach ($this->thumbnails() as $thumbnail => $transformations) 
		{
			$this->server()->save_from_local($this->full_path($thumbnail), $this->file($thumbnail));
		}

		$this->_source = NULL;
		$this->_source_type = NULL;
	}

	public function cleanup()
	{
		$this->temp()->clear();
	}

	public function delete()
	{
		$this->server()->unlink($this->full_path());

		foreach ($this->thumbnails() as $thumbnail => $transformations) 
		{
			$this->server()->unlink($this->full_path($thumbnail));
		}

		$this->cleanup();
	}

	public function file($thumbnail = NULL)
	{
		return $this->location('realpath', $thumbnail);
	}

	public function url($thumbnail = NULL)
	{
		return $this->location('webpath', $thumbnail);
	}

	protected function full_path($thumbnail = NULL)
	{
		return Upload_File::combine($this->path(), $thumbnail, $this->filename());
	}

	protected function location($method, $thumbnail = NULL)
	{
		$server = $this->_source ? $this->temp() : $this->server();

		if ($this->_source)
		{
			return $this->temp()->$method(Upload_File::combine($this->temp()->directory(), $thumbnail, $this->filename()));
		}
		else
		{
			return $this->server()->$method($this->full_path($thumbnail));
		}
	}

	public function is_empty()
	{
		return (bool) $this->filename();
	}

	public function __toString()
	{
		return $this->filename();
	}
}