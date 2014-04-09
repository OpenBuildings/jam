<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Core class that all fields must extend
 *
 * @package    Jam
 * @category   Fields
 * @author     Ivan Kerin
 * @copyright  (c) 2011-2012 Despark Ltd.
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Timezone {

	const DEFAULT_TIMEZONE = 'default_timezone';
	const MASTER_TIMEZONE = 'master_timezone';
	const USER_TIMEZONE = 'user_timezone';

	protected static $_instance;

	public static function instance()
	{
		if ( ! Jam_Timezone::$_instance)
		{
			Jam_Timezone::$_instance = new Jam_Timezone();
		}
		return Jam_Timezone::$_instance;
	}

	public static function shift($value, DateTimeZone $timezone_from, DateTimeZone $timezone_to)
	{
		$time = new DateTime();
		$time->setTimestamp($value);

		return $value + $timezone_to->getOffset($time) - $timezone_from->getOffset($time);
	}

	protected $_user_timezone = NULL;
	protected $_master_timezone = NULL;
	protected $_default_timezone = NULL;

	public function master_timezone($timezone = NULL)
	{
		if ($timezone !== NULL)
		{
			$this->_master_timezone = new DateTimeZone($timezone);
			return $this;
		}

		if ( ! $this->_master_timezone)
		{
			$this->_master_timezone = new DateTimeZone('UTC');
		}

		return $this->_master_timezone;
	}

	public function default_timezone($timezone = NULL)
	{
		if ($timezone !== NULL)
		{
			$this->_default_timezone = new DateTimeZone($timezone);
			return $this;
		}

		if ( ! $this->_default_timezone)
		{
			$this->_default_timezone = new DateTimeZone(date_default_timezone_get());
		}

		return $this->_default_timezone;
	}

	public function user_timezone($timezone = NULL)
	{
		if ($timezone !== NULL)
		{
			$this->_user_timezone = new DateTimeZone($timezone);
			return $this;
		}

		return $this->_user_timezone;
	}

	public function is_active()
	{
		return $this->_user_timezone !== NULL;
	}

	public function date($format, $time = NULL)
	{
		if ( ! $this->is_active())
			return ($time !== NULL) ? date($format, $time) : date($format);

		$time = ($time !== NULL) ? $time : time();
		$time = Jam_Timezone::shift($time, $this->default_timezone(), $this->master_timezone());

		return date($format, $time);
	}

	public function time()
	{
		if ( ! $this->is_active())
			return time();

		return Jam_Timezone::shift(time(), $this->default_timezone(), $this->master_timezone());
	}

	public function strtotime($time_string)
	{
		if ( ! $this->is_active())
			return strtotime($time_string);

		return Jam_Timezone::shift(strtotime($time_string), $this->default_timezone(), $this->master_timezone());
	}

	public function convert($value, $from, $to)
	{
		if ( ! $this->is_active())
			return $value;

		return Jam_Timezone::shift($value, $this->$from(), $this->$to());
	}

	public function to_db($value, $from = 'user_timezone')
	{
		if ( ! $this->is_active())
			return $value;

		if ( ! is_numeric($value))
		{
			$value = strtotime($value);
		}

		$value = $this->convert($value, $from, Jam_Timezone::MASTER_TIMEZONE);
		return date('Y-m-d H:i:s', $value);
	}

} // End Kohana_Jam_Field
