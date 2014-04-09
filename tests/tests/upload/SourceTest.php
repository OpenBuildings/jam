<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam.upload
 * @group   jam.upload.source
 */
class Jam_Upload_SourceTest extends Testcase_Validate_Upload {

	public function data_guess_type()
	{
		$test_local = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'test_data', 'test_local')).DIRECTORY_SEPARATOR;

		return array(
			array('http://example.com/file.png', 'url'),
			array('http://sub.example.com/deep/nesting/file.png', 'url'),
			array('php://input', 'stream'),
			array('populate/test_upload_populate.txt', 'temp'),
			array('populate/missing', 'temp'),
			array(array('error' => 0, 'name' => 'name', 'type' => 'text/plain', 'tmp_name' => '12345', 'size' => 10), 'upload'),
			array(array('error' => 0, 'name' => 'name', 'type' => 'text/plain', 'tmp_name' => '12345'), FALSE),
			array($test_local.'file'.DIRECTORY_SEPARATOR.'file1.txt', 'file'),
			array($test_local.'file', FALSE),
		);
	}

	/**
	 * @dataProvider data_guess_type
	 */
	public function test_guess_type($source, $expected_type)
	{
		$this->assertEquals($expected_type, Upload_Source::guess_type($source));
		$this->assertEquals($expected_type !== FALSE, Upload_Source::valid($source));
	}


	public function data_process_type_upload()
	{
		return array(
			array(
				UPLOAD_ERR_INI_SIZE,
				'File not uploaded properly. Error: must not be larger than '.ini_get('post_max_size')
			),
			array(
				UPLOAD_ERR_PARTIAL,
				'File not uploaded properly. Error: was only partially uploaded.'
			),
			array(
				UPLOAD_ERR_NO_TMP_DIR,
				'File not uploaded properly. Error: missing a temporary folder.'
			),
			array(
				UPLOAD_ERR_CANT_WRITE,
				'File not uploaded properly. Error: failed to write file to disk.'
			),
			array(
				UPLOAD_ERR_EXTENSION,
				'File not uploaded properly. Error: file upload stopped by extension.'
			),
		);
	}

	/**
	 * @dataProvider data_process_type_upload
	 */
	public function test_process_type_upload($upload_error, $expected_exception)
	{
		$data = array(
			'name' => 'file1.txt',
			'type' => 'text/plain',
			'size' => '4',
			'tmp_name' => $this->test_temp.'/test_file',
			'error' => $upload_error,
		);

		$this->setExpectedException('Jam_Exception_Upload', $expected_exception);

		Upload_Source::process_type_upload($data, $this->test_local);
	}
}
