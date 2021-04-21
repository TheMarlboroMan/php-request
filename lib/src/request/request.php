<?php
namespace request;

abstract class request {

	public abstract function 			is_multipart();
	protected abstract function			body_to_string();

	private								$status=0;
	private								$method="";
	private 							$uri="";
	private 							$protocol="";
	private 							$query_string="";
	private 							$query_string_form=[];
	private								$headers=[];
	private								$raw_cookies="";
	private								$cookies=[];
	private                             $ip="";

	//!Returns the request client ip.
	public function                     get_ip() {

		return $this->ip;
	}

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

		return array_key_exists($_key, $this->headers);
	}

	//!Returns the array of headers.
	public function						get_headers() {

		return $this->headers;
	}

	//!Returns the given header. Throws if not present.
	public function						header($_key) {
		if(!$this->has_header($_key)) {
			throw new header_does_not_exist_exception($_key);
		}
		return $this->headers[$_key];
	}

	//!Convenience alias.
	public function						get_header($_key) {

		return $this->header($_key);
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
		return $this->raw_cookies;
	}

	//!Returns true if the cookie exists.
	public function						has_cookie($_key) {

		return is_array($this->cookies) && array_key_exists($_key, $this->cookies);
	}

	//Returns the given cookie.
	public function						cookie($_key, $_default=null) {

		if(!$this->has_cookie($_key)) {
			return $_default;
		}
		return $this->cookies[$_key];
	}

	//!Convenience alias.
	public function                     get_cookie($_key, $_default=null) {

		return $this->cookie($_key, $_default);
	}

	//!Sets the given cookie. Does not affect superglobals. Will have an
	//!effect on future requests.
	public function						set_cookie($_key, $_value, $_expiration_seconds, $_path=null, $_domain=null) {

		setcookie($_key, $_value, time()+$_expiration_seconds, $_path, $_domain);
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

	protected function 					__construct(
		/*string*/ $_ip,
		/*string*/ $_method,
		/*string*/ $_uri,
		/*string*/ $_query_string,
		/*string*/ $_protocol,
		array $_headers
	) {

		$this->ip=$_ip;
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

			if(false!==strpos($_item, '=')) {
				list($key, $value)=explode('=', $_item);
				$_carry[trim($key)]=trim(urldecode($value));
			}

			return $_carry;
		}, []);
	}
};
