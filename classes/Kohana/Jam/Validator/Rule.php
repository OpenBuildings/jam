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
abstract class Kohana_Jam_Validator_Rule {

	public $validate_empty = FALSE;

	function __construct($params)
	{
		if (is_array($params))
		{
			foreach ($params as $param => $value)
			{
				$this->$param = $value;
			}
		}
	}

	public function is_processable_attribute(Jam_Validated $model, $attribute)
	{
		if ($this->validate_empty)
			return TRUE;

		if ($field = $model->meta()->field($attribute))
			return ! $field->is_empty($model->$attribute);

		return $model->$attribute !== NULL;
	}

	public function html5_validation()
	{
		return NULL;
	}

	abstract public function validate(Jam_Validated $model, $attribute, $value);

}
