<?php namespace ogo;

class Lib {
	protected $app;
	protected $cache;
	
	public function __construct() {
		$this->app = App::create();
	}
	
	public function get_app() {
		return $this->app;
	}
	
	public function set_cache($key, $function) {
		if ($function == null) {
			unset($this->cache[$key]);
			return null;
		}
		if ( ! isset($this->cache[$key] )) {
			if (is_callable($function) ) {
				$this->cache[$key] = $function($this);
			}
			else {
				$this->cache[$key] = $function;
			}
		}
		return $this->cache[$key];
	}
}
