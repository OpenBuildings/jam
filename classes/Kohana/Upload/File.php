<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class is what the upload field accually returns
 * and has all the nesessary info and manipulation abilities to save / delete / validate itself
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
class Kohana_Upload_File {

	protected $_source;

	protected $_server;

	protected $_path;

	protected $_temp;

	protected $_filename;

	protected $_transformations = array();

	protected $_thumbnails = array();

	protected $_extracted_from_source = FALSE;


	public function __construct($server, $path, $filename = NULL)
	{
		$this->server($server);
		$this->_path = $path;

		if ($filename !== NULL)
		{
			$this->_filename = $filename;
		}
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

	public function move_to_server($new_server)
	{
		$file = Upload_Util::combine($this->temp()->directory_path(), $this->filename());
		$old_file = $this->full_path();

		if ( ! $this->server()->is_file($old_file))
			throw new Kohana_Exception('File '.$old_file.' does not exist');

		$this->server()->download_move($old_file, $file);

		foreach ($this->thumbnails() as $thumbnail => $thumbnail_params)
		{
			$thumbnail_file = Upload_Util::combine($this->temp()->directory_path($thumbnail), $this->filename());
			$old_thumbnail_file = $this->full_path($thumbnail);

			if ( ! $this->server()->is_file($old_thumbnail_file))
				throw new Kohana_Exception('File '.$old_thumbnail_file.' does not exist');

			$this->server()->download_move($old_thumbnail_file, $thumbnail_file);
		}

		$this->server($new_server);
		$this->server()->upload_move($this->full_path(), $file);

		foreach ($this->thumbnails() as $thumbnail => $thumbnail_params)
		{
			$thumbnail_file = Upload_Util::combine($this->temp()->directory_path($thumbnail), $this->filename());
			$this->server()->upload_move($this->full_path($thumbnail), $thumbnail_file);
		}

		$this->temp()->clear();
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
			$this->_source = Upload_Source::factory($source);

			if ($this->_source->type() === Upload_Source::TYPE_TEMP)
			{
				$this->temp()->directory(dirname($source));
				$this->filename(basename($source));
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
	 * Get the Upload_Temp object. Create it if it's not already created
	 * @return Upload_Temp
	 */
	public function temp()
	{
		if ( ! $this->_temp)
		{
			$this->_temp = Upload_Temp::factory();
		}

		return $this->_temp;
	}

	/**
	 * Get the upload server
	 * @return Upload_Server
	 */
	public function server($server = NULL)
	{
		if ($server !== NULL)
		{
			$this->_server = $server;
			return $this;
		}

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
			if (is_array($filename))
			{
				if (isset($filename['name']))
				{
					$this->_filename = $filename['name'];
				}
			}
			else
			{
				$this->_filename = $filename;
			}

			return $this;
		}

		return $this->_filename;
	}

	public function transform()
	{
		if (($this->_transformations OR $this->_thumbnails) AND @ getimagesize($this->file()))
		{
			if ($this->_transformations)
			{
				Upload_Util::transform_image($this->file(), $this->file(), $this->transformations());
			}

			$this->generate_thumbnails();
		}
	}

	/**
	 * Save the current source to the temp folder
	 */
	public function save_to_temp()
	{
		if ( ! $this->source())
			throw new Kohana_Exception("Cannot move file to temp directory, source does not exist, path :path, filename :filename", array(':path' => $this->path(), ':filename' => $this->filename()));

		if ( ! $this->source()->copied())
		{
			$this->source()->copy_to($this->temp()->directory_path());
			$this->filename($this->source()->filename());
		}

		return $this;
	}

	/**
	 * Generate the thumbnails if they are not generated
	 *
	 * @return Upload_File $this
	 */
	public function generate_thumbnails()
	{
		foreach ($this->thumbnails() as $thumbnail => $thumbnail_params)
		{
			if ( ! is_file($this->file($thumbnail)))
			{
				Upload_Util::transform_image($this->file(), $this->file($thumbnail), $thumbnail_params['transformations']);
			}
		}

		return $this;
	}

	/**
	 * Save the file by moving it from temporary to the upload server
	 * Generate the thumbnails if nesessary
	 * @return Upload_File $this
	 */
	public function save()
	{
		if ($this->_thumbnails AND @ getimagesize($this->file()))
		{
			$this->generate_thumbnails();
		}

		$this->server()->upload_move($this->full_path(), $this->file());

		foreach ($this->thumbnails() as $thumbnail => $thumbnail_params)
		{
			$this->server()->upload_move($this->full_path($thumbnail), $this->file($thumbnail));
		}

		$this->server()->unlink(dirname($this->file()));

		$this->_source = NULL;

		return $this;
	}

	/**
	 * Clear temporary files
	 * @return Upload_File $this
	 */
	public function clear()
	{
		$this->temp()->clear();

		return $this;
	}

	/**
	 * Delete the current file on the server and clear temporary files
	 * @return Upload_File $this
	 */
	public function delete()
	{
		$this->server()->unlink($this->full_path());

		foreach ($this->thumbnails() as $thumbnail => $transformations)
		{
			$this->server()->unlink($this->full_path($thumbnail));
		}

		$this->clear();

		return $this;
	}

	/**
	 * Get the current filename (temp or server)
	 * @param  string $thumbnail
	 * @return string
	 */
	public function file($thumbnail = NULL)
	{
		return $this->location('realpath', $thumbnail);
	}

	/**
	 * Get the current url (temp or server)
	 * @param  string $thumbnail
	 * @param  mixed $protocol
	 * @return string
	 */
	public function url($thumbnail = NULL)
	{
		return $this->location('url', $thumbnail);
	}

	/**
	 * Get the full path with the filename
	 * @param  string $thumbnail
	 * @return string
	 */
	public function full_path($thumbnail = NULL)
	{
		return Upload_Util::combine($this->path(), $thumbnail, $this->filename());
	}

	public function temp_source()
	{
		if ( ! $this->_source OR ! $this->filename())
			return NULL;

		return $this->temp()->directory().'/'.$this->filename();
	}

	protected function location($method, $thumbnail = NULL)
	{
		if ( ! $this->filename())
			return NULL;

		try
		{
			if ($this->_source)
			{
				return $this->temp()->$method(Upload_Util::combine($this->temp()->directory(), $thumbnail, $this->filename()));
			}
			else
			{
				return $this->server()->$method($this->full_path($thumbnail));
			}
		}
		catch (Flex\Storage\Exception_Notsupported $exception)
		{
			return NULL;
		}
	}

	/**
	 * Check if its empty (no filename or source)
	 * @return boolean
	 */
	public function is_empty()
	{
		return ! $this->filename();
	}

	public function __toString()
	{
		return (string) $this->filename();
	}
}
