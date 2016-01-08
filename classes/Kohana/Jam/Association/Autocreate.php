<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Creates the record if it does not exist and is a string
 *
 * @package    Jam
 * @category   Associations
 * @author     Ivan Kerin
 * @copyright  (c) 2013 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Association_Autocreate extends Jam_Association_Belongsto {

	/**
	 * @deprecated Use $additional_fields instead
	 * Will be removed in 0.6
	 * @var array
	 */
	public $default_fields = array();

	/**
	 * Additional fields to search for existing entry by.
	 * They get set if a new entry is autocreated.
	 * @var array
	 */
	public $additional_fields = array();

	public function set(Jam_Validated $model, $value, $is_changed)
	{
		if ($is_changed AND $value AND is_string($value) AND ! is_numeric($value))
		{
			if ($this->default_fields AND ! $this->additional_fields)
			{
				$this->additional_fields = $this->default_fields;
			}

			if (! is_array($this->additional_fields))
			{
				$this->additional_fields = array();
			}

			$value = Jam::find_or_create(
				$this->foreign_model,
				array_merge(
					array(':name_key' => $value),
					$this->additional_fields
				)
			);
		}

		return parent::set($model, $value, $is_changed);
	}
}
