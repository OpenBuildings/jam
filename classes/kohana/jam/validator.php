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
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
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

	public function condition_met(Jam_Model $model)
	{
		if ( ! $this->condition)
			return TRUE;

		
		if (is_string($this->condition))
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
			$result = call_user_func($this->condition, $model, $attributes);
		}
		
		return $this->condition_negative ? ! $result : $result;
	}

	public function validate_model(Jam_Model $model)
	{
		foreach ($this->attributes as $attribute) 
		{
			if (( ! $model->loaded() OR $model->changed($attribute)) AND $this->condition_met($model))
			{
				$value = $model->$attribute;

				foreach ($this->rules as $rule) 
				{
					if ($value !== NULL OR $rule->allow_null)
					{
						$rule->validate($model, $attribute, $value);
					}
				}
			}
		}
	}

} // End Jam_Validation