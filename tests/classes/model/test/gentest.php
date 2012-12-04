<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Represents a specific role in the database.
 *
 * @package  Jam
 */
class Model_Test_Gentest extends Jam_Model {

	public $new_test1;
	public $new_test2;

	public static function initialize(Jam_Meta $meta)
	{
		// Set database to connect to
		$meta->db(Unittest_Jam_Testcase::$database_connection);

		$meta->behaviors(array(
			'image_generator' => Jam::behavior('image_generator', array('images' => array('test1', 'test2')))
		));

		// Define fields
		$meta->fields(array(
			'id'   => Jam::field('primary'),
			'name' => Jam::field('string'),
		));
	}

	public function image_generator_test1_filename()
	{
		return $this->new_test1;
	}

	public function image_generator_test2_filename()
	{
		return $this->new_test2;
	}

	public function image_generator_test1()
	{
		$image = Image::factory(array(100, 50, $this->generated_path('test1')));
		return $image;
	}

} // End Model_Test_Role