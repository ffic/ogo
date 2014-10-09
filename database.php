<?php namespace ogo;

class Database {
	private $connection 	 = null;
	private $connection_data = null;
	private $query		 = null;
	private $statement	 = null;
	private $result		 = null;
	private $connected	 = false;
	private $params		 = null;
	private $offset		 = null;
	private $total           = null;
	private $driver          = null;		

	public function __construct($connection_data=null) {
		$this->connection_data = (array) $connection_data;
	}
	
	public function connect($connection_data=null) {
		if ($connection_data) {
			$this->connection_data = (array) $connection_data;
		}
		
		$dsn	  = $this->connection_data['dsn'];
		$user     = $this->connection_data['user'];
		$password = $this->connection_data['password'];
		
		try {
			$this->connection = new \PDO($dsn, $user, $password);
			$this->connected = true;
			$this->driver = $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
		} catch (PDOException $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function disconnect() {
		$this->connection = null;
		$this->connected = false;
	}
	
	public function prepare($query, $params=null) {
		if (!$this->connected) {
			$this->connect();
		}
		$this->offset = null;
		$this->total  = null;
		
		$this->query = $query;
		
		if ($params != null) {
			$this->bind($params);
		}
		
		return $this;
	}
	
	public function bind($params) {
		if (is_array($params)) {
			$this->params = [];
			foreach ((array) $params as $key => $value) {
				$this->params[':' . preg_replace('/^\:/', '', $key)] = $value;
			}
		}
		else {
			$this->params = null;
		}
		return $this;
	}

	public function execute($query=null, $params=null) {
		if (!$this->connected) {
			$this->connect();
		}
		if ($query != null) {
			$this->prepare($query, $params);
		}
		try {
			$query = $this->parse_query_with_limit();
			$this->statement = $this->connection->prepare($query);
			foreach ((array) $this->params as $key => $value) {
				$this->statement->bindValue($key, $value);
			}
			$b = $this->statement->execute();
			if ($b === false) {
				$info = $this->statement->errorInfo();
				$this->error("Database exception.\n\n" .$info[2]);
			}
			$this->result = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
			return $this->result;
			
		} catch (PDOException $e) {
			$this->error($e->getMessage());
		}
	}
	
	public function limit($offset, $total = null) {
		if ($total == null) {
			$total = $offset;
			$offset = 0;
		}
		$this->offset = (int) $offset;
		$this->total = (int) $total;
		return $this;

	}

	public function get_query() {
		$query = $this->query;
		foreach ($this->params as $key => $value) {
			$query = preg_replace('/' . $key . '/', "'" . $value . "'", $query);
		}
		return $query;
	}

	public function insert($table, $data) {
		
		$fields = array();
		$values = array();
		$esc = array();
		$index = 0;
		foreach ($data as $key => $value) {
			$fields[] = $key;
			$esc[] = ":v" . $index;
			$values["v" . $index] = $value;
			$index++;
		}
		$fields = implode(",", $fields);
		$esc = implode(",", $esc);
		
		$this->prepare("INSERT INTO {$table} ({$fields}) VALUES ({$esc})", $values)->execute();
		
		return $this->get_insert_id();
	}
	
	public function update($table, $data, $condition) {
		$fields = array();
		$values = array();
		$index = 0;
		foreach ($data as $key => $value) {
			$fields[] = $key . "=:v" . $index;
			$values["v" . $index] = $value;
			$index++;
		}
		$string = implode(",", $fields);
		
		$this->prepare("update {$table} set {$string} where {$condition}", $values)->execute();
	}

	public function get_result() {
		return $this->result;
	}
	
	public function get_insert_id() {
		return $this->connection->lastInsertId();
	}
	
	public function get_pdo() {
		return $this->connection;
	}
	
	public function escape($string) {
		if (!$this->connected) {
			$this->connect();
		}
		return $this->connection->quote($string);
	}
	
	public function error($message) {
	
		$info = "";
		$info .= $message . "\n";
		$backtrace = debug_backtrace();
		foreach($backtrace as $b) {
			$info .= "Line: ".$b['line']." File: ".$b["file"] . "\n";	
		}
		
		throw new Exception($info);
	}
	
	private function parse_query_with_limit() {
		$query = $this->query;

		if ($this->offset !== null && $this->total !== null) {
			switch ($this->driver) {
				case 'mysql':
					$query = trim($query) . ' LIMIT ' . $this->offset . ', ' . $this->total;
				break;
			}
		}
		return $query;
	}
}
