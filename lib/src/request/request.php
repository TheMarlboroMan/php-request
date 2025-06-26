<?php
declare(strict_types=1);
namespace request;

abstract class request {

	public abstract function 			is_multipart(): bool;
	protected abstract function			body_to_string(): string;

	private string $status=""; //status line, mind you.
	private string $method="";
	private string $uri="";
	private string $protocol="";
	private string $query_string="";
	/** @var array<string,string> */
	private array $query_string_form=[];
	/** @var array<string,string> */
	private array $headers=[];
	/** @var array<string,string> */
	private array $ci_headers_to_headers_map=[];
	private string $raw_cookies="";
	/** @var array<string,string> */
	private array $cookies=[];
	private string $ip="";
	private bool $parsed_query_string_form=false;

/**
*Returns the request client ip.
*/
	public function get_ip() : string {

		return $this->ip;
	}

/**
*Returns the request method.
*/
	public function get_method() : string {
		return $this->method;
	}

/**
*Returns the request URI
*/
	public function get_uri() : string {
		return $this->uri;
	}

/**
*Returns the protocol
*/
	public function get_protocol() : string {
		return $this->protocol;
	}

/**
*Returns the request URI without the query string attached.
*/
	public function get_uri_without_query_string() : string {

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
*/
	public function has_header(
		string $_key
	) : bool {

		$lckey=strtolower($_key);
		return array_key_exists($lckey, $this->ci_headers_to_headers_map);
	}

/**
*Returns the array of headers.
*@return array<string, string>
*/
	public function get_headers() : array {

		return $this->headers;
	}

/**
*Returns the array of cookies.
*@return array<string, string>
*/
	public function get_cookies() : array {

		return $this->cookies;
	}

/**
*Returns the given header. Throws if not present.
*/
	public function header(
		string $_key
	) : string {

		if(!$this->has_header($_key)) {

			throw new header_does_not_exist_exception($_key);
		}

		$lckey=strtolower($_key);
		$key=$this->ci_headers_to_headers_map[$lckey];
		return $this->headers[$key];
	}

/**
*Convenience alias.
*/
	public function get_header(
		string $_key
	) : string {

		return $this->header($_key);
	}

//Query string manipulation....

/**
*Returns the raw query string.
*/
	public function get_query_string() : string {

		return $this->query_string;
	}

/**
*Returns the query string parsed as an array.
*@return array<string, string>
*/
	public function 					get_query_string_form() : array {

		if(!$this->parsed_query_string_form) {

			//@phpstan-ignore-next-line sorry phpstan, but I don't see how parse_str writes anything else...
			parse_str($this->query_string, $this->query_string_form);
			$this->parsed_query_string_form=true;
		}

		return $this->query_string_form;
	}

/**
*Attempts to retrieve $_key from a urlencoded query string. Returns
*$_default if not possible.
*/
	public function query(
		string $_key, 
		string $_default=""
	) : string {

		$data=$this->get_query_string_form();
		return $this->has_query($_key) ? $data[$_key] : $_default;
	}

/**
*Returns true if the query string has the given key.
*/
	public function has_query(
		string $_key
	) : bool {

		return isset($this->get_query_string_form()[$_key]);
	}


//Cookie manipulation....

/**
*Returns the raw query string.
*@return string
*/
	public function get_raw_cookies() : string {

		return $this->raw_cookies;
	}

/**
*Returns true if the cookie exists.
*/
	public function has_cookie(
		string $_key
	) : bool {

		return array_key_exists($_key, $this->cookies);
	}

/**
*Returns the given cookie.
*/
	public function cookie(
		string $_key, 
		string $_default=""
	) : string {

		if(!$this->has_cookie($_key)) {

			return $_default;
		}
		return $this->cookies[$_key];
	}

/**
*Convenience alias.
*/
	public function get_cookie(
		string $_key, 
		string $_default=""
	) : string {

		return $this->cookie($_key, $_default);
	}

/**
*Sets the given cookie. Does not affect superglobals. Will have an
*effect on future requests. Set -1 to expiration seconds for a session cookie.
*/
	public function set_cookie(
		string $_key, 
		string $_value, 
		int $_expiration_seconds=0, 
		string $_path="", 
		string $_domain=""
	) : self {

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
*/
	public function unset_cookie(
		string $_key
	) : self {

		setcookie($_key, "", time());
		if($this->has_cookie($_key)) {
			unset($this->cookies[$_key]);
		}

		$this->rebuild_raw_cookie_string();
		return $this;
	}

/**
*Parses the request as a string.
*/
	public function to_string() : string {

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
*@param array<string, string> $_headers
*@param array<string, string> $_cookies
*/
	protected function __construct(
		string $_ip,
		string $_method,
		string $_uri,
		string $_query_string,
		string $_protocol,
		array $_headers,
		array $_cookies
	) {

		$this->ip=$_ip;
		$this->method=$_method;
		$this->uri=$_uri;
		$this->protocol=$_protocol;
		$this->query_string=$_query_string;
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;
		$this->cookies=$_cookies;

		foreach($this->headers as $key => $value) {

			$this->ci_headers_to_headers_map[strtolower($key)]=$key;
		}
	}

/**
*@return void
*/
	private function rebuild_raw_cookie_string() : void {

		$this->raw_cookies=implode(';', $this->cookies);
	}
};
