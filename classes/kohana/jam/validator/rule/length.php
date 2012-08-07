<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Jam Validatior Rule
 *
 * @package    Jam
 * @category   Validation
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 OpenBuildings Inc.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
class Kohana_Jam_Validator_Rule_Length extends Jam_Validator_Rule {

	public $minimum;

	public $maximum;

	public $within;

	public $is;

	public function validate(Jam_Model $model, $attribute, $value)
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

		if ($this->within !== NULL AND ! ($length >= $this->within[0] AND $length <= $this->within[1]))
		{
			$model->errors()->add($attribute, 'length_within', array(':minimum' => $this->within[0], ':maximum' => $this->within[1]));
		}

		if ($this->is !== NULL AND ! ($length == $this->is))
		{
			$model->errors()->add($attribute, 'length_is', array('is' => $this->is));
		}



	}
}