<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Sortable behavior for Jam ORM library
 *
 * @package    Jam
 * @category   Behavior
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2014 OpenBuildings, Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Behavior_Sortable extends Jam_Behavior {

	public $_field = 'sort_position';

	public $_scope = NULL;

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->field($this->_field, Jam::field('integer', array('default' => 0)));
	}

	/**
	 * Sort all the ids (in the order given), with one query
	 * @param  array  $ids
	 * @return $this
	 */
	public function builder_call_sort_ids(Database_Query_Builder $builder, Jam_Event_Data $data, array $ids)
	{
		$case = 'CASE  `id` ';
		$template = 'WHEN :id THEN :position ';

		foreach ($ids as $i => $id)
		{
			$case .= strtr($template, array(':id' => $id, ':position' => $i));
		}

		$case .= 'END';

		Jam::update($this->_model)->value($this->_field, DB::expr($case))->execute();

		return $this;
	}

	/**
	 * $select->order_by_position()
	 *
	 * @param Jam_Builder $builder
	 */
	public function builder_call_order_by_position(Database_Query_Builder $builder, Jam_Event_Data $data, $direction = NULL)
	{
		$builder->order_by($this->_field, $direction);
	}

	public function builder_before_select(Jam_Query_Builder_Select $select)
	{
		if ( ! $select->order_by)
		{
			$select->order_by_position();
		}
	}

	public function builder_call_where_in_scope(Database_Query_Builder $builder, Jam_Event_Data $data, Jam_Model $model)
	{
		if ($this->_scope)
		{
			foreach ( (array) $this->_scope as $scope_field)
			{
				$builder->where($scope_field, '=', $model->$scope_field);
			}
		}
	}

	public function model_before_update(Jam_Model $model)
	{
		if ( ! $model->changed($this->_field)
		 AND $this->_is_scope_changed($model))
		{
			$model->{$this->_field} = $model->get_position();
		}
	}

	/**
	 * Set the position to the last item when creating
	 *
	 * @param Jam_Model $model
	 */
	public function model_before_create(Jam_Model $model)
	{
		if ( ! $model->changed($this->_field))
		{
			$model->{$this->_field} = $model->get_position();
		}
	}

	public function model_call_switch_position_with(Jam_Model $model, Jam_Event_Data $data, Jam_Model $switch_with)
	{
		$current_position = $model->{$this->_field};
		$model->update_fields($this->_field, $switch_with->{$this->_field});
		$switch_with->update_fields($this->_field, $current_position);
	}

	public function model_call_move_position_to(Jam_Model $model, Jam_Event_Data $data, Jam_Model $to)
	{
		$builder = Jam::update($this->_model)
			->where_in_scope($model);

		if ($model->{$this->_field} !== $to->{$this->_field})
		{
			if ($model->{$this->_field} > $to->{$this->_field})
			{
				$builder
					->where($this->_field, '>=', $to->{$this->_field})
					->where($this->_field, '<', $model->{$this->_field})
					->value($this->_field, DB::expr($this->_field.' + 1'));
			}
			else
			{
				$builder
					->where($this->_field, '<=', $to->{$this->_field})
					->where($this->_field, '>', $model->{$this->_field})
					->value($this->_field, DB::expr($this->_field.' - 1'));
			}
			$builder->execute();
			$model->update_fields($this->_field, $to->{$this->_field});
		}
		else
		{
			$model->update_fields($this->_field, $model->{$this->_field} + 1);
		}
	}

	public function model_call_sibling(Jam_Model $model, Jam_Event_Data $data, $offset)
	{
		$offset = (int) $offset;

		$data->return = Jam::all($this->_model)
			->where_in_scope($model)
			->order_by($this->_field, ($offset > 0) ? 'ASC' : 'DESC')
			->where($this->_field, ($offset > 0) ? '>=' : '<=', $model->{$this->_field} + $offset)
			->first();

		$data->stop = TRUE;
	}

	public function model_call_decrease_position(Jam_Model $model)
	{
		$sibling = $model->sibling(-1);

		if ($sibling)
		{
			$model->switch_position_with($sibling);
		}
	}

	public function model_call_increase_position(Jam_Model $model)
	{
		$sibling = $model->sibling(+1);

		if ($sibling)
		{
			$model->switch_position_with($sibling);
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

	/**
	 * Get a new value for the position field.
	 * This is called before create and before update (it the scope is changed).
	 *
	 * @return int the new position field value
	 * @uses Jam_Behavior_Sortable::builder_call_where_in_scope
	 */
	public function model_call_get_position(Jam_Model $model, Jam_Event_Data $data)
	{
		$last = Jam::all($this->_model)
			->where_in_scope($model)
			->order_by($this->_field, 'DESC');

		if ($model->loaded())
		{
			$last->where(':primary_key', '!=', $model->id());
		}

		$last = $last->first();

		return $data->return = $last ? $last->{$this->_field} + 1 : 1;
	}

	private function _is_scope_changed(Jam_Model $model)
	{
		if ( ! $this->_scope)
			return FALSE;

		foreach ( (array) $this->_scope as $scope_field)
		{
			if ($model->changed($scope_field))
				return TRUE;
		}

		return FALSE;
	}
}
