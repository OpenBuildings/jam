<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This class handles getting data from different sources (url, upload, stream)
 * and can copy the content of the source to a specified local directory
 *
 * @package    Jam
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
*/
abstract class Kohana_Upload_Source {

	const TYPE_URL = 'url';
	const TYPE_STREAM = 'stream';
	const TYPE_TEMP = 'temp';
	const TYPE_FILE = 'file';
	const TYPE_UPLOAD = 'upload';

	/**
	 * Determine if the source is a valid one
	 * @param  mixed $source
	 * @return boolean
	 */
	public static function valid($source)
	{
		return Upload_Source::guess_type($source) !== FALSE;
	}

	/**
	 * Store any native errors occurred during upload
	 */
	protected $_error;

	public function error($error = NULL)
	{
		if ($error !== NULL)
		{
			$this->_error = $error;
			return $this;
		}

		return $this->_error;
	}

	/**
	 * Guess the type of the source:
	 *
	 *  - Upload_Source::TYPE_URL
	 *  - Upload_Source::TYPE_STREAM
	 *  - Upload_Source::TYPE_TEMP
	 *  - Upload_Source::TYPE_FILE
	 *  - Upload_Source::TYPE_UPLOAD
	 *  - FALSE
	 *
	 * @param  mixed $source
	 * @return string|boolean
	 */
	public static function guess_type($source)
	{
		if (is_array($source))
		{
			return (Upload::valid($source) AND $source['error'] !== UPLOAD_ERR_NO_FILE) ? Upload_Source::TYPE_UPLOAD : FALSE;
		}
		elseif ($source == 'php://input')
		{
			return Upload_Source::TYPE_STREAM;
		}
		elseif (Valid::url($source))
		{
			return Upload_Source::TYPE_URL;
		}
		elseif (substr_count($source, DIRECTORY_SEPARATOR) === 1)
		{
			return Upload_Source::TYPE_TEMP;
		}
		elseif (is_file($source))
		{
			return Upload_Source::TYPE_FILE;
		}
		else
		{
			return FALSE;
		}
	}

	public static function factory($data)
	{
		return new Upload_Source($data);
	}

	protected $_filename;
	protected $_data = NULL;
	protected $_type = NULL;
	protected $_copied = FALSE;

	public function __construct($data)
	{
		$this->_data = $data;

		if (($type = Upload_Source::guess_type($data)))
		{
			$this->_type = $type;
		}
	}

	public function type()
	{
		return $this->_type;
	}

	public function data()
	{
		return $this->_data;
	}

	public function copied()
	{
		return $this->_copied;
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

	/**
	 * move the uploaded file to a specified location, throw an exception on upload error (with appropriate error message)
	 * Return the filename
	 *
	 * @param  array $data
	 * @param  string $directory
	 * @return string
	 */
	public static function process_type_upload(array $data, $directory)
	{
		if ( ! Upload::not_empty($data))
		{
			$errors = array(
				UPLOAD_ERR_OK          => 'No errors.',
				UPLOAD_ERR_INI_SIZE    => 'must not be larger than '.ini_get('post_max_size'),
				UPLOAD_ERR_FORM_SIZE   => 'must not be larger than specified',
				UPLOAD_ERR_PARTIAL     => 'was only partially uploaded.',
				UPLOAD_ERR_NO_FILE     => 'no file was uploaded.',
				UPLOAD_ERR_NO_TMP_DIR  => 'missing a temporary folder.',
				UPLOAD_ERR_CANT_WRITE  => 'failed to write file to disk.',
				UPLOAD_ERR_EXTENSION   => 'file upload stopped by extension.',
			);

			throw new Jam_Exception_Upload("File not uploaded properly. Error: :error", array(':error' => Arr::get($errors, Arr::get($data, 'error'), '-')));
		}

		if ( ! move_uploaded_file($data['tmp_name'], Upload_Util::combine($directory, $data['name'])))
			throw new Jam_Exception_Upload('There was an error moving the file to :directory', array(':directory' => $directory));

		return $data['name'];
	}

	/**
	 * Check if the file can be operated with.
	 *
	 * @param  string $file
	 */
	public static function valid_file($file)
	{
		return (strpos($file, DOCROOT) === 0 OR Kohana::$environment === Kohana::TESTING);
	}

	/**
	 * Copy the source data to a directory
	 *
	 * @param  string $directory
	 * @return Upload_Source $this
	 */
	public function copy_to($directory)
	{
		switch ($this->type())
		{
			case Upload_Source::TYPE_URL:
				$filename = Upload_Util::download($this->data(), $directory, $this->filename());
				$this->filename($filename);
			break;

			case Upload_Source::TYPE_UPLOAD:
				try
				{
					$filename = Upload_Source::process_type_upload($this->data(), $directory);
					$this->filename($filename);
				}
				catch (Jam_Exception_Upload $e)
				{
					$this->error($e->getMessage());
				}
			break;

			case Upload_Source::TYPE_STREAM:
				if ( ! $this->filename())
				{
					$this->filename(uniqid());
				}
				Upload_Util::stream_copy_to_file($this->data(), Upload_Util::combine($directory, $this->filename()));
			break;

			case Upload_Source::TYPE_FILE:
				if ( ! Upload_Source::valid_file($this->data()))
					throw new Kohana_Exception("File must be in the document root, or must be testing environment");

				if ( ! $this->filename())
				{
					$this->filename(basename($this->data()));
				}

				copy($this->data(), Upload_Util::combine($directory, $this->filename()));
			break;

			case Upload_Source::TYPE_TEMP:
				if ( ! Upload_Temp::valid($this->data()))
					throw new Kohana_Exception("This file does not exist");

				$this->filename(basename($this->data()));
			break;
		}

		$this->_copied = TRUE;

		return $this;
	}
}
