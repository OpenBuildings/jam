<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles belongs to relationships
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Belongsto extends Jam_Association {

	/**
	 * Indicates whether this is a polymorphic association. Will add the polymorphic field,
	 * named <name>_model, if you set this as a string you can change the name of the field to it.
	 * @var boolean|string
	 */
	public $polymorphic = FALSE;

	/**
	 * The name of the actual field holding the id of the associated model. Defaults to
	 * <name>_id
	 * @var string
	 */
	public $foreign_key = NULL;

	public $inverse_of = NULL;

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
			$this->foreign_key = $name.'_id';
		}

		if ($this->foreign_key === $name)
			throw new Kohana_Exception('In association ":name" for model ":model" - invalid foreign_key name. Field and Association cannot be the same name', array(
					':model' => $this->model,
					':name' => $name,
				));
		
		$meta->field($this->foreign_key, Jam::field('integer', array(
			'default' => NULL,
			'allow_null' => TRUE,
			'convert_empty' => TRUE,
		)));

		if ($this->is_polymorphic())
		{
			if ( ! is_string($this->polymorphic))
			{
				$this->polymorphic = $name.'_model';
			}

			$meta->field($this->polymorphic, Jam::field('string', array('convert_empty' => TRUE)));
		}
		elseif ( ! $this->foreign_model)
		{
			$this->foreign_model = $name;
		}
	}

	public function is_polymorphic()
	{
		return (bool) $this->polymorphic;
	}

	public function foreign_model(Jam_Model $model)
	{
		return $this->is_polymorphic() ? $model->{$this->polymorphic} : $this->foreign_model;
	}

	public function model_after_check(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name) AND Jam_Association::is_changed($value))
		{
			if ( ! $model->{$this->name}->is_validating() AND ! $model->{$this->name}->check())
			{
				$model->errors()->add($this->name, 'association', array(':errors' => $model->{$this->name}->errors()));
			}
		}
	}

	public function join($alias, $type = NULL)
	{
		if ($this->is_polymorphic())
		{
			$foreign_model = $alias;

			if ( ! $foreign_model)
				throw new Kohana_Exception('Jam does not join automatically polymorphic belongsto associations!');

			$join = Jam_Query_Builder_Join::factory($foreign_model, $type)
				->on(DB::expr("'$foreign_model'"), '=', $this->polymorphic);
		}
		else
		{
			$join = Jam_Query_Builder_Join::factory($alias ? array($this->foreign_model, $alias) : $this->foreign_model, $type);
		}

		return $join
			->context_model($this->model)
			->on(':primary_key', '=', $this->foreign_key);
	}

	protected function _find_item($foreign_model, $key)
	{
		return Jam_Query_Builder_Collection::factory($foreign_model)->limit(1)->where(':unique_key', '=', $key)->current();
	}

	protected function _delete_item($foreign_model, $key)
	{
		return Jam_Query_Builder_Delete::factory($foreign_model)->limit(1)->where(':unique_key', '=', $key)->execute();
	}

	public function get(Jam_Validated $model, $value, $is_changed)
	{
		if ($is_changed)
		{
			if ($value instanceof Jam_Validated OR ! $value)
				return $value;

			$key = Jam_Association::primary_key($this->foreign_model($model), $value);
		}
		else
		{
			$key = $model->{$this->foreign_key};
		}

		$item = $this->_find_item($this->foreign_model($model), $key);

		if ($item)
		{
			if (is_array($value) AND Jam_Association::is_changed($value))
			{
				$item->set($value);
			}

			if ($this->inverse_of)
			{
				$item->{$this->inverse_of} = $model;
			}
		}

		return $item;
	}

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if (is_array($value) AND $this->is_polymorphic())
		{
			$model->{$this->polymorphic} = key($value);
			$value = current($value);
		}

		$key = Jam_Association::primary_key($this->foreign_model($model), $value);

		if (is_numeric($key) OR $key === NULL)
		{
			$model->{$this->foreign_key} = $key;
		}

		return $value;
	}

	public function model_before_save(Jam_Model $model, Jam_Event_Data $data, $changed)
	{
		if ($value = Arr::get($changed, $this->name) AND Jam_Association::is_changed($value))
		{
			$this->set($model, $model->{$this->name}->save(), TRUE);
		}
	}

	public function model_before_delete(Jam_Model $model)
	{
		if ($this->dependent == Jam_Association::DELETE)
		{
			$model->{$this->name}->delete();
		}
		elseif ($this->dependent == Jam_Association::ERASE)
		{
			$this->_delete_item($this->foreign_model($model), $model->{$this->foreign_key});	
		}
	}
}
