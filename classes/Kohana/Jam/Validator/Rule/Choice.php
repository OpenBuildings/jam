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
class Kohana_Jam_Validator_Rule_Choice extends Jam_Validator_Rule {

	public $in;

	public $not_in;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ($this->in !== NULL AND ! (in_array($value, $this->in)))
		{
			$model->errors()->add($attribute, 'choice_in', array(':in' => join(', ', $this->in)));
		}

		if ($this->not_in !== NULL AND ! ( ! in_array($value, $this->not_in)))
		{
			$model->errors()->add($attribute, 'choice_not_in', array(':not_in' => join(', ', $this->not_in)));
		}
	}
}
