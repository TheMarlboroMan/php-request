<?php
namespace request;

class request_header_does_not_exist_exception extends request_exception {
	public function __construct($_key) {
		parent::__construct("header ".$_key." does not exist");
	}
};

class cookie_does_not_exist_exception extends request_exception {
	public function __construct($_key) {
		parent::__construct("cookie ".$_key." does not exist");
	}
};

abstract class request {

	public abstract function 			is_multipart();
	protected abstract function			body_to_string();

	private								$status=null;
	private								$method=null;
	private 							$uri=null;
	private 							$protocol=null;
	private 							$query_string=null;
	private 							$query_string_form=null;
	private								$headers=null;
	private								$raw_cookies=null;
	private								$cookies=null;

	//!Returns the request method.
	public function						get_method() {
		return $this->method;
	}

	//!Returns the request URI
	public function						get_uri() {
		return $this->uri;
	}

	//!Returns the request URI
	public function						get_protocol() {
		return $this->protocol;
	}

	//!Returns the request URI without the query string attached.
	public function						get_uri_without_query_string() {

		$qstrlen=strlen($this->query_string);

		if($qstrlen) {
			return substr($this->uri, 0, -(++$qstrlen));
		}
		else {
			return $this->uri;
		}
	}

	//Header manipulation....

	//!Returns true if the headers contain the given key.
	public function 					has_header($_key) {
		return isset($this->headers[$_key]);
	}

	//!Returns the array of headers.
	public function						get_headers() {
		return $this->headers;
	}

	//!Returns the given header. Throws if not present.
	public function						header($_key) {
		if(!$this->has_header($_key)) {
			throw new request_header_does_not_exist_exception($_key);
		}
		return $this->headers[$_key];
	}

//Query string manipulation....

	//!Returns the raw query string.
	public function 					get_query_string() {
		return $this->query_string;
	}

	//!Returns the query string parsed as an array.
	public function 					get_query_string_form() {

		if(null==$this->query_string_form) {
			parse_str($this->query_string, $this->query_string_form);
		}
		return $this->query_string_form;
	}

	//!Attempts to retrieve $_key from a urlencoded query string. Returns
	//!$_default if not possible.
	public function						query($_key, $_default=null) {

		$data=$this->get_query_string_form();
		return $this->has_query($_key) ? $data[$_key] : $_default;
	}

	//!Returns true if the query string has the given key.
	public function						has_query($_key) {

		return isset($this->get_query_string_form()[$_key]);
	}


//Cookie manipulation....

	//!Returns the raw query string.
	public function						get_raw_cookies() {
		return $this->raw_cookies();
	}

	//!Returns true if the cookie exists.
	public function						has_cookie($_key) {
		return null!==$this->cookies && isset($this->cookies[$_key]);
	}

	//Returns the given cookie.
	public function						cookie($_key, $_default=null) {
		if(!$this->has_cookie($_key)) {
			return $_default;
		}
		return $this->cookies[$_key];
	}

	//!Sets the given cookie. Does not affect superglobals. Will have an
	//!effect on future requests.
	public function						set_cookie($_key, $_value, $_expiration_seconds, $_path) {

		setcookie($_key, $_value, time()+$_expiration_seconds, $_path);
		if($this->has_cookie($_key)) {
			$this->cookies[$_key]=$_value;
		}

		$this->rebuild_raw_cookie_string();
	}

	//!Sets the given cookie. Does not affect superglobals. Will have an
	//!effect on future requests.
	public function						unset_cookie($_key) {

		setcookie($_key, null, time());
		if($this->has_cookie($_key)) {
			unset($this->cookies[$_key]);
		}

		$this->rebuild_raw_cookie_string();
	}

	//!Parses the request as a string.
	public function						to_string() {

		$headers='';
		foreach($this->headers as $k => $v) {
			$headers.=$k.':'.$v.PHP_EOL;
		}

		return <<<R
{$this->status}
{$headers}
{$this->body_to_string()}
R;
	}

	protected function 					__construct($_method, $_uri, $_query_string, $_protocol, array $_headers) {

		$this->method=$_method;
		$this->uri=$_uri;
		$this->protocol=$_protocol;
		$this->query_string=$_query_string;
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;
		$this->cookies=[];

		//TODO: Mind the casing!.
		if(isset($this->headers['Cookie'])) {
			$this->load_cookies($this->headers['Cookie']);
		}

	}

	private function					rebuild_raw_cookie_string() {

		$this->raw_cookies=implode(';', $this->cookies);
	}

	private function					load_cookies($_raw_cookie_string) {

		$this->raw_cookies=$_raw_cookie_string;
		$this->cookies=array_reduce(explode(';', $this->raw_cookies), function($_carry, $_item) {

			list($key, $value)=explode('=', $_item);
			$_carry[trim($key)]=trim(urldecode($value));
			return $_carry;
		}, []);
	}
};
