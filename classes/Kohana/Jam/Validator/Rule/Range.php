<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Validatior Rule
 *
 * @package    Jam
 * @category   Validation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Validator_Rule_Range extends Jam_Validator_Rule {

	public $consecutive;

	public $minimum;

	public $minimum_or_equal_to;

	public $maximum;

	public $maximum_or_equal_to;

	public $between;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ( ! ($value instanceof Jam_Range))
			throw new Kohana_Exception('Range validation rule can only be applied to range');

		if ( ! is_numeric($value->min()) OR ! is_numeric($value->max()))
		{
			$model->errors()->add($attribute, 'range_numeric');

			return;
		}

		$min = min($value->min(), $value->max());
		$max = max($value->min(), $value->max());

		if ($this->minimum !== NULL AND ($min <= $this->minimum))
		{
			$model->errors()->add($attribute, 'range_minimum', array(':minimum' => $this->minimum));
		}

		if ($this->minimum_or_equal_to !== NULL AND ($min < $this->minimum_or_equal_to))
		{
			$model->errors()->add($attribute, 'range_minimum_or_equal_to', array(':minimum_or_equal_to' => $this->minimum_or_equal_to));
		}

		if ($this->maximum !== NULL AND ($max >= $this->maximum))
		{
			$model->errors()->add($attribute, 'range_maximum', array(':maximum' => $this->maximum));
		}

		if ($this->maximum_or_equal_to !== NULL AND ($max > $this->maximum_or_equal_to))
		{
			$model->errors()->add($attribute, 'range_maximum_or_equal_to', array(':maximum_or_equal_to' => $this->maximum_or_equal_to));
		}

		if ($this->between !== NULL AND ! ($min >= $this->between[0] AND $max <= $this->between[1]))
		{
			$model->errors()->add($attribute, 'range_between', array(':minimum' => $this->between[0], ':maximum' => $this->between[1]));
		}

		if ($this->consecutive !== NULL AND ($value->min() > $value->max()))
		{
			$model->errors()->add($attribute, 'range_consecutive');
		}
	}
}
