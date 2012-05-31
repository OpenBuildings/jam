<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 *  Uploadable behavior for Jam ORM library
 *  
 *  @copyright 2011 Despark Ltd.
 *  @version 1.0
 *  @author Radil Radenkov
 */

abstract class Kohana_Jam_Behavior_Uploadable extends Jam_Behavior 
{	
	public $_thumbnails = array();
	public $_server = NULL;
	public $_save_size = FALSE;
	public $_path = NULL;

	public function initialize(Jam_Event $event, $model, $name) 
	{
		parent::initialize($event, $model, $name);
		
		Jam::meta($model)->field($name, Jam::field('upload_image', array(
			'path' => $this->_path, 
			'thumbnails' => $this->_thumbnails, 
			'server' => $this->_server, 
			'save_size' => $this->_save_size
		)));
		
		if ($this->_save_size)
		{
			Jam::meta($model)->field($name.'_width', Jam::field('integer'));
			Jam::meta($model)->field($name.'_height', Jam::field('integer'));
			Jam::meta($model)->field('is_portrait', Jam::field('integer'));
		}
	}

	public function model_before_save(Jam_Model $model, Jam_Event_Data $data)
	{
		if ($model->changed($this->_name) AND is_file($model->{$this->_name}->file()))
		{
			list($model->{$this->_name.'_width'}, $model->{$this->_name.'_height'}) = getimagesize($model->{$this->_name}->file());
			if ($model->{$this->_name.'_width'} AND $model->{$this->_name.'_height'})
			{
				$model->is_portrait = (bool) (($model->{$this->_name.'_width'} / $model->{$this->_name.'_height'}) < 1);
			}
		}
	}
	
}