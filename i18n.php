<?php namespace ogo;

class I18n {

	private $data;
	
	public function __construct($folder, $language) {
		$this->language = $language;
		
		$file = rtrim($folder, "/") . "/" . strtolower($language) . ".php";
		
		if (file_exists($file)) {
			$this->data = include $file;
		}
		else {
			$this->data = [];
		}
	}
	
	public function translate($txt) {
	
		if (isset($this->data[$txt])) {
			return $this->data[$txt];
		}
		else {
			$parts = explode(".", $txt);
			if (count($parts) == 1) {
				return $txt;
			}
			else {
				$r = [];
				$first = true;
				foreach ($parts as $part) {
					if ($first) {
						$r = $this->data;
						$first = false;
					}
					$r = isset($r[$part]) ? $r[$part] : false;
					if (!$r) return $txt;
				}
				return $r;
			}
		}
	}
	
	public function txt($txt) {
		return $this->translate($txt);
	}
	
	public function __($txt) {
		return $this->translate($txt);
	}
	
}
