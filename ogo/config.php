<?php namespace ogo;

class Config {

	private $data = [];
	
	public function __construct($file) {
		if (is_array($file)) {
			$this->data = $file;
		}
		else {
			$this->data = include $file;
		}
	}
	
	public function get($name) {
	
		if (preg_match("/\./", $name)) {
			$parts = explode(".", $name);
			$data = $this->data;
			foreach ($parts as $part) {
				$data = $data[$part];
			}
			return $data;
		}
	
		if (!isset($this->data[$name])) {
			return null;
		}
		return $this->data[$name];
	}
	
	public function get_data() {
		return $this->data;
	}
	
	public function __get($key) {
		$r = $this->data[$key];
		if (is_array($r)) {
			$r = json_decode(json_encode($r), FALSE);
		}
		return $r;
	}

}
