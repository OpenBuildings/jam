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
			// Get existing post
			array(array('test_author', 1, 'test_post'), TRUE),

			// Get non-existing post
			array(array('test_author', 2, 'test_post'), FALSE),

			// Get non-existing author
			array(array('test_author', 555, 'test_post'), FALSE),

			// Get post without specifying an author
			array(array('test_author', NULL, 'test_post'), FALSE),
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
		$model = Jam::factory($args[0], $args[1]);

		if ( ! $model->loaded())
		{
			$this->setExpectedException('Kohana_Exception');
		}

		$builder = $model->builder($args[2]);

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

	public function test_polymorphic_as()
	{
		$post = Jam::factory('test_post', 1);
		$image = Jam::factory('test_image')->set(array('file' => 'new file.file'))->save();
		$post->test_cover_image = $image;
		$post->save();

		$image = Jam::factory('test_image', $image->id());

		$this->assertEquals($post->id(), $image->test_holder->id());
		$this->assertEquals($image->id(), Jam::factory('test_post', 1)->test_cover_image->id());
	}

	public function test_polymorphic_join_association()
	{
		$this->assertEquals(1, Jam::query('test_post')
			->join_association('test_cover_image')
			->count());
	}

} // End Jam_Field_HasOneTest
