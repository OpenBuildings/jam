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
class Kohana_Jam_Validator_Rule_Length extends Jam_Validator_Rule {

	public $minimum;

	public $maximum;

	public $between;

	public $is;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		$length = mb_strlen($value);

		if ($this->minimum !== NULL AND ! ($length >= $this->minimum))
		{
			$model->errors()->add($attribute, 'length_minimum', array(':minimum' => $this->minimum));
		}

		if ($this->maximum !== NULL AND ! ($length <= $this->maximum))
		{
			$model->errors()->add($attribute, 'length_maximum', array(':maximum' => $this->maximum));
		}

		if ($this->between !== NULL AND ! ($length >= $this->between[0] AND $length <= $this->between[1]))
		{
			$model->errors()->add($attribute, 'length_between', array(':minimum' => $this->between[0], ':maximum' => $this->between[1]));
		}

		if ($this->is !== NULL AND ! ($length == $this->is))
		{
			$model->errors()->add($attribute, 'length_is', array('is' => $this->is));
		}
	}

	public function html5_validation()
	{
		if ($this->is)
		{
			return array(
				'pattern' => ".{{$this->is}}",
				'title' => "Value must be $this->is letters"
			);
		}
		elseif ($this->between)
		{
			return array(
				'pattern' => ".{{$this->between[0]},{$this->between[1]}}",
				'title' => "Value must be longer than {$this->between[0]} and shorter than {$this->between[1]} letters"
			);
		}
		elseif ($this->minimum OR $this->maximum)
		{
			$minimum = $this->minimum ? $this->minimum : 0;
			return array(
				'pattern' => ".{{$minimum},{$this->maximum}}",
				'title' => 'Value must be '
					.($this->minimum ? "longer than $this->minimum".($this->maximum ? ' and ' : '') : '')
					.($this->maximum ? "shorter than $this->maximum" : '').' letters'
			);
		}
	}
}
