<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package    OpenBuildings/jam-upload
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
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
	 * @var  array  specifications for all of the thumbnails that should be automatically generated when a new image is uploaded
	 */
	public $thumbnails = array();

	public function attribute_get($model, $value, $is_changed)
	{
		if ($value instanceof Upload_File)
			return $value;
		
		$upload_file = $this->upload_file($model);
		$upload_file->filename($value);

		if ($is_changed)
		{
			$upload_file->source($value);
		}

		return $upload_file;
	}

	public function attribute_before_check($model, $is_changed)
	{
		if ($is_changed AND $upload_file = $model->{$this->name} AND $upload_file->source())
		{
			$upload_file->save_to_temp();
		}
	}

	/**
	 * Cleanup temporary file directories when the files are successfully saved
	 * @param  Jam_Model $model
	 * @param  boolean $is_changed 
	 */
	public function attribute_after_save($model, $is_changed)
	{
		if ($is_changed AND $upload_file = $model->{$this->name} AND $upload_file->source())
		{
			$upload_file->path($this->path($model));

			// Delete the old file if there was one
			if ($this->delete_file AND $original = $model->original($this->name))
			{
				$this->upload_file($model)->filename($original)->delete();
			}

			$upload_file->save();
		}

		if ($is_changed AND $upload_file = $model->{$this->name})
		{
			$upload_file->cleanup();
		}
	}

	/**
	 * Get the filename to store in the database
	 * @param  Jam_Model $model       
	 * @param  Upload_File $upload_file 
	 * @param  boolean $is_loaded   
	 */
	public function attribute_convert($model, $upload_file, $is_loaded)
	{
		if ( ! ($upload_file instanceof Upload_File))
		{
			$upload_file = $this->attribute_get($model, $upload_file, FALSE);
		}

		return $upload_file->filename();
	}

	/**
	 * Remove files on model deletion
	 * 
	 * @param  Jam_Model $model      
	 * @param  boolean $is_changed 
	 */
	public function attribute_after_delete($model, $is_changed)
	{
		if ($this->delete_file AND $upload_file = $model->{$this->name})
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

		if ($this->save_size AND $model->get($this->name.'_width') AND $model->get($this->name.'_height'))
		{
			$upload_file->set_size($model->get($this->name.'_width'), $model->get($this->name.'_height'));
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

				default:
					$converted_params[$param] = $model->{str_replace(':','', $param)};
			}
		}
		return rtrim(strtr($this->path, $converted_params), '/').'/';
	}

} // End Jam_Core_Field_File