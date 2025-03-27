<?php
namespace request;


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

/**
*@return request
*/
	public static function	from_apache_request(
		factory_options $_options=null
	) {

		if(null===$_options) {

			$_options=new factory_options();
		}

		if(php_sapi_name()=="cli") {
			
			throw new no_source_exception;
		}

		$headers=getallheaders();
		$method=$_SERVER['REQUEST_METHOD'];
		$uri_host=isset($headers['Host']) ? $headers['Host'] : '';
		$protocol=$_SERVER['SERVER_PROTOCOL'];
		$scheme=isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : null;
		$ip=self::extract_ip();


		if(!$scheme) {
		    $scheme=isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https' : 'http';
		}

		$uri=$scheme.'://'.$uri_host.$_SERVER['REQUEST_URI'];

		$query_string=$_SERVER['QUERY_STRING'];

		$body=self::can_get_body_from_input($headers, $method) ?
					\file_get_contents('php://input') :
					raw_request_body_tools::raw_body_from_php_parsed_data($_POST, $_FILES, $headers);

		//TODO: Mind the casing!.
		$cookies=array_key_exists("Cookie", $headers)
			? self::load_cookies($headers["Cookie"], $_options)
			: [];

		return self::is_multipart($headers) ?
				new multipart_request($ip, $method, $uri, $query_string, $protocol, $headers, $body, $cookies) :
				new urlencoded_request($ip, $method, $uri, $query_string, $protocol, $headers, $body, $cookies);
	}

/**
*@param string $_raw_cookie_string
*@return array<string, string>
*/
	private static function load_cookies(
		$_raw_cookie_string,
		factory_options $_options
	) {

		return array_reduce(
			explode(';', $_raw_cookie_string), 
			function($_carry, $_item) {

				if(false!==strpos($_item, '=')) {

					list($key, $value)=explode('=', $_item, 2);

					if($_options->trim_cookies) {
						$value=trim($value);
					}

					if($_options->urldecode_cookies) {

						$value=urldecode($value);
					}

					$_carry[trim($key)]=$value;
				}

				return $_carry;
			}, 
			[]
		);
	}

/**
*@param array<string, string> $_headers
*@return bool
*/
	private static function	is_multipart($_headers) {

			$header_value=raw_request_body_tools::get_content_type($_headers);
			return $header_value && false!==strpos($header_value, 'multipart/form-data');
	}

	//!Body cannot be retrieved from php://input when there is a multipart/form-data content type in a post request.
	private static function can_get_body_from_input(array $_headers, $_method) {

		$header_value=raw_request_body_tools::get_content_type($_headers);
		return ! ('POST'===strtoupper($_method) && null!==$header_value && false!==strpos($header_value, 'multipart/form-data'));
	}

/**
*@return string
*/
	private static function extract_ip() {

		foreach(["HTTP_X_FORWARDED_FOR", "REMOTE_ADDR"] as $key) {

			if(isset($_SERVER[$key]) && strlen($_SERVER[$key])) {

				return $_SERVER[$key];
			}
		}

		return "???";
	}
}
