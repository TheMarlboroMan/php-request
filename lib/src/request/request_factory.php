<?php
namespace request;

class request_exception_no_cli extends request_exception {
	public function __construct() {parent::__construct("cannot create web request in cli mode.");}
};

//https://stackoverflow.com/questions/41427359/phpunit-getallheaders-not-work/4142872
if (!function_exists('getallheaders')) {
    function getallheaders() {
	    $headers = [];
	    foreach ($_SERVER as $name => $value) {
	        if (substr($name, 0, 5) == 'HTTP_') {
	            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	        }
	    }
	    return $headers;
    }
}

class request_factory {

	public static function	from_apache_request() {

		if(php_sapi_name()=="cli") {
			throw new request_exception_no_cli;
		}

		$headers=getallheaders();
		$method=$_SERVER['REQUEST_METHOD'];
		$uri_host=isset($headers['Host']) ? $headers['Host'] : '';
		$protocol=$_SERVER['SERVER_PROTOCOL'];
		$scheme=$_SERVER['REQUEST_SCHEME'];
		$scheme=isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : null;
		if(!$scheme) {
		    $scheme=isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https' : 'http';
		}

		$uri=$scheme.'://'.$uri_host.$_SERVER['REQUEST_URI'];

		$query_string=$_SERVER['QUERY_STRING'];

		$body=self::can_get_body_from_input($headers, $method) ?
					\file_get_contents('php://input') :
					raw_request_body_tools::raw_body_from_php_parsed_data($_POST, $_FILES, $headers);

		return self::is_multipart($headers) ?
				new multipart_request($method, $uri, $query_string, $protocol, $headers, $body) :
				new urlencoded_request($method, $uri, $query_string, $protocol, $headers, $body);
	}

	private static function	is_multipart($_headers) {

			$header_value=raw_request_body_tools::get_content_type($_headers);
			return $header_value && false!==strpos($header_value, 'multipart/form-data');
	}

	//!Body cannot be retrieved from php://input when there is a multipart/form-data content type in a post request.
	private static function can_get_body_from_input(array $_headers, $_method) {

		$header_value=raw_request_body_tools::get_content_type($_headers);
		return ! ('POST'===strtoupper($_method) && null!==$header_value && false!==strpos($header_value, 'multipart/form-data'));
	}
}
