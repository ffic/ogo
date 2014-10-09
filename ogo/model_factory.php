<?php namespace ogo;

class Model_Factory {
	
	protected $model;
	protected $app;
	
	public function __construct() {
		$this->app = App::create();
	}
	
	public function find_by_id($id) {
		$class = $this->get_model_class();
		return new $class($id);
	}
	
	public function find_by($condition, $data, $offset=null, $limit=null) {
		$class = $this->get_model_class();
		$vars = get_class_vars($class);
		$table = $vars["table"];
		$rows = $this->app->get_connection()->prepare("select * from {$table} where " . $condition, $data)->limit($offset, $limit)->execute();
		$response = [];
		foreach ($rows as $data) {
			$response[] = new $class($data);
		}
		return $response;
	}
	
	protected function get_model_class() {
		$class = str_replace('.', '\\', $this->model);
		return '\\' . trim($class, '\\');
	}
	
	public function get_app() {
		return $this->app;
	}
}
