<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 *  Clipping behavior for Jam ORM library 
 *  
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Behavior_Sortable extends Jam_Behavior 
{ 
	public $_field = 'sort_position';

	public function initialize(Jam_Event $event, $model, $name) 
	{
		parent::initialize($event, $model, $name);

		Jam::meta($model)->field($this->_field, Jam::field('integer', array('default' => 0)));	
	}

	/**
	 * Perform an order by position at the end of the select
	 * 
	 * @param Jam_Builder $builder 
	 */
	public function builder_before_select(Jam_Builder $builder)
	{
		$builder->order_by_position();
	}

	/**
	 * $builder->order_by_position()
	 * 
	 * @param Jam_Builder $builder 
	 */
	public function builder_call_order_by_position(Jam_Builder $builder)
	{
		$builder->order_by($this->_field, "ASC");
	}

	/**
	 * Set the position to the last item when creating
	 * 
	 * @param Jam_Model $model 
	 */
	public function model_before_create(Jam_Model $model)
	{
		if ( ! $model->{$this->_field})
		{
			$model->{$this->_field} = Jam::query($this->_model)->count();
		}
	}

	/**
	 * Helper method to perform ordering for arrays of models
	 * 
	 * @param Jam_Model $item1 
	 * @param Jam_Model $item2 
	 */
	public function compare(Jam_Model $item1, Jam_Model $item2)
	{
		return $item1->{$this->_field} - $item2->{$this->_field};
	}
} // End Jam_Behavior_Sluggable