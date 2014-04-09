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
class Kohana_Jam_Validator_Rule_Numeric extends Jam_Validator_Rule {

	public $greater_than_or_equal_to;

	public $greater_than;

	public $equal_to;

	public $less_than;

	public $less_than_or_equal_to;

	public $between;

	public $odd;

	public $even;

	public $only_integer;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ( ! is_numeric($value))
		{
			$model->errors()->add($attribute, 'numeric');
		}

		if ($this->only_integer !== NULL AND ! (filter_var($value, FILTER_VALIDATE_INT) !== FALSE))
		{
			$model->errors()->add($attribute, 'numeric_only_integer');
		}

		if ($this->greater_than_or_equal_to !== NULL AND ! ($value >= $this->greater_than_or_equal_to))
		{
			$model->errors()->add($attribute, 'numeric_greater_than_or_equal_to', array(':greater_than_or_equal_to' => $this->greater_than_or_equal_to));
		}

		if ($this->greater_than !== NULL AND ! ($value > $this->greater_than))
		{
			$model->errors()->add($attribute, 'numeric_greater_than', array(':greater_than' => $this->greater_than));
		}

		if ($this->equal_to !== NULL AND ! ($value == $this->equal_to))
		{
			$model->errors()->add($attribute, 'numeric_equal_to', array(':equal_to' => $this->equal_to));
		}

		if ($this->less_than !== NULL AND ! ($value < $this->less_than))
		{
			$model->errors()->add($attribute, 'numeric_less_than', array(':less_than' => $this->less_than));
		}

		if ($this->less_than_or_equal_to !== NULL AND ! ($value <= $this->less_than_or_equal_to))
		{
			$model->errors()->add($attribute, 'numeric_less_than_or_equal_to', array(':less_than_or_equal_to' => $this->less_than_or_equal_to));
		}

		if ($this->between !== NULL AND ! ($value >= $this->between[0] AND $value <= $this->between[1]))
		{
			$model->errors()->add($attribute, 'numeric_between', array(':minimum' => $this->between[0], ':maximum' => $this->between[1]));
		}

		if ($this->odd === TRUE AND ! ($value % 2 == 0))
		{
			$model->errors()->add($attribute, 'numeric_odd', array(':odd' => $this->odd));
		}

		if ($this->even === TRUE AND ! ($value % 2 == 1))
		{
			$model->errors()->add($attribute, 'numeric_even', array(':even' => $this->even));
		}
	}

	public function html5_validation()
	{
		if ($this->only_integer)
			return array(
				'pattern' => '-?\d+',
				'title' => 'Integer numbers'
			);

		return array(
			'pattern' => '-?\d+(\.\d+)?',
			'title' => 'Numbers with an optional floating point'
		);
	}
}
