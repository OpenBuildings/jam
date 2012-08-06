<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests for Jam_Validator functionality.
 *
 * @package Jam
 * @group   jam.upload
 * @group   jam.upload.file
 */
class Jam_Upload_FileTest extends Unittest_Jam_TestCase {

	public $test_local;

	public function setUp()
	{
		$this->test_local = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'test_data', 'test_local')).DIRECTORY_SEPARATOR;

		$this->environmentDefault = array(
			'jam.upload.temp' => array(
				'path' => join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'test_data', 'temp')),
				'web' => '/temp/',
			),
			'jam.upload.servers' => array(
				'test_local' => array(
					'type' => 'local',
					'params' => array(
						'path' => $this->test_local,
						'web' => 'upload',
					),
				),
				'default' => array(
					'type' => 'local',
					'params' => array(
						'path' => $this->test_local,
						'web' => 'upload',
					),
				),			
			),

		);

		parent::setUp();
	}

	public function data_sanitize()
	{
		return array(
			array('filename.jpg', 'filename.jpg'),
			array('file 1 name.jpg', 'file-1-name.jpg'),
			array('filênàme.jpg', 'filename.jpg'),
			array('филенаме.jpg', 'filename.jpg'),
			array('file- -1.jpg', 'file-1.jpg'),
		);
	}

	/**
	 * @dataProvider data_sanitize
	 */
	public function test_sanitize($filename, $sanitized_filename)
	{
		$this->assertEquals($sanitized_filename, Upload_File::sanitize($filename));
	}

	public function data_pick_filename()
	{
		return array(
			array('/var/logo.gif', NULL, 'logo.gif'),
			array('/var/logo.png', NULL, 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/logo.png', 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/logo.jpg?test=1', 'logo.jpg'),
			array('/var/logo.tiff', 'http://example.com/file?test=logo.gif', 'logo.gif'),
			array('/var/logo.tiff', 'http://example.com/file?test=logo.png&post=get', 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/file.php?test=logo.png&post=get', 'logo.png'),
			array('/var/logo.tiff', 'http://example.com/file', 'logo.tiff'),
			array('/var/logo.gif', 'http://example.com/file.gif', 'file.gif'),
		);
	}

	/**
	 * @dataProvider data_pick_filename
	 */
	public function test_pick_filename($file, $url, $filename)
	{
		$this->assertEquals($filename, Upload_File::pick_filename($file, $url));
	}

	public function data_normalize_extension()
	{
		return array(
			// FROM FILE
			array('name/logo.gif', NULL, NULL, 'logo.gif'),
			array('name/logo_gif', NULL, NULL, 'logo-gif.gif'),
			array('name/logo.png', NULL, NULL, 'logo.png'),
			array('name/logo_png', NULL, NULL, 'logo-png.png'),
			array('name/logo.jpg', NULL, NULL, 'logo.jpg'),
			array('name/logo_jpg', NULL, NULL, 'logo-jpg.jpg'),
			array('name/test.txt', NULL, NULL, 'test.txt'),
			array('name/test_txt', NULL, NULL, 'test-txt.txt'),

			// FROM MIME
			array('logo', 'image/gif', NULL, 'logo.gif'),
			array('logo', 'image/png', NULL, 'logo.png'),
			array('logo', 'image/jpeg', NULL, 'logo.jpg'),
			array('logo', 'image/jpg', NULL, 'logo.jpg'),
			array('logo', 'text/plain', NULL, 'logo.txt'),
			array('logo', 'image/gif', 'http://example.com/logo.php?query=param', 'logo.gif'),

			// FROM URL
			array(NULL, NULL, 'http://example.com/logo.gif', 'logo.gif'),
			array(NULL, NULL, 'http://example.com/logo.png', 'logo.png'),
			array(NULL, NULL, 'http://example.com/logo.jpg?query=param', 'logo.jpg'),
			array(NULL, NULL, 'http://example.com/logo?query=test&post=1', 'logo.jpg'),
			array(NULL, NULL, 'http://example.com/logo?file=logo.json', 'logo.json'),
			array(NULL, NULL, 'http://example.com/logo.php?file=logo.gif', 'logo.gif'),
			array(NULL, NULL, 'http://example.com/test', 'test.jpg'),
		);
	}

	public function data_combine()
	{
		return array(
			array(array('test', 'test2'), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array('test', 'test2'.DIRECTORY_SEPARATOR), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array('test'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.'test2'), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array('test'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR.'test2'.DIRECTORY_SEPARATOR), 'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array(DIRECTORY_SEPARATOR.'test', DIRECTORY_SEPARATOR.'test2'), DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test2'),
			array(array(DIRECTORY_SEPARATOR.'test', DIRECTORY_SEPARATOR.'test2', 'test3'), DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test2'.DIRECTORY_SEPARATOR.'test3'),
			array(array(DIRECTORY_SEPARATOR.'test', DIRECTORY_SEPARATOR.'test2', 'test3', 'test4'), DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'test2'.DIRECTORY_SEPARATOR.'test3'.DIRECTORY_SEPARATOR.'test4'),
		);
	}
	
	/**
	 * @dataProvider data_combine
	 */
	public function test_combine($filename_parts, $combined_filename)
	{
		$this->assertEquals($combined_filename, call_user_func_array('Upload_File::combine', $filename_parts));
	}

	public function data_is_filename()
	{
		return array(
			array('file.png', TRUE),
			array('file.js', TRUE),
			array('/to/file/file.png', TRUE),
			array('http://example.com/file.png', TRUE),
			array('http://example.com/page', FALSE),
			array('/to/file/dir', FALSE),
			array('page', FALSE),
		);
	}


	/**
	 * @dataProvider data_is_filename
	 */
	public function test_is_filename($is_filename, $is_filename)
	{
		$this->assertEquals($is_filename, Upload_File::is_filename($filename));
	}

	/**
	 * @dataProvider data_normalize_extension
	 */
	public function test_normalize_extension($file, $mime, $url, $expected_filename)
	{
		$file = $file ? $this->test_local.$file : NULL;
		$filename = Upload_File::normalize_Extension($file, $mime, $url);

		$this->assertEquals($expected_filename, $filename);
	}

	public function test_width_height_aspect()
	{
		$upload = new Upload_File('default');
		$upload->set_size(100, 200);

		$this->assertEquals(100, $upload->width());
		$this->assertEquals(200, $upload->height());
		$this->assertEquals(array(100, 200), $upload->aspect()->dims());
	}

}