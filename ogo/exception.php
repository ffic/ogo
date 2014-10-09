<?php namespace ogo;

class Exception extends \Exception {
	public function get_message() {
		return $this->getMessage();
	}
}

