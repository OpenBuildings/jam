<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * @package    Despark/jam-upload
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Kohana_Jam_Field_Upload extends Jam_Field {

	/**
	 * @var  boolean  whether or not to delete the old file when a new file is added
	 */
	public $delete_file = TRUE;

	/**
	 * @var boolean save the sizes of the image when saving the field
	 */
	public $save_size = FALSE;

	/**
	 * @var  string  the server used to store the file
	 */
	public $server = 'default';

	/**
	 * @var string the path to the file
	 */
	public $path = ":model/:id";

	/**
	 * @var  array  an array of transformation to apply to the image
	 */
	public $transformations = array();

	/**
	 * @var bool|string the field that has the data which server we are using at the moment
	 */
	public $dynamic_server = NULL;

	/**
	 * @var  array  specifications for all of the thumbnails that should be automatically generated when a new image is uploaded
	 */
	public $thumbnails = array();

	public function get(Jam_Validated $model, $upload_file, $is_changed)
	{
		if ( ! ($upload_file instanceof Upload_File))
		{
			$upload_file = $this->upload_file($model);
		}

		return $upload_file
			->server($this->dynamic_server ? $model->{$this->dynamic_server} : $this->server)
			->path($this->path($model));
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ( ! $is_changed)
			return $this->upload_file($model)->filename($value);

		if ($value instanceof Upload_File)
		{
			$upload_file = $value;
		}
		else
		{
			$upload_file = $model->{$this->name};

			if ($value === NULL)
			{
				$upload_file->filename('');
			}
			elseif ($value)
			{
				if (Upload_Source::valid($value))
				{
					$upload_file->source($value);
				}

				$upload_file->filename($value);
			}

		}

		return $upload_file->path($this->path($model));
	}

	/**
	 * Move the file to the temp directory even before it validates, so validaitons can be properly performed
	 * @param  Jam_Model $model
	 */
	public function model_before_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $upload_file = $model->{$this->name} AND $upload_file->source())
		{
			try {
				$upload_file->save_to_temp();
			} catch (Kohana_Exception $e) {
				$model->errors()->add($this->name, 'uploaded_native', array(':message' => $e->getMessage()));
			}
		}
	}

	/**
	 * After the validation for this field passes without any errors, fire up transformations and thumbnail generation
	 * @param  Jam_Model $model
	 */
	public function model_after_check(Jam_Model $model)
	{
		if ($model->changed($this->name) AND ! $model->errors($this->name) AND $upload_file = $model->{$this->name} AND $upload_file->source())
		{
			$upload_file->transform();
		}
	}

	/**
	 * Cleanup temporary file directories when the files are successfully saved
	 * @param  Jam_Model $model
	 * @param  boolean $is_changed
	 */
	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $upload_file = $model->{$this->name} AND $upload_file->source())
		{
			$upload_file
				->server($this->dynamic_server ? $model->{$this->dynamic_server} : $this->server)
				->path($this->path($model));

			// Delete the old file if there was one
			if ($this->delete_file AND $original = $model->original($this->name))
			{
				$this->upload_file($model)->filename($original)->delete();
			}

			$upload_file->save();
		}

		if ($model->changed($this->name) AND $upload_file = $model->{$this->name})
		{
			$upload_file->clear();
		}
	}

	/**
	 * Get the filename to store in the database
	 * @param  Jam_Model $model
	 * @param  Upload_File $upload_file
	 * @param  boolean $is_loaded
	 */
	public function convert(Jam_Validated $model, $upload_file, $is_loaded)
	{
		return ($upload_file AND $upload_file !== $this->default) ? $upload_file->filename() : $upload_file;
	}

	/**
	 * Remove files on model deletion
	 *
	 * @param  Jam_Model $model
	 * @param  boolean $is_changed
	 */
	public function model_after_delete(Jam_Validated $model, Jam_Event_Data $data, $delete_finishd)
	{
		if ($this->delete_file AND $delete_finishd AND $upload_file = $model->{$this->name})
		{
			$upload_file->delete();
		}
	}

	/**
	 * Get upload file object for a given model
	 *
	 * @param  Jam_Model $model
	 * @return Upload_File
	 */
	public function upload_file(Jam_Model $model)
	{
		$upload_file = new Upload_File($this->server, $this->path($model));

		if ($this->transformations)
		{
			$upload_file->transformations($this->transformations);
		}

		if ($this->thumbnails)
		{
			$upload_file->thumbnails($this->thumbnails);
		}

		return $upload_file;
	}

	/**
	 * Get the localized path for a given model, so that there are less filename conflicts and files are easily located,
	 * for example the default path is model/id so that Model_Image(1) images will be stored as images/1/file.jpg
	 *
	 * @param  Jam_Model $model the model for the context
	 * @return string
	 */
	protected function path(Jam_Model $model)
	{
		$converted_params = array();
		preg_match_all('#\:[a-zA-z]*#', $this->path, $params);
		foreach ($params[0] as $param)
		{
			switch ($param) {

				case ':column':
					$converted_params[$param] = $this->column;
				break;

				case ':model':
					$converted_params[$param] = $this->model;
				break;

				case ':name':
					$converted_params[$param] = $this->name;
				break;

				case ':id':
					$converted_params[$param] = ($model->loaded()) ? $model->id() : 'new';
				break;

				case ':id_group':
					$converted_params[$param] = ($model->loaded()) ? ceil($model->id() / 10000) : 'new';
				break;

				default:
					$converted_params[$param] = $model->{str_replace(':','', $param)};
			}
		}
		return rtrim(strtr($this->path, $converted_params), '/').'/';
	}

} // End Jam_Core_Field_File
