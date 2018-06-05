<?php
namespace tools;

abstract class request {

	public abstract function 	is_multipart();

	//TODO: Add cookies... They come from the headers, you know.
	private	$status=null;
	private	$method=null;
	private $uri=null;
	private $query_string=null;
	private $query_string_form=null;
	private	$headers=null;

	public function		get_method() {
		return $this->method;
	}

	public function 	get_query_string() {
		return $this->query_string;
	}

	public function 	get_query_string_form() {

		if(null==$this->query_string_form) {
			parse_str($this->query_string, $this->query_string_form);
		}
		return $this->query_string_form;
	}

	protected function 	__construct($_method, $_uri, $_protocol, array $_headers) {

		$this->method=$_method;
		$this->uri=$_uri;
		$this->query_string=self::query_string_from_uri($this->uri);
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;
		//TODO: Cookies!.
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
};
