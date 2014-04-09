<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package Jam
 * @group   jam
 * @group   jam.form
 * @group   jam.form.general
 */
class Jam_Form_GeneralTest extends PHPUnit_Framework_TestCase {

	protected $form;
	protected $post;

	public function setUp()
	{
		parent::setUp();

		$this->post = Jam::build('test_post')->load_fields(array('id' => 1, 'name' => 'First Post', 'slug' => 'first-post', 'status' => 'draft', 'test_blog_id' => 1));
		$this->form = Jam::form($this->post, 'general');
	}

	public function test_checkbox()
	{

		$unchecked = $this->form->checkbox('is_test', array(), array('class' => 'myclass'));

		$this->form->object()->is_test = TRUE;
		$checked = $this->form->checkbox('is_test');

		$this->assertSelectCount('input[type="hidden"][name="is_test"][value="0"]', 1, $unchecked);
		$this->assertSelectCount('input.myclass[type="checkbox"][name="is_test"][id="is_test"][value="1"]', 1, $unchecked);

		$this->assertSelectCount('input[type="hidden"][name="is_test"][value="0"]', 1, $checked);
		$this->assertSelectCount('input[type="checkbox"][name="is_test"][id="is_test"][value="1"][checked="checked"]', 1, $checked);
	}

	public function test_hidden()
	{
		$input = $this->form->hidden('name', array(), array('class' => 'myclass'));

		$this->assertSelectCount('input.myclass[type="hidden"][name="name"][id="name"][value="'.$this->post->name.'"]', 1, $input);
	}

	public function test_radio()
	{
		$this->form->object()->sex = 'female';
		$male_radio = $this->form->radio('sex', array('value' => 'male'), array('class' => 'myclass'));
		$female_radio = $this->form->radio('sex', array('value' => 'female'));

		$this->assertSelectCount('input.myclass[type="radio"][name="sex"][id="sex_male"][value="male"]', 1, $male_radio);
		$this->assertSelectCount('input[type="radio"][name="sex"][id="sex_female"][value="female"][checked="checked"]', 1, $female_radio);
	}

	public function test_input()
	{
		$input = $this->form->input('name', array(), array('class' => 'myclass'));

		$this->assertSelectCount('input.myclass[type="text"][name="name"][id="name"][value="'.$this->post->name.'"]', 1, $input);
	}

	public function test_file()
	{
		$input = $this->form->file('name', array(), array('class' => 'myclass'));

		$this->assertSelectCount('input.myclass[type="file"][name="name"][id="name"][value=""]', 1, $input);
	}

	public function test_textarea()
	{
		$textarea = $this->form->textarea('name', array(), array('class' => 'myclass'));

		$this->assertSelectEquals('textarea.myclass[name="name"][id="name"]', $this->post->name, 1, $textarea);
	}

	public function test_select()
	{
		$select = $this->form->select('status', array('choices' => array('draft' => 'Draft Title', 'published' => 'Published Title', 'review' => 'Published Review')), array('class' => 'myclass'));

		$this->assertSelectCount('select.myclass[name="status"][id="status"]', 1, $select);
		$this->assertSelectCount('option', 3, $select);
		$this->assertSelectEquals('option[value="draft"][selected="selected"]', 'Draft Title', 1, $select);
		$this->assertSelectEquals('option[value="published"]', 'Published Title', 1, $select);
		$this->assertSelectEquals('option[value="review"]', 'Published Review', 1, $select);

		$blogs = new Jam_Query_Builder_Collection('test_blog');
		$blogs->load_fields(array(
			array('id' => 1, 'name' => 'Flowers blog', 'url' => 'http://flowers.wordpress.com'),
			array('id' => 2, 'name' => 'Awesome programming', 'url' => 'http://programming-blog.com'),
			array('id' => 3, 'name' => 'Tabless', 'url' => 'http://bobby-tables-ftw.com'),
		));

		$this->form->object()->test_blog = $blogs[0];

		$select = $this->form->select('test_blog', array('choices' => $blogs, 'include_blank' => 'BLANK'), array('class' => 'myclass2'));

		$this->assertSelectCount('select.myclass2[name="test_blog"][id="test_blog"]', 1, $select);
		$this->assertSelectCount('option', count($blogs) + 1, $select);

		$this->assertSelectEquals('option[value="'.$this->post->test_blog_id.'"][selected="selected"]', $blogs[0]->name(), 1, $select);
		foreach ($blogs as $blog)
		{
			$this->assertSelectEquals('option[value="'.$blog->id().'"]', $blog->name(), 1, $select);
		}
	}

	public function test_label()
	{
		$label = $this->form->label('name');
		$this->assertSelectEquals('label[for="name"]', 'Name', 1, $label);

		$label = $this->form->label('name', 'My Label');

		$this->assertSelectEquals('label[for="name"]', 'My Label', 1, $label);

		$label = $this->form->label('name', 'My Label', array('id' => 'new_id'));
		$this->assertSelectEquals('label[for="new_id"]', 'My Label', 1, $label);
	}

	public function test_row()
	{
		$row = $this->form->row('input', 'name', array(), array('class' => 'myclass'));
		$this->assertSelectCount('input.myclass[type="text"][name="name"][id="name"][value="'.$this->post->name.'"]', 1, $row);
		$this->assertSelectEquals('label[for="name"]', 'Name', 1, $row);
		$this->assertSelectCount('div.row.name-field.input-field', 1, $row);

		$row = $this->form->row('input', 'name', array('label' => 'My New Name', 'help' => 'help message'), array('class' => 'myclass'));

		$this->assertSelectEquals('label[for="name"]', 'My New Name', 1, $row);
		$this->assertSelectEquals('span.help-block', 'help message', 1, $row);
	}

}
