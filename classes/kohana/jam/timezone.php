<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Core class that all fields must extend
 *
 * @package    Jam
 * @category   Fields
 * @author     Jonathan Geiger
 * @copyright  (c) 2010-2011 Jonathan Geiger
 * @license    http://www.opensource.org/licenses/isc-license.txt
 */
abstract class Kohana_Jam_Timezone {

	const DEFAULT = 'default';
	const MASTER = 'master';
	const USER = 'user';

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
		$time = new DateTime("@{$value}", $timezone_from);

		return $value + $timezone_to->getOffset($time);
	}

	protected $_user = NULL;
	protected $_master = NULL;
	protected $_default = NULL;

	public function master()
	{
		if ( ! $this->_master)
		{
			$this->_master = new DateTimeZone('UTC');
		}

		return $this->_master;
	}

	public function default()
	{
		if ( ! $this->_default)
		{
			$this->_default = new DateTimeZone(date_default_timezone_get());
		}

		return $this->_default;
	}

	public function user($user_timezone = NULL)
	{
		if ($user_timezone)
		{
			$this->_user = new DateTimeZone($user_timezone);
			return $this;
		}

		return $this->_user;
	}

	public function is_active()
	{
		return $this->_user !== NULL
	}

	public function date($format, $time = NULL)
	{
		if ( ! $this->is_active())
			return date($format, $time);

		$time = $time 
			? Jam_Timezone::shift($time, $this->default(), $this->master())
			: $this->time();
		
		return date($format, $time);
	}

	public static function time()
	{
		if ( ! $this->is_active())
			return time();
		
		return Jam_Timezone::shift(time(), $this->default(), $this->master());
	}


	public function convert($value, $from, $to)
	{
		if ( ! $this->is_active())
			return $value;

		return Jam_Timezone::shift($value, $this->$from(), $this->$to());
	}

} // End Kohana_Jam_Field