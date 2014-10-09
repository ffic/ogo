<?php namespace ogo;

class Controller {

	protected $app;
	protected $config;
	protected $folder;
	protected $i18n;

	public function __construct() {
	
		$this->app = App::create();
		
		$currentClass = get_class($this);
		$refl = new \ReflectionClass($currentClass);
		$namespace = $refl->getNamespaceName();
		
		if (defined("APP")) {
			$namespace = preg_replace('/^APP/', APP, $namespace);
			$namespace = preg_replace('/^\\APP/', APP, $namespace);
		}
		
		$this->request = new Request();
		
		$this->folder = $this->app->get_folder() . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $namespace);
		
		$functions = $this->folder . DIRECTORY_SEPARATOR . 'functions.php';
		if ( file_exists($functions) ) {
			include_once $functions;
		}
		$config = $this->folder . DIRECTORY_SEPARATOR . 'config.php';
		if ( file_exists($config) ) {
			$this->config = new Config($config);
		}
		
		if ($this->app->config->i18n && is_dir($this->folder . DIRECTORY_SEPARATOR . "i18n")) {
			$this->i18n = new I18n($this->folder . DIRECTORY_SEPARATOR . "i18n", $this->app->get_language());
		}
		
		if (method_exists($this, "init")) {
			$this->init();
		}
	}
	
	public function set_i18n($i18n) {
		$this->i18n = $i18n;
	}
	
	public function get_app() {
		return $this->app;
	}

	public function get_view($name) {
		
 		$file = $this->folder . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $name . '.phtml';
		
		if ( file_exists( $file ) ) {
			$view = new View($file);
			$view->set_i18n($this->i18n);
			return $view;
		}
		else {
			throw new \ogo\Exception("View {$file} not found.");
		}
		
		return null;
		
	}
	
	public function create_response($status=null, $type=null, $data=null) {
		return new Response($status, $type, $data);
	}
	
	public static function call($module, $action, $params=null) {

		$class = explode('.', $module);
		
		$class = '\\' . implode('\\', $class);
		
		if (class_exists($class)) {
			$w = new $class;
		}
		else {
			if (defined('APP')) {
				$class = preg_replace('/^\\\\APP/', '\\' . APP, $class);
				if (class_exists($class)) {
					$w = new $class;
				}
				else {
					throw new \ogo\Exception("Class {$class} not exists.");
				}
			}
			else {
				throw new \ogo\Exception("Class {$class} not exists.");
			}
		}
		
		$method = 'execute_' . strtolower($action);
		
		if ( method_exists($w, $method) ) {
			return $w->$method($params);
		}
		else {
			throw new \ogo\Exception("Action {$method} not exists.");
		}
	}
}