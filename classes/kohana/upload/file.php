<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class is what the upload field accually returns 
 * and has all the nesessary info and manipulation abilities to save / delete / validate itself
 * 
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

	/**
	 * Method to make a filename safe for writing on the filesystem, removing all strange characters
	 * @param  string $filename 
	 * @return string
	 */
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

	/**
	 * Check if a file looks like a filename ("file.ext")
	 * @param  string  $filename 
	 * @return boolean           
	 */
	public static function is_filename($filename)
	{
		return (bool) pathinfo($filename, PATHINFO_EXTENSION);
	}

	/**
	 * Get a proper filename from file or url source, ordering them by usefulness and returning the most appropriate one
	 * @param  string $file The full filename
	 * @param  string $url  Source URL
	 * @return string       
	 */
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

	/**
	 * Get the correct extension for the file from a varaiety of sources.
	 * Any or all of the sources can be null. Uses .jpg by default
	 * 
	 * @param  string $file      phisical file location
	 * @param  string $mime_type mimetype of the file
	 * @param  string $url       source url
	 * @return string            
	 */
	public static function normalize_extension($file = NULL, $mime_type = NULL, $url = NULL)
	{
		$ext = NULL;
		$filename = Upload_File::pick_filename($file, $url);

		$base = pathinfo($filename, PATHINFO_FILENAME);

		// Get extension from mimetype
		if ($mime_type)
		{
			$ext = File::ext_by_mime($mime_type);
		}
		
		// Get extension from the file itself
		if ( ! $ext AND $file AND is_file($file))
		{
			$ext = File::ext_by_mime(File::mime($file));
		}
		
		// Get the extension from the URL
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

	/**
	 * Download a file from a url to a specified directory, and return the new filename
	 * 
	 * @param  string $url       
	 * @param  string $directory 
	 * @return string            the resulting filename
	 */
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

	/**
	 * Download a file from a standard PHP upload array, 
	 * and place it in the specified directory, 
	 * returning the new filename.
	 * 
	 * @param  array  $file      PHP FILE array
	 * @param  string $directory destination directory
	 * @return string            resulting filename
	 */
	public static function from_upload(array $file, $directory)
	{
		if ( ! Upload::not_empty($file))
			return NULL;

		$filename = Upload_File::sanitize($file['name']);
		$result_file = Upload_File::combine($directory, $filename);

		move_uploaded_file($file['tmp_name'], $result_file);

		return is_file($result_file) ? $filename : NULL;
	}

	/**
	 * Download a file from the filesystem. This is for testing only
	 * 
	 * @param  string $file      
	 * @param  string $directory 
	 * @return string            
	 */
	public static function from_file($file, $directory)
	{
		if ( ! is_file($file) OR Kohana::$environment !== Kohana::TESTING)
			return NULL;

		$filename = Upload_File::normalize_extension($file);
		$result_file = Upload_File::combine($directory, $filename);

		copy($file, $result_file);

		return is_file($result_file) ? $filename : NULL;
	}

	/**
	 * Download a file from the request body (stream)
	 * 
	 * @param  stream $file      
	 * @param  string $directory 
	 * @return bool
	 */
	public static function from_stream($stream, $directory, $filename = NULL)
	{
		$result_file = Upload_File::combine($directory, $filename ? $filename : uniqid());

		$stream_handle = fopen($stream, "r");
		$result_handle = fopen($result_file, 'w');
		$realSize = stream_copy_to_stream($stream_handle,  $result_handle);
		fclose($stream_handle);
		fclose($result_handle);

		return is_file($result_file);
	}

	/**
	 * A no op if the file has already been uploaded to the temp directory 
	 * 
	 * @param  string $file      
	 * @param  string $directory 
	 * @return string            
	 */
	public static function from_temp($file, $directory)
	{
		return Upload_Temp::preloaded_filename($file);
	}

	/**
	 * Perform transformations on an image and store it at a different location (or overwrite existing)
	 * 
	 * @param  string $from            
	 * @param  string $to              
	 * @param  array  $transformations 
	 */
	public static function transform_image($from, $to, array $transformations = array())
	{
		$image = Image::factory($from, Kohana::$config->load('jam.upload.image_driver'));

		// Process tranformations
		foreach ($transformations as $transformation => $params)
		{
			if ( ! in_array($transformation, array('factory', 'save', 'render')))
			{
				// Call the method excluding the factory, save and render methods
				call_user_func_array(array($image, $transformation), $params);
			}
		}

		if ( ! file_exists(dirname($to)))
		{
			mkdir(dirname($to), 0777, TRUE);
		}

		$image->save($to, 95);
	}

	protected $_source;

	protected $_source_type;

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

	/**
	 * Geuss the source type based on the source i
	 * @param  mixed $source 
	 * @return string         upload, stream, url, tmp, file, FALSE
	 */
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

	/**
	 * Get the source type 
	 * @return string upload, stream, url, tmp, file, FALSE
	 */
	public function source_type()
	{
		return $this->_source_type;
	}

	/**
	 * Get / Set the path for the image on the server
	 * @param  string $path 
	 * @return string|Upload_File       
	 */
	public function path($path = NULL)
	{
		if ($path !== NULL)
		{
			$this->_path = $path;

			return $this;
		}

		return $this->_path;
	}

	/**
	 * Get / Set the source. Automatically set the source_type
	 * 
	 * @param  mixed $source 
	 * @return mixed         
	 */
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

	/**
	 * Get / Set transformations
	 * @param  array $transformations 
	 * @return array|Upload_File                  
	 */
	public function transformations(array $transformations = NULL)
	{
		if ($transformations !== NULL)
		{
			$this->_transformations = $transformations;

			return $this;
		}

		return $this->_transformations;
	}


	/**
	 * Get / Set thumbnails
	 * @param  array $thumbnails 
	 * @return array|Upload_File                  
	 */
	public function thumbnails(array $thumbnails = NULL)
	{
		if ($thumbnails !== NULL)
		{
			$this->_thumbnails = $thumbnails;

			return $this;
		}

		return $this->_thumbnails;
	}

	/**
	 * Set the width and the height
	 * 
	 * @param integer $width  
	 * @param integer $height 
	 */
	public function set_size($model, $width_attribute, $height_attribute)
	{
		$this->_aspect = new Upload_File_Aspect($model, $width_attribute, $height_attribute);
	}

	/**
	 * @depricated 
	 * @param  integer  $width   
	 * @param  integer  $height  
	 * @param  boolean $upscale 
	 * @return array           
	 */
	public function constrained_dimensions($width = NULL, $height = NULL, $upscale = TRUE)
	{
		if ( ! $this->aspect()->width() OR ! $this->aspect()->height())
			return array('width' => NULL, 'height' => NULL);

		if ($width === NULL AND $height === NULL)
			return array('width' => $this->width(), 'height' => $this->height());

		if ($height === NULL)
			return Arr::extract($this->aspect()->width($width)->as_array(), array('width', 'height'));

		if ($width === NULL)
			return Arr::extract($this->aspect()->height($height)->as_array(), array('width', 'height'));

		return Arr::extract($this->aspect()->constrain($width, $height, $upscale)->as_array(), array('width', 'height'));
	}

	/**
	 * Get the width of the image
	 * @return integer 
	 */
	public function width()
	{
		return $this->aspect()->width();
	}

	/**
	 * Get the height of the image
	 * @return integer 
	 */
	public function height()
	{
		return $this->aspect()->height();
	}

	/**
	 * Get the Image_Aspect for the image or NULL if there are no width / height
	 * @return Image_Aspect|NULL
	 */
	public function aspect()
	{
		if ( ! $this->_aspect)
			throw new Kohana_Exception("This file has no file or width set");

		return $this->_aspect;
	}

	/**
	 * Get the Upload_Temp object. Create it if it's not already created
	 * @return Upload_Temp 
	 */
	public function temp()
	{
		if ( ! $this->_temp)
		{
			$this->_temp = new Upload_Temp();
		}

		return $this->_temp;
	}

	/**
	 * Get the upload server
	 * @return Upload_Server 
	 */
	public function server()
	{
		return Upload_Server::instance($this->_server);
	}

	/**
	 * Get / Set the current filename
	 * @param  string $filename 
	 * @return string|Upload_File           
	 */
	public function filename($filename = NULL)
	{
		if ($filename !== NULL)
		{
			$this->_filename = $filename;

			return $this;
		}

		return $this->_filename;
	}

	/**
	 * Save the current source to the temp folder
	 */
	public function save_to_temp()
	{
		if ( ! $this->source())
			throw new Kohana_Exception("Cannot move file to temp directory, source does not exist, path :path, filename :filename", array(':path' => $this->path(), ':filename' => $this->filename()));

		if ( ! $this->source_type())
			throw new Kohana_Exception("Not a valid source for file input - :source_type for source :source", array(':source_type' => $this->source_type(), ':source' => $this->source()));	

		$from_method = "from_".$this->source_type();
		$filename = Upload_File::$from_method($this->source(), $this->temp()->directory_path(), $this->filename());
		$this->filename($filename);

		if ($this->_transformations)
		{
			Upload_File::transform_image($this->file(), $this->file(), $this->transformations());
		}

		$this->generate_thumbnails();
	}

	public function generate_thumbnails()
	{
		foreach ($this->thumbnails() as $thumbnail => $thumbnail_params) 
		{
			if ( ! is_file($this->file($thumbnail)))
			{
				Upload_File::transform_image($this->file(), $this->file($thumbnail), $thumbnail_params['transformations']);	
			}
		}
	}
 
	public function save()
	{
		$this->generate_thumbnails();
		
		$this->server()->save_from_local($this->full_path(), $this->file());

		foreach ($this->thumbnails() as $thumbnail => $thumbnail_params) 
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

	public function url($thumbnail = NULL, $protocol = NULL)
	{
		$url = $this->location('webpath', $thumbnail);

		return URL::site($url, $protocol);
	}

	protected function full_path($thumbnail = NULL)
	{
		return Upload_File::combine($this->path(), $thumbnail, $this->filename());
	}

	protected function location($method, $thumbnail = NULL)
	{
		if ( ! $this->filename())
			return NULL;

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
		return ! $this->filename() AND ! $this->source();
	}

	public function __toString()
	{
		return (string) $this->filename();
	}
}