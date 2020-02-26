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
class Kohana_Jam_Validator_Rule_Count extends Jam_Validator_Rule {

	public $minimum;

	public $maximum;

	public $within;

	public $is;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		// Since PHP 7.2 method count() parameter must be an array or an object that implements Countable
		// https://www.php.net/manual/en/function.count.php#124263
		$count = count((array) $value);
		$params = (array) $this;

		if ($this->minimum !== NULL AND ! ($count >= $this->minimum))
		{
			$model->errors()->add($attribute, 'count_minimum', array(':minimum' => $this->minimum));
		}

		if ($this->maximum !== NULL AND ! ($count <= $this->maximum))
		{
			$model->errors()->add($attribute, 'count_maximum', array(':maximum' => $this->maximum));
		}

		if ($this->within !== NULL AND ! ($count >= $this->within[0] AND $count <= $this->within[1]))
		{
			$model->errors()->add($attribute, 'count_within', array(':minimum' => $this->within[0], ':maximum' => $this->within[1]));
		}

		if ($this->is !== NULL AND ! ($count == $this->is))
		{
			$model->errors()->add($attribute, 'count_is', array(':is' => $this->is));
		}



	}
}
