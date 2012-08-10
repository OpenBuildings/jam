<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 *  Uploadable behavior for Jam ORM library
 *  
 * @package    Jam
 * @category   Behavior
 * @author     Radil Radenkov
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Behavior_Uploadable extends Jam_Behavior 
{	
	public $_thumbnails = array();
	public $_transformations = array();
	public $_server = NULL;
	public $_save_size = FALSE;
	public $_path = NULL;

	public function initialize(Jam_Event $event, $model, $name) 
	{
		parent::initialize($event, $model, $name);
		
		if ($this->_save_size)
		{
			Jam::meta($model)->field($name.'_width', Jam::field('integer'));
			Jam::meta($model)->field($name.'_height', Jam::field('integer'));
			Jam::meta($model)->field('is_portrait', Jam::field('boolean'));
		}
		
		Jam::meta($model)->field($name, Jam::field('upload', array(
			'path' => $this->_path, 
			'thumbnails' => $this->_thumbnails, 
			'transformations' => $this->_transformations, 
			'server' => $this->_server, 
			'save_size' => $this->_save_size
		)));	
	}

	public function model_after_check(Jam_Model $model, Jam_Event_Data $data)
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