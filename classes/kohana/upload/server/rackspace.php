<?php
/**
 * Local filesystem driver
 * 
 * @package    OpenBuildings/jam-upload
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Upload_Server_Rackspace extends Upload_Server
{
	protected $_container;

	function __construct($name, $config)
	{
		parent::__construct($name, $config, array("user", "key", "container", "cdn"));	
	}

	public function container()
	{
		if ( ! $this->_container)
		{
			require_once Kohana::find_file('vendor/cloudfiles', 'cloudfiles');
		
			$auth = new CF_Authentication($this->_config["user"], $this->_config["key"], NULL, Arr::get($this->_config, "server", US_AUTHURL));
			$auth->authenticate();
			$conn = new CF_Connection($auth);

			$this->_container = $conn->get_container($this->_config["container"]);
		}
		
		return $this->_container;
	}

	public function file_exists($file)
	{
	  try 
	  {
  		return (bool) $this->container()->get_object($file);
	  } 
	  catch (Exception $e)
	  {
	    return FALSE;
	  }
	}

	public function is_file($file)
	{
		return (bool) $this->container()->get_object($file);
	}

	public function is_dir($file)
	{
		return TRUE;
	}

	public function unlink($file)
	{
		try 
		{
			return $this->container()->delete_object($file);	
		} 
		catch (NoSuchObjectException $e) 
		{
			return FALSE;
		}
	}

	public function mkdir($file)
	{
		return $this->container()->create_paths($file);
	}

	public function rename($file, $new_file)
	{
		return $this->container()->move_object_to($this->get_object($file), $this->container(), $new_file);
	}

	public function copy($file, $new_file)
	{
		return $this->container()->copy_object_to($this->get_object($file), $this->container(), $new_file);
	}	

	public function save_from_local($file, $local_file, $remove_file = TRUE)
	{
		$result = $this->container()->create_object($file)->load_from_filename($local_file);
		if ($result AND $remove_file)
		{
			unlink($local_file);
		}
		return $result;
	}

	public function copy_to_local($file, $local_file)
	{
		$object = $this->container()->get_object($file);

		if ( ! $object )
			throw new Kohana_Exception(":file does not exist", array(":file" => $file));

		if ( ! is_dir(dirname($local_file)))
	    throw new Kohana_Exception(":dir must be local directory", array(":dir" => dirname($local_file)));
	    
	  return $object->save_to_filename($local_file);
	}

	public function move_to_local($file, $local_file)
	{
		if ($this->copy_to_local($file, $local_file))
		{
			return $this->unlink($file);
		}
		else
		{
			return FALSE;
		}
	}

	public function read($file)
	{
		return $this->container->get_object($file)->read();
	}

	public function write($file, $content)
	{
		return $this->container->get_object($file)->write($content);
	}

	public function is_writable($file)
	{
		return TRUE;
	}

	public function realpath($file)
	{
		//throw new Kohana_Exception("Rackspace driver does not have real system paths");
	}

	public function webpath($file)
	{
		return rtrim($this->_config['cdn'], '/').'/'.$file;
	}		
}