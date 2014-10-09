<?php
define('ROOT_FOLDER', realpath(__DIR__));

function get_var($name, $type='GET', $default=null, $values=null) {
	$type = strtoupper($type);
	if ($type == "GET") {
		$data = $_GET;
	}
	elseif ($type == "POST") {
		$data = $_POST;
	}
	if (!isset($data[$name])) {
		return $default;
	}
	if (is_array($values)) {
		if ( ! in_array($data[$name], $values) ) {
			return $default;
		}
	}
	return trim($data[$name]);
}

function debug($data) {
	$app = \ogo\App::create();
	$app->debug($data);
}

// AUTOLOAD
spl_autoload_register(function ($name) {
	
	if (defined("APP")) {
		$name = preg_replace('/^APP/', APP, $name);
		$name = preg_replace('/^\\APP/', APP, $name);
	}
	
	$file = __DIR__ . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, strtolower($name)) . '.php';
	
	if ( file_exists( $file ) ) {
		require_once $file;
	}

});
// END AUTOLOAD