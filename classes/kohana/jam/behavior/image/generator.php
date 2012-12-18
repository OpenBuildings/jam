<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 *  @copyright 2011 Despark Ltd.
 *  @version 1.0
 *  @author Ivan Kerin
 */
class Kohana_Jam_Behavior_Image_Generator extends Jam_Behavior {

	protected $_images = array('cover' => array('dynamic_filename' => FALSE));
	protected $_auto_generate_filename = TRUE;
	protected $_file = NULL;

	public function model_call_image_generator(Jam_Model $model, Jam_Event_Data $data, $image = 'cover')
	{
		$generated = new Image_Generator($model, $image);

		if ($this->_file)
		{
			$generated->file_template($this->_file);
		}

		if (Arr::path($this->_images, $image.'.dynamic_filename'))
		{
			$generated->filename_generator("image_generator_{$image}_filename");
		}

		$data->return = $generated;
	}

	public static function convert_options($raw_images)
	{
		$images = array();

		foreach ($raw_images as $image => $options) 
		{
			if (is_numeric($image))
			{
				$images[$options] = array('cache' => TRUE);
			}
			else
			{
				$images[$image] = $options;
			}
		}
		return $images;
	}

	public function initialize(Jam_Event $event, $model, $name) 
	{			
		parent::initialize($event, $model, $name);

		$meta = Jam::meta($model);

		$this->_images = Jam_Behavior_Image_Generator::convert_options($this->_images);

		foreach ($this->_images as $image => $options) 
		{
			if (Arr::get($options, 'cache'))
			{
				$meta->field('image_generator_'.$image.'_filename', Jam::field('string'));
			}
		}
	}

	public function model_call_image_generator_clear(Jam_Model $model, Jam_Event_Data $data)
	{
		foreach ($this->_images as $image => $options) 
		{
			$model->image_generator($image)->clear();
		}
	}

	public function model_call_image_generator_update(Jam_Model $model, Jam_Event_Data $data)
	{
		foreach ($this->_images as $image => $options) 
		{
			$model->image_generator($image)->update_cache();
		}
	}

	public function model_before_update(Jam_Model $model)
	{
		$model->update_generated();
	}

}
