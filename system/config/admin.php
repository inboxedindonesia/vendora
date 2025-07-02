<?php
// Site
$_['site_url']          = HTTP_SERVER;

// Database
$_['db_autostart']      = true;
$_['db_engine']         = DB_DRIVER; // mysqli, pdo or pgsql
$_['db_hostname']       = getenv('DB_HOST') ?: 'localhost';
$_['db_username']       = getenv('DB_USER') ?: 'root';
$_['db_password']       = getenv('DB_PASS') ?: '';
$_['db_database']       = getenv('DB_NAME') ?: '';
$_['db_port']           = getenv('DB_PORT') ?: 3306;
//$_['db_ssl_key']        = DB_SSL_KEY;
//$_['db_ssl_cert']       = DB_SSL_CERT;
//$_['db_ssl_ca']         = DB_SSL_CA;

// Session
$_['session_autostart'] = false;
$_['session_engine']    = 'db'; // db or file

// Error
$_['error_display']     = true;

// Actions
$_['action_pre_action'] = [
	'startup/setting',
	'startup/session',
	'startup/language',
	'startup/application',
	'startup/extension',
	'startup/startup',
	'startup/error',
	'startup/event',
	'startup/sass',
	'startup/login',
	'startup/authorize',
	'startup/permission'
];

// Actions
$_['action_default']     = 'common/dashboard';

// Action Events
$_['action_event']       = [
	'controller/*/before' => [
		0 => 'event/modification.controller',
		1 => 'event/language.before'
	],
	'controller/*/after' => [
		0 => 'event/language.after'
	],
	'model/*/before' => [
		0 => 'event/modification.model'
		//1 => 'event/debug.before'
	],
	//'model/*/after' => [
	//	0 => 'event/debug.after'
	//],
	'view/*/before' => [
		0   => 'event/modification.view',
		999 => 'event/language'
	],
	'language/*/before' => [
		0 => 'event/modification.language'
	],
	'language/*/after' => [
		0 => 'startup/language.after'
	]
];
