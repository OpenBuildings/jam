<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Kohana_Upload_File {

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
			$arg = $i == 0 ? rtrim($arg, '/') : trim($arg, '/');
		}
		
		return join('/', array_filter($args));
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

	public static function source_type($source)
	{
		if (Valid::url($source)) 
		{
			return 'url';
		}
		elseif ($source == 'php://input')
		{
			return 'stream';
		}
		elseif (Upload_Temp::valid($source))
		{
			return 'temp';
		}
		elseif (Upload::valid($source))
		{
			return 'upload';
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

	protected $_source;

	protected $_source_type = NULL;

	protected $_server;

	protected $_path;

	protected $_transformations;

	protected $_thumbnails;

	protected $_temp;

	protected $_filename;

	public function __construct($source, $path, $server, array $transformations = array(), array $thumbnails = array())
	{
		$this->source($source);

		$this->_server = $server;

		$this->_path = $path;

		$this->_transformations = $transformations;

		$this->_thumbnails = $thumbnails;
	}

	public function source($source = NULL)
	{
		if ($source !== NULL)
		{
			if ($this->_source_type = Upload_File::source_type($source))
			{
				$this->_source = $source;
				$this->_filename = NULL;
			}
			else
			{
				$this->_source = NULL;
				$this->_filename = $source;
			}
			return $this;
		}

		return $this->_source;
	}

	public function path($path)
	{
		if ($path !== NULL)
		{
			$this->_path = $path;

			return $this;
		}

		return $this->_path;
	}

	public function temp()
	{
		if ( ! $this->_temp)
		{
			$this->_temp = new Upload_Temp();

			if ($directory = Upload_Temp::check($this->_source))
			{
				$this->_temp->directory($directory);
			}
		}

		return $this->_temp;
	}

	public function server()
	{
		return Upload_Server::instance($this->server);
	}

	public function filename()
	{
		return $this->_filename;
	}

	public function save_to_temp()
	{
		if ( ! $this->_source)
			throw new Kohana_Exception("Cannot move file to temp directory, source does not exist");

		if ($type = $this->source_type())
		{
			$from_method = "from_$type";
			$this->_filename = Upload_File::$from_method($this->_source, $this->temp->realpath());	
		}
		else
		{
			throw new Kohana_Exception("Not a valid source for file input - :source", array(':source' => $this->_source));	
		}

		if ($this->_transformations)
		{
			Upload_File::transform_image($this->file(), $this->file(), $this->_transformations);
		}

		foreach ($this->_thumbnails as $thumbnail => $transformations) 
		{
			Upload_File::transform_image($this->file(), $this->file($thumbnail), $transformations);	
		}
	}

	public function save()
	{
		$this->server()->save_from_local($this->location(), $this->file());

		foreach ($this->_thumbnails as $thumbnail => $transformations) 
		{
			$this->server()->save_from_local($this->location($thumbnail), $this->file($thumbnail));
		}
	}

	public function cleanup()
	{
		$this->temp->clear();
	}

	public function delete()
	{
		$this->server()->delete($this->location());

		foreach ($this->_thumbnails as $thumbnail => $transformations) 
		{
			$this->server()->delete($this->location($thumbnail));
		}

		$this->cleanup();
	}

	public function location($thumbnail = NULL)
	{
		if ( ! $this->_source)
			return Upload_File::combine($thumbnail, $this->_filename);

		return Upload_File::combine($this->path(), $thumbnail, $this->_filename);
	}

	public function path_server()
	{
		return $this->_source ? $this->temp() : $this->server();	
	}

	public function file($thumbnail = NULL)
	{
		return $this->path_server()->realpath($this->location($thumbnail));
	}

	public function url($thumbnail = NULL)
	{
		return $this->path_server()->webpath($this->location($thumbnail));
	}

	public function with()
	{
		return $this->model->{$this->name.'_width'};
	}

	public function height()
	{
		return $this->model->{$this->name.'_height'};
	}

}