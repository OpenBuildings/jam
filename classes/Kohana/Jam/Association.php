<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Core class that all associations must extend
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association extends Jam_Attribute {

	const NULLIFY  = 'nullify';
	const ERASE    = 'erase';
	const DELETE   = 'delete';

	/**
	 * Get the primary key from whatever value you have
	 *
	 * @param  string $model_name The name of the model
	 * @param  string|integer|Jam_Validated|array $value the value or a container of the value
	 * @return string|integer|NULL NULL when no value is provided or could be extracted.
	 */
	public static function primary_key($model_name, $value)
	{
		if ( ! $value)
			return NULL;

		if ($value instanceof Jam_Validated)
			return $value->id();

		if (is_integer($value) OR is_numeric($value))
			return (int) $value;

		if (is_string($value))
			return $value;

		if (is_array($value))
			return Arr::get($value, Jam::meta($model_name)->primary_key());
	}

	public static function is_changed($value)
	{
		if ($value instanceof Jam_Model AND ( ! $value->loaded() OR $value->changed()))
			return TRUE;

		return is_array($value);
	}

	public $foreign_model = NULL;

	public $readonly = NULL;

	/**
	 * If set to true, will delete the association object when this one gets deleted
	 * possible values are Jam_Association::DELETE and Jam_Association::ERASE and Jam_Association::NULLIFY
	 * Jam_Association::DELETE will run the delete event of the associated model, Jam_Association::ERASE will not
	 *
	 * @var boolean|string
	 */
	public $dependent = FALSE;

	/**
	 * See if the association is polymorphic.
	 * This is overloaded in the associations themselves
	 *
	 * @return boolean
	 */
	public function is_polymorphic()
	{
		return FALSE;
	}

	abstract public function join($table, $type = NULL);

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		return $value;
	}
}
