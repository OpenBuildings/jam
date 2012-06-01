<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Tests for core Jam methods.
 * @group jam
 * @group jam.field
 * @group jam.field.upload
 * @package Jam
 */
if ( ! defined("JAM_UPLOAD_TEMP"))
	define("JAM_UPLOAD_TEMP", MODPATH . "extensions/jam/tests/test_data/temp");

if ( ! defined("JAM_UPLOAD_LOCAL"))
	define("JAM_UPLOAD_LOCAL", MODPATH . "extensions/jam/tests/test_data/local");
	
if ( ! defined("JAM_UPLOAD_TEST_LOCAL"))
	define("JAM_UPLOAD_TEST_LOCAL", MODPATH . "extensions/jam/tests/test_data/test_local");

class Jam_Field_UploadTest extends Unittest_TestCase {

	protected $environmentDefault = array(
		'jam.upload.temp' => array(
			'path' => JAM_UPLOAD_TEMP,
			'web' => '/temp/',
		),
		'jam.upload.servers' => array(
			'test_local' => array(
				'type' => 'local',
				'params' => array(
					'path' => JAM_UPLOAD_TEST_LOCAL,
					'web' => 'upload',
				),
			),
			'default' => array(
				'type' => 'local',
				'params' => array(
					'path' => JAM_UPLOAD_TEST_LOCAL,
					'web' => 'upload',
				),
			),			
		),

	);

	static public function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		
		if ( ! is_dir(JAM_UPLOAD_LOCAL))
		{
			mkdir(JAM_UPLOAD_LOCAL, 0777, true);	
		}
		if ( ! is_dir(JAM_UPLOAD_TEST_LOCAL))
		{
			mkdir(JAM_UPLOAD_TEST_LOCAL, 0777, true);	
		}			
	}	

	public function test_constructor()
	{
		$field = Jam::field('upload', array(
			'server' => 'test_local'
		));
		$field->temp = Upload_Temp::factory();
		
		$this->assertInstanceOf('Upload_Server_Local', $field->server);
		$this->assertInstanceOf('Upload_Temp', $field->temp);
	}

	public function test_initialize()
	{
		$jam = new Model_Test_Upload();

		$field = Jam::field('upload', array('server' => 'test_local'));

		$field->initialize($jam, 'file');
		$this->assertNotEmpty($field->rules);
	}


	public function provider_is_valid_upload()
	{
		return array(
			array( array(),                                                                                                               true,  null,                 true ),
			array( array('error' => 4, 'name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4),             true,  null,                 true ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4), false, null,                 true ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4), true,  array('txt'),         false ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4), true,  array('html'),        true ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4), true,  array('txt', 'html'), false ),
			array( array('name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4),                           true,  null,                 true ),
			array( array('error' => UPLOAD_ERR_OK, 'type' => 'test/plain', 'tmp_name' => '/tmp/file', 'size' => 4),                       true,  null,                 true ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'tmp_name' => '/tmp/file', 'size' => 4),                         true,  null,                 true ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'type' => 'test/plain', 'size' => 4),                            true,  null,                 true ),
			array( array('error' => UPLOAD_ERR_OK, 'name' => 'test.txt', 'type' => 'test/plain', 'tmp_name' => '/tmp/file'),              true,  null,                 true ),
		);
	}

	/**
	 * @dataProvider provider_is_valid_upload
	 */
	public function test_is_invalid_upload($file, $is_uploaded_file, $types, $is_invalid)
	{
		$tmp = $this->getMock('Upload_Temp', array('is_uploaded_file'));
		$tmp->expects($this->any())->method('is_uploaded_file')->will($this->returnValue($is_uploaded_file));
		$field = Jam::field('upload', array('temp' => $tmp, 'types' => (array) $types));
		
		$model = Jam::factory('Test_Upload');
		
		$this->assertEquals( ! $is_invalid, $field->check_valid_upload($file, 'file', Validation::factory(array()), $model ));
	}

	public function test_upload()
	{
		$file = array('error' => UPLOAD_ERR_OK, 'name' => 'upload_test.txt', 'type' => 'test/plain', 'tmp_name' => JAM_UPLOAD_LOCAL.'/upload_test', 'size' => 4);

		file_put_contents($file['tmp_name'], 'data');

		$field = Jam::field('upload', array(
			'server' => 'test_local'
		));
		$field->temp = Upload_Temp::factory();
		
		$field->temp->sequrity_check = FALSE;

		$jam = new Model_Test_Upload();

		$validation = Validation::factory(array('file' => $file));
		$this->assertTrue($field->_upload( $validation, $jam, 'file'));

		$this->assertFileExists($field->temp->file());
		unlink($field->temp->file());
	}

	public function test_upload_populate()
	{
		if ( ! is_dir(JAM_UPLOAD_TEMP.'/populate'))
		{
			mkdir(JAM_UPLOAD_TEMP.'/populate', 0777, TRUE);
		}
		file_put_contents(JAM_UPLOAD_TEMP.'/populate/test_upload_populate.txt', 'data');

		$field = Jam::field('upload');
		$field->temp = Upload_Temp::factory();
		
		$jam = new Model_Test_Upload();

		$validation = Validation::factory(array('file' => 'populate/test_upload_populate.txt'));
		$this->assertTrue($field->_upload( $validation, $jam, 'file'));

		$this->assertEquals("populate", $field->temp->key);
		$this->assertFileExists($field->temp->file());
	}


	public function test_upload_save()
	{
		$tmp = $this->getMock('Upload_Temp', array('is_uploaded_file'));
		$tmp->filename("test.txt");

		$field = Jam::field('upload', array('temp' => $tmp));
		$jam = new Model_Test_Upload();
		$this->assertEquals('test.txt', $field->save($jam, 'test.txt', true));
	}

	public function test_delete_value()
	{
		$tmp = $this->getMock('Upload_Temp', array('is_uploaded_file'));

		$field = Jam::field('upload', array('temp' => $tmp));
		$jam = new Model_Test_Upload();
		$this->assertEquals('', $field->save($jam, '', true));
	}



}