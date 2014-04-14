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
class Kohana_Jam_Behavior_Nested extends Jam_Behavior {

	protected $_field = 'parent_id';
	protected $_children_dependent = NULL;

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->associations(array(
			'parent' => Jam::association('belongsto', array(
				'foreign_model' => $this->_model,
				'foreign_key' => $this->_field,
				'inverse_of' => 'children',
			)),
			'children' => Jam::association('hasmany', array(
				'foreign_model' => $this->_model,
				'foreign_key' => $this->_field,
				'inverse_of' => 'parent',
				'dependent' => $this->_children_dependent,
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
	public function builder_call_root(Database_Query $builder, Jam_Event_Data $data, $is_root = TRUE)
	{
		if ($is_root)
		{
			$data->return = $builder->where_open()->where($this->_field, '=', 0)->or_where($this->_field, 'IS', NULL)->where_close();
		}
		else
		{
			$data->return = $builder->where($this->_field, '!=', 0)->where($this->_field, 'IS NOT', NULL);
		}

		$data->stop = TRUE;
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
	 * A special query that gets you the IDs of all the parents
	 * @param  string $child_id starting point id (included in the result)
	 * @return Database_Query
	 */
	public function parents_query($child_id)
	{
		$meta = Jam::meta($this->_model);

		return DB::select()
			->select(array(DB::expr('@row'), '_id'))
			->select(array(DB::expr("(SELECT @row := `{$this->_field}` FROM `{$meta->table()}` WHERE `{$meta->primary_key()}` = _id)"), $this->_field))
			->select(array(DB::expr('@l := @l + 1'), 'lvl'))
			->from(array(DB::expr('(SELECT @row := :id, @l := 0)', array(':id' => $child_id)), 'vars'))
			->from($meta->table())
			->where(DB::expr('@row'), '!=', 0);
	}

	/**
	 * $model->parents() get all the parents of a given item
	 * @param Jam_Model      $model
	 * @param Jam_Event_Data $data
	 * @return array
	 */
	public function model_call_parents(Jam_Model $model, Jam_Event_Data $data)
	{
		$data->return = Jam::all($this->_model)
			->join(array($this->parents_query($model->parent_id), 'recursion_table'))
			->on('recursion_table._id', '=', ':primary_key')
			->order_by('recursion_table.lvl', 'DESC');

		$data->stop = TRUE;
	}

	public static function builder_call_nested_collection(Database_Query_Builder $builder, Jam_Event_Data $data, $groups = FALSE, $depth = 0)
	{
		$choices = array();

		if ($depth == 0)
		{
			$builder->root();
		}

		$items = $builder->order_by(':name_key', 'ASC');

		foreach ($items as $item)
		{
			if ($groups AND $depth == 0)
			{
				$choices[$item->name()] = array();

				if ($item->children->count())
				{
					$choices[$item->name()] = self::builder_call_nested_collection($item->children->collection(), $data, $groups, $depth+1);
				}
			}
			else
			{
				$choices[$item->id()] = str_repeat('&nbsp;&nbsp;&nbsp;', $depth).$item->name();

				if ($item->children->count())
				{
					$choices = Arr::merge($choices, self::builder_call_nested_collection($item->children->collection(), $data, $groups, $depth+1));
				}
			}
		}

		if ($depth == 0)
		{
			$data->return = $choices;
		}
		else
			return $choices;
	}
}
