<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a specific role in the database.
 *
 * @package  Jam
 */
class Model_Test_Position extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Unittest_Jam_Testcase::$database_connection);

		// Define fields
		$meta->fields(array(
			'id'   => Jam::field('primary'),
			'name' => Jam::field('string'),
		));
	}

} // End Model_Test_Role