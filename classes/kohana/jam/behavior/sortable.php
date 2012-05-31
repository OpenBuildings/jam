<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 *  Clipping behavior for Jam ORM library 
 *  
 *  @copyright 2011 Despark Ltd.
 *  @version 1.0
 *  @author Ivan Kerin
 */

class Kohana_Jam_Behavior_Sortable extends Jam_Behavior 
{ 
	public $_field = 'sort_position';

	public function initialize(Jam_Event $event, $model, $name) 
	{
		parent::initialize($event, $model, $name);

		Jam::meta($model)->field($this->_field, Jam::field('integer', array('default' => 0)));	
	}

	public function builder_before_select(Jam_Builder $builder)
	{
		$builder->order_by_position();
	}

	public function builder_call_order_by_position(Jam_Builder $builder)
	{
		$builder->order_by($this->_field, "ASC");
	}

	public function model_before_create(Jam_Model $model)
	{
		if ( ! $model->{$this->_field})
		{
			$model->{$this->_field} = Jam::query($this->_model)->count();
		}
	}

	public function compare(Jam_Model $item1, Jam_Model $item2)
	{
		return $item1->{$this->_field} - $item2->{$this->_field};
	}
} // End Jam_Behavior_Sluggable