<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an image copyright in the database.
 *
 * @package  Jam
 */
class Model_Test_Element extends Jam_Model {

	public $name_is_ip;
	public $name_is_email;

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		$meta->associations(array(
			'test_author'     => Jam::association('belongsto'),
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'name'            => Jam::field('string'),
			'url'             => Jam::field('string'),
			'email'           => Jam::field('string'),
			'description'     => Jam::field('string'),
			'amount'          => Jam::field('integer'),
		));

		$meta
			->validator('name', array('present' => TRUE))
			->validator('name', 'email', 'url', array('count' => array('minimum' => 2)))
			->validator('email', array('format' => array('filter' => FILTER_VALIDATE_EMAIL)))
			->validator('url', array('format' => array('filter' => FILTER_VALIDATE_URL)))
			->validator('amount', array('numeric' => array('greater_than' => 10, 'less_than' => 100)))
			->validator('description', array('length' => array('between' => array(10, 1000))))
			->validator('name', array('format' => array('filter' => FILTER_VALIDATE_IP), 'if' => 'name_is_ip'))
			->validator('name', array('length' => array('minimum' => 2)));

		$meta
			->with_options(array('unless' => 'name_is_normal()'))
				->validator('name', array('format' => array('filter' => FILTER_VALIDATE_EMAIL)));

	}

	public function name_is_normal()
	{
		return ! $this->name_is_email;
	}

} // End Model_Test_Post
