<?php namespace ogo;

class Request {
	private $path;
	private $string_query;
	private $data;
	private $languages;
	private $method;
	private $ajax = false;
	
	public function __construct($method=null, $path=null, $data=null, $language=null) {
		if ($path == null) {
			$this->path = explode("?", $_SERVER["REQUEST_URI"])[0];
			$parts = explode("?", $_SERVER["REQUEST_URI"]);
		}
		else {
			$parts = explode("?", $path);
			$this->path = $parts[0];
		}
		if (count($parts) == 2) {
			$this->string_query = $parts[1];
		}
		else {
			$this->string_query = "";
		}
		if ($method == null) {
			$this->method = strtolower($_SERVER["REQUEST_METHOD"]);
		}
		else {
			$this->method = strtolower($method);
		}
		if ($data == null) {
			$this->set_data();
		}
		else {
			$this->data = $data;
		}
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			$this->ajax = true;
		}
		if ($language == null) {
			$this->parse_languages();
		}
		else {
			$this->languages = [$language];
		}
	}
	
	public function get($name, $default=null, $values=null) {
		if (!isset($this->data[$name])) {
			return $default;
		}
		if (is_array($values)) {
			if ( ! in_array($this->data[$name], $values) ) {
				return $default;
			}
		}
		return $this->data[$name];
	}
	
	public function get_data() {
		return $this->data;
	}
	
	public function get_languages() {
		return $this->languages;
	}
	
	public function get_method() {
		return $this->method;
	}
	
	public function is_ajax() {
		return $this->ajax;
	}
	
	public function get_path() {
		return $this->path;
	}
	
	public function get_path_parts() {
		return explode("/", trim($this->get_path(), "/"));
	}
	
	public function get_client_ip() {
		$ipaddress = '';
		if ($_SERVER['HTTP_CLIENT_IP'])
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if($_SERVER['HTTP_X_FORWARDED_FOR'])
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if($_SERVER['HTTP_X_FORWARDED'])
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if($_SERVER['HTTP_FORWARDED_FOR'])
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if($_SERVER['HTTP_FORWARDED'])
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if($_SERVER['REMOTE_ADDR'])
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';
		return $ipaddress;
	}
	
	private function set_data() {
		$method = $this->get_method();
		
		switch ($method) {
			case 'get':
				$this->data = $_GET;
			break;
			case 'post':
				$this->data = $_POST;
			break;
			case 'put':
				parse_str(file_get_contents('php://input'), $this->data);
			break;
			case 'delete':
				$this->data = null;
			break;
		}
	}
	
	private function parse_languages() {
		$langs = array();
		if ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
			// break up string into pieces (languages and q factors)
			preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse);

			if (count($lang_parse[1])) {
				// create a list like "en" => 0.8
				$langs = array_combine($lang_parse[1], $lang_parse[4]);
				
				// set default to 1 for any without q factor
				foreach ($langs as $lang => $val) {
					if ($val === '') $langs[$lang] = 1;
				}

				// sort list based on value	
				arsort($langs, SORT_NUMERIC);
			}
		}
		else {
			$langs["en"] = 1;
		}
		
		$this->languages = [];
		
		foreach ($langs as $lang => $p) {
			$l = str_replace("_", "-", $lang);
			$short_name = explode("-", $l)[0];
			if (!in_array($short_name, $this->languages)) {
				$this->languages[] = $short_name;
			}
		}
	}
}
