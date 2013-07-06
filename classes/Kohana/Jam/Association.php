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

	public static function primary_key($model_name, $value)
	{
		if ( ! $value)
		{
			return NULL;
		}			
		elseif ($value instanceof Jam_Validated) 
		{
			return $value->id();
		}
		elseif (is_integer($value) OR is_string($value)) 
		{
			return $value;
		}
		elseif (is_array($value)) 
		{
			return Arr::get($value, Jam::meta($model_name)->primary_key());
		}
	}

	public static function is_changed($value)
	{
		if ($value instanceof Jam_Model AND ( ! $value->loaded() OR $value->changed()))
			return TRUE;

		if (is_array($value)) 
			return TRUE;
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