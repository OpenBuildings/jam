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

	/**
	 * Set this to false to disable deleting the association table entry when the model is deleted
	 * @var boolean
	 */
	public $join_table_dependent = TRUE;

	/**
	 * Set paranoid column
	 * @var boolean
	 */
	public $join_table_paranoid = NULL;

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
	 * Then n ame of the join table
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

		if ($this->join_table_paranoid === TRUE)
		{
			$this->join_table_paranoid = 'is_deleted';
		}

		if ( ! $this->association_foreign_key)
		{
			$this->association_foreign_key = $this->foreign_model.'_id';
		}
	}

	/**
	 * Return a Jam_Query_Builder_Join object to allow a query to join with this association
	 * @param  string $alias table name alias
	 * @param  string $type  join type (LEFT, NATURAL)
	 * @return Jam_Query_Builder_Join
	 */
	public function join($alias, $type = NULL)
	{
		$join = Jam_Query_Builder_Join::factory($this->join_table, $type)
			->context_model($this->model)
			->model($this->foreign_model)
			->on($this->join_table.'.'.$this->foreign_key, '=', ':primary_key');

		if ($this->join_table_paranoid) {
			$join->on($this->join_table.'.'.$this->join_table_paranoid, '=', DB::expr('0'));
		}

		return $join
			->join_table($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
				->on(':primary_key', '=' , $this->join_table.'.'.$this->association_foreign_key)
				->context_model($this->model)
			->end();
	}

	public function collection(Jam_Model $model)
	{
		$collection = Jam::all($this->foreign_model);

		$collection
			->join_table($this->join_table)
				->context_model($this->foreign_model)
				->on($this->association_foreign_key, '=', ':primary_key')
			->end()
			->where($this->join_table.'.'.$this->foreign_key, '=' , $model->id());

		if ($this->join_table_paranoid) {
			$collection->where($this->join_table.'.'.$this->join_table_paranoid, '=', FALSE);
		}

		return $collection;
	}


	/**
	 * After the model is deleted, remove all the join_table entries associated with it,
	 * You can disabled this by setting join_table_dependent to FALSE
	 * @param  Jam_Model $model
	 */
	public function model_after_delete(Jam_Model $model)
	{
		if ($model->loaded() AND $this->join_table_dependent)
		{
			$this->erase_query($model)
				->execute(Jam::meta($this->model)->db());
		}
	}

	/**
	 * Generate a query to delete associated models in the database
	 * @param  Jam_Model $model
	 * @return Database_Query
	 */
	public function erase_query(Jam_Model $model)
	{
		return DB::delete($this->join_table)
			->where($this->foreign_key, '=', $model->id());
	}

	/**
	 * Generate a query to remove models from the association (without deleting them), for specific ids
	 * @param  Jam_Model $model
	 * @param  array     $ids
	 * @return Database_Query
	 */
	public function remove_items_query(Jam_Model $model, array $ids)
	{
		return DB::delete($this->join_table)
			->where($this->foreign_key, '=', $model->id())
			->where($this->association_foreign_key, 'IN', $ids);
	}

	/**
	 * Generate a query to add models from the association (without deleting them), for specific ids
	 * @param  Jam_Model $model
	 * @param  array     $ids
	 * @return Database_Query
	 */
	public function add_items_query(Jam_Model $model, array $ids)
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
