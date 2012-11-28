<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Handles has one to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Hasmany extends Jam_Association_Collection {

	public $as;

	public $foreign_key = NULL;

	public $polymorphic_key = NULL;

	public $count_cache = NULL;

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

		if ( ! $this->foreign_key)
		{
			$this->foreign_key = $this->model.'_id';
		}

		// Polymorphic associations
		if ($this->as)
		{
			$this->foreign_key = $this->as.'_id';
			$this->polymorphic_key = $this->as.'_model';
		}

		// Count Cache
		if ($this->count_cache)
		{
			if ($this->is_polymorphic())
				throw new Kohana_Exception('Cannot use count cache on polymorphic associations');
			
			if ($this->count_cache === TRUE)
			{
				$this->count_cache = $this->name.'_count';
			}

			$meta->field($this->count_cache, Jam::field('integer', array('default' => 0, 'allow_null' => FALSE)));

			// $this->extension('countcache', Jam::extension('countcache'));
		}
	}

	public function join($alias, $type = NULL)
	{
		$join = Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type)
			->context_model($this->model)
			->on($this->foreign_key, '=', ':primary_key');

		if ($this->is_polymorphic())
		{
			$join->on($this->polymorphic_key, '=', DB::expr('"'.$this->foreign_model.'"'));
		}

		return $join;
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		$builder = Jam_Query_Builder_Dynamic::factory($this->foreign_model)
			->where($this->foreign_key, '=', $model->id());

		if ($this->is_polymorphic())
		{
			$builder->where($this->polymorphic_key, '=', $model->meta()->model());
		}

		if ($is_changed)
		{
			$value = Jam_Query_Builder_Dynamic::convert_collection_to_array($value);
			$builder->result(new Jam_Query_Builder_Dynamic_Result($value, NULL, FALSE));
		}

		return $builder;
	}

	public function model_before_delete(Jam_Model $model)
	{
		switch ($this->dependent) 
		{
			case Jam_Association::DELETE:
				foreach ($model->{$this->name} as $item) 
				{
					$item->delete();
				}
			break;

			case Jam_Association::ERASE:
				$delete = Jam_Query_Builder_Delete::factory($this->foreign_model)
					->where($this->foreign_key, '=', $model->id());

				if ($this->is_polymorphic())
				{
					$delete->where($this->polymorphic_key, '=', $model->meta()->model());
				}
				
				$delete->execute();

			break;

			case Jam_Association::NULLIFY:
				$nullify = Jam_Query_Builder_Update::factory($this->foreign_model)
					->value($this->foreign_key, NULL)
					->where($this->foreign_key, '=', $model->id());

				if ($this->is_polymorphic())
				{
					$nullify
						->where($this->polymorphic_key, '=', $model->meta()->model())
						->value($this->polymorphic_key, NULL);
				}

				$nullify->execute();

			break;
		}
	}

	public function model_after_save(Jam_Model $model)
	{
		if ($model->changed($this->name) AND $collection = $model->{$this->name} AND $collection->changed())
		{
			list($old_ids, $new_ids) = $this->diff_collection_ids($model, $collection);

			if ($old_ids)
			{
				$nullify = Jam_Query_Builder_Update::factory($this->foreign_model)
					->where(':primary_key', 'IN', $old_ids)
					->value($this->foreign_key, NULL);

				if ($this->is_polymorphic())
				{
					$nullify->value($this->polymorphic_key, NULL);
				}

				$nullify->execute();
			}
			
			if ($new_ids)
			{
				$assign = Jam_Query_Builder_Update::factory($this->foreign_model)
					->where(':primary_key', 'IN', $new_ids)
					->value($this->foreign_key, $model->id());

				if ($this->is_polymorphic())
				{
					$assign->value($this->polymorphic_key, $model->meta()->model());
				}
				$assign->execute();
			}
		}
	}

	/**
	 * See if the association is polymorphic
	 * @return boolean 
	 */
	public function is_polymorphic()
	{
		return (bool) $this->as;
	}
} // End Kohana_Jam_Association_HasMany
