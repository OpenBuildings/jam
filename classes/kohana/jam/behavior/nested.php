<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *  Nested behavior for Jam ORM library
 *  Creates a nested set for this model, where an object can have a parent object of the same model. Requires parent_id field in the database. Reference @ref behaviors  
 * 
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Behavior_Nested extends Jam_Behavior
{
	protected $_field = 'parent_id';
	
	public function initialize(Jam_Event $event, $model, $name) 
	{
		parent::initialize($event, $model, $name);

		Jam::meta($model)->associations(array(
			'parent' => Jam::association('belongsto', array(
				'foreign' => $model,
				'column' => $this->_field,
				'default' => 0,
				'inverse_of' => 'children'
			)),
			'children' => Jam::association('hasmany', array(
				'foreign' => $model.'.'.$this->_field,
				'inverse_of' => 'parent'
			)),
		));
	}
	
	/**
	 * $builder->root() select only the root items 
	 * 
	 * @param Jam_Builder    $builder 
	 * @param Jam_Event_Data $data    
	 * @return Jam_Builder
	 */
	public function builder_call_root(Jam_Builder $builder, Jam_Event_Data $data)
	{														
		$data->return = $builder->where_open()->where($this->_field, '=', 0)->or_where($this->_field, 'IS', NULL)->where_close();
		$data->stop = TRUE;
	}
	
	/**
	 * $model->root() method to get the root element from Jam_Model
	 * 
	 * @param Jam_Model      $model 
	 * @param Jam_Event_Data $data 
	 * @return Jam_Model|NULL
	 */
	public function model_call_root(Jam_Model $model, Jam_Event_Data $data)
	{
		for ($item = $model; $item->parent->loaded(); $item = $item->parent);
		$data->stop = TRUE;
		$data->return = $item;
	}

	/**
	 * $model->is_root() check if an item is a root object
	 * @param Jam_Model      $model 
	 * @param Jam_Event_Data $data  
	 * @return Jam_Model 
	 */
	public function model_call_is_root(Jam_Model $model, Jam_Event_Data $data)
	{
		$data->stop = TRUE;
		$data->return = ! (bool) $model->{$this->_field};
	}
	
	/**
	 * $model->parents() get an array of all the parents of a given item
	 * @param Jam_Model      $model 
	 * @param Jam_Event_Data $data  
	 * @return array
	 */
	public function model_call_parents(Jam_Model $model, Jam_Event_Data $data)
	{	
		$parents = array();	
		for ($item = $model; $item->parent->loaded(); $parents[] = $item = $item->parent);
		
		$data->return = $parents;
		$data->stop = TRUE;
	}																		
}