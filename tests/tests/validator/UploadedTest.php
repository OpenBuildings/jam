<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam
 * @group   jam.validator
 * @group   jam.validator.uploaded
 */
class Jam_Validator_UploadedTest extends Testcase_Validate_Upload {

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
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_is_file', 'Should be ok if the file exists');
		$this->assertNotHasError($this->model, 'file', 'uploaded_extension', 'Should be ok if the right extension');
	}

	public function test_is_file()
	{
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'));

		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_is_file');

	}

	public function test_extension()
	{
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'text.txt'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('only' => 'image'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_extension');
	}

	public function test_minimum_size()
	{
		// Logo is 1,215 bytes
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('minimum_size' => '10B'))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_minimum_size');

		Jam::validator_rule('uploaded', array('minimum_size' => '2KB'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_minimum_size');
	}

	public function test_maximum_size()
	{
		// Logo is 1,215 bytes
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('maximum_size' => '2KB'))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_maximum_size');

		Jam::validator_rule('uploaded', array('maximum_size' => '10B'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_maximum_size');
	}

	public function test_exact_size()
	{
		// Logo is 1,215 bytes
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('exact_size' => 1215))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_exact_size');

		Jam::validator_rule('uploaded', array('exact_size' => '10B'))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_exact_size');
	}

	public function test_minimum_width()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
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
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
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
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
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
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('maximum_height' => 100))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_maximum_height');

		Jam::validator_rule('uploaded', array('maximum_height' => 20))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_maximum_height');
	}

	public function test_exact_width()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('exact_width' => 127))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_exact_width');

		Jam::validator_rule('uploaded', array('exact_width' => 300))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_exact_width');
	}

	public function test_exact_height()
	{
		// Logo is 127 x 34
		$this->value
			->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
			->save_to_temp();

		Jam::validator_rule('uploaded', array('exact_height' => 34))->validate($this->model, 'file', $this->value);

		$this->assertNotHasError($this->model, 'file', 'uploaded_exact_height');

		Jam::validator_rule('uploaded', array('exact_height' => 300))->validate($this->model, 'file', $this->value);

		$this->assertHasError($this->model, 'file', 'uploaded_exact_height');
	}

    public function test_multiple_rules()
    {
        // Logo is 127 x 34
        $this->value
            ->source(Upload_Util::combine($this->test_local, 'source', 'logo.gif'))
            ->save_to_temp();

        Jam::validator_rule('uploaded', array(
            'only' => array('png'),
            'maximum_size' => '1B',
            'exact_height' => 20,
        ))->validate($this->model, 'file', $this->value);

        $this->assertHasError($this->model, 'file', 'uploaded_extension');
        $this->assertHasError($this->model, 'file', 'uploaded_maximum_size');
        $this->assertHasError($this->model, 'file', 'uploaded_exact_height');
    }
}
