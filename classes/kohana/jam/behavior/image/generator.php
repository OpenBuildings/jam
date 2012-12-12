<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 *  @copyright 2011 Despark Ltd.
 *  @version 1.0
 *  @author Ivan Kerin
 */
class Kohana_Jam_Behavior_Image_Generator extends Jam_Behavior {

	protected $_images = array('cover');
	protected $_auto_generate_filename = TRUE;
	protected $_file = NULL;

	public function model_call_image_generator(Jam_Model $model, Jam_Event_Data $data, $image = 'cover')
	{
		$generated = new Image_Generator($model, $image);

		if ($this->_file)
		{
			$generated->file_template($this->_file);
		}

		$data->return = $generated;
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

	public function model_call_update_generated(Jam_Model $model, Jam_Event_Data $data)
	{
		foreach ($this->_images as $image) 
		{
			$model->generated($image)->update_cache();
		}
	}

}
