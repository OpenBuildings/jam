<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles belongs to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2010-2011 OpenBuildings
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Extension_CountCache extends Jam_Extension {

	protected static function update_count_cache(Jam_Association $association, Jam_model $model, $count = NULL)
	{
		if ($count === NULL)
		{
			$count = $model->builder($association->name)->count();
		}
		else
		{
			$count = max(0, $model->{$association->count_cache} + $count);
		}

		Jam::query($association->model, $model->id())->value($association->count_cache, $count)->update();
	}

	public function initialize(Jam_Attribute $attribute)
	{
		if ($attribute instanceof Jam_Association_BelongsTo)
		{
			$attribute->bind('after.before_save', array($this, 'collect_original'));
			$attribute->bind('after.after_save', array($this, 'update_inverse_association_count'));
			$attribute->bind('after.before_delete', array($this, 'update_count_after_delete'));
		}
		elseif ($attribute instanceof Jam_Association_HasMany) 
		{
			$attribute->bind('after.before_save', array($this, 'update_model_count'));
			$attribute->bind('before.after_save', array($this, 'collect_affected_ids'));
			$attribute->bind('after.after_save', array($this, 'update_affected_models'));
		}
	}

	/**
	 * BELONGSTO METHODS
	 * =================
	 */

	public function collect_original(Jam_Association $association, Jam_Event_Data $data, Jam_Model $model)
	{
		$model->{'_original_'.$association->column} = $model->original($association->column);
	}

	public function update_count_after_delete(Jam_Association $association, Jam_Event_Data $data, Jam_Model $model)
	{
		if ($assoc = $association->inverse_association() AND $assoc->count_cache)
		{	
			if ($model->{$association->column})
			{
				Jam_Extension_CountCache::update_count_cache($assoc, $model->{$association->name}, -1);
			}
		}
	}
				
	public function update_inverse_association_count(Jam_Association $association, Jam_Event_Data $data, Jam_Model $model)
	{
		if ($assoc = $association->inverse_association() AND $assoc instanceof Jam_Association_HasMany AND $assoc->count_cache)
		{
			$item = $model->{$association->name};

			if ($item AND $item->loaded())
			{
				Jam_Extension_CountCache::update_count_cache($assoc, $item);
			}

			$original = $model->{'_original_'.$association->column};

			if ($original AND ( ! $item OR ($item->id() !== $original) ))
			{
				Jam_Extension_CountCache::update_count_cache($assoc, Jam::factory($association->foreign(), $original), -1);
			}
		}
	}

	/**
	 * HASMANY METHODS
	 * =================
	 */

	public function update_model_count(Jam_Association $association, Jam_Event_Data $data, Jam_Model $model, $is_changed)
	{
		if ($model->{$association->name})
		{
			$model->{$association->count_cache} = count($model->{$association->name});
		}
	}

	public function collect_affected_ids(Jam_Association $association, Jam_Event_Data $data, Jam_Model $model)
	{
		if ($item = $model->{$association->name} AND $item->changed())
		{
			$model->_count_cache_affected_ids = array();
			foreach ($item as $item) 
			{
				$model->_count_cache_affected_ids[] = $item->original($association->foreign['field']);
			}
			unset($model->_count_cache_affected_ids[array_search($model->id(), $model->_count_cache_affected_ids)]);
		}
	}

	public function update_affected_models(Jam_Association $association, Jam_Event_Data $data, Jam_Model $model, $is_changed)
	{
		if ($is_changed AND $item = $model->{$association->name} AND $item->changed())
		{
			if ( ! empty($model->_count_cache_affected_ids))
			{
				foreach (Jam::query($association->model)->find($model->_count_cache_affected_ids) as $item) 
				{
					Jam_Extension_CountCache::update_count_cache($association, $item);
				}
			}

			unset($model->_count_cache_affected_ids);
		}
	}
}
