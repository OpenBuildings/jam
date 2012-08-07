<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Unittest_Jam_Upload_TestCase extends Unittest_Jam_TestCase {

	public $test_local;
	public $test_temp;

	public function setUp()
	{
		$this->test_local = realpath(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', '..', '..', '..', 'test_data', 'test_local'))).DIRECTORY_SEPARATOR;
		$this->test_temp = realpath(join(DIRECTORY_SEPARATOR, array(dirname(__FILE__),  '..', '..', '..', '..', '..', 'test_data', 'temp')));

		$this->environmentDefault = Arr::merge(
			array(
				'jam.upload.temp' => array(
					'path' => $this->test_temp,
					'web' => '/temp/',
				),
				'jam.upload.servers' => array(
					'test_local' => array(
						'type' => 'local',
						'params' => array(
							'path' => $this->test_local,
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
				),
			),
			(array) $this->environmentDefault
		);

		parent::setUp();
	}

} // End Kohana_Unittest_Jam_TestCase