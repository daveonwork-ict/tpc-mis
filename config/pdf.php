<?php

return [
	'mode'                  => 'utf-8',
	'format'                => 'A4',
	'author'                => env('APP_NAME', 'Laravel'),
	'subject'               => '',
	'keywords'              => '',
	'creator'               =>  env('APP_NAME', 'Laravel'),
	'display_mode'          => 'fullpage',
	'tempDir'               => base_path('../temp/'),
	'pdf_a'                 => false,
	'pdf_a_auto'            => false,
	'icc_profile_path'      => ''
];
