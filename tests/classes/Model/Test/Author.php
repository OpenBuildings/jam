<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents an author in the database.
 *
 * @package  Jam
 */
class Model_Test_Author extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Kohana::TESTING);

		$meta->associations(array(
			'test_post'        => Jam::association('hasone', array('inverse_of' => 'test_author')),
			'test_posts'       => Jam::association('hasmany'),
			'test_blogs_owned' => Jam::association('hasmany', array(
				'foreign_model' => 'test_blog',
				'foreign_key' => 'test_owner_id',
			)),
			'test_categories'  => Jam::association('hasmany'),
			'test_position'    => Jam::association('belongsto'),

			// Relationship with non-standard naming
			'permission' => Jam::association('belongsto', array(
				'foreign_model' => 'test_position',
				'foreign_key'  => 'test_position_id',
			)),
		));

		// Define fields
		$meta->fields(array(
			'id'         => Jam::field('primary'),
			'name'       => Jam::field('string'),
			'password'   => Jam::field('password'),
			'email'      => Jam::field('string'),
		 ));
	}

} // End Model_Test_Author
