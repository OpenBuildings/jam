<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests BelongsTo associatons.
 *
 * @package Jam
 * @group   jam
 * @group   jam.association
 * @group   jam.association.belongs_to
 */
class Jam_Association_BelongsToTest extends Unittest_Jam_TestCase {

	/**
	 * Provides test data for test_builder()
	 *
	 * @return  array
	 */
	public function provider_builder()
	{
		return array(
			// Get existing author
			array(array('test_post', 1, 'test_author'), TRUE),

			// Get existing author
			array(array('test_post', 2, 'test_author'), TRUE),

			// Get approved_by model with custom column name and model
			array(array('test_post', 2, 'approved_by'), TRUE),

			// Get non-existing author
			array(array('test_post', 555, 'test_author'), FALSE),

			// Get author without specifying a post
			array(array('test_post', NULL, 'test_author'), FALSE),

			// Get polymorphic field
			array(array('test_image', 1, 'test_holder'), TRUE),

			// Get another polymorphic field
			array(array('test_image', 2, 'test_holder'), TRUE),

			// Get tag with condition TRUE
			array(array('test_tag', 1, 'test_post'), TRUE),

			// Get tag with condition FALSE
			array(array('test_tag', 3, 'test_post'), FALSE),
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
	public function test_builder($builder, $loaded)
	{
		$builder = Jam::factory($builder[0], $builder[1])->builder($builder[2]);

		$this->assertTrue($builder instanceof Jam_Builder, "Must load Jam_Builder object for the association");

		// Load the model
		$model = $builder->select();

		// Ensure it's loaded if it should be
		$this->assertSame($loaded, $model->loaded());
	}

	public function test_model_assignment()
	{
		$test_post = Jam::factory('test_post', 1);
		$test_author = Jam::factory('test_author', 2);

		$test_post->test_author = $test_author;

		$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column');
		$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model');

		$test_post->save();

		$test_post = Jam::factory('test_post', 1);

		$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column after save');
		$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model after save');

		$test_post->test_author = NULL;

		$this->assertNull($test_post->test_author_id, 'Should set the column as null');

		$test_post->save();

		$test_post = Jam::factory('test_post', 1);

		$this->assertNull($test_post->test_author_id, 'Should set the column as null');
	}

	public function test_column_assignment()
	{
		$test_post = Jam::factory('test_post', 1);
		$test_author = Jam::factory('test_author', 2);

		$test_post->test_author_id = $test_author->id();

		$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column');
		$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model');

		$test_post->save();

		$test_post = Jam::factory('test_post', 1);

		$this->assertEquals($test_author->id(), $test_post->test_author_id, 'Should set the column after save');
		$this->assertEquals($test_author->id(), $test_post->test_author->id(), 'Should set the actual model after save');

		$test_post = Jam::factory('test_post', 1);
		$test_post->test_author_id = NULL;

		$this->assertFalse($test_post->test_author->loaded(), 'Should set the model to empty one');

		$test_post->save();

		$test_post = Jam::factory('test_post', 1);

		$this->assertFalse($test_post->test_author->loaded(), 'Should set the model to empty one');
	}

	public function test_build_association()
	{
		$test_post = Jam::factory('test_post', 1);
		$test_author = $test_post->build('test_author');
		$this->assertInstanceOf('Model_Test_Author', $test_author);
		$this->assertSame($test_post->test_author, $test_author);
	}

	public function test_create_association()
	{
		$test_post = Jam::factory('test_post', 1);
		$test_author = $test_post->create('test_author', array('test_position_id' => 1));
		$test_post->save();

		$this->assertInstanceOf('Model_Test_Author', $test_author);
		$this->assertEquals($test_post->test_author->id(), $test_author->id());
		$this->assertEquals($test_post->test_author_id, $test_author->id());
	}

	public function test_delete()
	{
		$test_copyright = Jam::factory('test_copyright', 1);
		$test_copyright_id = $test_copyright->id();
		$test_image = $test_copyright->test_image;
		$test_image_id = $test_image->id();

		$test_copyright->delete();
		$this->assertNotExists('test_copyright', $test_copyright_id);
		$this->assertNotExists('test_image', $test_image_id);
	}

	public function test_delete_nested_depencies()
	{
		$test_blog = Jam::factory('test_blog', 1);
		$test_post = $test_blog->test_posts->first();
		$images_ids = $test_post->test_images->as_array(NULL, 'id');

		$test_blog->delete();
		$this->assertNotExists('test_post', $test_post->id());
		$this->assertNotExists('test_image', $images_ids);
	}

	public function test_mass_assignument()
	{
		$test_post = Jam::factory('test_post', 1);
		$test_post->test_author = array('name' => 'new_author', 'test_position_id' => 1);
		$test_post->save();

		$this->assertInstanceOf('Model_Test_Author', $test_post->test_author);
		$this->assertTrue($test_post->test_author->loaded());
		$this->assertEquals('new_author', $test_post->test_author->name);

		$test_post->test_author = 1;

		$this->assertInstanceOf('Model_Test_Author', $test_post->test_author);
		$this->assertTrue($test_post->test_author->loaded());
		$this->assertEquals(1, $test_post->test_author->id());

		$test_post->save();

		$this->assertInstanceOf('Model_Test_Author', $test_post->test_author);
		$this->assertTrue($test_post->test_author->loaded());
		$this->assertEquals(1, $test_post->test_author->id());
		$this->assertEquals(1, $test_post->test_author_id);
	}

	public function test_polymorphic_mass_assignment()
	{
		$test_image = Jam::factory('test_image', 1);

		$test_image->test_holder = array('test_post' => array('name' => 'polymorphic post', 'slug' => 'pol-post-1'));
		$test_image->save();

		$this->assertInstanceOf('Model_Test_Post', $test_image->test_holder);
		$this->assertTrue($test_image->test_holder->loaded());
		$this->assertEquals('polymorphic post', $test_image->test_holder->name);

		$test_image->test_holder = array('test_post' => 1);
		$test_image->save();

		$this->assertInstanceOf('Model_Test_Post', $test_image->test_holder);
		$this->assertTrue($test_image->test_holder->loaded());
		$this->assertEquals(1, $test_image->test_holder->id());
	}
	
	public function test_touch()
	{
		$test_tag = Jam::factory('test_tag', 1);
		$post = $test_tag->test_post;
		$this->assertEquals(1264985737, $post->updated);
		
		$test_tag->name .= '_edited';
		$test_tag->save();
		
		$post = Jam::factory('test_tag', 1)->test_post;
		
		$this->assertTrue($post->updated > 1264985737);
		
	}

	public function test_polymorphic_model_set()
	{
		$test_image = Jam::factory('test_image', 3);

		$this->assertNull($test_image->test_holder, 'Should return NULL if no model or id set');
		$test_image->test_holder = array('test_post' => NULL);

		$this->assertInstanceOf('Model_Test_Post', $test_image->test_holder, 'Should set the test model only');
		$this->assertFalse($test_image->test_holder->loaded(), 'Should not be loaded if only the model is set');

	}

	public function test_polymorphic_join_association()
	{
		$this->markTestAsSkipped('join_association is not implemented for belongs_to association');
		$this->assertEquals(1, Jam::query('test_image')->join_association('test_holder')->count());
	}

} // End Jam_Association_BelongsToTest