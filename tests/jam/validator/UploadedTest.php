<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.uploaded
 */
class Jam_Validator_UploadedTest extends Unittest_Jam_Upload_TestCase {

	public function setUp()
	{
		parent::setUp();
		$this->model = Jam::build('test_image')->load_fields(array('id' => 1, 'file' => 'file.jpg'));
		$this->value = $this->model->file;
	}

	public function tearDown()
	{
		$this->value->clear();
		parent::tearDown();
	}

	public function test_no_source()
	{
		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_is_file', 'Should be ok when no file is passed');
	}

	public function test_normal()
	{
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_is_file', 'Should be ok if the file exists');
		$this->assertNotHasError($this->model, 'file', 'uploaded_extension', 'Should be ok if the right extension');
	}

	public function test_is_file()
	{
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'logo.gif'));

		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_is_file');

	}

	public function test_extension()
	{
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'text.txt'))
			->save_to_temp();
	
		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_extension');
	}

	public function test_minimum_width()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();
		
		Jam::validator_rule('uploaded', array('minimum_width' => 100))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_minimum_width');

		Jam::validator_rule('uploaded', array('minimum_width' => 200))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_minimum_width');
	}

	public function test_minimum_height()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();
		
		Jam::validator_rule('uploaded', array('minimum_height' => 20))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_minimum_height');

		Jam::validator_rule('uploaded', array('minimum_height' => 50))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_minimum_height');
	}

	public function test_maximum_width()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();
		
		Jam::validator_rule('uploaded', array('maximum_width' => 300))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_maximum_width');

		Jam::validator_rule('uploaded', array('maximum_width' => 100))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_maximum_width');
	}

	public function test_maximum_height()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_File::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();
		
		Jam::validator_rule('uploaded', array('maximum_height' => 100))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_maximum_height');

		Jam::validator_rule('uploaded', array('maximum_height' => 20))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_maximum_height');
	}
}