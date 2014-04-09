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
class Kohana_Jam_Validator_Rule_Format extends Jam_Validator_Rule {

	public $regex;

	public $filter;

	public $flag;

	public $email;

	public $url;

	public $ip;

	public $credit_card;

	public function validate(Jam_Validated $model, $attribute, $value)
	{
		if ($this->regex !== NULL AND ! (preg_match($this->regex, $value)))
		{
			$model->errors()->add($attribute, 'format_regex', array(':regex' => $this->regex));
		}

		if ($this->filter !== NULL AND ! (filter_var($value, $this->filter, $this->flag) !== FALSE))
		{
			$model->errors()->add($attribute, 'format_filter', array(':filter' => $this->filter));
		}

		if ($this->ip === TRUE AND ! (Valid::ip($value)))
		{
			$model->errors()->add($attribute, 'format_ip');
		}

		if ($this->url === TRUE AND ! (Valid::url($value)))
		{
			$model->errors()->add($attribute, 'format_url');
		}

		if ($this->email === TRUE AND ! (Valid::email($value)))
		{
			$model->errors()->add($attribute, 'format_email');
		}

		if ($this->credit_card === TRUE AND ! (Valid::credit_card($value)))
		{
			$model->errors()->add($attribute, 'format_credit_card');
		}
	}

	public function html5_validation()
	{
		if ($this->url)
		{
			return array('type' => 'url');
		}
		elseif ($this->email)
		{
			return array('type' => 'email');
		}
		elseif ($this->regex)
		{
			return array('pattern' => trim($this->regex, '/'));
		}
		elseif ($this->credit_card)
		{
			return array('pattern' => '^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$');
		}
	}
}
