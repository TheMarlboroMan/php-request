<?php
namespace request;

abstract class request {

	/** @return bool */
	public abstract function 			is_multipart();
	/** @return string */
	protected abstract function			body_to_string();

	/** @var int */
	private	$status=0;
	/** @var string */
	private	$method="";
	/** @var string */
	private $uri="";
	/** @var string */
	private $protocol="";
	/** @var string */
	private $query_string="";
	/** @var ?array<string,string> */
	private $query_string_form=null;
	/** @var array<string,string> */
	private	$headers=[];
	/** @var array<string,string> */
	private	$ci_headers_to_headers_map=[];
	/** @var string */
	private	$raw_cookies="";
	/** @var array<string,string> */
	private	$cookies=[];
	/** @var string */
	private $ip="";

/**
*@return string
*Returns the request client ip.
*/
	public function get_ip() {

		return $this->ip;
	}

/**
*@return string
*Returns the request method.
*/
	public function get_method() {
		return $this->method;
	}

/**
*@return string
*Returns the request URI
*/
	public function get_uri() {
		return $this->uri;
	}

/**
*@return string
*Returns the protocol
*/
	public function get_protocol() {
		return $this->protocol;
	}

/**
*@return string
*Returns the request URI without the query string attached.
*/
	public function get_uri_without_query_string() {

		$qstrlen=strlen($this->query_string);

		if($qstrlen) {
			return substr($this->uri, 0, -(++$qstrlen));
		}
		else {
			return $this->uri;
		}
	}

	//Header manipulation....

/**
*Returns true if the headers contain the given key.
*@param string $_key
*@return bool
*/
	public function has_header($_key) {

		$lckey=strtolower($_key);
		return array_key_exists($lckey, $this->ci_headers_to_headers_map);
	}

/**
*Returns the array of headers.
*@return array<string, string>
*/
	public function get_headers() {

		return $this->headers;
	}

/**
*Returns the array of cookies.
*@return array<string, string>
*/
	public function get_cookies() {

		return $this->cookies;
	}

/**
*Returns the given header. Throws if not present.
*@param string $_key
*@return string
*/
	public function header($_key) {

		if(!$this->has_header($_key)) {

			throw new header_does_not_exist_exception($_key);
		}

		$lckey=strtolower($_key);
		$key=$this->ci_headers_to_headers_map[$lckey];
		return $this->headers[$key];
	}

/**
*Convenience alias.
*@param string $_key
*@return string
*/
	public function get_header($_key) {

		return $this->header($_key);
	}

//Query string manipulation....

/**
*Returns the raw query string.
*@return string
*/
	public function get_query_string() {

		return $this->query_string;
	}

/**
*Returns the query string parsed as an array.
*@return array<string, string>
*/
	public function 					get_query_string_form() {

		if(null==$this->query_string_form) {

			parse_str($this->query_string, $this->query_string_form);
		}

		return $this->query_string_form;
	}

/**
*Attempts to retrieve $_key from a urlencoded query string. Returns
*$_default if not possible.
*@param string $_key
*@param string $_default
*@return string
*/
	public function query($_key, $_default="") {

		$data=$this->get_query_string_form();
		return $this->has_query($_key) ? $data[$_key] : $_default;
	}

/**
*Returns true if the query string has the given key.
*@param string $_key
*@return bool
*/
	public function has_query($_key) {

		return isset($this->get_query_string_form()[$_key]);
	}


//Cookie manipulation....

/**
*Returns the raw query string.
*@return string
*/
	public function get_raw_cookies() {

		return $this->raw_cookies;
	}

/**
*Returns true if the cookie exists.
*@param string $_key
*@return bool
*/
	public function has_cookie($_key) {

		return is_array($this->cookies) && array_key_exists($_key, $this->cookies);
	}

/**
*Returns the given cookie.
*@param string $_key
*@param string $_default
*@return string
*/
	public function cookie($_key, $_default="") {

		if(!$this->has_cookie($_key)) {

			return $_default;
		}
		return $this->cookies[$_key];
	}

/**
*Convenience alias.
*@param string $_key
*@param string $_default
*@return string
*/
	public function get_cookie($_key, $_default="") {

		return $this->cookie($_key, $_default);
	}

/**
*Sets the given cookie. Does not affect superglobals. Will have an
*effect on future requests. Set -1 to expiration seconds for a session cookie.
*@param string $_key
*@param string $_value
*@param int $_expiration_seconds
*@param string $_path
*@param string $_domain
*@return self
*/
	public function set_cookie($_key, $_value, $_expiration_seconds=0, $_path="", $_domain="") {

		$expiration_seconds=-1===$_expiration_seconds
			? 0
			: time()+$_expiration_seconds;

		setcookie($_key, $_value, $expiration_seconds, $_path, $_domain);
		if($this->has_cookie($_key)) {
			$this->cookies[$_key]=$_value;
		}

		$this->rebuild_raw_cookie_string();
		return $this;
	}

/**
*Removes the given cookie. Does not affect superglobals. Will have an
*effect on future requests.
*@param string $_key
*@return self
*/
	public function unset_cookie($_key) {

		setcookie($_key, "", time());
		if($this->has_cookie($_key)) {
			unset($this->cookies[$_key]);
		}

		$this->rebuild_raw_cookie_string();
		return $this;
	}

/**
*Parses the request as a string.
*@return string
*/
	public function to_string() {

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

/**
*@param string $_ip
*@param string $_method
*@param string $_uri
*@param string $_query_string
*@param string $_protocol
*@param array<string, string> $_headers
*/
	protected function __construct(
		$_ip,
		$_method,
		$_uri,
		$_query_string,
		$_protocol,
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

		foreach($this->headers as $key => $value) {

			$this->ci_headers_to_headers_map[strtolower($key)]=$key;
		}
	}

/**
*@return void
*/
	private function rebuild_raw_cookie_string() {

		$this->raw_cookies=implode(';', $this->cookies);
	}

/**
*@param string $_raw_cookie_string
*@return void
*/
	private function load_cookies($_raw_cookie_string) {

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
