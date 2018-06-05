<?php
namespace request;

class request_exception extends \Exception {
	public function __construct($_msg=null) {
		$msg=$_msg ? "request exception : ".$_msg : "request exception";
		parent::__construct($msg);
	}
};

class request_exception_no_cli extends request_exception {
	public function __construct() {parent::__construct("cannot create web request in cli mode.");}
};

class request {

	//TODO: Add cookies

	private	$status=null;
	private	$method=null;
	private $uri=null;
	private $query_string=null;
	private	$body=null;	//TODO: Likely a reference to bodies[0]...
	private $bodies=[];	//Multiple body parts, you see...
	private	$headers=null;

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
			//TODO: There should be an option to do it less expensively, without recombining file data.
			self::can_get_body_from_input($headers, $method) ?
				file_get_contents('php://input') :
				self::raw_body_from_php_parsed_data($_POST, $_FILES, $headers));
	}

	public function		get_method() {
		return $this->method;
	}

	public function 	get_query_string() {
		return $this->query_string;
	}

	//TODO: Should actually be another class.
	//TODO: What about multiple bodies???.
	public function		get_body() {

		if($this->is_multipart()) {
			throw new request_exception('cannot access single body for multipart requests');
		}
		return $this->body;
	}

	//TODO: Should actually be another class.
	//TODO: What about multiple bodies???
	//TODO: Cache.
	public function 	get_body_form() {

		if($this->is_multipart()) {
			throw new request_exception('cannot access single body for multipart requests');
		}
		return self::as_query_string($this->body);
	}

	//TODO: Should actually be another class.
	//TODO: public function get_bodies() {} //Throw if not multipart.
	//TODO: public function get_body_index($v) {} //Throw if not multipart.
	//TODO: Cache.
	public function 	get_query_string_form() {

		return self::as_query_string($this->query_string);
	}


	//TODO: This should be another class.
	public function 	is_multipart() {

		return isset($this->headers['Content-Type']) && false!==strpos($this->headers['Content-Type'], 'multipart/form-data');
	}

	private function 	__construct($_method, $_uri, $_protocol, array $_headers, $_body) {

		$this->method=$_method;
		$this->uri=$_uri;
		$this->query_string=self::query_string_from_uri($this->uri);
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;

		//TODO: These should be different classes.
		if($this->is_multipart()) {
			//TODO: Mind the letter casing...
			self::parse_multipart_bodies($this->bodies, $_body, self::boundary_from_content_type_header($this->headers['Content-Type']));
		}
		else {
			$this->body=$_body;
		}
	}

	////////////////////////////////////////////////////////////////////////

	private static function	parse_multipart_bodies(array &$_bodies, $_body, $_boundary) {

		$end_boundary=$_boundary.'--';
		$current_body='';

		$tk=new string_tokenizer($_body, PHP_EOL);

		while(!$tk->is_done()) {
			$line=$tk->next();

			if($_boundary==$line) {
				if(strlen($current_body)) {
					$_bodies[]=request_body::from_raw_part($current_body);
					$current_body='';
				}
			}
			else if($end_boundary==$line) {
				$_bodies[]=request_body::from_raw_part($current_body);
				break;
			}

			$current_body.=$line.PHP_EOL;
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

	//!Returns $_str as a query string, useful to parse post or get data to familiar PHP forms.
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

	private static function	boundary_from_content_type_header($_header) {

		//TODO: Mind the letter casing.
		//Lol... Explode the header by ;, the second part is the boundary=xxxx part. Explode that by = and return the second part.
		return 	trim(explode('=', explode(';',$_header, 2)[1])[1]);
	}

	//!Converts the post and files superglobals into their original raw forms.
	private static function raw_body_from_php_parsed_data(array $_post, array $_files, array $_headers) {

		$boundary=self::boundary_from_content_type_header($_headers['Content-Type']);

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
		//TODO: There should be an option to be a little less hardcore, like just grab $_FILES.

		$file_data=null;
		foreach($_files as $k => $v) {

			//TODO: Will lie about the contents of the stuff... Better to see how it is done in raw.
			if(!file_exists($v['tmp_name'])) {
				continue;
			}

			$file_body=file_get_contents($v['tmp_name']);
			$file_data.=<<<R
{$boundary}
Content-Disposition: form-data; name="{$k}"; filename="{$v['name']}"
Content-Type: {$v['type']}

{$file_body}

R;
		}

		return <<<R
{$post_data}{$file_data}{$boundary}--
R;
	}
};
