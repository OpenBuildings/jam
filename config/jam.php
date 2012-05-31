<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'default_form' => 'general',
	
	'upload' => array(
		'temp' => array(
			'path' => DOCROOT.'upload'.DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR, 
			'web' => 'upload/temp/'
		),		
		'servers' => array(
			'local' => array(
				'type' => 'local',
				'params' => array(
					'path' => DOCROOT.'upload',
					'web' => 'upload',
				)
			)
		)
	)
);