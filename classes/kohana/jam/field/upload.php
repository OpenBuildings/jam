<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package    OpenBuildings/jam-upload
 * @author     Ivan Kerin
 * @copyright  (c) 2011 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Kohana_Jam_Field_Upload extends Jam_Field {

	/**
	 * @var  boolean  whether or not to delete the old file when a new file is added
	 */
	public $delete_file = TRUE;
	
	/**
	 * @var boolean whether to save the sizes of images alongside file
	 */
	public $save_size = FALSE;

	/**
	 * @var  string  the server used to store the file
	 */
	public $server = 'default';

	/**
	 * @var string the path to the file
	 */
	protected $_path = ":model/:id";

	/**
	 * @var  array  valid types for the file
	 */
	public $types = array();

	protected $_old_filename = null;

	public $temp = null;

	/**
	 * Ensures there is a path for saving set.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);
		$this->_path = Arr::get($options, 'path', $this->_path);

		// Set the path
		$server = Arr::get($options, 'server', $this->server);
		$this->server = ($server instanceof Upload_Server) ? $server : Upload_Server::instance($server);
	}

	/**
	 * Adds a rule that uploads the file.
	 *
	 * @param   Jam_Model  $model
	 * @param   string       $column
	 * @return void
	 */
	public function initialize($model, $column)
	{
		parent::initialize($model, $column);

		// Add a rule to save the file when validating
		$this->rules[] = array(array(':field', '_upload'), array(':validation', ':model', ':field'));
	}

	/**
	 * Implementation for Jam_Field_Supports_Save.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $loaded
	 * @return  void
	 */
	public function save($model, $value, $loaded)
	{
		if ($model->changed($this->name))
		{		
			$this->create_model_temp($model);
			$model->meta()->events()->bind("model.after_save", array($this, 'after_save'));

			if ($model->_temp_server->filename())
			{
				return $model->_temp_server->filename();
			}
			else
			{
				if ( ! $value )
				{
					$this->_delete_old_file($model, $model->original($this->column));
				}

				if (is_array($value) AND isset($value['name']) AND empty($value['name']) )
				{
					$value = $model->original($this->column);
				}
				return $value;
			}
		}
		
		return $value;
	}

	public function after_save($model, $params)
	{
		$this->create_model_temp($model);
		if ($model->_temp_server->filename())
		{
			if($this->_old_filename)
			{
				$this->_delete_old_file($model, $this->_old_filename);
			}
			
			$this->_list_files( $model->_temp_server->pathdir(), '/', $files);
			
			$model->_temp_server->move_to_server($this->server, $this->path($model));
		}
	}
	
	private function _list_files($dir, $path, &$files) {
		if ( ! is_dir($dir)) return ;
		$dir = realpath($dir).DIRECTORY_SEPARATOR;
		$path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		if ( ! is_dir($dir.$path)) return ;

		foreach (glob($dir.$path.'*') as $item)
		{
			if (is_dir($item))
			{
				$this->_list_files($dir, str_replace($dir,'', $item), $files);
			}
			else 
			{
				$files[] = str_replace($dir.DIRECTORY_SEPARATOR,'', $item);
			}
		}
		return true;
	}
	
	/**
	 * Returns the Jam model that this model belongs to.
	 *
	 * @param   Jam_Model    $model
	 * @param   mixed          $value
	 * @return  Jam_Builder
	 */
	public function get($model, $value)
	{
		$this->create_model_temp($model);
		$model->_temp_server->clear_only_empty = TRUE;

		if ($model->_temp_server->filename())
		{
			return new Upload_File(
				$model->_temp_server->filename(),
				$model->_temp_server->hidden_filename(),
				$model->_temp_server->webdir(),
				$model->_temp_server->pathdir(),
				$this->save_size ? @getimagesize($model->_temp_server->filename()) : NULL
			);
		}
		else
		{
			return new Upload_File(
				is_array($value) ? Arr::get($value, 'name') : $value,
				NULL,
				$this->server->webpath($this->path($model)),
				$this->server->realpath($this->path($model)),
				$this->save_size ? array($model->{$this->name.'_width'}, $model->{$this->name.'_height'}) : NULL
			);
		}
	}

	/**
	 * Deletes the file if automatic file deletion
	 * is enabled.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $key
	 * @return  void
	 */
	public function delete($model, $key)
	{
		if ($this->delete_file)
		{
			// Delete file
			$this->_delete_old_file($model, $model->{$this->name});
		}

		return;
	}

	public function check_valid_upload(array $file, $field, Validation $validation, $model )
	{
	  $this->create_model_temp($model);
		// Check if it's a valid file
		if ( ! Upload::valid($file) OR ! ($file['error'] === UPLOAD_ERR_OK))
		{
			$validation->error($field, 'invalid_upload');
			return FALSE;
		}

		// Check to see if it's a valid type
		if ($this->types AND ! Upload::type($file, $this->types))
		{
			$validation->error($field, 'invalid_upload', array(':expected' => $this->types));
			return FALSE;
		}			

		if ( ! isset($file['tmp_name']) OR ! $model->_temp_server->is_uploaded_file($file['tmp_name']))
		{
			$validation->error($field, 'corrupted_file', array(':file' => $file['tmp_name']));
			return FALSE;
		}

		return TRUE;
	}

	public function create_model_temp(Jam_Model $model)
	{
		if( ! isset($model->_temp_server))
		{
			$model->_temp_server = $this->temp ? $this->temp : Upload_Temp::factory();
		}
	}

	/**
	 * Logic to deal with uploading the image file and generating thumbnails according to
	 * what has been specified in the $thumbnails array.
	 *
	 * @param   Validation   $validation
	 * @param   Jam_Model  $model
	 * @param   Jam_Field  $field
	 * @return  bool
	 */
	public function _upload(Validation $validation, $model, $field)
	{
		$this->create_model_temp($model);
		$this->_old_filename = (string) $model->original($field);

		// Get the file from the validation object
		$file = $validation[$field];

		if (is_array($file) AND $file['error'] === UPLOAD_ERR_OK)
		{
			if ( ! $this->check_valid_upload($file, $field, $validation, $model))
			{
				return TRUE;
			}

			$model->_temp_server
				->filename(Jam_Field_Upload::sanitize_filename($file['name']))
				->get_uploaded_file($file['tmp_name']);
		}
		elseif (is_string($file))
		{
			if (is_file($file) AND (strpos($file, APPPATH.'tests/test_data/files/') === 0 OR strpos($file, DOCROOT.'upload/') === 0 ))
			{
				$model->_temp_server->get_file($file);
			}
			elseif ($model->_temp_server->is_file($file))
			{
				$model->_temp_server->populate($file);
			}
			elseif (Valid::url($file))
			{
				return $model->_temp_server->download($file);
			}
			elseif (empty($file) AND $this->delete_file)
			{
				$this->_filename = '';	
			}
			else
			{
				$this->_filename = $file;	
			}
		}

		return TRUE;
	}
	
	public function file($model, $file)
	{
		$this->create_model_temp($model);
		return $model->_temp_server->filename() ? $model->_temp_server->file() : $this->server->realpath($this->path($model).$file);
	}

	public function url($model, $file)
	{
		$this->create_model_temp($model);
		
		return $model->_temp_server->filename() ? $model->_temp_server->url() : $this->server->webpath($this->path($model).$file);
	}

	protected function path($model = null)
	{
		$opts = array();
		preg_match_all('#\:[a-zA-z]*#', $this->_path, $matches);
		if (isset($matches[0]))
		{
			foreach ($matches[0] as $match)
			{
				switch ($match) {
					case ':column':
						$opts[$match] = $this->column;
					break;
					case ':model':
						$opts[$match] = $this->model;
					break;
					case ':name':
						$opts[$match] = $this->name;
					break;
					case ':id':
						$opts[$match] = ($model AND $model->loaded()) ? $model->id() : 'new';
					break;
					default:
						$opts[$match] = $model->{str_replace(':','',$match)};
				}
			}
		}
		return rtrim(strtr($this->_path, $opts), '/').'/';
	}

	static public function sanitize_filename($filename)
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
	 * Deletes the previously used file if necessary.
	 *
	 * @param   string  $filename
	 * @return  void
	 */
	protected function _delete_old_file($model, $file)
	{
		 // Delete the old file if we need to
		if ($this->delete_file AND $file != $this->default)
		{
			// Check if file exists
			if ($this->server->file_exists($this->path($model).$file))
			{
				// Delete file
				$this->server->unlink($this->path($model).$file);
			}
		}
	}


} // End Jam_Core_Field_File