<?php namespace ogo;

class App {

	public $config;
	public $request;
	public $language;
	private $connections = [];
	private $data = [];
	
	private static $instance = null;
	private $event_dispatchers;
	private $routes = [];
	private $default_action;
	private $environment;
	private $public_folder;
	private $cache_folder;
	private $i18n = [];
	
	public function __construct() {
		$this->request = new Request();
	}
	
	public static function create() {
		if (!self::$instance) {
			self::$instance = new App();
		}
		return self::$instance;
	}
	
	public function get_environment() {
		return $this->environment;
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function get($key) {
		return $this->data[$key];
	}
	
	public function set_environment($env) {
		$env = strtolower($env);
		$this->environment = $env;
		switch ($env) {
			case "production":
				ini_set("display_errors", 0);
			break;
			case "development":
				ini_set("display_errors", 1);
				error_reporting(E_ALL ^ E_NOTICE);
			break;
			case "debug":
				ini_set("display_errors", 1);
				error_reporting(E_ALL ^ E_NOTICE);
			break;
			default: 
				ini_set("display_errors", 1);
				error_reporting(E_ALL ^ E_NOTICE);
			break;
		}
		
		if ($env != "production") {
			set_exception_handler(function ($e) {
				echo "<pre>";
				echo "Uncaught Exception: " , $e->getMessage(), "\n\n";
				
				echo "" . $e->getFile() . " ";
				echo "(" . $e->getLine() . ")\n";
				
				$trace = $e->getTrace();
				
				foreach ($trace as $part) {
					echo "" . $part["file"] . " ";
					echo "(" . $part["line"] . ")\n";
				}
				
				echo "</pre>";
				exit;
			});
		}
	}
	
	public function debug($data) {
		if ($this->environment != "production") {
			echo "<pre>";
			print_r($data);
			echo "</pre>";
		}
	}
	
	public function get_folder() {
		return ROOT_FOLDER;
	}
	
	public function set_public_folder($folder) {
		$this->public_folder = $folder;
	}
	
	public function get_public_folder() {
		return $this->public_folder;
	}
	
	public function set_cache_folder($folder) {
		$this->cache_folder = $folder;
	}
	
	public function get_cache_folder() {
		return $this->cache_folder;
	}
	
	public function set_connection($name, $conn) {
		$this->connections[$name] = $conn;
	}
	
	public function get_connection($name='default') {
		return $this->connections[$name];
	}
	
	public function get_request() {
		return $this->request;
	}
	
	public function get_config() {
		return $this->config;
	}
	
	public function set_config($file) {
		$config = new Config($file);
		if (!$this->environment) {
			throw new Exception('Environment not set.');
		}
		if (!$config->get($this->environment)) {
			throw new Exception('Environment not set in configuration file.');
		}
		$data = $config->get_data();
		$this->config = new Config($data[$this->environment]);
	}
	
	public function get_i18n($module) {
		if (defined("APP")) {
			$module = preg_replace('/^APP/', APP, $module);
			$module = preg_replace('/^\\APP/', APP, $module);
		}
		
		$folder = $this->get_folder() . '/' . str_replace(".", "/", $module) . '/i18n';
		$file = $folder . '/' . $this->get_language();
		if (!isset($this->i18n[$file])) {
			if ($this->config->i18n && is_dir($folder)) {
				$this->i18n[$file] = new I18n($folder, $this->get_language());
			}
			else {
				throw new Exception('I18n not found');
			}
		}
		
		return $this->i18n[$file];
	}
	
	public function enable_session() {
		$this->session = true;
		session_start();
	}
	
	public function disable_session() {
		$this->session = false;
		session_destroy();
	}
	
	public function session_set($key, $value) {
		$_SESSION[$key] = $value;
	}
	
	public function session_get($key) {
		return $_SESSION[$key];
	}
	
	public function add_route($method, $pattern, $exec) {
		$this->routes[] = [
			'pattern' 	=> $pattern,
			'method'	=> $method,
			'controller'	=> $exec
		];
	}
	
	public function def($exec) {
		$this->default_action = $exec;
	}
	
	public function run($request=null) {
	
		if ($request == null) {
			$request = $this->request;
		}
	
		$path = $request->get_path();
		$method = $request->get_method();
		
		foreach ($this->routes as $route) {
			$r = $this->test_pattern($request, $route);
			if ($r === false) {
				continue;
			}
			if (is_object($r)) {
				return $r;
			}
			else {
				$r = new Response('error');
				$r->set_code(500);
				return $r;
			}
		}
		
		if (is_callable($this->default_action)) {
			$action = $this->default_action;
			return $action($request);
		}
		
		return new Response('error');
	}
	
	public function redirect($url, $permanent=false) {
		if ($permanent) {
			header("HTTP/1.1 301 Moved Permanently"); 
		}
		header("Location: {$url}");
		exit();
	}
	
	public function finish($status, $options=null) {
	
		$code = isset($options["code"]) ? $options["code"] : null;
		$type = isset($options["type"]) ? $options["type"] : null;
		$data = isset($options["data"]) ? $options["data"] : null;
	
		$response = new Response($status, $type, $data, $code);
		$response->render();
		exit();
	}
	
	public function get_language() {
		return $this->language;
	}
	
	public function get_languages() {
		return $this->config->get("languages");
	}
	
	public function set_language($lang) {
		$this->language = $lang;
	}
	
	public function create_response($status=null, $type=null, $data=null) {
		return new Response($status, $type, $data);
	}
	
	public function call($namespace, $action, $params=null) {
	
		$parts = explode('.', $namespace);

		$controller = implode('\\', $parts) . '\Controller';
		
		return Controller::call($controller, $action, $params);
	}
	
	public function make_request($method, $path, $data=null, $language=null) {
	
		if ($language == null) {
			$language = $this->get_language();
		}
		
		$request = new Request($method, $path, $data, $language);
		
		$prev_request  = $this->request;
		$prev_language = $this->get_language();
		
		$this->request = $request;
		$this->set_language($language);
	
		$response = $this->run($request);
		
		$this->request = $prev_request;
		$this->set_language($prev_language);
		
		return $response;
	}
	
	public function attach_event($name, $function) {
		$name = strtolower($name);
		if (!$this->event_dispatchers) {
			$this->event_dispatchers = [];
		}
		if (!$this->event_dispatchers[$name]) {
			$this->event_dispatchers[$name] = [];
		}
		$this->event_dispatchers[$name][] = $function;
	}
	
	public function remove_event($name) {
		$name = strtolower($name);
		unset($this->event_dispatchers[$name]);
	}
	
	public function trigger($name, $data=null) {
		$name = strtolower($name);
		$event = new Event($name, $data);
		foreach ($this->event_dispatchers[$name] as $i => $f) {
			if (is_callable($f)) {
				$b = $f($event);
				if ($b === false) {
					$event->stop_propagation();
				}
			}
			if (!$event->running) {
				break;
			}
		}
	}
	
	private function test_pattern($request, $route) {
	
		$path = $request->get_path();
		$method = $request->get_method();

		if (preg_match("/{$method}/", $route["method"]) === 0) return false;
		
		$original_pattern = $route["pattern"];
		
		$pattern = preg_replace('/\[\:[a-z0-9_]+\]/i', '([^/]+)', $route["pattern"]);
		$pattern = str_replace('/', '\/', $pattern);
		
		if (preg_match('/^' . $pattern . '$/i', $path)) {
			
			preg_match('/' . $pattern . '/i', $path, $matches);
			
			array_shift($matches);
			
			preg_match_all('/\[\:([a-z0-9_]+)\]/i', $original_pattern, $keys);
			
			array_shift($keys);
			$keys = $keys[0];
			
			$params = array();
			for ($i = 0; $i < count($keys); $i++) {
				$params[$keys[$i]] = urldecode($matches[$i]);
			}
			
			if (is_callable($route["controller"])) {
				$b = $route["controller"]($request, $params);
				if ($b === false) {
					return false;
				}
			}
			return $b;
		}
		
		return false;
	}

}
