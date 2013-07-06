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
}