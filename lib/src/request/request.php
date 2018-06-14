<?php
namespace request;

class request_header_does_not_exist_exception extends request_exception {
	public function __construct($_key) {
		parent::__construct("header ".$_key." does not exist");
	}
};

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

	public function		get_headers() {
		return $this->headers;
	}

	public function		get_uri() {
		return $this->uri;
	}

	public function		get_uri_without_query_string() {

		$qstrlen=strlen($this->query_string);

		if($qstrlen) {
			return substr($this->uri, 0, -(++$qstrlen));
		}
		else {
			return $this->uri;
		}
	}

	public function 	header_exists($_key) {
		return isset($this->headers[$_key]);
	}

	public function		get_header($_key) {
		if(!$this->header_exists($_key)) {
			throw new request_header_does_not_exist_exception($_key);
		}
		return $this->headers[$_key];
	}

	public function 	get_query_string_form() {

		if(null==$this->query_string_form) {
			parse_str($this->query_string, $this->query_string_form);
		}
		return $this->query_string_form;
	}

	protected function 	__construct($_method, $_uri, $_query_string, $_protocol, array $_headers) {

		$this->method=$_method;
		$this->uri=$_uri;
		$this->query_string=$_query_string;
		$this->status="{$_method} {$_uri} {$_protocol}";
		$this->headers=$_headers;
		//TODO: Cookies!.
	}
};
