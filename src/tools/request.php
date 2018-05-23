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

class body {

	private $headers=[];
	private $body=null;
	private $name=null; //After content disposition, which is always form-data.
//	private $is_a_file=null; //TODO: public function is_file();
	private $filename=null;

/*
------------------------1cd9f201078b387e
Content-Disposition: form-data; name="userid"

1
------------------------1cd9f201078b387e
Content-Disposition: form-data; name="filecomment"

This is an image file
------------------------1cd9f201078b387e
Content-Disposition: form-data; name="data"; filename="hola.txt"
Content-Type: text/plain

HOLA

*/	

	//TODO.
}

class request {

	private	$status=null;
	private	$method=null;
	private $uri=null;
	private $query_string=null;
	private	$body=null;
	private $bodies=[];	//Multiple body parts, you see...
	private	$headers=[];

	public static function	from_apache_request() {

		if(php_sapi_name()=="cli") {
			throw new request_exception_no_cli;
		}

		$headers=getallheaders();
		$method=$_SERVER['REQUEST_METHOD'];

		return new request($method, 
			$_SERVER['REQUEST_URI'],
			$_SERVER['SERVER_PROTOCOL'],
			$headers,
			self::can_get_body_from_input($headers, $method) ? file_get_contents('php://input') : self::raw_body_from_php_parsed_data($_POST, $_FILES, $headers));
	}

	public function	get_method() {return $this->method;}

	public function get_query_string() {return $this->query_string;}

	//TODO: ACTUALLY: SEPARATE IN TWO: THE MULTIPART AND THE NON MULTIPART....

	//TODO: What about multiple bodies???.
	public function	get_body() {
		if($this->is_multipart()) {
			throw new request_exception('cannot access single body for multipart requests');
		}
		return $this->body;
	}

	//TODO: What about multiple bodies???
	public function get_body_form() {return self::as_query_string($this->body);}

	//TODO: public function get_bodies() {} //Throw if not multipart.
	//TODO: public function get_body_index($v) {} //Throw if not multipart.

	public function get_query_string_form() {
		if($this->is_multipart()) {
			throw new request_exception('cannot access single body for multipart requests');
		}
		return self::as_query_string($this->query_string);
	}



	public function is_multipart() {
		//TODO: Mind the casing.
		return isset($this->headers['Content-Type']) && false!==strpos($this->headers['Content-Type'], 'multipart/form-data');
	}

	private function __construct($_method, $_uri, $_protocol, array $_headers, $_body) {

		$this->method=$_method;
		$this->uri=$_uri;
		$this->query_string=self::query_string_from_uri($this->uri);
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;

		if(this->is_multipart()) {
			die('sorry, multipart/form-data is not yet implemented!');
			//TODO: Each body part should be a instance of "body", with headers and shit.
			//TODO: A facility method for compacting non-file multipart bodies into a query string could be added.
			//TODO: A facility method for compacting multipart files into other data could be added.
			//TODO: $body should be left to null and throw if accessed.
		}
		else {
			$this->body=$_body;
		}
	}

	////////////////////////////////////////////////////////////////////////

	//!Obtains the query string part from the URI. Might as well get it from $_SERVER.
	private static function query_string_from_uri($_uri) {

		if(false===strpos($_uri, '?')) {
			return null;
		}
		else {
			return explode('?', $_uri, 2)[1];
		}
	}

	//Returns $_str as a query string, useful to parse post or get data to familiar PHP forms.
	private static function as_query_string($_str) {

		$result=[];
		parse_str($_str, $result);
		return $result;
	}

	//!Body cannot be retrieved from php://input when there is a multipart/form-data content type in a post request.
	private static function can_get_body_from_input(array $_headers, $method) {

		//TODO: What about headers upper and lowercasing...
		return ! ('POST'===strtoupper($method) && isset($_headers['Content-Type']) && false!==strpos($_headers['Content-Type'], 'multipart/form-data'));
	}

	//!Converts the post and files superglobals into their original raw forms.
	private static function raw_body_from_php_parsed_data($_post, $_files, $_headers) {

		//TODO: Mind the letter casing.
		$content_type=$_headers['Content-Type'];
		//Lol... Explode the header by ;, the second part is the boundary=xxxx part. Explode that by = and return the second part.
		$boundary=trim(explode('=', explode(';',$_headers['Content-Type'], 2)[1])[1]);

		//First the post data...
		$post_data=null;
		foreach($_post as $k => $v) {
			$post_data.=<<<R
{$boundary}
Content-Disposition: form-data; name="{$k}"

{$v}

R;
		}

		//TODO: What about invalid files???
		//TODO: Try sending an empty file!.

		$file_data=null;
		foreach($_files as $k => $v) {
			$file_body=file_get_contents($v['tmp_name']);
			$file_data.=<<<R
{$boundary}
Content-Disposition: form-data; name="{$k}"; filename="{$v['name']}"
Content-Type: {$v['type']}

{$file_body}

R;
		}

		$raw_body=<<<R
{$post_data}{$file_data}{$boundary}--
R;

		return $raw_body;
	}
};
