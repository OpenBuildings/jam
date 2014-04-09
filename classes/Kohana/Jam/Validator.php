<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Validation
 *
 * Jam_Validation overrides Kohana's core validation class in order to add a few
 * Jam-specific features.
 *
 * @package    Jam
 * @category   Security
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Validator {

	public $attributes = array();

	public $rules = array();

	public $condition = NULL;
	public $condition_negative = FALSE;

	function __construct(array $attributes, array $options)
	{
		$this->attributes = $attributes;

		if (isset($options['if']))
		{
			$this->condition = $options['if'];
			unset($options['if']);
		}

		if (isset($options['unless']))
		{
			$this->condition = $options['unless'];
			$this->condition_negative = TRUE;
			unset($options['unless']);
		}

		foreach ($options as $rule => $params)
		{
			$this->rules[] = ($params instanceof Jam_Validator_Rule) ? $params : Jam::validator_rule($rule, $params);
		}
	}

	public function condition_met(Jam_Validated $model)
	{
		if ( ! $this->condition)
			return TRUE;


		if (is_string($this->condition) AND strpos($this->condition, '::') === FALSE)
		{
			if (substr($this->condition, -2) == '()')
			{
				$method_name = substr($this->condition, 0, -2);
				$result = $model->$method_name();
			}
			else
			{
				$result = $model->{$this->condition};
			}
		}
		else
		{
			$result = call_user_func($this->condition, $model, $this->attributes);
		}

		return $this->condition_negative ? ! $result : (bool) $result;
	}

	public function html5_validation(Jam_Validated $model, $name)
	{
		$attributes = array();
		if (in_array($name, $this->attributes) AND $model instanceof Jam_Validated AND $this->condition_met($model))
		{
			foreach ($this->rules as $rule)
			{
				if ($rule_attributes = $rule->html5_validation($name))
				{
					$attributes = Arr::merge($attributes, $rule_attributes);
				}
			}
		}
		return $attributes;
	}

	public function validate_model(Jam_Validated $model, $force = FALSE)
	{
		foreach ($this->attributes as $attribute)
		{
			if ($model instanceof Jam_Validated AND $this->condition_met($model))
			{
				if ($force OR ($model instanceof Jam_Model AND ! $model->loaded()) OR $model->changed($attribute) OR $model->unmapped($attribute) OR ! $model->{$attribute})
				{
					foreach ($this->rules as $rule)
					{
						if ($rule->is_processable_attribute($model, $attribute))
						{
							$rule->validate($model, $attribute, $model->$attribute);
						}
					}
				}
			}
		}
	}

} // End Jam_Validation
