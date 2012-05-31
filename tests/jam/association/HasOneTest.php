<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests BelongsTo associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.has_one
 */
class Jam_Association_HasOneTest extends Unittest_Jam_TestCase {

	/**
	 * Provides test data for test_builder()
	 *
	 * @return  array
	 */
	public function provider_builder()
	{
		return array(
			array(array('test_author', 1, 'test_post'), TRUE),
			array(array('test_author', 2, 'test_post'), FALSE),
			array(array('test_author', 555, 'test_post'), FALSE),
		);
	}

	/**
	 * Tests for Jam_Association_BelongsTo::builder()
	 *
	 * @dataProvider  provider_builder
	 * @param         Jam         $builder
	 * @param         bool          $loaded
	 * @return        void
	 */
	public function test_builder($args, $loaded)
	{
		$builder = Jam::factory($args[0], $args[1])->builder($args[2]);

		$this->assertTrue($builder instanceof Jam_Builder, "Must load Jam_Builder object for the association");

		// Load the model
		$model = $builder->select();

		// Ensure it's loaded if it should be
		$this->assertSame($loaded, $model->loaded());
	}


	public function test_build_association()
	{
		$test_author = Jam::factory('test_author', 1);
		$test_post = $test_author->build('test_post');
		$this->assertInstanceOf('Model_Test_Post', $test_post);
		$this->assertSame($test_post->test_author, $test_author);
	}

	public function test_create_association()
	{
		$test_author = Jam::factory('test_author', 1);
		$test_post = $test_author->create('test_post');
		$test_author->save();

		$this->assertInstanceOf('Model_Test_Post', $test_post);
		$this->assertEquals($test_post->test_author->test_post->id(), $test_author->id());
		$this->assertEquals($test_post->test_author_id, $test_author->id());
	}

	public function test_delete()
	{
		$test_image = Jam::factory('test_image', 1);
		$test_image_id = $test_image->id();
		$test_copyright = $test_image->test_copyright;
		$test_copyright_id = $test_copyright->id();

		$test_image->delete();
		$this->assertNotExists('test_image', $test_copyright_id);
		$this->assertNotExists('test_copyright', $test_image_id);
	}


} // End Jam_Field_BelongsToTest