<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 *  @copyright 2011 Despark Ltd.
 *  @version 1.0
 *  @author Ivan Kerin
 */
class Kohana_Jam_Behavior_Image_Generator extends Jam_Behavior {

	protected $_images = array('cover');
	protected $_file = NULL;
	protected $_path_dir = NULL;
	protected $_web_dir = NULL;

	public function __construct($params = array())
	{
		parent::__construct($params);

		$this->_path_dir = Kohana::$config->load('jam.image_generator.path_dir');
		$this->_web_dir = Kohana::$config->load('jam.image_generator.web_dir');

		if ( ! $this->_file)
		{
			$this->_file = Kohana::$config->load('jam.image_generator.file');
		}
	}

	public function web_dir()
	{
		return $this->_web_dir;
	}

	public function path_dir()
	{
		return $this->_path_dir;
	}

	public function initialize(Jam_Event $event, $model, $name) 
	{			
		parent::initialize($event, $model, $name);

		$meta = Jam::meta($model);
		foreach ($this->_images as $image) 
		{
			$meta->field('image_generator_'.$image.'_filename', Jam::field('string'));
		}
	}

	protected function file(Jam_Model $model, $image = 'cover')
	{
		return strtr($this->_file, array(
			':model' => $model->meta()->model(),
			':group' => ceil($model->id() / 10000),
			':filename' => $model->{"image_generator_{$image}_filename"},
			':id' => $model->id(),
			':image' => $image,
		));
	}

	public function model_call_generated_path(Jam_Model $model, Jam_Event_Data $data, $image = 'cover')
	{
		$data->return = $this->path_dir().$this->file($model, $image);
	}

	public function model_call_generated_url(Jam_Model $model, Jam_Event_Data $data, $image = 'cover')
	{
		$data->return = URL::site($this->web_dir().$this->file($model, $image));
	}

	public function filename(Jam_Model $model, $images)
	{
		$ids = array();
		foreach ($images as $image) 
		{
			$ids = $image->meta()->model().'-'.$image->id();
		}
		return $model->id.'_'.md5(join(', ', $ids));
	}

	public function model_call_clear_old_generated_images(Jam_Model $model)
	{
		$new_filenames = array();

		foreach ($this->_images as $image) 
		{
			$field = 'image_generator_'.$image.'_filename';

			$new_filenames[$field] = $model->{$field}();
			if ($new_filenames[$field] !==  $model->{$field} AND is_file($model->generated_path($image)))
			{
				unlink($model->generated_path($image));
			}
		}
		
		if ($model->changed())
		{
			$model->update_fields($new_filenames);
		}
	}

	public function model_call_update_generated_image(Jam_Model $model, Jam_Event_Data $data, $image = 'cover')
	{
		$image = $model->{'image_generator_'.$image}();
		$image->save();
	}

}
