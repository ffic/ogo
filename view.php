<?php namespace ogo;

class View {

	private $file;
	private $data = array();
	private $app;
	private $i18n;
	private $uid;
	
	public function __construct($file) {
		$this->file = "/" . trim($file, "/");
		$this->app = \ogo\App::create();
		$this->uid = uniqid();
	}
	
	public function parse() {
		try {
			ob_start();
			$this->render();
			$contents = ob_get_contents();
			ob_end_clean();
		}
		catch (\ogo\Exception $e) {
			ob_clean();
			$contents = $e->get_message();
		}
		return $contents;
	}
	
	public function render() {
		extract($this->data, EXTR_OVERWRITE);
		include $this->file;
	}
	
	public function html($txt) {
		return htmlentities($txt, ENT_QUOTES, 'UTF-8');
	}
	
	public function escape($txt) {
		return $this->html($txt);
	}
	
	public function escape_text($txt) {
		return str_replace('"', '\"', html_entity_decode($txt, ENT_COMPAT, 'utf-8'));
	}
	
	public function bind($data) {
		foreach ($data as $key => $value) {
			$this->data[$key] = $value;
		}
	}
	
	public function __set($name, $value) {
		$this->data[$name] = $value;
	}
	
	public function __get($name) {
		return !empty($this->data[$name]) ? $this->data[$name] : null;
	}
	
	public function txt($text) {
		return $this->i18n->translate($text);
	}
	
	public function etxt($text) {
		return $this->escape($this->txt($text));
	}
	
	public function set_i18n($i18n) {
		$this->i18n = $i18n;
	}
	
	public function get_uid($prefix='') {
		return $prefix . $this->uid;
	}
	
	public function include_min_js($files) {
		if ($this->app->get_environment() == "development") {
			$res = "";
			foreach ($files as $file) {
				$res .= '<script type="text/javascript" src="' . $file . '"></script>'. "\n";
			}
			return $res;
		}
		else {
			return '<script type="text/javascript" src="/min/f=' . implode($files, ",") . '"></script>';
		}
	}
	
	public function include_min_css($files) {
		if ($this->app->get_environment() == "development") {
			$res = "";
			foreach ($files as $file) {
				$res .= '<link rel="stylesheet" href="' . $file . '">'. "\n";
			}
			return $res;
		}
		else {
			return '<link rel="stylesheet" href="/min/f=' . implode($files, ",") . '">';
		}
	}

}