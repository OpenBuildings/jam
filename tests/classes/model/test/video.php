<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a video in the database.
 *
 * @package  Jam
 */
class Model_Test_Video extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Unittest_Jam_Testcase::$database_connection);

		$meta->behaviors(array(
			'paranoid' => Jam::behavior('paranoid', array('field' => 'deleted')),
			'sortable' => Jam::behavior('sortable', array('field' => 'position')),
			'sluggable' => Jam::behavior('sluggable', array('auto_save' => TRUE))
		));

		$meta->associations(array(
			'test_holder'	=> Jam::association('belongsto', array(
				'polymorphic' => 'test_holder_type'
			)),
		));

		// Set fields
		$meta->fields(array(
			'id'              => Jam::field('primary'),
			'file'            => Jam::field('string', array(
				'rules' => array(
					array('min_length', array(':value', 4))
				)
			)),
		));
	}

} // End Model_Test_Post