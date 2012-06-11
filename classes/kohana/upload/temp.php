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
	/**
	 * @var  array the current temporary folder inside the temp folder used to store files for the current object
	 */
	public $key = NULL;
	public $path = NULL;
	public $web = NULL;
	public $sequrity_check = TRUE;
	public $clear = TRUE;
	public $clear_only_empty = FALSE;
	
	protected $_filename = NULL;
	protected $_initialized = FALSE;
	protected $_thumbnails = array();

	static public function factory(array $config = NULL)
	{
		return new Upload_Temp($config);
	}

	public function __construct(array $config = NULL)
	{
		// Store the config locally
		$config = Arr::merge(Kohana::$config->load("jam.upload.temp"), (array) $config);

		if (($missing_keys = array_diff(array('path', 'web'), array_keys($config))))
			throw new Kohana_Exception("Missing config options :missing", array(":missing" => join(", ", $missing_keys)));

		$this->key = Arr::get($config, 'key', uniqid());
		$this->path = $config['path'];
		$this->web = $config['web'];
		$this->sequrity_check = Arr::get($config, 'sequrity_check', TRUE);
		$this->clear = Arr::get($config, 'clear', TRUE);					
	}

	public function __destruct()
	{
		$this->clear_files();
	}

	public function populate($filename)
	{
		$this->clear_files();
		
		$this->key = dirname($filename);
		$this->_filename = basename($filename);
		$this->initialize();
		
		return $this;
	}

	public function get_file($file)
	{
		$this->initialize();
		$this->_filename = Upload_Temp::filename_with_extension($file, $file);
		$new_name = Upload_Server::combine_path($this->pathdir(), $this->_filename);
		
		copy($file, $new_name);
		
		return is_file($new_name);
	}

	public function download($url)
	{
		$this->initialize();
		
		$url = self::sanitize_url($url);

		$curl = curl_init($url);
		$file = Upload_Server::combine_path($this->pathdir(), uniqid());
		$file_handle = fopen($file, 'w');

		curl_setopt($curl, CURLOPT_FILE, $file_handle);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_exec($curl);
		fclose($file_handle);
		
		$this->_filename = Upload_Temp::filename_with_extension(curl_getinfo($curl, CURLINFO_CONTENT_TYPE), curl_getinfo($curl, CURLINFO_EFFECTIVE_URL), $file);

		$new_name = Upload_Server::combine_path($this->pathdir(), $this->_filename);
		
		rename($file, $new_name);
		
		return is_file($new_name);
	}

	public static function filename_with_extension($content_type = NULL, $url = NULL, $file = NULL)
	{
		$ext = NULL;
		$url = self::sanitize_url($url);
		$filename = basename($url);

		if ($content_type)
		{
			$ext = Upload_Temp::get_extension_from_content_type($content_type);
		}
		
		
		if ( ! $ext AND $file)
		{
			$ext = Upload_Temp::get_extension_from_file($file);
		}
		
		
		if ( ! $ext)
		{
			$ext = Upload_Temp::get_extension_from_filename($filename);
			if ($ext)
			{
				$filename = substr($filename, 0, - strlen($ext) - 1);
				$ext = preg_replace('/(\?[^?]+|\&.+)$/', '', $ext);
			}
		}
		

		$filename = Jam_Field_Upload::sanitize_filename($filename);

		if ( ! $ext)
		{
			$ext = 'jpg';
		}
		
		return $filename.'.'.$ext;
	}

	public static function get_extension_from_content_type($content_type)
	{
		return File::ext_by_mime($content_type);
	}

	public static function get_extension_from_filename($filename)
	{
		return pathinfo($filename, PATHINFO_EXTENSION);
	}

	public static function get_extension_from_file($file)
	{
		if (is_file($file))
		{
			return File::ext_by_mime(File::mime($file));
		}
		return NULL;
	}

	public function file($thumb = null)
	{
		return Upload_Server::combine_path($this->pathdir(), $thumb, $this->_filename);
	}

	public function thumbnail($thumbnail)
	{
		$this->initialize();

		if ( ! in_array($thumbnail, $this->_thumbnails))
		{
			$this->_thumbnails[] = $thumbnail;

			if ( ! is_dir(Upload_Server::combine_path($this->pathdir(), $thumbnail)))
			{
				mkdir(Upload_Server::combine_path($this->pathdir(), $thumbnail), 0777, TRUE);
			}
		}
				
		return $this;
	}

	public static function sanitize_url($url)
	{
		$url = str_replace(' ', '%20', $url);

		return $url;
	}
	
	public function url($thumb = null)
	{
		return Upload_Server::combine_path($this->webdir(), $thumb, $this->_filename);
	}

	public function hidden_filename()
	{
		return Upload_Server::combine_path($this->key, $this->_filename);
	}

	public function is_file($file)
	{
		$this->initialize();
		return is_file(Upload_Server::combine_path($this->path, $file));
	}


	public function move_to_server(Upload_Server $server, $path, $clear = TRUE)
	{
		$this->clear = $clear;
		
		if ($this->_filename)
		{
			$this->initialize();

			$server->save_from_local(Upload_Server::combine_path($path, $this->_filename), $this->file(), $clear);

			foreach ($this->_thumbnails as $thumb)
			{
				$server->save_from_local(Upload_Server::combine_path($path, $thumb, $this->_filename), $this->file($thumb), $clear);
			}
		}
		return $this->clear_files();
	}
	
	public function copy_to_server(Upload_Server $server, $path)
	{
		return $this->move_to_server($server, $path, false);
	}

	public function initialize()
	{
		if ( ! $this->_initialized)
		{
			if ( ! is_dir($this->path))
			{
				mkdir( $this->path, 0777, TRUE);
			}

			if ( ! is_writable($this->path))
			{
				throw new Kohana_Exception("The assigned path :path must be writable", array(":path" => $this->path));
			}

			if ( ! is_dir($this->pathdir()))
			{
				mkdir($this->pathdir(), 0777, TRUE);
			}		
			
			$this->_initialized = TRUE;
		}
		
		return $this;
	}

	/**
	 * Clear all files and directories
	 *
	 * @return $this
	 * @author Ivan K
	 **/
	public function clear_files()
	{	
		if ( ! $this->clear)
			return $this;

		if (is_dir($this->pathdir()))
		{
			if ($this->clear_only_empty)
			{
				@rmdir($this->pathdir());
			}
			else
			{
				$this->recursive_delete($this->pathdir());
			}
		}	

		$this->thumbnails = array();
		$this->_filename = null;
		$this->_initialized = FALSE;
		
		return $this;
	}
	
	private function recursive_delete($dir_path)
	{
		$this->initialize();
		
		if (is_file($dir_path))
		{
				return unlink($dir_path);
		}
		elseif (is_dir($dir_path))
		{
				foreach (glob(rtrim($dir_path,'/').'/*') as $index=>$path)
				{
						$this->recursive_delete($path);
				}
				return rmdir($dir_path);
		}
	}

	/**
	 * The main temporary category
	 * @return string 
	 */
	public function tempdir()
	{
		return $this->web;
	}
	
	/**
	 * The current temporary directory filesystem path
	 *
	 * @return string
	 * @author Ivan K
	 **/
	public function pathdir()
	{
		return Upload_Server::combine_path($this->path, $this->key);
	}

	/**
	 * The current temporary directory publicaly accessable path
	 *
	 * @return string
	 * @author Ivan K
	 **/
	public function webdir()
	{
		return Upload_Server::combine_path($this->web, $this->key);
	}

	public function get_uploaded_file($uploaded_file, $thumb = null)
	{
		$this->initialize();
		
		if ( ! is_file($uploaded_file) AND $uploaded_file != "php://input")
			throw new Kohana_Exception("File :file does not exist", array(":file" => $uploaded_file));

		if ($uploaded_file == "php://input")
		{
			$input = fopen($uploaded_file, "r");
			$temp = fopen($this->file($thumb), 'w');
			$realSize = stream_copy_to_stream($input, $temp);
			fclose($input);
			fclose($temp);
			return $realSize;
		}
		elseif($this->sequrity_check)
		{
			return move_uploaded_file($uploaded_file, $this->file($thumb));
		}		
		else
		{
			return rename($uploaded_file, $this->file($thumb));	
		}
	}

	public function is_uploaded_file($uploaded_file)
	{
		return $this->sequrity_check ? is_uploaded_file($uploaded_file) : true;
	}

	public function filename($filename = null)
	{		
		if ($filename !== null)
		{
			$this->initialize();
			$this->_filename = URL::title(pathinfo((string) $filename, PATHINFO_FILENAME)).'.'.pathinfo((string) $filename, PATHINFO_EXTENSION);

			return $this;
		}
		return $this->_filename;
	}
}