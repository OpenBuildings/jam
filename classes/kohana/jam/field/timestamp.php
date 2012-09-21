<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Handles timestamps and conversions to and from different formats
 *
 * All timestamps are represented internally by UNIX timestamps, regardless
 * of their format in the database. When the model is saved, the value is
 * converted back to the format specified by $format (which is a valid
 * date() string).
 *
 * This means that you can have timestamp logic exist relatively independently
 * of your database's format. If, one day, you wish to change the format used
 * to represent dates in the database, you just have to update the $format
 * property for the field.
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Field_Timestamp extends Jam_Field {

	
	/**
	 * @var  int  default is NULL, which implies no date
	 */
	public $default = NULL;

	/**
	 * @var  boolean  whether or not to automatically set now() on creation
	 */
	public $auto_now_create = FALSE;

	/**
	 * @var  boolean  whether or not to automatically set now() on update
	 */
	public $auto_now_update = FALSE;

	/**
	 * @var  string  a date formula representing the time in the database
	 */
	public $format = NULL;

	/**
	 * Sets the default to 0 if we have no format, or an empty string otherwise.
	 *
	 * @param  array  $options
	 */
	public function __construct($options = array())
	{
		parent::__construct($options);

		if ( ! isset($options['default']) AND ! $this->allow_null)
		{
			// Having a implies saving we're format a string, so we want a proper default
			$this->default = $this->format ? '' : 0;
		}
	}


	public function attribute_get($model, $value, $is_loaded)
	{
		if (Jam_Timezone::instance()->is_active())
		{
			if ( ! is_numeric($value) AND FALSE !== strtotime($value))
			{
				$value = strtotime($value);
			}

			$value = Jam_Timestamp::instance()->convert($value, Jam_Timestamp::MASTER, Jam_Timestamp::USER);

			if ($this->format AND is_numeric($value))
			{
				$value = date($this->format, $value);
			}
		}

		return $value;
	}

	/**
	 * Automatically creates or updates the time and
	 * converts it, if necessary.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $value
	 * @param   boolean      $is_loaded
	 * @return  int|string
	 */
	public function attribute_convert($model, $value, $is_loaded)
	{
		// Do we need to provide a default since we're creating or updating
		if (( ! $is_loaded AND $this->auto_now_create) OR ($is_loaded AND $this->auto_now_update))
		{
			$value = Jam_Timestamp::time();
		}
		else
		{
			// Convert to UNIX timestamp
			if (is_numeric($value))
			{
				$value = (int) $value;
			}
			elseif (FALSE !== ($to_time = strtotime($value)))
			{
				$value = $to_time;
			}

			if ( ! is_numeric($value) AND FALSE !== strtotime($value))
			{
				$value = strtotime($value);
			}

			$value = Jam_Timestamp::instance()->convert($value, Jam_Timestamp::USER, Jam_Timestamp::MASTER);
		}

		// Convert if necessary
		if ($this->format AND is_numeric($value))
		{
			$value = date($this->format, $value);
		}


		return $value;
	}

} // End Kohana_Jam_Field_Timestamp