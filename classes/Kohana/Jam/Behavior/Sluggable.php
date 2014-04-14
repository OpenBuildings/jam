<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Resource_Jam_Behavior_Sluggable class
 *
 *  Sluggable behavior for Jam ORM library
 *  Provides functionality to generate a slug based on a combination of the model primary value, model singular name and the id
 *  e.g news: some-news-title-23
 *  Slugs are automatically updated upon save
 *
 * @package    Jam
 * @author     Yasen Yanev
 * @author     Ivan Kerin
 * @author     Haralan Dobrev
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 * @version 1.0
 */
class Kohana_Jam_Behavior_Sluggable extends Jam_Behavior {

	const SLUG = "/^[a-z0-9-]+$/";
	const ID_SLUG = "/^([a-z0-9-]+?-)?([1-9][0-9]*)$/";

	protected $_slug = NULL;

	protected $_pattern = NULL;

	protected $_uses_primary_key = TRUE;

	protected $_auto_save = TRUE;

	protected $_unique = TRUE;

	/**
	 * Initializes the behavior
	 *
	 * It sets the fields used in generating the slug
	 *
	 * @param  Jam_Event $event the jam event for the behavior
	 * @param  Jam_Model      $model The Jam_Model object on which the behavior is applies
	 * @param  string      $name
	 * @return void
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->field('slug', Jam::field('slug'));

		if ($this->_unique)
		{
			$meta->validator('slug', array('unique' => $this->_unique));
		}

		if ( ! $this->_slug)
		{
			$this->_slug = $this->_uses_primary_key ? 'Jam_Behavior_Sluggable::_uses_primary_key_pattern' : 'Jam_Behavior_Sluggable::_no_primary_key_pattern';
		}

		if (empty($this->_pattern))
		{
			$this->_pattern = $this->_uses_primary_key ? Jam_Behavior_Sluggable::ID_SLUG : Jam_Behavior_Sluggable::SLUG;
		}
	}

	/**
	 * Getter for parameter
	 * @return bool
	 */
	public function auto_save()
	{
		return $this->_auto_save;
	}

	/**
	 * Getter for parameter
	 * @return bool
	 */
	public function unique()
	{
		return $this->_unique;
	}

	/**
	 * Getter for parameter
	 * @return bool
	 */
	public function uses_primary_key()
	{
		return $this->_uses_primary_key;
	}

	/**
	 * Getter for parameter
	 * @return bool
	 */
	public function pattern()
	{
		return $this->_pattern;
	}

	/**
	 * Getter for parameter
	 * @return bool
	 */
	public function slug()
	{
		return $this->_slug;
	}

	/**
	 * Called before validation.
	 * If the slug does not use the primary key the slug is built event before
	 * the validation. This way it could be validated and there are no
	 * additional database queries to update it.
	 *
	 * @param  Jam_Model $model
	 */
	public function model_before_check(Jam_Model $model)
	{
		if ($this->auto_save() AND ! $this->uses_primary_key() AND ! $model->changed('slug'))
		{
			// Use the built in slug transformation
			$model->slug = $model->build_slug();
		}
	}

	/**
	 * Called after save.
	 * If the slug uses the primary key it is built after save and it is updated
	 * with an additional database query.
	 *
	 * @param  Jam_Model $model
	 */
	public function model_after_save(Jam_Model $model)
	{
		if ($this->auto_save() AND $this->uses_primary_key() AND ! $model->changed('slug'))
		{
			// Use the built in slug transformation
			$model->slug = $model->build_slug();

			if ($model->slug != $model->original('slug'))
			{
				Jam::update($this->_model)
					->where_key($model->id())
					->value('slug', $model->slug)
					->execute();
			}
		}
	}

	static public function _uses_primary_key_pattern(Jam_Model $model)
	{
		return $model->name().'-'.$model->id();
	}

	static public function _no_primary_key_pattern(Jam_Model $model)
	{
		return $model->name();
	}

	/**
	 * Generates the slug for a model object
	 * @param  Jam_Model $model the Jam_Model object
	 * @return string the generated slug
	 * @uses URL::title to strip obsolete characters and build the slug
	 */
	public function model_call_build_slug(Jam_Model $model, Jam_Event_Data $data)
	{
		$source_string = trim(strtolower(URL::title(call_user_func($this->_slug, $model), '-', TRUE)), '-');

		if (empty($source_string))
			throw new Jam_Exception_Sluggable('The slug source is empty!', $model);

		return $data->return = $source_string;
	}


	/**
	 * Generated a find_by_slug method for Jam_Builder
	 * @param  Jam_Builder    $builder the builder object
	 * @param  string           $slug    the slug to search for
	 * @param  Jam_Event_Data $data
	 * @return void
	 */
	public function builder_call_where_slug(Jam_Query_Builder_Select $builder, Jam_Event_Data $data, $slug)
	{
		if (preg_match($this->_pattern, $slug, $matches))
		{
			$builder->where($this->_uses_primary_key ? ':primary_key' : 'slug', '=', $matches[$this->_uses_primary_key ? 2 : 0]);
		}
		else
		{
			throw new Kohana_Exception("Invalid Slug :slug for :model", array(':slug' => $slug, ':model' => $builder->meta()->model()));
		}
	}

	/**
	 * Generated a find_by_slug method for Jam_Builder
	 * @param  Jam_Builder    $builder the builder object
	 * @param  string           $slug    the slug to search for
	 * @param  Jam_Event_Data $data
	 * @return void
	 */
	public function builder_call_find_by_slug(Jam_Query_Builder_Select $builder, Jam_Event_Data $data, $slug)
	{
		$this->builder_call_where_slug($builder, $data, $slug);
		$data->return = $builder->first();
		$data->stop = TRUE;
	}

	/**
	 * Generates a find_by_slug_insist method for Jam_Builder
	 * @param  Jam_Builder    $builder the builder object
	 * @param  string           $slug    the slug to search for
	 * @param  Jam_Event_Data $data
	 * @return void
	 */
	public function builder_call_find_by_slug_insist(Jam_Query_Builder_Select $builder, Jam_Event_Data $data, $slug)
	{
		$this->builder_call_where_slug($builder, $data, $slug);
		$model = $builder->first_insist();

		$model->matches_slug_insist($slug);

		$data->return = $model;
		$data->stop = TRUE;
	}

	public function model_call_matches_slug(Jam_Model $model, Jam_Event_Data $data, $slug)
	{
		$data->return = ! ($this->_uses_primary_key AND $model->slug AND $model->slug != $slug);
	}

	public function model_call_matches_slug_insist(Jam_Model $model, Jam_Event_Data $data, $slug)
	{
		if ( ! $model->matches_slug($slug))
			throw new Jam_Exception_Slugmismatch("Stale slug :slug for model :model ", $model, $slug);

		$data->return = TRUE;
	}
} // End Jam_Behavior_Sluggable
