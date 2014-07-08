<?php

require_once __DIR__.'/../vendor/autoload.php';

Kohana::modules(array(
	'database' => MODPATH.'database',
	'image'    => MODPATH.'image',
	'jam'      => __DIR__.'/..',
));

function test_autoload($class)
{
	$file = str_replace('_', '/', $class);

	if ($file = Kohana::find_file('tests/classes', $file))
	{
		require_once $file;
	}
}

spl_autoload_register('test_autoload');

Kohana::$config
	->load('database')
		->set(Kohana::TESTING, array(
			'type'       => 'MySQL',
			'connection' => array(
				'hostname'   => 'localhost',
				'database'   => 'openbuildings/jam',
				'username'   => 'root',
				'password'   => '',
				'persistent' => TRUE,
			),
			'table_prefix' => '',
			'charset'      => 'utf8',
			'caching'      => FALSE,
		));

error_reporting(E_ALL ^ E_DEPRECATED);

Kohana::$environment = Kohana::TESTING;
foreach (Database::instance(Kohana::TESTING)->list_tables() as $table)
{
	Database::instance(Kohana::TESTING)->query(NULL, "TRUNCATE `{$table}`");
}
require_once __DIR__.'/database/fixtures/data.php';

