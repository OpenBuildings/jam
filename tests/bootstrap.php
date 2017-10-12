<?php

require_once __DIR__.'/../vendor/autoload.php';

Kohana::modules(array(
	'database' => MODPATH.'database',
	'image'    => MODPATH.'image',
	'jam'      => __DIR__.'/..',
	'jam-monetary' => MODPATH.'jam-monetary',
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
			'type'       => 'PDO',
			'connection' => array(
				'dsn' => 'mysql:host=localhost;dbname=openbuildings/jam',
				'username'   => 'root',
				'password'   => '',
				'persistent' => TRUE,
			),
			'identifier' => '`',
			'table_prefix' => '',
			'charset'      => 'utf8',
			'caching'      => FALSE,
		));

error_reporting(E_ALL ^ E_DEPRECATED);

Kohana::$environment = Kohana::TESTING;
foreach (Database::instance(Kohana::TESTING)->query(Database::SELECT, 'SHOW TABLES') as $table)
{
	$table_name = reset($table);
	Database::instance(Kohana::TESTING)->query(NULL, "TRUNCATE `{$table_name}`");
}
require_once __DIR__.'/database/fixtures/data.php';

