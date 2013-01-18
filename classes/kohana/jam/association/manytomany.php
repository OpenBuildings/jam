<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles many-to-many relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Manytomany extends Jam_Association_Collection {

	public $join_table_dependent = TRUE;

	/**
	 * The name of the field on the join table, corresponding to the model
	 * @var string
	 */
	public $foreign_key = NULL;

	/**
	 * The name of the field on the join table, corresponding to the key for the foreign model
	 * @var string
	 */
	public $association_foreign_key = NULL;

	/**
	 * Then ame of the join table
	 * @var string
	 */
	public $join_table = NULL;

	/**
	 * Automatically sets foreign to sensible defaults.
	 *
	 * @param   string  $model
	 * @param   string  $name
	 * @return  void
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		if ( ! $this->join_table)
		{
			$this->join_table = Jam_Association_Collection::guess_through_table($this->foreign_model, $this->model);
		}

		if ( ! $this->foreign_key)
		{
			$this->foreign_key = $this->model.'_id';
		}

		if ( ! $this->association_foreign_key)
		{
			$this->association_foreign_key = $this->foreign_model.'_id';
		}
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		$builder = Jam_Query_Builder_Associated::factory($this->foreign_model)
			->parent($model)
			->association($this);

		if ($model->loaded())
		{
			$builder	
				->join_nested($this->join_table)
					->context_model($this->foreign_model)
					->on($this->association_foreign_key, '=', ':primary_key')
				->end()
				->where($this->join_table.'.'.$this->foreign_key, '=' , $model->id());
		}	
		else
		{
			$builder->load_fields(array());
		}

		if ($is_changed)
		{
			$builder->set($value);
		}

		return $builder;
	}

	public function join($alias, $type = NULL)
	{
		return Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on(':primary_key', '=' , $this->join_table.'.'.$this->association_foreign_key)
			->join_nested($this->join_table, $type)
				->context_model($this->model)
				->on($this->join_table.'.'.$this->foreign_key, '=', ':primary_key')
			->end();
	}

	public function model_after_delete(Jam_Model $model)
	{
		if ($model->loaded() AND $this->join_table_dependent)
		{
			$this->erase_query($model)
				->execute(Jam::meta($this->model)->db());
		}
	}

	public function erase_query(Jam_Model $model)
	{
		return DB::delete($this->join_table)
			->where($this->foreign_key, '=', $model->id());
	}

	public function remove_items_query(array $ids, Jam_Model $model)
	{
		return DB::delete($this->join_table)
			->where($this->foreign_key, '=', $model->id())
			->where($this->association_foreign_key, 'IN', $ids);
	}

	public function add_items_query(array $ids, Jam_Model $model)
	{
		$query = DB::insert($this->join_table)
			->columns(array($this->foreign_key, $this->association_foreign_key));

		foreach ($ids as $id) 
		{
			$query->values(array($model->id(), $id));
		}

		return $query;
	}
}