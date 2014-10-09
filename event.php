<?php namespace ogo;

class Event {
	public $name;
	public $data;
	public $running = true;
	
	public function __construct($name, $data) {
		$this->name = $name;
		$this->data = $data;
	}
	
	public function stop_propagation() {
		$this->running = false;
	}
}
