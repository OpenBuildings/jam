<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.form
 * @group   jam.form.core
 */
class Jam_FormTest extends PHPUnit_Framework_TestCase {

	protected $form;
	protected $post;

	public function setUp()
	{
		parent::setUp();

		$this->post = Jam::build('test_post')->load_fields(array(
			'id' => 1,
			'name' => 'First Post',
			'slug' => 'first-post',
			'status' => 'draft',
			'test_blog_id' => 1,
			'test_blog' => array(
				'id' => 1,
				'name' => 'Flowers blog',
				'url' => 'http://flowers.wordpress.com/',
			),
			'test_tags' => array(
				array('id' => 1, 'name' => 'red', 'slug' => 'red'),
				array('id' => 2, 'name' => 'green', 'slug' => 'green'),
			)
		));
		$this->form = Jam::form($this->post);
	}

	public function test_list_id()
	{
		$this->assertEquals(1, Jam_Form::list_id($this->post));
		$this->assertEquals(1, Jam_Form::list_id(1));
		$this->assertEquals(array(1, 2), Jam_Form::list_id($this->post->test_tags));

		$this->assertEquals(1, Jam_Form::list_id($this->post->test_tags, TRUE));
		$this->assertEquals(1, Jam_Form::list_id(array(1, 5), TRUE));
	}

	public function test_common_params()
	{
		$collection = Jam::all('test_tag')->load_fields(array(
			array('id' => 1, 'name' => 'red', 'slug' => 'green'),
			array('id' => 2, 'name' => 'orange', 'slug' => 'green'),
			array('id' => 3, 'name' => 'green', 'slug' => 'green'),
		));

		$this->assertEquals(array('slug' => 'green', 'name' => NULL), Jam_Form::common_params($collection, array('slug', 'name')));

		$posts = Jam::all('test_post')->load_fields(array(
			array(
				'id' => 1,
				'name' => 'Big Post',
				'test_blog' => array(
					'id' => 1,
					'name' => 'Green blog',
				),
			),
			array(
				'id' => 2,
				'name' => 'Big Post',
				'test_blog' => array(
					'id' => 2,
					'name' => 'Green blog',
				),
			)
		));

		$expected = array(
			'id' => NULL,
			'name' => 'Big Post',
			'test_blog' => array(
				'id' => NULL,
				'name' => 'Green blog'
			)
		);

		$this->assertEquals($expected, Jam_Form::common_params($posts, array('id', 'name', 'test_blog' => array('id', 'name'))));

	}

	public function test_choices()
	{
		$blogs = Jam::all('test_blog')->load_fields(array(
			array('id' => 1, 'name' => 'Flowers blog', 'url' => 'http://flowers.wordpress.com'),
			array('id' => 2, 'name' => 'Awesome programming', 'url' => 'http://programming-blog.com'),
			array('id' => 3, 'name' => 'Tabless', 'url' => 'http://bobby-tables-ftw.com'),
		));

		$select = $blogs->as_array(':primary_key', ':name_key');

		$choices = Jam_Form::list_choices($blogs);
		$this->assertEquals($select, $choices);

		$choices = Jam_Form::list_choices($select);
		$this->assertEquals($select, $choices);
	}

	public function test_defaults()
	{
		$this->assertSame($this->form->object(), $this->post, 'Assert same object');

		$this->assertEquals('field', $this->form->default_id('field'));
		$this->assertEquals('field', $this->form->default_name('field'));
		$this->assertEquals(array('id' => 'field', 'name' => 'field'), $this->form->default_attributes('field'));
		$this->assertEquals(array('id' => 'name', 'name' => 'name'), $this->form->default_attributes('name'));
	}

	public function test_fields_for()
	{
		$empty_form = Jam::form(Jam::build('test_post'));
		$empty_blog_form = $empty_form->fields_for('test_blog');

		$this->assertEquals('test_blog[%s]', $empty_blog_form->prefix());
		$this->assertEquals('test_blog_field', $empty_blog_form->default_id('field'));
		$this->assertEquals('test_blog[field]', $empty_blog_form->default_name('field'));
		$this->assertEquals(array('id' => 'test_blog_field', 'name' => 'test_blog[field]'), $empty_blog_form->default_attributes('field'));


		$blog_form = $this->form->fields_for('test_blog');

		$this->assertEquals('test_blog[%s]', $blog_form->prefix());

		$this->assertEquals('test_blog_field', $blog_form->default_id('field'));
		$this->assertEquals('test_blog[field]', $blog_form->default_name('field'));
		$this->assertEquals(array('id' => 'test_blog_field', 'name' => 'test_blog[field]'), $blog_form->default_attributes('field'));

		$this->assertGreaterThan(1, count($this->post->test_tags), 'Should have some tags to test on');

		foreach ($this->post->test_tags as $i => $test_tag)
		{
			$tag_form = $this->form->fields_for('test_tags', $i);

			$this->assertEquals("test_tags[$i][%s]", $tag_form->prefix());

			$this->assertEquals("test_tags_{$i}_field", $tag_form->default_id('field'));
			$this->assertEquals("test_tags[$i][field]", $tag_form->default_name('field'));
			$this->assertEquals(array('id' => "test_tags_{$i}_field", 'name' => "test_tags[$i][field]"), $tag_form->default_attributes('field'));

		}
	}
}
