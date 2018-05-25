<?php
namespace tools;

class request_exception extends \Exception {
	public function __construct($_msg=null) {
		$msg=$_msg ? "request exception : ".$_msg : "request exception";
		parent::__construct($msg);
	}
};

class request_exception_no_cli extends request_exception {
	//TODO: Which alternative constructors???
	public function __construct() {parent::__construct("cannot create web request in cli mode. Use alternative constructors.");}
};

class request {

	private	$status=null;
	private	$method=null;
	private $uri=null;
	private $query_string=null;
	private	$body=null;
	private	$headers=[];

	public static function	from_apache_request() {

		if(php_sapi_name()=="cli") {
			throw new request_exception_no_cli;
		}

		return new request($_SERVER['REQUEST_METHOD'], 
			$_SERVER['REQUEST_URI'],
			$_SERVER['SERVER_PROTOCOL'],
			getallheaders(),
			file_get_contents('php://input'));
	}

	public function	get_method() {return $this->method;}
	public function get_query_string() {return $this->query_string;}
	public function	get_body() {return $this->body;}
	public function get_query_string_form() {return self::as_query_string($this->query_string);}
	public function get_body_form() {return self::as_query_string($this->body);}

	private function __construct($_method, $_uri, $_protocol, array $_headers, $_body) {

		$this->method=$_method;
		$this->uri=$_uri;
		$this->query_string=self::query_string_from_uri($this->uri);
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;
		$this->body=$_body;
	}

	private static function query_string_from_uri($_uri) {

		if(false===strpos($_uri, '?')) {
			return null;
		}
		else {
			return explode('?', $_uri, 2)[1];
		}
	}

	private static function as_query_string($_str) {

		$result=[];
		parse_str($_str, $result);
		return $result;
	}
};
