<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles image uploads
 *
 * @package    OpenBuildings/jam-upload
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
abstract class Kohana_Jam_Field_Upload_Image extends Jam_Field_Upload {

	/**
	 * @var  array  allowed file types
	 */
	public $types = array('jpg', 'gif', 'png', 'jpeg', 'jpe');

	/**
	 * @var  array  an array of transformation to apply to the image
	 */
	public $transformations = array();

	/**
	 * @var  int  image quality
	 */
	public $quality = 100;

	/**
	 * @var  string  image driver
	 */
	public $driver = NULL;

	/**
	 * @var  array  specifications for all of the thumbnails that should be automatically generated when a new image is uploaded
	 *
	 */
	public $thumbnails = array();

	/**
	 * Logic to deal with uploading the image file and generating thumbnails according to
	 * what has been specified in the $thumbnails array.
	 *
	 * @param   Validation   $validation
	 * @param   Jam_Model  $model
	 * @param   string       $field
	 * @return  bool
	 * @uses    Image::factory
	 */
	public function _upload(Validation $validation, $model, $field)
	{
		if ( ! parent::_upload($validation, $model, $field, FALSE))
		{
			// Couldn't save the original untouched
			return FALSE;
		}
		$ext = strtolower(pathinfo($model->_temp_server->file(), PATHINFO_EXTENSION));
	
		if ($model->changed($field) AND $model->_temp_server->filename() AND in_array($ext, $this->types))
		{
			// Process thumbnails
			foreach ($this->thumbnails as $key => $thumbnail)
			{
				// Set thumb
				$thumb = Image::factory($model->_temp_server->file(), $this->driver);

				// Process thumbnail transformations
				$thumb = $this->_transform($thumb, Arr::get( $thumbnail, 'transformations', array()));

				// Save the thumbnail
				$thumb->save($model->_temp_server->thumbnail($key)->file($key), Arr::get($thumbnail, 'quality', $this->quality));
			}

			if (count($this->transformations) > 0)
			{
				// Set image
				$image = Image::factory($model->_temp_server->file(), $this->driver);

				// Process image transformations
				$image = $this->_transform($image, $this->transformations);

				$image->save($model->_temp_server->file(), $this->quality);
			}			
		}

		return TRUE;
	}

	public function thumbnail_file($model, $key, $file)
	{
		$this->create_model_temp($model);
		return $model->_temp_server->file($key, $this->server->realpath(Upload_Server::combine_path($this->path($model), $key, $file)));
	}

	public function thumbnail_url($model, $key, $file)
	{
		$this->create_model_temp($model);
		return $model->_temp_server->url($key, $this->server->webpath(Upload_Server::combine_path($this->path($model), $key, $file)));
	}	

	/**
	 * Applies transformations to the image.
	 *
	 * [!!] Image will not be saved.
	 *
	 * @param   Image  $image
	 * @param   array  $transformations
	 * @return  Image
	 */
	protected function _transform(Image $image, array $transformations)
	{
		// Process tranformations
		foreach ($transformations as $transformation => $params)
		{
			if ($transformation !== 'factory' OR $transformation !== 'save' OR $transformation !== 'render')
			{
				// Call the method excluding the factory, save and render methods
				call_user_func_array(array($image, $transformation), $params);
			}
		}

		return $image;
	}

	/**
	 * Deletes the previously used file if necessary.
	 *
	 * @param   string  $filename
	 * @return  void
	 */
	protected function _delete_old_file($model, $filename)
	{
		 // Delete the old file if we need to
		if ($this->delete_file AND $filename != $this->default)
		{
			// Check if file exists
			if ($this->server->file_exists($this->path($model).$filename))
			{
				// Delete file
				$this->server->unlink($this->path($model).$filename);
			}
			
			foreach ($this->thumbnails as $key => $thumbnail) 
			{
				// Check if file exists
				if ($this->server->file_exists(Upload_server::combine_path($this->path($model), $key, $filename)))
				{
					// Delete file
					$this->server->unlink(Upload_server::combine_path($this->path($model), $key, $filename));
				}
			}
		}
	}

} // End Jam_Core_Field_Image