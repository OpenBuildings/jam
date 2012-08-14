<?php
/**
 * Local filesystem driver
 * 
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Upload_Server_Ftp extends Upload_Server
{
	protected $_connection;
	protected $_tmp_files = array();
	protected $_path = null;

	function __construct($name, $config)
	{
		parent::__construct($name, $config, array("host", "web"));

		$this->_connection = ftp_connect($config["host"], Arr::get($config, "port"),Arr::get($config, "timeout"));
		$this->_path = Arr::get($config, 'path', '/');

		if (isset($config['user']) AND isset($config['password']))
		{
			ftp_login($this->_connection, $config['user'], $config['password']);
		}
	}

	public function __destruct()
	{
		ftp_close($this->_connection);
		array_map('unlink', array_filter($this->_tmp_files, 'is_file'));
	}

	public function connection()
	{
		return $this->_container;
	}

	public function file_exists($file)
	{
		return ftp_size($this->_connection, $this->path.$file) > 0;
	}

	public function is_file($file)
	{
		return $this->file_exists($this->path.$file);
	}

	public function is_dir($file)
	{
		return $this->file_exists($this->path.$file);
	}

	public function unlink($file)
	{
		return ftp_delete($this->_connection, $this->path.$file);
	}

	public function mkdir($file)
	{
		return ftp_mkdir($this->_connection, $this->path.$file);
	}

	public function rename($file, $new_file)
	{
		return ftp_rename($this->_connection, $this->path.$file, $this->path.$new_file);
	}

	public function copy($file, $new_file)
	{
		return copy($this->path.$file, $this->path.$new_file, $this->_connection);
	}	

	public function save_from_local($file, $local_file, $remove_file = true)
	{
		$result = ftp_put($this->_connection, $this->path.$file, $local_file);

		if ($result AND $remove_file)
		{
			unlink($local_file);
		}
		return $result;
	}

	public function copy_to_local($file, $local_file)
	{
		if ( ! is_dir(dirname($local_file)))
	    throw new Kohana_Exception(":dir must be local directory", array(":dir" => dirname($local_file)));
	    
	  return ftp_get($this->_connection, $local_file, $this->path.$file);
	}

	public function move_to_local($file, $local_file)
	{
		if ($this->copy_to_local($file, $local_file))
		{
			return $this->unlink($file);
		}
		else
		{
			return false;
		}
	}

	public function read($file)
	{
		$this->_tmp_files[] = $tmp_file = tempnam(sys_get_temp_dir());
		ftp_get($this->_connection, $tmp_file, $this->path.$file);

		return file_get_contents($tmp_file);
	}

	public function write($file, $content)
	{
		$this->_tmp_files[] = $tmp_file = tempnam(sys_get_temp_dir());
 		file_get_contents($tmp_file, $content);

		return ftp_put($this->_connection, $this->path.$file, $tmp_file);
	}

	public function is_writable($file)
	{
		return true;
	}

	public function realpath($file)
	{
		throw new Kohana_Exception("Rackspace driver does not have real system paths");
	}

	public function webpath($file, $protocol = NULL)
	{
		return URL::site(rtrim($this->_config['web'], '/').'/'.$file, $protocol);
	}		
}