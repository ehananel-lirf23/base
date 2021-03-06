<?php defined('SYSPATH') OR die('No direct access allowed.');

return array
(
	'default' => array
	(
		'type'       => 'MySQLi',
		'connection' => array(
			/**
			 * The following options are available for MySQL:
			 *
			 * string   hostname     server hostname, or socket
			 * string   database     database name
			 * string   username     database username
			 * string   password     database password
			 * boolean  persistent   use persistent connections?
			 * array    variables    system variables as "key => value" pairs
			 *
			 * Ports and sockets may be appended to the hostname.
			 */
			'hostname'   => '{$DATABASE_HOST}',
			'database'   => '{$DATABASE_NAME}',
			'username'   => '{$DATABASE_USER}',
			'password'   => '{$DATABASE_PWD}',
			'port'       => NULL,
			'socket'     => NULL
		),
		'table_prefix' => '{$PROJECT_NAME}_',
		'charset'      => 'utf8mb4',
		'caching'      => FALSE,
	),
	'mysql' => array
	(
		'type'       => 'MySQL',
		'connection' => array(
			/**
			 * The following options are available for MySQL:
			 *
			 * string   hostname     server hostname, or socket
			 * string   database     database name
			 * string   username     database username
			 * string   password     database password
			 * boolean  persistent   use persistent connections?
			 * array    variables    system variables as "key => value" pairs
			 *
			 * Ports and sockets may be appended to the hostname.
			 */
			'hostname'   => '{$DATABASE_HOST}',
			'database'   => '{$DATABASE_NAME}',
			'username'   => '{$DATABASE_USER}',
			'password'   => '{$DATABASE_PWD}',
			'persistent' => FALSE,
		),
		'table_prefix' => '{$PROJECT_NAME}_',
		'charset'      => 'utf8mb4',
		'caching'      => FALSE,
	),
	'alternate' => array(
		'type'       => 'PDO',
		'connection' => array(
			/**
			 * The following options are available for PDO:
			 *
			 * string   dsn         Data Source Name
			 * string   username    database username
			 * string   password    database password
			 * boolean  persistent  use persistent connections?
			 */
			'dsn'        => 'mysql:host={$DATABASE_HOST};dbname={$DATABASE_NAME}',
			'username'   => '{$DATABASE_USER}',
			'password'   => '{$DATABASE_PWD}',
			'persistent' => FALSE,
		),
		/**
		 * The following extra options are available for PDO:
		 *
		 * string   identifier  set the escaping identifier
		 */
		'table_prefix' => '{$PROJECT_NAME}_',
		'charset'      => 'utf8mb4',
		'caching'      => FALSE,
	),
);
