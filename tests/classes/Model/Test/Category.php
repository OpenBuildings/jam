<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a category in the database.
 *
 * @package  Jam
 */
class Model_Test_Category extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		$meta->behaviors(array(
			'nested' => Jam::behavior('nested')
		));

		$meta->associations(array(
			'test_blog'   => Jam::association('belongsto', array('inverse_of' => 'test_categories')),
			'test_posts'  => Jam::association('manytomany'),
			'test_author' => Jam::association('belongsto')
		));

		// Define fields
		$meta->fields(array(
			'id'          => Jam::field('primary'),
			'name'        => Jam::field('string'),
			'is_featured' => Jam::field('boolean')
		));
	}

} // End Model_Test_Category
