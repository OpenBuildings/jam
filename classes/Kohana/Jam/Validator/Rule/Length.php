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
		$params = (array) $this;

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
}