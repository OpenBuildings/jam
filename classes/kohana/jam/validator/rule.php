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
abstract class Kohana_Jam_Validator_Rule {

	public $allow_null = TRUE;

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

	abstract public function validate(Jam_Validated $model, $attribute, $value);

}