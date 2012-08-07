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
class Kohana_Jam_Validator_Rule_Format extends Jam_Validator_Rule {

	public $regex;

	public $filter;

	public $flag;

	public function validate(Jam_Model $model, $attribute, $value)
	{
		if ($this->regex !== NULL AND ! (preg_match($this->regex, $value)))
		{
			$model->errors()->add($attribute, 'format_regex', array(':regex' => $this->regex));
		}

		if ($this->filter !== NULL AND ! (filter_var($value, $this->filter, $this->flag) !== FALSE))
		{
			$model->errors()->add($attribute, 'format_filter', array(':filter' => $this->filter));
		}
	}
}