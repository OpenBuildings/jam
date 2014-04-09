<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 *  Nested behavior for Jam ORM library
 *  Creates a nested set for this model, where an object can have a parent object of the same model. Requires parent_id field in the database. Reference @ref behaviors
 *
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Behavior_Cascade extends Jam_Behavior {

	static public $nesting_depth = 0;

	protected $_callbacks = array();

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.before_save', array($this, 'record_depth'))
			->bind('model.after_save', array($this, 'rollback_depth'));
	}

	public function callbacks()
	{
		return $this->_callbacks;
	}

	protected static function _get_current_children_of_parent($current, $parent, array $children = array())
	{
		if ($current === $parent)
			return $children;

		foreach ($children as $association_name => $association_children)
		{
			$name = is_numeric($association_name) ? $association_children : $association_name;
			$association = Jam::meta($parent)->association($name);

			if ($association AND ! $association->is_polymorphic())
			{
				$child_model = $association->foreign_model;
				$child_children = is_numeric($association_name) ? array() : $association_children;
				$result = Jam_Behavior_Cascade::_get_current_children_of_parent($current, $child_model, $child_children);

				if ($result !== NULL)
					return $result;
			}
		}
		return NULL;
	}

	public static function get_current_children($current, array $children = array())
	{
		foreach ($children as $model => $associations)
		{
			if ($result = Jam_Behavior_Cascade::_get_current_children_of_parent($current, $model, $associations))
				return $result;
		}
		return NULL;
	}

	protected static function _models($models)
	{
		if ($models instanceof Jam_Model)
		{
			return array($models);
		}
		if ($models instanceof Jam_Array_Association AND count($models))
		{
			return $models->as_array();
		}
		return array();
	}

	public static function collect_models(Jam_Model $model, array $children = array())
	{
		$collection = array($model);

		foreach ($children as $child_name => $child_children)
		{
			if (is_numeric($child_name))
			{
				$collection = array_merge($collection, Jam_Behavior_Cascade::_models($model->$child_children));
			}
			else
			{
				foreach (Jam_Behavior_Cascade::_models($model->$child_name) as $child_item)
				{
					$collection = array_merge($collection, Jam_Behavior_Cascade::collect_models($child_item, (array) $child_children));
				}
			}
		}
		return $collection;
	}

	/**
	 * Record another level of nesting for model saving
	 */
	public function record_depth()
	{
		Jam_Behavior_Cascade::$nesting_depth++;
	}

	/**
	 * Rollback a level of nesting for model saving, when we reech the top, call the execute method
	 * @param  Jam_Model $model
	 */
	public function rollback_depth(Jam_Model $model)
	{
		Jam_Behavior_Cascade::$nesting_depth--;

		if (Jam_Behavior_Cascade::$nesting_depth === 0)
		{
			$this->execute($model);
		}
	}

	public function execute(Jam_Model $model)
	{
		foreach ($this->callbacks() as $method => $model_names)
		{
			$children = Jam_Behavior_Cascade::get_current_children($model->meta()->model(), $model_names);
			$models = Jam_Behavior_Cascade::collect_models($model, (array) $children);

			call_user_func($method, $model, $models);
		}
	}
}
