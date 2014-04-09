<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Testcase_Validate_Upload extends Testcase_Validate {

	public $test_local;
	public $test_local2;
	public $test_temp;

	public function setUp()
	{
		parent::setUp();

		Database::instance(Kohana::TESTING)->begin();

		$this->test_local = realpath(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', '..', 'test_data', 'test_local'))).DIRECTORY_SEPARATOR;
		$this->test_local2 = realpath(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', '..', 'test_data', 'test_local2'))).DIRECTORY_SEPARATOR;
		$this->test_temp = realpath(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__),  '..', '..', '..', 'test_data', 'temp')));

		Kohana::$config->load('jam')
			->set('upload.temp.path', $this->test_temp)
			->set('upload.temp.web', '/temp/')
			->set('upload.servers', array(
				'test_local' => array(
					'type' => 'local',
					'params' => array(
						'path' => $this->test_local,
						'web' => '/upload',
					),
				),
				'test_local2' => array(
					'type' => 'local',
					'params' => array(
						'path' => $this->test_local2,
						'web' => '/upload',
					),
				),
				'default' => array(
					'type' => 'local',
					'params' => array(
						'path' => $this->test_local,
						'web' => '/upload',
					),
				),
			));
	}

	public function tearDown()
	{
		Database::instance(Kohana::TESTING)->rollback();
		parent::tearDown();
	}
}
