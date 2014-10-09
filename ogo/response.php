<?php namespace ogo;

class Response {

	const statusERROR 	= 'error'; 	// === 0
	const statusOK 		= 'ok'; 	// === 1
	const statusREDIRECT 	= 'redirect'; 	// === 2
	
	private static $headers = false;
	
	public static $types = array(
		"php"		=> "php",
		"widget" 	=> "widget",
		"component"	=> "widget",
		"html"		=> "text/html",
		"text"		=> "text/plain",
		"json"		=> "application/json",
		"xml"		=> "text/xml",
		"rss"		=> "application/rss+xml",
		"csv"		=> "text/csv",
		"bin"		=> "application/octet-stream",
		"pdf"		=> "application/pdf",
		"jpeg"		=> "image/jpeg",
		"jpg"		=> "image/jpeg",
		"gif"		=> "image/gif",
		"png"		=> "image/png"
	);
	
	public static $messages	= array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		449 => 'Retry With',
		450 => 'Blocked by Windows Parental Controls',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);
	
	private $status;
	private $type;
	private $data = null;
	private $code = null;
	
	public function __construct($status=null, $type=null, $data=null, $code=null) {
		if ($status == null) {
			$status = "ok";
		}
		if ($type == null) {
			$type = "html";
		}
		$this->set_status($status);
		$this->set_type($type);
		$this->set_data($data);
		if ($code != null) {
			$this->set_code($code);
		}
	}
	
	public function set_code($code) {
		if (isset(self::$messages[$code])) {
			$this->code = $code;
		}
		else {
			throw new Exception("Invalid response code.");
		}
	}
	
	public function get_code() {
		return $this->code;
	}
	
	public function set_status($status) {
		if ($status === 0 || $status == "error") {
			$this->status = "error";
			$this->code = 404;
		}
		elseif ($status === 1 || $status == "ok") {
			$this->status = "ok";
			$this->code = 200;
		}
		elseif ($status === 2 || $status == "redirect") {
			$this->status = "redirect";
			$this->code = 303;
		}
		else {
			throw new Exception("Invalid response status.");
		}
	}
	
	public function get_status() {
		return $this->status;
	}
	
	public function set_type($type) {
		if (isset(self::$types[$type])) {
			$this->type = self::$types[$type];
		}
		elseif (array_search($type, self::$types) !== false) {
			$this->type = $type;
		}
		else {
			throw new Exception("Invalid response type.");
		}
	}
	
	public function get_type() {
		return $this->type;
	}
	
	public function set_data($data) {
		$this->data = $data;
	}
	
	public function get_data() {
		return $this->data;
	}
	
	public function to_JSON() {
		$data = array(
			"status"	=> $this->status,
			"type"		=> $this->type,
			"code"		=> $this->code,
			"data"		=> $this->data
		);
		
		$parsed = array();
		
		foreach ($data as $key => $value) {
			if ($value != null) {
				$parsed[$key] = $value;
			}
		}
		
		return json_encode($parsed);
	}
	
	public function render() {
		
		if ($this->type == "php") {
			throw new Exception("Response of type php can not be rendered. It's just to exchange data in the application.");
		}
		
		echo $this->parse();
	}
	
	public function parse() {
		if ($this->type == "php") {
			return $this->data;
		}
		
		if ($this->type == "application/json") {
			header("HTTP/1.1 200 OK");
			header("Content-type: application/json; charset=utf-8");
			self::$headers = true;
			return $this->to_JSON();
		}
		
		if ($this->status == "redirect") {
			$this->code 	= $this->code ? $this->code : 303;
			$message 	= self::$messages[$this->code];
			header("HTTP/1.1 {$this->code} {$message}");
			header("Location: " . $this->data);
			self::$headers = true;
			exit();
		}
		
		if ($this->status == "error") {
			$this->code 	= $this->code ? $this->code : 404;
			$message 	= self::$messages[$this->code];
			header("HTTP/1.1 {$this->code} {$message}");
			header("Content-type: " . $this->type . "; charset=utf-8");
			self::$headers = true;
			return $this->data;
		}
		
		if ($this->status == "ok") {
			$this->code 	= $this->code ? $this->code : 200;
			$message 	= self::$messages[$this->code];
			if ($this->get_type() != "widget") {
				header("HTTP/1.1 {$this->code} {$message}");
				header("Content-type: " . $this->type . "; charset=utf-8");
				self::$headers = true;
			}
			if ($this->type == self::$types["json"]) {
				return $this->to_JSON();
			}
			else {
				return $this->data;
			}
		}	
	}

	public function __toString() {
		if ($this->status == 'ok') {
			return $this->data;
		}
		else {
			return '';
		}
	}
	
	
	public function headers_already_sent() {
		return self::$headers;
	}
	
}
